<?php
namespace App\Services;

use App\Models\Section;

class SectionService
{
    public function getAllActive()
    {
        return Section::withCount('subjects')->orderBy('order')->get();
    }

    public function getById($id)
    {
        $query = Section::where('id', $id)->where('is_active', true);
        $query->with(['subjects' => function ($q) {
            $q->where('is_active', true)->orderBy('order')->withCount('units');
        }]);
        return $query->firstOrFail();
    }

    public function create(array $data)
    {
        return Section::create($data);
    }

    public function update($id, array $data)
    {
        $model = Section::findOrFail($id);
        $model->update($data);
        return $model;
    }

    public function delete($id)
    {
        $model = Section::findOrFail($id);
        return $model->delete();
    }
}
