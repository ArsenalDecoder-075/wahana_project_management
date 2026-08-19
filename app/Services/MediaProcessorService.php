<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Services\HuaweiObsService;

class MediaProcessorService
{
    protected $imageManager;
    protected $obsService;

    public function __construct(HuaweiObsService $obsService)
    {
        $this->obsService = $obsService;
        $this->imageManager = new ImageManager(driver: Driver::class);
    }

    private function isObsEnabled(): bool
    {
        return filter_var(config('obs.enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    // private function storeFile(string $sourcePath, string $destinationPath, string $filename, ?string $contentType = null): array
    // {
    //     $normalizedPath = ltrim($destinationPath . '/' . $filename, '/');

    //     if ($this->isObsEnabled()) {
    //         try {
    //             $uploadResult = $this->obsService->uploadFile($normalizedPath, $sourcePath);

    //             if ($uploadResult['success']) {
    //                 Log::info('File uploaded to OBS successfully', [
    //                     'key' => $normalizedPath,
    //                     'storage' => 'obs'
    //                 ]);

    //                 if (file_exists($sourcePath)) {
    //                     @unlink($sourcePath);
    //                 }

    //                 return ['path' => $normalizedPath, 'is_obs' => true];
    //             }

    //             Log::warning('OBS Upload failed, falling back to local storage', [
    //                 'key' => $normalizedPath,
    //                 'error' => $uploadResult['message'] ?? 'Unknown error'
    //             ]);
    //         } catch (\Exception $e) {
    //             Log::error('OBS Exception, falling back to local storage', [
    //                 'key' => $normalizedPath,
    //                 'error' => $e->getMessage()
    //             ]);
    //         }
    //     }

    //     try {
    //         $fullPhysicalPath = storage_path('app/public/' . $destinationPath);
    //         $this->ensureDirectoryExists($fullPhysicalPath);

    //         $finalPath = $fullPhysicalPath . '/' . $filename;

    //         if (!copy($sourcePath, $finalPath)) {
    //             throw new \Exception("Failed to copy file to local storage");
    //         }

    //         $this->setFilePermissions($finalPath);

    //         if (file_exists($sourcePath)) {
    //             @unlink($sourcePath);
    //         }

    //         if (!file_exists($finalPath)) {
    //             throw new \Exception("File was copied but cannot be found at destination");
    //         }

    //         Log::info('File saved to local storage', [
    //             'key' => $normalizedPath,
    //             'storage' => 'local',
    //             'physical_path' => $finalPath
    //         ]);

    //         return ['path' => '/storage/' . $normalizedPath, 'is_obs' => false];
    //     } catch (\Exception $e) {
    //         Log::error('Local Storage Failed: ' . $e->getMessage());
    //         throw new \Exception('Failed to store file: ' . $e->getMessage());
    //     }
    // }

    private function storeFile(string $sourcePath, string $destinationPath, string $filename): array
    {
        $normalizedPath = ltrim($destinationPath . '/' . $filename, '/');

        if ($this->isObsEnabled()) {
            try {
                $result = $this->obsService->uploadFile($normalizedPath, $sourcePath);
                if ($result['success']) {
                    Log::info('File uploaded to OBS successfully', [
                        'key' => $normalizedPath,
                        'storage' => 'obs'
                    ]);

                    if (file_exists($sourcePath)) {
                        @unlink($sourcePath);
                    }

                    return ['path' => $normalizedPath, 'is_obs' => true];
                }

                Log::warning('OBS Upload failed, falling back to local storage', [
                    'key' => $normalizedPath,
                    'error' => $result['message'] ?? 'Unknown error'
                ]);
            } catch (\Exception $e) {
                Log::error('OBS Exception, falling back to local storage', [
                    'key' => $normalizedPath,
                    'error' => $e->getMessage()
                ]);
            }
        }

        try {
            $fullPhysicalPath = storage_path('app/public/' . $destinationPath);
            $this->ensureDirectoryExists($fullPhysicalPath);

            $finalPath = $fullPhysicalPath . '/' . $filename;

            if (!copy($sourcePath, $finalPath)) {
                throw new \Exception("Failed to copy file to local storage");
            }

            $this->setFilePermissions($finalPath);

            if (file_exists($sourcePath)) {
                @unlink($sourcePath);
            }

            if (!file_exists($finalPath)) {
                throw new \Exception("File was copied but cannot be found at destination");
            }

            Log::info('File saved to local storage', [
                'key' => $normalizedPath,
                'storage' => 'local',
                'physical_path' => $finalPath
            ]);

            return ['path' => '/storage/' . $normalizedPath, 'is_obs' => false];
        } catch (\Exception $e) {
            Log::error('Local Storage Failed: ' . $e->getMessage());
            throw new \Exception('Failed to store file: ' . $e->getMessage());
        }
    }

    public function deleteMedia(string $path): void
    {
        if (empty($path)) {
            return;
        }

        $cleanPath = $path;
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $parsed = parse_url($path);
            $cleanPath = ltrim($parsed['path'] ?? '', '/');
        }

        $relativePath = str_replace('/storage/', '', $cleanPath);

        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
            Log::info('Local file deleted: ' . $relativePath);
            return;
        }

        try {
            $this->obsService->deleteFile($relativePath);
            Log::info('OBS delete requested for: ' . $relativePath);
        } catch (\Exception $e) {
            Log::warning('OBS delete failed: ' . $e->getMessage());
        }
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (!file_exists($path)) {
            try {
                $relativePath = str_replace(storage_path('app/public/'), '', $path);
                Storage::disk('public')->makeDirectory($relativePath);
            } catch (\Exception $e) {
                @mkdir($path, 0755, true);
            }
        }

        if (!is_writable($path)) {
            @chmod($path, 0755);
        }
    }

    private function setFilePermissions(string $filePath): void
    {
        try {
            @chmod($filePath, 0664);
            @chmod(dirname($filePath), 0775);
        } catch (\Exception $e) {
        }
    }

    // public function processImage(UploadedFile $file, string $destinationPath): array
    // {
    //     $originalExtension = strtolower($file->getClientOriginalExtension());
    //     $filename = uniqid('img_') . '.' . $originalExtension;

    //     try {
    //         $fileSizeInMB = $file->getSize() / (1024 * 1024);
    //         $img = $this->imageManager->read($file->getRealPath());

    //         $maxWidth = $fileSizeInMB > 5 ? 1280 : 1920;
    //         $maxHeight = $fileSizeInMB > 5 ? 1280 : 1920;

    //         if ($img->width() > $maxWidth || $img->height() > $maxHeight) {
    //             $scale = min($maxWidth / $img->width(), $maxHeight / $img->height());
    //             $img->resize((int)($img->width() * $scale), (int)($img->height() * $scale));
    //         }

    //         $tempPath = sys_get_temp_dir() . '/' . $filename;

    //         if ($originalExtension === 'png') {
    //             file_put_contents($tempPath, $img->toPng());
    //         } elseif ($originalExtension === 'webp') {
    //             file_put_contents($tempPath, $img->toWebp(80));
    //         } elseif (in_array($originalExtension, ['jpg', 'jpeg'])) {
    //             $quality = $fileSizeInMB > 5 ? 65 : 75;
    //             file_put_contents($tempPath, $img->toJpeg($quality));
    //         } else {
    //             file_put_contents($tempPath, $img->toPng());
    //         }

    //         return $this->storeFile($tempPath, $destinationPath, $filename, $file->getMimeType());
    //     } catch (\Exception $e) {
    //         Log::error('Error processing image: ' . $e->getMessage());
    //         throw new \Exception('Failed to process image: ' . $e->getMessage());
    //     }
    // }

    public function processImage(UploadedFile $file, string $destinationPath): array
    {
        $originalExtension = strtolower($file->getClientOriginalExtension());
        $filename = uniqid('img_') . '.' . $originalExtension;
        $tempPath = sys_get_temp_dir() . '/' . $filename;

        try {
            $fileSizeInMB = $file->getSize() / (1024 * 1024);
            $img = $this->imageManager->read($file->getRealPath());

            $maxWidth = $fileSizeInMB > 5 ? 1280 : 1920;
            $maxHeight = $fileSizeInMB > 5 ? 1280 : 1920;

            if ($img->width() > $maxWidth || $img->height() > $maxHeight) {
                $scale = min($maxWidth / $img->width(), $maxHeight / $img->height());
                $img->resize((int)($img->width() * $scale), (int)($img->height() * $scale));
            }

            if ($originalExtension === 'png') {
                file_put_contents($tempPath, $img->toPng());
            } elseif ($originalExtension === 'webp') {
                file_put_contents($tempPath, $img->toWebp(80));
            } elseif (in_array($originalExtension, ['jpg', 'jpeg'])) {
                $quality = $fileSizeInMB > 5 ? 65 : 75;
                file_put_contents($tempPath, $img->toJpeg($quality));
            } else {
                file_put_contents($tempPath, $img->toPng());
            }

            return $this->storeFile($tempPath, $destinationPath, $filename);
        } catch (\Exception $e) {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
            Log::error('Error processing image: ' . $e->getMessage());
            throw new \Exception('Failed to process image: ' . $e->getMessage());
        }
    }

    public function storeDocument(UploadedFile $file, string $destinationPath): array
    {
        $filename = uniqid('doc_') . '.' . $file->getClientOriginalExtension();
        $mime = $file->getMimeType();
        $tempPath = sys_get_temp_dir() . '/' . $filename;

        if (!copy($file->getRealPath(), $tempPath)) {
            throw new \Exception('Failed to copy document to temporary path');
        }

        try {
            return $this->storeFile($tempPath, $destinationPath, $filename);
        } catch (\Exception $e) {
            @unlink($tempPath);
            throw $e;
        }
    }
}
