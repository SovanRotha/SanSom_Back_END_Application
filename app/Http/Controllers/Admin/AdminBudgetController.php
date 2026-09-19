<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Budget\Budget;
use App\Models\Category\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminBudgetController extends Controller
{
    //
    public function budgets($id)
{
    $user = User::where('role_id', 2)->findOrFail($id);

    $currentMonth = Carbon::now()->format('Y-m');

    $budgets = Budget::with([
        'budgetCategories.category'
    ])
        ->where('user_id', $user->id)
        ->where('month', $currentMonth)
        ->get();

    $result = [];

    foreach ($budgets as $budget) {

        foreach ($budget->budgetCategories as $budgetCategory) {

            $spent = Transaction::where('user_id', $user->id)
                ->where('category_id', $budgetCategory->category_id)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ])
                ->sum('amount');

            $limit = (float) $budgetCategory->limit_amount;

            $percentage = $limit > 0
                ? ($spent / $limit) * 100
                : 0;

            $result[] = [
                'budget_id' => $budget->id,
                'budget_name' => $budget->name,
                'month' => $budget->month,

                'category_id' => $budgetCategory->category_id,
                'category_name' => $budgetCategory->category->name ?? null,

                'limit' => $limit,
                'spent' => (float) $spent,
                'remaining' => max($limit - $spent, 0),

                'percentage' => round($percentage, 2),

                'status' => $spent > $limit
                    ? 'exceeded'
                    : 'within_limit',
            ];
        }
    }

    return response()->json([
        'message' => 'User budgets retrieved successfully',
        'user_id' => $user->id,
        'month' => $currentMonth,
        'total_budgets' => count($result),
        'budgets' => $result,
    ]);
}
}
