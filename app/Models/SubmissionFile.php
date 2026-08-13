<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionFile extends Model
{
    // protected $fillable = ['submission_id', 'file_url'];
    // Harus lebih detail, tambahin file_name, mime_type, size
    protected $fillable = [
        'submission_id',
        'file_path',
        'file_name',
        'mime_type',
        'size'
    ];
        public function submission() {
        return $this->belongsTo(TaskSubmission::class);
    }

    // Helper untuk mendapatkan URL lengkap
    public function getFileUrlAttribute($value)
    {
        return asset('storage/' . $value);
    }

    // Cek apakah file adalah gambar
    public function isImage()
    {
        return in_array($this->mime_type, [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'image/bmp'
        ]);
    }

    // Format ukuran file
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
