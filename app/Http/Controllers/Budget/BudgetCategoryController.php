<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Budget\Budget as ModelsBudget;
use App\Models\Budget\BudgetCategory as BudgetBudgetCategory;
use App\Models\BudgetCategory;
use App\Models\Category;
use App\Models\Category\Category as ModelsCategory;
use Illuminate\Http\Request;

class BudgetCategoryController extends Controller
{
    /**
     * Get all categories inside a budget or for the authenticated user.
     */
    public function index(Request $request, $budgetId = null)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            if ($budgetId) {
                $budget = ModelsBudget::where('id', $budgetId)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$budget) {
                    return response()->json([
                        'message' => 'Budget not found'
                    ], 404);
                }

                $budgetCategories = BudgetBudgetCategory::where('budget_id', $budget->id)
                    ->with('category')
                    ->get();
            } else {
                $budgetCategories = BudgetBudgetCategory::whereHas('budget', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                    ->with('category', 'budget')
                    ->get();
            }

            return response()->json([
                'message' => 'Budget categories retrieved successfully',
                'budget_categories' => $budgetCategories
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to retrieve budget categories',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error',
            ], 500);
        }
    }


    /**
     * Add category to budget
     */
    public function store(Request $request, $budgetId = null)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $budgetId = $budgetId ?? $request->input('budget_id');

            if (!$budgetId) {
                return response()->json([
                    'message' => 'Budget id is required'
                ], 422);
            }

            $budget = ModelsBudget::where('id', $budgetId)
                ->where('user_id', $user->id)
                ->first();

            if (!$budget) {
                return response()->json([
                    'message' => 'Budget not found'
                ], 404);
            }

            $validated = $request->validate([
                'category_id' => 'required|exists:categories,id',
                'limit_amount' => 'required|numeric|min:0',
                'alert_percentage' => 'required|integer|min:1|max:100',
                'rollover_enabled' => 'sometimes|boolean',
            ]);

            $category = ModelsCategory::where('id', $validated['category_id'])
                ->where('status', 'active')
                ->where(function ($query) use ($user) {
                    $query->where('is_system', true)
                          ->orWhere('user_id', $user->id);
                })
                ->first();

            if (!$category) {
                return response()->json([
                    'message' => 'Category not found'
                ], 404);
            }

            $exists = BudgetBudgetCategory::where('budget_id', $budget->id)
                ->where('category_id', $category->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'This category is already in the budget'
                ], 422);
            }

            $budgetCategory = BudgetBudgetCategory::create([
                'budget_id' => $budget->id,
                'category_id' => $category->id,
                'limit_amount' => $validated['limit_amount'],
                'alert_percentage' => $validated['alert_percentage'],
                'rollover_enabled' => $validated['rollover_enabled'] ?? false,
                'rollover_amount' => 0,
            ]);

            return response()->json([
                'message' => 'Category added to budget successfully',
                'budget_category' => $budgetCategory->load('category')
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to add category to budget',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error',
            ], 500);
        }
    }


    /**
     * Get one budget category.
     */
    public function show(Request $request, $budgetId = null, $budgetCategoryId = null)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            if ($budgetCategoryId === null) {
                $budgetCategoryId = $budgetId;
                $budgetId = null;
            }

            if ($budgetId !== null) {
                $budget = ModelsBudget::where('id', $budgetId)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$budget) {
                    return response()->json([
                        'message' => 'Budget not found'
                    ], 404);
                }

                $budgetCategory = BudgetBudgetCategory::where('id', $budgetCategoryId)
                    ->where('budget_id', $budget->id)
                    ->with('category')
                    ->first();
            } else {
                $budgetCategory = BudgetBudgetCategory::where('id', $budgetCategoryId)
                    ->whereHas('budget', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->with('category', 'budget')
                    ->first();
            }

            if (!$budgetCategory) {
                return response()->json([
                    'message' => 'Budget category not found'
                ], 404);
            }

            return response()->json([
                'message' => 'Budget category retrieved successfully',
                'budget_category' => $budgetCategory
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to retrieve budget category',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error',
            ], 500);
        }
    }


    /**
     * Update budget category
     */
    public function update(Request $request, $budgetId = null, $budgetCategoryId = null)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            if ($budgetCategoryId === null) {
                $budgetCategoryId = $budgetId;
                $budgetId = null;
            }

            if ($budgetId !== null) {
                $budget = ModelsBudget::where('id', $budgetId)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$budget) {
                    return response()->json([
                        'message' => 'Budget not found'
                    ], 404);
                }

                $budgetCategory = BudgetBudgetCategory::where('id', $budgetCategoryId)
                    ->where('budget_id', $budget->id)
                    ->first();
            } else {
                $budgetCategory = BudgetBudgetCategory::where('id', $budgetCategoryId)
                    ->whereHas('budget', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->first();
            }

            if (!$budgetCategory) {
                return response()->json([
                    'message' => 'Budget category not found'
                ], 404);
            }

            $validated = $request->validate([
                'limit_amount' => 'sometimes|numeric|min:0',
                'alert_percentage' => 'sometimes|integer|min:1|max:100',
                'rollover_enabled' => 'sometimes|boolean',
            ]);

            $budgetCategory->update($validated);

            return response()->json([
                'message' => 'Budget category updated successfully',
                'budget_category' => $budgetCategory->fresh()->load('category')
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to update budget category',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error',
            ], 500);
        }
    }


    /**
     * Remove category from budget
     */
    public function destroy(Request $request, $budgetId = null, $budgetCategoryId = null)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            if ($budgetCategoryId === null) {
                $budgetCategoryId = $budgetId;
                $budgetId = null;
            }

            if ($budgetId !== null) {
                $budget = ModelsBudget::where('id', $budgetId)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$budget) {
                    return response()->json([
                        'message' => 'Budget not found'
                    ], 404);
                }

                $budgetCategory = BudgetBudgetCategory::where('id', $budgetCategoryId)
                    ->where('budget_id', $budget->id)
                    ->first();
            } else {
                $budgetCategory = BudgetBudgetCategory::where('id', $budgetCategoryId)
                    ->whereHas('budget', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->first();
            }

            if (!$budgetCategory) {
                return response()->json([
                    'message' => 'Budget category not found'
                ], 404);
            }

            $budgetCategory->delete();

            return response()->json([
                'message' => 'Category removed from budget successfully'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to remove category from budget',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error',
            ], 500);
        }
    }
}