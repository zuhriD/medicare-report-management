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
        DB::table('plan_of_actions')->whereNotNull('description')->cursor()->each(function ($poa) {
            $description = $poa->description;

            // Skip if already valid JSON array
            $decoded = json_decode($description, true);
            if (is_array($decoded)) {
                return;
            }

            // Strip HTML tags, split by dashes/newlines into individual tasks
            $clean = trim(strip_tags($description));
            $tasks = array_values(array_filter(array_map('trim', preg_split('/[\n\r]+|(?<=\s)-\s/', $clean))));

            if (empty($tasks)) {
                $tasks = [$clean];
            }

            DB::table('plan_of_actions')
                ->where('id', $poa->id)
                ->update(['description' => json_encode($tasks)]);
        });

        Schema::table('plan_of_actions', function (Blueprint $table) {
            $table->json('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('plan_of_actions')->whereNotNull('description')->cursor()->each(function ($poa) {
            $decoded = json_decode($poa->description, true);
            if (is_array($decoded)) {
                $text = implode("\n- ", $decoded);
                DB::table('plan_of_actions')
                    ->where('id', $poa->id)
                    ->update(['description' => $text]);
            }
        });

        Schema::table('plan_of_actions', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
    }
};
