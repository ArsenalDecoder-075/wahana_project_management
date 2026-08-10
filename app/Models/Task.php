<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'project_id',
        'assigned_to',
        'created_by',
        'title',
        'description',
        'priority',
        'status',
        'deadline',
        'start_date',
    ];

    public function project() {
        return $this->belongsTo(Project::class);
    }

    public function assignee() {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }

    // public function submissions() {
    //     return $this->hasMany(TaskSubmission::class);
    // }

    public function submissions()
    {
        return $this->hasMany(TaskSubmission::class, 'task_id');
    }

    public function employee()
    {
        // 'employee_id' adalah foreign key di tabel tasks
        return $this->belongsTo(User::class, 'employee_id', 'id');
    }
}
