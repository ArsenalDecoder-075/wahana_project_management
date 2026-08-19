<?php

namespace App\Services;

use Obs\ObsClient;
use Obs\ObsException;
use Illuminate\Support\Facades\Log;

class HuaweiObsService
{
    protected $obsClient;
    protected $bucketName;
    protected $isConfigured = false;

    public function __construct()
    {
        // Gunakan config() agar kompatibel dengan config:cache
        $this->bucketName = config('obs.bucket');
        $key = config('obs.access_key_id');
        $secret = config('obs.secret_access_key');
        $endpoint = config('obs.endpoint');

        // Only initialize if all credentials are present
        if ($this->bucketName && $key && $secret && $endpoint) {
            try {
                $this->obsClient = new ObsClient([
                    'key' => $key,
                    'secret' => $secret,
                    'endpoint' => $endpoint,
                ]);
                $this->isConfigured = true;
                Log::debug('OBS Client initialized successfully', ['bucket' => $this->bucketName]);
            } catch (\Exception $e) {
                Log::warning('OBS Client initialization failed: ' . $e->getMessage());
                $this->isConfigured = false;
            }
        } else {
            Log::debug('OBS not configured - missing credentials', [
                'has_bucket' => !empty($this->bucketName),
                'has_key' => !empty($key),
                'has_secret' => !empty($secret),
                'has_endpoint' => !empty($endpoint)
            ]);
        }
    }

    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    /**
     * Upload File dengan mendeteksi Tipe Kontennya
     */
    public function uploadFile($filename, $sourceFile)
    {
        if (!$this->isConfigured) {
            return ['success' => false, 'message' => 'OBS not configured'];
        }

        try {
            $contentType = mime_content_type($sourceFile);

            $result = $this->obsClient->putObject([
                'Bucket' => $this->bucketName,
                'Key' => $filename,
                'SourceFile' => $sourceFile,
                'ContentType' => $contentType
            ]);

            return [
                'success' => true,
                'url' => $result['ObjectURL'] ?? "Berhasil upload: $filename"
            ];
        } catch (ObsException $e) {
            return ['success' => false, 'message' => $e->getExceptionMessage()];
        }
    }

    public function getFile($filename)
    {
        if (!$this->isConfigured) {
            return ['success' => false, 'message' => 'OBS not configured'];
        }

        try {
            $result = $this->obsClient->getObject([
                'Bucket' => $this->bucketName,
                'Key' => $filename,
            ]);

            return [
                'success' => true,
                'content' => $result['Body']
            ];
        } catch (ObsException $e) {
            return ['success' => false, 'message' => $e->getExceptionMessage()];
        }
    }

    public function deleteFile($filename)
    {
        if (!$this->isConfigured) {
            return ['success' => false, 'message' => 'OBS not configured'];
        }

        try {
            $this->obsClient->deleteObject([
                'Bucket' => $this->bucketName,
                'Key' => $filename
            ]);

            return ['success' => true];
        } catch (ObsException $e) {
            return ['success' => false, 'message' => $e->getExceptionMessage()];
        }
    }

    public function getTempUrl($key, $expires = 3600)
    {
        if (!$this->isConfigured) {
            return null;
        }

        try {
            $response = $this->obsClient->createSignedUrl([
                'Method' => 'GET',
                'Bucket' => $this->bucketName,
                'Key' => $key,
                'Expires' => $expires,
            ]);
            return $response['SignedUrl'];
        } catch (ObsException $e) {
            Log::error('Failed to generate signed URL: ' . $e->getExceptionMessage());
            return null;
        }
    }

    /**
     * Get Raw Object Stream (for Proxy Streaming)
     */
    public function getStream($filename)
    {
        if (!$this->isConfigured) {
            return ['success' => false, 'message' => 'OBS not configured'];
        }

        try {
            $result = $this->obsClient->getObject([
                'Bucket' => $this->bucketName,
                'Key' => $filename,
                'SaveAsStream' => true
            ]);

            // Access InterfaceResult->Content for stream
            if (isset($result['Body'])) {
                return [
                    'success' => true,
                    'body' => $result['Body'],
                    'ContentType' => $result['ContentType'] ?? 'application/octet-stream',
                    'ContentLength' => $result['ContentLength'] ?? 0
                ];
            }

            return ['success' => false, 'message' => 'No body in response'];
        } catch (ObsException $e) {
            return ['success' => false, 'message' => $e->getExceptionMessage()];
        }
    }

    /**
     * Check if object exists in OBS
     */
    public function exists($filename)
    {
        if (!$this->isConfigured) {
            return false;
        }

        try {
            $this->obsClient->getObjectMetadata([
                'Bucket' => $this->bucketName,
                'Key' => $filename
            ]);
            return true;
        } catch (ObsException $e) {
            return false;
        }
    }

    // Ini buat cek
    public function testConnection()
    {
        if (!$this->isConfigured) {
            return ['success' => false, 'message' => 'OBS not configured'];
        }

        try {
            // Try to list objects with max 1 result
            $this->obsClient->listObjects([
                'Bucket' => $this->bucketName,
                'MaxKeys' => 1,
            ]);
            return ['success' => true, 'message' => 'Connected to OBS successfully!'];
        } catch (ObsException $e) {
            return ['success' => false, 'message' => $e->getExceptionMessage()];
        }
    }
}
