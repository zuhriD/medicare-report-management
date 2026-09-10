<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Implicitly grant "super_admin" role all permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        FileUpload::configureUsing(function (FileUpload $component) {
            $component->disk('gcs')->directory('weekly-reports')->visibility('public')->maxSize(2048);
        });

        RichEditor::configureUsing(function (RichEditor $component) {
            $component->fileAttachmentsDisk('gcs')->fileAttachmentsDirectory('weekly-reports')->fileAttachmentsVisibility('public');
        });
    }
}
