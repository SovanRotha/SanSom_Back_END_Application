<?php

namespace App\Http\Controllers\Saving;

use App\Http\Controllers\Controller;
use App\Models\Category\Transaction;
use App\Models\Saving\SavingContribution;
use App\Models\Saving\SavingGoal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavingContributionController extends Controller
{
    /**
     * Get all saving contributions for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $contributions = SavingContribution::whereHas('savingGoal', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->with('transaction')
            ->latest('contribution')
            ->get();

        return response()->json([
            'message' => 'Savings contributions retrieved successfully',
            'contributions' => $contributions,
        ]);
    }

    /**
     * Get one saving contribution.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $contribution = SavingContribution::where('id', $id)
            ->whereHas('savingGoal', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with('transaction')
            ->first();

        if (!$contribution) {
            return response()->json([
                'message' => 'Contribution not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Contribution retrieved successfully',
            'contributions' => $contribution,
        ]);
    }

    /**
     * Create a new saving contribution.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'saving_goal_id' => 'required|exists:saving_goals,id',
            'transaction_id' => 'nullable|exists:transactions,id',
            'amount' => 'required|numeric|min:0.01',
            'contribution' => 'required|date',
            'note' => 'nullable|string',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Check Saving Goal Ownership
        |--------------------------------------------------------------------------
        */

        $goal = SavingGoal::where('id', $validated['saving_goal_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$goal) {
            return response()->json([
                'message' => 'Savings goal not found',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Check Transaction Ownership
        |--------------------------------------------------------------------------
        */

        if (!empty($validated['transaction_id'])) {
            $transaction = Transaction::where('id', $validated['transaction_id'])
                ->where('user_id', $user->id)
                ->first();

            if (!$transaction) {
                return response()->json([
                    'message' => 'Transaction not found',
                ], 404);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Create Contribution
        |--------------------------------------------------------------------------
        */

        $contribution = DB::transaction(function () use ($goal, $validated) {

            $contribution = SavingContribution::create([
                'saving_goal_id' => $goal->id,
                'transaction_id' => $validated['transaction_id'] ?? null,
                'amount' => $validated['amount'],
                'contribution' => $validated['contribution'],
                'note' => $validated['note'] ?? null,
            ]);

            /*
            | Add contribution amount to saving goal
            */

            $goal->increment('current_amount', $validated['amount']);

            /*
            | Check if goal is completed
            */

            $goal->refresh();

            if ($goal->current_amount >= $goal->target_amount) {
                $goal->update([
                    'status' => 'completed',
                ]);
            }

            return $contribution;
        });

        return response()->json([
            'message' => 'Savings contribution added successfully',
            'contributions' => $contribution->load('transaction'),
            'savings_goal' => $goal->fresh(),
        ], 201);
    }

    /**
     * Update a saving contribution.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Find Contribution
        |--------------------------------------------------------------------------
        */

        $contribution = SavingContribution::where('id', $id)
            ->whereHas('savingGoal', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (!$contribution) {
            return response()->json([
                'message' => 'Contribution not found',
            ], 404);
        }

        $goal = $contribution->savingGoal;

        /*
        |--------------------------------------------------------------------------
        | Validate
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:0.01',
            'contribution' => 'sometimes|date',
            'note' => 'sometimes|nullable|string',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update Contribution
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use (
            $goal,
            $contribution,
            $validated
        ) {

            /*
            | Update goal amount if contribution amount changes
            */

            if (array_key_exists('amount', $validated)) {

                $difference =
                    $validated['amount'] - $contribution->amount;

                $goal->increment(
                    'current_amount',
                    $difference
                );
            }

            /*
            | Update contribution
            */

            $contribution->update([
                'amount' => $validated['amount'] ?? $contribution->amount,

                'contribution' =>
                    $validated['contribution']
                    ?? $contribution->contribution,

                'note' => array_key_exists('note', $validated)
                    ? $validated['note']
                    : $contribution->note,
            ]);

            /*
            | Recalculate goal status
            */

            $goal->refresh();

            if ($goal->current_amount >= $goal->target_amount) {

                $goal->update([
                    'status' => 'completed',
                ]);

            } else {

                $goal->update([
                    'status' => 'active',
                ]);
            }
        });

        return response()->json([
            'message' => 'Contribution updated successfully',
            'contributions' => $contribution->fresh()->load('transaction'),
            'savings_goal' => $goal->fresh(),
        ]);
    }

    /**
     * Delete a saving contribution.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Find Contribution
        |--------------------------------------------------------------------------
        */

        $contribution = SavingContribution::where('id', $id)
            ->whereHas('savingGoal', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (!$contribution) {
            return response()->json([
                'message' => 'Contribution not found',
            ], 404);
        }

        $goal = $contribution->savingGoal;

        /*
        |--------------------------------------------------------------------------
        | Delete Contribution
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($goal, $contribution) {

            /*
            | Remove contribution amount from goal
            */

            $goal->decrement(
                'current_amount',
                $contribution->amount
            );

            /*
            | Delete contribution
            */

            $contribution->delete();

            /*
            | Recalculate goal status
            */

            $goal->refresh();

            if ($goal->current_amount < $goal->target_amount) {

                $goal->update([
                    'status' => 'active',
                ]);
            }
        });

        return response()->json([
            'message' => 'Contribution deleted successfully',
            'savings_goal' => $goal->fresh(),
            // 'contributions' => $goal->contributions,
        ]);
    }
}