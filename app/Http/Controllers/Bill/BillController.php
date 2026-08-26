<?php

namespace App\Http\Controllers\Bill;

use App\Http\Controllers\Controller;
use App\Models\Bill\Bill;
use Illuminate\Http\Request;

class BillController extends Controller
{
    //
    public function index(Request $request)
    {
        $user = $request->user();

        $bills = Bill::where('user_id', $user->id)
            ->with(['account', 'category'])
            ->latest('due_date')
            ->get();

        return response()->json([
            'message' => 'Bills retrieved successfully',
            'bills' => $bills
        ]);
    }

     public function show(Request $request, $id)
    {
        $user = $request->user();

        $bill = Bill::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['account', 'category'])
            ->first();

        if (!$bill) {
            return response()->json([
                'message' => 'Bill not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Bill retrieved successfully',
            'bill' => $bill
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',

            'category_id' => 'nullable|exists:categories,id',

            'name' => 'required|string|max:100',

            'amount' => 'required|numeric|min:0.01',

            'due_date' => 'required|date',

            'status' => [
                'nullable',
                'in:upcoming,due_soon,paid,overdue,cancelled'
            ],

            'note' => 'nullable|string',
        ]);


        // Check account belongs to user
        $accountExists = $user->accounts()
            ->where('id', $validated['account_id'])
            ->exists();

        if (!$accountExists) {
            return response()->json([
                'message' => 'Account not found'
            ], 404);
        }


        $bill = Bill::create([
            'user_id' => $user->id,
            'account_id' => $validated['account_id'],
            'category_id' => $validated['category_id'] ?? null,
            'name' => $validated['name'],
            'amount' => $validated['amount'],
            'due_date' => $validated['due_date'],
            'status' => $validated['status'] ?? 'upcoming',
            'notes' => $validated['notes'] ?? null,
        ]);


        return response()->json([
            'message' => 'Bill created successfully',
            'bill' => $bill->load(['account', 'category'])
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        $bill = Bill::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$bill) {
            return response()->json([
                'message' => 'Bill not found'
            ], 404);
        }


        $validated = $request->validate([
            'account_id' => 'sometimes|exists:accounts,id',

            'category_id' => 'nullable|exists:categories,id',

            'name' => 'sometimes|string|max:100',

            'amount' => 'sometimes|numeric|min:0.01',

            'due_date' => 'sometimes|date',

            'status' => [
                'sometimes',
                'in:upcoming,due_soon,paid,overdue,cancelled'
            ],

            'notes' => 'nullable|string',
        ]);


        if (isset($validated['account_id'])) {

            $accountExists = $user->accounts()
                ->where('id', $validated['account_id'])
                ->exists();

            if (!$accountExists) {
                return response()->json([
                    'message' => 'Account not found'
                ], 404);
            }
        }


        $bill->update($validated);


        return response()->json([
            'message' => 'Bill updated successfully',
            'bill' => $bill->fresh()->load([
                'account',
                'category'
            ])
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $bill = Bill::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$bill) {
            return response()->json([
                'message' => 'Bill not found'
            ], 404);
        }


        $bill->delete();


        return response()->json([
            'message' => 'Bill deleted successfully'
        ]);
    }


}
