<?php

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Matpel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jadwal_pelajarans', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Guru::class)->constrained('gurus')->restrictOnDelete()->onUpdate('cascade');
            $table->foreignIdFor(Matpel::class)->constrained('matpels')->restrictOnDelete()->onUpdate('cascade');
            $table->foreignIdFor(Kelas::class)->constrained('kelas')->restrictOnDelete()->onUpdate('cascade');
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_pelajarans');
    }
};
