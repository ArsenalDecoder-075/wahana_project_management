<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = [
        'area',
        'city',
        'name',
        'initials',
        'category',
        'address'
    ];
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
