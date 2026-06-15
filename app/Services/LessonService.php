<?php
namespace App\Services;

use App\Models\Lesson;

class LessonService
{
    public function getAllActive()
    {
        return Lesson::withCount('lessonFiles')->orderBy('order')->get();
    }

    public function getById($id)
    {
        $query = Lesson::where('id', $id)->where('is_active', true);
        $query->with(['lessonFiles' => function ($q) {
            $q->where('is_active', true)->orderBy('order');
        }]);
        return $query->firstOrFail();
    }

    public function create(array $data)
    {
        return Lesson::create($data);
    }

    public function update($id, array $data)
    {
        $model = Lesson::findOrFail($id);
        $model->update($data);
        return $model;
    }

    public function delete($id)
    {
        $model = Lesson::findOrFail($id);
        return $model->delete();
    }
}
