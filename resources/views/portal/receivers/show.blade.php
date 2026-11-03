@extends('layouts.app')

@section('title', $receiver->account_name)

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('nav.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('portal.receivers.index') }}">{{ __('portal.receiver_accounts') }}</a></li>
                    <li class="breadcrumb-item active">{{ $receiver->account_name }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">{{ $receiver->account_name }}</h1>
            <p class="text-muted small mb-0">{{ $receiver->method_name }} &bull; {{ $receiver->account_number }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('portal.receivers.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> {{ __('portal.actions.back_to_list') }}
            </a>
            @if(auth()->user()->can('manage_receiver_accounts'))
                <a href="{{ route('portal.receivers.edit', $receiver) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-1"></i> {{ __('portal.actions.edit') }}
                </a>
            @endif
        </div>
    </div>

    <div class="row g-4">
        {{-- Main Account Info Card --}}
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">{{ __('portal.receivers.account_details') }}</h5>
                    <div class="d-flex gap-1">
                        <span class="badge {{ $receiver->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $receiver->is_active ? __('common.active') : __('common.inactive') }}
                        </span>
                        <span class="badge {{ $receiver->is_verified ? 'bg-info text-dark' : 'bg-warning text-dark' }}">
                            {{ $receiver->is_verified ? __('portal.receivers.verified') : __('portal.receivers.unverified') }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="text-muted small">{{ __('portal.receivers.payment_method') }}</label>
                            <div class="fw-semibold fs-6">
                                <span class="badge bg-dark">{{ $receiver->method_name }}</span>
                                <span class="badge bg-light text-dark border ms-1">{{ $receiver->type_name }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">{{ __('portal.receivers.account_number') }}</label>
                            <div class="fs-5 fw-bold text-primary">
                                <code>{{ $receiver->account_number }}</code>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">{{ __('portal.receivers.account_name') }}</label>
                            <div class="fw-semibold fs-6">{{ $receiver->account_name }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">{{ __('portal.receivers.display_order') }}</label>
                            <div><span class="badge bg-light text-dark border">#{{ $receiver->display_order }}</span></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">{{ __('portal.receivers.associated_event') }}</label>
                            <div>
                                @if($receiver->event)
                                    <a href="{{ route('portal.events.show', $receiver->event) }}" class="fw-semibold text-decoration-none">
                                        <i class="bi bi-calendar-event me-1"></i> {{ $receiver->event->name }} ({{ $receiver->event->event_year }})
                                    </a>
                                @else
                                    <span class="badge bg-light text-muted border">{{ __('portal.receivers.all_events_general') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($receiver->payment_method === \App\Models\ReceiverAccount::METHOD_BANK_TRANSFER)
                        <hr>
                        <h6 class="fw-bold mb-3 text-secondary">
                            <i class="bi bi-bank me-1"></i> {{ __('portal.payment_methods.bank_transfer') }}
                        </h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="text-muted small">{{ __('portal.receivers.bank_name') }}</label>
                                <div class="fw-semibold">{{ $receiver->bank_name ?: '—' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">{{ __('portal.receivers.branch_name') }}</label>
                                <div class="fw-semibold">{{ $receiver->branch_name ?: '—' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">{{ __('portal.receivers.routing_number') }}</label>
                                <div><code>{{ $receiver->routing_number ?: '—' }}</code></div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">{{ __('portal.receivers.swift_code') }}</label>
                                <div><code>{{ $receiver->swift_code ?: '—' }}</code></div>
                            </div>
                        </div>
                    @endif

                    @if($receiver->instructions_en || $receiver->instructions_bn)
                        <hr>
                        <h6 class="fw-bold mb-3 text-secondary">
                            <i class="bi bi-info-circle me-1"></i> {{ __('portal.receivers.instructions') }}
                        </h6>
                        <div class="row g-3">
                            @if($receiver->instructions_en)
                                <div class="col-md-6">
                                    <label class="text-muted small">{{ __('portal.receivers.instructions_en') }}</label>
                                    <p class="text-secondary mb-0">{{ $receiver->instructions_en }}</p>
                                </div>
                            @endif
                            @if($receiver->instructions_bn)
                                <div class="col-md-6">
                                    <label class="text-muted small font-bengali">{{ __('portal.receivers.instructions_bn') }}</label>
                                    <p class="text-secondary font-bengali mb-0">{{ $receiver->instructions_bn }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Linked Accounting Ledger Card --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-semibold">
                        <i class="bi bi-journal-check me-1 text-success"></i> {{ __('portal.receivers.financial_account') }}
                    </h6>
                </div>
                <div class="card-body">
                    @if($receiver->financialAccount)
                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border">
                            <div>
                                <div class="fw-bold fs-6">{{ $receiver->financialAccount->name }}</div>
                                <div class="small text-muted">
                                    <code>{{ $receiver->financialAccount->account_code }}</code> &bull;
                                    <span class="badge bg-secondary">{{ strtoupper($receiver->financialAccount->account_type) }}</span>
                                </div>
                            </div>
                            <div>
                                <a href="{{ route('finance.accounts.show', $receiver->financialAccount) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> {{ __('portal.actions.view') }}
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="text-muted small">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            Not linked to a double-entry ledger account. Edit this receiver to map it to an active Cash or Bank account.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar: QR Code & Verification Control --}}
        <div class="col-lg-4">
            {{-- QR Code Preview Card --}}
            <div class="card shadow-sm border-0 mb-4 text-center">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-semibold">{{ __('portal.receivers.qr_code') }}</h6>
                </div>
                <div class="card-body p-4">
                    @if($receiver->qr_code_image_path)
                        <img src="{{ $receiver->qr_code_url }}" alt="QR Code" class="img-fluid rounded border shadow-sm mb-3" style="max-height: 200px;">
                        <div>
                            <a href="{{ $receiver->qr_code_url }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrows-fullscreen me-1"></i> {{ __('portal.actions.preview') }}
                            </a>
                        </div>
                    @else
                        <div class="py-4 text-muted">
                            <i class="bi bi-qr-code text-secondary fs-1 d-block mb-2"></i>
                            <div class="small">{{ __('portal.receivers.no_qr') }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Controls & Actions --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-semibold">{{ __('common.actions') }}</h6>
                </div>
                <div class="card-body d-grid gap-2">
                    @if(auth()->user()->can('manage_receiver_accounts'))
                        <form action="{{ route('portal.receivers.toggle-active', $receiver) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn {{ $receiver->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} w-100">
                                <i class="bi {{ $receiver->is_active ? 'bi-toggle-on' : 'bi-toggle-off' }} me-1"></i>
                                {{ $receiver->is_active ? __('portal.actions.deactivate') : __('portal.actions.activate') }}
                            </button>
                        </form>
                    @endif

                    @if(auth()->user()->can('verify_receiver_accounts'))
                        @if($receiver->is_verified)
                            <form action="{{ route('portal.receivers.unverify', $receiver) }}" method="POST" onsubmit="return confirm('{{ __('portal.receivers.unverify_confirm') }}')">
                                @csrf
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="bi bi-patch-minus me-1"></i> {{ __('portal.receivers.unverify') }}
                                </button>
                            </form>
                        @else
                            <form action="{{ route('portal.receivers.verify', $receiver) }}" method="POST" onsubmit="return confirm('{{ __('portal.receivers.verify_confirm') }}')">
                                @csrf
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-patch-check me-1"></i> {{ __('portal.receivers.verify') }}
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Audit Information --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-semibold">{{ __('common.audit_information') }}</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">{{ __('portal.documents.uploaded_by') }}:</span>
                            <span class="fw-semibold">{{ $receiver->creator?->name ?? '—' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">{{ __('common.created_at') }}:</span>
                            <span class="fw-semibold">{{ $receiver->created_at->format('d M Y, h:i A') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">{{ __('portal.receivers.verified') }}:</span>
                            <span class="fw-semibold">{{ $receiver->is_verified ? 'Yes' : 'No' }}</span>
                        </li>
                        @if($receiver->is_verified)
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">{{ __('portal.published_by') }}:</span>
                                <span class="fw-semibold">{{ $receiver->verifier?->name ?? '—' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">{{ __('portal.published_at') }}:</span>
                                <span class="fw-semibold">{{ $receiver->verified_at ? $receiver->verified_at->format('d M Y, h:i A') : '—' }}</span>
                            </li>
                        @endif
                    </ul>
                </div>
                @if(auth()->user()->can('manage_receiver_accounts'))
                    <div class="card-footer bg-white border-top p-3">
                        <form action="{{ route('portal.receivers.destroy', $receiver) }}" method="POST" onsubmit="return confirm('{{ __('portal.receivers.delete_confirm') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100 btn-sm">
                                <i class="bi bi-trash me-1"></i> {{ __('portal.actions.delete') }}
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
