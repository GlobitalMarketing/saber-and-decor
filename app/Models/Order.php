<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'installation_id',
        'ordernumber',
        'billing_email',
        'billing_phone',
        'billing_firstname',
        'billing_lastname',
        'billing_company',
        'billing_address1',
        'billing_address2',
        'billing_address3',
        'billing_address4',
        'billing_city',
        'billing_state',
        'billing_postcode',
        'billing_country',
        'shipping_phone',
        'shipping_firstname',
        'shipping_lastname',
        'shipping_company',
        'shipping_address1',
        'shipping_address2',
        'shipping_address3',
        'shipping_address4',
        'shipping_city',
        'shipping_state',
        'shipping_postcode',
        'shipping_country',
        'shipping_cost',
        'shipping_method',
        'payment_method',
        'payment_ref',
        'order_date',
        'order_time',
        'order_comment',
        'order_total',
        'vatindicator',
        'is_imported',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    
    public function installation()
    {
        return $this->belongsTo(Installation::class);
    }
}
