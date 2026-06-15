<?php

namespace App\Filament\Resources\Notifications\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class NotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                Textarea::make('body')
                    ->required()
                    ->columnSpanFull(),
                Select::make('target_type')
                    ->options([
                        'all' => 'All Users',
                        'stage' => 'Educational Stage',
                        'grade' => 'Grade',
                    ])
                    ->default('all')
                    ->required(),
                TextInput::make('target_id')
                    ->numeric()
                    ->nullable(),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'scheduled' => 'Scheduled',
                        'sent' => 'Sent',
                    ])
                    ->default('draft')
                    ->required(),
                DateTimePicker::make('sent_at')
                    ->nullable(),
            ]);
    }
}
