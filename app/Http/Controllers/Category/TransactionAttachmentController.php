<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Models\Category\Transaction;
use App\Models\Category\TransactionAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TransactionAttachmentController extends Controller
{
    //
    public function index(Request $request, $transactionId)
    {
        $user = $request->user();

        $transaction = Transaction::where('id' , $transactionId)->where('user_id', $user->id)->first();

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found'
            ], 404);
        }

        $attachment = $transaction->attachments->get();

        return response()->json([
            'message' => 'Attachments retrieved successfully',
            'attachments' => $attachment
        ]);
    }

    public function show(Request $request, $transactionId, $attachmentId)
    {
        $user = $request->user();

        $transaction = Transaction::where('id', $transactionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found'
            ], 404);
        }

        $attachment = TransactionAttachment::where('id', $attachmentId)
            ->where('transaction_id', $transaction->id)
            ->first();

        if (!$attachment) {
            return response()->json([
                'message' => 'Attachment not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Attachment retrieved successfully',
            'attachment' => $attachment
        ]);
    }

    public function store(Request $request, $transactionId)
    {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Check transaction ownership
        |--------------------------------------------------------------------------
        */

        $transaction = Transaction::where('id', $transactionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Validate file
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,webp,pdf'
            ],
        ]);


        $file = $request->file('file');


        $path = $file->store('transaction-attachments', 'public');

        $attachment = TransactionAttachment::create([
            'transaction_id' => $transaction->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);


        return response()->json([
            'message' => 'Attachment uploaded successfully',
            'attachment' => $attachment
        ], 201);
    }


    /**
     * Delete attachment
     */
    public function destroy(
        Request $request,
        $transactionId,
        $attachmentId
    ) {
        $user = $request->user();

        $transaction = Transaction::where('id', $transactionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found'
            ], 404);
        }

        $attachment = TransactionAttachment::where('id', $attachmentId)
            ->where('transaction_id', $transaction->id)
            ->first();

        if (!$attachment) {
            return response()->json([
                'message' => 'Attachment not found'
            ], 404);
        }

        if (
            $attachment->file_path &&
            Storage::disk('public')->exists($attachment->file_path)
        ) {
            Storage::disk('public')->delete(
                $attachment->file_path
            );
        }

        $attachment->delete();


        return response()->json([
            'message' => 'Attachment deleted successfully'
        ]);
    }
}
