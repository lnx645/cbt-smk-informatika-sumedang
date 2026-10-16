<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_ujians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('cbt_events')->cascadeOnDelete();
            $table->foreignId('kelompok_bank_soal_id')->constrained('cbt_kelompok_bank_soals')->cascadeOnDelete();
            $table->string('nama');
            $table->date('tanggal');
            $table->integer('durasi_menit');
            $table->enum('mode', ['luring', 'daring'])->default('luring');
            $table->string('status')->default('draft');
            $table->boolean('acak_soal')->default(true);
            $table->boolean('acak_opsi')->default(true);
            $table->boolean('aktifkan_token')->default(true);
            $table->boolean('tampilkan_nilai')->default(true);
            $table->timestamps();
        });

        Schema::create('jadwal_ujian_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_ujian_id')->constrained('jadwal_ujians')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['jadwal_ujian_id', 'kelas_id']);
        });

        Schema::create('jadwal_ujian_sesis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_ujian_id')->constrained('jadwal_ujians')->cascadeOnDelete();
            $table->string('nama');
            $table->integer('urutan');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->foreignId('ruang_ujian_id')->nullable()->constrained('cbt_ruang_ujians')->nullOnDelete();
            $table->integer('kapasitas')->default(40);
            $table->integer('terisi')->default(0);
            $table->string('token')->nullable();
            $table->timestamp('token_released_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_ujian_sesis');
        Schema::dropIfExists('jadwal_ujian_kelas');
        Schema::dropIfExists('jadwal_ujians');
    }
};
