<?php
namespace App\Services;

use App\Models\Grade;

class GradeService
{
    public function getAllActive()
    {
        return Grade::withCount('sections')->orderBy('order')->get();
    }

    public function getById($id)
    {
        $query = Grade::where('id', $id)->where('is_active', true);
        $query->with(['sections' => function ($q) {
            $q->where('is_active', true)->orderBy('order')->withCount('subjects');
        }]);
        return $query->firstOrFail();
    }

    public function create(array $data)
    {
        return Grade::create($data);
    }

    public function update($id, array $data)
    {
        $model = Grade::findOrFail($id);
        $model->update($data);
        return $model;
    }

    public function delete($id)
    {
        $model = Grade::findOrFail($id);
        return $model->delete();
    }
}
