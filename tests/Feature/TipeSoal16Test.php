<?php

use App\Models\Guru;
use App\Models\KelompokBankSoal;
use App\Models\Matpel;
use App\Models\User;

beforeEach(function (): void {
    $this->matpel = Matpel::factory()->create(['name' => 'Matematika']);
    $this->guru = Guru::factory()->create();
    $this->guruUser = User::factory()->create(['guru_id' => $this->guru->id]);
    $this->kelompok = KelompokBankSoal::create([
        'nama' => 'Bank Test',
        'matpel_id' => $this->matpel->id,
        'guru_id' => $this->guru->id,
    ]);
});

function postSoal($test, string $tipe, array $detail = []): void
{
    $response = $test->actingAs($test->guruUser)->post(
        "/app/guru/bank-soal/kelompok/{$test->kelompok->id}/soal",
        ['tipe' => $tipe, 'pertanyaan' => "<p>Soal {$tipe}</p>", 'detail' => $detail]
    );
    $response->assertRedirect();
    expect(\App\Models\BankSoal::where('kelompok_bank_soal_id', $test->kelompok->id)->where('tipe', $tipe)->exists())->toBeTrue();
}

test('semua 16 tipe soal bisa disimpan ke bank soal', function (): void {
    $cases = [
        'pilihan_ganda' => ['opsi' => [['teks' => 'A'], ['teks' => 'B']], 'kunci' => 'a'],
        'pilihan_ganda_kompleks' => ['opsi' => [['teks' => 'A'], ['teks' => 'B']], 'kunci' => ['a']],
        'pilihan_ganda_citra' => ['opsi' => [['teks' => 'A'], ['teks' => 'B']], 'kunci' => 'a'],
        'menjodohkan' => ['pasangan' => [['kiri' => '1', 'kanan' => 'satu']]],
        'menjodohkan_banyak' => ['pasangan' => [['kiri' => '1', 'kanan' => 'satu']]],
        'isian_singkat' => ['kunci' => 'Jakarta'],
        'uraian' => ['kunci' => 'Pedoman...'],
        'mengurutkan' => ['urutan' => ['Pertama', 'Kedua']],
        'benar_salah' => ['pernyataan' => [['teks' => 'Air basah', 'jawaban' => 'benar']]],
        'setuju_tidak' => ['pernyataan' => [['teks' => 'Saya setuju', 'jawaban' => 'setuju']]],
        'matrix_text' => ['rows' => ['R1'], 'cols' => ['C1'], 'kunci' => [['X']]],
        'matrix_checkbox' => ['rows' => ['R1'], 'cols' => ['C1'], 'kunci' => [['1']]],
        'mengelompokkan' => ['kelompok' => [['nama' => 'G1', 'items' => ['a']]]],
        'blank_space' => ['teks' => 'Ibukota [___]', 'kunci' => ['Jakarta']],
        'image_labeling' => ['image' => 'http://x/y.png', 'markers' => [['x' => 50, 'y' => 50, 'label' => 'T1', 'kunci' => 'K1']]],
        'listening' => ['audio' => 'http://x/a.mp3'],
    ];

    expect(count($cases))->toBe(16);

    foreach ($cases as $tipe => $detail) {
        postSoal($this, $tipe, $detail);
    }

    // Pastikan 16 soal tersimpan
    expect(\App\Models\BankSoal::where('kelompok_bank_soal_id', $this->kelompok->id)->count())->toBe(16);

    // Pastikan detail JSON tersimpan untuk tipe kompleks
    $menjodohkan = \App\Models\BankSoal::where('tipe', 'menjodohkan')->first();
    expect($menjodohkan->detail['pasangan'][0]['kiri'])->toBe('1');

    $matrix = \App\Models\BankSoal::where('tipe', 'matrix_text')->first();
    expect($matrix->detail['rows'][0])->toBe('R1');
});

test('opsi pilihan ganda tersimpan ke tabel opsi', function (): void {
    postSoal($this, 'pilihan_ganda', [
        'opsi' => [['teks' => 'Opsi A'], ['teks' => 'Opsi B']],
        'kunci' => 'a',
    ]);

    $soal = \App\Models\BankSoal::where('tipe', 'pilihan_ganda')->first();
    expect($soal->opsi)->toHaveCount(2);
    expect($soal->opsi->firstWhere('benar', true))->not->toBeNull();
});
