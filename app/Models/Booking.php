<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id',

        'guest_name',
        'guest_email',
        'guest_phone',
        'checkin_date',
        'checkout_date',
        'number_of_guests',
        'total_price',
        'status',
        'payment_status',
    ];


    public function booking_details()
    {
        return $this->hasMany(BookingDetail::class, 'booking_id');
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
