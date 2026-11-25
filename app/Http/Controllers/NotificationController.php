<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filter = $request->query('filter', 'all');

        if (! in_array($filter, ['all', 'unread'], true)) {
            $filter = 'all';
        }

        $query = Notification::query()
            ->forUser($user->id)
            ->with(['booking.user'])
            ->latest('created_at')
            ->latest('id');

        if ($filter === 'unread') {
            $query->unread();
        }

        $notifications = $query->paginate(10)->withQueryString();
        $unreadCount = Notification::query()->forUser($user->id)->unread()->count();
        $totalCount = Notification::query()->forUser($user->id)->count();

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'totalCount' => $totalCount,
            'filter' => $filter,
        ]);
    }

    public function markAsRead(Request $request, Notification $notification): RedirectResponse
    {
        if ((int) $notification->user_id !== (int) $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke notifikasi ini.');
        }

        $notification->markAsRead();

        if ($request->boolean('open_detail')) {
            return redirect()->to($notification->detailUrl());
        }

        $params = $request->input('filter') === 'unread' ? ['filter' => 'unread'] : [];

        return redirect()
            ->route('notifikasi.index', $params)
            ->with('success', 'Notifikasi ditandai sudah dibaca.');
    }
}