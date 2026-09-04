<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Models\Account\Account as ModelsAccount;
use App\Models\Budget\Budget;
use App\Models\Budget\BudgetCategory;
use App\Models\Category\Category;
use App\Models\Category\Transaction;
use App\Services\Notification\NotificationService;
use App\Services\Notification\BudgetNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
        protected BudgetNotificationService $budgetNotification
    ) {
    }

  
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        $transactions = Transaction::where('user_id', $user->id)
            ->with(['user', 'account', 'category'])
            ->get();

        return response()->json([
            'message' => 'Transaction retrieved successfully',
            'transaction' => $transactions,
        ]);
    }

    /**
     * Get single transaction.
     * Unchanged from your version.
     */
    public function show($id)
    {
        $transaction = Transaction::with(['user', 'account', 'category'])->find($id);

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Transaction retrieved successfully',
            'transaction' => $transaction,
        ]);
    }

    /**
     * Create transaction.
     * Unchanged from your version.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'required|exists:categories,id',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // Account must belong to this user and be active
        $account = ModelsAccount::where('id', $validated['account_id'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        // Category must be a system category or owned by this user
        $category = Category::where('id', $validated['category_id'])
            ->where('status', 'active')
            ->where(function ($query) use ($user) {
                $query->where('is_system', true)
                    ->orWhere('user_id', $user->id);
            })
            ->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        // Transaction type must match the category's type
        if ($category->type !== $validated['type']) {
            return response()->json([
                'message' => ucfirst($validated['type']) . ' transaction must use a ' . $validated['type'] . ' category',
            ], 422);
        }

        // Create the transaction and adjust the account balance in one DB transaction
        $transaction = DB::transaction(function () use ($user, $account, $validated) {
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'account_id' => $account->id,
                'category_id' => $validated['category_id'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'transaction_date' => $validated['transaction_date'],
                'notes' => $validated['notes'] ?? null,
                'source' => 'manual',
                'status' => 'completed',
            ]);

            $validated['type'] === 'income'
                ? $account->increment('balance', $validated['amount'])
                : $account->decrement('balance', $validated['amount']);

            return $transaction;
        });

        // Only check budget thresholds for expenses
        if ($transaction->type === 'expense') {
            $this->notifyBudgetIfNeeded($user, $transaction);
        }

        return response()->json([
            'message' => 'Transaction created successfully',
            'transaction' => $transaction,
        ], 201);
    }

    /**
     * Delete transaction.
     * Unchanged from your version.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $transaction = Transaction::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        DB::transaction(function () use ($transaction) {
            $transaction->type === 'income'
                ? $transaction->account->decrement('balance', $transaction->amount)
                : $transaction->account->increment('balance', $transaction->amount);

            $transaction->delete();
        });

        return response()->json(['message' => 'Transaction deleted successfully']);
    }

    /**
     * Update transaction.
     * Unchanged from your version.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $transaction = Transaction::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'required|exists:categories,id',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $newAccount = ModelsAccount::where('id', $validated['account_id'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$newAccount) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        $category = Category::where('id', $validated['category_id'])
            ->where('status', 'active')
            ->where(function ($query) use ($user) {
                $query->where('is_system', true)
                    ->orWhere('user_id', $user->id);
            })
            ->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        if ($category->type !== $validated['type']) {
            return response()->json([
                'message' => 'Category type does not match transaction type',
            ], 422);
        }

        DB::transaction(function () use ($transaction, $newAccount, $validated) {
            // Reverse the old balance effect on the old account
            $transaction->type === 'income'
                ? $transaction->account->decrement('balance', $transaction->amount)
                : $transaction->account->increment('balance', $transaction->amount);

            $transaction->update([
                'account_id' => $validated['account_id'],
                'category_id' => $validated['category_id'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'transaction_date' => $validated['transaction_date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Apply the new balance effect on the (possibly new) account
            $validated['type'] === 'income'
                ? $newAccount->increment('balance', $validated['amount'])
                : $newAccount->decrement('balance', $validated['amount']);
        });

        return response()->json([
            'message' => 'Transaction updated successfully',
            'transaction' => $transaction->fresh(),
        ]);
    }

    protected function notifyBudgetIfNeeded($user, Transaction $transaction): void
    {
        $transactionDate = \Carbon\Carbon::parse($transaction->transaction_date);
        $monthPrefix = $transactionDate->format('Y-m');

        $budget = Budget::where('user_id', $user->id)
            ->where('month', 'like', $monthPrefix . '%')
            ->where('status', 'active')
            ->first();

        if (!$budget) {
            return;
        }

        $budgetCategory = BudgetCategory::where('budget_id', $budget->id)
            ->where('category_id', $transaction->category_id)
            ->first();

        if (!$budgetCategory) {
            return;
        }

        $spent = Transaction::where('user_id', $user->id)
            ->where('category_id', $transaction->category_id)
            ->where('type', 'expense')
            ->whereYear('transaction_date', $transactionDate->year)
            ->whereMonth('transaction_date', $transactionDate->month)
            ->sum('amount');

        $this->budgetNotification->checkBudget($budgetCategory, (float) $spent);
    }
}