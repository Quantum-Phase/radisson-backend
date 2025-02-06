<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LedgerType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ledgerTypeId',
        'name',
        'type'
    ];

    // Specify the primary key for the model
    protected $primaryKey = 'ledgerTypeId'; // Custom primary key

    // Set to true if primary key is incrementing (default behavior)
    public $incrementing = true;

    // Set to false if primary key is not an integer
    protected $keyType = 'int'; // or 'string' if using a non-integer key
}
