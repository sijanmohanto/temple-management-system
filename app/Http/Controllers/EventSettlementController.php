<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettleEventFundsRequest;
use App\Models\Event;
use App\Services\EventSettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class EventSettlementController extends Controller implements HasMiddleware
{
    public function __construct(
        protected EventSettlementService $settlementService
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:close_events|manage_events'),
        ];
    }

    /**
     * Execute event fund settlement and surplus transfer.
     */
    public function settle(SettleEventFundsRequest $request, Event $event): RedirectResponse
    {
        $settlement = $this->settlementService->settleEvent(
            $event,
            $request->validated(),
            Auth::id()
        );

        return redirect()
            ->route('events.show', $event)
            ->with('success', __('events.settlement_success_message', [
                'number' => $settlement->settlement_number,
                'amount' => number_format((float) $settlement->surplus_amount, 2),
            ]));
    }
}
