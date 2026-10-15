<?php

use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\TahunAjaran;
use App\Models\Ujian;
use App\Models\User;

beforeEach(function (): void {
    $this->tahunAjaran = TahunAjaran::factory()->create(['active' => true]);
    $this->matpel = Matpel::factory()->create(['name' => 'Matematika']);
    $this->kelas = Kelas::factory()->create(['nama' => 'X-RPL-1']);

    $this->guru = Guru::factory()->create();
    $this->guruUser = User::factory()->create(['guru_id' => $this->guru->id]);

    $this->guruKelas = GuruKelas::factory()->create([
        'guru_id' => $this->guru->id,
        'kelas_id' => $this->kelas->id,
        'matpel_id' => $this->matpel->id,
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'aktif' => true,
    ]);
});

test('wizard langkah 1 membuat draft ujian mode santai', function (): void {
    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.wizard.info'), [
            'mode' => 'santai',
            'guru_kelas_id' => $this->guruKelas->id,
            'kategori' => 'kuis',
            'judul' => 'Kuis Wizard',
            'durasi_menit' => 30,
        ])
        ->assertRedirect();

    $ujian = Ujian::where('judul', 'Kuis Wizard')->first();
    expect($ujian)->not->toBeNull()
        ->and($ujian->status)->toBe('draft')
        ->and($ujian->kategori)->toBe('kuis')
        ->and($ujian->tata_tertib)->not->toBeEmpty();
});

test('wizard mode santai memaksa kategori non-besar', function (): void {
    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.wizard.info'), [
            'mode' => 'santai',
            'guru_kelas_id' => $this->guruKelas->id,
            'kategori' => 'usbk', // tidak boleh di mode santai
            'judul' => 'Kuis Paksa',
            'durasi_menit' => 30,
        ])
        ->assertRedirect();

    expect(Ujian::where('judul', 'Kuis Paksa')->first()->kategori)->toBe('kuis');
});

test('wizard mode resmi default ke uts bila kategori kecil', function (): void {
    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.wizard.info'), [
            'mode' => 'resmi',
            'guru_kelas_id' => $this->guruKelas->id,
            'kategori' => 'kuis', // tidak boleh di mode resmi
            'judul' => 'UTS Wizard',
            'durasi_menit' => 90,
        ])
        ->assertRedirect();

    expect(Ujian::where('judul', 'UTS Wizard')->first()->kategori)->toBe('uts');
});

test('wizard langkah token mengisi token, tata tertib, dan kkm', function (): void {
    $ujian = Ujian::factory()->create([
        'guru_kelas_id' => $this->guruKelas->id,
        'guru_id' => $this->guru->id,
        'kategori' => 'usbk',
        'status' => 'draft',
    ]);

    $this->actingAs($this->guruUser)
        ->post(route('app.guru.ujian.wizard.token', $ujian), [
            'tata_tertib' => "1. Jujur.\n2. Dilarang nyontek.",
            'kkm' => 75,
        ])
        ->assertRedirect();

    $ujian->refresh();
    expect($ujian->token)->not->toBeNull()
        ->and($ujian->token_released_at)->not->toBeNull()
        ->and($ujian->tata_tertib)->toContain('Jujur')
        ->and($ujian->kkm)->toBe(75);
});

test('guru lain tidak bisa akses wizard ujian orang', function (): void {
    $ujian = Ujian::factory()->create([
        'guru_kelas_id' => $this->guruKelas->id,
        'guru_id' => $this->guru->id,
        'kategori' => 'kuis',
        'status' => 'draft',
    ]);

    $guruLain = Guru::factory()->create();
    $guruLainUser = User::factory()->create(['guru_id' => $guruLain->id]);

    $this->actingAs($guruLainUser)
        ->get(route('app.guru.ujian.wizard.langkah', [$ujian, 2]).'?mode=santai')
        ->assertNotFound();
});
