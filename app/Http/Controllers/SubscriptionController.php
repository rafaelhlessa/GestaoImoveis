<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // GET /plans
    public function showPlans()
    {
        $plans = SubscriptionPlan::orderBy('price_monthly')->get();
        return Inertia::render('Subscriptions/Plans', compact('plans'));
    }

    // POST /subscribe
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'interval' => 'required|in:monthly,yearly',
        ]);

        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        $user = Auth::user();

        $gatewaySubscriptionId = app('App\Services\SubscriptionGateway')
            ->createSubscription($user, $plan, $request->interval);

        Subscription::create([
            'user_id'                 => $user->id,
            'plan_id'                 => $plan->id,
            'gateway_subscription_id' => $gatewaySubscriptionId,
            'status'                  => 'active',
            'starts_at'               => now(),
            'ends_at'                 => $request->interval === 'monthly'
                                          ? now()->addMonth()
                                          : now()->addYear(),
        ]);

        return redirect()->route('subscriptions.plans')
                         ->with('success', 'Assinatura ativa.');
    }

    // POST /subscription/webhook
    public function webhook(Request $request)
    {
        $payload = $request->all();
        $eventType = $payload['type'] ?? null;
        $subId     = $payload['data']['id'] ?? null;

        if ($eventType === 'subscription.updated') {
            $subscription = Subscription::where('gateway_subscription_id', $subId)->first();
            if ($subscription) {
                $subscription->update([
                    'status'  => $payload['data']['status'],
                    'ends_at' => $payload['data']['current_period_end'],
                ]);
            }
        }

        return response()->json(['received' => true]);
    }

    // POST /subscription/cancel
    public function cancel(Request $request)
    {
        $user = Auth::user();
        $subscription = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest('starts_at')
            ->firstOrFail();

        app('App\Services\SubscriptionGateway')
            ->cancelSubscription($subscription->gateway_subscription_id);

        $subscription->update([
            'status'  => 'canceled',
            'ends_at' => now(),
        ]);

        return redirect()->route('subscriptions.plans')
                         ->with('success', 'Assinatura cancelada.');
    }
}
