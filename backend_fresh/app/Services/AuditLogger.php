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
                'severity' => $severity,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Jangan crash request user kalau audit log gagal — cukup error log
            logger()->error('AuditLogger gagal: ' . $e->getMessage());
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
