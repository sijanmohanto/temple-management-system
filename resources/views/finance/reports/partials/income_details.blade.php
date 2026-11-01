<div class="report-details-drawer">
    <!-- Header Summary Alert -->
    <div class="d-flex justify-content-between align-items-center p-3 mb-3 bg-light rounded border">
        <div>
            <span class="text-muted small text-uppercase fw-bold">{{ __('finance.transaction_code') }}</span>
            <h5 class="mb-0 fw-bold font-monospace text-primary">
                {{ $type === 'collection' ? $record->payment_code : $record->income_number }}
            </h5>
        </div>
        <div class="text-end">
            <span class="text-muted small text-uppercase fw-bold d-block">{{ __('common.amount') }}</span>
            <span class="fs-4 fw-bold text-success">৳{{ number_format((float)$record->amount, 2) }}</span>
        </div>
    </div>

    <!-- Section A: Transaction Info -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2 px-3 fw-bold text-dark border-bottom">
            <i class="bi bi-info-circle text-primary me-2"></i>{{ __('finance.transaction_info') }}
        </div>
        <div class="card-body p-3">
            <div class="row g-2 small">
                <div class="col-sm-6">
                    <span class="text-muted d-block">{{ __('finance.entry_date') }}:</span>
                    <strong class="font-monospace">
                        {{ ($type === 'collection' ? $record->payment_date : $record->income_date)?->format('Y-m-d') ?? '-' }}
                    </strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted d-block">{{ __('finance.income_type') }}:</span>
                    @if($type === 'collection')
                        <span class="badge bg-secondary-subtle text-secondary border">
                            {{ match($record->collection_type) {
                                'event_pledge' => __('finance.event_pledge'),
                                'event_donation' => __('finance.event_donation'),
                                'monthly_contribution' => __('finance.monthly_contribution'),
                                'general_donation' => __('finance.general_donation'),
                                default => ucwords(str_replace('_', ' ', $record->collection_type))
                            } }}
                        </span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border">{{ __('finance.misc_income') }}</span>
                    @endif
                </div>
                <div class="col-sm-6">
                    <span class="text-muted d-block">{{ __('finance.payment_method') }}:</span>
                    <strong class="text-capitalize">
                        {{ __('donations.method_' . $record->payment_method) !== 'donations.method_' . $record->payment_method 
                            ? __('donations.method_' . $record->payment_method) 
                            : (__('finance.method_' . $record->payment_method) !== 'finance.method_' . $record->payment_method ? __('finance.method_' . $record->payment_method) : ucfirst(str_replace('_', ' ', $record->payment_method))) }}
                    </strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted d-block">{{ __('finance.reference_number') }}:</span>
                    <span class="font-monospace">
                        {{ ($type === 'collection' ? $record->transaction_reference : $record->reference_number) ?: '-' }}
                    </span>
                </div>
                @if($type === 'collection' && $record->bank_name)
                    <div class="col-sm-6">
                        <span class="text-muted d-block">{{ __('donations.bank_name') }}:</span>
                        <strong>{{ $record->bank_name }}</strong>
                    </div>
                @endif
                @if($type === 'collection' && $record->cheque_number)
                    <div class="col-sm-6">
                        <span class="text-muted d-block">{{ __('donations.cheque_number') }}:</span>
                        <strong class="font-monospace">{{ $record->cheque_number }}</strong>
                    </div>
                @endif
                @if($type === 'manual' && $record->incomeAccount)
                    <div class="col-12">
                        <span class="text-muted d-block">{{ __('finance.credit_account') }}:</span>
                        <strong>{{ $record->incomeAccount->display_name }}</strong>
                    </div>
                @endif
                @if($type === 'manual' && $record->depositAccount)
                    <div class="col-12">
                        <span class="text-muted d-block">{{ __('finance.debit_account') }}:</span>
                        <strong>{{ $record->depositAccount->display_name }}</strong>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Section B: Donor Info -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2 px-3 fw-bold text-dark border-bottom">
            <i class="bi bi-person text-primary me-2"></i>{{ __('finance.donor_info') }}
        </div>
        <div class="card-body p-3">
            <div class="row g-2 small">
                <div class="col-sm-6">
                    <span class="text-muted d-block">{{ __('finance.donor_type') }}:</span>
                    <span class="badge {{ $is_committee_member ? 'bg-primary-subtle text-primary border' : 'bg-secondary-subtle text-secondary border' }}">
                        {{ $donor_type_label }}
                    </span>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted d-block">{{ __('common.name') }}:</span>
                    <strong class="fs-6">{{ $donor_display_name }}</strong>
                </div>
                @if($type === 'collection' && $record->payerPerson)
                    <div class="col-sm-6">
                        <span class="text-muted d-block">{{ __('people.person_code') }}:</span>
                        <span class="font-monospace fw-bold">{{ $record->payerPerson->person_code }}</span>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted d-block">{{ __('people.phone') }}:</span>
                        <span>{{ $record->payerPerson->phone ?: '-' }}</span>
                    </div>
                    @if($is_committee_member)
                        @php
                            $activeMem = $record->payerPerson->committeeMemberships->where('status', 'active')->first();
                        @endphp
                        @if($activeMem)
                            <div class="col-12">
                                <span class="text-muted d-block">{{ __('committees.designation') }}:</span>
                                <span class="badge bg-warning-subtle text-dark border">
                                    {{ $activeMem->designation_en }} ({{ $activeMem->committee?->name_en ?? 'Executive Committee' }})
                                </span>
                            </div>
                        @endif
                    @endif
                @elseif($type === 'collection' && $record->payerFamily)
                    <div class="col-sm-6">
                        <span class="text-muted d-block">{{ __('families.family_code') }}:</span>
                        <span class="font-monospace fw-bold">{{ $record->payerFamily->family_code }}</span>
                    </div>
                @else
                    <div class="col-sm-6">
                        <span class="text-muted d-block">{{ __('common.phone') }}:</span>
                        <span>{{ $record->payer_phone_override ?: '-' }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Section C: Event & Fund Allocation -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2 px-3 fw-bold text-dark border-bottom">
            <i class="bi bi-calendar-event text-primary me-2"></i>{{ __('finance.event_fund_allocation') }}
        </div>
        <div class="card-body p-3">
            <div class="mb-3 small">
                <span class="text-muted d-block">{{ __('events.event') }}:</span>
                @if($record->event)
                    <span class="fw-bold">{{ $record->event->title_en }}</span>
                    @if($record->event->category)
                        <span class="badge bg-light text-muted border ms-1">{{ $record->event->category->name_en }}</span>
                    @endif
                @else
                    <span class="badge bg-light text-dark border">{{ __('finance.general_temple_fund') }}</span>
                @endif
            </div>

            @if($type === 'collection' && isset($allocations) && $allocations->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 align-middle small">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('finance.target_type') }}</th>
                                <th>{{ __('common.notes') }}</th>
                                <th class="text-end">{{ __('common.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allocations as $allocation)
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ ucwords(str_replace('_', ' ', $allocation->target_type)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($allocation->pledge)
                                            <span class="font-monospace small">{{ $allocation->pledge->pledge_code }}</span>
                                        @elseif($allocation->monthlyContribution)
                                            <span class="small">{{ $allocation->monthlyContribution->contribution_month }}</span>
                                        @else
                                            <span class="text-muted small">{{ $allocation->notes ?: '-' }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold font-monospace">
                                        ৳{{ number_format((float)$allocation->amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Section D: Verification & Audit Info -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2 px-3 fw-bold text-dark border-bottom">
            <i class="bi bi-shield-check text-primary me-2"></i>{{ __('finance.verification_audit_info') }}
        </div>
        <div class="card-body p-3">
            <div class="row g-2 small">
                <div class="col-sm-6">
                    <span class="text-muted d-block">{{ __('common.status') }}:</span>
                    @if($type === 'collection')
                        @if($record->status === 'verified')
                            <span class="badge bg-success">{{ __('finance.verified') }}</span>
                        @elseif($record->status === 'pending_verification')
                            <span class="badge bg-warning text-dark">{{ __('finance.pending') }}</span>
                        @elseif($record->status === 'rejected')
                            <span class="badge bg-danger">{{ __('finance.rejected') }}</span>
                        @elseif($record->status === 'reversed')
                            <span class="badge bg-secondary">{{ __('finance.reversed') }}</span>
                        @endif
                    @else
                        @if($record->status === 'posted')
                            <span class="badge bg-success">{{ __('finance.posted') }}</span>
                        @elseif($record->status === 'reversed')
                            <span class="badge bg-secondary">{{ __('finance.reversed') }}</span>
                        @endif
                    @endif
                </div>

                <div class="col-sm-6">
                    <span class="text-muted d-block">{{ __('finance.submitted_at') }}:</span>
                    <span>
                        {{ $record->submitter?->name ?? $record->creator?->name ?? '-' }}
                        <div class="text-muted small">{{ $record->created_at?->format('d M Y, h:i A') }}</div>
                    </span>
                </div>

                @if($type === 'collection' && $record->verified_by)
                    <div class="col-12">
                        <span class="text-muted d-block">{{ __('finance.verified_at') }}:</span>
                        <span>{{ $record->verifier?->name }} on {{ $record->verified_at?->format('d M Y, h:i A') }}</span>
                        @if($record->verification_note)
                            <div class="fst-italic text-muted small mt-1">"{{ $record->verification_note }}"</div>
                        @endif
                    </div>
                @endif

                @if($type === 'collection' && $record->rejected_by)
                    <div class="col-12">
                        <span class="text-muted d-block text-danger">{{ __('finance.rejected_at') }}:</span>
                        <span>{{ $record->rejecter?->name }} on {{ $record->rejected_at?->format('d M Y, h:i A') }}</span>
                        @if($record->rejection_reason)
                            <div class="alert alert-danger py-1 px-2 small mt-1 mb-0">{{ $record->rejection_reason }}</div>
                        @endif
                    </div>
                @endif

                @if($type === 'collection' && $record->reversed_by)
                    <div class="col-12">
                        <span class="text-muted d-block text-secondary">{{ __('finance.reversed_at') }}:</span>
                        <span>{{ $record->reverser?->name }} on {{ $record->reversed_at?->format('d M Y, h:i A') }}</span>
                        @if($record->reversal_reason)
                            <div class="alert alert-secondary py-1 px-2 small mt-1 mb-0">{{ $record->reversal_reason }}</div>
                        @endif
                    </div>
                @endif

                @if($record->finance_post_reference || $record->journal_entry_id)
                    <div class="col-12">
                        <span class="text-muted d-block">{{ __('finance.journal_voucher') }}:</span>
                        <span class="font-monospace fw-bold text-success">
                            {{ $record->finance_post_reference ?: $record->journalEntry?->journal_number }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Section E: Related Records & Actions -->
    <div class="card border-0 shadow-sm mb-2">
        <div class="card-header bg-white py-2 px-3 fw-bold text-dark border-bottom">
            <i class="bi bi-link-45deg text-primary me-2"></i>{{ __('finance.related_records_actions') }}
        </div>
        <div class="card-body p-3">
            @if(isset($receipt) && $receipt)
                <div class="p-2 bg-light rounded border mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="small text-muted">{{ __('donations.receipt_number') }}:</span>
                            <div class="font-monospace fw-bold text-primary">{{ $receipt->receipt_number }}</div>
                            <small class="text-muted">{{ $receipt->issued_at?->format('d M Y, h:i A') }}</small>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('receipts.print', $receipt->id) }}" target="_blank" class="btn btn-outline-secondary">
                                <i class="bi bi-printer me-1"></i>{{ __('finance.print_report') }}
                            </a>
                            <a href="{{ route('receipts.download', $receipt->id) }}" class="btn btn-outline-primary">
                                <i class="bi bi-download me-1"></i>{{ __('finance.download_receipt') }}
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <div class="d-flex gap-2 flex-wrap">
                @if($record->journalEntry)
                    <a href="{{ route('finance.journals.show', $record->journalEntry->id) }}" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-journal-text me-1"></i>{{ __('finance.journal_voucher') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
