<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account\Account;
use App\Models\Budget\Budget;
use App\Models\Category\Category;
use App\Models\Category\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $totalUsers = User::where('role_id', 2)->count();

        $totalUsersActive = User::where('role_id', 2)
            ->where('status', 'active')
            ->count();

        $totalTransactions = Transaction::count();

        $totalAccounts = Account::count();

        $totalBudgets = Budget::count();

        $totalCategories = Category::count();

        $totalIncome = Transaction::where('type', 'income')
            ->where('status', 'completed')
            ->sum('amount');

        $totalExpense = Transaction::where('type', 'expense')
            ->where('status', 'completed')
            ->sum('amount');

        $netBalance = $totalIncome - $totalExpense;


        // =========================
        // USER GROWTH RATE
        // =========================

        $currentMonthUsers = User::where('role_id', 2)
            ->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])
            ->count();

        $previousMonthUsers = User::where('role_id', 2)
            ->whereBetween('created_at', [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth(),
            ])
            ->count();


        if ($previousMonthUsers > 0) {
            $userGrowthRate =
                (($currentMonthUsers - $previousMonthUsers)
                    / $previousMonthUsers) * 100;
        } else {
            $userGrowthRate = $currentMonthUsers > 0 ? 100 : 0;
        }


        return response()->json([
            'total_users' => $totalUsers,
            'total_transactions' => $totalTransactions,
            'total_user_actives' => $totalUsersActive,
            'total_accounts' => $totalAccounts,
            'total_budgets' => $totalBudgets,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'total_incomes' => $totalIncome,
            'total_expenses' => $totalExpense,
            'total_net' => $netBalance,
            'total_categories' => $totalCategories,

            // User growth
            'current_month_users' => $currentMonthUsers,
            'previous_month_users' => $previousMonthUsers,
            'user_growth_rate' => round($userGrowthRate, 2),
        ]);
    }
}
