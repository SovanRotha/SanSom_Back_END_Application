<?php

namespace App\Http\Controllers\Bill;

use App\Http\Controllers\Controller;
use App\Models\Bill\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    //
    public function index(Request $request)
    {
        $user = $request->user();

        $subscriptions = Subscription::where(
            'user_id',
            $user->id
        )
            ->with(['account', 'category'])
            ->latest('next_payment_date')
            ->get();

        return response()->json([
            'message' => 'Subscriptions retrieved successfully',
            'subscriptions' => $subscriptions
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        $subscription = Subscription::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['account', 'category'])
            ->first();

        if (!$subscription) {
            return response()->json([
                'message' => 'Subscription not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Subscription retrieved successfully',
            'subscription' => $subscription
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',

            'category_id' => 'required|exists:categories,id',

            'name' => 'required|string|max:100',

            'amount' => 'required|numeric|min:0.01',

            'billing_cycle' => 'required|in:daily,weekly,monthly,yearly',

            'next_payment_date' => 'required|date',

            'start_date' => 'required|date',

            'end_date' => 'nullable|date|after_or_equal:start_date',

            'status' => 'nullable|in:active,inactive,cancelled',
        ]);


        // Check account ownership
        $accountExists = $user->accounts()
            ->where('id', $validated['account_id'])
            ->exists();

        if (!$accountExists) {
            return response()->json([
                'message' => 'Account not found'
            ], 404);
        }


        $subscription = Subscription::create([
            'user_id' => $user->id,
            'account_id' => $validated['account_id'],
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'amount' => $validated['amount'],
            'billing_cycle' => $validated['billing_cycle'],
            'next_payment_date' => $validated['next_payment_date'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ]);


        return response()->json([
            'message' => 'Subscription created successfully',
            'subscription' =>
            $subscription->load(['account', 'category'])
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        $subscription = Subscription::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$subscription) {
            return response()->json([
                'message' => 'Subscription not found'
            ], 404);
        }


        $validated = $request->validate([
            'account_id' => 'sometimes|exists:accounts,id',

            'category_id' => 'sometimes|exists:categories,id',

            'name' => 'sometimes|string|max:100',

            'amount' => 'sometimes|numeric|min:0.01',

            'billing_cycle' => 'sometimes|in:daily,weekly,monthly,yearly',

            'next_payment_date' => 'sometimes|date',

            'start_date' => 'sometimes|date',

            'end_date' => 'nullable|date',

            'status' => 'sometimes|in:active,inactive,cancelled',
        ]);


        if (isset($validated['account_id'])) {

            $accountExists = $user->accounts()
                ->where('id', $validated['account_id'])
                ->exists();

            if (!$accountExists) {
                return response()->json([
                    'message' => 'Account not found'
                ], 404);
            }
        }

        $subscription->update($validated);


        return response()->json([
            'message' => 'Subscription updated successfully',
            'subscription' =>
            $subscription->fresh()->load([
                'account',
                'category'
            ])
        ]);
    }


    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $subscription = Subscription::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$subscription) {
            return response()->json([
                'message' => 'Subscription not found'
            ], 404);
        }


        $subscription->delete();


        return response()->json([
            'message' => 'Subscription deleted successfully'
        ]);
    }
}
