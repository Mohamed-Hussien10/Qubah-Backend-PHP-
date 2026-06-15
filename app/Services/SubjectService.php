<?php
namespace App\Services;

use App\Models\Subject;

class SubjectService
{
    public function getAllActive()
    {
        return Subject::withCount('units')->orderBy('order')->get();
    }

    public function getById($id)
    {
        $query = Subject::where('id', $id)->where('is_active', true);
        $query->with(['units' => function ($q) {
            $q->where('is_active', true)->orderBy('order')->withCount('lessons');
        }]);
        return $query->firstOrFail();
    }

    public function create(array $data)
    {
        return Subject::create($data);
    }

    public function update($id, array $data)
    {
        $model = Subject::findOrFail($id);
        $model->update($data);
        return $model;
    }

    public function delete($id)
    {
        $model = Subject::findOrFail($id);
        return $model->delete();
    }
}
