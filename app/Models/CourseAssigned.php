<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseAssigned extends Model
{
    use HasFactory;

    protected $fillable = [
        'userId',
        'courseId',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'courseId');
    }
}
