<?php

namespace App\Filament\Resources\LessonFiles;

use App\Filament\Resources\LessonFiles\Pages\CreateLessonFile;
use App\Filament\Resources\LessonFiles\Pages\EditLessonFile;
use App\Filament\Resources\LessonFiles\Pages\ListLessonFiles;
use App\Filament\Resources\LessonFiles\Schemas\LessonFileForm;
use App\Filament\Resources\LessonFiles\Tables\LessonFilesTable;
use App\Models\LessonFile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LessonFileResource extends Resource
{
    protected static ?string $model = LessonFile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return LessonFileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LessonFilesTable::configure($table);
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
            'index' => ListLessonFiles::route('/'),
            'create' => CreateLessonFile::route('/create'),
            'edit' => EditLessonFile::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
