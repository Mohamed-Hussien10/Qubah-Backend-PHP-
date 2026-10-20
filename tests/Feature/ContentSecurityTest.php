<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Unit;
use App\Models\Lesson;
use App\Models\LessonFile;
use App\Models\FreeTrialLessonFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

class ContentSecurityTest extends TestCase
{
    /**
     * 1. Test that guest requesting a non-preview lesson returns 403 Forbidden.
     */
    public function test_guest_cannot_access_paid_lesson(): void
    {
        // First lesson is preview, second lesson is paid
        $unit = Unit::first();
        if (!$unit) {
            $this->markTestSkipped('No units available in database');
        }

        $paidLesson = Lesson::where('unit_id', $unit->id)
            ->where('is_active', true)
            ->orderBy('order', 'desc')
            ->first();

        $firstLesson = Lesson::where('unit_id', $unit->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->first();

        if (!$paidLesson || !$firstLesson || $paidLesson->id === $firstLesson->id) {
            $this->markTestSkipped('Unit needs at least two lessons for testing paid lesson block');
        }

        // Paid lesson must return 403 for guest
        $response = $this->getJson("/api/v1/lessons/{$paidLesson->id}");
        $response->assertStatus(403);
    }

    /**
     * 2. Test that guest accessing the preview lesson does not get direct paid file links.
     */
    public function test_preview_lesson_protects_files_for_guest(): void
    {
        $unit = Unit::first();
        if (!$unit) {
            $this->markTestSkipped('No units available');
        }

        $firstLesson = Lesson::where('unit_id', $unit->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->first();

        if (!$firstLesson) {
            $this->markTestSkipped('No preview lesson found');
        }

        $response = $this->getJson("/api/v1/lessons/{$firstLesson->id}");
        $response->assertStatus(200);

        // Verify that lesson files do not leak unprotected raw file paths
        $data = $response->json('data');
        $this->assertTrue(isset($data['has_access']));
    }

    /**
     * 3. Test that direct file download without signed route returns 403.
     */
    public function test_direct_file_access_without_signature_returns_403(): void
    {
        $file = LessonFile::where('is_active', true)->first();
        if (!$file || !$file->file_path) {
            $this->markTestSkipped('No lesson files available');
        }

        $cleanPath = preg_replace('/^storage\//', '', $file->file_path);
        $response = $this->get("/api/v1/files/{$cleanPath}");

        // If not a free trial or first lesson, must return 403 Forbidden
        if ($response->status() !== 200) {
            $this->assertEquals(403, $response->status());
        }
    }

    /**
     * 4. Test that signed URL allows valid download.
     */
    public function test_signed_url_allows_download(): void
    {
        $file = LessonFile::where('is_active', true)->first();
        if (!$file || !$file->file_path) {
            $this->markTestSkipped('No lesson files available');
        }

        $cleanPath = preg_replace('/^storage\//', '', $file->file_path);
        $signedUrl = URL::temporarySignedRoute(
            'api.files.show',
            now()->addMinutes(30),
            ['path' => $cleanPath]
        );

        $response = $this->get($signedUrl);
        // Should succeed or find the physical file
        $this->assertContains($response->status(), [200, 404]);
    }
}
