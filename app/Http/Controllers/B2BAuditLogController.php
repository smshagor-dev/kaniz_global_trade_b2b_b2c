<?php

namespace App\Http\Controllers;

use App\Models\B2BAuditLog;
use Illuminate\Http\Request;

class B2BAuditLogController extends Controller
{
    public function adminIndex(Request $request)
    {
        $auditLogs = B2BAuditLog::with(['actor', 'actorCompany'])
            ->when($request->event_type, fn ($query, $eventType) => $query->where('event_type', $eventType))
            ->latest()
            ->paginate(30);

        return view('backend.b2b.audit_logs.index', compact('auditLogs'));
    }
}
