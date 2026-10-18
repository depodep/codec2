<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAuditLogController extends Controller
{
    public function index(Request $request)
    {
        // Optional date range filtering
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Base query with filters
        $query = AuditLog::query();

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('module')) {
            $query->where('module', $request->input('module'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('user')) {
            $query->where('user_name', 'like', '%' . $request->input('user') . '%');
        }

        // Statistics calculations
        $totalActivities = (clone $query)->count();
        $loginActivities = (clone $query)->where('action', 'LIKE', '%Login%')->count();
        $dataChanges = (clone $query)->where(function ($q) {
            $q->where('action', 'LIKE', 'Added%')
              ->orWhere('action', 'LIKE', 'Updated%')
              ->orWhere('action', 'LIKE', 'Deleted%')
              ->orWhere('action', 'LIKE', 'Imported%')
              ->orWhere('action', 'LIKE', 'Cleared%')
              ->orWhere('action', 'LIKE', 'Overrode%')
              ->orWhere('action', 'LIKE', '%Created%')
              ->orWhere('action', 'LIKE', '%Modified%')
              ->orWhere('action', 'LIKE', 'Assigned%')
              ->orWhere('action', 'LIKE', 'Regenerated%')
              ->orWhere('action', 'LIKE', '%Link%')
              ->orWhere('action', 'LIKE', '%Time%');
        })->count();
        $failedActivities = (clone $query)->where('status', 'Failed')->count();

        // Paginated logs table (15 per page)
        $auditLogs = $query->latest('id')->paginate(15)->withQueryString();

        return view('admin.audit-logs.index', compact(
            'totalActivities',
            'loginActivities',
            'dataChanges',
            'failedActivities',
            'auditLogs',
            'startDate',
            'endDate'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $format = strtolower($request->input('format', 'csv'));

        $query = AuditLog::query();

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) $query->where('role', $request->input('role'));
        if ($request->filled('module')) $query->where('module', $request->input('module'));
        if ($request->filled('status')) $query->where('status', $request->input('status'));
        if ($request->filled('user')) $query->where('user_name', 'like', '%' . $request->input('user') . '%');

        $logs = $query->latest('id')->get();

        // Log the export activity
        AuditLog::record(
            'Exported Audit Logs',
            'Audit Logs',
            'Success',
            "Exported {$logs->count()} audit log record(s) as " . strtoupper($format)
        );

        $fileName = 'siatrack-audit-logs-' . date('Y-m-d') . '.' . ($format === 'excel' ? 'xls' : 'csv');

        $headers = [
            "Content-Type"        => $format === 'excel' ? 'application/vnd.ms-excel; charset=UTF-8' : 'text/csv; charset=UTF-8',
            "Content-Disposition" => "attachment; filename=\"$fileName\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens with proper character encoding
            fwrite($file, "\xEF\xBB\xBF");

            fputcsv($file, ['ID', 'Date & Time', 'User Name', 'Role', 'Action', 'Module', 'Status', 'IP Address', 'Device', 'Description']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '',
                    $log->user_name,
                    $log->role,
                    $log->action,
                    $log->module,
                    $log->status,
                    $log->ip_address,
                    $log->device ?? 'N/A',
                    $log->description
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}