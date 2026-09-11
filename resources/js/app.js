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

    // `threshold: 0.15` was unsafe: intersectionRatio is measured against the
    // element's OWN height, so anything taller than ~6.7x the viewport can
    // never reach 0.15 and would stay invisible forever. Several sections on
    // this site are full-page-height wrappers. A zero threshold with a
    // bottom rootMargin triggers as soon as the element's leading edge is
    // meaningfully on screen, independent of how tall it is.
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0, rootMargin: '0px 0px -12% 0px' });

    targets.forEach((el) => observer.observe(el));

    // Last-resort safety net. Content visibility must never depend on an
    // observer callback firing; anything still hidden shortly after load is
    // revealed unconditionally.
    window.setTimeout(() => {
        document.querySelectorAll('.reveal-on-scroll:not(.is-visible)').forEach((el) => {
            const rect = el.getBoundingClientRect();
            if (rect.top < window.innerHeight) el.classList.add('is-visible');
        });
    }, 2500);
}

// The counters are server-rendered with their real, approved value already in
// the markup (see components/stat-number.blade.php — the live site shipped a
// literal "0" instead, so crawlers and any visitor with slow or blocked JS
// were told a twenty-year-old company had "0 Years of Experience"). This
// function's only job is the count-*up* flourish: it takes the final value
// that is already on screen, rewinds to 0, animates back, and restores the
// exact display string. Every early return below therefore leaves the correct
// figure showing rather than a zero.
function initCounters() {
    const counters = document.querySelectorAll('[data-counter-target]');
    if (!counters.length) return;

    // Reduced motion: the value is already correct in the DOM, so there is
    // simply nothing to do — never rewrite it to 0 first.
    if (prefersReducedMotion()) return;

    const animate = (el) => {
        const target = parseInt(el.dataset.counterTarget, 10);
        const display = el.dataset.counterDisplay || el.textContent;

        if (!Number.isFinite(target) || target <= 0) return;

        const duration = 1200;
        const start = performance.now();

        const step = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            if (progress < 1) {
                el.textContent = Math.floor(progress * target).toLocaleString();
                requestAnimationFrame(step);
            } else {
                // Restore the approved string ("20+", "10,000+"), not a bare
                // number — the "+" is part of the figure the owner approved.
                el.textContent = display;
            }
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
            // The <img> ships with no `src` and `hidden` set, so it is not a
            // broken image on every page load; reveal it now that it has one.
            img.hidden = false;
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    });
}

// Homepage "Filter My Packages" widget. The service <select> holds the target
// listing URL as its value; picking one just retargets the form. Everything
// else is a plain GET submit into the listing's own existing filters, so there
// is no duplicated filtering logic and the widget still works with JS disabled
// (it falls back to submitting against the Hajj listing, its default action).
function initPackageFinder() {
    const form = document.getElementById('packageFinder');
    if (!form) return;

    const service = form.querySelector('[data-finder-service]');
    if (!service) return;

    const retarget = () => { form.action = service.value; };
    service.addEventListener('change', retarget);
    retarget();
}

// Map embeds are held in a <template> so the browser never fetches Google
// until the visitor asks. The facade is already a working link to Google Maps;
// this upgrades it to load the map inline instead. See contact.blade.php for
// why the iframe cannot simply sit in the page.
function initMapEmbeds() {
    document.querySelectorAll('[data-map-embed]').forEach((wrap) => {
        const trigger = wrap.querySelector('[data-map-load]');
        const source = wrap.querySelector('[data-map-source]');
        if (!trigger || !source || !('content' in source)) return;

        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            wrap.classList.add('is-loaded');
            trigger.remove();
            wrap.appendChild(source.content.cloneNode(true));
            source.remove();
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Each initialiser is isolated: before this, all four ran in one
    // un-caught handler, so a throw in any of them silently prevented
    // `initScrollReveal` from ever adding `.is-visible` — leaving most of the
    // homepage stuck at `opacity: 0`.
    [initScrollReveal, initCounters, initParallax, initLightbox, initPackageFinder, initMapEmbeds].forEach((fn) => {
        try {
            fn();
        } catch (error) {
            // eslint-disable-next-line no-console
            console.error(`[ub] ${fn.name} failed`, error);
        }
    });
});
