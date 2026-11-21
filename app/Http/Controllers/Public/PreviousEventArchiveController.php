<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventDocument;
use App\Services\EventCollectionReportService;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PreviousEventArchiveController extends Controller
{
    /**
     * Display a paginated listing of published historical events with financial reports.
     */
    public function index(Request $request): View
    {
        $query = Event::with('category')
            ->published()
            ->whereNotIn('status', [Event::STATUS_DRAFT])
            ->withCount(['documents as published_documents_count' => function ($q) {
                $q->published();
            }]);

        // Filter: Category
        if ($request->filled('category_id')) {
            $query->where('event_category_id', $request->integer('category_id'));
        }

        // Filter: Financial / Event Year
        if ($request->filled('year')) {
            $query->where('event_year', $request->integer('year'));
        }

        // Filter: Search by Name or Code
        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('name_en', 'like', "%{$term}%")
                    ->orWhere('name_bn', 'like', "%{$term}%")
                    ->orWhere('event_code', 'like', "%{$term}%");
            });
        }

        // Filter: Report Category (events that possess published reports in this category)
        if ($request->filled('report_category')) {
            $cat = $request->input('report_category');
            $query->whereHas('documents', function ($dq) use ($cat) {
                $dq->published()->where('report_category', $cat);
            });
        }

        // Ordering: Closed/historical first, then by year and date descending
        $events = $query->orderByRaw("
            CASE
                WHEN status = 'closed' THEN 1
                WHEN status = 'closing' THEN 2
                ELSE 3
            END ASC
        ")
            ->orderByDesc('event_year')
            ->orderByDesc('end_date')
            ->paginate(12)
            ->withQueryString();

        $categories = EventCategory::active()->orderBy('name_en')->get();
        $availableYears = Event::published()
            ->whereNotIn('status', [Event::STATUS_DRAFT])
            ->whereNotNull('event_year')
            ->distinct()
            ->orderByDesc('event_year')
            ->pluck('event_year');

        $reportCategories = EventDocument::REPORT_CATEGORIES;
        $filters = $request->only(['category_id', 'year', 'search', 'report_category']);

        return view('public.previous_events.index', compact(
            'events',
            'categories',
            'availableYears',
            'reportCategories',
            'filters'
        ));
    }

    /**
     * Display historical event financial summary and official published documents.
     */
    public function show(
        string $slug,
        FinancialReportService $financialReportService,
        EventCollectionReportService $collectionReportService
    ): View {
        $event = Event::with(['category', 'settlement.destinationAccount'])
            ->published()
            ->whereNotIn('status', [Event::STATUS_DRAFT])
            ->where('slug', $slug)
            ->firstOrFail();

        // 1. Authoritative double-entry ledger calculation
        $incomeExpenseStatement = $financialReportService->getIncomeExpenseStatement(null, null, $event->id);

        // 2. Collection & pledge metrics
        $collectionMetrics = $collectionReportService->getMetrics($event);

        // 3. Published historical documents uploaded by Admin
        $documents = EventDocument::forEvent($event->id)
            ->published()
            ->orderByDesc('published_at')
            ->get();

        // 4. Verify whether reliable accounting records exist
        $hasLedgerRecords = (
            $incomeExpenseStatement['total_income'] > 0
            || $incomeExpenseStatement['total_expense'] > 0
            || ! empty($incomeExpenseStatement['incomes'])
            || ! empty($incomeExpenseStatement['expenses'])
            || $collectionMetrics['total_verified_collections'] > 0
            || $collectionMetrics['total_approved_expenses'] > 0
            || ($event->settlement && $event->settlement->isCompleted())
        );

        return view('public.previous_events.show', compact(
            'event',
            'incomeExpenseStatement',
            'collectionMetrics',
            'documents',
            'hasLedgerRecords'
        ));
    }

    /**
     * Secure preview of published event document (PDF or image).
     */
    public function previewDocument(string $slug, string $uuid): Response
    {
        return $this->serveDocument($slug, $uuid, preview: true);
    }

    /**
     * Secure download of published event document.
     */
    public function downloadDocument(string $slug, string $uuid): Response
    {
        return $this->serveDocument($slug, $uuid, preview: false);
    }

    /**
     * Verify publication status, event ownership, disk existence, and serve safely.
     */
    protected function serveDocument(string $slug, string $uuid, bool $preview): Response
    {
        $event = Event::published()
            ->whereNotIn('status', [Event::STATUS_DRAFT])
            ->where('slug', $slug)
            ->firstOrFail();

        $document = EventDocument::where('uuid', $uuid)
            ->where('event_id', $event->id)
            ->published()
            ->firstOrFail();

        $filePath = $document->file_path;

        // Path traversal guard
        if (! str_starts_with($filePath, 'private/event_documents/') || str_contains($filePath, '..')) {
            abort(404, __('portal.documents.file_not_found'));
        }

        if (! Storage::disk('local')->exists($filePath)) {
            abort(404, __('portal.documents.file_not_found'));
        }

        $fullPath = Storage::disk('local')->path($filePath);
        $mime = $document->mime_type ?: 'application/octet-stream';
        $fileName = $document->file_name ?: 'document.pdf';

        if ($preview && in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])) {
            return response()->file($fullPath, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.addslashes($fileName).'"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return Storage::disk('local')->download($filePath, $fileName, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
