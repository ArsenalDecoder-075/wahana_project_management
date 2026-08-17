<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskSubmission extends Model
{
    // protected $fillable = ['task_id', 'employee_id', 'notes'];
    protected $fillable = ['task_id', 'employee_id', 'notes', 'is_deleted', 'deleted_at'];

    // Menentukan apakah ini kehapus
    public function getNotesAttribute($value)
    {
        if ($this->is_deleted) {
            return 'Pesan ini dihapus oleh karyawan.';
        }
        return $value;
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
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

    // public function review()
    // {
    //     return $this->hasOne(TaskReview::class, 'submission_id');
    // }

    public function review()
    {
        // Untuk kemudahan: hasOne review terbaru? Sebenarnya bisa pakai hasOne dengan order, tapi kita pakai accessor di atas.
        return $this->hasOne(TaskReview::class, 'submission_id')->latest();
    }

    public function getReviewerAttribute()
    {
        return $this->review ? $this->review->reviewer : null;
    }

    public function isReviewed()
    {
        return $this->review()->exists();
    }

    // Untuk kemudahan, ambil review terbaru (atau null)
    public function getLatestReviewAttribute()
    {
        return $this->reviews()->latest()->first();
    }

    // Cek status review
    public function getReviewStatusAttribute()
    {
        if (!$this->review) {
            return 'pending';
        }
        return $this->review->status;
    }

    // Dapatkan catatan review
    // public function getReviewNotesAttribute()
    // {
    //     return $this->review->notes ?? null;
    // }

    public function getReviewNotesAttribute()
    {
        return $this->review ? $this->review->feedback_notes : null; // ← feedback_notes
    }

    // Mendapatkan waktu review
    public function getReviewedAtAttribute()
    {
        return $this->review->reviewed_at ?? null;
    }
}
