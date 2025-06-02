<?php

namespace App\Filament\Resources\LicenseResource\Pages;

use Filament\Actions;
use App\Models\License;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\LicenseResource;
use Filament\Forms\Components\Actions\Action;

class CreateLicense extends CreateRecord
{
    protected static string $resource = LicenseResource::class;
}
