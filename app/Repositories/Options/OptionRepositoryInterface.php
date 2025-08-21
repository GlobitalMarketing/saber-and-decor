<?php
namespace App\Repositories\Options;

interface OptionRepositoryInterface
{
    public function store(array $options, string $type, int $installationId);
    public function options();
    public function getSizesByInstallation(int $installationId);
    public function getColorsByInstallation(int $installationId);
}
