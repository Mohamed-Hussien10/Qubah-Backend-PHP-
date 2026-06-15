<?php

namespace App\Filament\Resources\LessonFiles\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LessonFileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('lesson_id')
                    ->relationship('lesson', 'title')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('type')
                    ->required(),
                TextInput::make('file_path'),
                TextInput::make('thumbnail_path'),
                Textarea::make('metadata')
                    ->columnSpanFull(),
                TextInput::make('order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
