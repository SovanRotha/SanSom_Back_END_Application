<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Account\Account as ModelsAccount;
use App\Models\Budget\Budget;
use App\Models\Budget\BudgetCategory;
use App\Models\Category\Category;
use App\Models\Category\Transaction;
use App\Services\Notification\NotificationService;
use App\Services\Notification\Notification\BudgetNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    //

    public function __construct(
        protected NotificationService $notificationService,
        protected BudgetNotificationService $budgetNotification,
    ) {}



    public function index(Request $request)
    {
        $search = $request->query('search');

        $transactions = Transaction::with('user', 'account', 'category')
            ->when($search, function ($query) use ($search) {
                $query
                    ->where('description', 'like', "%{$search}%")
                    ->orWhereHas('account', function ($query) use ($search) {
                        $query->where('account_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('category', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%");
                    });
            })
            ->get();

        return response()->json([
            'message' => 'Transactions retrieved successfully',
            'transactions' => $transactions,
        ]);
    }
    public function show($id)
    {
        $transaction = Transaction::with('user', 'account', 'category')->find($id);

        return response()->json([
            'message' => 'Transaction retrieved successfully',
            'transactions' => $transaction,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            // 'user_id' => 'required|exists:users,id',
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'required|exists:categories,id',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $account = ModelsAccount::where('id', $validated['account_id'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$account) {
            return response()->json(
                [
                    'message' => 'Account not found',
                ],
                404,
            );
        }

        $category = null;

        if (!empty($validated['category_id'])) {
            $category = Category::where('id', $validated['category_id'])
                ->where('status', 'active')
                ->where(function ($query) use ($user) {
                    $query->where('is_system', true)->orWhere('user_id', $user->id);
                })
                ->first();

            if (!$category) {
                return response()->json(
                    [
                        'message' => 'Category not found',
                    ],
                    404,
                );
            }
        }

        if ($validated['type'] === 'transfer' && $validated['category_id'] !== null) {
            return response()->json(
                [
                    'message' => 'Transfer should not have a category',
                ],
                422,
            );
        }

        if ($validated['type'] === 'income' && $category->type !== 'income') {
            return response()->json(
                [
                    'message' => 'Income transaction must use an income category',
                ],
                422,
            );
        }

        if ($validated['type'] === 'expense' && $category->type !== 'expense') {
            return response()->json(
                [
                    'message' => 'Expense transaction must use an expense category',
                ],
                422,
            );
        }

        //  if ($validated['type'] === 'transfer') {

        // return response()->json([
        //     'message' => 'Use the transfer endpoint to create a transfer'
        // ], 422);
        // }

        $transaction = DB::transaction(function () use ($user, $account, $validated) {
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'account_id' => $account->id,
                'category_id' => $validated['category_id'] ?? null,
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'transaction_date' => $validated['transaction_date'],
                'notes' => $validated['notes'] ?? null,
                'source' => 'manual',
                'status' => 'completed',
            ]);

            if ($validated['type'] === 'income') {
                $account->increment('balance', $validated['amount']);
            } elseif ($validated['type'] === 'expense') {
                $account->decrement('balance', $validated['amount']);
            }
            return $transaction;
        });

        // if ($transaction->type === 'expense') {

        //     // Find the user's active budget
        //     $transactionDate = Carbon::parse($transaction->transaction_date);
        //     $budget = Budget::where('user_id', $user->id)
        //         ->where('month', 'like', $transactionDate->format('Y-m-') . '%')
        //         ->first();

        //     if ($budget) {

        //         // Find budget category
        //         $budgetCategory = BudgetCategory::where(
        //             'budget_id',
        //             $budget->id
        //         )
        //             ->where(
        //                 'category_id',
        //                 $transaction->category_id
        //             )
        //             ->first();

        //         if ($budgetCategory) {

        //             // Calculate total spending
        //             $spent = Transaction::where(
        //                 'user_id',
        //                 $user->id
        //             )
        //                 ->where(
        //                     'category_id',
        //                     $transaction->category_id
        //                 )
        //                 ->where(
        //                     'type',
        //                     'expense'
        //                 )
        //                 ->whereBetween(
        //                     'transaction_date',
        //                     [
        //                         $transactionDate->copy()->startOfMonth(),
        //                         $transactionDate->copy()->endOfMonth()
        //                     ]
        //                 )
        //                 ->sum('amount');

        //             // Send to BudgetNotificationService
        //             $this->budgetNotification->checkBudget(
        //                 $budgetCategory,
        //                 (float) $spent
        //             );
        //         }
        //     }
        // }
        $this->checkBudgetNotification($transaction);

        return response()->json([
            'Message' => 'Transaction created Successfully',
            'transactions' => $transaction,
        ]);
    }

    private function checkBudgetNotification(Transaction $transaction): void
    {
        if ($transaction->type !== 'expense') {
            return;
        }

        $transactionDate = Carbon::parse($transaction->transaction_date);
        $budget = Budget::where('user_id', $transaction->user_id)
            ->where('month', 'like', $transactionDate->format('Y-m-') . '%')
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

        $spent = Transaction::where('user_id', $transaction->user_id)
            ->where('category_id', $transaction->category_id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [
                $transactionDate->copy()->startOfMonth(),
                $transactionDate->copy()->endOfMonth(),
            ])
            ->sum('amount');

        $this->budgetNotification->checkBudget($budgetCategory, (float) $spent);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $transaction = Transaction::where('id', $id)->where('user_id', $user->id)->first();

        if (!$transaction) {
            return response()->json(
                [
                    'message' => 'Transaction not found',
                ],
                404,
            );
        }

        DB::transaction(function () use ($transaction) {
            if ($transaction->type === 'income') {
                $transaction->account->decrement('balance', $transaction->amount);
            } elseif ($transaction->type === 'expense') {
                $transaction->account->increment('balance', $transaction->amount);
            }
        });

        $transaction->delete();

        return response()->json([
            'Message' => 'Transaction deleted Successfully !',
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        $transaction = Transaction::where('id', $id)->where('user_id', $user->id)->first();

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts, id',
            'category_id' => 'required|exists:categories, id',
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
            return response()->json(
                [
                    'message' => 'Account not found',
                ],
                404,
            );
        }

        if ($validated['category_id']) {
            $category = Category::where('id', $validated['category_id'])
                ->where('status', 'active')
                ->where(function ($query) use ($user) {
                    $query->where('is_system', true)->orWhere('user_id', $user->id);
                })
                ->first();

            if (!$category) {
                return response()->json(
                    [
                        'message' => 'Category not found',
                    ],
                    404,
                );
            }

            if ($category->type !== $validated['type']) {
                return response()->json(
                    [
                        'message' => 'Category type does not match transaction type',
                    ],
                    422,
                );
            }
        }

        DB::transaction(function () use ($transaction, $newAccount, $validated) {
            if ($transaction->type === 'income') {
                $transaction->account->decrement('balance', $transaction->amount);
            } elseif ($transaction->type === 'expense') {
                $transaction->account->increment('balance', $transaction->amount);
            }

            $transaction->update([
                'account_id' => $validated['account_id'],
                'category_id' => $validated['category_id'] ?? null,
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'transaction_date' => $validated['transaction_date'],
                'notes' => $validated['notes'] ?? null,
            ]);
            if ($validated['type'] === 'income') {
                $newAccount->increment('balance', $validated['amount']);
            } else {
                $newAccount->decrement('balance', $validated['amount']);
            }
        });

        return response()->json([
            'Message' => 'Transaction updated Successfully',
            'transactions' => $transaction->fresh(),
        ]);
    }

    public function summary(Request $request)
    {
        $user = $request->user();

        $income = Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->where('status', 'completed')
            ->sum('amount');

        $expense = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->sum('amount');

        $net = $income - $expense;

        return response()->json([
            'income' => $income,
            'expense' => $expense,
            'net' => $net,
        ]);
    }

    public function monthlySummary(Request $request)
    {
        $user = $request->user();

        $monthlySummary = Transaction::where('user_id', $user->id)
            ->where('status', 'completed')
            ->selectRaw(
                "
            EXTRACT(YEAR FROM transaction_date)::integer as year,
            EXTRACT(MONTH FROM transaction_date)::integer as month,
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense
        ",
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return response()->json([
            'monthly_summary' => $monthlySummary,
        ]);
    }
    public function budgetCalculation(Request $request)
    {
        $user = $request->user();

        $budget = Budget::where('user_id', $user->id)
            ->where('month', 'like', Carbon::now()->format('Y-m-') . '%')
            ->first();

        if (!$budget) {
            return response()->json([
                'message' => 'No budget found for this month',
                'budget_calculation' => [],
            ]);
        }

        $budgetCategories = BudgetCategory::where('budget_id', $budget->id)
            ->with('category')
            ->get();

        $budgetCalculation = $budgetCategories->map(function ($budgetCategory) use ($user, $budget) {

            $spent = Transaction::where('user_id', $user->id)
                ->where('category_id', $budgetCategory->category_id)
                ->where('type', 'expense')
                ->where('status', 'completed')
                ->whereBetween('transaction_date', [
                    Carbon::parse($budget->month)->startOfMonth(),
                    Carbon::parse($budget->month)->endOfMonth(),
                ])
                ->sum('amount');

            $limit = (float) $budgetCategory->limit_amount;
            $spent = (float) $spent;

            return [
                'budget_category_id' => $budgetCategory->id,
                'category_id' => $budgetCategory->category_id,
                'category_name' => $budgetCategory->category->name,

                'limit_amount' => $limit,
                'spent' => $spent,
                'remaining' => $limit - $spent,

                'percentage' => $limit > 0
                    ? round(($spent / $limit) * 100, 2)
                    : 0,

                'alert_percentage' => (float) $budgetCategory->alert_percentage,
            ];
        });

        return response()->json([
            'message' => 'Budget calculation retrieved successfully',
            'budget' => $budget,
            'budget_calculation' => $budgetCalculation,
        ]);
    }
}
