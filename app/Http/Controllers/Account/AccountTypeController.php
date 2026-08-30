<?php

namespace App\Http\Controllers\AccountController;

use App\Http\Controllers\Controller;
use App\Models\Account\AccountType as AccountAccountType;
use Illuminate\Http\Request;

class AccountType extends Controller
{
    //

    public function index()
    {
        $accountType = AccountAccountType::with('account')->get();

        return response()->json([
            'message' => 'Account Types Retrieved Successfully',
            'accountType' => $accountType,
        ], 200);
    }

    // Get one account type
    public function show($id)
    {
        $accountType = AccountAccountType::with('account')->find($id);

        if (!$accountType) {
            return response()->json([
                'message' => 'Account Type Not Found',
            ], 404);
        }

        return response()->json([
            'message' => 'Account Type Retrieved Successfully',
            'accountType' => $accountType,
        ], 200);
    }

    // Create account type
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
        ]);

        $accountType = AccountAccountType::create($validated);

        return response()->json([
            'message' => 'Account Type Created Successfully',
            'accountType' => $accountType,
        ], 201);
    }

    // Update account type
    public function update(Request $request, $id)
    {
        $accountType = AccountAccountType::find($id);

        if (!$accountType) {
            return response()->json([
                'message' => 'Account Type Not Found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
        ]);

        $accountType->update($validated);

        return response()->json([
            'message' => 'Account Type Updated Successfully',
            'accountType' => $accountType,
        ], 200);
    }

    // Delete account type
    public function destroy($id)
    {
        $accountType = AccountAccountType::find($id);

        if (!$accountType) {
            return response()->json([
                'message' => 'Account Type Not Found',
            ], 404);
        }

        $accountType->delete();

        return response()->json([
            'message' => 'Account Type Deleted Successfully',
        ], 200);
    }
}
