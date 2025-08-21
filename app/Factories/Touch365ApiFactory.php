<?php

namespace App\Factories;

use App\Models\Installation;
use App\Services\Touch365Api;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Touch365ApiFactory
{
    /**
     * Create Touch365Api with direct credentials.
     */
    public function make(string $username, string $password, string $tenant): Touch365Api
    {
        return new Touch365Api($username, $password, $tenant);
    }

    /**
     * Create Touch365Api instance based on installation ID.
     */
    public function fromInstallation(int $installationId): Touch365Api
    {
        $installation = Installation::findOrFail($installationId);

        if (
            empty($installation->username) ||
            empty($installation->password) ||
            empty($installation->tenant)
        ) {
            throw new \InvalidArgumentException("Missing Touch365 credentials on installation ID {$installationId}");
        }

        return $this->make(
            $installation->username,
            $installation->password,
            $installation->tenant
        );
    }
}
