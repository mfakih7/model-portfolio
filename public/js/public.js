document.addEventListener('DOMContentLoaded', () => {
    // Navbar scroll effect
    const navbar = document.querySelector('.navbar-public');
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
    }

    // Image lazy load with skeleton
    document.querySelectorAll('.portfolio-item img, .featured-card img').forEach((img) => {
        const skeleton = img.closest('.portfolio-item, .featured-card')?.querySelector('.skeleton');
        const markLoaded = () => {
            img.classList.add('loaded');
            skeleton?.remove();
        };
        if (img.complete) {
            markLoaded();
        } else {
            img.addEventListener('load', markLoaded);
            img.addEventListener('error', markLoaded);
        }
    });

    // Scroll animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.fade-up').forEach((el) => observer.observe(el));

    // Lightbox
    const lightbox = document.getElementById('lightbox');
    if (lightbox) {
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

        const open = (data) => {
            img.loading = 'lazy';
            img.decoding = 'async';
            img.src = data.src || '';
            img.alt = data.title || '';
            setText(title, data.title);
            setText(desc, data.description);
            setText(cat, data.category);

            if (download) {
                if (data.download) {
                    download.href = data.download;
                    download.style.display = '';
                } else {
                    download.style.display = 'none';
                }
            }

            lightbox.classList.add('open');
            lightbox.setAttribute('aria-hidden', 'false');
            document.body.classList.add('lightbox-open');
            closeBtn.focus({ preventScroll: true });
        };

        const close = () => {
            lightbox.classList.remove('open');
            lightbox.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('lightbox-open');
        };

        document.querySelectorAll('[data-lightbox]').forEach((item) => {
            item.addEventListener('click', () => open(item.dataset));
        });

        closeBtn.addEventListener('click', close);

        // Close when clicking outside the image/caption (on the overlay itself).
        lightbox.addEventListener('click', (event) => {
            if (!content.contains(event.target)) {
                close();
            }
        });

        // Close on ESC.
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && lightbox.classList.contains('open')) {
                close();
            }
        });
    }
});
