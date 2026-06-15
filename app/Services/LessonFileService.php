<?php
namespace App\Services;

use App\Models\LessonFile;

class LessonFileService
{
    public function getAllActive()
    {
        return LessonFile::where('is_active', true)->orderBy('order')->get();
    }

    public function getById($id)
    {
        $query = LessonFile::where('id', $id)->where('is_active', true);
        return $query->firstOrFail();
    }

    public function create(array $data)
    {
        return LessonFile::create($data);
    }

    public function update($id, array $data)
    {
        $model = LessonFile::findOrFail($id);
        $model->update($data);
        return $model;
    }

    public function delete($id)
    {
        $model = LessonFile::findOrFail($id);
        return $model->delete();
    }
}
