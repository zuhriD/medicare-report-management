<?php

namespace App\Filament\Resources\PlanOfActionResource\Pages;

use App\Filament\Resources\PlanOfActionResource;
use App\Models\PlanOfAction;
use Filament\Actions;
use Filament\Forms\Components\Section;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListPlanOfActions extends ListRecords
{
    protected static string $resource = PlanOfActionResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\CreateAction::make(),
        ];
        
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user && ($user->hasRole('super_admin') || $user->hasRole('admin'))) {
            $actions[] = Actions\Action::make('recap')
                ->label('Recap POA')
                ->icon('heroicon-m-document-text')
                ->modalHeading('POA Recap Report')
                ->modalIcon('heroicon-o-document-text')
                ->modalWidth('3xl')
                ->modalSubmitAction(false)
                ->form([
                    \Filament\Forms\Components\DatePicker::make('recap_date')
                        ->label('Select Date')
                        ->default(now()->toDateString())
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->live(),
                    \Filament\Forms\Components\Placeholder::make('recap_content')
                        ->hiddenLabel()
                        ->content(function (\Filament\Forms\Get $get) {
                            $rawDate = $get('recap_date') ?? now()->toDateString();
                            
                            try {
                                if (is_string($rawDate) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $rawDate)) {
                                    $dateObj = \Illuminate\Support\Carbon::createFromFormat('d/m/Y', $rawDate);
                                } else {
                                    $dateObj = \Illuminate\Support\Carbon::parse($rawDate);
                                }
                            } catch (\Throwable $e) {
                                $dateObj = now();
                            }
                            
                            $dbDate = $dateObj->format('Y-m-d');
                            
                            $poas = PlanOfAction::with(['user', 'module', 'subModule'])
                                ->whereNotNull('user_id')
                                ->whereDate('start_date', $dbDate)
                                ->orderBy('created_at', 'desc')
                                ->get()
                                ->groupBy(function ($poa) {
                                    return 'MEDIKCARE';
                                });
                            
                            $recapText = $this->generateRecapText($poas, $dateObj);
                            
                            return new \Illuminate\Support\HtmlString(
                                view('poa.recap-modal', [
                                    'poas' => $poas,
                                    'recapText' => $recapText,
                                    'selectedDate' => $dbDate,
                                ])->render()
                            );
                        }),
                ]);
        }
        
        return $actions;
    }
    
    private function generateRecapText($poas, \Illuminate\Support\Carbon $dateObj): string
    {
        $dateStr = $dateObj->format('d/m/Y') . ' (' . $dateObj->format('l') . ')';
        
        $text = "PLAN OF ACTION\nDate: {$dateStr}\n\n";
        
        if ($poas->isEmpty()) {
            $text .= "No Plan of Action records found for {$dateStr}.";
            return $text;
        }

        foreach ($poas as $team => $teamPoas) {
            $text .= " {$team}\n";
            $counter = 1;
            
            // Group by user within team
            $userGroups = $teamPoas->groupBy('user_id');
            foreach ($userGroups as $userId => $userPoas) {
                $userName = $userPoas->first()?->user?->name ?? 'Unknown';
                $text .= "{$counter}. {$userName}\n";
                
                foreach ($userPoas as $poa) {
                    $text .= "{$poa->module?->name} | {$poa->subModule?->name}\n";
                    
                    $tasks = is_array($poa->description)
                        ? $poa->description
                        : array_filter(array_map('trim', explode('-', strip_tags($poa->description ?? ''))));
                    if (!empty($tasks)) {
                        foreach ($tasks as $task) {
                            $cleanTask = trim(strip_tags($task));
                            if ($cleanTask) {
                                $text .= "- {$cleanTask}\n";
                            }
                        }
                    }
                    $text .= "\n";
                }
                $counter++;
            }
            $text .= "\n";
        }
        
        return trim($text);
    }
}
