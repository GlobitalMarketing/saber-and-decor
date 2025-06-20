<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
 
    protected $guarded = [
        ''
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department', 'id');
    }

    public function subdepartment()
    {
        return $this->belongsTo(Department::class, 'subdepartment', 'parent_id');
    }
}
