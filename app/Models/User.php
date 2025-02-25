<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phoneNo',
        'dob',
        'gender',
        'premanentAddress',
        'temporaryAddress',
        'emergencyContactNo',
        'startDate',
        'profileimg',
        'blockId'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    // Specify the primary key for the model
    protected $primaryKey = 'userId'; // Custom primary key

    // Set to true if primary key is incrementing (default behavior)
    public $incrementing = true;

    // Set to false if primary key is not an integer
    protected $keyType = 'int'; // or 'string' if using a non-integer key

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function studentBatch(): HasMany
    {
        return $this->hasMany(StudentBatch::class, 'userId');
    }

    // public function studentBatches(): HasMany
    // {
    //     return $this->hasMany(StudentBatch::class, 'userId');
    // }

    public function batches(): HasMany
    {
        return $this->hasMany(StudentBatch::class, 'userId');
    }
    public function course()
    {
        return $this->belongsTo(Course::class, 'courseId');
    }

    // A user has one user fee
    public function userFee()
    {
        return $this->hasOne(UserPayment::class, 'userId');
    }

    public function block()
    {
        return $this->belongsTo(Block::class, 'blockId');
    }
    public function hasRole($role)
    {
        return $this->role === $role;
    }
    public function mentorBatches()
    {
        return $this->hasMany(Batch::class, 'mentorId', 'userId');
    }
    public function userFeeDetail(): HasMany
    {
        return $this->hasMany(UserFeeDetail::class, 'userId');
    }
    public function job()
    {
        return $this->hasMany(Job::class, 'studentId', 'userId');
    }
}
