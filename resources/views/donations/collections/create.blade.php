@extends('layouts.app')

@section('title', __('donations.submit_payment'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('collections.index') }}" class="text-decoration-none">{{ __('donations.collections') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('donations.submit_payment') }}</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold mb-0 text-dark">{{ __('donations.submit_payment') }}</h1>
        </div>

        <div>
            <a href="{{ route('collections.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4 p-md-5">
            <form action="{{ route('collections.store') }}" method="POST" id="submitPaymentForm">
                @csrf

                @if($selectedPledge)
                    <!-- LOCKED PLEDGE VIEW -->
                    <input type="hidden" name="collection_type" value="event_pledge">
                    <input type="hidden" name="pledge_id" value="{{ $selectedPledge->id }}" id="lockedPledgeId">
                    <input type="hidden" name="event_id" value="{{ $selectedPledge->event_id }}">
                    @if($selectedPledge->person_id)
                        <input type="hidden" name="payer_person_id" value="{{ $selectedPledge->person_id }}">
                    @elseif($selectedPledge->family_id)
                        <input type="hidden" name="payer_family_id" value="{{ $selectedPledge->family_id }}">
                    @endif

                    <div class="card border-primary border-opacity-25 bg-primary bg-opacity-10 rounded-4 p-4 mb-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                            <div>
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <span class="badge bg-primary px-3 py-1 font-monospace fs-6">
                                        <i class="bi bi-lock-fill me-1"></i> {{ $selectedPledge->pledge_code }}
                                    </span>
                                    <span class="badge bg-white text-dark border">
                                        {{ __('donations.type_event_pledge') }}
                                    </span>
                                    @if($selectedPledge->event)
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-calendar-event me-1"></i>{{ $selectedPledge->event->name }}
                                        </span>
                                    @endif
                                </div>
                                <h4 class="fw-bold text-dark mb-1">{{ $selectedPledge->owner_name }}</h4>
                                @if($selectedPledge->person)
                                    <div class="small text-muted">
                                        <span class="font-monospace text-dark fw-bold">{{ $selectedPledge->person->person_code }}</span>
                                        @if($selectedPledge->person->phone) &bull; <i class="bi bi-telephone me-1"></i>{{ $selectedPledge->person->phone }} @endif
                                        @if($selectedPledge->person->address) &bull; <i class="bi bi-geo-alt me-1"></i>{{ $selectedPledge->person->address }} @endif
                                    </div>
                                @elseif($selectedPledge->family)
                                    <div class="small text-muted">
                                        <span class="font-monospace text-dark fw-bold">{{ $selectedPledge->family->family_code }}</span>
                                        @if($selectedPledge->family->primaryContact) &bull; Primary Contact: {{ $selectedPledge->family->primaryContact->name_en }} ({{ $selectedPledge->family->primaryContact->phone }}) @endif
                                    </div>
                                @endif
                            </div>
                            <div class="text-md-end">
                                <div class="small text-muted">{{ __('donations.outstanding_amount') }}</div>
                                <div class="fs-4 fw-bold text-danger" id="targetOutstandingDisplay" data-outstanding="{{ $selectedPledge->outstanding_amount }}">
                                    ৳{{ number_format($selectedPledge->outstanding_amount, 2) }}
                                </div>
                                <div class="small text-muted">({{ __('donations.pledged_amount') }}: ৳{{ number_format((float) $selectedPledge->pledged_amount, 2) }})</div>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-top border-primary border-opacity-25 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <small class="text-muted"><i class="bi bi-shield-lock text-primary me-1"></i>{{ __('donations.locked_pledge_payer_notice') }}</small>
                            <a href="{{ route('collections.create') }}" class="btn btn-sm btn-outline-secondary rounded-pill bg-white">
                                <i class="bi bi-unlock me-1"></i>{{ __('donations.switch_to_open_payment') }}
                            </a>
                        </div>
                    </div>

                @elseif($selectedMonthlyContribution)
                    <!-- LOCKED MONTHLY CONTRIBUTION VIEW -->
                    <input type="hidden" name="collection_type" value="monthly_contribution">
                    <input type="hidden" name="monthly_contribution_id" value="{{ $selectedMonthlyContribution->id }}" id="lockedContribId">
                    <input type="hidden" name="payer_person_id" value="{{ $selectedMonthlyContribution->membership?->person_id }}">

                    <div class="card border-primary border-opacity-25 bg-primary bg-opacity-10 rounded-4 p-4 mb-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                            <div>
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <span class="badge bg-primary px-3 py-1 font-monospace fs-6">
                                        <i class="bi bi-lock-fill me-1"></i> {{ $selectedMonthlyContribution->contribution_code }}
                                    </span>
                                    <span class="badge bg-white text-dark border">
                                        {{ __('donations.type_monthly_contribution') }}
                                    </span>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-calendar-check me-1"></i>{{ $selectedMonthlyContribution->month_name }}
                                    </span>
                                </div>
                                <h4 class="fw-bold text-dark mb-1">{{ $selectedMonthlyContribution->member_name }}</h4>
                                <div class="small text-muted">
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border me-1">{{ $selectedMonthlyContribution->membership?->designation ?? 'Member' }}</span>
                                    <span class="text-muted">{{ $selectedMonthlyContribution->membership?->committee?->name }}</span>
                                    @if($selectedMonthlyContribution->membership?->person?->phone)
                                        &bull; <i class="bi bi-telephone me-1"></i>{{ $selectedMonthlyContribution->membership->person->phone }}
                                    @endif
                                </div>
                            </div>
                            <div class="text-md-end">
                                <div class="small text-muted">{{ __('donations.outstanding_amount') }}</div>
                                <div class="fs-4 fw-bold text-danger" id="targetOutstandingDisplay" data-outstanding="{{ $selectedMonthlyContribution->outstanding_amount }}">
                                    ৳{{ number_format($selectedMonthlyContribution->outstanding_amount, 2) }}
                                </div>
                                <div class="small text-muted">({{ __('donations.expected_amount') }}: ৳{{ number_format((float) $selectedMonthlyContribution->expected_amount, 2) }})</div>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-top border-primary border-opacity-25 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <small class="text-muted"><i class="bi bi-shield-lock text-primary me-1"></i>{{ __('donations.locked_member_payer_notice') }}</small>
                            <a href="{{ route('collections.create') }}" class="btn btn-sm btn-outline-secondary rounded-pill bg-white">
                                <i class="bi bi-unlock me-1"></i>{{ __('donations.switch_to_open_payment') }}
                            </a>
                        </div>
                    </div>

                @else
                    <!-- OPEN PAYMENT FLOW (Fresh from Collections) -->
                    <!-- Context Selection -->
                    <div class="border-bottom pb-4 mb-4">
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-tag me-2 text-primary"></i>{{ __('donations.type') }}</h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold required">{{ __('donations.type') }}</label>
                                <select name="collection_type" id="collectionTypeSelect" class="form-select @error('collection_type') is-invalid @enderror" required>
                                    <option value="event_pledge" {{ old('collection_type') === 'event_pledge' ? 'selected' : '' }}>
                                        {{ __('donations.type_event_pledge') }}
                                    </option>
                                    <option value="event_donation" {{ old('collection_type') === 'event_donation' ? 'selected' : '' }}>
                                        {{ __('donations.type_event_donation') }}
                                    </option>
                                    <option value="monthly_contribution" {{ old('collection_type') === 'monthly_contribution' ? 'selected' : '' }}>
                                        {{ __('donations.type_monthly_contribution') }}
                                    </option>
                                    <option value="general_donation" {{ old('collection_type', 'general_donation') === 'general_donation' ? 'selected' : '' }}>
                                        {{ __('donations.type_general_donation') }}
                                    </option>
                                </select>
                            </div>

                            <!-- Specific Pledge Selector (for event_pledge) -->
                            <div class="col-md-6" id="pledgeFieldContainer" style="display: none;">
                                <label class="form-label fw-semibold required">{{ __('donations.pledges') }}</label>
                                <select name="pledge_id" id="pledgeSelect" class="form-select select2" data-placeholder="Select Active Pledge">
                                    <option value="">Select Pledge</option>
                                    @foreach(\App\Models\Pledge::with(['event', 'person', 'family'])->whereIn('status', ['unpaid', 'partial'])->latest()->get() as $plg)
                                        <option value="{{ $plg->id }}" data-outstanding="{{ $plg->outstanding_amount }}" data-event="{{ $plg->event_id }}" data-person="{{ $plg->person_id }}" data-family="{{ $plg->family_id }}" {{ old('pledge_id') == $plg->id ? 'selected' : '' }}>
                                            {{ $plg->pledge_code }} ({{ $plg->owner_name }}) - Due: ৳{{ number_format($plg->outstanding_amount, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Specific Monthly Contribution Selector (for monthly_contribution) -->
                            <div class="col-md-6" id="contributionFieldContainer" style="display: none;">
                                <label class="form-label fw-semibold required">{{ __('donations.committee_contributions') }}</label>
                                <select name="monthly_contribution_id" id="contributionSelect" class="form-select select2" data-placeholder="Select Unpaid Committee Month">
                                    <option value="">Select Obligation</option>
                                    @foreach(\App\Models\MonthlyCommitteeContribution::with('membership.person')->whereIn('status', ['unpaid', 'partial'])->latest('contribution_month')->get() as $mc)
                                        <option value="{{ $mc->id }}" data-outstanding="{{ $mc->outstanding_amount }}" data-person="{{ $mc->membership?->person_id }}" {{ old('monthly_contribution_id') == $mc->id ? 'selected' : '' }}>
                                            {{ $mc->month_name }} - {{ $mc->member_name }} (Due: ৳{{ number_format($mc->outstanding_amount, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Event Selector (for voluntary event donation or pledge) -->
                            <div class="col-md-6" id="eventFieldContainer" style="display: none;">
                                <label class="form-label fw-semibold">{{ __('events.event') }}</label>
                                <select name="event_id" id="eventSelect" class="form-select select2" data-placeholder="Select Event">
                                    <option value="">Select Event (Optional for general)</option>
                                    @foreach($events as $event)
                                        <option value="{{ $event->id }}" {{ old('event_id', $selectedEventId) == $event->id ? 'selected' : '' }}>
                                            {{ $event->name }} ({{ $event->event_year }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Payer Information -->
                    <div class="border-bottom pb-4 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-person me-2 text-primary"></i>{{ __('donations.payer') }}</h5>
                            <div id="payerObligationsBadge" style="display: none;"></div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex flex-wrap gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payer_source" id="sourcePerson" value="person" {{ old('payer_name_override') ? '' : (old('payer_family_id') ? '' : 'checked') }}>
                                    <label class="form-check-label fw-semibold" for="sourcePerson">
                                        <i class="bi bi-person me-1"></i> {{ __('donations.individual_devotee') }}
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payer_source" id="sourceFamily" value="family" {{ old('payer_family_id') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="sourceFamily">
                                        <i class="bi bi-house-heart me-1"></i> {{ __('donations.family_unit') }}
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payer_source" id="sourceWalkIn" value="walkin" {{ old('payer_name_override') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="sourceWalkIn">
                                        <i class="bi bi-person-walking me-1"></i> {{ __('donations.guest_or_visitor') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3" id="personPayerRow">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">{{ __('people.person') }}</label>
                                <select name="payer_person_id" id="payerPersonSelect" class="form-select select2" data-ajax--url="{{ route('people.search') }}" data-placeholder="Search devotee by name, code or phone...">
                                    @if(old('payer_person_id') || $selectedPerson)
                                        @php
                                            $per = $selectedPerson ?? \App\Models\Person::find(old('payer_person_id'));
                                        @endphp
                                        @if($per)
                                            <option value="{{ $per->id }}" selected>{{ $per->name_en }} ({{ $per->person_code }}) - {{ $per->phone }}</option>
                                        @endif
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div class="row g-3" id="familyPayerRow" style="display: none;">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">{{ __('families.family') }}</label>
                                <select name="payer_family_id" id="payerFamilySelect" class="form-select select2" data-ajax--url="{{ route('families.search') }}" data-placeholder="Search family by name or code...">
                                    @if(old('payer_family_id') || $selectedFamily)
                                        @php
                                            $fam = $selectedFamily ?? \App\Models\Family::find(old('payer_family_id'));
                                        @endphp
                                        @if($fam)
                                            <option value="{{ $fam->id }}" selected>{{ $fam->family_name }} ({{ $fam->family_code }})</option>
                                        @endif
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div class="row g-3" id="walkInPayerRow" style="display: none;">
                            <div class="col-12">
                                <div class="alert alert-light border small text-muted mb-0 py-2">
                                    <i class="bi bi-info-circle text-primary me-1"></i> {{ __('donations.guest_donor_notice') }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold required">{{ __('donations.payer_name') }}</label>
                                <input type="text" name="payer_name_override" id="payerNameOverride" class="form-control" value="{{ old('payer_name_override') }}" placeholder="Donor full name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('donations.payer_phone') }}</label>
                                <input type="text" name="payer_phone_override" class="form-control" value="{{ old('payer_phone_override') }}" placeholder="Contact phone number (optional)">
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Payment Details -->
                <div class="border-bottom pb-4 mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-credit-card me-2 text-primary"></i>{{ __('donations.payment_details') }}</h5>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold required">{{ __('donations.amount') }} (৳)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">৳</span>
                                <input type="number" step="0.01" min="1" name="amount" id="paymentAmountInput" class="form-control form-control-lg fw-bold @error('amount') is-invalid @enderror" value="{{ old('amount', $selectedPledge ? $selectedPledge->outstanding_amount : ($selectedMonthlyContribution ? $selectedMonthlyContribution->outstanding_amount : '')) }}" required placeholder="0.00">
                            </div>
                            @error('amount')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <div id="excessCalculationNotice" class="mt-2" style="display: none;"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold required">{{ __('donations.payment_date') }}</label>
                            <input type="date" name="payment_date" class="form-control form-control-lg @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                            @error('payment_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold required">{{ __('donations.payment_method') }}</label>
                            <select name="payment_method" id="paymentMethodSelect" class="form-select form-select-lg @error('payment_method') is-invalid @enderror" required>
                                <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>{{ __('donations.method_cash') }}</option>
                                <option value="bkash" {{ old('payment_method') === 'bkash' ? 'selected' : '' }}>{{ __('donations.method_bkash') }}</option>
                                <option value="nagad" {{ old('payment_method') === 'nagad' ? 'selected' : '' }}>{{ __('donations.method_nagad') }}</option>
                                <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>{{ __('donations.method_bank_transfer') }}</option>
                                <option value="cheque" {{ old('payment_method') === 'cheque' ? 'selected' : '' }}>{{ __('donations.method_cheque') }}</option>
                                <option value="rocket" {{ old('payment_method') === 'rocket' ? 'selected' : '' }}>{{ __('donations.method_rocket') }}</option>
                                <option value="other" {{ old('payment_method') === 'other' ? 'selected' : '' }}>{{ __('donations.method_other') }}</option>
                            </select>
                        </div>

                        <!-- Excess Contribution Destination (Revealed when amount exceeds pledge) -->
                        <div class="col-12" id="excessDestinationContainer" style="display: none;">
                            <div class="card border-warning border-opacity-50 bg-warning bg-opacity-10 rounded-4 p-4">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-gift-fill text-warning-emphasis fs-4"></i>
                                    <h6 class="fw-bold text-dark mb-0">{{ __('donations.excess_destination') }}</h6>
                                    <span class="badge bg-warning text-dark border ms-auto px-3 py-1 font-monospace" id="excessAmountDisplayBadge">+৳0.00</span>
                                </div>
                                <p class="small text-muted mb-3">{{ __('donations.excess_destination_help') }}</p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check p-3 bg-white border rounded-3 h-100 shadow-sm">
                                            <input class="form-check-input ms-0 me-2" type="radio" name="excess_target_type" id="excessTargetEvent" value="voluntary_event" {{ old('excess_target_type', 'voluntary_event') === 'voluntary_event' ? 'checked' : '' }}>
                                            <label class="form-check-label w-100" for="excessTargetEvent">
                                                <div class="fw-bold text-primary mb-1"><i class="bi bi-calendar-event me-1"></i>{{ __('donations.excess_destination_event') }}</div>
                                                <div class="small text-muted">{{ __('donations.excess_destination_event_desc') }}</div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check p-3 bg-white border rounded-3 h-100 shadow-sm">
                                            <input class="form-check-input ms-0 me-2" type="radio" name="excess_target_type" id="excessTargetGeneral" value="voluntary_general" {{ old('excess_target_type') === 'voluntary_general' ? 'checked' : '' }}>
                                            <label class="form-check-label w-100" for="excessTargetGeneral">
                                                <div class="fw-bold text-success mb-1"><i class="bi bi-bank me-1"></i>{{ __('donations.excess_destination_general') }}</div>
                                                <div class="small text-muted">{{ __('donations.excess_destination_general_desc') }}</div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reference Details for Non-Cash -->
                        <div class="col-md-4" id="trxRefContainer" style="display: none;">
                            <label class="form-label fw-semibold">{{ __('donations.transaction_reference') }}</label>
                            <input type="text" name="transaction_reference" class="form-control" value="{{ old('transaction_reference') }}" placeholder="e.g. bKash TrxID / Deposit Slip #">
                        </div>

                        <div class="col-md-4" id="bankNameContainer" style="display: none;">
                            <label class="form-label fw-semibold">{{ __('donations.bank_name') }}</label>
                            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}" placeholder="Bank & Branch Name">
                        </div>

                        <div class="col-md-4" id="chequeContainer" style="display: none;">
                            <label class="form-label fw-semibold">{{ __('donations.cheque_number') }}</label>
                            <input type="text" name="cheque_number" class="form-control" value="{{ old('cheque_number') }}" placeholder="Cheque #">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">{{ __('donations.payment_notes') }}</label>
                            <textarea name="payment_notes" rows="2" class="form-control" placeholder="Optional comments, voucher notes, or counter remarks...">{{ old('payment_notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Separation of Duties Notice -->
                <div class="alert alert-secondary border-0 rounded-3 mb-4 p-3 d-flex align-items-center gap-3">
                    <i class="bi bi-shield-check fs-3 text-primary"></i>
                    <div>
                        <div class="fw-bold text-dark">{{ __('donations.separation_of_duties_notice') }}</div>
                        <div class="small text-muted">{{ __('donations.unverified_submission_notice') }}</div>
                    </div>
                </div>

                <!-- Submit Action Buttons -->
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('collections.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                        {{ __('common.cancel') }}
                    </a>
                    <button type="submit" id="savePaymentBtn" class="btn btn-primary rounded-pill px-5 shadow-sm" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                        <i class="bi bi-check2-circle me-1"></i> Submit Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        var isLocked = {{ $selectedPledge || $selectedMonthlyContribution ? 'true' : 'false' }};
        var initialOutstanding = parseFloat($('#targetOutstandingDisplay').data('outstanding') || 0);

        function checkExcessCalculation() {
            var entered = parseFloat($('#paymentAmountInput').val()) || 0;
            var outstanding = initialOutstanding;

            if (!isLocked) {
                var type = $('#collectionTypeSelect').val();
                if (type === 'event_pledge') {
                    outstanding = parseFloat($('#pledgeSelect').find(':selected').data('outstanding') || 0);
                } else if (type === 'monthly_contribution') {
                    outstanding = parseFloat($('#contributionSelect').find(':selected').data('outstanding') || 0);
                } else {
                    outstanding = 0;
                }
            }

            var currentType = isLocked ? ('{{ $selectedPledge ? "event_pledge" : ($selectedMonthlyContribution ? "monthly_contribution" : "") }}') : $('#collectionTypeSelect').val();

            if (outstanding > 0 && entered > outstanding) {
                var excess = (entered - outstanding).toFixed(2);
                $('#excessCalculationNotice').html(
                    '<div class="p-2 bg-success bg-opacity-10 border border-success border-opacity-25 rounded-3 text-success small">' +
                    '<i class="bi bi-heart-fill me-1"></i> <strong>Exceeds target balance:</strong> ৳' + outstanding.toFixed(2) + ' covers pledge/dues, and <strong>৳' + excess + '</strong> is allocated as an excess contribution.' +
                    '</div>'
                ).show();

                if (currentType === 'event_pledge') {
                    $('#excessAmountDisplayBadge').text('+৳' + excess + ' Excess');
                    $('#excessDestinationContainer').slideDown();
                } else {
                    $('#excessDestinationContainer').slideUp();
                }
            } else {
                $('#excessCalculationNotice').hide();
                $('#excessDestinationContainer').slideUp();
            }
        }

        $('#paymentAmountInput').on('input change', checkExcessCalculation);
        checkExcessCalculation();

        if (!isLocked) {
            function updateContextFields() {
                var type = $('#collectionTypeSelect').val();

                if (type === 'event_pledge') {
                    $('#pledgeFieldContainer').show();
                    $('#contributionFieldContainer').hide();
                    $('#eventFieldContainer').show();
                } else if (type === 'monthly_contribution') {
                    $('#pledgeFieldContainer').hide();
                    $('#contributionFieldContainer').show();
                    $('#eventFieldContainer').hide();
                } else if (type === 'event_donation') {
                    $('#pledgeFieldContainer').hide();
                    $('#contributionFieldContainer').hide();
                    $('#eventFieldContainer').show();
                } else {
                    $('#pledgeFieldContainer').hide();
                    $('#contributionFieldContainer').hide();
                    $('#eventFieldContainer').hide();
                }
                checkExcessCalculation();
            }

            $('#collectionTypeSelect').on('change', updateContextFields);
            updateContextFields();

            function togglePayerSource() {
                var src = $('input[name="payer_source"]:checked').val();
                if (src === 'person') {
                    $('#personPayerRow').show();
                    $('#familyPayerRow').hide();
                    $('#walkInPayerRow').hide();
                } else if (src === 'family') {
                    $('#personPayerRow').hide();
                    $('#familyPayerRow').show();
                    $('#walkInPayerRow').hide();
                } else {
                    $('#personPayerRow').hide();
                    $('#familyPayerRow').hide();
                    $('#walkInPayerRow').show();
                    $('#payerObligationsBadge').hide();
                }
            }

            $('input[name="payer_source"]').on('change', togglePayerSource);
            togglePayerSource();

            // Fetch obligations on person or family selection
            function fetchPayerObligations(personId, familyId) {
                if (!personId && !familyId) {
                    $('#payerObligationsBadge').hide();
                    return;
                }

                $.ajax({
                    url: "{{ route('collections.payer-obligations') }}",
                    data: { person_id: personId, family_id: familyId },
                    success: function(res) {
                        var pledgeCount = res.pledges ? res.pledges.length : 0;
                        var contribCount = res.contributions ? res.contributions.length : 0;

                        if (pledgeCount > 0 || contribCount > 0) {
                            $('#payerObligationsBadge').html(
                                '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">' +
                                '<i class="bi bi-info-circle me-1"></i> ' +
                                (pledgeCount > 0 ? pledgeCount + ' unpaid pledge(s) ' : '') +
                                (pledgeCount > 0 && contribCount > 0 ? '&bull; ' : '') +
                                (contribCount > 0 ? contribCount + ' unpaid dues' : '') +
                                '</span>'
                            ).show();

                            // Populate pledges dropdown
                            if (pledgeCount > 0) {
                                var $plgSelect = $('#pledgeSelect');
                                var currentPlg = $plgSelect.val();
                                $plgSelect.find('option:not(:first)').remove();
                                $.each(res.pledges, function(i, p) {
                                    $plgSelect.append(
                                        $('<option>', {
                                            value: p.id,
                                            text: p.pledge_code + ' (' + p.event_name + ') - Due: ৳' + p.outstanding_amount,
                                            'data-outstanding': p.outstanding_amount,
                                            'data-event': p.event_id
                                        })
                                    );
                                });
                                if (currentPlg) $plgSelect.val(currentPlg);
                            }

                            // Populate contributions dropdown
                            if (contribCount > 0) {
                                var $cntSelect = $('#contributionSelect');
                                var currentCnt = $cntSelect.val();
                                $cntSelect.find('option:not(:first)').remove();
                                $.each(res.contributions, function(i, c) {
                                    $cntSelect.append(
                                        $('<option>', {
                                            value: c.id,
                                            text: c.month_name + ' (' + c.committee_name + ') - Due: ৳' + c.outstanding_amount,
                                            'data-outstanding': c.outstanding_amount
                                        })
                                    );
                                });
                                if (currentCnt) $cntSelect.val(currentCnt);
                            }
                        } else {
                            $('#payerObligationsBadge').hide();
                        }
                    }
                });
            }

            $('#payerPersonSelect').on('change', function() {
                fetchPayerObligations($(this).val(), null);
            });

            $('#payerFamilySelect').on('change', function() {
                fetchPayerObligations(null, $(this).val());
            });

            // Initial trigger if old person was selected
            if ($('#payerPersonSelect').val()) {
                fetchPayerObligations($('#payerPersonSelect').val(), null);
            } else if ($('#payerFamilySelect').val()) {
                fetchPayerObligations(null, $('#payerFamilySelect').val());
            }

            $('#pledgeSelect').on('change', function() {
                var $opt = $(this).find(':selected');
                var due = $opt.data('outstanding');
                var ev = $opt.data('event');
                if (due && !$('#paymentAmountInput').val()) {
                    $('#paymentAmountInput').val(due);
                }
                if (ev) {
                    $('#eventSelect').val(ev).trigger('change');
                }
                checkExcessCalculation();
            });

            $('#contributionSelect').on('change', function() {
                var due = $(this).find(':selected').data('outstanding');
                if (due && !$('#paymentAmountInput').val()) {
                    $('#paymentAmountInput').val(due);
                }
                checkExcessCalculation();
            });
        }

        function toggleMethodFields() {
            var method = $('#paymentMethodSelect').val();
            if (method === 'cash') {
                $('#trxRefContainer').hide();
                $('#bankNameContainer').hide();
                $('#chequeContainer').hide();
            } else if (method === 'cheque') {
                $('#trxRefContainer').hide();
                $('#bankNameContainer').show();
                $('#chequeContainer').show();
            } else if (method === 'bank_transfer') {
                $('#trxRefContainer').show();
                $('#bankNameContainer').show();
                $('#chequeContainer').hide();
            } else {
                $('#trxRefContainer').show();
                $('#bankNameContainer').hide();
                $('#chequeContainer').hide();
            }
        }

        $('#paymentMethodSelect').on('change', toggleMethodFields);
        toggleMethodFields();

        $('#submitPaymentForm').on('submit', function() {
            TempleUI.button.loading('#savePaymentBtn');
        });
    });
</script>
@endpush
