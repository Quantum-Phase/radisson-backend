<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentBatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'userId',
        'batchId',
        'discountType',
        'discountAmount',
        'discountPercent'
    ];

    // Define the relationship to the User model
    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    // Define the relationship to the Batch model
    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batchId');
    }
}
