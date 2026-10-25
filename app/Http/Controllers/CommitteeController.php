<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommitteeMembershipRequest;
use App\Http\Requests\StoreCommitteeRequest;
use App\Http\Requests\UpdateCommitteeMembershipRequest;
use App\Http\Requests\UpdateCommitteeRequest;
use App\Models\Classification;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\Person;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CommitteeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_people', only: ['index', 'show']),
            new Middleware('permission:manage_committee', except: ['index', 'show']),
        ];
    }

    /**
     * Display a listing of committees.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $query = Committee::query()
            ->withCount([
                'memberships as active_members_count' => function ($q) {
                    $q->where('status', 'active');
                },
                'memberships as total_members_count',
            ]);

        if (! empty($status) && in_array($status, ['active', 'inactive', 'dissolved'])) {
            $query->where('status', $status);
        }

        $committees = $query->latest('term_start_date')->paginate(12)->withQueryString();

        return view('committees.index', compact('committees', 'status'));
    }

    /**
     * Show form for creating a new committee.
     */
    public function create(): View
    {
        return view('committees.create');
    }

    /**
     * Store a newly created committee.
     */
    public function store(StoreCommitteeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $committee = DB::transaction(function () use ($validated) {
            if (empty($validated['code'])) {
                $validated['code'] = Committee::generateCommitteeCode();
            }

            $committee = Committee::create($validated);

            AuditLogger::log(
                'create_committee',
                "Created committee {$committee->name_en} ({$committee->code})",
                $committee
            );

            return $committee;
        });

        return redirect()->route('committees.show', $committee)
            ->with('success', __('committees.created_successfully'));
    }

    /**
     * Display committee details, active members, and historical records.
     */
    public function show(Committee $committee): View
    {
        $committee->load([
            'memberships' => function ($q) {
                $q->with('person')->orderBy('status')->orderBy('designation_en');
            },
        ]);

        $activeMemberships = $committee->memberships->where('status', 'active');
        $historicalMemberships = $committee->memberships->where('status', '!=', 'active');

        // Existing people available for selection (prevent duplicate person creation!)
        $availablePeople = Person::active()->orderBy('name_en')->get();

        return view('committees.show', compact('committee', 'activeMemberships', 'historicalMemberships', 'availablePeople'));
    }

    /**
     * Show form for editing the committee.
     */
    public function edit(Committee $committee): View
    {
        return view('committees.edit', compact('committee'));
    }

    /**
     * Update the specified committee.
     */
    public function update(UpdateCommitteeRequest $request, Committee $committee): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($committee, $validated) {
            $committee->update($validated);

            AuditLogger::log(
                'update_committee',
                "Updated committee {$committee->name_en} ({$committee->code})",
                $committee
            );
        });

        return redirect()->route('committees.show', $committee)
            ->with('success', __('committees.updated_successfully'));
    }

    /**
     * Add an existing person to the committee.
     */
    public function addMember(StoreCommitteeMembershipRequest $request, Committee $committee): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $committee) {
            $membership = CommitteeMembership::create($validated);

            // Automatically ensure the person has the 'committee_member' classification
            $person = Person::find($validated['person_id']);
            $committeeClass = Classification::where('slug', 'committee_member')->first();
            if ($person && $committeeClass) {
                $person->assignClassification($committeeClass, "Assigned to {$committee->name_en}");
            }

            AuditLogger::log(
                'add_committee_member',
                "Assigned {$person->name_en} as {$membership->designation_en} to {$committee->name_en}",
                $committee,
                ['person_id' => $person->id, 'membership_id' => $membership->id]
            );
        });

        return redirect()->route('committees.show', $committee)
            ->with('success', __('committees.member_added_successfully'));
    }

    /**
     * Update membership designation or term dates.
     */
    public function updateMember(UpdateCommitteeMembershipRequest $request, Committee $committee, CommitteeMembership $membership): RedirectResponse
    {
        if ($membership->committee_id !== $committee->id) {
            abort(404);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($membership, $validated, $committee) {
            $membership->update($validated);

            AuditLogger::log(
                'update_committee_member',
                "Updated membership details for {$membership->person->name_en} in {$committee->name_en}",
                $committee,
                ['membership_id' => $membership->id]
            );
        });

        return redirect()->route('committees.show', $committee)
            ->with('success', __('committees.member_updated_successfully'));
    }

    /**
     * End a committee membership, preserving history without deleting records.
     */
    public function endMembership(Request $request, Committee $committee, CommitteeMembership $membership): RedirectResponse
    {
        if ($membership->committee_id !== $committee->id) {
            abort(404);
        }

        $request->validate([
            'end_date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:ended,resigned'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($membership, $request, $committee) {
            $membership->endMembership(
                $request->end_date,
                $request->status,
                $request->notes
            );

            AuditLogger::log(
                'end_committee_member',
                "Concluded membership for {$membership->person->name_en} in {$committee->name_en}",
                $committee,
                ['membership_id' => $membership->id, 'status' => $request->status]
            );
        });

        return redirect()->route('committees.show', $committee)
            ->with('success', __('committees.membership_ended_successfully'));
    }
}
