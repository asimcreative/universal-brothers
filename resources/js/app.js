import './bootstrap';

// Design-system typefaces (Poppins body / Playfair Display headings) were
// declared in `_variables.scss` from the start of this project but the
// actual font files were never loaded anywhere — every page has silently
// been rendering in fallback system fonts (Georgia/system-ui) the whole
// time, undermining the brand's intended premium typographic identity.
// Self-hosted via @fontsource (not a Google Fonts CDN link) so there's no
// external network dependency and no CSP loosening required.
import '@fontsource/poppins/latin-400.css';
import '@fontsource/poppins/latin-500.css';
import '@fontsource/poppins/latin-600.css';
import '@fontsource/poppins/latin-700.css';
import '@fontsource/playfair-display/latin-400.css';
import '@fontsource/playfair-display/latin-600.css';
import '@fontsource/playfair-display/latin-700.css';
import '@fontsource/playfair-display/latin-900.css';
import '@fontsource/playfair-display/latin-600-italic.css';

import $ from 'jquery';
window.$ = window.jQuery = $;

import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function initScrollReveal() {
    const targets = document.querySelectorAll('.reveal-on-scroll');
    if (!targets.length) return;

    if (prefersReducedMotion()) {
        targets.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    targets.forEach((el) => observer.observe(el));
}

function initCounters() {
    const counters = document.querySelectorAll('[data-counter-target]');
    if (!counters.length) return;

    const reduced = prefersReducedMotion();

    const animate = (el) => {
        const target = parseInt(el.dataset.counterTarget, 10);

        if (reduced) {
            el.textContent = target.toLocaleString();
            return;
        }

        const duration = 1200;
        const start = performance.now();

        const step = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            el.textContent = Math.floor(progress * target).toLocaleString();
            if (progress < 1) requestAnimationFrame(step);
            else el.textContent = target.toLocaleString();
        };
        requestAnimationFrame(step);
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                animate(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach((el) => observer.observe(el));
}

function initParallax() {
    const layers = document.querySelectorAll('.parallax-layer');
    if (!layers.length || prefersReducedMotion()) return;

    let ticking = false;
    const update = () => {
        layers.forEach((el) => {
            const rect = el.getBoundingClientRect();
            // Only nudge the layer while its section is actually on screen,
            // and cap the drift to a few percent of viewport height so this
            // reads as subtle depth rather than a scroll-jack gimmick.
            if (rect.bottom < 0 || rect.top > window.innerHeight) return;
            const progress = (rect.top) / window.innerHeight;
            el.style.transform = `translate3d(0, ${(progress * 6).toFixed(2)}%, 0)`;
        });
        ticking = false;
    };

    window.addEventListener('scroll', () => {
        if (!ticking) {
            requestAnimationFrame(update);
            ticking = true;
        }
    }, { passive: true });

    update();
}

function initLightbox() {
    const modalEl = document.getElementById('lightboxModal');
    if (!modalEl) return;
    const img = document.getElementById('lightboxModalImage');

    document.querySelectorAll('[data-lightbox-trigger]').forEach((el) => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            img.src = el.dataset.lightboxSrc;
            img.alt = el.dataset.lightboxCaption || '';
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initScrollReveal();
    initCounters();
    initParallax();
    initLightbox();
});
