<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'parent_id',
        'installation_id'
    ];

    /**
     * Get the parent department that owns the subdepartment.
     */
    // Each department *may* belong to a parent department
    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    // Each department *may* have many child departments (subdepartments)
    public function subdepartments()
    {
        return $this->hasMany(Department::class, 'parent_id');
    }
    public function installation()
    {
        return $this->belongsTo(Installation::class);
    }
}
