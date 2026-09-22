<?php

namespace App\Console\Commands;

use App\Models\Office;
use App\Services\HolidayService;
use Illuminate\Console\Command;

class SyncHolidaysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holidays:sync {year? : Tahun hari libur (default tahun saat ini)} {--country=ID : Kode negara (ID atau MY)} {--office= : ID kantor spesifik}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronkan hari libur nasional dari Nager.Date API ke database lokal';

    /**
     * Execute the console command.
     */
    public function handle(HolidayService $holidayService): int
    {
        $year = (int) ($this->argument('year') ?: now()->year);
        $country = strtoupper((string) $this->option('country'));
        $officeId = $this->option('office') ? (int) $this->option('office') : null;

        $this->info("Menyinkronkan hari libur nasional untuk tahun {$year} (Negara: {$country})...");

        $result = $holidayService->syncHolidaysFromApi($year, $country, $officeId);

        if ($result['success']) {
            $this->info($result['message']);
            return Command::SUCCESS;
        }

        $this->error($result['message']);
        return Command::FAILURE;
    }
}
