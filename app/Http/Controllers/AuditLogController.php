<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs with DataTables support.
     */
    public function index(Request $request): View|JsonResponse
    {
        if (($request->ajax() || $request->wantsJson()) && $request->has('draw')) {
            return $this->dataTableResponse($request);
        }

        $logs = AuditLog::with('user.roles')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.audit_logs', compact('logs'));
    }

    /**
     * Server-side DataTables JSON response for audit logs.
     */
    protected function dataTableResponse(Request $request): JsonResponse
    {
        $draw = $request->integer('draw');
        $start = $request->integer('start', 0);
        $length = $request->integer('length', 25);
        $searchValue = trim($request->input('search.value', ''));

        $query = AuditLog::query()->with('user.roles');
        $totalRecords = AuditLog::count();

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('id', 'like', "%{$searchValue}%")
                    ->orWhere('action', 'like', "%{$searchValue}%")
                    ->orWhere('description', 'like', "%{$searchValue}%")
                    ->orWhere('model_type', 'like', "%{$searchValue}%")
                    ->orWhere('ip_address', 'like', "%{$searchValue}%")
                    ->orWhereHas('user', function ($uq) use ($searchValue) {
                        $uq->where('name', 'like', "%{$searchValue}%")
                            ->orWhere('email', 'like', "%{$searchValue}%");
                    });
            });
        }

        $filteredRecords = $query->count();

        $orderColumnIndex = $request->has('order.0.column') ? (int) $request->input('order.0.column') : null;
        $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $columnMap = [
            0 => 'id',
            1 => 'created_at',
            3 => 'action',
            4 => 'description',
            5 => 'model_type',
            6 => 'ip_address',
        ];

        if ($orderColumnIndex !== null && isset($columnMap[$orderColumnIndex])) {
            $query->orderBy($columnMap[$orderColumnIndex], $orderDir);
        } else {
            $query->latest();
        }

        $records = $query->skip($start)->take($length)->get();

        $timezone = config('app.timezone');

        $data = $records->map(function ($log) use ($timezone) {
            $idHtml = '<span class="text-muted small">#'.$log->id.'</span>';
            $timeHtml = '<span class="text-nowrap text-muted small">'.e($log->created_at->timezone($timezone)->format('Y-m-d H:i:s')).'</span>';

            if ($log->user) {
                $roleSlug = $log->user->roles->first()?->slug ?? 'devotee';
                $roleTitle = $log->user->primary_role_title;
                $userHtml = '<span class="badge badge-role-'.e($roleSlug).' me-1">'.e($roleTitle).'</span>'
                    .'<span class="fw-semibold">'.e($log->user->name).'</span>';
            } else {
                $userHtml = '<span class="text-muted fst-italic">System</span>';
            }

            $actionHtml = '<span class="badge bg-light text-dark border">'.e($log->action).'</span>';
            $descHtml = e($log->description);
            $modelHtml = '<span class="small text-muted">'.e($log->model_type ? class_basename($log->model_type).' #'.$log->model_id : '—').'</span>';
            $ipHtml = '<span class="text-muted small">'.e($log->ip_address ?? '127.0.0.1').'</span>';

            $metaHtml = $log->properties
                ? '<code class="small">'.e(json_encode($log->properties)).'</code>'
                : '<span class="text-muted small">—</span>';

            return [
                $idHtml,
                $timeHtml,
                $userHtml,
                $actionHtml,
                $descHtml,
                $modelHtml,
                $ipHtml,
                $metaHtml,
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }
}
