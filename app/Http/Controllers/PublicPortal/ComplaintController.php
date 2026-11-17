<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicPortal\AdminComplaintNoteRequest;
use App\Http\Requests\PublicPortal\AdminComplaintReplyRequest;
use App\Http\Requests\PublicPortal\AdminComplaintStatusRequest;
use App\Models\Complaint;
use App\Models\ComplaintActivity;
use App\Models\Event;
use App\Models\Person;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ComplaintController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_complaints|view_public_complaints', only: ['index', 'show', 'downloadAttachment']),
            new Middleware('permission:manage_complaints|manage_public_complaints', only: ['updatePriority', 'addInternalNote', 'linkPerson', 'markUnderReview']),
            new Middleware('permission:assign_complaints|manage_complaints|manage_public_complaints', only: ['assign']),
            new Middleware('permission:respond_complaints|reply_public_complaints|manage_complaints|manage_public_complaints', only: ['respond', 'reply']),
            new Middleware('permission:resolve_complaints|resolve_public_complaints|manage_complaints|manage_public_complaints', only: ['resolve', 'rejectComplaint', 'close']),
        ];
    }

    /**
     * Display a listing of complaints.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax() || $request->wantsJson()) {
            $query = Complaint::with(['person', 'event', 'assignee']);

            // Filters
            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->filled('category') && $request->category !== 'all') {
                $query->where('category', $request->category);
            }

            if ($request->filled('priority') && $request->priority !== 'all') {
                $query->where('priority', $request->priority);
            }

            if ($request->filled('assigned_to')) {
                if ($request->assigned_to === 'unassigned') {
                    $query->whereNull('assigned_to');
                } else {
                    $query->where('assigned_to', $request->integer('assigned_to'));
                }
            }

            if ($request->filled('event_id')) {
                $query->where('event_id', $request->integer('event_id'));
            }

            // Search
            if ($searchValue = $request->input('search.value')) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('tracking_number', 'like', "%{$searchValue}%")
                        ->orWhere('subject', 'like', "%{$searchValue}%")
                        ->orWhere('complainant_name', 'like', "%{$searchValue}%")
                        ->orWhere('complainant_phone', 'like', "%{$searchValue}%")
                        ->orWhere('complainant_email', 'like', "%{$searchValue}%");
                });
            }

            $totalRecords = Complaint::count();
            $filteredRecords = $query->count();

            // Ordering
            $orderColumnIndex = $request->input('order.0.column');
            $orderDirection = $request->input('order.0.dir', 'desc');

            $columnMap = [
                0 => 'tracking_number',
                1 => 'complainant_name',
                2 => 'category',
                4 => 'assigned_to',
                5 => 'status',
                6 => 'created_at',
            ];

            if ($orderColumnIndex !== null && isset($columnMap[$orderColumnIndex])) {
                $query->orderBy($columnMap[$orderColumnIndex], $orderDirection);
            } else {
                $query->orderByDesc('id');
            }

            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 15);
            $complaints = $query->skip($start)->take($length)->get();

            $data = $complaints->map(function (Complaint $c) {
                return [
                    'tracking_info' => $this->formatTrackingColumn($c),
                    'complainant' => $this->formatComplainantColumn($c),
                    'category_priority' => $this->formatCategoryPriorityColumn($c),
                    'related_to' => $this->formatRelatedColumn($c),
                    'assigned_to' => $this->formatAssigneeColumn($c),
                    'status' => $this->formatStatusBadge($c->status),
                    'submitted_at' => $c->created_at->format('d M Y, h:i A'),
                    'actions' => $this->formatActionsColumn($c),
                ];
            });

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        $events = Event::orderBy('name_en')->get(['id', 'name_en', 'name_bn', 'event_code']);
        $staffUsers = User::where('status', 'active')
            ->where(function ($q) {
                $q->whereHas('roles', function ($rq) {
                    $rq->whereIn('slug', ['super_admin', 'temple_admin', 'committee_member', 'treasurer'])
                        ->orWhereHas('permissions', function ($pq) {
                            $pq->whereIn('slug', ['view_complaints', 'manage_complaints', 'resolve_complaints', 'respond_complaints', 'assign_complaints']);
                        });
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('portal.complaints.index', compact('events', 'staffUsers'));
    }

    /**
     * Show complaint details.
     */
    public function show(Complaint $complaint): View
    {
        $complaint->load(['person', 'event', 'payment.receiverAccount', 'assignee', 'resolver', 'closer', 'activities.actor']);
        $staffUsers = User::where('status', 'active')
            ->where(function ($q) {
                $q->whereHas('roles', function ($rq) {
                    $rq->whereIn('slug', ['super_admin', 'temple_admin', 'committee_member', 'treasurer'])
                        ->orWhereHas('permissions', function ($pq) {
                            $pq->whereIn('slug', ['view_complaints', 'manage_complaints', 'resolve_complaints', 'respond_complaints', 'assign_complaints']);
                        });
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Suggested match for Person by phone
        $suggestedPersons = collect();
        if (! $complaint->person_id && $complaint->complainant_phone) {
            $suggestedPersons = Person::where('phone', 'like', "%{$complaint->complainant_phone}%")
                ->limit(5)
                ->get();
        }

        $activities = $complaint->activities;

        return view('portal.complaints.show', compact('complaint', 'staffUsers', 'suggestedPersons', 'activities'));
    }

    /**
     * Mark complaint as Under Review.
     */
    public function markUnderReview(Request $request, Complaint $complaint): JsonResponse|RedirectResponse
    {
        if (in_array($complaint->status, [Complaint::STATUS_RESOLVED, Complaint::STATUS_REJECTED, Complaint::STATUS_CLOSED])) {
            $msg = __('portal.complaints_module.terminal_status_error', ['status' => strtoupper($complaint->status)]);
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $prevStatus = $complaint->status;

        DB::transaction(function () use ($complaint, $prevStatus) {
            $complaint->update(['status' => Complaint::STATUS_UNDER_REVIEW]);

            ComplaintActivity::create([
                'complaint_id' => $complaint->id,
                'actor_id' => auth()->id(),
                'activity_type' => ComplaintActivity::TYPE_STATUS_CHANGE,
                'previous_status' => $prevStatus,
                'new_status' => Complaint::STATUS_UNDER_REVIEW,
                'content' => 'Marked under review by admin',
                'visibility' => ComplaintActivity::VISIBILITY_DONOR,
            ]);

            AuditLogger::log(
                'complaint_under_review',
                "Marked complaint {$complaint->tracking_number} as under review",
                $complaint
            );
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('portal.complaints_module.status_updated'),
            ]);
        }

        return back()->with('success', __('portal.complaints_module.status_updated'));
    }

    /**
     * Assign complaint to staff user.
     */
    public function assign(Request $request, Complaint $complaint): JsonResponse|RedirectResponse
    {
        $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $assignedTo = $request->input('assigned_to') ? $request->integer('assigned_to') : null;
        $targetUser = null;

        if ($assignedTo) {
            $targetUser = User::where('id', $assignedTo)->where('status', 'active')->first();
            if (! $targetUser) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => __('portal.complaints_module.invalid_assignee')], 422);
                }

                return back()->with('error', __('portal.complaints_module.invalid_assignee'));
            }

            // Ensure target user is active staff/admin and not ordinary devotee
            $isStaffOrAdmin = $targetUser->hasRole(['super_admin', 'temple_admin', 'committee_member', 'treasurer'])
                || $targetUser->can('view_complaints')
                || $targetUser->can('manage_complaints')
                || $targetUser->can('assign_complaints');

            if (! $isStaffOrAdmin) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => __('portal.complaints_module.invalid_assignee')], 422);
                }

                return back()->with('error', __('portal.complaints_module.invalid_assignee'));
            }
        }

        $prevAssigneeName = $complaint->assignee?->name ?? __('portal.complaints_module.unassigned');
        $newAssigneeName = $targetUser?->name ?? __('portal.complaints_module.unassigned');

        $status = $complaint->status;
        $prevStatus = $complaint->status;

        // Auto move from submitted to under_review if assigned
        if ($assignedTo && $complaint->status === Complaint::STATUS_SUBMITTED) {
            $status = Complaint::STATUS_UNDER_REVIEW;
        }

        DB::transaction(function () use ($complaint, $assignedTo, $status, $prevStatus, $prevAssigneeName, $newAssigneeName) {
            $complaint->update([
                'assigned_to' => $assignedTo,
                'status' => $status,
            ]);

            $assignmentContent = ($prevAssigneeName !== __('portal.complaints_module.unassigned'))
                ? "Assignment changed from {$prevAssigneeName} to {$newAssigneeName}"
                : "Assigned to {$newAssigneeName}";

            ComplaintActivity::create([
                'complaint_id' => $complaint->id,
                'actor_id' => auth()->id(),
                'activity_type' => ComplaintActivity::TYPE_ASSIGNMENT,
                'previous_status' => $prevStatus,
                'new_status' => $status,
                'content' => $assignmentContent,
                'visibility' => ComplaintActivity::VISIBILITY_DONOR,
            ]);

            AuditLogger::log(
                'complaint_assigned',
                "Assigned complaint {$complaint->tracking_number} to {$newAssigneeName} (previous: {$prevAssigneeName})",
                $complaint
            );
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('portal.complaints_module.assigned_successfully'),
                'assignee_name' => $newAssigneeName,
            ]);
        }

        return back()->with('success', __('portal.complaints_module.assigned_successfully'));
    }

    /**
     * Respond to complaint (and optionally update status).
     */
    public function respond(AdminComplaintReplyRequest $request, Complaint $complaint): JsonResponse|RedirectResponse
    {
        $replyContent = $request->input('admin_response') ?: $request->input('reply');
        $newStatus = $request->input('update_status') ?: Complaint::STATUS_REPLIED;
        $prevStatus = $complaint->status;

        DB::transaction(function () use ($complaint, $replyContent, $newStatus, $prevStatus) {
            $updateData = [
                'admin_response' => $replyContent,
                'status' => $newStatus,
            ];

            if ($newStatus === Complaint::STATUS_RESOLVED) {
                $updateData['resolved_at'] = now();
                $updateData['resolved_by'] = auth()->id();
            }

            $complaint->update($updateData);

            ComplaintActivity::create([
                'complaint_id' => $complaint->id,
                'actor_id' => auth()->id(),
                'activity_type' => ComplaintActivity::TYPE_REPLY,
                'previous_status' => $prevStatus,
                'new_status' => $newStatus,
                'content' => $replyContent,
                'visibility' => ComplaintActivity::VISIBILITY_DONOR,
            ]);

            AuditLogger::log(
                'complaint_responded',
                "Recorded admin response for complaint {$complaint->tracking_number}",
                $complaint
            );
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('portal.complaints_module.response_recorded'),
            ]);
        }

        return back()->with('success', __('portal.complaints_module.response_recorded'));
    }

    /**
     * Resolve complaint with resolution note.
     */
    public function resolve(AdminComplaintStatusRequest $request, Complaint $complaint): JsonResponse|RedirectResponse
    {
        $note = $request->input('note') ?: ($request->input('admin_response') ?: 'Resolved');
        $prevStatus = $complaint->status;

        DB::transaction(function () use ($complaint, $note, $prevStatus) {
            $complaint->update([
                'admin_response' => $note,
                'status' => Complaint::STATUS_RESOLVED,
                'resolved_at' => now(),
                'resolved_by' => auth()->id(),
            ]);

            ComplaintActivity::create([
                'complaint_id' => $complaint->id,
                'actor_id' => auth()->id(),
                'activity_type' => ComplaintActivity::TYPE_RESOLUTION,
                'previous_status' => $prevStatus,
                'new_status' => Complaint::STATUS_RESOLVED,
                'content' => $note,
                'visibility' => ComplaintActivity::VISIBILITY_DONOR,
            ]);

            AuditLogger::log(
                'complaint_resolved',
                "Resolved complaint {$complaint->tracking_number}",
                $complaint
            );
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('portal.complaints_module.resolved_successfully'),
            ]);
        }

        return back()->with('success', __('portal.complaints_module.resolved_successfully'));
    }

    /**
     * Reject complaint with reason.
     */
    public function rejectComplaint(AdminComplaintStatusRequest $request, Complaint $complaint): JsonResponse|RedirectResponse
    {
        $reason = $request->input('rejection_reason') ?: ($request->input('note') ?: 'Rejected');
        $prevStatus = $complaint->status;

        DB::transaction(function () use ($complaint, $reason, $prevStatus) {
            $complaint->update([
                'admin_response' => $reason,
                'status' => Complaint::STATUS_REJECTED,
                'resolved_at' => now(),
                'resolved_by' => auth()->id(),
            ]);

            ComplaintActivity::create([
                'complaint_id' => $complaint->id,
                'actor_id' => auth()->id(),
                'activity_type' => ComplaintActivity::TYPE_REJECTION,
                'previous_status' => $prevStatus,
                'new_status' => Complaint::STATUS_REJECTED,
                'content' => $reason,
                'visibility' => ComplaintActivity::VISIBILITY_DONOR,
            ]);

            AuditLogger::log(
                'complaint_rejected',
                "Rejected complaint {$complaint->tracking_number} (Reason: {$reason})",
                $complaint
            );
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('portal.complaints_module.rejected_successfully'),
            ]);
        }

        return back()->with('success', __('portal.complaints_module.rejected_successfully'));
    }

    /**
     * Close complaint.
     */
    public function close(Request $request, Complaint $complaint): JsonResponse|RedirectResponse
    {
        $prevStatus = $complaint->status;

        DB::transaction(function () use ($complaint, $prevStatus) {
            $complaint->update([
                'status' => Complaint::STATUS_CLOSED,
                'closed_at' => now(),
                'closed_by' => auth()->id(),
            ]);

            ComplaintActivity::create([
                'complaint_id' => $complaint->id,
                'actor_id' => auth()->id(),
                'activity_type' => ComplaintActivity::TYPE_CLOSE,
                'previous_status' => $prevStatus,
                'new_status' => Complaint::STATUS_CLOSED,
                'content' => 'Closed complaint',
                'visibility' => ComplaintActivity::VISIBILITY_DONOR,
            ]);

            AuditLogger::log(
                'complaint_closed',
                "Closed complaint {$complaint->tracking_number}",
                $complaint
            );
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('portal.complaints_module.closed_successfully'),
            ]);
        }

        return back()->with('success', __('portal.complaints_module.closed_successfully'));
    }

    /**
     * Update complaint priority.
     */
    public function updatePriority(Request $request, Complaint $complaint): JsonResponse|RedirectResponse
    {
        $request->validate([
            'priority' => ['required', 'string', 'in:low,medium,high,urgent'],
        ]);

        $complaint->update([
            'priority' => $request->input('priority'),
        ]);

        AuditLogger::log(
            'complaint_priority_updated',
            "Updated priority of complaint {$complaint->tracking_number} to {$request->input('priority')}",
            $complaint
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('portal.complaints_module.priority_updated'),
            ]);
        }

        return back()->with('success', __('portal.complaints_module.priority_updated'));
    }

    /**
     * Add private internal note.
     */
    public function addInternalNote(AdminComplaintNoteRequest $request, Complaint $complaint): JsonResponse|RedirectResponse
    {
        $note = trim($request->input('internal_notes') ?: $request->input('note'));

        DB::transaction(function () use ($complaint, $note) {
            $timestamp = now()->format('d M Y, h:i A');
            $userName = auth()->user()->name;
            $entry = "[{$timestamp} - {$userName}]: {$note}";
            $newInternal = $complaint->internal_notes
                ? $complaint->internal_notes."\n\n".$entry
                : $entry;

            $complaint->update(['internal_notes' => $newInternal]);

            ComplaintActivity::create([
                'complaint_id' => $complaint->id,
                'actor_id' => auth()->id(),
                'activity_type' => ComplaintActivity::TYPE_INTERNAL_NOTE,
                'previous_status' => $complaint->status,
                'new_status' => $complaint->status,
                'content' => $note,
                'visibility' => ComplaintActivity::VISIBILITY_INTERNAL,
            ]);

            AuditLogger::log(
                'complaint_internal_note_added',
                "Added internal note to complaint {$complaint->tracking_number}",
                $complaint
            );
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('portal.complaints_module.internal_note_saved'),
            ]);
        }

        return back()->with('success', __('portal.complaints_module.internal_note_saved'));
    }

    /**
     * Link complaint to Person.
     */
    public function linkPerson(Request $request, Complaint $complaint): JsonResponse|RedirectResponse
    {
        $request->validate([
            'person_id' => ['required', 'integer', 'exists:people,id'],
        ]);

        $person = Person::findOrFail($request->integer('person_id'));

        $complaint->update([
            'person_id' => $person->id,
        ]);

        AuditLogger::log(
            'complaint_linked_person',
            "Linked complaint {$complaint->tracking_number} to person {$person->name_en} ({$person->person_code})",
            $complaint
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('portal.complaints_module.person_linked'),
            ]);
        }

        return back()->with('success', __('portal.complaints_module.person_linked'));
    }

    /**
     * Download or stream complaint attachment for authorized administrator.
     */
    public function downloadAttachment(Complaint $complaint): Response
    {
        if (! $complaint->attachment_path) {
            abort(404, __('portal.complaints_module.no_attachment'));
        }

        $path = str_replace('\\', '/', $complaint->attachment_path);
        if (str_contains($path, '..')) {
            abort(400, 'Invalid file path.');
        }

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->response($path);
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->response($path);
        }

        abort(404, __('portal.complaints_module.no_attachment'));
    }

    // -------------------------------------------------------------
    // Formatters for DataTables
    // -------------------------------------------------------------

    protected function formatTrackingColumn(Complaint $c): string
    {
        $showUrl = route('portal.complaints.show', $c);
        $trackingNumber = e($c->tracking_number);
        $subject = e($c->subject);

        return '<div><a href="'.$showUrl.'" class="fw-bold text-decoration-none"><code class="text-primary fs-6">'.$trackingNumber.'</code></a>'
            .'<div class="small fw-semibold text-truncate text-dark" style="max-width: 200px;" title="'.$subject.'">'.$subject.'</div></div>';
    }

    protected function formatComplainantColumn(Complaint $c): string
    {
        $name = e($c->complainant_name);
        $phone = e($c->complainant_phone);

        $html = '<div class="fw-semibold">'.$name.'</div>';

        if ($c->person) {
            $personUrl = route('people.show', $c->person_id);
            $html .= '<div class="small"><a href="'.$personUrl.'" class="badge bg-success-subtle text-success border text-decoration-none"><i class="bi bi-person-check me-1"></i>'.e($c->person->person_code).'</a></div>';
        } else {
            $html .= '<div class="small"><span class="badge bg-light text-muted border">Guest</span></div>';
        }

        if ($phone) {
            $html .= '<div class="small text-muted"><i class="bi bi-telephone me-1"></i>'.$phone.'</div>';
        }

        return $html;
    }

    protected function formatCategoryPriorityColumn(Complaint $c): string
    {
        $catBadge = '<span class="badge bg-secondary mb-1">'.e($c->category_name).'</span>';

        $priorityBadge = match ($c->priority) {
            Complaint::PRIORITY_URGENT => '<span class="badge bg-danger">'.__('portal.complaint_priorities.urgent').'</span>',
            Complaint::PRIORITY_HIGH => '<span class="badge bg-warning text-dark">'.__('portal.complaint_priorities.high').'</span>',
            Complaint::PRIORITY_MEDIUM => '<span class="badge bg-info text-dark">'.__('portal.complaint_priorities.medium').'</span>',
            default => '<span class="badge bg-light text-dark border">'.__('portal.complaint_priorities.low').'</span>',
        };

        return '<div><div>'.$catBadge.'</div><div>'.$priorityBadge.'</div></div>';
    }

    protected function formatRelatedColumn(Complaint $c): string
    {
        if ($c->event) {
            $eventUrl = route('portal.events.show', $c->event_id);

            return '<div><a href="'.$eventUrl.'" class="text-decoration-none small fw-semibold">'.e($c->event->name).'</a>'
                .'<div class="small text-muted"><code>'.e($c->event->event_code).'</code></div></div>';
        }

        if ($c->payment) {
            $paymentUrl = route('portal.payments.show', $c->collection_payment_id);

            return '<div><a href="'.$paymentUrl.'" class="text-decoration-none small fw-semibold"><code>'.e($c->payment->payment_code).'</code></a>'
                .'<div class="small text-muted">৳ '.number_format((float) $c->payment->amount, 2).'</div></div>';
        }

        return '<span class="text-muted small">—</span>';
    }

    protected function formatAssigneeColumn(Complaint $c): string
    {
        if ($c->assignee) {
            return '<span class="badge bg-light text-dark border"><i class="bi bi-person me-1"></i>'.e($c->assignee->name).'</span>';
        }

        return '<span class="badge bg-warning-subtle text-warning-emphasis border"><i class="bi bi-person-dash me-1"></i>'.__('portal.complaints_module.unassigned').'</span>';
    }

    protected function formatStatusBadge(string $status): string
    {
        return match ($status) {
            Complaint::STATUS_RESOLVED => '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>'.__('portal.complaint_statuses.resolved').'</span>',
            Complaint::STATUS_REJECTED => '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>'.__('portal.complaint_statuses.rejected').'</span>',
            Complaint::STATUS_CLOSED => '<span class="badge bg-dark"><i class="bi bi-lock me-1"></i>'.__('portal.complaint_statuses.closed').'</span>',
            Complaint::STATUS_REPLIED => '<span class="badge bg-info text-dark"><i class="bi bi-chat-dots me-1"></i>'.__('portal.complaint_statuses.replied').'</span>',
            Complaint::STATUS_UNDER_REVIEW => '<span class="badge bg-primary"><i class="bi bi-eye me-1"></i>'.__('portal.complaint_statuses.under_review').'</span>',
            default => '<span class="badge bg-warning text-dark"><i class="bi bi-envelope me-1"></i>'.__('portal.complaint_statuses.submitted').'</span>',
        };
    }

    protected function formatActionsColumn(Complaint $c): string
    {
        $showUrl = route('portal.complaints.show', $c);
        $user = auth()->user();

        $html = '<div class="btn-group btn-group-sm" role="group">';
        $html .= '<a href="'.$showUrl.'" class="btn btn-outline-secondary" title="'.__('portal.actions.view').'"><i class="bi bi-eye"></i></a>';

        if (! in_array($c->status, [Complaint::STATUS_RESOLVED, Complaint::STATUS_REJECTED, Complaint::STATUS_CLOSED]) && $user) {
            if ($user->can('respond_complaints') || $user->can('manage_complaints') || $user->can('reply_public_complaints')) {
                $html .= '<button type="button" class="btn btn-outline-primary btn-respond" '
                    .'data-id="'.$c->id.'" '
                    .'data-tracking="'.e($c->tracking_number).'" '
                    .'data-url="'.route('portal.complaints.respond', $c).'" '
                    .'title="'.__('portal.complaints_module.respond').'">'
                    .'<i class="bi bi-reply"></i>'
                    .'</button>';
            }

            if ($user->can('resolve_complaints') || $user->can('manage_complaints') || $user->can('resolve_public_complaints')) {
                $html .= '<button type="button" class="btn btn-outline-success btn-resolve" '
                    .'data-id="'.$c->id.'" '
                    .'data-tracking="'.e($c->tracking_number).'" '
                    .'data-url="'.route('portal.complaints.resolve', $c).'" '
                    .'title="'.__('portal.complaints_module.resolve').'">'
                    .'<i class="bi bi-check-lg"></i>'
                    .'</button>';
            }
        }

        $html .= '</div>';

        return $html;
    }
}
