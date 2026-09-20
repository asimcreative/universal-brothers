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
import '@fontsource/fira-sans-condensed/latin-400.css';
import '@fontsource/fira-sans-condensed/latin-500.css';
import '@fontsource/fira-sans-condensed/latin-600.css';
import '@fontsource/fira-sans-condensed/latin-700.css';
import '@fontsource/fira-sans-condensed/latin-800.css';
import '@fontsource/playfair-display/latin-400.css';
import '@fontsource/playfair-display/latin-600.css';
import '@fontsource/playfair-display/latin-700.css';
import '@fontsource/playfair-display/latin-900.css';
import '@fontsource/playfair-display/latin-600-italic.css';
// One handwritten face, used for exactly one line: the hero's closing note,
// as in the reference. Latin 600 only — nothing else on the site sets it.
import '@fontsource/caveat/latin-600.css';

import $ from 'jquery';
window.$ = window.jQuery = $;

import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

import { initAiAssistant } from './ai-assistant';
import { initMotionSystem } from './motion';

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

// Staging.
//
// Measured against the reference: it starts 86 below-the-fold elements at
// opacity 0 with a 28px downward offset and lifts each one in as it arrives —
// the eyebrow, then the heading, then the sentence under it, then every card
// in the row one after another. We were doing that for 30 elements, almost all
// of them whole columns, so our page arrived all at once and sat still. That
// difference is most of what "the page feels alive" actually is.
//
// Tagging happens here rather than in forty places in the templates so that
// inner pages and anything an admin builds in the page builder get the same
// staging without a single markup change.
// The designer's template drives its header, drawer and hero carousel with
// React. These are the same three behaviours, written against the markup the
// Blade partials emit, so nothing on the page needs a framework.
function initTemplateHeader() {
    const header = document.querySelector('.ub-site-header');
    const hero = document.querySelector('[data-ub-hero]');

    // Pin the bar once the hero has scrolled by. A sentinel at the foot of the
    // hero costs nothing per frame; reading getBoundingClientRect() on every
    // scroll event would run layout each time.
    if (header && hero && 'IntersectionObserver' in window) {
        const sentinel = document.createElement('div');
        sentinel.style.cssText = 'position:absolute;bottom:0;left:0;width:1px;height:1px;pointer-events:none';
        sentinel.setAttribute('aria-hidden', 'true');
        hero.appendChild(sentinel);
        new IntersectionObserver(
            ([entry]) => header.classList.toggle('is-stuck', !entry.isIntersecting && entry.boundingClientRect.top < 0),
            { threshold: 0 }
        ).observe(sentinel);
    }

    // Drawer.
    const drawer = document.getElementById('ub-mobile-nav');
    if (drawer) {
        const opener = document.querySelector('[data-ub-menu-open]');
        let lastFocused = null;

        const setOpen = (open) => {
            drawer.hidden = !open;
            document.body.style.overflow = open ? 'hidden' : '';
            if (opener) opener.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) {
                lastFocused = document.activeElement;
                drawer.querySelector('a, button')?.focus();
            } else if (lastFocused) {
                lastFocused.focus();
            }
        };

        opener?.addEventListener('click', () => setOpen(true));
        drawer.querySelectorAll('[data-ub-menu-close]').forEach((el) => el.addEventListener('click', () => setOpen(false)));
        // Following a link should close it too, or the drawer stays over the
        // page it just navigated to when the target is an in-page anchor.
        drawer.querySelectorAll('nav a').forEach((el) => el.addEventListener('click', () => setOpen(false)));
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !drawer.hidden) setOpen(false);
        });
    }

    // Hero carousel. Only ever runs when the CMS holds more than one slide —
    // with a single slide there are no dots and nothing to advance.
    const slides = hero ? Array.from(hero.querySelectorAll('[data-ub-slide]')) : [];
    const dots = hero ? Array.from(hero.querySelectorAll('[data-ub-dot]')) : [];
    if (slides.length > 1) {
        let current = 0;
        let timer = null;

        const show = (next) => {
            slides.forEach((el, i) => {
                el.style.opacity = i === next ? '1' : '0';
                el.setAttribute('aria-hidden', i === next ? 'false' : 'true');
            });
            dots.forEach((dot, i) => {
                dot.setAttribute('aria-selected', i === next ? 'true' : 'false');
                const bar = dot.firstElementChild;
                if (bar) bar.className = 'block h-2.5 rounded-full transition-all duration-300 '
                    + (i === next ? 'w-7 bg-accent-500' : 'w-2.5 bg-ivory/45 group-hover:bg-ivory/70');
            });
            current = next;
        };

        const start = () => {
            if (prefersReducedMotion()) return;
            stop();
            timer = window.setInterval(() => show((current + 1) % slides.length), 6000);
        };
        const stop = () => { if (timer) window.clearInterval(timer); timer = null; };

        dots.forEach((dot, i) => dot.addEventListener('click', () => { show(i); start(); }));
        hero.addEventListener('mouseenter', stop);
        hero.addEventListener('mouseleave', start);
        // Advancing a carousel behind a hidden tab burns battery for nobody.
        document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));
        start();
    }
}

// Motion, applied from one place.
//
// The styles live in `resources/css/site.css`; this decides what wears
// them. Doing it here rather than in fourteen partials means a section an admin
// builds in the page builder gets the same treatment, and there is one file to
// read when something moves that should not.
function initMotion() {
    const main = document.getElementById('main-content');
    if (!main) return;

    // The hero arrives a line at a time. Ordered by how the hero reads, not by
    // position in the DOM, so the eyebrow leads and the buttons land last.
    const hero = document.querySelector('[data-ub-hero]');
    if (hero) {
        const sequence = [
            hero.querySelector('p.uppercase'),
            hero.querySelector('h1'),
            hero.querySelector('h1 + p'),
            hero.querySelector('h1 ~ div'),
            hero.querySelector('.font-hand'),
        ].filter(Boolean);
        sequence.forEach((el, i) => el.classList.add('ub-enter', `ub-enter-${i + 1}`));
    }

    // Anything with a photograph in it answers the pointer.
    main.querySelectorAll('article, .rounded-card, [class*="rounded-card"]').forEach((card) => {
        if (card.querySelector('img, .bg-cover')) card.classList.add('ub-zoom');
    });
    main.querySelectorAll('article').forEach((card) => card.classList.add('ub-lift'));

    // The two off-centre bands come in from their own side rather than both
    // lifting straight up, which is what makes them read as two columns.
    ['#introduction', '#why-us'].forEach((id) => {
        const section = document.querySelector(id);
        if (!section) return;
        const columns = section.querySelectorAll(':scope > div > div > [class*="lg:"], :scope > div > .grid > div');
        columns.forEach((col, i) => col.classList.add(i % 2 ? 'ub-from-right' : 'ub-from-left'));
    });

    // The page's one loud figure gets a little more than a lift.
    document.querySelectorAll('#impact h2, #impact [class*="rounded-pill"]').forEach((el) => el.classList.add('ub-pop'));

    // Mark the marquees so hovering one pauses it — a reader should be able to
    // stop a moving line to finish reading it.
    document.querySelectorAll('.ub-marquee-track').forEach((t) => t.parentElement?.classList.add('ub-marquee'));
    document.querySelectorAll('.ub-ticker-track').forEach((t) => t.closest('div')?.classList.add('ub-ticker'));
}

// `initAutoReveal` and `initScrollReveal` used to live here: one tagged
// elements with `.reveal-on-scroll`, the other observed them. Both are now
// `resources/js/motion.js`, which does the same work for every page rather
// than for the ones loading this bundle, and which adopts the old class by
// name so markup written before it still reveals.

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

// Publish the site header's REAL rendered height as `--ub-header-h`.
//
// Four separate sticky elements (the Hajj and Umrah detail sidebars, the Hajj
// listing's filter panel and the About page aside) each hard-coded their own
// offset — 100px, 100px, 6.5rem, 6.5rem. Measured, the header is 63.97px below
// 1200px, 125.75px AT 1200px (the nav wraps to two lines at exactly that
// width) and 107.75px above it, so every one of those guesses left its sticky
// element sitting UNDER the header on at least one desktop width. There is no
// CSS-only way to read an element's height, so it is measured here and fed
// back as a custom property; `_variables.scss` carries a fallback that already
// clears the tallest case, so the layout is correct before this runs and
// simply gets tighter afterwards.
function initHeaderOffset() {
    const header = document.querySelector('.site-header');
    if (!header) return;

    const apply = () => {
        const height = Math.ceil(header.getBoundingClientRect().height);
        if (height > 0) {
            document.documentElement.style.setProperty('--ub-header-h', `${height}px`);
        }
    };

    apply();

    // The header's height changes with viewport width (the nav wraps), and on
    // mobile when the offcanvas toggler reflows — a resize listener alone
    // misses font-loading reflow, which ResizeObserver catches.
    if ('ResizeObserver' in window) {
        new ResizeObserver(apply).observe(header);
    } else {
        window.addEventListener('resize', apply, { passive: true });
    }
}

// On a page with a full-bleed photographic hero the header sits ON the
// photograph (see `.site-header--overlay`). Once the hero has scrolled past, it
// pins itself as the ordinary solid bar so the navigation stays reachable.
//
// The switch point is the hero's own height rather than a fixed number of
// pixels: the hero is 100vh on a desktop and considerably shorter on a phone,
// and a constant would fire in the middle of the photograph on one of them.
function initOverlayHeader() {
    const header = document.querySelector('.site-header--overlay');
    if (!header) return;

    const hero = document.querySelector('.hero-slide');
    if (!hero) return;

    // A sentinel at the foot of the hero, watched by the observer, costs
    // nothing per frame — a scroll listener reading getBoundingClientRect()
    // would run layout on every scroll event.
    const sentinel = document.createElement('div');
    sentinel.style.cssText = 'position:absolute;bottom:0;left:0;width:1px;height:1px;pointer-events:none;';
    sentinel.setAttribute('aria-hidden', 'true');
    hero.appendChild(sentinel);

    if (!('IntersectionObserver' in window)) return;

    new IntersectionObserver(
        ([entry]) => header.classList.toggle('is-stuck', !entry.isIntersecting && entry.boundingClientRect.top < 0),
        { threshold: 0 }
    ).observe(sentinel);
}

// Hajj package detail: currency switching and option hand-off.
//
// Previously an inline <script> in show-hajj.blade.php. Two behavioural
// changes came with the move:
//
//   - Prices are now server-rendered in the default currency (see
//     components/hajj/price.blade.php). This function only ever SWAPS a value
//     that is already correct in the markup, so a visitor with JS blocked, a
//     crawler, or anyone reading before the bundle executes still sees every
//     published price instead of a page of empty cells.
//   - Only true multi-currency values participate. Transport fares, upgrades
//     and Aziziya services store one price in one currency, so they are
//     rendered in that currency and left alone; the old code pushed them
//     through the switcher too, which blanked every one of them to "N/A" the
//     moment a visitor clicked SAR.
function initHajjDetail() {
    const switcher = document.getElementById('currency-switcher');
    const symbols = { USD: 'US$', SAR: 'SAR ', PKR: 'PKR ' };

    if (switcher) {
        const buttons = switcher.querySelectorAll('[data-currency]');

        const render = (currency) => {
            document.querySelectorAll('.currency-price').forEach((el) => {
                const value = el.dataset[currency.toLowerCase()];
                el.textContent = value ? symbols[currency] + Number(value).toLocaleString() : 'N/A';
            });
        };

        buttons.forEach((btn) => {
            btn.addEventListener('click', () => {
                buttons.forEach((b) => {
                    b.classList.remove('active');
                    b.setAttribute('aria-pressed', 'false');
                });
                btn.classList.add('active');
                btn.setAttribute('aria-pressed', 'true');
                render(btn.dataset.currency);
            });
        });
    }

    // Choosing an option marks it as selected and carries the choice into the
    // enquiry message, so the office receives "Package B — Fairmont" rather
    // than just the package name and a blank message. The links are plain
    // `#enquire` anchors, so this is purely an enhancement: with JS off they
    // still jump to the form.
    const choosers = document.querySelectorAll('[data-hajj-choose]');
    if (!choosers.length) return;

    const message = document.querySelector('#enquire textarea[name="message"]');

    choosers.forEach((link) => {
        link.addEventListener('click', () => {
            document.querySelectorAll('.hajj-option').forEach((card) => card.classList.remove('is-chosen'));
            link.closest('.hajj-option')?.classList.add('is-chosen');

            // Never overwrite something the visitor has already typed.
            if (message && message.value.trim() === '') {
                message.value = `I am interested in ${link.dataset.hajjChoose}. Please send me the details.`;
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Each initialiser is isolated: before this, all four ran in one
    // un-caught handler, so a throw in any of them silently prevented the
    // reveal pass from ever adding `.is-visible` — leaving most of the
    // homepage stuck at `opacity: 0`.
    [initHeaderOffset, initOverlayHeader, initTemplateHeader, initMotion, initMotionSystem, initCounters, initParallax, initLightbox, initPackageFinder, initMapEmbeds, initHajjDetail, initAiAssistant].forEach((fn) => {
        try {
            fn();
        } catch (error) {
            // eslint-disable-next-line no-console
            console.error(`[ub] ${fn.name} failed`, error);
        }
    });
});
