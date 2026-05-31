document.addEventListener('DOMContentLoaded', () => {
    const navbar = document.querySelector('.navbar-public');
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
    }

    initPortfolioImages(document);

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.fade-up').forEach((el) => observer.observe(el));

    setupLightbox();
    initPortfolioLoadMore();
});

function initPortfolioImages(root) {
    const scope = root instanceof Document ? root : root;
    scope.querySelectorAll('.portfolio-item img, .featured-card img').forEach((img) => {
        if (img.dataset.lazyInit === '1') {
            return;
        }
        img.dataset.lazyInit = '1';

        const skeleton = img.closest('.portfolio-item, .featured-card')?.querySelector('.skeleton');
        const markLoaded = () => {
            img.classList.add('loaded');
            skeleton?.remove();
        };

        if (img.complete && img.naturalWidth > 0) {
            markLoaded();
        } else {
            img.addEventListener('load', markLoaded, { once: true });
            img.addEventListener('error', markLoaded, { once: true });
        }
    });
}

function setupLightbox() {
    const lightbox = document.getElementById('lightbox');
    if (!lightbox || lightbox.dataset.ready === '1') {
        bindLightboxTriggers(document);
        return;
    }

    lightbox.dataset.ready = '1';

    const content = lightbox.querySelector('.lightbox-content');
    const img = lightbox.querySelector('.lightbox-image');
    const title = lightbox.querySelector('.lightbox-title');
    const desc = lightbox.querySelector('.lightbox-desc');
    const cat = lightbox.querySelector('.lightbox-category');
    const download = lightbox.querySelector('.lightbox-download');
    const closeBtn = lightbox.querySelector('.lightbox-close');

    const setText = (el, value) => {
        if (!el) return;
        el.textContent = value || '';
        el.style.display = value ? '' : 'none';
    };

    const open = (el) => {
        const largeSrc = el.dataset.src || '';
        img.removeAttribute('src');
        img.alt = el.dataset.title || '';
        setText(title, el.dataset.title);
        setText(desc, el.dataset.description);
        setText(cat, el.dataset.category);

        if (download) {
            if (el.dataset.download) {
                download.href = el.dataset.download;
                download.style.display = '';
            } else {
                download.style.display = 'none';
            }
        }

        lightbox.classList.add('open');
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.classList.add('lightbox-open');
        closeBtn.focus({ preventScroll: true });

        if (largeSrc) {
            img.src = largeSrc;
        }
    };

    const close = () => {
        lightbox.classList.remove('open');
        lightbox.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('lightbox-open');
        img.removeAttribute('src');
        img.alt = '';
    };

    lightbox._openLightbox = open;

    closeBtn.addEventListener('click', close);

    lightbox.addEventListener('click', (event) => {
        if (!content.contains(event.target)) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && lightbox.classList.contains('open')) {
            close();
        }
    });

    bindLightboxTriggers(document);
}

function bindLightboxTriggers(root) {
    const lightbox = document.getElementById('lightbox');
    const open = lightbox?._openLightbox;
    if (!open) {
        return;
    }

    const scope = root instanceof Document ? root : root;
    scope.querySelectorAll('[data-lightbox]').forEach((item) => {
        if (item.dataset.lightboxBound === '1') {
            return;
        }
        item.dataset.lightboxBound = '1';
        item.addEventListener('click', () => open(item));
    });
}

function initPortfolioLoadMore() {
    const btn = document.getElementById('portfolioLoadMore');
    const grid = document.getElementById('portfolioGrid');

    if (!btn || !grid) {
        return;
    }

    btn.addEventListener('click', async () => {
        const offset = parseInt(btn.dataset.offset, 10) || 0;
        const category = btn.dataset.category || '';
        const label = btn.textContent;

        btn.disabled = true;
        btn.textContent = 'Loading...';

        try {
            const params = new URLSearchParams({
                offset: String(offset),
                limit: '9',
            });

            if (category) {
                params.set('category', category);
            }

            const response = await fetch(`/portfolio/load-more?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error('Request failed');
            }

            const data = await response.json();
            grid.insertAdjacentHTML('beforeend', data.html);
            initPortfolioImages(grid);
            bindLightboxTriggers(grid);

            btn.dataset.offset = String(data.nextOffset);

            if (!data.hasMore) {
                btn.remove();
            } else {
                btn.disabled = false;
                btn.textContent = label;
            }
        } catch {
            btn.disabled = false;
            btn.textContent = label;
        }
    });
}
