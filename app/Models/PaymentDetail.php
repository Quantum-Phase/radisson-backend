<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'remaining_balance',
        'paid_balance',
        'total_balance_to_be_paid'
    ];

    // Specify the primary key for the model
    protected $primaryKey = 'paymentDetailsId'; // Custom primary key

    // Set to true if primary key is incrementing (default behavior)
    public $incrementing = true;

    // Set to false if primary key is not an integer
    protected $keyType = 'int'; // or 'string' if using a non-integer key

    public function users(): HasOne
    {
        return $this->hasOne(User::class, 'userId');
    }
}
