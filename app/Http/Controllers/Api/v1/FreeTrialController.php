<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\FreeTrialEducationalStage;
use App\Models\FreeTrialGrade;
use App\Models\FreeTrialSubject;
use App\Models\FreeTrialLessonFile;
use Illuminate\Http\Request;

class FreeTrialController extends Controller
{
    // === PUBLIC ROUTES === //

    public function getStages()
    {
        return response()->json(['data' => FreeTrialEducationalStage::withCount('grades')->orderBy('order')->get()]);
    }

    public function getGradesByStage($stageId)
    {
        return response()->json(['data' => FreeTrialGrade::where('free_trial_educational_stage_id', $stageId)->withCount('subjects')->orderBy('order')->get()]);
    }

    public function getSubjectsByGrade($gradeId)
    {
        return response()->json(['data' => FreeTrialSubject::where('free_trial_grade_id', $gradeId)->withCount('lessonFiles')->where('is_active', true)->orderBy('order')->get()]);
    }

    public function showSubject($id)
    {
        return response()->json(['data' => FreeTrialSubject::findOrFail($id)]);
    }

    public function getLessonFilesBySubject($subjectId)
    {
        return response()->json(['data' => FreeTrialLessonFile::where('free_trial_subject_id', $subjectId)->where('is_active', true)->orderBy('order')->get()]);
    }

    public function showLessonFile($id)
    {
        return response()->json(['data' => FreeTrialLessonFile::findOrFail($id)]);
    }

    // === ADMIN ROUTES === //

    // Stages
    public function storeStage(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'thumbnail_path' => 'nullable|string',
            'background_image_path' => 'nullable|string',
            'order' => 'integer',
            'is_active' => 'boolean'
        ]);
        $stage = FreeTrialEducationalStage::create($data);
        return response()->json(['data' => $stage], 201);
    }

    public function updateStage(Request $request, $id)
    {
        $stage = FreeTrialEducationalStage::findOrFail($id);
        $stage->update($request->all());
        return response()->json(['data' => $stage]);
    }

    public function destroyStage($id)
    {
        FreeTrialEducationalStage::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // Grades
    public function storeGrade(Request $request)
    {
        $data = $request->validate([
            'free_trial_educational_stage_id' => 'required|exists:free_trial_educational_stages,id',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'thumbnail_path' => 'nullable|string',
            'order' => 'integer',
            'is_active' => 'boolean'
        ]);
        $grade = FreeTrialGrade::create($data);
        return response()->json(['data' => $grade], 201);
    }

    public function updateGrade(Request $request, $id)
    {
        $grade = FreeTrialGrade::findOrFail($id);
        $grade->update($request->all());
        return response()->json(['data' => $grade]);
    }

    public function destroyGrade($id)
    {
        FreeTrialGrade::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // Subjects
    public function storeSubject(Request $request)
    {
        $data = $request->validate([
            'free_trial_grade_id' => 'required|exists:free_trial_grades,id',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'thumbnail_path' => 'nullable|string',
            'order' => 'integer',
            'is_active' => 'boolean'
        ]);
        $subject = FreeTrialSubject::create($data);
        return response()->json(['data' => $subject], 201);
    }

    public function updateSubject(Request $request, $id)
    {
        $subject = FreeTrialSubject::findOrFail($id);
        $subject->update($request->all());
        return response()->json(['data' => $subject]);
    }

    public function destroySubject($id)
    {
        FreeTrialSubject::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // Lesson Files
    public function storeLessonFile(Request $request)
    {
        $data = $request->validate([
            'free_trial_subject_id' => 'required|exists:free_trial_subjects,id',
            'title' => 'required|string',
            'type' => 'required|string',
            'file_path' => 'nullable|string',
            'thumbnail_path' => 'nullable|string',
            'order' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array'
        ]);
        $file = FreeTrialLessonFile::create($data);
        return response()->json(['data' => $file], 201);
    }

    public function uploadLessonFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'free_trial_subject_id' => 'required|exists:free_trial_subjects,id',
            'title' => 'required|string',
            'type' => 'required|string',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            
            // Store file in 'public/lesson_files'
            $path = $file->store('lesson_files', 'public');
            
            $data = $request->only(['free_trial_subject_id', 'title', 'type']);
            $data['file_path'] = $path;
            
            // Add metadata
            $data['metadata'] = [
                'file_size' => round($file->getSize() / 1048576, 2) . ' MB',
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType()
            ];

            $model = FreeTrialLessonFile::create($data);
            return response()->json(['data' => $model], 201);
        }

        return response()->json([
            'success' => false, 
            'message' => 'No file provided.'
        ], 400);
    }

    public function updateLessonFile(Request $request, $id)
    {
        $file = FreeTrialLessonFile::findOrFail($id);
        $file->update($request->all());
        return response()->json(['data' => $file]);
    }

    public function destroyLessonFile($id)
    {
        FreeTrialLessonFile::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
