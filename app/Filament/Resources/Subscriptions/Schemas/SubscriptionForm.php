<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $user = \App\Models\User::find($state);
                        if ($user) {
                            $set('user_name', $user->name);
                        }
                    }),
                TextInput::make('user_name')
                    ->required(),
                Select::make('plan_name')
                    ->options(function () {
                        return \App\Models\Plan::all()->pluck('name', 'name')->toArray();
                    })
                    ->required(),
                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('active')
                    ->required(),
                DateTimePicker::make('start_date')
                    ->required(),
                DateTimePicker::make('end_date')
                    ->required(),
                TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->prefix('$'),
            ]);
    }
}
