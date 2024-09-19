<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'userId',
        'feeId',
        'batchId'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function fee()
    {
        return $this->belongsTo(Payment::class, 'paymentId');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batchId');
    }
}
