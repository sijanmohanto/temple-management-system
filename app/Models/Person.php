<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Person extends Model
{
    use HasFactory;

    protected $table = 'people';

    protected $fillable = [
        'person_code',
        'name_en',
        'name_bn',
        'phone',
        'email',
        'date_of_birth',
        'gender',
        'blood_group',
        'father_name',
        'mother_name',
        'spouse_name',
        'gotra',
        'occupation',
        'present_address',
        'permanent_address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'family_id',
        'family_relationship',
        'status',
        'private_notes',
        'public_notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    /**
     * Family association.
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    /**
     * Person classifications (Resident, Donor, Committee Member, Volunteer).
     */
    public function classifications(): BelongsToMany
    {
        return $this->belongsToMany(Classification::class, 'person_classifications')
            ->withPivot(['assigned_at', 'notes'])
            ->withTimestamps();
    }

    /**
     * Pledges made by this person.
     */
    public function pledges(): HasMany
    {
        return $this->hasMany(Pledge::class);
    }

    /**
     * Collection payments made by this person.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(CollectionPayment::class, 'payer_person_id');
    }

    /**
     * Committee memberships (both active and historical terms).
     */
    public function committeeMemberships(): HasMany
    {
        return $this->hasMany(CommitteeMembership::class)->orderByDesc('start_date');
    }

    /**
     * Active committee memberships.
     */
    public function activeCommitteeMemberships(): HasMany
    {
        return $this->hasMany(CommitteeMembership::class)->where('status', 'active')->orderByDesc('start_date');
    }

    /**
     * Record creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Record updater.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Localized name accessor.
     */
    public function getNameAttribute(): string
    {
        if (app()->getLocale() === 'bn' && ! empty($this->name_bn)) {
            return $this->name_bn;
        }

        return $this->name_en;
    }

    /**
     * Determine if person is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if person has a specific classification slug.
     */
    public function hasClassification(string $slug): bool
    {
        return $this->classifications->contains('slug', $slug);
    }

    /**
     * Assign a classification, preventing duplicate assignments.
     */
    public function assignClassification(Classification|int|string $classification, ?string $notes = null): void
    {
        $classificationId = $classification instanceof Classification
            ? $classification->id
            : (is_numeric($classification) ? (int) $classification : Classification::where('slug', $classification)->value('id'));

        if ($classificationId && ! $this->classifications->contains('id', $classificationId)) {
            $this->classifications()->attach($classificationId, [
                'assigned_at' => now(),
                'notes' => $notes,
            ]);
            $this->load('classifications');
        }
    }

    /**
     * Determine if a user is permitted to view private notes.
     */
    public function canViewPrivateNotes(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole('super_admin') || $user->hasPermission('manage_people');
    }

    /**
     * Scope for active people.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for searching people.
     */
    public function scopeSearch(Builder $query, string|array|null $term): Builder
    {
        if (is_array($term)) {
            $term = $term['value'] ?? '';
        }

        if (empty($term)) {
            return $query;
        }

        $clean = trim((string) $term);

        return $query->where(function (Builder $q) use ($clean) {
            $q->where('person_code', 'like', "%{$clean}%")
                ->orWhere('name_en', 'like', "%{$clean}%")
                ->orWhere('name_bn', 'like', "%{$clean}%")
                ->orWhere('phone', 'like', "%{$clean}%")
                ->orWhere('email', 'like', "%{$clean}%")
                ->orWhere('gotra', 'like', "%{$clean}%")
                ->orWhere('occupation', 'like', "%{$clean}%");
        });
    }

    /**
     * Filter query by search, classification, status, family.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (! empty($filters['search'])) {
            $searchTerm = is_array($filters['search']) ? ($filters['search']['value'] ?? '') : $filters['search'];
            if (! empty($searchTerm)) {
                $query->search((string) $searchTerm);
            }
        }

        if (! empty($filters['status']) && in_array($filters['status'], ['active', 'inactive'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['classification'])) {
            $query->whereHas('classifications', function (Builder $q) use ($filters) {
                $q->where('slug', $filters['classification'])
                    ->orWhere('classifications.id', $filters['classification']);
            });
        }

        if (! empty($filters['family_id'])) {
            $query->where('family_id', $filters['family_id']);
        }

        if (! empty($filters['gotra'])) {
            $query->where('gotra', 'like', '%'.trim($filters['gotra']).'%');
        }

        return $query;
    }

    /**
     * Generate unique sequential person code: TMP-P-00001
     */
    public static function generatePersonCode(): string
    {
        return DB::transaction(function () {
            $last = static::orderByDesc('id')->lockForUpdate()->first();
            $nextId = $last ? ($last->id + 1) : 1;

            return sprintf('DEV-%05d', $nextId);
        });
    }
}
