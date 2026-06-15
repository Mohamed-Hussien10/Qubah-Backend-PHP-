<?php

namespace App\Filament\Resources\UserProgress\Schemas;

use App\Enums\ProgressStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserProgressForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('content_id')
                    ->relationship('content', 'title')
                    ->required(),
                Select::make('status')
                    ->options(ProgressStatus::class)
                    ->default('started')
                    ->required(),
                TextInput::make('time_spent')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('score')
                    ->numeric(),
            ]);
    }
}
