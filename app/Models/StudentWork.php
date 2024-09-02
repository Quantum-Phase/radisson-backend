<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentWork extends Model
{
    use HasFactory;

    protected $fillable = [
        'userId',
        'workId'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function work()
    {
        return $this->belongsTo(Work::class, 'workId');
    }
}
