(() => {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('desktopSidebar');
    if (!toggle || !sidebar) return;

    const arrow = document.getElementById('sidebarToggleArrow');

    function setCollapsed(collapsed) {
        sidebar.classList.toggle('is-collapsed', collapsed);
        toggle.setAttribute('aria-expanded', String(!collapsed));
        const label = collapsed ? 'Buka sidebar' : 'Minimalkan sidebar';
        toggle.setAttribute('aria-label', label);
        toggle.title = label;
        arrow.setAttribute('d', collapsed ? 'M9 3v18m3-13 3 4-3 4' : 'M9 3v18m6-13-3 4 3 4');
    }

    try {
        setCollapsed(localStorage.getItem('sekata.sidebar.collapsed') === 'true');
    } catch {
        setCollapsed(false);
    }

    toggle.addEventListener('click', () => {
        const collapsed = !sidebar.classList.contains('is-collapsed');
        setCollapsed(collapsed);
        try {
            localStorage.setItem('sekata.sidebar.collapsed', String(collapsed));
        } catch {
            // Sidebar tetap bisa dipakai ketika penyimpanan browser dibatasi.
        }
    });
})();
