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
    //
    public function index(Request $request, $goalId)
    {
        $user = $request->user();

        $goal = SavingGoal::where('id', $goalId)
            ->where('user_id', $user->id)
            ->first();

        if (!$goal) {
            return response()->json([
                'message' => 'Savings goal not found'
            ], 404);
        }

        $contributions = SavingContribution::where(
            'savings_goal_id',
            $goal->id
        )
            ->with('transaction')
            ->latest('contribution_date')
            ->get();


        return response()->json([
            'message' => 'Savings contributions retrieved successfully',
            'contributions' => $contributions
        ]);
    }


    public function show(
        Request $request,
        $goalId,
        $contributionId
    ) {
        $user = $request->user();


        $goal = SavingGoal::where('id', $goalId)
            ->where('user_id', $user->id)
            ->first();

        if (!$goal) {
            return response()->json([
                'message' => 'Savings goal not found'
            ], 404);
        }

        $contribution = SavingContribution::where(
            'id',
            $contributionId
        )
            ->where('savings_goal_id', $goal->id)
            ->with('transaction')
            ->first();

        if (!$contribution) {
            return response()->json([
                'message' => 'Contribution not found'
            ], 404);
        }


        return response()->json([
            'message' => 'Contribution retrieved successfully',
            'contribution' => $contribution
        ]);
    }

    public function store(Request $request, $goalId)
    {
        $user = $request->user();

         $goal = SavingGoal::where('id', $goalId)
            ->where('user_id', $user->id)
            ->first();

        if (!$goal) {
            return response()->json([
                'message' => 'Savings goal not found'
            ], 404);
        }

        $validated = $request->validate([
            'transaction_id' => 'nullable|exists:transactions,id',

            'amount' => 'required|numeric|min:0.01',

            'contribution_date' => 'required|date',

            'note' => 'nullable|string',
        ]);

        if (!empty($validated['transaction_id'])) {

            $transaction = Transaction::where(
                'id',
                $validated['transaction_id']
            )
                ->where('user_id', $user->id)
                ->first();

            if (!$transaction) {
                return response()->json([
                    'message' => 'Transaction not found'
                ], 404);
            }
        }

        $contribution = DB::transaction(function() use ($goal, $validated){
            $contribution = SavingContribution::create([
                'savings_goal_id' => $goal->id,

                'transaction_id' =>
                    $validated['transaction_id'] ?? null,

                'amount' => $validated['amount'],

                'contribution_date' =>
                    $validated['contribution_date'],

                'note' =>
                    $validated['note'] ?? null,
            ]);

             $goal->increment(
                'current_amount',
                $validated['amount']
            );

            if ($goal->fresh()->current_amount >= $goal->target_amount) {

                $goal->update([
                    'status' => 'completed'
                ]);
            }

            return $contribution;

        });

         return response()->json([
            'message' => 'Savings contribution added successfully',

            'contribution' =>
                $contribution->load('transaction'),

            'savings_goal' =>
                $goal->fresh()
        ], 201);

    }

    public function destroy(
        Request $request,
        $goalId,
        $contributionId
    ) {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Check goal
        |--------------------------------------------------------------------------
        */

        $goal = SavingGoal::where('id', $goalId)
            ->where('user_id', $user->id)
            ->first();

        if (!$goal) {
            return response()->json([
                'message' => 'Savings goal not found'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Find contribution
        |--------------------------------------------------------------------------
        */

        $contribution = SavingContribution::where(
            'id',
            $contributionId
        )
            ->where('savings_goal_id', $goal->id)
            ->first();

        if (!$contribution) {
            return response()->json([
                'message' => 'Contribution not found'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Delete + update goal
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use (
            $goal,
            $contribution
        ) {

            /*
            | Remove contribution amount
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
            | Re-open goal if necessary
            */

            $goal->refresh();

            if ($goal->current_amount < $goal->target_amount) {

                $goal->update([
                    'status' => 'active'
                ]);
            }
        });


        return response()->json([
            'message' => 'Contribution deleted successfully',

            'savings_goal' =>
                $goal->fresh()
        ]);
    }

    public function update(
        Request $request,
        $goalId,
        $contributionId
    ) {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Check goal
        |--------------------------------------------------------------------------
        */

        $goal = SavingGoal::where('id', $goalId)
            ->where('user_id', $user->id)
            ->first();

        if (!$goal) {
            return response()->json([
                'message' => 'Savings goal not found'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Find contribution
        |--------------------------------------------------------------------------
        */

        $contribution = SavingContribution::where(
            'id',
            $contributionId
        )
            ->where('savings_goal_id', $goal->id)
            ->first();

        if (!$contribution) {
            return response()->json([
                'message' => 'Contribution not found'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Validate
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:0.01',

            'contribution_date' => 'sometimes|date',

            'note' => 'nullable|string',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Update inside transaction
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use (
            $goal,
            $contribution,
            $validated,
        ) {

            /*
            | If amount changes,
            | update goal current_amount
            */

            if (isset($validated['amount'])) {

                $difference =
                    $validated['amount']
                    - $contribution->amount;

                $goal->increment(
                    'current_amount',
                    $difference
                );
            }


            /*
            | Update contribution
            */

            $contribution->update($validated);


            /*
            | Check completion
            */

            $goal->refresh();

            if ($goal->current_amount >= $goal->target_amount) {

                $goal->update([
                    'status' => 'completed'
                ]);

            } elseif ($goal->status === 'completed') {

                $goal->update([
                    'status' => 'active'
                ]);
            }
        });


        return response()->json([
            'message' => 'Contribution updated successfully',

            'contribution' =>
                $contribution->fresh(),

            'savings_goal' =>
                $goal->fresh()
        ]);
    }


}
