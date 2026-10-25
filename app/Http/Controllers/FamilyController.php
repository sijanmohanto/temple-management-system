<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFamilyRequest;
use App\Http\Requests\UpdateFamilyRequest;
use App\Models\Family;
use App\Models\Person;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FamilyController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_people', only: ['index', 'show']),
            new Middleware('permission:manage_people', except: ['index', 'show']),
        ];
    }

    /**
     * Display a listing of families.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');

        $families = Family::query()
            ->with(['primaryContact'])
            ->withCount('members')
            ->search($search)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('families.index', compact('families', 'search'));
    }

    /**
     * Show form for creating a new family.
     */
    public function create(): View
    {
        $people = Person::active()->orderBy('name_en')->get();

        return view('families.create', compact('people'));
    }

    /**
     * Store a newly created family.
     */
    public function store(StoreFamilyRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $family = DB::transaction(function () use ($validated) {
            $familyCode = Family::generateFamilyCode();
            $validated['family_code'] = $familyCode;

            $family = Family::create($validated);

            // If a primary contact was selected and is not yet in this family, associate them
            if (! empty($validated['primary_contact_person_id'])) {
                $primaryContact = Person::find($validated['primary_contact_person_id']);
                if ($primaryContact && ! $primaryContact->family_id) {
                    $primaryContact->update([
                        'family_id' => $family->id,
                        'family_relationship' => 'head',
                    ]);
                }
            }

            AuditLogger::log(
                'create_family',
                "Created family {$family->family_name} ({$family->family_code})",
                $family
            );

            return $family;
        });

        return redirect()->route('families.show', $family)
            ->with('success', __('families.created_successfully'));
    }

    /**
     * Display family details and members.
     */
    public function show(Family $family): View
    {
        $family->load(['primaryContact', 'members.classifications']);

        // Eligible candidates to be added as members: people with no family or in this family
        $unassignedPeople = Person::active()
            ->whereNull('family_id')
            ->orderBy('name_en')
            ->get();

        return view('families.show', compact('family', 'unassignedPeople'));
    }

    /**
     * Show form for editing the family.
     */
    public function edit(Family $family): View
    {
        $family->load('members');
        // Potential primary contacts include current family members or all people
        $people = Person::active()->orderBy('name_en')->get();

        return view('families.edit', compact('family', 'people'));
    }

    /**
     * Update the family.
     */
    public function update(UpdateFamilyRequest $request, Family $family): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($family, $validated) {
            $family->update($validated);

            // If a primary contact was designated, ensure they belong to this family as head
            if (! empty($validated['primary_contact_person_id'])) {
                $contact = Person::find($validated['primary_contact_person_id']);
                if ($contact && $contact->family_id !== $family->id) {
                    $contact->update([
                        'family_id' => $family->id,
                        'family_relationship' => $contact->family_relationship ?: 'head',
                    ]);
                }
            }

            AuditLogger::log(
                'update_family',
                "Updated family {$family->family_name} ({$family->family_code})",
                $family
            );
        });

        return redirect()->route('families.show', $family)
            ->with('success', __('families.updated_successfully'));
    }

    /**
     * Assign an existing person to this family.
     */
    public function addMember(Request $request, Family $family): RedirectResponse
    {
        $request->validate([
            'person_id' => ['required', 'exists:people,id'],
            'family_relationship' => ['nullable', 'string', 'max:50'],
        ]);

        $person = Person::findOrFail($request->person_id);

        $person->update([
            'family_id' => $family->id,
            'family_relationship' => $request->family_relationship ?: 'member',
        ]);

        // If family doesn't have a primary contact, or relationship is head, set as primary contact
        if (! $family->primary_contact_person_id || $request->family_relationship === 'head') {
            $family->update(['primary_contact_person_id' => $person->id]);
        }

        AuditLogger::log(
            'add_family_member',
            "Assigned {$person->name_en} to family {$family->family_name}",
            $family,
            ['person_id' => $person->id, 'relationship' => $request->family_relationship]
        );

        return redirect()->route('families.show', $family)
            ->with('success', __('families.member_added_successfully'));
    }

    /**
     * Remove / disassociate a member from this family.
     */
    public function removeMember(Family $family, Person $person): RedirectResponse
    {
        if ($person->family_id !== $family->id) {
            return redirect()->back()->with('error', __('families.not_a_member'));
        }

        DB::transaction(function () use ($family, $person) {
            $person->update([
                'family_id' => null,
                'family_relationship' => null,
            ]);

            // If this was the primary contact, clear it
            if ($family->primary_contact_person_id === $person->id) {
                $newPrimary = $family->members()->where('id', '!=', $person->id)->first();
                $family->update(['primary_contact_person_id' => $newPrimary?->id]);
            }

            AuditLogger::log(
                'remove_family_member',
                "Disassociated {$person->name_en} from family {$family->family_name}",
                $family,
                ['person_id' => $person->id]
            );
        });

        return redirect()->route('families.show', $family)
            ->with('success', __('families.member_removed_successfully'));
    }

    /**
     * Delete or safely deactivate a family.
     * Prevents unsafe deletion if family has members!
     */
    public function destroy(Family $family): RedirectResponse
    {
        if (! $family->canBeSafelyDeleted()) {
            return redirect()->back()->with('error', __('families.cannot_delete_has_members', [
                'count' => $family->members()->count(),
            ]));
        }

        DB::transaction(function () use ($family) {
            AuditLogger::log(
                'delete_family',
                "Deleted family {$family->family_name} ({$family->family_code})",
                $family
            );

            $family->delete();
        });

        return redirect()->route('families.index')
            ->with('success', __('families.deleted_successfully'));
    }
}
