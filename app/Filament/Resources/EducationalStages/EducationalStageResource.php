<?php

namespace App\Filament\Resources\EducationalStages;

use App\Filament\Resources\EducationalStages\Pages\CreateEducationalStage;
use App\Filament\Resources\EducationalStages\Pages\EditEducationalStage;
use App\Filament\Resources\EducationalStages\Pages\ListEducationalStages;
use App\Filament\Resources\EducationalStages\Schemas\EducationalStageForm;
use App\Filament\Resources\EducationalStages\Tables\EducationalStagesTable;
use App\Models\EducationalStage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EducationalStageResource extends Resource
{
    protected static ?string $model = EducationalStage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return EducationalStageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EducationalStagesTable::configure($table);
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
            'index' => ListEducationalStages::route('/'),
            'create' => CreateEducationalStage::route('/create'),
            'edit' => EditEducationalStage::route('/{record}/edit'),
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
