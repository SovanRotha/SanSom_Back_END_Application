<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Models\Budget\Budget;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    //
    public function index(Request $request)
    {
        $user = $request->user();

        $budget = Budget::where('user_id', $user->id)->with('budgetCategory.category')->latest('month')->get();

         return response()->json([
            'message' => 'Budgets retrieved successfully',
            'budgets' => $budget
        ]);
    }

     public function show(Request $request, $id)
    {
        $user = $request->user();

        $budget = Budget::where('id', $id)
            ->where('user_id', $user->id)
            ->with('budgetCategory.category')
            ->first();

        if (!$budget) {
            return response()->json([
                'message' => 'Budget not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Budget retrieved successfully',
            'budget' => $budget
        ]);
    }

    public function store(Request $request, $id)
    {
        $user = $request->user();

         $validated = $request->validate([
            'name' => 'required|string|max:100',

            'month' => ['required', 'date_format:Y-m-d',],

            'total_limit' => 'required|numeric|min:0',

            'rollover_enabled' => 'nullable|boolean',

            'status' => 'nullable|in:active,inactive',
        ]);

        $existingBudget = Budget::where('user_id', $user->id)
            ->whereDate('month', $validated['month'])
            ->exists();

        if ($existingBudget) {
            return response()->json([
                'message' => 'A budget for this month already exists'
            ], 422);
        }

        $budget = Budget::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'month' => $validated['month'],
            'total_limit' => $validated['total_limit'],
            'rollover_enabled' => $validated['rollover_enabled'] ?? false,
            'rollover_amount' => 0,
            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json([
            'message' => 'Budget created successfully',
            'budget' => $budget
        ], 201);
    }

    public function update(Request $request, $id)
    {
         $user = $request->user();

        $budget = Budget::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$budget) {
            return response()->json([
                'message' => 'Budget not found'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',

            'month' => ['sometimes','date_format:Y-m-d',],

            'total_limit' => 'sometimes|numeric|min:0',

            'rollover_enabled' => 'sometimes|boolean',

            'status' => 'sometimes|in:active,inactive',
        ]);

        $budget->update($validated);


        return response()->json([
            'message' => 'Budget updated successfully',
            'budget' => $budget->fresh()
        ]);
    }

    public function destroy(Request $request, $id)
    {
         $user = $request->user();

        $budget = Budget::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$budget) {
            return response()->json([
                'message' => 'Budget not found'
            ], 404);
        }
         $budget->delete();


        return response()->json([
            'message' => 'Budget deleted successfully'
        ]);
    }

}
