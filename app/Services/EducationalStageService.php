<?php
namespace App\Services;

use App\Models\EducationalStage;

class EducationalStageService
{
    public function getAllActive()
    {
        return EducationalStage::withCount('grades')->orderBy('order')->get();
    }

    public function getById($id)
    {
        $query = EducationalStage::where('id', $id)->where('is_active', true);
        $query->with(['grades' => function ($q) {
            $q->where('is_active', true)->orderBy('order')->withCount('sections');
        }]);
        return $query->firstOrFail();
    }

    public function create(array $data)
    {
        return EducationalStage::create($data);
    }

    public function update($id, array $data)
    {
        $model = EducationalStage::findOrFail($id);
        $model->update($data);
        return $model;
    }

    public function delete($id)
    {
        $model = EducationalStage::findOrFail($id);
        return $model->delete();
    }
}
