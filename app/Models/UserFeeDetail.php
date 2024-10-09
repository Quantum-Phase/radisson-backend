<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserFeeDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'userId',
        'batchId',
        'amountToBePaid',
        'totalAmountPaid',
        'remainingAmount'
    ];

    // Specify the primary key for the model
    protected $primaryKey = 'userFeeDetailId'; // Custom primary key

    // Set to true if primary key is incrementing (default behavior)
    public $incrementing = true;

    // Set to false if primary key is not an integer
    protected $keyType = 'int'; // or 'string' if using a non-integer key


    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batchId');
    }
}
