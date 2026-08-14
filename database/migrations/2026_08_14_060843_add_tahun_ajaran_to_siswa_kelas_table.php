<?php

use App\Models\TahunAjaran;
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
        Schema::table('siswa_kelas', function (Blueprint $table) {
            $table->foreignIdFor(TahunAjaran::class)
                ->after('kelas_id')
                ->nullable()
                ->constrained('tahun_ajaran')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

        $activeTahunAjaranId = TahunAjaran::where('active', true)->first()?->id
            ?? TahunAjaran::orderBy('id')->value('id');

        if ($activeTahunAjaranId) {
            DB::table('siswa_kelas')
                ->whereNull('tahun_ajaran_id')
                ->update(['tahun_ajaran_id' => $activeTahunAjaranId]);
        }

        Schema::table('siswa_kelas', function (Blueprint $table) {
            $table->unsignedBigInteger('tahun_ajaran_id')->nullable(false)->change();

            $table->dropUnique(['siswa_nisn', 'kelas_id']);
            $table->unique(['siswa_nisn', 'kelas_id', 'tahun_ajaran_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siswa_kelas', function (Blueprint $table) {
            $table->dropUnique(['siswa_nisn', 'kelas_id', 'tahun_ajaran_id']);
            $table->unique(['siswa_nisn', 'kelas_id']);
            $table->dropConstrainedForeignId('tahun_ajaran_id');
        });
    }
};
