<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskReview extends Model
{
    protected $fillable = ['submission_id', 'manager_id', 'notes', 'status'];

    public function submission() {
        return $this->belongsTo(TaskSubmission::class);
    }
}
