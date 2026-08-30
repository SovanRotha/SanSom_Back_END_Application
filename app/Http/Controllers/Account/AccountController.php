<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountType;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Get all accounts belonging to the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $accounts = Account::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('accountType')
            ->get();

        return response()->json([
            'message' => 'Accounts retrieved successfully',
            'accounts' => $accounts
        ]);
    }


    /**
     * Get one account
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $account = Account::where('id', $id)
            ->where('user_id', $user->id)
            ->with('accountType')
            ->first();

        if (!$account) {
            return response()->json([
                'message' => 'Account not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Account retrieved successfully',
            'account' => $account
        ]);
    }


    /**
     * Create account
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'account_type_id' => 'required|exists:account_types,id',
            'name' => 'required|string|max:100',
            'balance' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:20',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Check account type
        |--------------------------------------------------------------------------
        */

        $accountType = AccountType::find($validated['account_type_id']);

        if (!$accountType) {
            return response()->json([
                'message' => 'Account type not found'
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Create account
        |--------------------------------------------------------------------------
        */

        $account = Account::create([
            'user_id' => $user->id,
            'account_type_id' => $validated['account_type_id'],
            'name' => $validated['name'],
            'balance' => $validated['balance'],
            'currency' => $validated['currency'],
            'icon' => $validated['icon'] ?? null,
            'color' => $validated['color'] ?? null,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Account created successfully',
            'account' => $account->load('accountType')
        ], 201);
    }


    /**
     * Update account
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $account = Account::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$account) {
            return response()->json([
                'message' => 'Account not found'
            ], 404);
        }

        $validated = $request->validate([
            'account_type_id' => 'sometimes|exists:account_types,id',
            'name' => 'sometimes|string|max:100',
            'currency' => 'sometimes|string|max:10',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:20',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $account->update($validated);

        return response()->json([
            'message' => 'Account updated successfully',
            'account' => $account->fresh()->load('accountType')
        ]);
    }


    /**
     * Delete account
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $account = Account::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$account) {
            return response()->json([
                'message' => 'Account not found'
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Don't delete account if it has transactions
        |--------------------------------------------------------------------------
        */

        if ($account->transactions()->exists()) {
            return response()->json([
                'message' => 'Cannot delete account because it has transactions'
            ], 422);
        }

        $account->delete();

        return response()->json([
            'message' => 'Account deleted successfully'
        ]);
    }
}