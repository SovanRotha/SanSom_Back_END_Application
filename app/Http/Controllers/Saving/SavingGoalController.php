<?php

namespace App\Http\Controllers\Saving;

use App\Http\Controllers\Controller;
use App\Models\Saving\SavingGoal;
use App\Services\Notification\Notification\SavingNotificationService;
use Illuminate\Http\Request;

class SavingGoalController extends Controller
{
    //

    public function __construct(protected SavingNotificationService $savingNotificationService) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $goals = SavingGoal::where('user_id', $user->id)
            ->with('savingContribution')
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Savings goals retrieved successfully',
            'savings_goals' => $goals
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        $goal = SavingGoal::where('id', $id)
            ->where('user_id', $user->id)
            ->with('savingContribution')
            ->first();

        if (!$goal) {
            return response()->json([
                'message' => 'Savings goal not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Savings goal retrieved successfully',
            'savings_goal' => $goal
        ]);
    }

    public function store(Request $request, $id)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',

            'target_amount' => 'required|numeric|min:0.01',

            'target_date' => 'nullable|date',

            'icon' => 'nullable|string|max:100',

            'color' => 'nullable|string|max:20',

            'auto_allocate' => 'sometimes|boolean',

            'allocation_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'status' => 'nullable|in:active,completed,inactive',
        ]); // it should check more on auto_allocate. to verify the calculation

        $goal = SavingGoal::create([
            'user_id' => $user->id,

            'name' => $validated['name'],

            'description' => $validated['description'] ?? null,

            'target_amount' => $validated['target_amount'],

            // New goal starts at 0
            'current_amount' => 0,

            'target_date' => $validated['target_date'] ?? null,

            'icon' => $validated['icon'] ?? null,

            'color' => $validated['color'] ?? null,

            'auto_allocate' => $validated['auto_allocate'] ?? false,

            'allocation_percentage' =>
            $validated['allocation_percentage'] ?? null,

            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json([
            'message' => 'Savings goal created successfully',
            'savings_goal' => $goal
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        $goal = SavingGoal::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$goal) {
            return response()->json([
                'message' => 'Savings goal not found'
            ], 404);
        }


        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',

            'description' => 'nullable|string',

            'target_amount' => 'sometimes|numeric|min:0.01',

            'target_date' => 'nullable|date',

            'icon' => 'nullable|string|max:100',

            'color' => 'nullable|string|max:20',

            'auto_allocate' => 'sometimes|boolean',

            'allocation_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'status' => 'sometimes|in:active,completed,inactive',
        ]);


        $goal->update($validated);


        if ($goal->current_amount >= $goal->target_amount) {
            $goal->update([
                'status' => 'completed'
            ]);
        }


        return response()->json([
            'message' => 'Savings goal updated successfully',
            'savings_goal' => $goal->fresh()
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $goal = SavingGoal::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$goal) {
            return response()->json([
                'message' => 'Savings goal not found'
            ], 404);
        }

        $goal->delete();


        return response()->json([
            'message' => 'Savings goal deleted successfully'
        ]);
    }

    public function addMoney(Request $request, $id)
    {
        $user = $request->user();

        $goal = SavingGoal::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$goal) {
            return response()->json([
                'message' => 'Savings goal not found'
            ], 404);
        }

        if ($goal->status !== 'active') {
            return response()->json([
                'message' => 'Savings goal is not active'
            ], 422);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        // Save previous amount
        $previousAmount = (float) $goal->current_amount;

        // Add money
        $goal->current_amount += $validated['amount'];

        // Check if completed
        if ($goal->current_amount >= $goal->target_amount) {
            $goal->current_amount = $goal->target_amount;
            $goal->status = 'completed';
        }

        $goal->save();

        // Check saving milestones
        $this->savingNotificationService->checkSaving(
            $goal,
            $previousAmount,
            (float) $goal->current_amount
        );

        return response()->json([
            'message' => 'Money added to savings goal successfully',
            'savings_goal' => $goal->fresh()
        ]);
    }
}
