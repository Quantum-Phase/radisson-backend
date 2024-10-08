<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'jobId',
        'companyId',
        'departmentId',
        'studentId',
        'paid_amount',
        'start_date',
        'type'
    ];

    // Specify the primary key for the model
    protected $primaryKey = 'jobId'; // Custom primary key

    // Set to true if primary key is incrementing (default behavior)
    public $incrementing = true;

    // Set to false if primary key is not an integer
    protected $keyType = 'int'; // or 'string' if using a non-integer key

    public function company()
    {
        return $this->belongsTo(Company::class, 'companyId', 'companyId');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'departmentId', 'departmentId');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'studentId', 'userId');
    }
}
