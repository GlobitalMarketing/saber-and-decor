<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Manufacturer extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'installation_id'
    ];

    public function installation()
    {
        return $this->belongsTo(Installation::class);
    }
}
