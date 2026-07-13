<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['manager_id', 'title', 'description', 'start_date', 'end_date', 'progress', 'status'];

    public function manager() {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function tasks() {
        return $this->hasMany(Task::class);
    }
}
