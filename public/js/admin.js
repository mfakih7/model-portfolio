document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (!toggle || !sidebar) {
        return;
    }

    const open = () => {
        sidebar.classList.add('show');
        backdrop?.classList.add('show');
        document.body.classList.add('sidebar-open');
        toggle.setAttribute('aria-expanded', 'true');
    };

    const close = () => {
        sidebar.classList.remove('show');
        backdrop?.classList.remove('show');
        document.body.classList.remove('sidebar-open');
        toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.addEventListener('click', () => {
        sidebar.classList.contains('show') ? close() : open();
    });

    backdrop?.addEventListener('click', close);

    // Close the drawer after tapping a navigation link on mobile.
    sidebar.querySelectorAll('a.nav-link').forEach((link) => {
        link.addEventListener('click', close);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        }
    });

    // Reset state if the viewport grows back to desktop while the drawer is open.
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992) {
            close();
        }
    });
});
