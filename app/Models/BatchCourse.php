<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchCourse extends Model
{
    use HasFactory;

    protected $fillable = [
        'courseId',
        'batchId'
    ];

    // Define the relationship to the User model
    public function course()
    {
        return $this->belongsTo(Course::class, 'courseId');
    }

    // Define the relationship to the Batch model
    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batchId');
    }
}
