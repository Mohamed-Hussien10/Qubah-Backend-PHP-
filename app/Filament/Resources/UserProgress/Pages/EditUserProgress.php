<?php

namespace App\Filament\Resources\UserProgress\Pages;

use App\Filament\Resources\UserProgress\UserProgressResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUserProgress extends EditRecord
{
    protected static string $resource = UserProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
