<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Option extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'installation_id'
    ];

    public function installation()
    {
        return $this->belongsTo(Installation::class);
    }
}
