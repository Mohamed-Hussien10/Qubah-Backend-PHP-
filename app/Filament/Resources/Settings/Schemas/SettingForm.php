<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->required()
                    ->unique(ignoreRecord: true),
                Textarea::make('value')
                    ->required()
                    ->columnSpanFull()
                    ->rows(5)
                    ->placeholder('Value or JSON string'),
            ]);
    }
}
