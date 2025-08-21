<?php
namespace App\Repositories\Manufacturers;

interface ManufacturerRepositoryInterface
{
    public function store(array $manufacturers, int $installationId);
    public function manufacturers();
    public function getManufacturersByInstallation(int $installationId);
}
