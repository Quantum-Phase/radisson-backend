<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountantBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'accountBlockId',
        'userId',
        'blockId'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    // Define the relationship to the block model
    public function block()
    {
        return $this->belongsTo(Block::class, 'blockId');
    }
}
