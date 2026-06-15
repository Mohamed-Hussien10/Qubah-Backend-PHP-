<?php

namespace App\Filament\Resources\EducationalStages\Pages;

use App\Filament\Resources\EducationalStages\EducationalStageResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditEducationalStage extends EditRecord
{
    protected static string $resource = EducationalStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
