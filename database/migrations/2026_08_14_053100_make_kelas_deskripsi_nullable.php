<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('kelas', 'deskripsi')) {
            DB::statement('ALTER TABLE kelas ALTER COLUMN deskripsi DROP NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('kelas', 'deskripsi')) {
            DB::statement("UPDATE kelas SET deskripsi = '' WHERE deskripsi IS NULL");
            DB::statement('ALTER TABLE kelas ALTER COLUMN deskripsi SET NOT NULL');
        }
    }
};
