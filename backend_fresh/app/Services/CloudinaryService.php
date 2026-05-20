<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Wrapper sederhana untuk Cloudinary API (signed upload + destroy).
 * Tidak pakai SDK official supaya tidak ada konflik composer di Railway.
 *
 * ENV yang dibutuhkan:
 *  - CLOUDINARY_CLOUD_NAME
 *  - CLOUDINARY_API_KEY
 *  - CLOUDINARY_API_SECRET
 */
class CloudinaryService
{
    private string $cloudName;
    private string $apiKey;
    private string $apiSecret;
    private Client $http;

    public function __construct()
    {
        $this->cloudName = (string) config('services.cloudinary.cloud_name', env('CLOUDINARY_CLOUD_NAME', ''));
        $this->apiKey = (string) config('services.cloudinary.api_key', env('CLOUDINARY_API_KEY', ''));
        $this->apiSecret = (string) config('services.cloudinary.api_secret', env('CLOUDINARY_API_SECRET', ''));
        // Timeout pendek supaya kalau Cloudinary slow/down → fail-fast,
        // fallback ke local storage yang dipanggil di controller.
        // SSL verify off karena shared hosting (LiteSpeed/IDcloudHost) sering
        // punya CA bundle outdated → cert verify fail meskipun cert valid.
        $this->http = new Client([
            'timeout' => 15,
            'connect_timeout' => 5,
            'verify' => false,
        ]);
    }

    public function isConfigured(): bool
    {
        return $this->cloudName !== '' && $this->apiKey !== '' && $this->apiSecret !== '';
    }

    /**
     * Upload file ke Cloudinary. Return URL secure (https) atau null jika gagal.
     *
     * @param UploadedFile $file
     * @param string $folder ex: "ipl/avatars", "ipl/pengaduan", "ipl/news", "ipl/logos"
     */
    public function upload(UploadedFile $file, string $folder): ?string
    {
        if (!$this->isConfigured()) {
            Log::warning('Cloudinary not configured, skipping upload');
            return null;
        }

        $timestamp = time();
        // Params yang ikut signature (alphabetical key order, exclude file/api_key/signature)
        $paramsToSign = [
            'folder' => $folder,
            'timestamp' => $timestamp,
        ];
        $signature = $this->signRequest($paramsToSign);

        try {
            $response = $this->http->post(
                "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/upload",
                [
                    'multipart' => [
                        [
                            'name' => 'file',
                            'contents' => fopen($file->getRealPath(), 'r'),
                            'filename' => $file->getClientOriginalName(),
                        ],
                        ['name' => 'api_key', 'contents' => $this->apiKey],
                        ['name' => 'timestamp', 'contents' => (string) $timestamp],
                        ['name' => 'folder', 'contents' => $folder],
                        ['name' => 'signature', 'contents' => $signature],
                    ],
                ]
            );

            $body = json_decode((string) $response->getBody(), true);
            return $body['secure_url'] ?? null;
        } catch (\Throwable $e) {
            Log::error('Cloudinary upload failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete file by URL (atau public_id). Extract public_id dari URL.
     */
    public function deleteByUrl(string $url): bool
    {
        if (!$this->isConfigured()) return false;
        if (!str_contains($url, 'cloudinary.com')) return false;

        // Extract public_id dari URL Cloudinary
        // contoh: https://res.cloudinary.com/xxx/image/upload/v123/ipl/avatars/abc.jpg
        // public_id = "ipl/avatars/abc" (tanpa extension, tanpa version)
        $publicId = $this->extractPublicId($url);
        if (!$publicId) return false;

        $timestamp = time();
        $paramsToSign = [
            'public_id' => $publicId,
            'timestamp' => $timestamp,
        ];
        $signature = $this->signRequest($paramsToSign);

        try {
            $this->http->post(
                "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/destroy",
                [
                    'form_params' => [
                        'public_id' => $publicId,
                        'api_key' => $this->apiKey,
                        'timestamp' => $timestamp,
                        'signature' => $signature,
                    ],
                ]
            );
            return true;
        } catch (\Throwable $e) {
            Log::warning('Cloudinary delete failed: ' . $e->getMessage());
            return false;
        }
    }

    private function signRequest(array $params): string
    {
        ksort($params);
        $toSign = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        // Cloudinary butuh "&" plain (tidak url-encoded di value), tapi http_build_query encode value
        // Safer: manual concat
        $parts = [];
        foreach ($params as $k => $v) {
            $parts[] = "{$k}={$v}";
        }
        $toSign = implode('&', $parts);
        return sha1($toSign . $this->apiSecret);
    }

    private function extractPublicId(string $url): ?string
    {
        // Match /upload/v123/PATH.ext atau /upload/PATH.ext
        if (preg_match('#/upload/(?:v\d+/)?(.+)\.[^./]+$#', $url, $m)) {
            return $m[1];
        }
        return null;
    }
}
