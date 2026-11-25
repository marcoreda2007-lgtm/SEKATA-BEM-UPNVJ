<a href="{{ route('notifikasi.index') }}"
   class="btn-sekata-primary notif-topbar-btn d-inline-flex align-items-center justify-content-center text-decoration-none {{ request()->routeIs('notifikasi.*') ? 'is-active' : '' }}"
   aria-label="Notifikasi{{ ($unreadNotificationCount ?? 0) > 0 ? ' (' . $unreadNotificationCount . ' belum dibaca)' : '' }}">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
        <path d="M10 21h4"></path>
    </svg>
    @if(($unreadNotificationCount ?? 0) > 0)
        <span class="badge rounded-pill notif-badge-count" data-unread-badge aria-hidden="true">
            {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
        </span>
    @endif
</a>
