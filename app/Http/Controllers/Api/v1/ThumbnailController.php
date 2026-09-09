<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\StorageCleaner;
use Illuminate\Http\Request;

class ThumbnailController extends Controller
{
    /**
     * Upload a thumbnail image.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,webp|max:20480',
            'folder' => 'nullable|string|in:stages,grades,sections,subjects,units,lessons,files,free-trial/stages,free-trial/grades,free_trial_subjects'
        ]);

        if ($request->hasFile('thumbnail')) {
            $folder = $request->input('folder', 'general');
            $file = $request->file('thumbnail');
            
            // Store file to the thumbnails disk
            $path = $file->store($folder, 'thumbnails');
            
            return response()->json([
                'success' => true,
                'data' => [
                    'path' => 'thumbnails/' . $path,
                    'url' => url('storage/thumbnails/' . $path)
                ],
                'message' => 'Thumbnail uploaded successfully.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No file provided.'
        ], 400);
    }

    /**
     * Delete a thumbnail image.
     */
    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        $path = $request->input('path');

        if (StorageCleaner::deleteThumbnail($path)) {
            return response()->json([
                'success' => true,
                'message' => 'Thumbnail deleted successfully.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Thumbnail not found or is currently referenced by a database record.'
        ], 400);
    }

    /**
     * Get default thumbnails for a specific stage.
     */
    public function getStageDefaultThumbnails($stageId)
    {
        $formats = ['pdf', 'video', 'audio', 'scorm', 'free_trial_pdf', 'free_trial_video', 'free_trial_audio', 'free_trial_scorm'];
        $thumbnails = [];
        
        foreach ($formats as $format) {
            // Check settings first (saved by admin)
            $key = "stage_file_thumb_{$stageId}_{$format}";
            $savedThumbnail = \App\Models\Setting::getValue($key);
            
            if ($savedThumbnail) {
                $thumbnails[$format] = $savedThumbnail;
                continue;
            }

            // Fallback: search files
            $isFreeTrial = str_starts_with($format, 'free_trial_');
            $actualFormat = $isFreeTrial ? str_replace('free_trial_', '', $format) : $format;
            $file = null;

            if ($isFreeTrial) {
                $file = \App\Models\FreeTrialLessonFile::where('type', $actualFormat)
                    ->whereNotNull('thumbnail_path')
                    ->whereHas('freeTrialSubject.freeTrialGrade', function($q) use ($stageId) {
                        $q->where('free_trial_educational_stage_id', $stageId);
                    })->first();
            } else {
                $file = \App\Models\LessonFile::where('type', $actualFormat)
                    ->whereNotNull('thumbnail_path')
                    ->whereHas('lesson.unit.subject.section.grade', function($q) use ($stageId) {
                        $q->where('educational_stage_id', $stageId);
                    })->first();
            }

            if ($file) {
                $thumbnails[$format] = $file->thumbnail_path;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $thumbnails
        ]);
    }

    /**
     * Save default thumbnail for a specific stage and format.
     */
    public function saveStageDefaultThumbnails(Request $request, $stageId)
    {
        $request->validate([
            'format' => 'required|string',
            'thumbnail_url' => 'required|string'
        ]);

        $format = $request->input('format');
        $url = $request->input('thumbnail_url');
        $key = "stage_file_thumb_{$stageId}_{$format}";

        \App\Models\Setting::setValue($key, $url);

        return response()->json([
            'success' => true,
            'message' => 'Default thumbnail saved.'
        ]);
    }

    /**
     * Delete default thumbnail for a specific stage and format.
     */
    public function deleteStageDefaultThumbnail($stageId, $format)
    {
        $key = "stage_file_thumb_{$stageId}_{$format}";
        \App\Models\Setting::where('key', $key)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Default thumbnail deleted.'
        ]);
    }
}

