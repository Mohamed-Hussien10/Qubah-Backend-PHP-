<?php

namespace App\Filament\Resources\LessonFiles\Pages;

use App\Filament\Resources\LessonFiles\LessonFileResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditLessonFile extends EditRecord
{
    protected static string $resource = LessonFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
