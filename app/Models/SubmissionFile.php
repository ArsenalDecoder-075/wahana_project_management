<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\HuaweiObsService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


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
        $cleanPath = $this->file_path;

        if (filter_var($this->file_path, FILTER_VALIDATE_URL)) {
            return $this->file_path;
        }

        $relativePath = ltrim(str_replace('/storage/', '', $cleanPath), '/');

        if (Storage::disk('public')->exists($relativePath)) {
            return asset('storage/' . $relativePath);
        }

        try {
            $obs = app(HuaweiObsService::class);
            $obsUrl = $obs->getTempUrl($relativePath, 3600);
            if ($obsUrl) {
                return $obsUrl;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to generate OBS URL', ['path' => $relativePath, 'error' => $e->getMessage()]);
        }

        return asset('storage/' . $relativePath);
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
