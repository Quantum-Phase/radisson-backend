<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Block extends Model
{
    use HasFactory;

    protected $fillable = [
        'blockId',
        'name',
    ];

    // Specify the primary key for the model
    protected $primaryKey = 'blockId'; // Custom primary key

    // Set to true if primary key is incrementing (default behavior)
    public $incrementing = true;

    // Set to false if primary key is not an integer
    protected $keyType = 'int'; // or 'string' if using a non-integer key

    // Assuming a Block can have multiple users
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'blockId');
    }
}
