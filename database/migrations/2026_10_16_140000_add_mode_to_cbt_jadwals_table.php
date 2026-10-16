<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cbt_jadwals', function (Blueprint $table) {
            $table->enum('mode', ['luring', 'daring'])->default('luring')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('cbt_jadwals', function (Blueprint $table) {
            $table->dropColumn('mode');
        });
    }
};
