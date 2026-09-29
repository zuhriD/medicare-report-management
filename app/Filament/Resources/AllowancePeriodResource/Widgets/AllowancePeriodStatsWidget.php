<?php

namespace App\Filament\Resources\AllowancePeriodResource\Widgets;

use App\Models\AllowancePeriod;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class AllowancePeriodStatsWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        /** @var AllowancePeriod $period */
        $period = $this->record;

        if (!$period) {
            return [];
        }

        $totalStaff = $period->periodStaff()->count();
        $paidStaff = $period->periodStaff()->where('payment_status', 'paid')->count();
        $unpaidStaff = $period->periodStaff()->where('payment_status', 'unpaid')->count();

        $totalPayable = (float) $period->total_amount;
        $totalPaid = (float) $period->total_paid_amount;
        $totalUnpaid = (float) $period->total_unpaid_amount;

        return [
            Stat::make('Total Tagihan', 'Rp ' . number_format($totalPayable, 0, ',', '.'))
                ->description('Total akumulasi tunjangan seluruh staff')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Sudah Dibayar', 'Rp ' . number_format($totalPaid, 0, ',', '.'))
                ->description("{$paidStaff} dari {$totalStaff} staff telah lunas")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Belum Dibayar', 'Rp ' . number_format($totalUnpaid, 0, ',', '.'))
                ->description("{$unpaidStaff} staff menunggu pembayaran")
                ->descriptionIcon('heroicon-m-clock')
                ->color($unpaidStaff > 0 ? 'danger' : 'gray'),

            Stat::make('Total Staff', "{$totalStaff} Orang")
                ->description('Staff dalam periode ini')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
}
