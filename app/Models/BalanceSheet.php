<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BalanceSheet extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'balanceSheetId',
        'name',
        'remarks',
        'amount',
        'transactionBy',
        'assetsId',
        'liabilitiesId',
        'blockId',
    ];

    // Specify the primary key for the model
    protected $primaryKey = 'balanceSheetId'; // Custom primary key

    // Set to true if primary key is incrementing (default behavior)
    public $incrementing = true;

    // Set to false if primary key is not an integer
    protected $keyType = 'int'; // or 'string' if using a non-integer key

    public function transactionBy()
    {
        return $this->belongsTo(User::class, 'transactionBy');
    }
    public function block()
    {
        return $this->belongsTo(Block::class, 'blockId');
    }

    public function assets()
    {
        return $this->belongsTo(Ledger::class, 'assetsId');
    }

    public function liabilities()
    {
        return $this->belongsTo(Ledger::class, 'liabilitiesId');
    }
}
