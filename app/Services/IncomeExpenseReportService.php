<?php

namespace App\Services;

use App\Models\CollectionPayment;
use App\Models\Event;
use App\Models\Expense;
use App\Models\ManualIncome;
use App\Models\Person;
use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncomeExpenseReportService
{
    /**
     * Get Income DataTables payload with server-side processing and live summary totals.
     * STRICTLY 7 columns, NO row number / # column.
     *
     * @return array{
     *     draw: int,
     *     recordsTotal: int,
     *     recordsFiltered: int,
     *     data: array,
     *     summary: array
     * }
     */
    public function getIncomeDataTable(Request $request): array
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 15);
        if ($length <= 0) {
            $length = 15;
        }

        // Base total records count (un-deleted)
        $totalPayments = CollectionPayment::count();
        $totalManual = ManualIncome::count();
        $totalRecords = $totalPayments + $totalManual;

        // Build filtered query sub-selects
        $paymentQuery = $this->buildFilteredCollectionPaymentsQuery($request);
        $manualQuery = $this->buildFilteredManualIncomeQuery($request);

        // Calculate summary cards matching the current filters
        $summary = $this->calculateIncomeSummary($paymentQuery, $manualQuery);

        // If both queries are empty/filtered out, return empty table
        $countPayments = (clone $paymentQuery)->count();
        $countManual = (clone $manualQuery)->count();
        $filteredRecords = $countPayments + $countManual;

        if ($filteredRecords === 0) {
            return [
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => 0,
                'data' => [],
                'summary' => $summary,
            ];
        }

        // Prepare selects for union
        $pSelect = $paymentQuery->select([
            'collection_payments.id as id',
            DB::raw("'collection' as source_type"),
            'collection_payments.amount as amount',
            'collection_payments.payment_date as trans_date',
            'collection_payments.collection_type as collection_type',
            'collection_payments.status as status',
            'collection_payments.event_id as event_id',
            'collection_payments.created_at as created_at',
        ]);

        $mSelect = $manualQuery->select([
            'manual_incomes.id as id',
            DB::raw("'manual' as source_type"),
            'manual_incomes.amount as amount',
            'manual_incomes.income_date as trans_date',
            DB::raw("'manual_income' as collection_type"),
            'manual_incomes.status as status',
            'manual_incomes.event_id as event_id',
            'manual_incomes.created_at as created_at',
        ]);

        $union = $pSelect->unionAll($mSelect);

        // Sorting mapping (7 columns: 0=donor_details, 1=purpose, 2=event, 3=amount, 4=date, 5=status, 6=actions)
        $orderColIdx = (int) $request->input('order.0.column', 4);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $orderCol = match ($orderColIdx) {
            1 => 'collection_type',
            2 => 'event_id',
            3 => 'amount',
            4 => 'trans_date',
            5 => 'status',
            default => 'trans_date',
        };

        $paginatedRows = DB::query()->fromSub($union, 'u')
            ->orderBy($orderCol, $orderDir)
            ->orderBy('id', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        // Separate IDs to eager load models efficiently
        $collectionIds = [];
        $manualIds = [];
        foreach ($paginatedRows as $row) {
            if ($row->source_type === 'collection') {
                $collectionIds[] = (int) $row->id;
            } else {
                $manualIds[] = (int) $row->id;
            }
        }

        $collections = CollectionPayment::with(['payerPerson.committeeMemberships', 'payerFamily', 'event.category', 'receipt'])
            ->whereIn('id', $collectionIds)
            ->get()
            ->keyBy('id');

        $manuals = ManualIncome::with(['event.category', 'incomeAccount', 'depositAccount'])
            ->whereIn('id', $manualIds)
            ->get()
            ->keyBy('id');

        // Render DataTables row array
        $data = [];
        foreach ($paginatedRows as $row) {
            if ($row->source_type === 'collection') {
                /** @var CollectionPayment|null $item */
                $item = $collections->get($row->id);
                if (! $item) {
                    continue;
                }
                $data[] = $this->formatCollectionRow($item);
            } else {
                /** @var ManualIncome|null $item */
                $item = $manuals->get($row->id);
                if (! $item) {
                    continue;
                }
                $data[] = $this->formatManualIncomeRow($item);
            }
        }

        return [
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
            'summary' => $summary,
        ];
    }

    /**
     * Calculate summary metrics for Income Report.
     */
    public function getIncomeSummaryCards(Request $request): array
    {
        $paymentQuery = $this->buildFilteredCollectionPaymentsQuery($request);
        $manualQuery = $this->buildFilteredManualIncomeQuery($request);

        return $this->calculateIncomeSummary($paymentQuery, $manualQuery);
    }

    /**
     * Get Expense DataTables payload with server-side processing and live summary totals.
     * STRICTLY 7 columns, NO row number / # column.
     *
     * @return array{
     *     draw: int,
     *     recordsTotal: int,
     *     recordsFiltered: int,
     *     data: array,
     *     summary: array
     * }
     */
    public function getExpenseDataTable(Request $request): array
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 15);
        if ($length <= 0) {
            $length = 15;
        }

        $totalRecords = Expense::count();
        $query = $this->buildFilteredExpenseQuery($request);

        // Live summary metrics based on applied filters
        $summary = $this->calculateExpenseSummary(clone $query);

        $filteredRecords = (clone $query)->count();

        // 7 columns: 0=payee_details, 1=purpose, 2=event, 3=amount, 4=date, 5=status, 6=actions
        $orderColIdx = (int) $request->input('order.0.column', 4);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $orderCol = match ($orderColIdx) {
            0 => 'vendor_name',
            1 => 'title_en',
            2 => 'event_id',
            3 => 'amount',
            4 => 'expense_date',
            5 => 'status',
            default => 'expense_date',
        };

        $expenses = $query->with(['expenseAccount', 'paymentAccount', 'event.category', 'creator'])
            ->orderBy($orderCol, $orderDir)
            ->orderBy('id', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($expenses as $expense) {
            $data[] = $this->formatExpenseRow($expense);
        }

        return [
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
            'summary' => $summary,
        ];
    }

    /**
     * Calculate summary metrics for Expense Report.
     */
    public function getExpenseSummaryCards(Request $request): array
    {
        $query = $this->buildFilteredExpenseQuery($request);

        return $this->calculateExpenseSummary($query);
    }

    /**
     * Load comprehensive details for an income transaction for the offcanvas view.
     *
     * @return array{
     *     type: string,
     *     record: CollectionPayment|ManualIncome,
     *     allocations?: Collection,
     *     receipt?: Receipt|null,
     *     is_committee_member: bool,
     *     donor_display_name: string,
     *     donor_type_label: string
     * }
     */
    public function getIncomeDetails(string $type, int $id): array
    {
        if ($type === 'collection') {
            $payment = CollectionPayment::with([
                'payerPerson.committeeMemberships.committee',
                'payerFamily',
                'event.category',
                'submitter',
                'verifier',
                'rejecter',
                'reverser',
                'receipt',
                'allocations.pledge',
                'allocations.monthlyContribution',
                'allocations.event',
                'journalEntry',
            ])->findOrFail($id);

            $isCommittee = false;
            $donorLabel = __('finance.guest_visitor');
            $donorName = $payment->payer_name_override ?? __('finance.guest_visitor');

            if ($payment->payerPerson) {
                $isCommittee = $payment->payerPerson->committeeMemberships
                    ->where('status', 'active')
                    ->isNotEmpty();
                $donorLabel = $isCommittee ? __('finance.committee_member') : __('finance.registered_person');
                $donorName = $payment->payerPerson->name;
            } elseif ($payment->payerFamily) {
                $donorLabel = __('donations.family');
                $donorName = $payment->payerFamily->family_name;
            }

            return [
                'type' => 'collection',
                'record' => $payment,
                'allocations' => $payment->allocations,
                'receipt' => $payment->receipt,
                'is_committee_member' => $isCommittee,
                'donor_display_name' => $donorName,
                'donor_type_label' => $donorLabel,
            ];
        }

        $manual = ManualIncome::with([
            'incomeAccount',
            'depositAccount',
            'event.category',
            'creator',
            'journalEntry',
        ])->findOrFail($id);

        return [
            'type' => 'manual',
            'record' => $manual,
            'is_committee_member' => false,
            'donor_display_name' => $manual->payer_name ?? __('finance.guest_visitor'),
            'donor_type_label' => __('finance.guest_visitor'),
        ];
    }

    /**
     * Load comprehensive details for an expense voucher for the offcanvas view.
     */
    public function getExpenseDetails(int $id): Expense
    {
        return Expense::with([
            'expenseAccount',
            'paymentAccount',
            'event.category',
            'creator',
            'approver',
            'rejecter',
            'journalEntry',
        ])->findOrFail($id);
    }

    /**
     * Search registered people and active payees for Select2 dropdowns.
     */
    public function searchPeople(Request $request): array
    {
        $term = trim((string) $request->input('q', ''));
        $context = (string) $request->input('context', 'all');

        $query = Person::query();

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name_en', 'like', "%{$term}%")
                    ->orWhere('name_bn', 'like', "%{$term}%")
                    ->orWhere('person_code', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        $people = $query->limit(20)->get(['id', 'person_code', 'name_en', 'name_bn', 'phone']);

        $results = $people->map(function (Person $person) {
            $name = $person->name;
            $phone = $person->phone ? " [{$person->phone}]" : '';

            return [
                'id' => $person->id,
                'text' => "{$name} ({$person->person_code}){$phone}",
                'name' => $name,
            ];
        })->all();

        // In expense context, also include unique vendor names if they match
        if ($context === 'expense' && $term !== '') {
            $vendors = Expense::where('vendor_name', 'like', "%{$term}%")
                ->distinct()
                ->limit(5)
                ->pluck('vendor_name');

            foreach ($vendors as $vendor) {
                if (! empty($vendor)) {
                    $results[] = [
                        'id' => 'vendor:'.$vendor,
                        'text' => $vendor.' ('.__('finance.payee_vendor').')',
                        'name' => $vendor,
                    ];
                }
            }
        }

        return ['results' => $results];
    }

    // ------------------------------------------------------------------------
    // Query Builders & Filter Logic
    // ------------------------------------------------------------------------

    protected function buildFilteredCollectionPaymentsQuery(Request $request)
    {
        $query = CollectionPayment::query();

        // Person / Donor filter
        if ($request->filled('person_id')) {
            $query->where('payer_person_id', (int) $request->input('person_id'));
        }

        // Donor Type filter
        if ($request->filled('donor_type') && $request->input('donor_type') !== 'all') {
            $donorType = $request->input('donor_type');
            if ($donorType === 'guest') {
                $query->whereNull('payer_person_id');
            } elseif ($donorType === 'committee_member') {
                $query->where(function ($q) {
                    $q->whereHas('payerPerson.committeeMemberships', function ($sub) {
                        $sub->where('status', 'active');
                    })->orWhere('collection_type', CollectionPayment::TYPE_MONTHLY_CONTRIBUTION);
                });
            } elseif ($donorType === 'registered_person') {
                $query->whereNotNull('payer_person_id')
                    ->whereDoesntHave('payerPerson.committeeMemberships', function ($sub) {
                        $sub->where('status', 'active');
                    })
                    ->where('collection_type', '!=', CollectionPayment::TYPE_MONTHLY_CONTRIBUTION);
            }
        }

        // Income Type filter
        if ($request->filled('income_type') && $request->input('income_type') !== 'all') {
            $incomeType = $request->input('income_type');
            if ($incomeType === 'manual_income') {
                // If user specifically asked for manual income, collection payments return 0
                $query->whereRaw('1 = 0');
            } elseif ($incomeType === 'all_donations') {
                $query->whereIn('collection_type', [
                    CollectionPayment::TYPE_EVENT_PLEDGE,
                    CollectionPayment::TYPE_EVENT_DONATION,
                    CollectionPayment::TYPE_GENERAL_DONATION,
                ]);
            } else {
                $query->where('collection_type', $incomeType);
            }
        }

        // Event filter
        if ($request->filled('event_id') && $request->input('event_id') !== 'all') {
            $eventId = $request->input('event_id');
            if ($eventId === 'general' || $eventId === 'none' || $eventId === '0') {
                $query->whereNull('event_id');
            } else {
                $query->where('event_id', (int) $eventId);
            }
        }

        // Date Range
        if ($request->filled('start_date')) {
            $query->where('payment_date', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('payment_date', '<=', $request->input('end_date'));
        }

        // Status
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = $request->input('status');
            if ($status === 'posted' || $status === 'verified') {
                $query->where('status', CollectionPayment::STATUS_VERIFIED);
            } elseif ($status === 'pending' || $status === 'pending_verification') {
                $query->where('status', CollectionPayment::STATUS_PENDING);
            } else {
                $query->where('status', $status);
            }
        }

        // Payment Method
        if ($request->filled('payment_method') && $request->input('payment_method') !== 'all') {
            $query->where('payment_method', $request->input('payment_method'));
        }

        // Search Keyword
        $search = trim((string) ($request->input('search.value') ?? $request->input('search', '')));
        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('payment_code', 'like', "%{$search}%")
                    ->orWhere('payer_name_override', 'like', "%{$search}%")
                    ->orWhere('payer_phone_override', 'like', "%{$search}%")
                    ->orWhere('transaction_reference', 'like', "%{$search}%")
                    ->orWhere('cheque_number', 'like', "%{$search}%")
                    ->orWhereHas('payerPerson', function ($p) use ($search) {
                        $p->where('name_en', 'like', "%{$search}%")
                            ->orWhere('name_bn', 'like', "%{$search}%")
                            ->orWhere('person_code', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('receipt', function ($r) use ($search) {
                        $r->where('receipt_number', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    protected function buildFilteredManualIncomeQuery(Request $request)
    {
        $query = ManualIncome::query();

        // If filtering by specific person ID, manual income has no registered person
        if ($request->filled('person_id')) {
            $query->whereRaw('1 = 0');

            return $query;
        }

        // Donor Type filter: Manual incomes are recorded with payee/payer text, counted as Guest
        if ($request->filled('donor_type') && $request->input('donor_type') !== 'all') {
            $donorType = $request->input('donor_type');
            if ($donorType !== 'guest') {
                $query->whereRaw('1 = 0');

                return $query;
            }
        }

        // Income Type filter:
        if ($request->filled('income_type') && $request->input('income_type') !== 'all') {
            $incomeType = $request->input('income_type');
            if ($incomeType !== 'manual_income') {
                $query->whereRaw('1 = 0');

                return $query;
            }
        }

        // Event filter
        if ($request->filled('event_id') && $request->input('event_id') !== 'all') {
            $eventId = $request->input('event_id');
            if ($eventId === 'general' || $eventId === 'none' || $eventId === '0') {
                $query->whereNull('event_id');
            } else {
                $query->where('event_id', (int) $eventId);
            }
        }

        // Date Range
        if ($request->filled('start_date')) {
            $query->where('income_date', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('income_date', '<=', $request->input('end_date'));
        }

        // Status filter:
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = $request->input('status');
            if ($status === 'verified' || $status === 'posted') {
                $query->where('status', ManualIncome::STATUS_POSTED);
            } elseif ($status === 'reversed') {
                $query->where('status', ManualIncome::STATUS_REVERSED);
            } else {
                // Pending or rejected do not apply to ManualIncome
                $query->whereRaw('1 = 0');

                return $query;
            }
        }

        // Payment Method
        if ($request->filled('payment_method') && $request->input('payment_method') !== 'all') {
            $query->where('payment_method', $request->input('payment_method'));
        }

        // Search Keyword
        $search = trim((string) ($request->input('search.value') ?? $request->input('search', '')));
        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('income_number', 'like', "%{$search}%")
                    ->orWhere('payer_name', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('title_en', 'like', "%{$search}%")
                    ->orWhere('title_bn', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    protected function buildFilteredExpenseQuery(Request $request)
    {
        $query = Expense::query();

        // Payee / Person filter
        if ($request->filled('person_id')) {
            $val = $request->input('person_id');
            if (str_starts_with($val, 'vendor:')) {
                $vendorName = substr($val, 7);
                $query->where('vendor_name', $vendorName);
            } else {
                $person = Person::find($val);
                if ($person) {
                    $query->where(function ($q) use ($person) {
                        $q->where('vendor_name', 'like', "%{$person->name_en}%")
                            ->orWhere('vendor_name', 'like', "%{$person->name_bn}%")
                            ->orWhere('vendor_phone', $person->phone);
                    });
                }
            }
        }

        // Expense Category Account
        if ($request->filled('expense_category_id') && $request->input('expense_category_id') !== 'all') {
            $query->where('financial_account_id', (int) $request->input('expense_category_id'));
        }

        // Event filter
        if ($request->filled('event_id') && $request->input('event_id') !== 'all') {
            $eventId = $request->input('event_id');
            if ($eventId === 'general' || $eventId === 'none' || $eventId === '0') {
                $query->whereNull('event_id');
            } else {
                $query->where('event_id', (int) $eventId);
            }
        }

        // Date Range
        if ($request->filled('start_date')) {
            $query->where('expense_date', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('expense_date', '<=', $request->input('end_date'));
        }

        // Status
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = $request->input('status');
            if ($status === 'pending' || $status === 'pending_approval') {
                $query->where('status', Expense::STATUS_PENDING);
            } elseif ($status === 'approved' || $status === 'paid' || $status === 'posted') {
                $query->where('status', Expense::STATUS_APPROVED);
            } else {
                $query->where('status', $status);
            }
        }

        // Payment Method
        if ($request->filled('payment_method') && $request->input('payment_method') !== 'all') {
            $query->where('payment_method', $request->input('payment_method'));
        }

        // Search Keyword
        $search = trim((string) ($request->input('search.value') ?? $request->input('search', '')));
        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('expense_number', 'like', "%{$search}%")
                    ->orWhere('vendor_name', 'like', "%{$search}%")
                    ->orWhere('vendor_phone', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('title_en', 'like', "%{$search}%")
                    ->orWhere('title_bn', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    // ------------------------------------------------------------------------
    // Calculation Helpers
    // ------------------------------------------------------------------------

    protected function calculateIncomeSummary($paymentQuery, $manualQuery): array
    {
        $verifiedPayments = (float) (clone $paymentQuery)
            ->where('status', CollectionPayment::STATUS_VERIFIED)
            ->sum('amount');

        $pendingPayments = (float) (clone $paymentQuery)
            ->where('status', CollectionPayment::STATUS_PENDING)
            ->sum('amount');

        $countPayments = (clone $paymentQuery)->count();

        $postedManual = (float) (clone $manualQuery)
            ->where('status', ManualIncome::STATUS_POSTED)
            ->sum('amount');

        $countManual = (clone $manualQuery)->count();

        $verifiedTotal = $verifiedPayments + $postedManual;
        $totalTransactions = $countPayments + $countManual;

        return [
            'verified_total' => $verifiedTotal,
            'verified_total_formatted' => '৳'.number_format($verifiedTotal, 2),
            'pending_amount' => $pendingPayments,
            'pending_amount_formatted' => '৳'.number_format($pendingPayments, 2),
            'transaction_count' => $totalTransactions,
            'transaction_count_formatted' => number_format($totalTransactions),
        ];
    }

    protected function calculateExpenseSummary($query): array
    {
        $paidTotal = (float) (clone $query)
            ->where('status', Expense::STATUS_APPROVED)
            ->sum('amount');

        $pendingAmount = (float) (clone $query)
            ->where('status', Expense::STATUS_PENDING)
            ->sum('amount');

        $count = (clone $query)->count();

        return [
            'paid_total' => $paidTotal,
            'paid_total_formatted' => '৳'.number_format($paidTotal, 2),
            'pending_amount' => $pendingAmount,
            'pending_amount_formatted' => '৳'.number_format($pendingAmount, 2),
            'transaction_count' => $count,
            'transaction_count_formatted' => number_format($count),
        ];
    }

    // ------------------------------------------------------------------------
    // Row Formatters (STRICTLY 7 columns, NO row number)
    // ------------------------------------------------------------------------

    protected function formatCollectionRow(CollectionPayment $payment): array
    {
        $donorDetails = '';
        if ($payment->payerPerson) {
            $isCommittee = $payment->payerPerson->committeeMemberships
                ->where('status', 'active')
                ->isNotEmpty();
            $badgeClass = $isCommittee ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle';
            $badgeLabel = $isCommittee ? __('finance.committee_member') : __('finance.registered_person');

            $donorDetails = '<div class="fw-bold">'.e($payment->payerPerson->name).'</div>';
            $donorDetails .= '<div class="small text-muted"><span class="badge '.$badgeClass.' me-1">'.$badgeLabel.'</span>'.e($payment->payerPerson->person_code);
            if ($payment->payerPerson->phone) {
                $donorDetails .= ' • '.e($payment->payerPerson->phone);
            }
            $donorDetails .= '</div>';
        } elseif ($payment->payerFamily) {
            $donorDetails = '<div class="fw-bold">'.e($payment->payerFamily->family_name).'</div>';
            $donorDetails .= '<div class="small text-muted"><span class="badge bg-info-subtle text-dark border me-1">'.__('families.family').'</span>'.e($payment->payerFamily->family_code).'</div>';
        } else {
            $donorName = $payment->payer_name_override ?? __('finance.guest_visitor');
            $donorDetails = '<div class="fw-bold">'.e($donorName).'</div>';
            $donorDetails .= '<div class="small text-muted"><span class="badge bg-secondary-subtle text-dark border me-1">'.__('finance.guest_visitor').'</span>';
            if ($payment->payer_phone_override) {
                $donorDetails .= e($payment->payer_phone_override);
            }
            $donorDetails .= '</div>';
        }

        // Purpose
        $purposeLabel = match ($payment->collection_type) {
            CollectionPayment::TYPE_EVENT_PLEDGE => '<span class="badge bg-warning-subtle text-dark border me-1">'.__('finance.event_pledge').'</span>',
            CollectionPayment::TYPE_EVENT_DONATION => '<span class="badge bg-success-subtle text-success border me-1">'.__('finance.event_donation').'</span>',
            CollectionPayment::TYPE_MONTHLY_CONTRIBUTION => '<span class="badge bg-primary-subtle text-primary border me-1">'.__('finance.monthly_contribution').'</span>',
            CollectionPayment::TYPE_GENERAL_DONATION => '<span class="badge bg-info-subtle text-dark border me-1">'.__('finance.general_donation').'</span>',
            default => '<span class="badge bg-light text-dark border me-1">'.e(ucwords(str_replace('_', ' ', $payment->collection_type))).'</span>',
        };
        $purpose = '<div>'.$purposeLabel.'<span class="font-monospace small text-muted">'.e($payment->payment_code).'</span></div>';
        if ($payment->payment_notes) {
            $purpose .= '<div class="small text-muted text-truncate" style="max-width: 220px;" title="'.e($payment->payment_notes).'">'.e($payment->payment_notes).'</div>';
        }

        // Event
        if ($payment->event) {
            $eventTitle = app()->getLocale() === 'bn' && $payment->event->title_bn ? $payment->event->title_bn : $payment->event->title_en;
            $event = '<div class="fw-semibold">'.e($eventTitle).'</div>';
            if ($payment->event->category) {
                $event .= '<span class="badge bg-light text-muted border" style="font-size: 0.7rem;">'.e($payment->event->category->name_en).'</span>';
            }
        } else {
            $event = '<span class="badge bg-light text-dark border">'.__('finance.general_temple_fund').'</span>';
        }

        // Amount
        $amount = '৳'.number_format((float) $payment->amount, 2);

        // Date
        $date = $payment->payment_date ? Carbon::parse($payment->payment_date)->format('Y-m-d') : '-';

        // Status
        $status = match ($payment->status) {
            CollectionPayment::STATUS_VERIFIED => '<span class="badge bg-success">'.__('finance.verified').'</span>',
            CollectionPayment::STATUS_PENDING => '<span class="badge bg-warning text-dark">'.__('finance.pending').'</span>',
            CollectionPayment::STATUS_REJECTED => '<span class="badge bg-danger">'.__('finance.rejected').'</span>',
            CollectionPayment::STATUS_REVERSED => '<span class="badge bg-secondary">'.__('finance.reversed').'</span>',
            default => '<span class="badge bg-secondary">'.e($payment->status).'</span>',
        };

        // Actions
        $action = '<button type="button" class="btn btn-sm btn-outline-primary btn-view-income-details" data-type="collection" data-id="'.$payment->id.'" title="'.__('finance.view_details').'">'.
            '<i class="bi bi-eye me-1"></i>'.__('finance.view_details').
            '</button>';

        return [
            'donor_details' => $donorDetails,
            'purpose' => $purpose,
            'event' => $event,
            'amount' => $amount,
            'date' => $date,
            'status' => $status,
            'actions' => $action,
        ];
    }

    protected function formatManualIncomeRow(ManualIncome $manual): array
    {
        $payerName = $manual->payer_name ?? __('finance.guest_visitor');
        $donorDetails = '<div class="fw-bold">'.e($payerName).'</div>';
        $donorDetails .= '<div class="small text-muted"><span class="badge bg-secondary-subtle text-dark border me-1">'.__('finance.guest_visitor').'</span>'.__('finance.misc_income').'</div>';

        // Purpose
        $purposeLabel = '<span class="badge bg-secondary-subtle text-secondary border me-1">'.__('finance.misc_income').'</span>';
        $purpose = '<div>'.$purposeLabel.'<span class="font-monospace small text-muted">'.e($manual->income_number).'</span></div>';
        $title = app()->getLocale() === 'bn' && $manual->title_bn ? $manual->title_bn : $manual->title_en;
        if ($title || $manual->notes) {
            $desc = $title ?: $manual->notes;
            $purpose .= '<div class="small text-muted text-truncate" style="max-width: 220px;" title="'.e($desc).'">'.e($desc).'</div>';
        }

        // Event
        if ($manual->event) {
            $eventTitle = app()->getLocale() === 'bn' && $manual->event->title_bn ? $manual->event->title_bn : $manual->event->title_en;
            $event = '<div class="fw-semibold">'.e($eventTitle).'</div>';
            if ($manual->event->category) {
                $event .= '<span class="badge bg-light text-muted border" style="font-size: 0.7rem;">'.e($manual->event->category->name_en).'</span>';
            }
        } else {
            $event = '<span class="badge bg-light text-dark border">'.__('finance.general_temple_fund').'</span>';
        }

        // Amount
        $amount = '৳'.number_format((float) $manual->amount, 2);

        // Date
        $date = $manual->income_date ? Carbon::parse($manual->income_date)->format('Y-m-d') : '-';

        // Status
        $status = match ($manual->status) {
            ManualIncome::STATUS_POSTED => '<span class="badge bg-success">'.__('finance.posted').'</span>',
            ManualIncome::STATUS_REVERSED => '<span class="badge bg-secondary">'.__('finance.reversed').'</span>',
            default => '<span class="badge bg-secondary">'.e($manual->status).'</span>',
        };

        // Actions
        $action = '<button type="button" class="btn btn-sm btn-outline-primary btn-view-income-details" data-type="manual" data-id="'.$manual->id.'" title="'.__('finance.view_details').'">'.
            '<i class="bi bi-eye me-1"></i>'.__('finance.view_details').
            '</button>';

        return [
            'donor_details' => $donorDetails,
            'purpose' => $purpose,
            'event' => $event,
            'amount' => $amount,
            'date' => $date,
            'status' => $status,
            'actions' => $action,
        ];
    }

    protected function formatExpenseRow(Expense $expense): array
    {
        $payeeName = $expense->payee_name ?? $expense->vendor_name ?? '-';
        $payeeDetails = '<div class="fw-bold">'.e($payeeName).'</div>';
        if ($expense->vendor_phone) {
            $payeeDetails .= '<div class="small text-muted font-monospace"><i class="bi bi-telephone me-1"></i>'.e($expense->vendor_phone).'</div>';
        }

        // Purpose
        $title = app()->getLocale() === 'bn' && $expense->title_bn ? $expense->title_bn : $expense->title_en;
        $categoryName = $expense->expenseAccount ? (app()->getLocale() === 'bn' && $expense->expenseAccount->name_bn ? $expense->expenseAccount->name_bn : $expense->expenseAccount->name_en) : '';
        $purpose = '<div>';
        if ($categoryName) {
            $purpose .= '<span class="badge bg-secondary-subtle text-secondary border me-1">'.e($categoryName).'</span>';
        }
        $purpose .= '<span class="font-monospace small text-muted">'.e($expense->expense_number).'</span>';
        $purpose .= '</div>';
        if ($title) {
            $purpose .= '<div class="small text-muted text-truncate" style="max-width: 220px;" title="'.e($title).'">'.e($title).'</div>';
        }

        // Event
        if ($expense->event) {
            $eventTitle = app()->getLocale() === 'bn' && $expense->event->title_bn ? $expense->event->title_bn : $expense->event->title_en;
            $event = '<div class="fw-semibold">'.e($eventTitle).'</div>';
            if ($expense->event->category) {
                $event .= '<span class="badge bg-light text-muted border" style="font-size: 0.7rem;">'.e($expense->event->category->name_en).'</span>';
            }
        } else {
            $event = '<span class="badge bg-light text-dark border">'.__('finance.general_temple_fund').'</span>';
        }

        // Amount
        $amount = '৳'.number_format((float) $expense->amount, 2);

        // Date
        $date = $expense->expense_date ? Carbon::parse($expense->expense_date)->format('Y-m-d') : '-';

        // Status
        $status = match ($expense->status) {
            Expense::STATUS_APPROVED => '<span class="badge bg-success">'.__('finance.approved').'</span>',
            Expense::STATUS_PENDING => '<span class="badge bg-warning text-dark">'.__('finance.pending_approval').'</span>',
            Expense::STATUS_REJECTED => '<span class="badge bg-danger">'.__('finance.rejected').'</span>',
            Expense::STATUS_REVERSED => '<span class="badge bg-secondary">'.__('finance.reversed').'</span>',
            default => '<span class="badge bg-secondary">'.e($expense->status).'</span>',
        };

        // Actions
        $action = '<button type="button" class="btn btn-sm btn-outline-primary btn-view-expense-details" data-id="'.$expense->id.'" title="'.__('finance.view_details').'">'.
            '<i class="bi bi-eye me-1"></i>'.__('finance.view_details').
            '</button>';

        return [
            'payee_details' => $payeeDetails,
            'purpose' => $purpose,
            'event' => $event,
            'amount' => $amount,
            'date' => $date,
            'status' => $status,
            'actions' => $action,
        ];
    }
}
