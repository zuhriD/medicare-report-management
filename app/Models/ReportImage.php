<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ReportImage extends Model
{
    use HasFactory;

    protected $fillable = ['daily_report_id', 'image_path', 'caption'];

    public function dailyReport()
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function getSrcAttribute(): string
    {
        $path = $this->image_path;

        if (blank($path)) {
            return '';
        }

        // If path is already a complete URL
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Order of disks to check: gcs first (since Filament uses gcs), then default/public
        $disksToTry = array_unique(array_filter([
            'gcs',
            config('filesystems.public_disk', 'public'),
            config('filesystems.default', 'local'),
            'public',
            'local',
        ]));

        foreach ($disksToTry as $diskName) {
            try {
                $disk = Storage::disk($diskName);
                if ($disk->exists($path)) {
                    try {
                        $mime = $disk->mimeType($path) ?: 'image/jpeg';
                        $content = $disk->get($path);
                        if ($content) {
                            return 'data:'.$mime.';base64,'.base64_encode($content);
                        }
                    } catch (\Throwable $e) {
                        // Ignore read failure and fall through to URL
                    }

                    return $disk->url($path);
                }
            } catch (\Throwable $e) {
                // Continue to next disk
            }
        }

        // Fallback to GCS disk URL if exists check failed (e.g. permission or adapter limitation)
        try {
            return Storage::disk('gcs')->url($path);
        } catch (\Throwable $e) {
            return Storage::url($path);
        }
    }
}
