<?php

namespace App\Filament\Resources\LessonFiles\Pages;

use App\Filament\Resources\LessonFiles\LessonFileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLessonFile extends CreateRecord
{
    protected static string $resource = LessonFileResource::class;
}
