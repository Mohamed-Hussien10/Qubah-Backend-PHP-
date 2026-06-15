<?php

namespace App\Filament\Resources\UserProgress\Pages;

use App\Filament\Resources\UserProgress\UserProgressResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserProgress extends ListRecords
{
    protected static string $resource = UserProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
