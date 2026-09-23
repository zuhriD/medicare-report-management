<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Models\LeaveRequest;
use App\Models\Office;
use App\Models\User;
use App\Services\AttendanceFineService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'HR & Attendance';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Leave Requests';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && method_exists($user, 'hasRole') && !$user->hasRole(['admin', 'super_admin', 'lead', 'Admin', 'Super Admin', 'Manager'])) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Leave Request Details')
                    ->description('Specify the leave period, type, and reason.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('user_id')
                                    ->label('Staff Member')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->default(fn() => auth()->id())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if ($state) {
                                            $u = User::find($state);
                                            if ($u && $u->office_id) {
                                                $set('office_id', $u->office_id);
                                                static::recalculateDaysAndFine($set, $get);
                                            }
                                        }
                                    }),
                                Select::make('office_id')
                                    ->label('Office')
                                    ->relationship('office', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->default(fn() => auth()->user()?->office_id)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        static::recalculateDaysAndFine($set, $get);
                                    }),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Select::make('leave_type')
                                    ->label('Leave Type')
                                    ->options([
                                        'sick' => 'Sakit (Sick Leave)',
                                        'permission' => 'Izin Keperluan (Permission)',
                                        'annual_leave' => 'Cuti Tahunan (Annual Leave)',
                                        'other' => 'Lainnya (Other)',
                                    ])
                                    ->default('permission')
                                    ->required(),
                                DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->default(now()->toDateString())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        static::recalculateDaysAndFine($set, $get);
                                    }),
                                DatePicker::make('end_date')
                                    ->label('End Date')
                                    ->default(now()->toDateString())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        static::recalculateDaysAndFine($set, $get);
                                    }),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('total_days')
                                    ->label('Total Working Days')
                                    ->numeric()
                                    ->default(1)
                                    ->readOnly()
                                    ->helperText('Calculated working days excluding Sundays.'),
                                TextInput::make('normal_fine_amount')
                                    ->label('Estimated Normal Fine')
                                    ->numeric()
                                    ->default(50000.00)
                                    ->prefix('Rp')
                                    ->readOnly()
                                    ->helperText('Standard fine rate without supervisor approval.'),
                            ]),

                        Textarea::make('reason')
                            ->label('Reason for Leave')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull()
                            ->placeholder('Describe the reason for your absence...'),

                        FileUpload::make('attachment_path')
                            ->label('Attachment / Medical Letter (Optional)')
                            ->disk('public')
                            ->directory('leave-attachments')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120)
                            ->columnSpanFull()
                            ->helperText('Upload medical letter (surat dokter) or proof photo if available.'),
                    ]),

                Section::make('Supervisor Review & Fine Relief')
                    ->description('Approval decision and fine adjustment determined by supervisor.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'pending' => 'Pending Approval',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->disabled(fn() => !auth()->user()?->hasRole(['admin', 'super_admin', 'lead', 'Admin', 'Super Admin', 'Manager'])),

                                TextInput::make('adjusted_fine_amount')
                                    ->label('Approved Fine Amount (Keringanan Denda)')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0.00)
                                    ->helperText('Nominal denda yang disepakati atasan. Isi 0 jika denda dihapus sepenuhnya.')
                                    ->disabled(fn() => !auth()->user()?->hasRole(['admin', 'super_admin', 'lead', 'Admin', 'Super Admin', 'Manager'])),

                                Select::make('approved_by')
                                    ->label('Reviewed By')
                                    ->relationship('approver', 'name')
                                    ->disabled(),
                            ]),

                        Textarea::make('approval_notes')
                            ->label('Supervisor Notes')
                            ->rows(2)
                            ->columnSpanFull()
                            ->placeholder('Approval or rejection remarks...')
                            ->disabled(fn() => !auth()->user()?->hasRole(['admin', 'super_admin', 'lead', 'Admin', 'Super Admin', 'Manager'])),
                    ])
                    ->visible(fn($record) => $record !== null || auth()->user()?->hasRole(['admin', 'super_admin', 'lead', 'Admin', 'Super Admin', 'Manager'])),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Staff Member')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('office.name')
                    ->label('Office')
                    ->sortable(),
                TextColumn::make('leave_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'sick' => 'danger',
                        'permission' => 'warning',
                        'annual_leave' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'sick' => 'Sakit',
                        'permission' => 'Izin',
                        'annual_leave' => 'Cuti',
                        default => ucfirst($state),
                    }),
                TextColumn::make('start_date')
                    ->label('Period')
                    ->getStateUsing(fn($record) => $record->start_date->format('d M Y') . ($record->start_date->ne($record->end_date) ? ' - ' . $record->end_date->format('d M Y') : ''))
                    ->sortable(),
                TextColumn::make('total_days')
                    ->label('Days')
                    ->formatStateUsing(fn($state) => $state . ' Hari')
                    ->sortable(),
                TextColumn::make('normal_fine_amount')
                    ->label('Normal Fine')
                    ->money('IDR', locale: 'id')
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('adjusted_fine_amount')
                    ->label('Approved Fine')
                    ->money('IDR', locale: 'id')
                    ->weight('bold')
                    ->color(fn($record) => $record->status === 'approved' ? 'success' : 'gray')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                        default => 'Pending',
                    }),
                TextColumn::make('approver.name')
                    ->label('Approver')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                SelectFilter::make('leave_type')
                    ->options([
                        'sick' => 'Sakit',
                        'permission' => 'Izin',
                        'annual_leave' => 'Cuti',
                    ]),
                SelectFilter::make('office_id')
                    ->relationship('office', 'name')
                    ->label('Office'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn(LeaveRequest $record) => $record->isPending() && auth()->user()?->hasRole(['admin', 'super_admin', 'lead', 'Admin', 'Super Admin', 'Manager']))
                    ->form([
                        TextInput::make('adjusted_fine_amount')
                            ->label('Nominal Denda Setelah Keringanan')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(fn(LeaveRequest $record) => 0.00)
                            ->helperText('Masukkan 0 jika denda dibebaskan sepenuhnya, atau masukkan nominal keringanan (misal Rp 50.000).')
                            ->required(),
                        Textarea::make('approval_notes')
                            ->label('Catatan Persetujuan (Opsional)')
                            ->placeholder('Catatan keringanan atau persetujuan izin...'),
                    ])
                    ->action(function (LeaveRequest $record, array $data) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'adjusted_fine_amount' => (float) $data['adjusted_fine_amount'],
                            'approval_notes' => $data['approval_notes'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Permohonan izin disetujui')
                            ->body('Keringanan denda ditetapkan: Rp ' . number_format($data['adjusted_fine_amount'], 0, ',', '.'))
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn(LeaveRequest $record) => $record->isPending() && auth()->user()?->hasRole(['admin', 'super_admin', 'lead', 'Admin', 'Super Admin', 'Manager']))
                    ->form([
                        Textarea::make('approval_notes')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->placeholder('Tuliskan alasan penolakan permohonan izin...'),
                    ])
                    ->action(function (LeaveRequest $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'approval_notes' => $data['approval_notes'],
                        ]);

                        Notification::make()
                            ->title('Permohonan izin ditolak')
                            ->danger()
                            ->send();
                    }),

                Action::make('copyChat')
                    ->label('Copy Chat')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('primary')
                    ->modalHeading('Format Chat Permohonan Izin')
                    ->modalDescription('Template pesan formal permohonan izin untuk disalin atau dikirimkan ke atasan (Dr. Adnan).')
                    ->modalIcon('heroicon-o-chat-bubble-bottom-center-text')
                    ->modalWidth('2xl')
                    ->form([
                        TextInput::make('recipient')
                            ->label('Kepada Yth. (Nama Penerima / Atasan)')
                            ->default('Dr. Adnan')
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, LeaveRequest $record) {
                                $recipient = !empty($state) ? $state : 'Dr. Adnan';
                                $set('chat_preview', $record->getFormattedChatTemplate($recipient));
                            }),
                        Textarea::make('chat_preview')
                            ->label('Teks Permohonan Izin')
                            ->rows(14)
                            ->default(fn(LeaveRequest $record) => $record->getFormattedChatTemplate('Dr. Adnan'))
                            ->helperText('Klik tombol "Salin ke Clipboard" untuk menyalin teks secara otomatis.'),
                    ])
                    ->modalSubmitActionLabel('Salin ke Clipboard')
                    ->action(function (LeaveRequest $record, array $data, $livewire) {
                        $text = $data['chat_preview'] ?? $record->getFormattedChatTemplate($data['recipient'] ?? 'Dr. Adnan');
                        
                        $livewire->js('
                            navigator.clipboard.writeText(' . json_encode($text) . ').then(() => {
                                new FilamentNotification()
                                    .title("Teks permohonan izin berhasil disalin!")
                                    .success()
                                    .send();
                            });
                        ');

                        Notification::make()
                            ->title('Teks izin berhasil disalin')
                            ->body('Format chat permohonan izin telah disalin ke clipboard.')
                            ->success()
                            ->send();
                    })
                    ->extraModalFooterActions([
                        Action::make('modalSendWhatsApp')
                            ->label('Kirim via WhatsApp')
                            ->icon('heroicon-o-chat-bubble-left-ellipsis')
                            ->color('success')
                            ->url(function (LeaveRequest $record, array $data) {
                                $recipient = !empty($data['recipient']) ? $data['recipient'] : 'Dr. Adnan';
                                $msg = rawurlencode($record->getWhatsAppChatTemplate($recipient));
                                $phone = '6281359983721';
                                return "https://api.whatsapp.com/send?phone={$phone}&text={$msg}";
                            }, shouldOpenInNewTab: true),
                        Action::make('modalDownloadPdf')
                            ->label('Download PDF')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('warning')
                            ->url(fn(LeaveRequest $record) => route('leave-requests.pdf', $record), shouldOpenInNewTab: true),
                    ]),

                Action::make('generatePdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('warning')
                    ->tooltip('Download Surat Permohonan Izin (PDF)')
                    ->url(fn(LeaveRequest $record) => route('leave-requests.pdf', $record), shouldOpenInNewTab: true),

                Action::make('sendWhatsApp')
                    ->label('Kirim WA')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('info')
                    ->tooltip('Kirim pesan izin formal ke Atasan via WhatsApp')
                    ->url(function (LeaveRequest $record) {
                        $phone = '6281359983721';
                        $msg = rawurlencode($record->getWhatsAppChatTemplate('Dr. Adnan'));
                        return "https://api.whatsapp.com/send?phone={$phone}&text={$msg}";
                    }, shouldOpenInNewTab: true),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function recalculateDaysAndFine(Set $set, Get $get): void
    {
        $startDateStr = $get('start_date');
        $endDateStr = $get('end_date');
        $officeId = $get('office_id');

        if (!$startDateStr || !$endDateStr) {
            return;
        }

        $start = Carbon::parse($startDateStr);
        $end = Carbon::parse($endDateStr);

        if ($end->lt($start)) {
            $end = $start->copy();
            $set('end_date', $start->toDateString());
        }

        $office = $officeId ? Office::find($officeId) : null;
        $fineService = app(AttendanceFineService::class);

        if ($office) {
            $calc = $fineService->calculateLeaveNormalFine($office, $start, $end);
            $set('total_days', $calc['total_days']);
            $set('normal_fine_amount', $calc['normal_fine_amount']);
        } else {
            $days = $fineService->calculateWorkingDays($start, $end);
            $set('total_days', $days);
            $set('normal_fine_amount', $days * 50000.00);
        }
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
