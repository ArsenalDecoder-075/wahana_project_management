<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionFile extends Model
{
    protected $fillable = ['submission_id', 'file_url'];

    public function submission() {
        return $this->belongsTo(TaskSubmission::class);
    }
}
