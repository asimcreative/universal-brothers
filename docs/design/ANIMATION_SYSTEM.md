# The animation system

How motion works on the Universal Brothers site, why it is built this way, and
what to do when you add a page.

Everything here is ours. No animation library is loaded, nothing was copied
from another site, and the whole system is two files and one observer.

- `resources/css/motion.css` — the vocabulary
- `resources/js/motion.js` — the tagging and the observer

Both layouts load `motion.css` as its own Vite entry. That matters: the site
runs on two stylesheets — `site.css` (a Tailwind build) on the homepage and
`app.scss` (Bootstrap) on the other fifteen pages — and neither includes the
other. When the motion rules lived inside `site.css` alone, every
`data-reveal` attribute on those fifteen pages was inert: the markup asked for
an animation and no stylesheet answered, so nothing was ever hidden and
nothing ever moved. Counting attributes did not reveal this. Measuring opacity
did.

---

## The two rules that do not bend

**1. Only `transform`, `opacity`, `filter` and `clip-path` are animated.**
Animating width, height, `top` or `box-shadow` makes the browser lay out or
repaint every frame, which is how a site full of movement becomes a site that
stutters on a phone. These four properties are handled by the compositor.

**2. Under `prefers-reduced-motion: reduce` everything stops and everything is
visible.** For some readers this kind of movement causes nausea, and that
setting is how they say so. Content must never depend on an animation having
run. The rule is last in `motion.css` and blunt on purpose — it forces
`opacity: 1` and `transform: none` with `!important` rather than trying to be
clever.

---

## Asking for motion

Markup names what it wants:

```html
<div data-reveal="up">                          <!-- rises into place -->
<div data-reveal="left" data-reveal-speed="slow">
<div data-reveal="up" data-reveal-delay="120">  <!-- milliseconds -->
<ul data-stagger>                               <!-- children follow one another -->
```

### Variants

| Variant | What it does | Use it for |
|---|---|---|
| `fade` | opacity only | eyebrows, small labels |
| `up` / `down` | slides along Y | headings, text, cards |
| `left` / `right` | slides along X | the halves of a two-column band |
| `scale` | settles from 94% | a figure that is the point of its section |
| `zoom` | settles from 106% | a badge or medallion |
| `blur` | 10px blur plus a small lift | a quiet, atmospheric entrance |
| `clip` | wipes up through its own frame | a photograph revealed in place |
| `image` | settles from a 1.08 push-in | photographs — the frame stays filled at every moment, so no edge is ever exposed |

### Speed

`data-reveal-speed="fast|normal|slow"` → 300ms / 600ms / 850ms. Normal is the
default. `clip` and `image` take `slow` automatically.

### Distance and stagger

Set once in `:root` and reduced on phones, where the same distance reads as a
longer journey and the whole list is on screen at once:

|  | desktop | ≤767px |
|---|---|---|
| `--reveal-distance` | 28px | 16px |
| `--stagger-step` | 80ms | 55ms |
| `--stagger-max` | 480ms | 280ms |

The cap matters. Past `--stagger-max` the remaining items share the last delay
and arrive together, which looks deliberate and stops the twentieth card being
two seconds behind the first.

---

## What happens automatically

`applyDefaults()` in `motion.js` stages the shapes the site repeats, so a page
written before this existed — or one an admin builds in the page builder — is
animated without anyone editing it.

It walks each **band** (a `<section>`, a `.section`, or a top-level
`.container`; the older pages open straight into a container and skipping
those left four of them with almost nothing staged) and assigns:

| What it finds | Variant | Why |
|---|---|---|
| eyebrow | `fade`, fast | it is a label, not an event |
| `h2` / section title | `up` | |
| lead paragraph | `up`, delayed behind the heading | reads in order |
| a stat pill or big number | `scale` | a figure settles; it does not slide |
| a two-column band ≥700px | `left` + `right` | the halves come from their own side — lifting both straight up says nothing about the relationship between them |
| other rows and grids | `up`, staggered | |
| a column that is mostly a picture | `image` | |
| a citation / affiliation / timeline list | alternating `left`, `right` | follows the zig-zag the layout already has |
| a page hero or CTA band | `up` then `fade`, in reading order | three lines, not one lump |
| loose cards and accordion items | `up`, fast | the shapes the older pages are built from |

Two refusals are as important as the assignments:

- **Never a reveal inside a reveal.** The outer element is already hidden, so
  an inner one animates a second time behind it.
- **Never a panel that is currently closed** (`.tab-pane:not(.show)`,
  `.collapse:not(.show)`, `[hidden]`). A closed panel has no box to intersect
  with, so staging it hides it for good: when the reader opens the tab there
  is no scroll to sweep it back into view.

A column that contains a whole accordion or another row is scaffolding, not
content — it is skipped so its contents are staged instead. Otherwise a single
`.col-lg-9` wrapping a page animates the entire page as one block.

### Never touched

```
.hero-slide, [data-ub-hero], .news-ticker, .ub-marquee, .ub-ticker,
.modal, .offcanvas, .ai-assistant, .carousel, [data-ub-slide], #ub-mobile-nav
```

These run their own motion or must never be hidden.

---

## The observer

One `IntersectionObserver` for the whole page. Not one per element, and no
scroll handler. A section reveals when it arrives and is then forgotten.

Three details are load-bearing:

**`threshold: 0` with a bottom margin, never a ratio.** `intersectionRatio` is
measured against the element's own height, so anything taller than about 6.7
screens can never reach a ratio of 0.15 and would stay hidden for ever.
Several sections here are full-page-height wrappers.

**`entry.isIntersecting || entry.boundingClientRect.top < 0`.** Intersection
records are coalesced and delivered at the end of a frame, so during a fast
flick — or an anchor jump, or a restored scroll position — an element can
enter and leave between two deliveries and be reported only as *not*
intersecting. It would then sit at `opacity: 0` with the reader already past
it. Anything whose top edge is above the viewport has been passed, so it is
shown.

**Two safety nets.** Anything on screen 2.5 seconds after load is revealed
whatever the observer did; and whenever scrolling settles, anything the reader
has already reached is revealed. The second detaches once nothing is left.
Content visibility must never hang on a callback.

**Scope.** Tagging is scoped to `#main-content` — the header and drawer have
their own motion and must never be hidden by this. *Observing* covers the
whole document, because the footer sits outside `<main>` and scoping the
observer to `<main>` left its four revealed blocks hidden for good.

**Gated on `.js`,** which the layout sets before first paint. If the bundle
never loads, nothing is ever hidden.

---

## Adding a page

Usually nothing. Write ordinary sections and the defaults stage them.

Reach for an explicit `data-reveal` when the automatic choice is wrong — a
photograph the heuristic read as text, a figure that deserves `scale`, an
order that should differ from source order. Do not give every section the same
fade-up; the variants exist so a page has a rhythm rather than a tic.

---

## Proving it still works

Four checks, in the scratchpad's `design/` directory. Run them against a local
server on `127.0.0.1:8129`.

| Script | Answers |
|---|---|
| `coverage.mjs` | how many elements are staged per page, and which variants — catches "everything is `up`" |
| `check_animates.mjs` | whether the motion actually **runs**: below-the-fold elements must really be at opacity 0 before scrolling. This is the one that caught fifteen pages of inert attributes |
| `check_reveal.mjs` | that nothing is left invisible after scrolling each page, in both normal and reduced-motion passes |
| `check_panels.mjs` | that opening every tab and accordion leaves nothing stuck |

Current state: **343 elements across 14 pages**, every page using at least two
variants; motion live on every page; nothing stuck in either pass.
