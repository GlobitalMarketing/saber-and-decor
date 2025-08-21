<?php

namespace App\Filament\Resources;

use Filament\Forms;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Installation;
use Filament\Resources\Resource;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Actions;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Str;
// use App\Filament\Resources\InstallationResource\RelationManagers;
use Filament\Forms\Components\Actions\Action;
use Filament\Tables\Actions\{EditAction, BulkActionGroup, DeleteBulkAction};
use App\Filament\Resources\InstallationResource\Pages\{ListInstallations, CreateInstallation, EditInstallation};
use Filament\Forms\Components\{Hidden, TextInput, Select, FileUpload, DateTimePicker, Tabs, TagsInput, Grid, Section, DatePicker};
use Carbon\Carbon;

class InstallationResource extends Resource
{
    protected static ?string $model = Installation::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';
    public static function getModelLabel(): string
    {
        return 'Subscription';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Subscriptions';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Wordpress Credentials')
                    ->schema([
                        Select::make('license_id')->relationship('license', 'license_key')->required(),
                        TextInput::make('site_url')->required()->url()->maxLength(255),
                        TextInput::make('consumer_key')
                            ->default(fn() => 'ck_' . Str::random(32))
                            ->readonly(),

                        TextInput::make('consumer_secret')
                            ->default(fn() => 'sk_' . Str::random(64))
                            ->readonly(),
                        DateTimePicker::make('installed_at')
                            ->default(Carbon::now()) // Carbon::now()
                            ->readonly()
                            ->required()
                    ])
                    ->columns(2),
                Section::make('Touch365 Credentials')
                    ->schema([
                        TextInput::make('username')
                            ->label('Touch365 Username')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label('Touch365 Password')
                            ->password()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('tenant')
                            ->label('Touch365 Tenant')
                            ->required()
                            ->maxLength(255),
                        Actions::make([
                            Action::make('testCredentials')
                                ->label('Test Touch365 Credentials')
                                ->action('testTouch365Credentials') // Livewire method
                                ->color('primary')
                                ->icon('heroicon-o-check-circle'),
                        ]),
                    ])
                    ->columns(3),
                Section::make('Attribute Settings')
                    ->schema([
                        TextInput::make('colorIndex')
                            ->label('Color Index ID')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('colorName')
                            ->label('Color Name')
                            ->helperText("Slug should be colours")
                            ->required()
                            ->maxLength(255),

                        TextInput::make('sizeIndex')
                            ->label('Size Index ID')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('sizeName')
                            ->label('Size Name')
                            ->helperText("Slug should be size")
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(4),
                Section::make('Sync Settings')
                    ->schema([
                        Toggle::make('child_category_indexing')->label('Child Category Indexing'),
                        Toggle::make('reset_entries')->label('Reset Entries'),
                        Toggle::make('assign_default_variation')->label('Assign Default Variation'),
                        Toggle::make('include_images')->label('Include Images'),
                        Toggle::make('assign_single_image')->label('Assign Single Image'),
                        Toggle::make('update_images')->label('Update Images'),
                        Toggle::make('update_only')->label('Update Only'),
                    ])
                    ->columns(2),
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
            'create' => CreateInstallation::route('/create'),
            'edit' => EditInstallation::route('/{record}/edit'),
        ];
    }
}
