<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu Peserta Ujian — {{ $sesi->nama }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 9pt; color: #222; padding: 16pt; }
        .kartu { border: 2px solid #4182b3; border-radius: 6pt; padding: 10pt 12pt; margin-bottom: 12pt; page-break-inside: avoid; width: 48%; display: inline-block; vertical-align: top; margin-right: 2%; }
        .kartu:nth-child(even) { margin-right: 0; }
        .kop { text-align: center; border-bottom: 2px solid #4182b3; padding-bottom: 6pt; margin-bottom: 8pt; }
        .kop h2 { font-size: 11pt; }
        .kop p { font-size: 8pt; color: #555; }
        .judul { text-align: center; font-size: 10pt; font-weight: bold; margin-bottom: 8pt; text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2pt 0; font-size: 8.5pt; vertical-align: top; }
        td.label { width: 34%; color: #555; }
        .ttd { margin-top: 10pt; display: flex; justify-content: space-between; font-size: 8pt; text-align: center; }
        .ttd div { width: 45%; }
        .ttd .nama { margin-top: 36pt; text-decoration: underline; font-weight: bold; }
    </style>
</head>
<body>
    @foreach ($pesertas as $siswa)
        <div class="kartu">
            <div class="kop">
                <h2>KARTU PESERTA UJIAN</h2>
                <p>SMK Informatika Sumedang</p>
            </div>
            <table>
                <tr><td class="label">Nama</td><td>: <strong>{{ $siswa->nama_lengkap }}</strong></td></tr>
                <tr><td class="label">NISN</td><td>: {{ $siswa->nisn }}</td></tr>
                <tr><td class="label">Ujian</td><td>: {{ $ujian->judul }}</td></tr>
                <tr><td class="label">Sesi</td><td>: {{ $sesi->nama }}</td></tr>
                <tr><td class="label">Waktu</td><td>: {{ $sesi->waktu_mulai->translatedFormat('d M Y H:i') }} – {{ $sesi->waktu_selesai->translatedFormat('H:i') }}</td></tr>
                <tr><td class="label">Ruang</td><td>: {{ $sesi->ruang?->nama ?? '-' }}</td></tr>
                @if ($ujian->token)
                    <tr><td class="label">Token</td><td>: <strong>{{ $ujian->token }}</strong></td></tr>
                @endif
            </table>
            <div class="ttd">
                <div>Peserta<div class="nama">{{ $siswa->nama_lengkap }}</div></div>
                <div>Pengawas<div class="nama">( ........................ )</div></div>
            </div>
        </div>
    @endforeach
</body>
</html>
