<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonRequest;
use App\Http\Requests\UpdatePersonRequest;
use App\Models\Classification;
use App\Models\Family;
use App\Models\Person;
use App\Services\AuditLogger;
use App\Services\DuplicateDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PersonController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_people', only: ['index', 'show', 'search']),
            new Middleware('permission:manage_people', except: ['index', 'show', 'checkDuplicates', 'search']),
        ];
    }

    /**
     * Display a paginated listing of people with filters.
     */
    public function index(Request $request): View|JsonResponse
    {
        $rawSearch = $request->input('search');
        $searchString = is_array($rawSearch) ? ($rawSearch['value'] ?? '') : (is_string($rawSearch) ? $rawSearch : '');
        $searchString = trim((string) $searchString);

        $filters = $request->only(['classification', 'status', 'family_id', 'gotra']);
        if (! empty($searchString)) {
            $filters['search'] = $searchString;
        }

        if (($request->ajax() || $request->wantsJson()) && $request->has('draw')) {
            return $this->dataTableResponse($request, $filters);
        }

        $people = Person::query()
            ->with(['family', 'classifications'])
            ->filter($filters)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $classifications = Classification::all();
        $families = Family::active()->orderBy('family_name')->get();

        return view('people.index', compact('people', 'filters', 'classifications', 'families'));
    }

    /**
     * Server-side DataTables JSON response.
     */
    protected function dataTableResponse(Request $request, array $filters): JsonResponse
    {
        $draw = $request->integer('draw');
        $start = $request->integer('start', 0);
        $length = $request->integer('length', 15);
        $searchValue = trim($request->input('search.value', ''));

        $query = Person::query()->with(['family', 'classifications']);
        $totalRecords = Person::count();

        // Filters applied, explicitly omitting 'search' as $searchValue handles DataTables search
        unset($filters['search']);
        $query->filter($filters);

        if (! empty($searchValue)) {
            $query->search($searchValue);
        }

        $filteredRecords = $query->count();

        $orderColumnIndex = $request->has('order.0.column') ? (int) $request->input('order.0.column') : null;
        $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $columnMap = [
            0 => 'person_code',
            1 => 'name_en',
            2 => 'phone',
            5 => 'status',
        ];

        if ($orderColumnIndex !== null && isset($columnMap[$orderColumnIndex])) {
            $query->orderBy($columnMap[$orderColumnIndex], $orderDir);
        } else {
            $query->latest();
        }

        $records = $query->skip($start)->take($length)->get();
        $canManage = $request->user()?->hasPermission('manage_people') ?? false;

        $data = $records->map(function ($person) use ($canManage) {
            $codeHtml = '<span class="badge bg-light text-dark border font-monospace">'.e($person->person_code).'</span>';

            $nameHtml = '<div class="fw-bold text-dark">'.e($person->name_en).'</div>';
            if ($person->name_bn) {
                $nameHtml .= '<small class="text-muted">'.e($person->name_bn).'</small>';
            }
            if ($person->gotra) {
                $nameHtml .= '<div><span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.72rem;">'.e($person->gotra).'</span></div>';
            }

            $phoneHtml = '<div>'.e($person->phone ?? '—').'</div>';
            if ($person->email) {
                $phoneHtml .= '<small class="text-muted">'.e($person->email).'</small>';
            }

            $classHtml = '<div class="d-flex flex-wrap gap-1">';
            if ($person->classifications->isEmpty()) {
                $classHtml .= '<span class="text-muted small">—</span>';
            } else {
                foreach ($person->classifications as $c) {
                    $color = match ($c->slug) {
                        'resident' => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                        'donor' => 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25',
                        'committee_member' => 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25',
                        'volunteer' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                        default => 'bg-secondary bg-opacity-10 text-secondary',
                    };
                    $classHtml .= '<span class="badge '.$color.'" style="font-size: 0.72rem;">'.e($c->name).'</span>';
                }
            }
            $classHtml .= '</div>';

            $familyHtml = '<span class="text-muted small">'.__('people.no_family_assigned').'</span>';
            if ($person->family) {
                $familyHtml = '<a href="'.route('families.show', $person->family).'" class="text-decoration-none fw-semibold text-primary"><i class="bi bi-house me-1"></i> '.e($person->family->family_name).'</a>';
                if ($person->family_relationship) {
                    $familyHtml .= '<div><span class="badge bg-light text-secondary border" style="font-size: 0.7rem;">'.e(__('people.relationship_'.$person->family_relationship)).'</span></div>';
                }
            }

            $statusHtml = $person->isActive()
                ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1"><i class="bi bi-check-circle me-1"></i> '.__('common.status_active').'</span>'
                : '<span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1"><i class="bi bi-slash-circle me-1"></i> '.__('common.status_inactive').'</span>';

            $actionsHtml = '<div class="btn-group btn-group-sm text-end">'
                .'<a href="'.route('people.show', $person).'" class="btn btn-outline-primary" title="'.__('common.view').'"><i class="bi bi-eye"></i></a>';

            if ($canManage) {
                $toggleConfirm = $person->isActive() ? __('people.deactivate_confirm') : __('people.reactivate_confirm');
                $toggleTitle = $person->isActive() ? __('people.deactivate') : __('people.reactivate');
                $toggleClass = $person->isActive() ? 'btn-outline-warning' : 'btn-outline-success';

                $actionsHtml .= '<a href="'.route('people.edit', $person).'" class="btn btn-outline-secondary" title="'.__('common.edit').'"><i class="bi bi-pencil"></i></a>'
                    .'<form method="POST" action="'.route('people.toggle-status', $person).'" class="d-inline" onsubmit="return confirm(\''.e($toggleConfirm).'\')">'
                    .csrf_field()
                    .'<button type="submit" class="btn '.$toggleClass.'" title="'.e($toggleTitle).'"><i class="bi bi-power"></i></button>'
                    .'</form>';
            }
            $actionsHtml .= '</div>';

            return [
                $codeHtml,
                $nameHtml,
                $phoneHtml,
                $classHtml,
                $familyHtml,
                $statusHtml,
                $actionsHtml,
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Show form for creating a new person.
     */
    public function create(): View
    {
        $families = Family::active()->orderBy('family_name')->get();
        $classifications = Classification::all();

        return view('people.create', compact('families', 'classifications'));
    }

    /**
     * Store a newly created person in storage.
     */
    public function store(StorePersonRequest $request, DuplicateDetectionService $dupService): RedirectResponse
    {
        $validated = $request->validated();

        // Duplicate detection warning check
        if (! $request->boolean('confirm_duplicate')) {
            $duplicateCheck = $dupService->findDuplicates($validated);
            if ($duplicateCheck['has_duplicates']) {
                return redirect()->back()
                    ->withInput()
                    ->with('warning_duplicates', $duplicateCheck['matches'])
                    ->with('warning', __('people.duplicate_detected_warning'));
            }
        }

        $person = DB::transaction(function () use ($validated, $request) {
            $personCode = Person::generatePersonCode();

            $personData = collect($validated)->except(['classifications', 'confirm_duplicate'])->toArray();
            $personData['person_code'] = $personCode;
            $personData['created_by'] = $request->user()->id;
            $personData['updated_by'] = $request->user()->id;

            $person = Person::create($personData);

            if (! empty($validated['classifications'])) {
                foreach ($validated['classifications'] as $classificationId) {
                    $person->assignClassification($classificationId);
                }
            }

            AuditLogger::log(
                'create_person',
                "Created person {$person->name_en} ({$person->person_code})",
                $person,
                ['person_code' => $person->person_code, 'name' => $person->name_en]
            );

            return $person;
        });

        return redirect()->route('people.show', $person)
            ->with('success', __('people.created_successfully'));
    }

    /**
     * Display the specified person profile.
     */
    public function show(Person $person, DuplicateDetectionService $dupService): View
    {
        $person->load([
            'family.members',
            'classifications',
            'committeeMemberships.committee',
            'creator',
            'updater',
        ]);

        $duplicateCheck = $dupService->findDuplicates([
            'phone' => $person->phone,
            'email' => $person->email,
            'name_en' => $person->name_en,
            'name_bn' => $person->name_bn,
            'date_of_birth' => $person->date_of_birth?->toDateString(),
            'father_name' => $person->father_name,
        ], $person->id);

        return view('people.show', compact('person', 'duplicateCheck'));
    }

    /**
     * Show form for editing the person.
     */
    public function edit(Person $person): View
    {
        $person->load('classifications', 'family');
        $families = Family::active()->orderBy('family_name')->get();
        $classifications = Classification::all();

        return view('people.edit', compact('person', 'families', 'classifications'));
    }

    /**
     * Update the specified person in storage.
     */
    public function update(UpdatePersonRequest $request, Person $person): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($person, $validated, $request) {
            $personData = collect($validated)->except(['classifications'])->toArray();
            $personData['updated_by'] = $request->user()->id;

            $person->update($personData);

            if (isset($validated['classifications'])) {
                $attachData = [];
                foreach ($validated['classifications'] as $id) {
                    $attachData[$id] = ['assigned_at' => now()];
                }
                $person->classifications()->sync($attachData);
            } else {
                $person->classifications()->detach();
            }

            AuditLogger::log(
                'update_person',
                "Updated profile for {$person->name_en} ({$person->person_code})",
                $person
            );
        });

        return redirect()->route('people.show', $person)
            ->with('success', __('people.updated_successfully'));
    }

    /**
     * Toggle active/inactive status safely.
     */
    public function toggleStatus(Request $request, Person $person): RedirectResponse
    {
        $newStatus = $person->status === 'active' ? 'inactive' : 'active';
        $person->update([
            'status' => $newStatus,
            'updated_by' => $request->user()->id,
        ]);

        AuditLogger::log(
            'toggle_status_person',
            "Toggled status to {$newStatus} for {$person->name_en} ({$person->person_code})",
            $person,
            ['old_status' => $person->getOriginal('status'), 'new_status' => $newStatus]
        );

        $messageKey = $newStatus === 'active' ? 'people.reactivated_successfully' : 'people.deactivated_successfully';

        return redirect()->back()->with('success', __($messageKey));
    }

    /**
     * AJAX search endpoint for Select2.
     */
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        $people = Person::query()
            ->search($term)
            ->active()
            ->limit(30)
            ->get(['id', 'person_code', 'name_en', 'name_bn']);

        $results = $people->map(function ($person) {
            $name = $person->name;

            return [
                'id' => $person->id,
                'text' => "{$name} ({$person->person_code})",
            ];
        });

        return response()->json(['results' => $results]);
    }

    /**
     * AJAX endpoint to check for duplicate profiles in real-time.
     */
    public function checkDuplicates(Request $request, DuplicateDetectionService $dupService): JsonResponse
    {
        $excludeId = $request->integer('exclude_id');
        $result = $dupService->findDuplicates($request->all(), $excludeId ?: null);

        $formatted = [];
        foreach ($result['matches'] as $match) {
            $formatted[] = [
                'id' => $match['person']->id,
                'person_code' => $match['person']->person_code,
                'name_en' => $match['person']->name_en,
                'name_bn' => $match['person']->name_bn,
                'phone' => $match['person']->phone,
                'email' => $match['person']->email,
                'reasons' => $match['reasons'],
            ];
        }

        return response()->json([
            'has_duplicates' => $result['has_duplicates'],
            'matches' => $formatted,
        ]);
    }
}
