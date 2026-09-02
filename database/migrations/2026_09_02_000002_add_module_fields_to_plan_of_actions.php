<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_of_actions', function (Blueprint $table) {
            $table->foreignId('module_id')->nullable()->constrained('modules')->onDelete('set null');
            $table->foreignId('sub_module_id')->nullable()->constrained('sub_modules')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('plan_of_actions', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Module::class);
            $table->dropForeignIdFor(\App\Models\SubModule::class);
        });
    }
};
