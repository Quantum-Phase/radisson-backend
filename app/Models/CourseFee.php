<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'courseId',
        'feeId'
    ];

    // Define the relationship to the User model
    public function course()
    {
        return $this->belongsTo(Course::class, 'courseId');
    }

    // Define the relationship to the Batch model
    public function Payment()
    {
        return $this->belongsTo(Payment::class, 'feeId');
    }
}
