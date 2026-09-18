<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account\Account;
use App\Models\Budget\Budget;
use App\Models\Category\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    //

    public function dashboard()
    {
        $totalUsers = User::where('role_id', 2)->count();

        $totalUsersActive = User::where('role_id', 2)->where('status', 'active')->count();

        $totalTransactions = Transaction::count();

        $totalAccounts = Account::count();

        $totalBudgets = Budget::count();

       

        return response()->json([
            'total_users' => $totalUsers,
            'total_transactions' => $totalTransactions,
            'total_user_actives' => $totalUsersActive,
            'total_accounts' => $totalAccounts,
            'total_budgets' => $totalBudgets,
            
        ]);
    }
}
