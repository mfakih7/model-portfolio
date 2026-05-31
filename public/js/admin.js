document.addEventListener('DOMContentLoaded', () => {
    initAdminSidebar();
    initImageUploadPreview();
    initPortfolioUploadState();
});

function initAdminSidebar() {
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

    sidebar.querySelectorAll('a.nav-link').forEach((link) => {
        link.addEventListener('click', close);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992) {
            close();
        }
    });
}

function initImageUploadPreview() {
    const input = document.getElementById('portfolioImageInput');
    const preview = document.getElementById('imageUploadPreview');
    const previewImg = document.getElementById('imageUploadPreviewImg');

    if (!input || !preview || !previewImg) {
        return;
    }

    input.addEventListener('change', () => {
        const file = input.files?.[0];

        if (!file || !file.type.startsWith('image/')) {
            preview.classList.add('d-none');
            previewImg.removeAttribute('src');
            return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
            previewImg.src = event.target?.result || '';
            preview.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    });
}

function initPortfolioUploadState() {
    document.querySelectorAll('form.admin-form').forEach((form) => {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('.admin-btn-submit');
            if (!btn || btn.dataset.loading === '1') {
                return;
            }

            btn.dataset.loading = '1';
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Uploading and optimizing...';
        });
    });
}
