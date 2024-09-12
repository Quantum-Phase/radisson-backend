<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'isActive',
        'isDeleted',
    ];

    // Specify the primary key for the model
    protected $primaryKey = 'batchId'; // Custom primary key

    // Set to true if primary key is incrementing (default behavior)
    public $incrementing = true;

    // Set to false if primary key is not an integer
    protected $keyType = 'int'; // or 'string' if using a non-integer key


    public function MentorBatches(): HasMany
    {
        return $this->hasMany(MentorBatch::class, 'batchId');
    }

    // Define the relationship to the User model through StudentBatch
    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, MentorBatch::class, 'batchId', 'userId');
    }

    public function course(): HasOne
    {
        return $this->hasOne(Course::class, 'courseId');
    }

    public function batchCourses()
    {
        return $this->hasMany(BatchCourse::class, 'batchId', 'batchId');
    }
}
