<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE kelas ALTER COLUMN guru_id DROP NOT NULL;');
        DB::statement('ALTER TABLE kelas ALTER COLUMN jurusan_id DROP NOT NULL;');
        DB::statement('ALTER TABLE kelas ALTER COLUMN parent_id DROP NOT NULL;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE kelas ALTER COLUMN parent_id SET NOT NULL;');
        DB::statement('ALTER TABLE kelas ALTER COLUMN jurusan_id SET NOT NULL;');
        DB::statement('ALTER TABLE kelas ALTER COLUMN guru_id SET NOT NULL;');
    }
};
