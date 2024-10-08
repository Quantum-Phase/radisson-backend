<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'departmentId',
        'name',
        'companyId'
    ];

    // Specify the primary key for the model
    protected $primaryKey = 'departmentId'; // Custom primary key

    // Set to true if primary key is incrementing (default behavior)
    public $incrementing = true;

    // Set to false if primary key is not an integer
    protected $keyType = 'int'; // or 'string' if using a non-integer key

    public function company()
    {
        return $this->belongsTo(Company::class, 'companyId', 'companyId');
    }

    public function jobs()
    {
        return $this->hasMany(Job::class, 'departmentId', 'departmentId');
    }
}
