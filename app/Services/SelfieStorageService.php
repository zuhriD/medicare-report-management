<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class SelfieStorageService
{
    /**
     * Primary storage disk for attendance selfie media.
     */
    protected string $primaryDisk = 'gcs';

    /**
     * Root folder for attendance selfies in GCS.
     */
    protected string $folder = 'attendance-selfies';

    /**
     * Get the active disk to use (falls back to local/default if GCS is not configured or in local testing).
     */
    public function getDisk(): string
    {
        if (app()->environment('testing')) {
            return config('filesystems.default', 'local');
        }

        // If GCS bucket is configured, use gcs; otherwise fallback to default disk
        if (config('filesystems.disks.gcs.bucket')) {
            return 'gcs';
        }

        return config('filesystems.default', 'local');
    }

    /**
     * Store live selfie image and return the relative path.
     *
     * @param string|UploadedFile $selfieData Base64 Data URL or UploadedFile
     * @param int $userId
     * @param string $type e.g. 'check_in', 'check_out', 'ot_check_in', 'ot_check_out'
     * @param Carbon|null $date
     * @return string Relative path inside storage disk (e.g. 'attendance-selfies/2026/09/21/1/check_in_1740000000_abc123.webp')
     */
    public function storeSelfie(
        string|UploadedFile $selfieData,
        int $userId,
        string $type = 'check_in',
        ?Carbon $date = null
    ): string {
        $date = $date ?? Carbon::now();
        $year = $date->format('Y');
        $month = $date->format('m');
        $day = $date->format('d');

        $directory = "{$this->folder}/{$year}/{$month}/{$day}/{$userId}";
        $timestamp = time();
        $random = Str::lower(Str::random(6));
        $filename = "{$type}_{$timestamp}_{$random}.webp";
        $relativePath = "{$directory}/{$filename}";

        $binaryData = $this->extractBinaryData($selfieData);

        // Convert to webp if GD is available
        $webpData = $this->convertToWebp($binaryData);

        $disk = $this->getDisk();

        try {
            Storage::disk($disk)->put($relativePath, $webpData, [
                'visibility' => 'public',
                'mimetype' => 'image/webp',
            ]);
        } catch (\Throwable $e) {
            // Fallback to local disk if cloud upload fails
            Storage::disk('local')->put($relativePath, $webpData);
        }

        return $relativePath;
    }

    /**
     * Extract raw image binary string from Base64 DataURL or UploadedFile.
     */
    protected function extractBinaryData(string|UploadedFile $selfieData): string
    {
        if ($selfieData instanceof UploadedFile) {
            return (string) file_get_contents($selfieData->getRealPath());
        }

        // Handle Base64 Data URL format (data:image/png;base64,....)
        if (preg_match('/^data:image\/(\w+);base64,/', $selfieData, $type)) {
            $data = substr($selfieData, strpos($selfieData, ',') + 1);
            $decoded = base64_decode($data);
            if ($decoded === false) {
                throw new \InvalidArgumentException('Base64 decode failed for live selfie image.');
            }
            return $decoded;
        }

        // Try raw base64 string
        $decoded = base64_decode($selfieData, true);
        if ($decoded !== false && base64_encode($decoded) === $selfieData) {
            return $decoded;
        }

        // Return as raw string
        return $selfieData;
    }

    /**
     * Convert binary image data into optimized WEBP format using GD.
     */
    protected function convertToWebp(string $binaryData): string
    {
        if (!extension_loaded('gd') || !function_exists('imagewebp')) {
            return $binaryData;
        }

        $image = @imagecreatefromstring($binaryData);
        if (!$image) {
            return $binaryData;
        }

        // Enable alpha blending / transparency support
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        ob_start();
        imagewebp($image, null, 85); // 85% quality
        $webpData = ob_get_clean();
        imagedestroy($image);

        return $webpData ?: $binaryData;
    }

    /**
     * Get the publicly accessible or displayable URL for a selfie path.
     */
    public function getSelfieUrl(?string $relativePath): string
    {
        if (blank($relativePath)) {
            return '';
        }

        if (str_starts_with($relativePath, 'http://') || str_starts_with($relativePath, 'https://')) {
            return $relativePath;
        }

        $disksToTry = array_unique(array_filter([
            $this->getDisk(),
            'gcs',
            config('filesystems.public_disk', 'public'),
            config('filesystems.default', 'local'),
            'local',
        ]));

        foreach ($disksToTry as $diskName) {
            try {
                $disk = Storage::disk($diskName);
                if ($disk->exists($relativePath)) {
                    return $disk->url($relativePath);
                }
            } catch (\Throwable $e) {
                // Continue checking next disk
            }
        }

        // Default GCS URL generator
        try {
            return Storage::disk('gcs')->url($relativePath);
        } catch (\Throwable $e) {
            return Storage::url($relativePath);
        }
    }

    /**
     * Check if a selfie exists in storage.
     */
    public function exists(string $relativePath): bool
    {
        $disksToTry = array_unique([$this->getDisk(), 'gcs', 'local', 'public']);

        foreach ($disksToTry as $diskName) {
            try {
                if (Storage::disk($diskName)->exists($relativePath)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Ignore and try next
            }
        }

        return false;
    }

    /**
     * Get absolute path for file in local storage (if stored locally).
     */
    public function getAbsolutePath(string $relativePath): ?string
    {
        $disksToTry = ['local', 'public', $this->getDisk()];

        foreach ($disksToTry as $diskName) {
            try {
                $disk = Storage::disk($diskName);
                if ($disk->exists($relativePath)) {
                    return $disk->path($relativePath);
                }
            } catch (\Throwable $e) {
                // Not a local disk or file missing
            }
        }

        return null;
    }

    /**
     * Return a Response for serving the file content.
     */
    public function getResponse(string $relativePath): Response
    {
        $disksToTry = array_unique([$this->getDisk(), 'gcs', 'local', 'public']);

        foreach ($disksToTry as $diskName) {
            try {
                $disk = Storage::disk($diskName);
                if ($disk->exists($relativePath)) {
                    $content = $disk->get($relativePath);
                    return response($content, 200, [
                        'Content-Type' => 'image/webp',
                        'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
                    ]);
                }
            } catch (\Throwable $e) {
                // Continue
            }
        }

        abort(404, 'Attendance selfie not found.');
    }

    /**
     * Delete selfie file from storage.
     */
    public function delete(string $relativePath): bool
    {
        $deleted = false;
        $disksToTry = array_unique([$this->getDisk(), 'gcs', 'local', 'public']);

        foreach ($disksToTry as $diskName) {
            try {
                $disk = Storage::disk($diskName);
                if ($disk->exists($relativePath)) {
                    $disk->delete($relativePath);
                    $deleted = true;
                }
            } catch (\Throwable $e) {
                // Continue
            }
        }

        return $deleted;
    }
}
