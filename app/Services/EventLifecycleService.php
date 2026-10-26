<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventLifecycleLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\ValidationException;

class EventLifecycleService
{
    /**
     * Allowed status transitions map.
     */
    protected array $allowedTransitions = [
        Event::STATUS_DRAFT => [
            Event::STATUS_UPCOMING,
            Event::STATUS_ACTIVE,
        ],
        Event::STATUS_UPCOMING => [
            Event::STATUS_ACTIVE,
            Event::STATUS_DRAFT,
        ],
        Event::STATUS_ACTIVE => [
            Event::STATUS_CLOSING,
            Event::STATUS_CLOSED,
        ],
        Event::STATUS_CLOSING => [
            Event::STATUS_CLOSED,
            Event::STATUS_ACTIVE,
        ],
        Event::STATUS_CLOSED => [
            // Closed events can only transition via explicit reopenEvent() workflow
        ],
    ];

    /**
     * Permitted metadata fields for post-closing adjustments in Mode B.
     * Core structural keys (code, year, category, status) are strictly immutable.
     */
    protected array $permittedPostClosingFields = [
        'description_en',
        'description_bn',
        'venue',
    ];

    /**
     * Validate if a status transition is allowed.
     */
    public function canTransition(Event $event, string $targetStatus): bool
    {
        if ($event->status === $targetStatus) {
            return true;
        }

        $allowed = $this->allowedTransitions[$event->status] ?? [];

        return in_array($targetStatus, $allowed, true);
    }

    /**
     * Transition event status.
     *
     * @throws ValidationException
     */
    public function transitionStatus(Event $event, string $targetStatus, ?string $reason = null): bool
    {
        if ($targetStatus === Event::STATUS_CLOSED) {
            throw ValidationException::withMessages([
                'status' => __('events.must_use_closing_workflow'),
            ]);
        }

        if (! $this->canTransition($event, $targetStatus)) {
            throw ValidationException::withMessages([
                'status' => __('events.invalid_status_transition', [
                    'from' => __('events.status_'.$event->status),
                    'to' => __('events.status_'.$targetStatus),
                ]),
            ]);
        }

        if ($event->status === $targetStatus) {
            return true;
        }

        return DB::transaction(function () use ($event, $targetStatus, $reason) {
            $fromStatus = $event->status;

            $event->update([
                'status' => $targetStatus,
                'updated_by' => Auth::id(),
            ]);

            EventLifecycleLog::create([
                'event_id' => $event->id,
                'user_id' => Auth::id(),
                'action' => 'status_changed',
                'from_status' => $fromStatus,
                'to_status' => $targetStatus,
                'closing_mode' => $event->closing_mode,
                'reason' => $reason,
                'ip_address' => Request::ip(),
            ]);

            AuditLogger::log(
                'event_status_changed',
                "Event {$event->name_en} ({$event->event_code}) status changed from {$fromStatus} to {$targetStatus}",
                $event,
                [
                    'from' => $fromStatus,
                    'to' => $targetStatus,
                    'reason' => $reason,
                ]
            );

            return true;
        });
    }

    /**
     * Close an event with selected closing mode.
     *
     * @throws ValidationException
     */
    public function closeEvent(Event $event, string $closingMode, string $reason): bool
    {
        if ($event->isClosed()) {
            throw ValidationException::withMessages([
                'closing_mode' => __('events.event_already_closed'),
            ]);
        }

        if (! in_array($closingMode, [Event::MODE_CLOSE_AND_LOCK, Event::MODE_CLOSE_WITH_ADJUSTMENTS], true)) {
            throw ValidationException::withMessages([
                'closing_mode' => __('events.invalid_closing_mode'),
            ]);
        }

        return DB::transaction(function () use ($event, $closingMode, $reason) {
            $fromStatus = $event->status;

            $event->update([
                'status' => Event::STATUS_CLOSED,
                'closing_mode' => $closingMode,
                'closing_reason' => $reason,
                'closed_by' => Auth::id(),
                'closed_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            EventLifecycleLog::create([
                'event_id' => $event->id,
                'user_id' => Auth::id(),
                'action' => 'closed',
                'from_status' => $fromStatus,
                'to_status' => Event::STATUS_CLOSED,
                'closing_mode' => $closingMode,
                'reason' => $reason,
                'ip_address' => Request::ip(),
            ]);

            AuditLogger::log(
                'event_closed',
                "Closed event {$event->name_en} ({$event->event_code}) with mode [{$closingMode}]. Reason: {$reason}",
                $event,
                [
                    'closing_mode' => $closingMode,
                    'reason' => $reason,
                ]
            );

            return true;
        });
    }

    /**
     * Reopen a closed event.
     * Preserves previous closing details in audit log.
     *
     * @throws ValidationException
     */
    public function reopenEvent(Event $event, string $targetStatus, string $reason): bool
    {
        if (! $event->isClosed()) {
            throw ValidationException::withMessages([
                'target_status' => __('events.event_not_closed'),
            ]);
        }

        if (! in_array($targetStatus, [Event::STATUS_ACTIVE, Event::STATUS_UPCOMING], true)) {
            throw ValidationException::withMessages([
                'target_status' => __('events.invalid_reopen_target_status'),
            ]);
        }

        return DB::transaction(function () use ($event, $targetStatus, $reason) {
            $prevClosingMode = $event->closing_mode;
            $prevClosingReason = $event->closing_reason;
            $prevClosedAt = $event->closed_at;
            $prevClosedBy = $event->closed_by;

            $event->update([
                'status' => $targetStatus,
                'closing_mode' => null,
                'closing_reason' => null,
                'closed_by' => null,
                'closed_at' => null,
                'reopened_by' => Auth::id(),
                'reopened_at' => now(),
                'reopen_reason' => $reason,
                'updated_by' => Auth::id(),
            ]);

            EventLifecycleLog::create([
                'event_id' => $event->id,
                'user_id' => Auth::id(),
                'action' => 'reopened',
                'from_status' => Event::STATUS_CLOSED,
                'to_status' => $targetStatus,
                'closing_mode' => $prevClosingMode,
                'reason' => $reason,
                'changes' => [
                    'previous_closing_mode' => $prevClosingMode,
                    'previous_closing_reason' => $prevClosingReason,
                    'previous_closed_at' => $prevClosedAt?->toIso8601String(),
                    'previous_closed_by' => $prevClosedBy,
                ],
                'ip_address' => Request::ip(),
            ]);

            AuditLogger::log(
                'event_reopened',
                "Reopened closed event {$event->name_en} ({$event->event_code}) to [{$targetStatus}]. Reason: {$reason}",
                $event,
                [
                    'target_status' => $targetStatus,
                    'reason' => $reason,
                    'previous_closing_mode' => $prevClosingMode,
                ]
            );

            return true;
        });
    }

    /**
     * Perform authorized post-closing metadata adjustment in Mode B.
     *
     * @throws ValidationException
     */
    public function adjustClosedEvent(Event $event, array $attributes, string $reason): bool
    {
        if (! $event->isClosed()) {
            throw ValidationException::withMessages([
                'adjustment' => __('events.adjustment_only_on_closed_events'),
            ]);
        }

        if ($event->isLocked()) {
            throw ValidationException::withMessages([
                'adjustment' => __('events.event_locked_adjustments_prohibited'),
            ]);
        }

        if (! $event->allowsAuthorizedAdjustments()) {
            throw ValidationException::withMessages([
                'adjustment' => __('events.adjustments_not_permitted_for_mode'),
            ]);
        }

        // Filter only permitted metadata attributes
        $filtered = array_intersect_key($attributes, array_flip($this->permittedPostClosingFields));

        $changes = [];
        foreach ($filtered as $key => $newValue) {
            $oldValue = $event->{$key};
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        if (empty($changes)) {
            return true; // No changes to persist
        }

        return DB::transaction(function () use ($event, $filtered, $changes, $reason) {
            $filtered['updated_by'] = Auth::id();
            $event->update($filtered);

            EventLifecycleLog::create([
                'event_id' => $event->id,
                'user_id' => Auth::id(),
                'action' => 'post_close_adjusted',
                'from_status' => Event::STATUS_CLOSED,
                'to_status' => Event::STATUS_CLOSED,
                'closing_mode' => $event->closing_mode,
                'reason' => $reason,
                'changes' => $changes,
                'ip_address' => Request::ip(),
            ]);

            AuditLogger::log(
                'event_post_close_adjusted',
                "Authorized adjustment on closed event {$event->name_en} ({$event->event_code}). Reason: {$reason}",
                $event,
                [
                    'reason' => $reason,
                    'changes' => $changes,
                ]
            );

            return true;
        });
    }
}
