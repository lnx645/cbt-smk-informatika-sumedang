<?php

use App\Models\JamPelajaran;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_pelajarans', function (Blueprint $table) {
            $table->unsignedBigInteger('jam_pelajaran_id')->nullable()->after('kelas_id');
        });

        $defaultJp = JamPelajaran::where('is_break', false)->orderBy('urutan')->first();
        $defaultId = $defaultJp ? $defaultJp->id : 1;

        DB::table('jadwal_pelajarans')->whereNull('jam_pelajaran_id')->update([
            'jam_pelajaran_id' => $defaultId,
        ]);

        Schema::table('jadwal_pelajarans', function (Blueprint $table) {
            $table->foreign('jam_pelajaran_id')->references('id')->on('jam_pelajarans')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedBigInteger('jam_pelajaran_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('jadwal_pelajarans', function (Blueprint $table) {
            $table->dropForeign(['jam_pelajaran_id']);
            $table->dropColumn('jam_pelajaran_id');
        });
    }
};
