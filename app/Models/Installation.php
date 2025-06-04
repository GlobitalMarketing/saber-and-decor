<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Installation extends Model
{
    protected $fillable = ['license_id', 'consumer_key', 'consumer_secret', 'site_url'];

    public function license()
    {
        return $this->belongsTo(License::class);
    }
    public function departments()
    {
        return $this->hasMany(Department::class);
    }

}
