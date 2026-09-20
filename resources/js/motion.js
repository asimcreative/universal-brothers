/**
 * The motion system.
 *
 * One IntersectionObserver for the whole page — not one per element, and no
 * scroll handler. A section reveals when it arrives, and is then forgotten.
 *
 * Markup asks for motion by name:
 *
 *     <div data-reveal="up">
 *     <div data-reveal="left" data-reveal-speed="slow">
 *     <div data-reveal="up" data-reveal-delay="120">
 *     <ul data-stagger>            each child follows the one before it
 *
 * `applyDefaults()` then tags the ordinary shapes — section headings, the
 * columns of a row, the cards in a grid — so a page that was written before
 * this existed, or one an admin builds in the page builder, is staged too
 * without anyone editing it.
 */

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

/** Read a number from a data attribute, falling back when it is absent or junk. */
function numberFrom(el, name, fallback) {
    const raw = Number.parseInt(el.dataset[name] ?? '', 10);
    return Number.isFinite(raw) ? raw : fallback;
}

/**
 * Give every child of a `[data-stagger]` container its own delay.
 *
 * Capped by `--stagger-max` so a long list never leaves the reader waiting: past
 * the cap the remaining items share the last delay and arrive together, which
 * looks deliberate and stops the twentieth card being two seconds behind the
 * first.
 */
function applyStagger(root) {
    const styles = getComputedStyle(document.documentElement);
    const step = Number.parseInt(styles.getPropertyValue('--stagger-step'), 10) || 80;
    const max = Number.parseInt(styles.getPropertyValue('--stagger-max'), 10) || 480;

    root.querySelectorAll('[data-stagger]').forEach((container) => {
        const own = numberFrom(container, 'staggerStep', step);
        Array.from(container.children).forEach((child, i) => {
            const target = child.matches('[data-reveal]') ? child : child.querySelector('[data-reveal]');
            if (!target || target.dataset.revealDelay) return;
            target.style.setProperty('--reveal-delay', `${Math.min(i * own, max)}ms`);
        });
    });
}

/**
 * Tag the shapes that repeat across the site.
 *
 * The variant is chosen from what the shape is, not applied uniformly: an
 * eyebrow fades, a heading rises, the two halves of a wide band come in from
 * their own sides, a photograph settles, a headline figure scales. A site where
 * every section does the identical fade-up reads as a tic rather than a
 * rhythm.
 *
 * Still deliberately conservative about WHAT is staged — section furniture,
 * the columns of a row, the cards of a grid, and little else. The site should
 * read as considered, not as a page where everything moves because it could.
 */
function applyDefaults(root) {
    const FORBIDDEN = '.hero-slide, [data-ub-hero], .news-ticker, .ub-marquee, .ub-ticker, '
        + '.modal, .offcanvas, .ai-assistant, .carousel, [data-ub-slide], #ub-mobile-nav';

    const styles = getComputedStyle(document.documentElement);
    const STEP = Number.parseInt(styles.getPropertyValue('--stagger-step'), 10) || 80;
    const MAX = Number.parseInt(styles.getPropertyValue('--stagger-max'), 10) || 480;

    const tag = (el, variant, delayIndex = 0, speed = null) => {
        if (!el || el.hasAttribute('data-reveal') || el.closest(FORBIDDEN)) return false;
        // A panel that is closed right now cannot be observed — it has no box
        // to intersect with. Staging it would hide it for good: when the
        // reader opens the tab or the accordion there is no scroll to sweep it
        // back into view. Closed panels are left alone.
        if (el.closest('.tab-pane:not(.show), .collapse:not(.show), [hidden]')) return false;
        // Never nest one reveal inside another: the outer one is already
        // hidden, so the inner would animate a second time behind it.
        if (el.querySelector('[data-reveal]')) return false;
        if (el.parentElement && el.parentElement.closest('[data-reveal]')) return false;
        el.setAttribute('data-reveal', variant);
        if (speed) el.setAttribute('data-reveal-speed', speed);
        if (delayIndex > 0) el.style.setProperty('--reveal-delay', `${Math.min(delayIndex * STEP, MAX)}ms`);
        return true;
    };

    /* Is this column mostly a picture? Then it earns the image treatment rather
       than being slid around like a block of text. */
    const isImagery = (el) => {
        const img = el.querySelector('img, .bg-cover, svg');
        if (!img) return false;
        return el.textContent.trim().length < 40;
    };

    /* A "band" is one horizontal stripe of the page. Most of the site says so
       with a <section>; the pages still on the older layout open straight into
       a `.container`, and skipping those left four of them with almost nothing
       staged. A container inside a section is not a band of its own. */
    const bands = Array.from(root.querySelectorAll('section, .section, .container'))
        .filter((el) => !el.matches('.container') || !el.parentElement?.closest('section, .section'));

    // The inner pages open on a photographic hero. Its three lines should
    // arrive in the order they are read, not as one block.
    root.querySelectorAll('.page-hero, .page-cta').forEach((hero) => {
        let n = 0;
        hero.querySelectorAll('h1, h2, .page-hero-lead, .page-cta-lead, .hero-actions, .page-cta-actions')
            .forEach((el) => { if (tag(el, n === 0 ? 'up' : 'fade', n)) n += 1; });
    });

    // A citation, an award, a testimony: a vertical list where each entry is a
    // photograph beside its text. Alternating the side they enter from follows
    // the zig-zag the layout already has; sliding all of them up ignores it.
    root.querySelectorAll('.award-citation-list, .affiliation-list, .timeline').forEach((list) => {
        Array.from(list.children).forEach((item, index) => {
            tag(item, index % 2 === 0 ? 'left' : 'right');
        });
    });

    bands.forEach((section) => {
        if (section.closest(FORBIDDEN)) return;

        // 1. The furniture of the section, in the order it is read. The eyebrow
        //    is quick; the heading and the sentence under it follow.
        let i = 0;
        section.querySelectorAll('.section-eyebrow, .ub-eyebrow').forEach((el) => {
            if (el.closest('.card, article, [class*="col-"]')) return;
            if (tag(el, 'fade', i, 'fast')) i += 1;
        });
        section.querySelectorAll('h2, .ub-section-title').forEach((el) => {
            if (el.closest('.card, article, [class*="col-"]')) return;
            if (tag(el, 'up', i)) i += 1;
        });
        section.querySelectorAll('.pt-lead, .ub-section-header > p').forEach((el) => {
            if (el.closest('.card, article, [class*="col-"]')) return;
            if (tag(el, 'up', i)) i += 1;
        });

        // 2. A figure that is the point of its section — a count, a big number —
        //    settles into place rather than sliding.
        section.querySelectorAll('.pt-stat-pill, .stat-tile, .stat-number').forEach((el) => {
            if (el.closest('[class*="col-"]')) return;
            tag(el, 'scale', 0);
        });

        // 3. Rows and grids.
        section.querySelectorAll('.row, [class*="grid-cols"], .grid').forEach((row) => {
            const children = Array.from(row.children).filter((c) => c.getBoundingClientRect().height > 8);
            if (!children.length) return;

            // A two-column band reads as two columns, so its halves come in
            // from their own side. Lifting both straight up says nothing about
            // the relationship between them.
            if (children.length === 2 && row.getBoundingClientRect().width > 700
                && !children.some((c) => c.querySelector('.row, .accordion'))) {
                tag(children[0], 'left');
                tag(children[1], 'right');
                return;
            }

            children.forEach((child, index) => {
                // A column that holds a whole accordion or another row is
                // scaffolding, not content. Tagging it would animate the page
                // as one block and hide everything inside it behind a single
                // fade; leave it alone so its contents are staged instead.
                if (child.querySelector('.row, .grid, [class*="grid-cols"], .accordion')) return;
                tag(child, isImagery(child) ? 'image' : 'up', index);
            });
        });

        // 4. A standalone photograph that is not inside a row.
        section.querySelectorAll('figure, .ub-photo-frame, .photo-figure').forEach((el) => {
            if (el.closest('.row, .grid, [class*="grid-cols"]')) return;
            tag(el, 'image');
        });

        // 5. Cards, accordions and list items that sit outside a grid — the
        //    shapes the pages still on the older layout are built from.
        const loose = section.querySelectorAll(':scope > .card, :scope > article, .accordion-item, .faq-accordion .accordion-item');
        Array.from(loose).forEach((el, index) => tag(el, 'up', index, 'fast'));
    });
}

/** The single observer. */
function observe(root) {
    const targets = root.querySelectorAll('[data-reveal]:not(.is-visible)');
    if (!targets.length) return;

    if (prefersReducedMotion()) {
        targets.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const reveal = (el) => {
        const delay = numberFrom(el, 'revealDelay', null);
        if (delay !== null) el.style.setProperty('--reveal-delay', `${delay}ms`);
        el.classList.add('is-visible');
    };

    // `threshold: 0` with a bottom margin, never a ratio: intersectionRatio is
    // measured against the element's own height, so anything taller than about
    // 6.7 screens can never reach a ratio of 0.15 and would stay hidden for
    // ever. Several sections here are full-page-height wrappers.
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            // `isIntersecting` alone is not enough. Intersection records are
            // coalesced and delivered at the end of a frame, so during a fast
            // flick — or an anchor jump, or a restored scroll position — an
            // element can enter and leave between two deliveries and be
            // reported only as "not intersecting". It would then sit at
            // opacity 0 with the reader already past it. Anything whose top
            // edge is above the viewport has been passed, so it is shown.
            if (entry.isIntersecting || entry.boundingClientRect.top < 0) {
                reveal(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0, rootMargin: '0px 0px -10% 0px' });

    targets.forEach((el) => observer.observe(el));

    // Safety net one: anything on screen shortly after load is shown whatever
    // the observer did. Content visibility must never hang on a callback.
    window.setTimeout(() => {
        document.querySelectorAll('[data-reveal]:not(.is-visible)').forEach((el) => {
            if (el.getBoundingClientRect().top < window.innerHeight) reveal(el);
        });
    }, 2500);

    // Safety net two, for the coalescing case above: whenever scrolling
    // settles, show anything the reader has already reached. Runs once per idle
    // moment rather than per scroll event, and detaches when nothing is left.
    let settle;
    const sweep = () => {
        const remaining = document.querySelectorAll('[data-reveal]:not(.is-visible)');
        if (!remaining.length) {
            window.removeEventListener('scroll', onScroll);
            return;
        }
        remaining.forEach((el) => {
            if (el.getBoundingClientRect().top < window.innerHeight) reveal(el);
        });
    };
    const onScroll = () => {
        window.clearTimeout(settle);
        settle = window.setTimeout(sweep, 150);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
}

export function initMotionSystem() {
    // Tagging the ordinary shapes is scoped to the page body — the header and
    // the drawer have their own motion and must never be hidden by this.
    const content = document.getElementById('main-content') || document.body;

    // Observing, though, covers the whole document. The footer sits outside
    // `<main>`, and scoping the observer to `<main>` left its four revealed
    // blocks hidden for good.
    applyDefaults(content);
    applyStagger(document);

    // Markup written before this system exists still works: the old class is
    // the same thing by another name.
    document.querySelectorAll('.reveal-on-scroll:not([data-reveal]), .reveal-scale:not([data-reveal])').forEach((el) => {
        el.setAttribute('data-reveal', el.classList.contains('reveal-scale') ? 'scale' : 'up');
    });

    observe(document);
}
