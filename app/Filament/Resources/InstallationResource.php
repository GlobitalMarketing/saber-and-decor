<?php

namespace App\Filament\Resources;

use App\Models\Installation;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\{EditAction, BulkActionGroup, DeleteBulkAction};
use Filament\Forms\Components\{Hidden, TextInput, Select, FileUpload, DateTimePicker, Tabs, TagsInput, Grid, Section, DatePicker};

// use App\Filament\Resources\InstallationResource\RelationManagers;
use App\Filament\Resources\InstallationResource\Pages\{ListInstallations, CreateInstallation, EditInstallation};


class InstallationResource extends Resource
{
    protected static ?string $model = Installation::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('license_id')->relationship('license', 'license_key')->required(),
                TextInput::make('site_url')->required()->url()->maxLength(255),
                TextInput::make('consumer_key')->required()->maxLength(255),
                TextInput::make('consumer_secret')->required()->maxLength(255),
                DateTimePicker::make('installed_at')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('license.license_key')->sortable(),
                TextColumn::make('site_url')->searchable(),
                TextColumn::make('consumer_key')->searchable(),
                TextColumn::make('consumer_secret')->searchable(),
                TextColumn::make('installed_at')->dateTime()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => ListInstallations::route('/'),
            'create'=> CreateInstallation::route('/create'),
            'edit'  => EditInstallation::route('/{record}/edit'),
        ];
    }
}
