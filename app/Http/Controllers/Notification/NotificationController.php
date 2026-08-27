<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    //
     public function index(Request $request)
    {
        $notifications = Notification::where(
            'user_id',
            $request->user()->id
        )
        ->latest()
        ->get();

        return response()->json([
            'message' => 'Notifications retrieved successfully',
            'notifications' => $notifications,
        ]);
    }

    public function unread(Request $request)
    {
        $notifications = Notification::where(
            'user_id',
            $request->user()->id
        )
        ->whereNull('read_at')
        ->latest()
        ->get();

        return response()->json([
            'message' => 'Unread notifications retrieved successfully',
            'notifications' => $notifications,
        ]);
    }

    public function show(Request $request, $id)
    {
        $notification = Notification::where(
            'id',
            $id
        )
        ->where(
            'user_id',
            $request->user()->id
        )
        ->first();

        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Notification retrieved successfully',
            'notification' => $notification,
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::where(
            'id',
            $id
        )
        ->where(
            'user_id',
            $request->user()->id
        )
        ->first();

        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->update([
            'read_at' => now(),
        ]);

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification,
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        Notification::where(
            'user_id',
            $request->user()->id
        )
        ->whereNull('read_at')
        ->update([
            'read_at' => now(),
        ]);

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $notification = Notification::where(
            'id',
            $id
        )
        ->where(
            'user_id',
            $request->user()->id
        )
        ->first();

        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully',
        ]);
    }

    public function destroyAll(Request $request)
    {
        Notification::where(
            'user_id',
            $request->user()->id
        )->delete();

        return response()->json([
            'message' => 'All notifications deleted successfully',
        ]);
    }




}
