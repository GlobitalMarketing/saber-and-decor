<?php

namespace App\Filament\Resources;

use App\Models\License;
use App\Enums\LicenseStatus;
// use Illuminate\Database\Eloquent\SoftDeletingScope;
// use Illuminate\Database\Eloquent\Builder;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\{Set,Form};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Resources\Resource;
use Filament\Forms\Components\Actions\Action;
use Filament\Tables\Actions\{EditAction, BulkActionGroup, DeleteBulkAction};
use Filament\Forms\Components\{Hidden, TextInput, Select, FileUpload, DateTimePicker, Tabs, TagsInput, Grid, Section, DatePicker};

// use App\Filament\Resources\LicenseResource\RelationManagers;
use App\Filament\Resources\LicenseResource\Pages\{ListLicenses, CreateLicense, EditLicense};


class LicenseResource extends Resource
{
    protected static ?string $model = License::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('license_key')
                ->required()
                ->readOnly()
                ->suffixAction(
                    Action::make('generateLicenseKey')
                        ->icon('heroicon-m-arrow-path')
                        ->requiresConfirmation()
                        ->action(function (Set $set, $state) {
                            do {
                                $key = strtoupper(bin2hex(random_bytes(16))); // Generates a 16-character key
                            } while (License::where('license_key', $key)->exists());

                            $set('license_key', $key);
                        })
                )
                ->dehydrated(),
                TextInput::make('site_url')->required()->url()->maxLength(255),
                Select::make('status')->options(LicenseStatus::class)->required(),
                DatePicker::make('issued_at'),
                DatePicker::make('expires_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('license_key')->searchable(),
                TextColumn::make('status'),
                TextColumn::make('issued_at')->dateTime()->sortable(),
                TextColumn::make('expires_at')->dateTime()->sortable(),
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
            'index' => ListLicenses::route('/'),
            'create'=> CreateLicense::route('/create'),
            'edit'  => EditLicense::route('/{record}/edit'),
        ];
    }

}
