<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MentorBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'userId',
        'batchId'
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
