<?php

namespace App\Filament\Resources\LessonFiles\Pages;

use App\Filament\Resources\LessonFiles\LessonFileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLessonFiles extends ListRecords
{
    protected static string $resource = LessonFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
