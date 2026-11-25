<a href="{{ route('notifikasi.index') }}"
   class="btn-sekata-secondary notif-topbar-btn d-inline-flex align-items-center gap-2 text-decoration-none {{ request()->routeIs('notifikasi.*') ? 'is-active' : '' }}"
   aria-label="Notifikasi{{ ($unreadNotificationCount ?? 0) > 0 ? ' (' . $unreadNotificationCount . ' belum dibaca)' : '' }}">
    <span>Notifikasi</span>
    @if(($unreadNotificationCount ?? 0) > 0)
        <span class="badge rounded-pill notif-badge-count" data-unread-badge>
            {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
        </span>
    @endif
</a>