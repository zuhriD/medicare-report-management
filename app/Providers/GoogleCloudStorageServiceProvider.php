<?php

namespace App\Providers;

use Google\Cloud\Storage\StorageClient;
use Illuminate\Filesystem\FilesystemAdapter as LaravelFilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter;
use League\Flysystem\Visibility;

class GoogleCloudStorageServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Storage::extend('gcs', function ($app, array $config): LaravelFilesystemAdapter {
            $clientConfig = array_filter([
                'projectId' => $config['project_id'] ?? null,
                'keyFilePath' => $config['key_file'] ?? null,
                'keyFile' => isset($config['key_file_json'])
                    ? json_decode($config['key_file_json'], true)
                    : null,
            ]);

            $bucketName = $config['bucket'] ?? null;

            if (! $bucketName) {
                throw new \InvalidArgumentException('The GCS filesystem disk requires GOOGLE_CLOUD_STORAGE_BUCKET.');
            }

            $adapter = new GoogleCloudStorageAdapter(
                bucket: (new StorageClient($clientConfig))->bucket($bucketName),
                prefix: trim($config['path_prefix'] ?? '', '/'),
                defaultVisibility: ($config['visibility'] ?? 'public') === 'public'
                    ? Visibility::PUBLIC
                    : Visibility::PRIVATE,
            );

            return new class(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            ) extends LaravelFilesystemAdapter {
                public function url($path)
                {
                    $url = $this->config['url'] ?? 'https://storage.googleapis.com/' . $this->config['bucket'];
                    
                    if (isset($this->config['path_prefix']) && $this->config['path_prefix']) {
                        $path = trim($this->config['path_prefix'], '/') . '/' . ltrim($path, '/');
                    }
                    
                    return rtrim($url, '/') . '/' . ltrim($path, '/');
                }
            };
        });
    }
}
