<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Budget\Budget;
use App\Models\Budget\BudgetCategory;
use App\Models\Category\Category;
use App\Models\Category\Transaction;
use App\Services\Notification\NotificationService;
use App\Services\Notification\Notification\BudgetNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    //

    public function __construct(protected NotificationService $notificationService, protected BudgetNotificationService $budgetNotification) {}

    public function index()
    {
        $transaction = Transaction::with('user', 'account', 'category')->get();

        return response()->json([
            "Message" => "Transaction retrieved Successfully",
            "transaction" => $transaction
        ]);
    }

    public function show($id)
    {
        $transaction = Transaction::with('user', 'account', 'category')->find($id);

        return response()->json([
            "Message" => "Transaction retrieved Successfully",
            "transaction" => $transaction
        ]);
    }

    public function store(Request $request, $id)
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

        $account = Account::where('id', $validated['account_id'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$account) {
            return response()->json([
                'message' => 'Account not found'
            ], 404);
        }

        $category = null;

        if (!empty($validated['category_id'])) {

            $category = Category::where('id', $validated['category_id'])
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
        }

        if (
            $validated['type'] === 'transfer'
            && $validated['category_id'] !== null
        ) {
            return response()->json([
                'message' => 'Transfer should not have a category'
            ], 422);
        }

        if (
            $validated['type'] === 'income'
            && $category->type !== 'income'
        ) {
            return response()->json([
                'message' => 'Income transaction must use an income category'
            ], 422);
        }

        if (
            $validated['type'] === 'expense'
            && $category->type !== 'expense'
        ) {
            return response()->json([
                'message' => 'Expense transaction must use an expense category'
            ], 422);
        }

        //  if ($validated['type'] === 'transfer') {

        // return response()->json([
        //     'message' => 'Use the transfer endpoint to create a transfer'
        // ], 422);
        // }

        $transaction = DB::transaction(function () use ($user, $account, $validated, $id) {
            $transaction = Transaction::create([
                "user_id" => $user->id,
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
                $account->increment(
                    'balance',
                    $validated['amount']
                );
            } elseif ($validated['type'] === 'expense') {
                $account->decrement(
                    'balance',
                    $validated['amount']
                );
            }
            return $transaction;
        });

        if ($transaction->type === 'expense') {

            // Find the user's active budget
            $budget = Budget::where('user_id', $user->id)
                ->whereDate(
                    'start_date',
                    '<=',
                    $transaction->transaction_date
                )
                ->whereDate(
                    'end_date',
                    '>=',
                    $transaction->transaction_date
                )
                ->first();

            if ($budget) {

                // Find budget category
                $budgetCategory = BudgetCategory::where(
                    'budget_id',
                    $budget->id
                )
                    ->where(
                        'category_id',
                        $transaction->category_id
                    )
                    ->first();

                if ($budgetCategory) {

                    // Calculate total spending
                    $spent = Transaction::where(
                        'user_id',
                        $user->id
                    )
                        ->where(
                            'category_id',
                            $transaction->category_id
                        )
                        ->where(
                            'type',
                            'expense'
                        )
                        ->whereBetween(
                            'transaction_date',
                            [
                                $budget->start_date,
                                $budget->end_date
                            ]
                        )
                        ->sum('amount');

                    // Send to BudgetNotificationService
                    $this->budgetNotification->checkBudget(
                        $budgetCategory,
                        (float) $spent
                    );
                }
            }
        }

        return response()->json([
            'Message' => 'Transaction created Successfully',
            'transaction' => $transaction
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $transaction = Transaction::where('id', $id)->where('user_id', $id)->first();

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found'
            ], 404);
        }

        DB::transaction(function () use ($transaction) {
            if ($transaction->type === 'income') {
                $transaction->account->decrement(
                    'balance',
                    $transaction->amount
                );
            } elseif ($transaction->type === 'expense') {
                $transaction->account->increment(
                    'balance',
                    $transaction->amount
                );
            }
        });

        $transaction->delete();

        return response()->json(
            [
                'Message' => 'Transaction deleted Successfully !'
            ]
        );
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        $transaction = Transaction::where('id', $id)->where('user_id', $user)->first();

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts, id',
            'category_id' => 'required|exists:categories, id',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $newAccount = Account::where('id', $validated['account_id'])->where('user_id', $user->id)->where('status', 'active')->first();

        if (!$newAccount) {
            return response()->json([
                'message' => 'Account not found'
            ], 404);
        }

        if ($validated['category_id']) {

            $category = Category::where('id', $validated['category_id'])
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

            if ($category->type !== $validated['type']) {
                return response()->json([
                    'message' => 'Category type does not match transaction type'
                ], 422);
            }
        }

        DB::transaction(function () use ($transaction, $newAccount, $validated) {
            if ($transaction->type === 'income') {

                $transaction->account->decrement(
                    'balance',
                    $transaction->amount
                );
            } elseif ($transaction->type === 'expense') {

                $transaction->account->increment(
                    'balance',
                    $transaction->amount
                );
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

                $newAccount->increment(
                    'balance',
                    $validated['amount']
                );
            } else {

                $newAccount->decrement(
                    'balance',
                    $validated['amount']
                );
            }
        });

        return response()->json([
            'Message' => 'Transaction updated Successfully',
            'transaction' => $transaction->fresh(),
        ]);
    }
}
