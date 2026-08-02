<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_modules', function (Blueprint $table) {
            $table->integer('progress_percentage')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sub_modules', function (Blueprint $table) {
            $table->dropColumn('progress_percentage');
        });
    }
};
