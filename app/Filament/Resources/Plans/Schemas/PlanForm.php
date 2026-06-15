<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TagsInput;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('price')
                    ->numeric()
                    ->required()
                    ->prefix('$'),
                TextInput::make('duration_months')
                    ->numeric()
                    ->required()
                    ->default(12),
                TagsInput::make('features')
                    ->placeholder('New feature')
                    ->nullable(),
                Toggle::make('is_active')
                    ->required()
                    ->default(true),
            ]);
    }
}
