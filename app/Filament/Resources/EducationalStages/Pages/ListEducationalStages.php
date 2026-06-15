<?php

namespace App\Filament\Resources\EducationalStages\Pages;

use App\Filament\Resources\EducationalStages\EducationalStageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEducationalStages extends ListRecords
{
    protected static string $resource = EducationalStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
