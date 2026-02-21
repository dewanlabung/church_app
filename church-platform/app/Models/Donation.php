<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    protected $fillable = [
        'donor_name', 'donor_email', 'amount', 'currency', 'purpose',
        'transaction_id', 'payment_method', 'status', 'notes', 'user_id'
    ];

    protected $casts = ['amount' => 'decimal:2'];

    public function user() { return $this->belongsTo(User::class); }
}
