<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'company'; // specify the table name

    protected $fillable = [
        'companyId',
        'name',
        'address'
    ];

    // Specify the primary key for the model
    protected $primaryKey = 'companyId'; // Custom primary key

    // Set to true if primary key is incrementing (default behavior)
    public $incrementing = true;

    // Set to false if primary key is not an integer
    protected $keyType = 'int'; // or 'string' if using a non-integer key

    public function departments()
    {
        return $this->hasMany(Department::class, 'companyId', 'companyId')->where('companyId', '=', $this->companyId);
    }

    public function jobs()
    {
        return $this->hasMany(Job::class, 'companyId', 'companyId');
    }
}
