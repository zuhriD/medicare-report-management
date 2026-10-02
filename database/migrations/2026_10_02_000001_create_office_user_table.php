<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('office_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'office_id']);
        });

        // Pre-populate office_user with existing user office_id assignments
        $now = now();
        $usersWithOffice = DB::table('users')
            ->whereNotNull('office_id')
            ->select('id as user_id', 'office_id')
            ->get();

        foreach ($usersWithOffice as $u) {
            DB::table('office_user')->insertOrIgnore([
                'user_id' => $u->user_id,
                'office_id' => $u->office_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_user');
    }
};
