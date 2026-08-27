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
     * Get all categories inside a budget
     */
    public function index(Request $request, $budgetId)
    {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Find user's budget
        |--------------------------------------------------------------------------
        */

        $budget = ModelsBudget::where('id', $budgetId)
            ->where('user_id', $user->id)
            ->first();

        if (!$budget) {
            return response()->json([
                'message' => 'Budget not found'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Get budget categories
        |--------------------------------------------------------------------------
        */

        $budgetCategories = BudgetBudgetCategory::where('budget_id', $budget->id)
            ->with('category')
            ->get();


        return response()->json([
            'message' => 'Budget categories retrieved successfully',
            'budget_categories' => $budgetCategories
        ]);
    }


    /**
     * Add category to budget
     */
    public function store(Request $request, $budgetId)
    {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Check budget
        |--------------------------------------------------------------------------
        */

        $budget = ModelsBudget::where('id', $budgetId)
            ->where('user_id', $user->id)
            ->first();

        if (!$budget) {
            return response()->json([
                'message' => 'Budget not found'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Validate
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',

            'limit_amount' => 'required|numeric|min:0',

            'alert_percentage' => 'required|integer|min:1|max:100',

            'rollover_enabled' => 'sometimes|boolean',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Check category access
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate category
        |--------------------------------------------------------------------------
        */

        $exists = BudgetBudgetCategory::where('budget_id', $budget->id)
            ->where('category_id', $category->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This category is already in the budget'
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Create budget category
        |--------------------------------------------------------------------------
        */


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
    }


    /**
     * Get one budget category
     */
    public function show(
        Request $request,
        $budgetId,
        $budgetCategoryId
    ) {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Check budget ownership
        |--------------------------------------------------------------------------
        */

        $budget = ModelsBudget::where('id', $budgetId)
            ->where('user_id', $user->id)
            ->first();

        if (!$budget) {
            return response()->json([
                'message' => 'Budget not found'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Get budget category
        |--------------------------------------------------------------------------
        */

        $budgetCategory = BudgetBudgetCategory::where('id', $budgetCategoryId)
            ->where('budget_id', $budget->id)
            ->with('category')
            ->first();

        if (!$budgetCategory) {
            return response()->json([
                'message' => 'Budget category not found'
            ], 404);
        }


        return response()->json([
            'message' => 'Budget category retrieved successfully',
            'budget_category' => $budgetCategory
        ]);
    }


    /**
     * Update budget category
     */
    public function update(
        Request $request,
        $budgetId,
        $budgetCategoryId
    ) {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Check budget
        |--------------------------------------------------------------------------
        */

        $budget = ModelsBudget::where('id', $budgetId)
            ->where('user_id', $user->id)
            ->first();

        if (!$budget) {
            return response()->json([
                'message' => 'Budget not found'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Find budget category
        |--------------------------------------------------------------------------
        */

        $budgetCategory = BudgetBudgetCategory::where('id', $budgetCategoryId)
            ->where('budget_id', $budget->id)
            ->first();

        if (!$budgetCategory) {
            return response()->json([
                'message' => 'Budget category not found'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Validate
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'limit_amount' => 'sometimes|numeric|min:0',

            'alert_percentage' => 'sometimes|integer|min:1|max:100',

            'rollover_enabled' => 'sometimes|boolean',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        $budgetCategory->update($validated);


        return response()->json([
            'message' => 'Budget category updated successfully',
            'budget_category' => $budgetCategory->fresh()->load('category')
        ]);
    }


    /**
     * Remove category from budget
     */
    public function destroy(
        Request $request,
        $budgetId,
        $budgetCategoryId
    ) {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Check budget
        |--------------------------------------------------------------------------
        */

        $budget = ModelsBudget::where('id', $budgetId)
            ->where('user_id', $user->id)
            ->first();

        if (!$budget) {
            return response()->json([
                'message' => 'Budget not found'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Find budget category
        |--------------------------------------------------------------------------
        */

        $budgetCategory = BudgetBudgetCategory::where('id', $budgetCategoryId)
            ->where('budget_id', $budget->id)
            ->first();

        if (!$budgetCategory) {
            return response()->json([
                'message' => 'Budget category not found'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Delete
        |--------------------------------------------------------------------------
        */

        $budgetCategory->delete();


        return response()->json([
            'message' => 'Category removed from budget successfully'
        ]);
    }
}