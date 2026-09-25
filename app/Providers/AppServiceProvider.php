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
            $component->disk('gcs')->visibility('private')->maxSize(5120);
        });

        RichEditor::configureUsing(function (RichEditor $component) {
            $component->fileAttachmentsDisk('gcs')->fileAttachmentsVisibility('private');
        });
    }
}
