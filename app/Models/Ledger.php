<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ledger extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ledgerId',
        'name',
        'ledgerTypeId',
        'isStudentFeeLedger',
        'isStudentRefundLedger',
    ];

    // Specify the primary key for the model
    protected $primaryKey = 'ledgerId'; // Custom primary key

    // Set to true if primary key is incrementing (default behavior)
    public $incrementing = true;

    // Set to false if primary key is not an integer
    protected $keyType = 'int'; // or 'string' if using a non-integer key

    public function payments()
    {
        return $this->hasMany(Payment::class, 'ledgerId', 'ledgerId');
    }

    public function ledgerType()
    {
        return $this->belongsTo(LedgerType::class, 'ledgerTypeId', 'ledgerTypeId');
    }

    protected $casts = [
        'isStudentFeeLedger' => 'boolean',
        'isStudentRefundLedger' => 'boolean',
    ];
}
