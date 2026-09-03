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
                ->modalContent(function () {
                    $poas = PlanOfAction::with(['user', 'module', 'subModule'])
                        ->whereNotNull('user_id')
                        ->orderBy('created_at', 'desc')
                        ->get()
                        ->groupBy(function ($poa) {
                            // Group by team - if you add company relationship later, use: $poa->user?->company?->name
                            return 'MEDIKCARE';
                        });
                    
                    $recapText = $this->generateRecapText($poas);
                    
                    return Section::make()
                        ->schema([])
                        ->view('poa.recap-modal', [
                            'poas' => $poas,
                            'recapText' => $recapText,
                        ]);
                });
        }
        
        return $actions;
    }
    
    private function generateRecapText($poas): string
    {
        $today = now();
        $dateStr = $today->format('d/m/Y') . ' (' . $today->format('l') . ')';
        
        $text = "DAILY ACHIEVEMENT & DEVELOPMENT REPORT\nDate: {$dateStr}\n\n";
        
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
