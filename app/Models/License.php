<?php

namespace App\Models;

use App\Enums\LicenseStatus;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    protected $fillable = [
        'license_key',
        'site_url',
        'status',
        'expires_at',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'issued_at' => 'datetime',
            'status' => LicenseStatus::class,
        ];
    }

    public function installation()
    {
        return $this->hasOne(Installation::class);
    }

    public function activeInstallation()
    {
        return $this->hasOne(Installation::class)->where('status', 'active');
    }
}
