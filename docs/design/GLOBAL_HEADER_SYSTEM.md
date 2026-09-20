# The global header and footer

One header and one footer serve every public page. This describes what it is made of, why it
is built this way, and the three traps anyone changing it will otherwise
fall into.

---

## The problem it solves

The site ran on two layouts and, until this change, two headers:

- `layouts.app` — Bootstrap, fifteen pages — used `partials/header.blade.php`,
  which carried a **mega menu**: two service columns, twenty deep links, an
  editorial panel, plus a Tourism dropdown.
- `layouts.template` — Tailwind, the homepage — used
  `partials/template-header.blade.php`, a flat bar of ten links.

So the same site offered two different menus depending on which page you were
on, and the homepage — the page most visitors see first — had the *smaller*
one. A menu change had to be made twice and could land on half the site.

The footer had split the same way. The Bootstrap one carried the deeper
service links — the process anchors, the FAQ anchors, the domestic and
international tour splits — while the homepage's showed a five-line summary.
`SiteNavigation::footerServices()` is the union of the two, so nothing a
visitor could reach before became unreachable.

There is now one header partial, one footer partial, one navigation tree, and
one stylesheet. Both partials carry the class `ub-chrome`, which is what the
preflight-substitute rules key on.

---

## The pieces

| File | Holds |
|---|---|
| `app/Support/SiteNavigation.php` | the menu, as data |
| `resources/views/layouts/partials/template-header.blade.php` | the header markup, rendered from that data |
| `resources/views/layouts/partials/template-footer.blade.php` | the footer markup, likewise |
| `resources/css/nav.css` | the header's own structure, as plain CSS |
| `resources/css/theme.css` | the design tokens, shared by both stylesheets |
| `resources/css/header.css` | the small Tailwind build the Bootstrap pages load |

### The navigation tree

`SiteNavigation::tree()` returns the menu in one shape:

```php
['label' => …, 'url' => …]                      // a plain link
['label' => …, 'url' => …, 'links'   => [ … ]]  // a short dropdown
['label' => …, 'url' => …, 'columns' => [ … ], 'feature' => [ … ]]  // a mega panel
```

Both the desktop bar and the mobile drawer render from it. A branch whose
category is not active is dropped rather than rendered empty, which is why
each group is built behind a check.

**Change the menu here and nowhere else.**

### How each shape renders

- **Plain link** — a link with an underline that grows on hover.
- **Dropdown / mega panel** — opens on `:hover` *and* `:focus-within`, so the
  keyboard reaches it with no JavaScript at all: tabbing to the parent opens
  the panel, tabbing past its last item closes it. `visibility` is animated
  alongside `opacity` so a closed panel is not a transparent sheet lying over
  the page.
- **In the drawer** — an item that owns a panel becomes a `<details>` group.
  Its columns flatten into one list: a drawer has no room for three columns,
  but every destination must still be reachable. `<details>` needs no script
  and is announced correctly by a screen reader.

### Overlay or solid

The partial takes `$ubOverlay`, default `true`.

- **Homepage** (`true`) — the bar is absolutely positioned over the hero
  photograph and is transparent.
- **Every other page** (`false`, passed by `layouts.app`) — no hero sits
  behind it, so the bar is a solid `brand-800` band in the normal flow.

**The bar pins itself on both, by two different routes.**

On the homepage, `initTemplateHeader` in `app.js` adds `.is-stuck` once the
reader is past the hero. The design it follows drops the header entirely at
that point; keeping it means the menu is still reachable nine thousand pixels
down the page.

Everywhere else there is no hero, so that observer never fires and the bar
used to scroll away and stay away. Those pages carry `ub-chrome--flow`, which
is `position: sticky` with `top: -41px` — minus the utility strip's height, so
the strip scrolls out of view and the menu bar lands against the top edge. No
script, and nothing jumps when it pins, because a sticky element keeps its
place in the flow.

Sticky is on the **wrapper**, not on the bar. A sticky element only sticks
within its own parent, and the bar's parent ends immediately below it — it
would come unstuck at once. The wrapper's parent is the body, so it sticks for
the length of the page.

### Where it switches to the drawer

**1280px.** Ten top-level items do not fit beside the wordmark and the
Register button below that, so it is a measured number, not a framework
default. Between 1280 and 1440 the gap between items closes to `0.875rem`:
at exactly 1280 the wordmark, ten items, the two chevrons and the button come
to slightly more than the container holds, and the button was pushed two
pixels past the edge.

---

## Trap one: Tailwind's preflight

The Bootstrap pages cannot simply load `site.css`. It begins with
`@import 'tailwindcss'`, which includes **preflight** — a reset that strips
margins from headings, unstyles lists and buttons, and changes how borders are
sized. Bootstrap has already reset those pages its own way. A second reset on
top of the first silently restyles fifteen pages of content nobody asked to
change.

So `header.css` imports the layers by hand and leaves `preflight.css` out:

```css
@layer theme;
@import 'tailwindcss/theme.css' layer(theme);
@import 'tailwindcss/utilities.css' source(none);   /* NOT in a layer */
```

Leaving preflight out means the header has to supply the handful of things
preflight would have given it. Those rules live at the bottom of `header.css`
— **not** in the shared `nav.css` — and every one is wrapped in `:where()` so
it contributes no specificity at all: it must beat the browser's defaults and
Bootstrap's element rules, and lose to every utility on the element itself.

| Rule | Without it |
|---|---|
| `ul, ol { list-style: none; margin: 0; padding: 0 }` | bullet points down the middle of the menu |
| `a { color: inherit }` | the phone number and email in the top strip in the body's dark ink on a dark bar |
| `a { text-decoration: none }` | every menu item underlined |
| `li, p { color: … }` | Bootstrap's `p, li` prose colour reaching into the panels and the editorial card |
| `svg, img { display: block }` | every icon two pixels taller than the same icon on the homepage |
| `button { padding: 0; background: transparent; … }` | the menu button as a grey system button |

The header also declares its own typeface in `nav.css`, rather than inheriting
whichever font the page's own reset set.

## Trap two: `source(none)`

Tailwind otherwise walks up from the stylesheet and scans the whole project to
decide which utilities to generate. A scan of all the views emitted **ninety-two**
class names Bootstrap already defines — `mb-3`, `p-4`, `container`, `col-6`,
`w-100` — which, loading after Bootstrap, would have quietly resized the
margins and padding on every page of the site.

`source(none)` switches detection off; the two files the header is actually
built from are named instead. That cut the output from 64 KB to 15 KB and the
shared names from 92 to 21.

Of those 21, fifteen genuinely differed in value (`gap-3` is 1rem in Bootstrap
and 0.75rem in Tailwind; `px-5` is 3rem versus 1.25rem; `border` sets a full
border in one and a width in the other). The header's markup now uses
arbitrary-value equivalents for exactly those — `gap-[0.75rem]`,
`px-[1.25rem]`, `border-solid border-[1px]` — which compute identically and
share a name with nothing.

**The shared count is now six, all harmless** (both sides declare the same
thing), and `check_collisions.mjs` proves it.

## Trap three: cascade layers

This one is subtle and cost two separate bugs, in opposite directions.

**An unlayered declaration beats a layered one whatever its specificity.**

With the header's utilities inside `@layer utilities`, Bootstrap's bare
`a { color: … }` — one element selector, unlayered — beat `.text-ivory`, and
every link in the header on fifteen pages rendered in the body's dark ink on a
dark bar. The markup was identical on every page, so nothing that compared the
DOM noticed. Hence `source(none)` **without** `layer(utilities)`: unlayered,
the two compete on specificity, which the class wins.

Then the same rule bit the other way. The preflight-substitute reset was
briefly in the shared `nav.css`, which the homepage loads unlayered while its
Tailwind utilities sit in `@layer utilities` — so `button { border: 0 }`
quietly removed the border the menu button asks for by name, on the one page
that had been correct all along. That is why the reset lives only in
`header.css`, the stylesheet the homepage never loads.

### Why `nav.css` is plain CSS

Everything in it has to work identically under a full Tailwind build and under
Bootstrap. Written as utilities it would have to exist in both stylesheets and
could drift. Written once as real rules with `ub-` names nothing else uses, it
cannot. It is imported by `site.css` and by `header.css` — never both on one
page.

---

## Changing the header safely

1. **Menu changes** go in `SiteNavigation::tree()`.
2. **Structural CSS** goes in `nav.css`, with a `ub-` name.
3. **New utility classes in the markup** — re-run `check_collisions.mjs`
   afterwards. A new class name that Bootstrap also defines is a silent
   fifteen-page regression, and it will not look like a header bug.
4. **Never put a bare element selector in `nav.css`.** It is shared, and on
   the homepage an unlayered rule there outranks every Tailwind utility. If
   the header needs an element-level default, it belongs in `header.css`,
   inside `:where()`.
5. **Re-run the header checks** (below).

### The checks

| Script | Answers |
|---|---|
| `check_header_parity.mjs` | that all thirteen pages render the same header — same ten items, same two panels, same twenty deep links, same drawer |
| `check_header_styles.mjs` | that it is *styled* the same everywhere — colours, fonts, borders, spacing, compared against the homepage. This is the one that catches the cascade-layer class of bug, which a DOM comparison cannot see |
| `check_collisions.mjs` | that no class name means different things in the two stylesheets |
| `header_geom.mjs` | the header's measured geometry at 1440/1024/768/390, for diffing before and after a change |
| `find_overflow.mjs` | which element, if any, is making the page wider than the viewport |

Playwright covers the behaviour: `public.spec.js` (the bar and the mega
panel), `responsive.spec.js` (the drawer opens, closes, expands its groups,
does not overflow and does not leave the page scrollable), and
`journeys-visitor.spec.js` (real journeys through the menu).

---

## Current state

Ten top-level items · two panels · twenty deep links · thirty drawer links —
identical on all thirteen public pages, and styled identically too
(`check_header_styles.mjs` reports no difference in colour, font, border or
spacing against the homepage on any of them).

The old `partials/header.blade.php` is no longer included anywhere. It has
been left on disk rather than deleted — that is a call for the project owner,
not a side effect of this change.
