<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'totalFee',
        'duration_unit',
        'duration'
    ];

    // Specify the primary key for the model
    protected $primaryKey = 'courseId'; // Custom primary key

    // Set to true if primary key is incrementing (default behavior)
    public $incrementing = true;

    // Set to false if primary key is not an integer
    protected $keyType = 'int'; // or 'string' if using a non-integer key

    public function StudentCourse(): HasMany
    {
        return $this->hasMany(StudentCourse::class, 'courseId');
    }

    // Define the relationship to the User model through StudentBatch
    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, StudentCourse::class, 'courseId', 'userId');
    }
}
