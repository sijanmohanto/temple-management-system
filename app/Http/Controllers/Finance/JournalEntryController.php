<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\JournalEntry;
use App\Services\JournalEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalEntryController extends Controller
{
    public function __construct(
        protected JournalEntryService $journalService
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('view_journals');

        if ($request->ajax()) {
            $query = JournalEntry::with(['event', 'creator']);

            if ($request->filled('source_type')) {
                $query->where('source_type', $request->get('source_type'));
            }
            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }
            if ($request->filled('event_id')) {
                $query->where('event_id', $request->get('event_id'));
            }
            if ($request->filled('start_date')) {
                $query->where('entry_date', '>=', $request->get('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->where('entry_date', '<=', $request->get('end_date'));
            }

            $totalRecords = $query->count();

            // Search
            if ($request->filled('search.value')) {
                $search = $request->input('search.value');
                $query->where(function ($q) use ($search) {
                    $q->where('journal_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $filteredRecords = $query->count();

            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $orderColIdx = (int) $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'desc');

            $columns = ['journal_number', 'entry_date', 'description', 'source_type', 'total_debit', 'status', 'created_at'];
            $orderCol = $columns[$orderColIdx] ?? 'entry_date';

            $entries = $query->orderBy($orderCol, $orderDir)
                ->skip($start)
                ->take($length)
                ->get();

            $data = $entries->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'journal_number' => $entry->journal_number,
                    'entry_date' => $entry->entry_date->toDateString(),
                    'description' => $entry->description,
                    'source_type' => ucfirst(str_replace('_', ' ', $entry->source_type)),
                    'event_title' => $entry->event?->title_en,
                    'total_debit' => number_format($entry->total_debit, 2),
                    'total_credit' => number_format($entry->total_credit, 2),
                    'status' => $entry->status,
                    'view_url' => route('finance.journals.show', $entry),
                ];
            });

            return response()->json([
                'draw' => (int) $request->input('draw'),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        $events = Event::orderBy('start_date', 'desc')->get();

        return view('finance.journals.index', compact('events'));
    }

    public function show(JournalEntry $journal): View
    {
        $this->authorize('view_journals');

        $journal->load(['lines.account', 'event', 'creator', 'poster', 'reversedByJournal', 'reversedJournal']);

        return view('finance.journals.show', compact('journal'));
    }

    public function reverse(Request $request, JournalEntry $journal): RedirectResponse|JsonResponse
    {
        $this->authorize('reverse_transactions');

        $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $reversal = $this->journalService->createReversal(
            $journal,
            auth()->id(),
            $request->input('reason')
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('finance.journal_reversed_successfully'),
                'reversal' => $reversal,
            ]);
        }

        return redirect()->route('finance.journals.show', $reversal)
            ->with('success', __('finance.journal_reversed_successfully'));
    }
}
