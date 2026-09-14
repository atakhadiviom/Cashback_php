<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\FollowupService;
use PHPUnit\Framework\TestCase;

final class FollowupAttachmentUploadTest extends TestCase
{
    private FollowupService $service;
    private \ReflectionMethod $method;

    protected function setUp(): void
    {
        $reflection = new \ReflectionClass(FollowupService::class);
        $this->service = $reflection->newInstanceWithoutConstructor();
        $this->method = $reflection->getMethod('handleUpload');
    }

    public function testReturnsNullWhenNoFileUploaded(): void
    {
        $errors = [];
        $res = $this->method->invokeArgs($this->service, [null, &$errors]);
        $this->assertNull($res);
        $this->assertEmpty($errors);

        $res = $this->method->invokeArgs($this->service, [['error' => UPLOAD_ERR_NO_FILE], &$errors]);
        $this->assertNull($res);
        $this->assertEmpty($errors);
    }

    public function testRejectsDisallowedExtensions(): void
    {
        $errors = [];
        $file = [
            'name' => 'malicious.php',
            'error' => UPLOAD_ERR_OK,
            'size' => 1024,
            'tmp_name' => '/tmp/fake',
        ];
        $res = $this->method->invokeArgs($this->service, [$file, &$errors]);
        $this->assertNull($res);
        $this->assertArrayHasKey('attachment_file', $errors);
        $this->assertStringContainsString('فرمت', $errors['attachment_file']);
    }

    public function testRejectsOverSizedFile(): void
    {
        $errors = [];
        $file = [
            'name' => 'large.pdf',
            'error' => UPLOAD_ERR_OK,
            'size' => 25 * 1024 * 1024,
            'tmp_name' => '/tmp/fake',
        ];
        $res = $this->method->invokeArgs($this->service, [$file, &$errors]);
        $this->assertNull($res);
        $this->assertArrayHasKey('attachment_file', $errors);
        $this->assertStringContainsString('۲۰ مگابایت', $errors['attachment_file']);
    }
}
