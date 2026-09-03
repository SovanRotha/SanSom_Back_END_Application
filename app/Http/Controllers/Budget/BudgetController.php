<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Models\Budget\Budget;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $budget = Budget::where('user_id', $user->id)
                ->with('budgetCategories.category')
                ->latest('month')
                ->get();

            return response()->json([
                'message' => 'Budgets retrieved successfully',
                'budgets' => $budget
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to retrieve budgets',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error',
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $budget = Budget::where('id', $id)
                ->where('user_id', $user->id)
                ->with('budgetCategories.category')
                ->first();

            if (!$budget) {
                return response()->json([
                    'message' => 'Budget not found'
                ], 404);
            }

            return response()->json([
                'message' => 'Budget retrieved successfully',
                'budgets' => $budget
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to retrieve budget',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error',
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'month' => ['required', 'date_format:Y-m-d'],
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
                'budgets' => $budget
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to create budget',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

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
                'month' => ['sometimes', 'date_format:Y-m-d'],
                'total_limit' => 'sometimes|numeric|min:0',
                'rollover_enabled' => 'sometimes|boolean',
                'status' => 'sometimes|in:active,inactive',
            ]);

            $budget->update($validated);

            return response()->json([
                'message' => 'Budget updated successfully',
                'budgets' => $budget->fresh()
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to update budget',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error',
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

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
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to delete budget',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error',
            ], 500);
        }
    }
}
