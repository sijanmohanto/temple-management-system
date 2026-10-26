<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Services\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ReceiptController extends Controller implements HasMiddleware
{
    public function __construct(
        protected ReceiptService $receiptService
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_receipts', only: ['index', 'show']),
            new Middleware('permission:print_receipts', only: ['print', 'download']),
        ];
    }

    /**
     * Display a listing of receipts.
     */
    public function index(Request $request): View|JsonResponse
    {
        $rawSearch = $request->input('search');
        $searchString = is_array($rawSearch) ? ($rawSearch['value'] ?? '') : (is_string($rawSearch) ? $rawSearch : '');
        $searchString = trim((string) $searchString);

        $query = Receipt::query()
            ->with(['payment.payerPerson', 'payment.payerFamily', 'payment.event', 'issuer']);

        if (! empty($searchString)) {
            $query->where(function ($q) use ($searchString) {
                $q->where('receipt_number', 'like', "%{$searchString}%")
                    ->orWhereHas('payment', function ($pq) use ($searchString) {
                        $pq->where('payment_code', 'like', "%{$searchString}%")
                            ->orWhere('payer_name_override', 'like', "%{$searchString}%")
                            ->orWhereHas('payerPerson', fn ($per) => $per->where('name_en', 'like', "%{$searchString}%"))
                            ->orWhereHas('payerFamily', fn ($fam) => $fam->where('family_name', 'like', "%{$searchString}%"));
                    });
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if (($request->ajax() || $request->wantsJson()) && $request->has('draw')) {
            $draw = $request->integer('draw');
            $start = $request->integer('start', 0);
            $length = $request->integer('length', 15);
            $totalRecords = Receipt::count();
            $filteredRecords = $query->count();

            $records = $query->latest('issued_at')->skip($start)->take($length)->get();

            $data = $records->map(function (Receipt $receipt) {
                $statusBadge = $receipt->isValid()
                    ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><i class="bi bi-check-circle me-1"></i>'.e(__('donations.receipt_status_valid')).'</span>'
                    : '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><i class="bi bi-slash-circle me-1"></i>'.e(__('donations.receipt_status_voided')).'</span>';

                $actions = '<div class="btn-group btn-group-sm text-end">'
                    .'<a href="'.route('receipts.show', $receipt).'" class="btn btn-outline-primary" title="'.e(__('common.view')).'"><i class="bi bi-eye"></i></a>'
                    .'<a href="'.route('receipts.print', $receipt).'" target="_blank" class="btn btn-outline-secondary" title="'.e(__('donations.print_receipt')).'"><i class="bi bi-printer"></i></a>'
                    .'</div>';

                return [
                    '<span class="badge bg-light text-dark border font-monospace">'.e($receipt->receipt_number).'</span>',
                    '<div class="fw-bold text-dark">'.e($receipt->payment->payer_display_name).'</div>',
                    '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">'
                        .e(__('donations.type_'.$receipt->payment->collection_type)).'</span>',
                    '<div class="fw-bold text-dark">৳'.number_format((float) $receipt->payment->amount, 2).'</div>',
                    '<div class="small text-muted">'.e($receipt->issued_at->format('d M Y, h:i A')).'</div>',
                    $statusBadge,
                    $actions,
                ];
            });

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        $receipts = $query->latest('issued_at')->paginate(15)->withQueryString();

        return view('donations.receipts.index', compact('receipts'));
    }

    /**
     * Show receipt details view.
     */
    public function show(Receipt $receipt): View
    {
        $receipt->load([
            'payment.payerPerson',
            'payment.payerFamily',
            'payment.event',
            'payment.allocations',
            'issuer',
            'voider',
        ]);

        $amountInWords = $this->receiptService->amountToWords((float) $receipt->payment->amount, app()->getLocale());

        return view('donations.receipts.show', compact('receipt', 'amountInWords'));
    }

    /**
     * Print-friendly receipt layout.
     */
    public function print(Receipt $receipt): View
    {
        $receipt->load([
            'payment.payerPerson',
            'payment.payerFamily',
            'payment.event',
            'payment.allocations',
            'issuer',
            'voider',
        ]);

        $amountInWords = $this->receiptService->amountToWords((float) $receipt->payment->amount, app()->getLocale());

        return view('donations.receipts.print', compact('receipt', 'amountInWords'));
    }

    /**
     * Download receipt as PDF / printable document.
     */
    public function download(Receipt $receipt): View
    {
        $receipt->load([
            'payment.payerPerson',
            'payment.payerFamily',
            'payment.event',
            'payment.allocations',
            'issuer',
            'voider',
        ]);

        $amountInWords = $this->receiptService->amountToWords((float) $receipt->payment->amount, app()->getLocale());
        $isDownloadMode = true;

        return view('donations.receipts.print', compact('receipt', 'amountInWords', 'isDownloadMode'));
    }
}
