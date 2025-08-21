<?php

namespace App\Filament\Resources\InstallationResource\Pages;

use Filament\Actions;
use App\Services\Touch365Api;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\InstallationResource;

class EditInstallation extends EditRecord
{
    protected static string $resource = InstallationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function testTouch365Credentials()
    {
        $data = $this->form->getState();

        try {
            $api = new Touch365Api($data['username'], $data['password'], $data['tenant']);
            $api->authenticate(); // your method in the service

            Notification::make()
                ->title('Success')
                ->body('Touch365 credentials are valid.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Authentication Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
