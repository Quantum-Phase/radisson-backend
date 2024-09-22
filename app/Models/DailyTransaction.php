<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'opening_balance',
        'total_credit',
        'total_debit',
        // 'closing_balance'
    ];
}
