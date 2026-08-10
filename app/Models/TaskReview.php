<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskReview extends Model
{
    protected $fillable = [
        'submission_id',
        'manager_id',
        'feedback_notes',
        'status',
        'reviewed_at'
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    // Relasi ke submission
    public function submission()
    {
        return $this->belongsTo(TaskSubmission::class, 'submission_id');
    }
    // Relasi ke manager/reviewer
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
