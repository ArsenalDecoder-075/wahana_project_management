<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Filters\Video\ResizeFilter;
use App\Services\HuaweiObsService;

class MediaProcessorService
{
    protected $ffmpeg;
    protected $imageManager;
    protected $obsService;

    public function __construct(HuaweiObsService $obsService)
    {
        $this->obsService = $obsService;
        $this->imageManager = new ImageManager(driver: Driver::class);

        // Check if FFmpeg is available before trying to initialize
        $ffmpegPath = config('ffmpeg.ffmpeg_binaries', '/usr/bin/ffmpeg');
        if (!file_exists($ffmpegPath)) {
            // Try common paths
            $commonPaths = ['/usr/local/bin/ffmpeg', '/opt/bin/ffmpeg', 'ffmpeg'];
            foreach ($commonPaths as $path) {
                if ($path === 'ffmpeg') {
                    // Linux/macOS fallback
                    exec('which ffmpeg 2>/dev/null', $output, $returnVar);
                    if ($returnVar === 0 && !empty($output)) {
                        $ffmpegPath = trim($output[0]);
                    }
                    // Windows fallback (perbaiki kondisi)
                    if ($returnVar !== 0) {
                        exec('where ffmpeg 2>nul', $output, $returnVar);
                        if ($returnVar === 0 && !empty($output)) {
                            // Ubah backslash menjadi forward slash agar konsisten
                            $ffmpegPath = str_replace('\\', '/', trim($output[0]));
                        }
                    }
                } elseif (file_exists($path)) {
                    $ffmpegPath = $path;
                    break;
                }
            }
        }

        if (file_exists($ffmpegPath)) {
            try {
                $this->ffmpeg = FFMpeg::create([
                    'ffmpeg.binaries'  => $ffmpegPath,
                    'ffprobe.binaries' => config('ffmpeg.ffprobe_binaries', str_replace('ffmpeg', 'ffprobe', $ffmpegPath)),
                    'timeout'          => 300,
                    'ffmpeg.threads'   => 4,
                ]);
            } catch (\Exception $e) {
                Log::error('FFMpeg initialization failed: ' . $e->getMessage());
                $this->ffmpeg = null;
            }
        } else {
            Log::warning('FFmpeg not found on system. Video processing will be disabled.');
            $this->ffmpeg = null;
        }
    }

    /**
     * Helper: Check if OBS is enabled
     */
    private function isObsEnabled()
    {
        // Gunakan config() agar kompatibel dengan config:cache
        $enabled = config('obs.enabled', false);
        Log::debug('OBS enabled check', ['enabled' => $enabled]);
        return filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Core Storage Logic: Upload to OBS or Save Locally
     * Returns the 'path' to be saved in database
     *
     * PENTING: Semua path disimpan dalam format yang sama (tanpa prefix /storage/)
     * agar konsisten dan mudah dihandle oleh accessor ReportMedia
     */
    private function storeFile($sourcePath, $destinationPath, $filename, $contentType = null)
    {
        // Normalize path - hapus leading slash jika ada
        $normalizedPath = ltrim($destinationPath . '/' . $filename, '/');

        // 1. Try Upload to OBS if Enabled
        if ($this->isObsEnabled()) {
            try {
                $uploadResult = $this->obsService->uploadFile($normalizedPath, $sourcePath);

                if ($uploadResult['success']) {
                    Log::info('File uploaded to OBS successfully', [
                        'key' => $normalizedPath,
                        'storage' => 'obs'
                    ]);

                    // Cleanup source file (usually temp)
                    if (file_exists($sourcePath)) {
                        @unlink($sourcePath);
                    }

                    // Return path tanpa prefix - akan dihandle accessor untuk generate URL
                    return [
                        'path' => $normalizedPath,
                        'is_obs' => true
                    ];
                } else {
                    Log::warning('OBS Upload failed, falling back to local storage', [
                        'key' => $normalizedPath,
                        'error' => $uploadResult['message'] ?? 'Unknown error'
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('OBS Exception, falling back to local storage', [
                    'key' => $normalizedPath,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 2. Fallback / Standard Local Storage
        try {
            $fullPhysicalPath = storage_path('app/public/' . $destinationPath);
            $this->ensureDirectoryExists($fullPhysicalPath);

            $finalPath = $fullPhysicalPath . '/' . $filename;

            // Move or Copy the file
            if (!copy($sourcePath, $finalPath)) {
                throw new \Exception("Failed to copy file to local storage");
            }

            $this->setFilePermissions($finalPath);

            // Cleanup source
            if (file_exists($sourcePath)) {
                @unlink($sourcePath);
            }

            Log::info('File saved to local storage', [
                'key' => $normalizedPath,
                'storage' => 'local',
                'physical_path' => $finalPath
            ]);

            // Return path dengan prefix /storage/ untuk file lokal
            // Accessor akan mendeteksi prefix ini dan return direct URL
            return [
                'path' => '/storage/' . $normalizedPath,
                'is_obs' => false
            ];
        } catch (\Exception $e) {
            Log::error('Local Storage Failed: ' . $e->getMessage());
            throw new \Exception('Failed to store file: ' . $e->getMessage());
        }
    }

    /**
     * Delete Media (Smart Delete)
     */
    public function deleteMedia($path)
    {
        if (empty($path)) return;

        // 1. Check if it looks like a full URL (OBS) or just doesn't start with /storage
        // Logic: Local files usually start with /storage/ or are relative paths that exist on disk.
        // OBS files might match the pattern "path/to/file" without /storage.

        // Clean path first
        $cleanPath = $path;
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $parsed = parse_url($path);
            $cleanPath = ltrim($parsed['path'] ?? '', '/');
        }

        // Check Local first (safest)
        // Remove /storage prefix if exists
        $relativePath = str_replace('/storage/', '', $cleanPath); // e.g. "reporting/img.jpg"

        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
            Log::info('Local file deleted: ' . $relativePath);
            return;
        }

        // If not found locally, and OBS is enabled or we suspect it's OBS
        // We can try deleting from OBS.
        // Even if ENABLE_OBS is false now, maybe the file was uploaded when it was true.
        // So we should try delete if it's not local.
        try {
            $this->obsService->deleteFile($relativePath); // path serves as key
            Log::info('OBS delete requested for: ' . $relativePath);
        } catch (\Exception $e) {
            Log::warning('OBS delete failed: ' . $e->getMessage());
        }
    }


    /**
     * Ensure directory exists with proper permissions
     */
    private function ensureDirectoryExists($path)
    {
        if (!file_exists($path)) {
            try {
                $relativePath = str_replace(storage_path('app/public/'), '', $path);
                Storage::disk('public')->makeDirectory($relativePath);
            } catch (\Exception $e) {
                if (!mkdir($path, 0755, true)) {
                    // Try to ignore error or log it
                }
            }
        }

        if (!is_writable($path)) {
            @chmod($path, 0755);
        }
    }

    /**
     * Process uploaded image file
     */
    public function processImage(UploadedFile $file, string $destinationPath)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(120);

        $filename = uniqid('img_') . '.jpg';

        try {
            $fileSizeInMB = $file->getSize() / (1024 * 1024);
            $img = $this->imageManager->read($file->getRealPath());

            if ($fileSizeInMB > 5) {
                $maxWidth = 1280;
                $maxHeight = 1280;
            } else {
                $maxWidth = 1920;
                $maxHeight = 1920;
            }

            if ($img->width() > $maxWidth || $img->height() > $maxHeight) {
                $scaleX = $maxWidth / $img->width();
                $scaleY = $maxHeight / $img->height();
                $scale = min($scaleX, $scaleY);
                $img->resize((int)($img->width() * $scale), (int)($img->height() * $scale));
            }

            $mainQuality = $fileSizeInMB > 5 ? 65 : 75;
            $compressedImageData = $img->toJpeg($mainQuality);

            // Save to temporary file first
            $tempPath = sys_get_temp_dir() . '/' . $filename;
            file_put_contents($tempPath, $compressedImageData);

            // Use the Smart Store
            $result = $this->storeFile($tempPath, $destinationPath, $filename, 'image/jpeg');

            return ['path' => $result['path']];
        } catch (\Exception $e) {
            Log::error('Error processing image: ' . $e->getMessage());
            throw new \Exception('Failed to process image: ' . $e->getMessage());
        }
    }

    /**
     * Process uploaded video file
     */
    // public function processVideo(UploadedFile $file, string $destinationPath)
    // {
    //     ini_set('memory_limit', '1G');
    //     set_time_limit(300);

    //     if ($this->ffmpeg === null) {
    //         return $this->storeOriginalVideo($file, $destinationPath);
    //     }

    //     $fileSizeInMB = $file->getSize() / (1024 * 1024);
    //     $outputFilename = uniqid('vid_') . '.mp4';

    //     // Use Laravel's storage temp path to ensure ffmpeg can write to it nicely
    //     $tempPath = storage_path('app/temp');
    //     $this->ensureDirectoryExists($tempPath);

    //     $tempInput = $tempPath . '/' . uniqid() . '_in.mp4';
    //     $tempOutput = $tempPath . '/' . uniqid() . '_out.mp4';

    //     try {
    //         if (!copy($file->getRealPath(), $tempInput)) {
    //             // Fallback
    //             return $this->storeOriginalVideo($file, $destinationPath);
    //         }

    //         $video = $this->ffmpeg->open($tempInput);

    //         // Validate stream
    //         $videoStream = $video->getStreams()->videos()->first();
    //         if (!$videoStream) throw new \Exception("No video stream found");


    //         $originalWidth = $videoStream->getDimensions()->getWidth();
    //         $originalHeight = $videoStream->getDimensions()->getHeight();

    //         $format = new X264('aac', 'libx264');

    //         // Compression settings matching original logic
    //         if ($fileSizeInMB > 50) {
    //             $format->setKiloBitrate(500);
    //             $format->setAdditionalParameters(['-preset', 'fast', '-crf', '32', '-maxrate', '600k', '-bufsize', '1200k', '-movflags', '+faststart', '-pix_fmt', 'yuv420p', '-profile:v', 'baseline']);
    //             $maxWidth = 854;
    //             $maxHeight = 480;
    //         } else {
    //             $format->setKiloBitrate(800);
    //             $format->setAdditionalParameters(['-preset', 'medium', '-crf', '28', '-maxrate', '1000k', '-bufsize', '2000k', '-movflags', '+faststart', '-pix_fmt', 'yuv420p', '-profile:v', 'main']);
    //             $maxWidth = 1280;
    //             $maxHeight = 720;
    //         }

    //         if ($originalWidth > $maxWidth || $originalHeight > $maxHeight) {
    //             $scaleX = $maxWidth / $originalWidth;
    //             $scaleY = $maxHeight / $originalHeight;
    //             $scale = min($scaleX, $scaleY);
    //             $newWidth = (int)($originalWidth * $scale);
    //             $newHeight = (int)($originalHeight * $scale);
    //             $newWidth = $newWidth % 2 == 0 ? $newWidth : $newWidth - 1;
    //             $newHeight = $newHeight % 2 == 0 ? $newHeight : $newHeight - 1;
    //             $video->filters()->resize(new Dimension($newWidth, $newHeight), ResizeFilter::RESIZEMODE_FIT);
    //         }

    //         $video->save($format, $tempOutput);

    //         // Smart Store
    //         $result = $this->storeFile($tempOutput, $destinationPath, $outputFilename, 'video/mp4');

    //         return ['path' => $result['path']];
    //     } catch (\Exception $e) {
    //         Log::error('Error processing video: ' . $e->getMessage());
    //         return $this->storeOriginalVideo($file, $destinationPath);
    //     } finally {
    //         if (isset($tempInput) && file_exists($tempInput)) @unlink($tempInput);
    //         if (isset($tempOutput) && file_exists($tempOutput)) @unlink($tempOutput);
    //     }
    // }

    /**
     * Store original video without compression
     */
    // protected function storeOriginalVideo(UploadedFile $file, string $destinationPath)
    // {
    //     $filename = uniqid('vid_') . '.mp4';

    //     // Create a temp copy
    //     $tempPath = sys_get_temp_dir() . '/' . $filename;
    //     copy($file->getRealPath(), $tempPath);

    //     $result = $this->storeFile($tempPath, $destinationPath, $filename, 'video/mp4');
    //     return ['path' => $result['path']];
    // }

    /**
     * Process uploaded PDF file
     */
    // public function processPdf(UploadedFile $file, string $destinationPath)
    // {
    //     ini_set('memory_limit', '512M');
    //     set_time_limit(120);

    //     $filename = uniqid('pdf_') . '.pdf';

    //     try {
    //         $fileSizeInMB = $file->getSize() / (1024 * 1024);

    //         // Skip compression for small files
    //         if ($fileSizeInMB < 1) {
    //             return $this->storeOriginalPdf($file, $destinationPath, $filename);
    //         }

    //         // Compression Logic (Ghostscript)
    //         $tempPath = storage_path('app/temp');
    //         $this->ensureDirectoryExists($tempPath);

    //         $tempInput = $tempPath . '/' . uniqid() . '_in.pdf';
    //         $tempOutput = $tempPath . '/' . uniqid() . '_out.pdf';

    //         if (!copy($file->getRealPath(), $tempInput)) {
    //             return $this->storeOriginalPdf($file, $destinationPath, $filename);
    //         }

    //         // Finding Ghostscript
    //         $gsBinary = $this->findGhostscript();
    //         if (!$gsBinary) {
    //             return $this->storeOriginalPdf($file, $destinationPath, $filename);
    //         }

    //         // Simple basic compression setting
    //         $command = sprintf(
    //             '%s -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -dDetectDuplicateImages=true -dCompressFonts=true -dSubsetFonts=true -dOptimize=true -dPDFSETTINGS=/ebook -sOutputFile=%s %s 2>&1',
    //             escapeshellcmd($gsBinary),
    //             escapeshellarg($tempOutput),
    //             escapeshellarg($tempInput)
    //         );

    //         exec($command, $output, $returnVar);

    //         if ($returnVar === 0 && file_exists($tempOutput) && filesize($tempOutput) < filesize($tempInput)) {
    //             $result = $this->storeFile($tempOutput, $destinationPath, $filename, 'application/pdf');
    //         } else {
    //             return $this->storeOriginalPdf($file, $destinationPath, $filename);
    //         }

    //         // Allow cleanup
    //         @unlink($tempInput);
    //         if (file_exists($tempOutput)) @unlink($tempOutput);

    //         return ['path' => $result['path']];
    //     } catch (\Exception $e) {
    //         Log::error('Error processing PDF: ' . $e->getMessage());
    //         return $this->storeOriginalPdf($file, $destinationPath, $filename);
    //     }
    // }

    private function findGhostscript()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $candidates = ['gswin64c.exe', 'gswin32c.exe', 'gs'];
        } else {
            $candidates = ['/usr/bin/gs', '/usr/local/bin/gs', 'gs'];
        }

        foreach ($candidates as $binary) {
            // Basic check
            return $binary; // Assuming path or binary is available, proper check is complex across OS
        }
        return 'gs';
    }

    // private function storeOriginalPdf($file, $destinationPath, $filename)
    // {
    //     $tempPath = sys_get_temp_dir() . '/' . $filename;
    //     copy($file->getRealPath(), $tempPath);
    //     $result = $this->storeFile($tempPath, $destinationPath, $filename, 'application/pdf');
    //     return ['path' => $result['path']];
    // }

    public function getFFMpeg()
    {
        return $this->ffmpeg;
    }
    public function getImageManager()
    {
        return $this->imageManager;
    }

    private function setFilePermissions($filePath)
    {
        try {
            @chmod($filePath, 0664);
            $directory = dirname($filePath);
            @chmod($directory, 0775);
        } catch (\Exception $e) {
        }
    }

    /**
     * Process and store audio file with optional compression
    */
    // public function processAudio(UploadedFile $file, string $destinationPath)
    // {
    //     $extension = $file->getClientOriginalExtension() ?: 'mp3';
    //     $filename = uniqid('aud_') . '.' . $extension;

    //     // Kalo FFmpeg gk ada langsung simpen original
    //     if ($this->ffmpeg === null) {
    //         return $this->storeOriginalAudio($file, $destinationPath, $filename);
    //     }

    //     // Target bitrate, standar untuk audiobook karena cuman audio (96 kbps untuk kualitas baik & ukuran kecil)
    //     $targetBitrate = 96;

    //     $tempPath = storage_path('app/temp');
    //     $this->ensureDirectoryExists($tempPath);

    //     $tempInput = $tempPath . '/' . uniqid() . '_in.' . $extension;
    //     $tempOutput = $tempPath . '/' . uniqid() . '_out.mp3';

    //     try {
    //         // Salin file asli ke temp
    //         if (!copy($file->getRealPath(), $tempInput)) {
    //             return $this->storeOriginalAudio($file, $destinationPath, $filename);
    //         }

    //         // Baca bitrate asli menggunakan FFProbe
    //         $ffprobe = $this->ffmpeg->getFFProbe();

    //         // Prioritas: ambil dari stream audio
    //         $audioStream = $ffprobe->streams($tempInput)->audios()->first();
    //         $bitrate = $audioStream ? $audioStream->get('bit_rate') : null;

    //         // Jika tidak ada audio stream (misal video tanpa audio), coba dari video
    //         if (!$bitrate) {
    //             $videoStream = $ffprobe->streams($tempInput)->videos()->first();
    //             $bitrate = $videoStream ? $videoStream->get('bit_rate') : null;
    //         }

    //         $doCompress = false; // default

    //         if (!$bitrate) {
    //             // Bitrate tidak terbaca → kita kompres sebagai fallback
    //             Log::warning('Unable to detect bitrate, compressing anyway', ['file' => $filename]);
    //             $doCompress = true;
    //         } else {
    //             $bitrateKbps = (int) $bitrate / 1000;
    //             Log::info('Original bitrate', ['bitrate' => $bitrateKbps . ' kbps', 'file' => $filename]);

    //             // Kompres hanya jika bitrate asli > target bitrate (96kbps)
    //             $doCompress = $bitrateKbps > $targetBitrate;
    //         }

    //         if ($doCompress) {
    //             // Kompresi ke target bitrate
    //             $audio = $this->ffmpeg->open($tempInput);
    //             $format = new \FFMpeg\Format\Audio\Mp3();
    //             $format->setAudioKiloBitrate($targetBitrate);
    //             $audio->save($format, $tempOutput);

    //             $sourcePath = $tempOutput;
    //             Log::info('Audio compressed', ['bitrate' => $targetBitrate . ' kbps', 'file' => $filename]);
    //         } else {
    //             // Bitrate asli sudah <= target, skip kompresi, gunakan file asli
    //             Log::info('Bitrate already <= target, skipping compression', ['bitrate' => $bitrateKbps . ' kbps', 'target' => $targetBitrate]);
    //             $sourcePath = $tempInput;
    //         }

    //         // Simpan file ke storage (OBS atau lokal)
    //         $result = $this->storeFile(
    //             $sourcePath,
    //             $destinationPath,
    //             $filename,
    //         );

    //         return ['path' => $result['path']];

    //     } catch (\Exception $e) {
    //         Log::error('Audio compression failed: ' . $e->getMessage());
    //         return $this->storeOriginalAudio($file, $destinationPath, $filename);
    //     } finally {
    //         // Hapus file temp
    //         if (isset($tempInput) && file_exists($tempInput)) @unlink($tempInput);
    //         if (isset($tempOutput) && file_exists($tempOutput)) @unlink($tempOutput);
    //     }
    // }

    /**
     * Fallback: store original audio without compression
     */
    // protected function storeOriginalAudio(UploadedFile $file, string $destinationPath, ?string $filename = null)
    // {
    //     if ($filename === null) {
    //         $extension = $file->getClientOriginalExtension() ?: 'mp3';
    //         $filename = uniqid('aud_') . '.' . $extension;
    //     }

    //     $tempPath = sys_get_temp_dir() . '/' . $filename;
    //     copy($file->getRealPath(), $tempPath);

    //     $result = $this->storeFile($tempPath, $destinationPath, $filename, 'audio/' . $file->getClientOriginalExtension());
    //     return ['path' => $result['path']];
    // }
}
