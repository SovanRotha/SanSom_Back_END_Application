<?php

namespace App\Http\Controllers\Bill;

use App\Http\Controllers\Controller;
use App\Models\Bill\RecurringTransaction;
use Illuminate\Http\Request;

class RecurringTransactionController extends Controller
{
    //
    public function index(Request $request)
    {

    $user = $request->user();

        $recurringTransactions = RecurringTransaction::where(
            'user_id',
            $user->id
        )
            ->with(['account', 'category'])
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Recurring transactions retrieved successfully',
            'recurring_transactions' => $recurringTransactions
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        $recurringTransaction = RecurringTransaction::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['account', 'category'])
            ->first();

        if (!$recurringTransaction) {
            return response()->json([
                'message' => 'Recurring transaction not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Recurring transaction retrieved successfully',
            'recurring_transaction' => $recurringTransaction
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',

            'category_id' => 'nullable|exists:categories,id',

            'type' => 'required|in:income,expense',

            'amount' => 'required|numeric|min:0.01',

            'description' => 'required|string|max:255',

            'frequency' => 'required|in:daily,weekly,monthly,yearly',

            'start_date' => 'required|date',

            'end_date' => 'nullable|date|after_or_equal:start_date',

            'next_date' => 'required|date',

            'auto_create' => 'sometimes|boolean',

            'status' => 'nullable|in:active,inactive,cancelled',
        ]);


        // Make sure account belongs to user
        $account = $user->accounts()
            ->where('id', $validated['account_id'])
            ->first();

        if (!$account) {
            return response()->json([
                'message' => 'Account not found'
            ], 404);
        }


        $recurringTransaction = RecurringTransaction::create([
            'user_id' => $user->id,
            'account_id' => $validated['account_id'],
            'category_id' => $validated['category_id'] ?? null,
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'frequency' => $validated['frequency'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'next_date' => $validated['next_date'],
            'auto_create' => $validated['auto_create'] ?? true,
            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json([
            'message' => 'Recurring transaction created successfully',
            'recurring_transaction' =>
                $recurringTransaction->load(['account', 'category'])
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        $recurringTransaction = RecurringTransaction::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$recurringTransaction) {
            return response()->json([
                'message' => 'Recurring transaction not found'
            ], 404);
        }


        $validated = $request->validate([
            'account_id' => 'sometimes|exists:accounts,id',

            'category_id' => 'nullable|exists:categories,id',

            'type' => 'sometimes|in:income,expense',

            'amount' => 'sometimes|numeric|min:0.01',

            'description' => 'sometimes|string|max:255',

            'frequency' => 'sometimes|in:daily,weekly,monthly,yearly',

            'start_date' => 'sometimes|date',

            'end_date' => 'nullable|date',

            'next_date' => 'sometimes|date',

            'auto_create' => 'sometimes|boolean',

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


        $recurringTransaction->update($validated);

        return response()->json([
            'message' => 'Recurring transaction updated successfully',
            'recurring_transaction' =>
                $recurringTransaction->fresh()->load([
                    'account',
                    'category'
                ])
        ]);
    }


    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $recurringTransaction = RecurringTransaction::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$recurringTransaction) {
            return response()->json([
                'message' => 'Recurring transaction not found'
            ], 404);
        }

        $recurringTransaction->delete();

        return response()->json([
            'message' => 'Recurring transaction deleted successfully'
        ]);
    }

}
