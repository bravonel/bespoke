<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class TaskNotificationController extends Controller
{
    public function open(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        abort_unless((int) $notification->notifiable_id === (int) $request->user()->id, 403);

        $notification->markAsRead();
        $url = (string) data_get($notification->data, 'url', '');

        return redirect(str_starts_with($url, '/') ? $url : route('tasks.mine'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'Novedades marcadas como leídas.');
    }
}
