<?php

use App\Models\TahunAjaran;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Window jadwal ujian besar (uts/uas/usbk) per tahun ajaran.
     * Guru wajib menjadwalkan ujian besar di dalam window ini.
     */
    public function up(): void
    {
        Schema::create('periode_ujians', function (Blueprint $table) {
            $table->id();
            $table->string('kategori', 10); // uts, uas, usbk
            $table->foreignIdFor(TahunAjaran::class)->constrained('tahun_ajaran')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('nama');
            $table->timestamp('tanggal_mulai');
            $table->timestamp('tanggal_selesai');
            $table->timestamps();

            $table->index(['tahun_ajaran_id', 'kategori']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periode_ujians');
    }
};
