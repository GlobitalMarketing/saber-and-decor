<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Installation extends Model
{
    protected $fillable = ['license_id', 'consumer_key', 'consumer_secret', 'site_url', 
        'username', 
        'password', 
        'colorIndex', 
        'colorName', 
        'sizeIndex', 
        'sizeName', 

        'child_category_indexing', 
        'reset_entries', 
        'assign_default_variation', 
        'include_images', 
        'assign_single_image', 
        'update_images', 
        'update_only', 
        'tenant'
    ];

    public function license()
    {
        return $this->belongsTo(License::class);
    }
    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function scopeFindIfActive($query, int $installationId){
        return $query->whereHas('license', function ($query) {
            $query->where('status', 'active');
        })->where('id', $installationId);
    }

}
