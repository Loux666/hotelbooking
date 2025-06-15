<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'max_use',
        'user_limit',
        'used_count',
        'min_order_price',
        'start_date',
        'end_date',
        'is_active'
    ];
}
