<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'parent_id',
    ];

    /**
     * Get the parent department that owns the subdepartment.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
