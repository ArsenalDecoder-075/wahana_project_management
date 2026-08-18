<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\HuaweiObsService;
use Illuminate\Support\Facades\Storage;


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
    public function getUrlAttribute()
    {
        // First check if the file exists in local storage
        if (Storage::disk('public')->exists($this->file_path)) {
            return asset('storage/' . $this->file_path);
        }

        // If not local, assume it's in OBS and generate temporary URL
        try {
            $obs = app(HuaweiObsService::class);
            return $obs->getTempUrl($this->file_path, 3600);
        } catch (\Exception $e) {
            // Fallback to local asset as a last resort? Or return a placeholder.
            return asset('storage/' . $this->file_path);
        }
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

    public function getCategoryAttribute()
    {
        $mime = $this->mime_type;

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }

        return match ($mime) {
            'application/pdf' => 'pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv' => 'spreadsheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'presentation',
            default => 'other',
        };
    }

    public function getIconAttribute()
    {
        return match ($this->category) {
            'image'       => 'fa-file-image',
            'pdf'         => 'fa-file-pdf',
            'document'    => 'fa-file-word',
            'spreadsheet' => 'fa-file-excel',
            'presentation'=> 'fa-file-powerpoint',
            default       => 'fa-file',
        };
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
