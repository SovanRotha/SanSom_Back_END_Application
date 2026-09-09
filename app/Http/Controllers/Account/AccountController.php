<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Account\Account as ModelsAccount;
use App\Models\Account\AccountType;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $accounts = ModelsAccount::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('accountType')
            ->get();

        return response()->json([
            'message' => 'Accounts retrieved successfully',
            'accounts' => $accounts,
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $account = ModelsAccount::where('id', $id)
            ->where('user_id', $user->id)
            ->with('accountType')
            ->first();

        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        return response()->json([
            'message' => 'Account retrieved successfully',
            'accounts' => $account,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'account_type_id' => 'required|exists:account_types,id',
            'name' => 'required|string|max:100',
            'balance' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:20',
        ]);

        if (!AccountType::find($validated['account_type_id'])) {
            return response()->json(['message' => 'Account type not found'], 404);
        }

        $account = ModelsAccount::create([
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
            'accounts' => $account->load('accountType'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $account = ModelsAccount::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
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
            'accounts' => $account->fresh()->load('accountType'),
        ]);
    }

    public function deactivate(Request $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $account = ModelsAccount::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        $account->update(['status' => 'inactive']);

        return response()->json([
            'message' => 'Account deactivated successfully',
            'accounts' => $account->fresh()->load('accountType'),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $account = ModelsAccount::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        if ($account->transaction()->exists()) {
            return response()->json([
                'message' => 'Cannot delete account because it has transactions',
            ], 422);
        }

        $account->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }
}