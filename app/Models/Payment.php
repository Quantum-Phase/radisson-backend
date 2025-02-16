<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'paymentId',
        'type',
        'remarks',
        'amount',
        'batchId',
        'paymentModeId',
        'ledgerId',
        'subLedgerId',
        'blockId',
        'recievedBy',
        'transactionBy',
        'dueAmount',
        'studentId',
    ];

    // Specify the primary key for the model
    protected $primaryKey = 'paymentId'; // Custom primary key

    // Set to true if primary key is incrementing (default behavior)
    public $incrementing = true;

    // Set to false if primary key is not an integer
    protected $keyType = 'int'; // or 'string' if using a non-integer key

    public function payedBy()
    {
        return $this->belongsTo(User::class, 'payed_by');
    }

    public function transactionBy()
    {
        return $this->belongsTo(User::class, 'transaction_by');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'studentId');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batchId');
    }

    public function paymentMode()
    {
        return $this->belongsTo(PaymentMode::class, 'paymentModeId');
    }

    public function block()
    {
        return $this->belongsTo(Block::class, 'blockId');
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class, 'ledgerId');
    }

    public function subLedger()
    {
        return $this->belongsTo(SubLedger::class, 'subLedgerId');
    }
}
