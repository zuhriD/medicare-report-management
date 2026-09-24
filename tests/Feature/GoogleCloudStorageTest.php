<?php

namespace Tests\Feature;

use Google\Cloud\Storage\Bucket;
use Illuminate\Support\Facades\Storage;
use ReflectionProperty;
use Tests\TestCase;

class GoogleCloudStorageTest extends TestCase
{
    public function test_public_uploads_do_not_send_object_acls(): void
    {
        $disk = Storage::build([
            'driver' => 'gcs',
            'project_id' => 'test-project',
            'bucket' => 'test-bucket',
            'visibility' => 'public',
            'throw' => true,
        ]);

        $bucket = $this->createMock(Bucket::class);
        $bucket->expects($this->once())
            ->method('upload')
            ->with('image contents', $this->callback(function (array $options): bool {
                $this->assertSame('daily-reports/test.png', $options['name']);
                $this->assertArrayNotHasKey('predefinedAcl', $options);
                $this->assertArrayNotHasKey('acl', $options['metadata']);

                return true;
            }));

        $adapter = $disk->getAdapter();
        (new ReflectionProperty($adapter, 'bucket'))->setValue($adapter, $bucket);

        $this->assertTrue($disk->put('daily-reports/test.png', 'image contents', 'public'));
    }
}
