<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;


class NotificationController extends Controller
{
    // Fetch all notifications
    public function fetch()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        $unreadCount = $notifications->where('status', false)->count();

        return response()->json([
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    // Mark a single notification as read
    public function markAsRead(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $notification = Notification::where('user_id', Auth::id())
            ->findOrFail($validated['id']);

        $notification->update([
            'status' => true,
        ]);

        return response()->json(['message' => 'Notification marked as seen']);
    }

    // Mark all notifications as read
    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())
            ->update(['status' => true]);
        return response()->json(['success' => true]);
    }

    // Clear all notifications
    public function clearAll()
    {
        Notification::where('user_id', auth()->id())->delete();
        return response()->json(['success' => true]);
    }
}
