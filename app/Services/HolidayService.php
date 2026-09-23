<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\Office;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HolidayService
{
    /**
     * Check if a given date is a holiday for an office.
     */
    public function isHoliday(string $date, ?int $officeId = null): ?Holiday
    {
        return Holiday::forOfficeAndDate($officeId, $date)->first();
    }

    /**
     * Get all holidays in a date range for an office.
     */
    public function getHolidaysForPeriod(Carbon|string $startDate, Carbon|string $endDate, ?int $officeId = null)
    {
        $startStr = $startDate instanceof Carbon ? $startDate->toDateString() : $startDate;
        $endStr = $endDate instanceof Carbon ? $endDate->toDateString() : $endDate;

        return Holiday::inPeriod($startStr, $endStr, $officeId)
            ->orderBy('holiday_date')
            ->get();
    }

    /**
     * Determine if a date is a standard working day (not Sunday and not a holiday).
     */
    public function isWorkingDay(Carbon $date, ?int $officeId = null): bool
    {
        if ($date->isSunday()) {
            return false;
        }

        return $this->isHoliday($date->toDateString(), $officeId) === null;
    }

    /**
     * Count total working days in a date range (excluding Sundays and holidays).
     */
    public function countWorkingDays(Carbon $startDate, Carbon $endDate, ?int $officeId = null): int
    {
        if ($startDate->gt($endDate)) {
            return 0;
        }

        $period = CarbonPeriod::create($startDate->copy()->startOfDay(), $endDate->copy()->startOfDay());
        $holidays = $this->getHolidaysForPeriod($startDate, $endDate, $officeId)
            ->keyBy(fn ($h) => $h->holiday_date->toDateString());

        $workingDays = 0;
        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            if (!$date->isSunday() && !$holidays->has($dateStr)) {
                $workingDays++;
            }
        }

        return max(1, $workingDays);
    }

    /**
     * Sync public holidays from Nager.Date API for a specific country and year.
     *
     * @return array{success: bool, message: string, synced: int, total: int}
     */
    public function syncHolidaysFromApi(int $year, string $countryCode = 'ID', ?int $officeId = null): array
    {
        $countryCode = strtoupper(trim($countryCode));
        $apiUrl = "https://date.nager.at/api/v3/PublicHolidays/{$year}/{$countryCode}";

        try {
            $response = Http::timeout(10)->get($apiUrl);

            if (!$response->successful()) {
                Log::warning("Failed to fetch public holidays from {$apiUrl}: HTTP " . $response->status());
                return [
                    'success' => false,
                    'message' => "Gagal mengambil data dari API publik (HTTP {$response->status()}).",
                    'synced' => 0,
                    'total' => 0,
                ];
            }

            $items = $response->json();
            if (!is_array($items)) {
                return [
                    'success' => false,
                    'message' => 'Format respons API tidak sesuai.',
                    'synced' => 0,
                    'total' => 0,
                ];
            }

            $syncedCount = 0;
            foreach ($items as $item) {
                if (empty($item['date']) || empty($item['name'])) {
                    continue;
                }

                $holidayName = !empty($item['localName']) ? $item['localName'] : $item['name'];

                Holiday::updateOrCreate(
                    [
                        'holiday_date' => $item['date'],
                        'office_id' => $officeId,
                        'country_code' => $countryCode,
                    ],
                    [
                        'name' => $holidayName,
                        'is_national_holiday' => true,
                        'description' => $item['name'] !== $holidayName ? $item['name'] : null,
                    ]
                );

                $syncedCount++;
            }

            return [
                'success' => true,
                'message' => "Berhasil menyinkronkan {$syncedCount} hari libur nasional untuk tahun {$year} ({$countryCode}).",
                'synced' => $syncedCount,
                'total' => count($items),
            ];
        } catch (\Throwable $e) {
            Log::error("Error syncing public holidays: {$e->getMessage()}");
            return [
                'success' => false,
                'message' => "Terjadi kesalahan koneksi API: {$e->getMessage()}",
                'synced' => 0,
                'total' => 0,
            ];
        }
    }
}
