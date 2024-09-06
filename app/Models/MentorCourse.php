<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MentorCourse extends Model
{
    use HasFactory;

    protected $fillable = [
        'userId',
        'courseId'
    ];
    // Define the relationship to the User model
    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    // Define the relationship to the Batch model
    public function course()
    {
        return $this->belongsTo(Course::class, 'courseId');
    }
}
