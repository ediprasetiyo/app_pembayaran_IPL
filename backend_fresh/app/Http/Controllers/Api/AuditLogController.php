<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * List audit log dengan filter.
     * Query params:
     *  - search: cari di description/user_name
     *  - action: filter by action (login, created, deleted, dll)
     *  - severity: info, warning, error, critical
     *  - user_id: filter by specific user
     *  - model_type: filter by model class
     *  - from_date / to_date: range tanggal
     *  - per_page: default 30
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query()->orderByDesc('created_at');

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%$s%")
                  ->orWhere('user_name', 'like', "%$s%")
                  ->orWhere('action', 'like', "%$s%");
            });
        }

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        if ($severity = $request->input('severity')) {
            $query->where('severity', $severity);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($modelType = $request->input('model_type')) {
            $query->where('model_type', 'like', "%$modelType%");
        }

        if ($from = $request->input('from_date')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('to_date')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $perPage = (int) ($request->input('per_page') ?? 30);
        $perPage = min(max($perPage, 1), 200);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Statistik untuk dashboard audit log.
     */
    public function stats(): JsonResponse
    {
        $total = AuditLog::count();
        $today = AuditLog::whereDate('created_at', today())->count();
        $bySeverity = AuditLog::selectRaw('severity, COUNT(*) as total')
            ->groupBy('severity')
            ->pluck('total', 'severity');
        $byAction = AuditLog::selectRaw('action, COUNT(*) as total')
            ->groupBy('action')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'action');

        return response()->json([
            'total' => $total,
            'today' => $today,
            'by_severity' => $bySeverity,
            'top_actions' => $byAction,
        ]);
    }

    /**
     * Hapus log lama untuk hemat storage (super_admin only).
     * Default: hapus yang > 90 hari.
     */
    public function cleanup(Request $request): JsonResponse
    {
        $days = (int) ($request->input('days') ?? 90);
        $days = max(7, min($days, 365));

        $cutoff = now()->subDays($days);
        $deleted = AuditLog::where('created_at', '<', $cutoff)->delete();

        return response()->json([
            'message' => "Berhasil hapus $deleted log lebih dari $days hari.",
            'deleted' => $deleted,
        ]);
    }
}
