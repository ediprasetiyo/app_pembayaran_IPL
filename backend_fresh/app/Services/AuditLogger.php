<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Helper untuk menulis audit log. Dipakai dari controller, command, atau service.
 *
 * Contoh:
 *   AuditLogger::log('login', 'User Edi login dari mobile', severity: 'info');
 *   AuditLogger::log('payment_success', 'Pembayaran #123 lunas Rp 65rb', model: $pembayaran);
 *   AuditLogger::log('login_failed', "Login gagal untuk nomor $phone", severity: 'warning');
 */
class AuditLogger
{
    /**
     * Tulis 1 log audit.
     *
     * @param string $action e.g. 'login', 'created', 'updated', 'deleted', 'payment_success', dll
     * @param string|null $description Deskripsi human-readable
     * @param mixed $model Model Eloquent (optional) → ambil model_type & model_id
     * @param array|null $oldValues Snapshot sebelum perubahan
     * @param array|null $newValues Snapshot sesudah perubahan
     * @param string $severity 'info', 'warning', 'error', 'critical'
     * @param int|null $userId Override user (kalau aksi non-authenticated, e.g. webhook)
     */
    public static function log(
        string $action,
        ?string $description = null,
        $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        string $severity = 'info',
        ?int $userId = null,
    ): void {
        try {
            $user = $userId ? \App\Models\User::find($userId) : Auth::user();

            $request = request();
            $ip = $request?->ip();
            $ua = substr((string)($request?->userAgent() ?? ''), 0, 500);

            // Parse user agent → device, browser, OS (lightweight regex, no extra package)
            $device = self::parseUserAgent($ua);

            // Lookup geo location dari IP (cached, dengan timeout 1s)
            $geo = self::lookupGeo($ip);

            AuditLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'system',
                'user_role' => $user?->role ?? 'system',
                'action' => $action,
                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model?->getKey(),
                'description' => $description ? substr($description, 0, 500) : null,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $ip,
                'user_agent' => $ua,
                'device_type' => $device['device'],
                'browser' => $device['browser'],
                'os' => $device['os'],
                'country' => $geo['country'] ?? null,
                'city' => $geo['city'] ?? null,
                'severity' => $severity,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Jangan crash request user kalau audit log gagal — cukup error log
            logger()->error('AuditLogger gagal: ' . $e->getMessage());
        }
    }

    /**
     * Parse User Agent string → device, browser, OS.
     * Simple regex-based (no extra package).
     */
    private static function parseUserAgent(string $ua): array
    {
        $device = 'Unknown';
        $browser = 'Unknown';
        $os = 'Unknown';

        if (empty($ua)) return compact('device', 'browser', 'os');

        // Device type
        if (preg_match('/iPad/i', $ua)) $device = 'Tablet (iPad)';
        elseif (preg_match('/Android.*Tablet/i', $ua)) $device = 'Tablet (Android)';
        elseif (preg_match('/Mobile|iPhone|Android/i', $ua)) $device = 'Mobile';
        elseif (preg_match('/Windows|Mac OS X|Linux|CrOS/i', $ua)) $device = 'Desktop';

        // OS
        if (preg_match('/Windows NT 10/i', $ua)) $os = 'Windows 10/11';
        elseif (preg_match('/Windows NT ([\d.]+)/i', $ua, $m)) $os = 'Windows ' . $m[1];
        elseif (preg_match('/Mac OS X ([\d_]+)/i', $ua, $m)) $os = 'macOS ' . str_replace('_', '.', $m[1]);
        elseif (preg_match('/iPhone OS ([\d_]+)/i', $ua, $m)) $os = 'iOS ' . str_replace('_', '.', $m[1]);
        elseif (preg_match('/Android ([\d.]+)/i', $ua, $m)) $os = 'Android ' . $m[1];
        elseif (preg_match('/Linux/i', $ua)) $os = 'Linux';

        // Browser
        if (preg_match('/Edg\/([\d.]+)/i', $ua, $m)) $browser = 'Edge ' . $m[1];
        elseif (preg_match('/OPR\/([\d.]+)/i', $ua, $m)) $browser = 'Opera ' . $m[1];
        elseif (preg_match('/Chrome\/([\d.]+)/i', $ua, $m)) $browser = 'Chrome ' . explode('.', $m[1])[0];
        elseif (preg_match('/Firefox\/([\d.]+)/i', $ua, $m)) $browser = 'Firefox ' . $m[1];
        elseif (preg_match('/Safari\/([\d.]+)/i', $ua, $m)) $browser = 'Safari';
        elseif (preg_match('/IPL-Mobile|Dart\//i', $ua)) $browser = 'IPL Mobile App';

        return compact('device', 'browser', 'os');
    }

    /**
     * Geo IP lookup via ip-api.com (free, 45 req/min per IP).
     * Cached 24 jam supaya tidak hit API tiap log.
     */
    private static function lookupGeo(?string $ip): array
    {
        $default = ['country' => null, 'city' => null];
        if (empty($ip) || $ip === '127.0.0.1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return $default;
        }

        try {
            return \Illuminate\Support\Facades\Cache::remember(
                "geo_ip_$ip",
                86400, // 24 jam
                function () use ($ip, $default) {
                    $url = "http://ip-api.com/json/{$ip}?fields=status,country,city";
                    $ctx = stream_context_create(['http' => ['timeout' => 1.5]]);
                    $resp = @file_get_contents($url, false, $ctx);
                    if ($resp === false) return $default;
                    $data = json_decode($resp, true);
                    if (($data['status'] ?? '') !== 'success') return $default;
                    return [
                        'country' => $data['country'] ?? null,
                        'city' => $data['city'] ?? null,
                    ];
                }
            );
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function info(string $action, ?string $description = null, $model = null): void
    {
        self::log($action, $description, $model, severity: 'info');
    }

    public static function warning(string $action, ?string $description = null, $model = null): void
    {
        self::log($action, $description, $model, severity: 'warning');
    }

    public static function error(string $action, ?string $description = null, $model = null): void
    {
        self::log($action, $description, $model, severity: 'error');
    }

    public static function critical(string $action, ?string $description = null, $model = null): void
    {
        self::log($action, $description, $model, severity: 'critical');
    }
}
