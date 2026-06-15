<?php
namespace App\Services;

use App\Models\Unit;

class UnitService
{
    public function getAllActive()
    {
        return Unit::withCount('lessons')->orderBy('order')->get();
    }

    public function getById($id)
    {
        $query = Unit::where('id', $id)->where('is_active', true);
        $query->with(['lessons' => function ($q) {
            $q->where('is_active', true)->orderBy('order')->withCount('lessonFiles');
        }]);
        return $query->firstOrFail();
    }

    public function create(array $data)
    {
        return Unit::create($data);
    }

    public function update($id, array $data)
    {
        $model = Unit::findOrFail($id);
        $model->update($data);
        return $model;
    }

    public function delete($id)
    {
        $model = Unit::findOrFail($id);
        return $model->delete();
    }
}
