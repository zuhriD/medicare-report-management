<?php

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
        Schema::create('section_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        if (Schema::hasColumn('users', 'section_id')) {
            Schema::table('users', function (Blueprint $table) {
                // Drop foreign key if exists
                // We use dropForeign with array syntax which derives the name automatically based on convention,
                // or we can pass the explicit name 'users_section_id_foreign'
                $table->dropForeign(['section_id']);
                $table->dropColumn('section_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_user');

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
        });
    }
};
