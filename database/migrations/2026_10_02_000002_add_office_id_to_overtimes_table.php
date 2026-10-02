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
        Schema::table('overtimes', function (Blueprint $table) {
            $table->foreignId('office_id')
                ->nullable()
                ->after('attendance_id')
                ->constrained('offices')
                ->nullOnDelete();
        });

        // Backfill existing overtime records with parent attendance's office_id
        $overtimes = DB::table('overtimes')
            ->join('attendances', 'overtimes.attendance_id', '=', 'attendances.id')
            ->select('overtimes.id as overtime_id', 'attendances.office_id')
            ->get();

        foreach ($overtimes as $ot) {
            if ($ot->office_id) {
                DB::table('overtimes')
                    ->where('id', $ot->overtime_id)
                    ->update(['office_id' => $ot->office_id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropForeign(['office_id']);
            $table->dropColumn('office_id');
        });
    }
};
