<?php

namespace App\Filament\Actions;

use App\Models\Office;
use App\Models\User;
use App\Services\AttendanceBackfillService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Notifications\Notification;

class BulkBackfillAttendanceAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'bulkBackfillAttendance';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Bulk Backfill Presensi (WhatsApp)')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('warning')
            ->modalHeading('Bulk Backfill Presensi Manual (WhatsApp)')
            ->modalDescription('Generate data absensi dan lembur sekaligus untuk staf pada rentang tanggal tertentu (misal 16–24 September). Perhitungan durasi kerja, ketepatan waktu, dan uang saku dihitung otomatis berdasarkan kebijakan kantor.')
            ->modalSubmitActionLabel('Mulai Proses Backfill')
            ->modalWidth('3xl')
            ->visible(function () {
                $user = auth()->user();
                if (!$user) {
                    return false;
                }
                if (method_exists($user, 'hasRole')) {
                    return $user->hasRole(['hr', 'HR', 'admin', 'super_admin', 'Admin', 'Super Admin', 'lead', 'Lead']);
                }
                return true;
            })
            ->form([
                Section::make('Target Staf & Rentang Tanggal')
                    ->description('Tentukan staf dan rentang tanggal absensi yang akan dibuat.')
                    ->schema([
                        Select::make('user_ids')
                            ->label('Pilih Staf')
                            ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Pilih satu atau lebih staf yang riwayat kehadirannya ingin diisi.'),
                        Grid::make(2)->schema([
                            DatePicker::make('start_date')
                                ->label('Tanggal Mulai')
                                ->default('2026-09-16')
                                ->required(),
                            DatePicker::make('end_date')
                                ->label('Tanggal Selesai')
                                ->default('2026-09-24')
                                ->required(),
                        ]),
                        Select::make('office_id')
                            ->label('Override Kantor (Opsional)')
                            ->options(fn () => Office::query()->pluck('name', 'id'))
                            ->placeholder('Otomatis ikuti kantor utama masing-masing staf')
                            ->helperText('Kosongkan untuk memakai kantor utama staf.'),
                    ]),

                Section::make('Jadwal Jam Kerja Reguler')
                    ->description('Waktu check-in dan check-out standar harian.')
                    ->schema([
                        Grid::make(2)->schema([
                            TimePicker::make('check_in_time')
                                ->label('Jam Masuk')
                                ->default('08:00:00')
                                ->seconds(false)
                                ->required(),
                            TimePicker::make('check_out_time')
                                ->label('Jam Pulang')
                                ->default('17:00:00')
                                ->seconds(false)
                                ->required(),
                        ]),
                        Grid::make(3)->schema([
                            Toggle::make('skip_sundays')
                                ->label('Lewati Hari Minggu')
                                ->default(true)
                                ->helperText('Tidak membuat absen di hari Minggu.')
                                ->inline(false),
                            Toggle::make('skip_holidays')
                                ->label('Lewati Libur Nasional')
                                ->default(true)
                                ->helperText('Sesuai master data libur kantor.')
                                ->inline(false),
                            Toggle::make('overwrite_existing')
                                ->label('Timpa Data yang Ada')
                                ->default(false)
                                ->helperText('Jika tgl 24 sudah ada absen, tidak ditimpa kecuali dicentang.')
                                ->inline(false),
                        ]),
                    ]),

                Section::make('Pengaturan Lembur (Opsional)')
                    ->description('Aktifkan jika staf memiliki riwayat lembur yang dilaporkan di grup chat.')
                    ->schema([
                        Toggle::make('include_overtime')
                            ->label('Sertakan Jam Lembur (Overtime)')
                            ->default(false)
                            ->live(),
                        Grid::make(2)->schema([
                            TimePicker::make('overtime_check_in_time')
                                ->label('Jam Masuk Lembur')
                                ->default('18:00:00')
                                ->seconds(false)
                                ->required(),
                            TimePicker::make('overtime_check_out_time')
                                ->label('Jam Pulang Lembur')
                                ->default('21:00:00')
                                ->seconds(false)
                                ->required(),
                        ])->visible(fn (Get $get) => (bool) $get('include_overtime')),
                        Radio::make('overtime_scope')
                            ->label('Terapkan Lembur Pada:')
                            ->options([
                                'dates' => 'Pilih tanggal tertentu saja (sesuai riwayat chat WA)',
                                'all' => 'Semua hari kerja dalam rentang tanggal',
                            ])
                            ->default('dates')
                            ->live()
                            ->visible(fn (Get $get) => (bool) $get('include_overtime')),
                        TagsInput::make('overtime_dates')
                            ->label('Daftar Tanggal Lembur (Format YYYY-MM-DD)')
                            ->placeholder('Ketik tanggal, cth: 2026-09-18 lalu tekan Enter')
                            ->helperText('Masukkan tanggal-tanggal di mana staf lembur.')
                            ->visible(fn (Get $get) => (bool) $get('include_overtime') && $get('overtime_scope') === 'dates'),
                    ]),

                Section::make('Catatan')
                    ->schema([
                        TextInput::make('notes')
                            ->label('Catatan Kehadiran')
                            ->default('Backfill presensi manual dari riwayat chat WhatsApp')
                            ->maxLength(255),
                    ]),
            ])
            ->action(function (array $data, AttendanceBackfillService $service) {
                $result = $service->backfill($data);

                if ($result['attendances_created'] > 0 || $result['overtimes_created'] > 0) {
                    Notification::make()
                        ->title('Bulk Backfill Presensi Berhasil')
                        ->body("Berhasil membuat {$result['attendances_created']} data kehadiran" .
                            ($result['overtimes_created'] > 0 ? " dan {$result['overtimes_created']} sesi lembur" : "") .
                            " untuk {$result['users_count']} staf ({$result['dates_skipped']} tanggal dilewati/sudah ada).")
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Tidak Ada Data Baru yang Dibuat')
                        ->body("Semua tanggal pada rentang tersebut sudah memiliki data atau dilewati ({$result['dates_skipped']} tanggal). Aktifkan opsi 'Timpa Data yang Ada' jika ingin memperbarui.")
                        ->warning()
                        ->send();
                }
            });
    }
}
