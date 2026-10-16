<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cbt_jadwals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('cbt_events')->cascadeOnDelete();
            $table->foreignId('kelompok_bank_soal_id')->constrained('cbt_kelompok_bank_soals')->cascadeOnDelete();
            $table->string('nama');
            $table->date('tanggal');
            $table->integer('durasi_menit');
            $table->boolean('acak_soal')->default(true);
            $table->boolean('acak_opsi')->default(true);
            $table->boolean('butuh_token')->default(true);
            $table->boolean('tampilkan_hasil')->default(true);
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('cbt_jadwal_sesi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('cbt_jadwals')->cascadeOnDelete();
            $table->string('nama');
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
        Schema::dropIfExists('cbt_jadwal_sesi');
        Schema::dropIfExists('cbt_jadwals');
    }
};
