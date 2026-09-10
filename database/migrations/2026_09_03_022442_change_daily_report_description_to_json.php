<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Convert existing plain-text/HTML descriptions to JSON arrays
        DB::table('daily_reports')->whereNotNull('description')->cursor()->each(function ($report) {
            $description = $report->description;

            // Skip if already valid JSON array
            $decoded = json_decode($description, true);
            if (is_array($decoded)) {
                return;
            }

            // Wrap existing description string into a single-element array
            DB::table('daily_reports')
                ->where('id', $report->id)
                ->update(['description' => json_encode([$description])]);
        });

        Schema::table('daily_reports', function (Blueprint $table) {
            $table->json('description')->change();
        });
    }

    public function down(): void
    {
        // Convert JSON arrays back to plain text (take first element)
        DB::table('daily_reports')->whereNotNull('description')->cursor()->each(function ($report) {
            $decoded = json_decode($report->description, true);
            if (is_array($decoded)) {
                DB::table('daily_reports')
                    ->where('id', $report->id)
                    ->update(['description' => $decoded[0] ?? '']);
            }
        });

        Schema::table('daily_reports', function (Blueprint $table) {
            $table->text('description')->change();
        });
    }
};
