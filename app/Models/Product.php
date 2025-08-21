<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'installation_id',
        'title',
        'description',
        'web_description',
        'parent_sku',
        'sku',
        'price_excluding',
        'price_including',
        'sale_price_excluding',
        'sale_price_including',
        'sale_start_date',
        'sale_end_date',
        'category_id',
        'category_name',
        'sub_category_id',
        'sub_category_name',
        'image',
        'image_2',
        'image_3',
        'image_4',
        'stock',
        'length',
        'breadth',
        'height',
        'weight',
        'size_code',
        'size',
        'color_code',
        'color',
        'item_status',
        'edited',
        'created',
    ];

    protected $casts = [
        'sale_start_date' => 'datetime',
        'sale_end_date' => 'datetime',
        'edited' => 'datetime',
        'created' => 'datetime',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department', 'id');
    }

    public function subdepartment()
    {
        return $this->belongsTo(Department::class, 'subdepartment', 'parent_id');
    }

    public function installation()
    {
        return $this->belongsTo(Installation::class);
    }
}
