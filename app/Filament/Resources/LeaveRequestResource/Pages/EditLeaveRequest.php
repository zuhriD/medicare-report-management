<?php

namespace App\Filament\Resources\LeaveRequestResource\Pages;

use App\Filament\Resources\LeaveRequestResource;
use App\Models\Office;
use App\Services\AttendanceFineService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeaveRequest extends EditRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('copyChat')
                ->label('Copy Chat')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary')
                ->modalHeading('Format Chat Permohonan Izin')
                ->modalDescription('Template pesan formal permohonan izin untuk disalin atau dikirimkan ke atasan (Dr. Adnan).')
                ->modalIcon('heroicon-o-chat-bubble-bottom-center-text')
                ->modalWidth('2xl')
                ->form([
                    \Filament\Forms\Components\TextInput::make('recipient')
                        ->label('Kepada Yth. (Nama Penerima / Atasan)')
                        ->default('Dr. Adnan')
                        ->live()
                        ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                            $recipient = !empty($state) ? $state : 'Dr. Adnan';
                            $set('chat_preview', $this->record->getFormattedChatTemplate($recipient));
                        }),
                    \Filament\Forms\Components\Textarea::make('chat_preview')
                        ->label('Teks Permohonan Izin')
                        ->rows(14)
                        ->default(fn() => $this->record->getFormattedChatTemplate('Dr. Adnan'))
                        ->helperText('Klik tombol "Salin ke Clipboard" untuk menyalin teks secara otomatis.'),
                ])
                ->modalSubmitActionLabel('Salin ke Clipboard')
                ->action(function (array $data) {
                    $text = $data['chat_preview'] ?? $this->record->getFormattedChatTemplate($data['recipient'] ?? 'Dr. Adnan');

                    $this->js('
                        navigator.clipboard.writeText(' . json_encode($text) . ').then(() => {
                            new FilamentNotification()
                                .title("Teks permohonan izin berhasil disalin!")
                                .success()
                                .send();
                        });
                    ');

                    \Filament\Notifications\Notification::make()
                        ->title('Teks izin berhasil disalin')
                        ->body('Format chat permohonan izin telah disalin ke clipboard.')
                        ->success()
                        ->send();
                })
                ->extraModalFooterActions([
                    Actions\Action::make('modalSendWhatsApp')
                        ->label('Kirim via WhatsApp')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->color('success')
                        ->url(function (array $data) {
                            $recipient = !empty($data['recipient']) ? $data['recipient'] : 'Dr. Adnan';
                            $msg = rawurlencode($this->record->getWhatsAppChatTemplate($recipient));
                            $phone = '6281359983721';
                            return "https://api.whatsapp.com/send?phone={$phone}&text={$msg}";
                        }, shouldOpenInNewTab: true),
                    Actions\Action::make('modalDownloadPdf')
                        ->label('Download PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('warning')
                        ->url(fn() => route('leave-requests.pdf', $this->record), shouldOpenInNewTab: true),
                ]),

            Actions\Action::make('generatePdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('warning')
                ->url(fn() => route('leave-requests.pdf', $this->record), shouldOpenInNewTab: true),

            Actions\Action::make('sendWhatsApp')
                ->label('Kirim WA')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('info')
                ->url(function () {
                    $phone = '6281359983721';
                    $msg = rawurlencode($this->record->getWhatsAppChatTemplate('Dr. Adnan'));
                    return "https://api.whatsapp.com/send?phone={$phone}&text={$msg}";
                }, shouldOpenInNewTab: true),

            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        if ($end->lt($start)) {
            $end = $start->copy();
            $data['end_date'] = $start->toDateString();
        }

        $office = !empty($data['office_id']) ? Office::find($data['office_id']) : null;
        $fineService = app(AttendanceFineService::class);

        if ($office) {
            $calc = $fineService->calculateLeaveNormalFine($office, $start, $end);
            $data['total_days'] = $calc['total_days'];
            $data['normal_fine_amount'] = $calc['normal_fine_amount'];
        } else {
            $days = $fineService->calculateWorkingDays($start, $end);
            $data['total_days'] = $days;
            $data['normal_fine_amount'] = $days * 50000.00;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
