<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category\Category;
use App\Models\Category\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    //
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
            'type' => 'required|in:income, expense, transfer',
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

             if ($validated['type'] === 'transfer') {

            return response()->json([
                'message' => 'Use the transfer endpoint to create a transfer'
            ], 422);
        }

        $transaction = DB::transaction(function() use($user, $account, $validated, $id)
        {
            $transaction = Transaction::create([
                "user_id" => $user->$id,
                'acount_id' => $account->$id,
                'category_id' => $validated['category_id'] ??null,
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'transaction_date' => $validated['transaction_date'],
                'notes' => $validated['notes'] ?? null,
                'source' => 'manual',
                'status' => 'completed',
            ]);

            if($validated['type'] === 'income')
                {
                    $account->increament(
                        'balance', $validated['amount']
                    );
                }
            elseif ($validated['type']==='expense'){
                $account->decrement(
                    'balance', $validated['amount']
                );
            }
            return $transaction;
        });

        return response()->json([
            'Message' => 'Transaction created Successfully',
            'transaction' => $transaction
        ]);
        
    }

    

    

    
}
