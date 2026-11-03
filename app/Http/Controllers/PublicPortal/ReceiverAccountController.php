<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicPortal\StoreReceiverAccountRequest;
use App\Http\Requests\PublicPortal\UpdateReceiverAccountRequest;
use App\Models\Event;
use App\Models\FinancialAccount;
use App\Models\ReceiverAccount;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReceiverAccountController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_receiver_accounts', only: ['index', 'show']),
            new Middleware('permission:manage_receiver_accounts', only: ['create', 'store', 'edit', 'update', 'destroy', 'toggleActive']),
            new Middleware('permission:verify_receiver_accounts', only: ['verify', 'unverify']),
        ];
    }

    /**
     * Display a listing of receiver accounts.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax() || $request->wantsJson()) {
            $query = ReceiverAccount::with(['financialAccount', 'event', 'verifier']);

            // Filters
            if ($request->filled('payment_method')) {
                $query->where('payment_method', $request->input('payment_method'));
            }

            if ($request->filled('is_active') && $request->input('is_active') !== '') {
                $query->where('is_active', (bool) $request->input('is_active'));
            }

            if ($request->filled('is_verified') && $request->input('is_verified') !== '') {
                $query->where('is_verified', (bool) $request->input('is_verified'));
            }

            // Global search
            if ($searchValue = $request->input('search.value')) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('account_name', 'like', "%{$searchValue}%")
                        ->orWhere('account_number', 'like', "%{$searchValue}%")
                        ->orWhere('bank_name', 'like', "%{$searchValue}%")
                        ->orWhere('branch_name', 'like', "%{$searchValue}%")
                        ->orWhere('routing_number', 'like', "%{$searchValue}%");
                });
            }

            $totalRecords = ReceiverAccount::count();
            $filteredRecords = $query->count();

            // Ordering
            $orderColumnIndex = $request->input('order.0.column');
            $orderDirection = $request->input('order.0.dir', 'asc');

            $columnMap = [
                0 => 'payment_method',
                1 => 'account_name',
                2 => 'account_number',
                4 => 'is_active',
                5 => 'display_order',
            ];

            if ($orderColumnIndex !== null && isset($columnMap[$orderColumnIndex])) {
                $query->orderBy($columnMap[$orderColumnIndex], $orderDirection);
            } else {
                $query->orderBy('display_order')->orderByDesc('id');
            }

            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 15);
            $accounts = $query->skip($start)->take($length)->get();

            $data = $accounts->map(function (ReceiverAccount $acc) {
                return [
                    'payment_method' => $this->formatMethodColumn($acc),
                    'account_name' => $this->formatAccountNameColumn($acc),
                    'account_details' => $this->formatAccountDetailsColumn($acc),
                    'qr_code' => $this->formatQrCodeColumn($acc),
                    'status' => $this->formatStatusColumn($acc),
                    'display_order' => '<span class="badge bg-light text-dark border">#'.e($acc->display_order).'</span>',
                    'actions' => $this->formatActionsColumn($acc),
                ];
            });

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        return view('portal.receivers.index');
    }

    /**
     * Show form to create a new receiver account.
     */
    public function create(): View
    {
        $events = Event::with('category')
            ->whereIn('status', [Event::STATUS_ACTIVE, Event::STATUS_UPCOMING, Event::STATUS_CLOSING])
            ->orderBy('start_date', 'desc')
            ->get();

        $financialAccounts = FinancialAccount::where('account_type', FinancialAccount::TYPE_ASSET)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        return view('portal.receivers.create', compact('financialAccounts', 'events'));
    }

    /**
     * Store a newly created receiver account.
     */
    public function store(StoreReceiverAccountRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $qrCodePath = null;
        if ($request->hasFile('qr_code')) {
            $qrCodePath = $request->file('qr_code')->store('receiver_qr_codes', 'public');
        }

        $canVerify = $request->user()->can('verify_receiver_accounts');
        $isVerified = $canVerify && $request->boolean('is_verified');

        $account = ReceiverAccount::create([
            'payment_method' => $validated['payment_method'],
            'account_name' => $validated['account_name'],
            'account_number' => $validated['account_number'],
            'account_type' => $validated['account_type'],
            'bank_name' => $validated['bank_name'] ?? null,
            'branch_name' => $validated['branch_name'] ?? null,
            'routing_number' => $validated['routing_number'] ?? null,
            'swift_code' => $validated['swift_code'] ?? null,
            'qr_code_image_path' => $qrCodePath,
            'instructions_en' => $validated['instructions_en'] ?? null,
            'instructions_bn' => $validated['instructions_bn'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'is_verified' => $isVerified,
            'verified_at' => $isVerified ? now() : null,
            'verified_by' => $isVerified ? auth()->id() : null,
            'display_order' => $validated['display_order'] ?? 0,
            'financial_account_id' => $validated['financial_account_id'] ?? null,
            'event_id' => $validated['event_id'] ?? null,
            'created_by' => auth()->id(),
        ]);

        AuditLogger::log(
            'receiver_account_created',
            "Created receiver account '{$account->account_name}' ({$account->payment_method})",
            $account
        );

        return redirect()
            ->route('portal.receivers.index')
            ->with('success', __('portal.receivers.created_successfully'));
    }

    /**
     * Display the specified receiver account details.
     */
    public function show(ReceiverAccount $receiver): View
    {
        $receiver->load(['financialAccount', 'creator', 'updater', 'verifier']);

        return view('portal.receivers.show', compact('receiver'));
    }

    /**
     * Show form to edit the receiver account.
     */
    public function edit(ReceiverAccount $receiver): View
    {
        $events = Event::with('category')->orderBy('start_date', 'desc')->get();
        $financialAccounts = FinancialAccount::where('account_type', FinancialAccount::TYPE_ASSET)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        return view('portal.receivers.edit', compact('receiver', 'financialAccounts', 'events'));
    }

    /**
     * Update the receiver account.
     */
    public function update(UpdateReceiverAccountRequest $request, ReceiverAccount $receiver): RedirectResponse
    {
        $validated = $request->validated();

        $qrCodePath = $receiver->qr_code_image_path;

        // Remove old QR code if requested
        if ($request->boolean('remove_qr_code') && $qrCodePath) {
            if (Storage::disk('public')->exists($qrCodePath)) {
                Storage::disk('public')->delete($qrCodePath);
            }
            $qrCodePath = null;
        }

        // Upload new QR code if provided
        if ($request->hasFile('qr_code')) {
            if ($qrCodePath && Storage::disk('public')->exists($qrCodePath)) {
                Storage::disk('public')->delete($qrCodePath);
            }
            $qrCodePath = $request->file('qr_code')->store('receiver_qr_codes', 'public');
        }

        $data = [
            'payment_method' => $validated['payment_method'],
            'account_name' => $validated['account_name'],
            'account_number' => $validated['account_number'],
            'account_type' => $validated['account_type'],
            'bank_name' => $validated['bank_name'] ?? null,
            'branch_name' => $validated['branch_name'] ?? null,
            'routing_number' => $validated['routing_number'] ?? null,
            'swift_code' => $validated['swift_code'] ?? null,
            'qr_code_image_path' => $qrCodePath,
            'instructions_en' => $validated['instructions_en'] ?? null,
            'instructions_bn' => $validated['instructions_bn'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'display_order' => $validated['display_order'] ?? 0,
            'financial_account_id' => $validated['financial_account_id'] ?? null,
            'event_id' => $validated['event_id'] ?? null,
            'updated_by' => auth()->id(),
        ];

        // Only user with verify permission can change verification status
        if ($request->user()->can('verify_receiver_accounts') && $request->has('is_verified')) {
            $isVerified = $request->boolean('is_verified');
            $data['is_verified'] = $isVerified;
            $data['verified_at'] = $isVerified ? ($receiver->verified_at ?? now()) : null;
            $data['verified_by'] = $isVerified ? ($receiver->verified_by ?? auth()->id()) : null;
        }

        $receiver->update($data);

        AuditLogger::log(
            'receiver_account_updated',
            "Updated receiver account '{$receiver->account_name}' (#{$receiver->id})",
            $receiver
        );

        return redirect()
            ->route('portal.receivers.index')
            ->with('success', __('portal.receivers.updated_successfully'));
    }

    /**
     * Remove the receiver account (soft delete).
     */
    public function destroy(ReceiverAccount $receiver): RedirectResponse|JsonResponse
    {
        $receiver->delete();

        AuditLogger::log(
            'receiver_account_deleted',
            "Soft-deleted receiver account '{$receiver->account_name}' (#{$receiver->id})",
            $receiver
        );

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('portal.receivers.deleted_successfully'),
            ]);
        }

        return redirect()
            ->route('portal.receivers.index')
            ->with('success', __('portal.receivers.deleted_successfully'));
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleActive(Request $request, ReceiverAccount $receiver): RedirectResponse|JsonResponse
    {
        $receiver->update([
            'is_active' => ! $receiver->is_active,
            'updated_by' => auth()->id(),
        ]);

        $statusText = $receiver->is_active ? 'activated' : 'deactivated';
        AuditLogger::log(
            'receiver_account_status_changed',
            "Receiver account #{$receiver->id} was {$statusText}",
            $receiver
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $receiver->is_active,
                'message' => $receiver->is_active
                    ? __('portal.receivers.activated_successfully')
                    : __('portal.receivers.deactivated_successfully'),
            ]);
        }

        return back()->with('success', __('portal.receivers.updated_successfully'));
    }

    /**
     * Verify receiver account.
     */
    public function verify(Request $request, ReceiverAccount $receiver): RedirectResponse|JsonResponse
    {
        $receiver->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        AuditLogger::log(
            'receiver_account_verified',
            "Verified receiver account #{$receiver->id} ('{$receiver->account_name}')",
            $receiver
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_verified' => true,
                'message' => __('portal.receivers.verified_successfully'),
            ]);
        }

        return back()->with('success', __('portal.receivers.verified_successfully'));
    }

    /**
     * Unverify receiver account.
     */
    public function unverify(Request $request, ReceiverAccount $receiver): RedirectResponse|JsonResponse
    {
        $receiver->update([
            'is_verified' => false,
            'verified_at' => null,
            'verified_by' => null,
            'updated_by' => auth()->id(),
        ]);

        AuditLogger::log(
            'receiver_account_unverified',
            "Unverified receiver account #{$receiver->id} ('{$receiver->account_name}')",
            $receiver
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_verified' => false,
                'message' => __('portal.receivers.unverified_successfully'),
            ]);
        }

        return back()->with('success', __('portal.receivers.unverified_successfully'));
    }

    // -------------------------------------------------------------
    // Formatters for DataTables
    // -------------------------------------------------------------

    protected function formatMethodColumn(ReceiverAccount $acc): string
    {
        $icon = match ($acc->payment_method) {
            ReceiverAccount::METHOD_BKASH => '<span class="badge bg-danger"><i class="bi bi-phone me-1"></i>bKash</span>',
            ReceiverAccount::METHOD_NAGAD => '<span class="badge bg-warning text-dark"><i class="bi bi-phone me-1"></i>Nagad</span>',
            ReceiverAccount::METHOD_ROCKET => '<span class="badge bg-primary"><i class="bi bi-phone me-1"></i>Rocket</span>',
            ReceiverAccount::METHOD_BANK_TRANSFER => '<span class="badge bg-dark"><i class="bi bi-bank me-1"></i>Bank</span>',
            default => '<span class="badge bg-secondary"><i class="bi bi-credit-card me-1"></i>'.e($acc->method_name).'</span>',
        };

        return '<div>'.$icon.'</div>';
    }

    protected function formatAccountNameColumn(ReceiverAccount $acc): string
    {
        $showUrl = route('portal.receivers.show', $acc);
        $name = e($acc->account_name);
        $type = e($acc->type_name);
        $eventBadge = $acc->event
            ? '<span class="badge bg-primary-subtle text-primary border ms-1 small"><i class="bi bi-calendar-event me-1"></i>'.e($acc->event->name).'</span>'
            : '<span class="badge bg-light text-muted border ms-1 small">'.e(__('portal.receivers.all_events_general')).'</span>';

        return '<div><a href="'.$showUrl.'" class="fw-semibold text-decoration-none text-dark">'.$name.'</a>'
            .'<div class="mt-1"><span class="badge bg-light text-secondary border small">'.$type.'</span>'.$eventBadge.'</div></div>';
    }

    protected function formatAccountDetailsColumn(ReceiverAccount $acc): string
    {
        $number = '<code class="fw-bold fs-6">'.e($acc->account_number).'</code>';

        if ($acc->payment_method === ReceiverAccount::METHOD_BANK_TRANSFER) {
            $bankDetails = e($acc->bank_name);
            if ($acc->branch_name) {
                $bankDetails .= ' ('.e($acc->branch_name).')';
            }
            if ($acc->routing_number) {
                $bankDetails .= '<div class="small text-muted">Routing: '.e($acc->routing_number).'</div>';
            }

            return '<div>'.$number.'<div class="small text-secondary">'.$bankDetails.'</div></div>';
        }

        return '<div>'.$number.'</div>';
    }

    protected function formatQrCodeColumn(ReceiverAccount $acc): string
    {
        if ($acc->qr_code_image_path) {
            $url = e($acc->qr_code_url);

            return '<a href="'.$url.'" target="_blank" title="'.__('portal.actions.preview').'">'
                .'<img src="'.$url.'" alt="QR" class="rounded border" style="width: 40px; height: 40px; object-fit: cover;">'
                .'</a>';
        }

        return '<span class="badge bg-light text-muted border">'.__('portal.receivers.no_qr').'</span>';
    }

    protected function formatStatusColumn(ReceiverAccount $acc): string
    {
        $activeBadge = $acc->is_active
            ? '<span class="badge bg-success me-1">'.__('common.active').'</span>'
            : '<span class="badge bg-secondary me-1">'.__('common.inactive').'</span>';

        $verifiedBadge = $acc->is_verified
            ? '<span class="badge bg-info text-dark"><i class="bi bi-patch-check-fill me-1"></i>'.__('portal.receivers.verified').'</span>'
            : '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>'.__('portal.receivers.unverified').'</span>';

        return '<div>'.$activeBadge.$verifiedBadge.'</div>';
    }

    protected function formatActionsColumn(ReceiverAccount $acc): string
    {
        $showUrl = route('portal.receivers.show', $acc);
        $editUrl = route('portal.receivers.edit', $acc);
        $user = auth()->user();

        $html = '<div class="btn-group btn-group-sm" role="group">';
        $html .= '<a href="'.$showUrl.'" class="btn btn-outline-secondary" title="'.__('portal.actions.view').'"><i class="bi bi-eye"></i></a>';

        if ($user && $user->can('manage_receiver_accounts')) {
            $html .= '<a href="'.$editUrl.'" class="btn btn-outline-primary" title="'.__('portal.actions.edit').'"><i class="bi bi-pencil"></i></a>';

            $toggleUrl = route('portal.receivers.toggle-active', $acc);
            $activeIcon = $acc->is_active ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted';
            $activeTitle = $acc->is_active ? __('portal.actions.deactivate') : __('portal.actions.activate');
            $html .= '<button type="button" class="btn btn-outline-secondary btn-toggle-active" data-url="'.$toggleUrl.'" title="'.$activeTitle.'"><i class="bi '.$activeIcon.'"></i></button>';
        }

        if ($user && $user->can('verify_receiver_accounts')) {
            if ($acc->is_verified) {
                $unverifyUrl = route('portal.receivers.unverify', $acc);
                $html .= '<button type="button" class="btn btn-outline-warning btn-verify-toggle" data-url="'.$unverifyUrl.'" title="'.__('portal.receivers.unverify').'"><i class="bi bi-patch-minus"></i></button>';
            } else {
                $verifyUrl = route('portal.receivers.verify', $acc);
                $html .= '<button type="button" class="btn btn-outline-success btn-verify-toggle" data-url="'.$verifyUrl.'" title="'.__('portal.receivers.verify').'"><i class="bi bi-patch-check"></i></button>';
            }
        }

        if ($user && $user->can('manage_receiver_accounts')) {
            $deleteUrl = route('portal.receivers.destroy', $acc);
            $html .= '<button type="button" class="btn btn-outline-danger btn-delete-receiver" data-url="'.$deleteUrl.'" title="'.__('portal.actions.delete').'"><i class="bi bi-trash"></i></button>';
        }

        $html .= '</div>';

        return $html;
    }
}
