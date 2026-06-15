<?php

namespace App\Filament\Resources\UserProgress;

use App\Filament\Resources\UserProgress\Pages\CreateUserProgress;
use App\Filament\Resources\UserProgress\Pages\EditUserProgress;
use App\Filament\Resources\UserProgress\Pages\ListUserProgress;
use App\Filament\Resources\UserProgress\Schemas\UserProgressForm;
use App\Filament\Resources\UserProgress\Tables\UserProgressTable;
use App\Models\UserProgress;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserProgressResource extends Resource
{
    protected static ?string $model = UserProgress::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return UserProgressForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserProgressTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserProgress::route('/'),
            'create' => CreateUserProgress::route('/create'),
            'edit' => EditUserProgress::route('/{record}/edit'),
        ];
    }
}
