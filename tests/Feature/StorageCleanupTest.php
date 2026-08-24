<?php

namespace Tests\Feature;

use App\Models\EducationalStage;
use App\Models\FreeTrialEducationalStage;
use App\Models\FreeTrialGrade;
use App\Models\FreeTrialLessonFile;
use App\Models\FreeTrialSubject;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\LessonFile;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Unit;
use App\Services\StorageCleaner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StorageCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure managed storage directories exist
        File::ensureDirectoryExists(storage_path('app/public/lesson_files'));
        File::ensureDirectoryExists(storage_path('app/public/thumbnails/stages'));
        File::ensureDirectoryExists(storage_path('app/public/thumbnails/grades'));
        File::ensureDirectoryExists(storage_path('app/public/thumbnails/sections'));
        File::ensureDirectoryExists(storage_path('app/public/thumbnails/subjects'));
        File::ensureDirectoryExists(storage_path('app/public/thumbnails/units'));
        File::ensureDirectoryExists(storage_path('app/public/thumbnails/lessons'));
        File::ensureDirectoryExists(storage_path('app/public/thumbnails/files'));
        File::ensureDirectoryExists(storage_path('app/public/thumbnails/free-trial/stages'));
        File::ensureDirectoryExists(storage_path('app/public/thumbnails/free-trial/grades'));
        File::ensureDirectoryExists(storage_path('app/public/thumbnails/free_trial_subjects'));
    }

    protected function createDummyFile(string $relativePath, string $content = 'dummy content'): string
    {
        $fullPath = storage_path('app/public/' . $relativePath);
        File::ensureDirectoryExists(dirname($fullPath));
        File::put($fullPath, $content);
        return $fullPath;
    }

    /** 1. Soft delete MUST NOT delete physical files or thumbnails */
    public function test_soft_delete_preserves_physical_files_and_thumbnails(): void
    {
        $filePath = 'lesson_files/test_soft_delete.pdf';
        $thumbPath = 'thumbnails/files/test_soft_delete_thumb.jpg';

        $this->createDummyFile($filePath);
        $this->createDummyFile($thumbPath);

        $stage = EducationalStage::create(['title' => 'Stage 1']);
        $grade = Grade::create(['title' => 'Grade 1', 'educational_stage_id' => $stage->id]);
        $section = Section::create(['title' => 'Sec 1', 'grade_id' => $grade->id]);
        $subject = Subject::create(['title' => 'Sub 1', 'section_id' => $section->id]);
        $unit = Unit::create(['title' => 'Unit 1', 'subject_id' => $subject->id]);
        $lesson = Lesson::create(['title' => 'Lesson 1', 'unit_id' => $unit->id]);

        $lessonFile = LessonFile::create([
            'lesson_id' => $lesson->id,
            'title' => 'Test File',
            'type' => 'pdf',
            'file_path' => $filePath,
            'thumbnail_path' => $thumbPath,
        ]);

        $lessonFile->delete(); // Soft delete

        $this->assertTrue($lessonFile->trashed());
        $this->assertFileExists(storage_path('app/public/' . $filePath));
        $this->assertFileExists(storage_path('app/public/' . $thumbPath));
    }

    /** 2. Force delete removes physical files and thumbnails */
    public function test_force_delete_removes_physical_files_and_thumbnails(): void
    {
        $filePath = 'lesson_files/test_force_delete.pdf';
        $thumbPath = 'thumbnails/files/test_force_delete_thumb.jpg';

        $this->createDummyFile($filePath);
        $this->createDummyFile($thumbPath);

        $stage = EducationalStage::create(['title' => 'Stage 1']);
        $grade = Grade::create(['title' => 'Grade 1', 'educational_stage_id' => $stage->id]);
        $section = Section::create(['title' => 'Sec 1', 'grade_id' => $grade->id]);
        $subject = Subject::create(['title' => 'Sub 1', 'section_id' => $section->id]);
        $unit = Unit::create(['title' => 'Unit 1', 'subject_id' => $subject->id]);
        $lesson = Lesson::create(['title' => 'Lesson 1', 'unit_id' => $unit->id]);

        $lessonFile = LessonFile::create([
            'lesson_id' => $lesson->id,
            'title' => 'Test File',
            'type' => 'pdf',
            'file_path' => $filePath,
            'thumbnail_path' => $thumbPath,
        ]);

        $lessonFile->forceDelete(); // Permanent delete

        $this->assertDatabaseMissing('lesson_files', ['id' => $lessonFile->id]);
        $this->assertFileDoesNotExist(storage_path('app/public/' . $filePath));
        $this->assertFileDoesNotExist(storage_path('app/public/' . $thumbPath));
    }

    /** 3. Shared files are protected and never deleted while another record references them */
    public function test_shared_file_is_preserved_until_last_reference_force_deleted(): void
    {
        $sharedFilePath = 'lesson_files/shared_file.pdf';
        $this->createDummyFile($sharedFilePath);

        $stage = EducationalStage::create(['title' => 'Stage 1']);
        $grade = Grade::create(['title' => 'Grade 1', 'educational_stage_id' => $stage->id]);
        $section = Section::create(['title' => 'Sec 1', 'grade_id' => $grade->id]);
        $subject = Subject::create(['title' => 'Sub 1', 'section_id' => $section->id]);
        $unit = Unit::create(['title' => 'Unit 1', 'subject_id' => $subject->id]);
        $lesson = Lesson::create(['title' => 'Lesson 1', 'unit_id' => $unit->id]);

        $fileA = LessonFile::create([
            'lesson_id' => $lesson->id,
            'title' => 'File A',
            'type' => 'pdf',
            'file_path' => $sharedFilePath,
        ]);

        $fileB = LessonFile::create([
            'lesson_id' => $lesson->id,
            'title' => 'File B',
            'type' => 'pdf',
            'file_path' => 'storage/' . $sharedFilePath, // variation in prefix
        ]);

        // Force delete A -> file MUST remain because B references it
        $fileA->forceDelete();
        $this->assertFileExists(storage_path('app/public/' . $sharedFilePath));

        // Force delete B -> file should now be deleted
        $fileB->forceDelete();
        $this->assertFileDoesNotExist(storage_path('app/public/' . $sharedFilePath));
    }

    /** 4. File replacement deletes old file only after DB update and keeps thumbnail untouched */
    public function test_file_path_replacement_deletes_old_file_and_preserves_thumbnail(): void
    {
        $oldFile = 'lesson_files/old_doc.pdf';
        $newFile = 'lesson_files/new_doc.pdf';
        $thumb = 'thumbnails/files/doc_thumb.jpg';

        $this->createDummyFile($oldFile);
        $this->createDummyFile($newFile);
        $this->createDummyFile($thumb);

        $stage = EducationalStage::create(['title' => 'Stage 1']);
        $grade = Grade::create(['title' => 'Grade 1', 'educational_stage_id' => $stage->id]);
        $section = Section::create(['title' => 'Sec 1', 'grade_id' => $grade->id]);
        $subject = Subject::create(['title' => 'Sub 1', 'section_id' => $section->id]);
        $unit = Unit::create(['title' => 'Unit 1', 'subject_id' => $subject->id]);
        $lesson = Lesson::create(['title' => 'Lesson 1', 'unit_id' => $unit->id]);

        $lessonFile = LessonFile::create([
            'lesson_id' => $lesson->id,
            'title' => 'Doc',
            'type' => 'pdf',
            'file_path' => $oldFile,
            'thumbnail_path' => $thumb,
        ]);

        // Update file_path
        $lessonFile->update(['file_path' => $newFile]);

        $this->assertFileDoesNotExist(storage_path('app/public/' . $oldFile));
        $this->assertFileExists(storage_path('app/public/' . $newFile));
        $this->assertFileExists(storage_path('app/public/' . $thumb)); // Thumbnail remains untouched
    }

    /** 5. Thumbnail replacement deletes old thumbnail and keeps main file untouched */
    public function test_thumbnail_path_replacement_deletes_old_thumbnail_and_preserves_file(): void
    {
        $file = 'lesson_files/main_video.mp4';
        $oldThumb = 'thumbnails/files/old_video_thumb.jpg';
        $newThumb = 'thumbnails/files/new_video_thumb.jpg';

        $this->createDummyFile($file);
        $this->createDummyFile($oldThumb);
        $this->createDummyFile($newThumb);

        $stage = EducationalStage::create(['title' => 'Stage 1']);
        $grade = Grade::create(['title' => 'Grade 1', 'educational_stage_id' => $stage->id]);
        $section = Section::create(['title' => 'Sec 1', 'grade_id' => $grade->id]);
        $subject = Subject::create(['title' => 'Sub 1', 'section_id' => $section->id]);
        $unit = Unit::create(['title' => 'Unit 1', 'subject_id' => $subject->id]);
        $lesson = Lesson::create(['title' => 'Lesson 1', 'unit_id' => $unit->id]);

        $lessonFile = LessonFile::create([
            'lesson_id' => $lesson->id,
            'title' => 'Video',
            'type' => 'video',
            'file_path' => $file,
            'thumbnail_path' => $oldThumb,
        ]);

        // Update thumbnail_path
        $lessonFile->update(['thumbnail_path' => $newThumb]);

        $this->assertFileDoesNotExist(storage_path('app/public/' . $oldThumb));
        $this->assertFileExists(storage_path('app/public/' . $newThumb));
        $this->assertFileExists(storage_path('app/public/' . $file)); // Main file untouched
    }

    /** 6. External URLs are never deleted or modified */
    public function test_external_urls_are_never_deleted(): void
    {
        $externalUrl = 'https://cdn.example.com/videos/lesson1.mp4';
        $this->assertNull(StorageCleaner::normalizePath($externalUrl));
        $this->assertFalse(StorageCleaner::deleteFile($externalUrl));
    }

    /** 7. Path traversal attacks are rejected and blocked */
    public function test_path_traversal_is_blocked(): void
    {
        $this->assertNull(StorageCleaner::normalizePath('../../.env'));
        $this->assertNull(StorageCleaner::normalizePath('lesson_files/../../../secret.txt'));
        $this->assertFalse(StorageCleaner::deleteFile('../../.env'));
    }

    /** 8. Main hierarchy permanent cascade delete */
    public function test_main_hierarchy_force_delete_cascades_and_cleans_all_assets(): void
    {
        $stageThumb = 'thumbnails/stages/stage_th.jpg';
        $stageBg = 'thumbnails/stages/stage_bg.jpg';
        $gradeThumb = 'thumbnails/grades/grade_th.jpg';
        $sectionThumb = 'thumbnails/sections/sec_th.jpg';
        $subjectThumb = 'thumbnails/subjects/sub_th.jpg';
        $unitThumb = 'thumbnails/units/unit_th.jpg';
        $lessonThumb = 'thumbnails/lessons/lesson_th.jpg';
        $fileMain = 'lesson_files/cascade_file.pdf';
        $fileThumb = 'thumbnails/files/cascade_file_th.jpg';

        $this->createDummyFile($stageThumb);
        $this->createDummyFile($stageBg);
        $this->createDummyFile($gradeThumb);
        $this->createDummyFile($sectionThumb);
        $this->createDummyFile($subjectThumb);
        $this->createDummyFile($unitThumb);
        $this->createDummyFile($lessonThumb);
        $this->createDummyFile($fileMain);
        $this->createDummyFile($fileThumb);

        $stage = EducationalStage::create(['title' => 'Stage Cascade', 'thumbnail_path' => $stageThumb, 'background_image_path' => $stageBg]);
        $grade = Grade::create(['title' => 'Grade', 'educational_stage_id' => $stage->id, 'thumbnail_path' => $gradeThumb]);
        $section = Section::create(['title' => 'Section', 'grade_id' => $grade->id, 'thumbnail_path' => $sectionThumb]);
        $subject = Subject::create(['title' => 'Subject', 'section_id' => $section->id, 'thumbnail_path' => $subjectThumb]);
        $unit = Unit::create(['title' => 'Unit', 'subject_id' => $subject->id, 'thumbnail_path' => $unitThumb]);
        $lesson = Lesson::create(['title' => 'Lesson', 'unit_id' => $unit->id, 'thumbnail_path' => $lessonThumb]);
        $file = LessonFile::create([
            'lesson_id' => $lesson->id,
            'title' => 'File',
            'type' => 'pdf',
            'file_path' => $fileMain,
            'thumbnail_path' => $fileThumb,
        ]);

        // Soft-deleted child should also be force-deleted when parent is force-deleted
        $file->delete(); // Soft-delete child

        // Force delete root stage
        $stage->forceDelete();

        // Verify all records permanently removed from DB
        $this->assertDatabaseMissing('educational_stages', ['id' => $stage->id]);
        $this->assertDatabaseMissing('grades', ['id' => $grade->id]);
        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
        $this->assertDatabaseMissing('lesson_files', ['id' => $file->id]);

        // Verify all physical files removed from disk
        $this->assertFileDoesNotExist(storage_path('app/public/' . $stageThumb));
        $this->assertFileDoesNotExist(storage_path('app/public/' . $stageBg));
        $this->assertFileDoesNotExist(storage_path('app/public/' . $gradeThumb));
        $this->assertFileDoesNotExist(storage_path('app/public/' . $sectionThumb));
        $this->assertFileDoesNotExist(storage_path('app/public/' . $subjectThumb));
        $this->assertFileDoesNotExist(storage_path('app/public/' . $unitThumb));
        $this->assertFileDoesNotExist(storage_path('app/public/' . $lessonThumb));
        $this->assertFileDoesNotExist(storage_path('app/public/' . $fileMain));
        $this->assertFileDoesNotExist(storage_path('app/public/' . $fileThumb));
    }

    /** 9. Free Trial hierarchy permanent cascade delete */
    public function test_free_trial_hierarchy_force_delete_cascades_and_cleans_all_assets(): void
    {
        $ftStageThumb = 'thumbnails/free-trial/stages/ft_stage.jpg';
        $ftGradeThumb = 'thumbnails/free-trial/grades/ft_grade.jpg';
        $ftSubjectThumb = 'thumbnails/free_trial_subjects/ft_sub.jpg';
        $ftFile = 'lesson_files/ft_file.pdf';
        $ftFileThumb = 'thumbnails/files/ft_file_th.jpg';

        $this->createDummyFile($ftStageThumb);
        $this->createDummyFile($ftGradeThumb);
        $this->createDummyFile($ftSubjectThumb);
        $this->createDummyFile($ftFile);
        $this->createDummyFile($ftFileThumb);

        $stage = FreeTrialEducationalStage::create(['title' => 'FT Stage', 'thumbnail_path' => $ftStageThumb]);
        $grade = FreeTrialGrade::create(['title' => 'FT Grade', 'free_trial_educational_stage_id' => $stage->id, 'thumbnail_path' => $ftGradeThumb]);
        $subject = FreeTrialSubject::create(['title' => 'FT Subject', 'free_trial_grade_id' => $grade->id, 'thumbnail_path' => $ftSubjectThumb]);
        $lessonFile = FreeTrialLessonFile::create([
            'free_trial_subject_id' => $subject->id,
            'title' => 'FT File',
            'type' => 'pdf',
            'file_path' => $ftFile,
            'thumbnail_path' => $ftFileThumb,
        ]);

        $stage->forceDelete();

        $this->assertDatabaseMissing('free_trial_educational_stages', ['id' => $stage->id]);
        $this->assertDatabaseMissing('free_trial_grades', ['id' => $grade->id]);
        $this->assertDatabaseMissing('free_trial_subjects', ['id' => $subject->id]);
        $this->assertDatabaseMissing('free_trial_lesson_files', ['id' => $lessonFile->id]);

        $this->assertFileDoesNotExist(storage_path('app/public/' . $ftStageThumb));
        $this->assertFileDoesNotExist(storage_path('app/public/' . $ftGradeThumb));
        $this->assertFileDoesNotExist(storage_path('app/public/' . $ftSubjectThumb));
        $this->assertFileDoesNotExist(storage_path('app/public/' . $ftFile));
        $this->assertFileDoesNotExist(storage_path('app/public/' . $ftFileThumb));
    }

    /** 10. Orphan cleanup command removes unreferenced files and keeps active/soft-deleted ones */
    public function test_orphan_cleanup_command(): void
    {
        $activeFile = 'lesson_files/active_ref.pdf';
        $softDeletedFile = 'lesson_files/soft_del_ref.pdf';
        $orphanFile = 'lesson_files/truly_orphan.pdf';

        $this->createDummyFile($activeFile);
        $this->createDummyFile($softDeletedFile);
        $this->createDummyFile($orphanFile);

        $stage = EducationalStage::create(['title' => 'Stage']);
        $grade = Grade::create(['title' => 'Grade', 'educational_stage_id' => $stage->id]);
        $section = Section::create(['title' => 'Section', 'grade_id' => $grade->id]);
        $subject = Subject::create(['title' => 'Subject', 'section_id' => $section->id]);
        $unit = Unit::create(['title' => 'Unit', 'subject_id' => $subject->id]);
        $lesson = Lesson::create(['title' => 'Lesson', 'unit_id' => $unit->id]);

        $active = LessonFile::create([
            'lesson_id' => $lesson->id,
            'title' => 'Active',
            'type' => 'pdf',
            'file_path' => $activeFile,
        ]);

        $softDel = LessonFile::create([
            'lesson_id' => $lesson->id,
            'title' => 'SoftDel',
            'type' => 'pdf',
            'file_path' => $softDeletedFile,
        ]);
        $softDel->delete(); // Soft-deleted

        $this->artisan('storage:clean')
            ->expectsOutputToContain('Cleanup complete')
            ->assertSuccessful();

        $this->assertFileExists(storage_path('app/public/' . $activeFile));
        $this->assertFileExists(storage_path('app/public/' . $softDeletedFile)); // Soft-deleted ref protected by default
        $this->assertFileDoesNotExist(storage_path('app/public/' . $orphanFile)); // Orphan cleaned
    }

    /** 11. Dry-run storage clean does not delete any file */
    public function test_storage_clean_dry_run_deletes_nothing(): void
    {
        $orphanFile = 'lesson_files/dry_run_orphan.pdf';
        $this->createDummyFile($orphanFile);

        $this->artisan('storage:clean --dry-run')
            ->expectsOutputToContain('DRY RUN')
            ->assertSuccessful();

        $this->assertFileExists(storage_path('app/public/' . $orphanFile));
        @unlink(storage_path('app/public/' . $orphanFile));
    }

    /** 12. Storage reset requires --force flag and safely wipes managed storage */
    public function test_storage_reset_requires_force_flag_and_preserves_structure(): void
    {
        $testFile1 = 'lesson_files/reset_test_1.pdf';
        $testFile2 = 'thumbnails/stages/reset_test_2.jpg';

        $this->createDummyFile($testFile1);
        $this->createDummyFile($testFile2);

        // Without --force -> failure, files preserved
        $this->artisan('storage:reset')
            ->assertFailed();

        $this->assertFileExists(storage_path('app/public/' . $testFile1));
        $this->assertFileExists(storage_path('app/public/' . $testFile2));

        // With --force -> success, files deleted, folders remain
        $this->artisan('storage:reset --force')
            ->expectsOutputToContain('Storage reset complete!')
            ->assertSuccessful();

        $this->assertFileDoesNotExist(storage_path('app/public/' . $testFile1));
        $this->assertFileDoesNotExist(storage_path('app/public/' . $testFile2));
        $this->assertDirectoryExists(storage_path('app/public/lesson_files'));
        $this->assertDirectoryExists(storage_path('app/public/thumbnails/stages'));
    }
}
