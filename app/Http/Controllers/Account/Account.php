<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Account as ModelAccount;
use Illuminate\Http\Request;

class Account extends Controller
{
    public function index()
    {
        $account = ModelAccount::with('accountType', 'user', 'transaction')->get();

        return response()->json([
            "Message" => "Account Retrieved Successfully",
            "account" => $account,
        ]);

    }

    public function show($id)
    {
        $account = ModelAccount::with('acount')->get();

        return response()->json([
            "Message" => "Account Retreived Successfully",
            "account" => $account
        ]);
    }

    public function store(Request $request, $id)
    {
        $account = $request->validate([
            "user_id" => 'required|exists:users, id',
            'account_type_id' => 'required|exists:account_types, id',
            'name' => 'required|string',
            'currency' => 'required|string',
            'icon' => 'nullable|string',
            'status' => 'required|in:active, inactive',
        ]);

        // did not do it yet, cuz it relates to balance
    }
    
}
