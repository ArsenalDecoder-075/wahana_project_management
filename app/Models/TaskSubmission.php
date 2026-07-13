<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskSubmission extends Model
{
    protected $fillable = ['task_id', 'employee_id', 'notes'];

    public function task() {
        return $this->belongsTo(Task::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function files() {
        return $this->hasMany(SubmissionFile::class, 'submission_id');
    }

    public function reviews() {
        return $this->hasMany(TaskReview::class, 'submission_id');
    }
}
