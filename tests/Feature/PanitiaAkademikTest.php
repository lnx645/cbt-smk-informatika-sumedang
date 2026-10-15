<?php

use App\Models\BeritaAcaraUjian;
use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\RuangUjian;
use App\Models\SesiUjian;
use App\Models\TahunAjaran;
use App\Models\Ujian;
use App\Models\User;

beforeEach(function (): void {
    $tahunAjaran = TahunAjaran::factory()->create(['active' => true]);
    $matpel = Matpel::factory()->create(['name' => 'Matematika']);
    $kelas = Kelas::factory()->create(['nama' => 'X-RPL-1']);
    $guru = Guru::factory()->create();
    $this->guruUser = User::factory()->create(['guru_id' => $guru->id]);
    $this->guruKelas = GuruKelas::factory()->create([
        'guru_id' => $guru->id,
        'kelas_id' => $kelas->id,
        'matpel_id' => $matpel->id,
        'tahun_ajaran_id' => $tahunAjaran->id,
        'aktif' => true,
    ]);
    $this->panitia = User::factory()->create(['role' => 'panitia']);
    $this->ujian = Ujian::factory()->create([
        'guru_id' => $this->guruUser->guru->id,
        'guru_kelas_id' => $this->guruKelas->id,
        'kategori' => 'uts',
        'status' => 'diajukan',
    ]);
});

test('panitia simpan berita acara dan cetak pdf', function (): void {
    $sesi = SesiUjian::factory()->create(['ujian_id' => $this->ujian->id]);

    $this->actingAs($this->panitia)
        ->post(route('app.panitia.ujian.berita-acara.store', $this->ujian), [
            'sesi_ujian_id' => $sesi->id,
            'tanggal' => now()->format('Y-m-d'),
            'nomor_surat' => '421/001/X/2026',
            'nama_pengawas' => 'Pak Pengawas',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $ba = BeritaAcaraUjian::where('ujian_id', $this->ujian->id)->first();
    expect($ba)->not->toBeNull()
        ->and($ba->nomor_surat)->toBe('421/001/X/2026');

    $this->actingAs($this->panitia)
        ->get(route('app.panitia.ujian.berita-acara.pdf', [$this->ujian, $ba]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('panitia cetak daftar hadir per sesi', function (): void {
    $sesi = SesiUjian::factory()->create(['ujian_id' => $this->ujian->id]);

    $this->actingAs($this->panitia)
        ->get(route('app.panitia.ujian.hadir', [$this->ujian, $sesi]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('panitia cetak jadwal ujian keseluruhan', function (): void {
    $this->actingAs($this->panitia)
        ->get(route('app.panitia.ujian.jadwal-pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('catatan panitia tersimpan dan terlihat guru', function (): void {
    $this->actingAs($this->panitia)
        ->post(route('app.panitia.ujian.kembalikan', $this->ujian), [
            'catatan' => 'Soal nomor 5 ambigu, perbaiki.',
        ])
        ->assertRedirect();

    $fresh = $this->ujian->fresh();
    expect($fresh->status)->toBe('draft')
        ->and($fresh->catatan_panitia)->toBe('Soal nomor 5 ambigu, perbaiki.');

    // Guru lihat di daftar ujian.
    $this->actingAs($this->guruUser)
        ->get(route('app.guru.ujian.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('ujians.data.0.catatan_panitia', 'Soal nomor 5 ambigu, perbaiki.')
        );
});
