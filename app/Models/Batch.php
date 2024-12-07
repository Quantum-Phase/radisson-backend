<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Batch extends Model
{
    use HasFactory, SoftDeletes;

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

    public function studentBatches(): HasMany
    {
        return $this->hasMany(StudentBatch::class, 'batchId');
    }

    // Define the relationship to Users through StudentBatch
    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, StudentBatch::class, 'batchId', 'userId');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'courseId');
    }

    public function mentor()
    {
        return $this->belongsTo(User::class, 'mentorId', 'userId');
    }

    public function userFeeDetail(): HasMany
    {
        return $this->hasMany(UserFeeDetail::class, 'batchId');
    }
}
