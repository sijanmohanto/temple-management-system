<?php

namespace App\Services;

use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\MonthlyCommitteeContribution;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MonthlyContributionService
{
    /**
     * Create an individual monthly committee contribution obligation.
     *
     * @throws ValidationException
     */
    public function createContribution(int $membershipId, string|Carbon $month, float $expectedAmount, ?string $notes, int $userId): MonthlyCommitteeContribution
    {
        $membership = CommitteeMembership::findOrFail($membershipId);
        $monthDate = ($month instanceof Carbon ? $month : Carbon::parse($month))->startOfMonth()->toDateString();

        if ($expectedAmount <= 0.0) {
            throw ValidationException::withMessages([
                'expected_amount' => __('donations.expected_amount_must_be_positive'),
            ]);
        }

        $exists = MonthlyCommitteeContribution::where('committee_membership_id', $membershipId)
            ->whereDate('contribution_month', $monthDate)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'contribution_month' => __('donations.contribution_already_exists_for_month'),
            ]);
        }

        return DB::transaction(function () use ($membership, $monthDate, $expectedAmount, $notes, $userId) {
            $code = MonthlyCommitteeContribution::generateContributionCode($monthDate);

            $contribution = MonthlyCommitteeContribution::create([
                'contribution_code' => $code,
                'committee_membership_id' => $membership->id,
                'contribution_month' => $monthDate,
                'expected_amount' => $expectedAmount,
                'status' => MonthlyCommitteeContribution::STATUS_UNPAID,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            AuditLogger::log(
                'committee_contribution_created',
                "Created monthly contribution {$contribution->contribution_code} for {$contribution->month_name}",
                $contribution,
                $contribution->toArray()
            );

            return $contribution;
        });
    }

    /**
     * Bulk generate monthly obligations for all active members in a committee for a month.
     *
     * @return array{created: int, skipped: int}
     *
     * @throws ValidationException
     */
    public function generateForCommittee(Committee $committee, string|Carbon $month, float $defaultExpectedAmount, int $userId): array
    {
        $monthDate = ($month instanceof Carbon ? $month : Carbon::parse($month))->startOfMonth()->toDateString();

        if ($defaultExpectedAmount <= 0.0) {
            throw ValidationException::withMessages([
                'default_expected_amount' => __('donations.expected_amount_must_be_positive'),
            ]);
        }

        $activeMemberships = $committee->memberships()->where('status', 'active')->get();
        if ($activeMemberships->isEmpty()) {
            throw ValidationException::withMessages([
                'committee_id' => __('donations.no_active_members_in_committee'),
            ]);
        }

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($activeMemberships, $monthDate, $defaultExpectedAmount, $userId, &$created, &$skipped) {
            foreach ($activeMemberships as $membership) {
                $alreadyExists = MonthlyCommitteeContribution::where('committee_membership_id', $membership->id)
                    ->whereDate('contribution_month', $monthDate)
                    ->exists();

                if ($alreadyExists) {
                    $skipped++;

                    continue;
                }

                $code = MonthlyCommitteeContribution::generateContributionCode($monthDate);
                MonthlyCommitteeContribution::create([
                    'contribution_code' => $code,
                    'committee_membership_id' => $membership->id,
                    'contribution_month' => $monthDate,
                    'expected_amount' => $defaultExpectedAmount,
                    'status' => MonthlyCommitteeContribution::STATUS_UNPAID,
                    'created_by' => $userId,
                ]);

                $created++;
            }
        });

        AuditLogger::log(
            'committee_contributions_bulk_generated',
            "Generated {$created} monthly contributions for {$committee->name_en} for month {$monthDate} (Skipped: {$skipped})",
            $committee,
            ['month' => $monthDate, 'created' => $created, 'skipped' => $skipped]
        );

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Waive a monthly committee contribution.
     */
    public function waiveContribution(MonthlyCommitteeContribution $contribution, string $reason, int $userId): bool
    {
        if ($contribution->status === MonthlyCommitteeContribution::STATUS_WAIVED) {
            return true;
        }

        if ($contribution->verified_paid_amount > 0.0) {
            throw ValidationException::withMessages([
                'contribution' => __('donations.cannot_waive_contribution_with_payments'),
            ]);
        }

        return DB::transaction(function () use ($contribution, $reason, $userId) {
            $contribution->update([
                'status' => MonthlyCommitteeContribution::STATUS_WAIVED,
                'waived_by' => $userId,
                'waived_at' => now(),
                'waiver_reason' => $reason,
            ]);

            AuditLogger::log(
                'committee_contribution_waived',
                "Waived monthly contribution {$contribution->contribution_code}. Reason: {$reason}",
                $contribution,
                ['reason' => $reason]
            );

            return true;
        });
    }
}
