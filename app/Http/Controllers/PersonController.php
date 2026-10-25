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
            new Middleware('permission:view_people', only: ['index', 'show']),
            new Middleware('permission:manage_people', except: ['index', 'show', 'checkDuplicates']),
        ];
    }

    /**
     * Display a paginated listing of people with filters.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'classification', 'status', 'family_id', 'gotra']);

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
