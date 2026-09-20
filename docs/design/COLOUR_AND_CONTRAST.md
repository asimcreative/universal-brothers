# Colour and contrast

What the site is made of, and the rule every text colour has to pass.

---

## The palette

Three colours carry the whole site, plus photography.

| Token | Value | Share of the site | Where |
|---|---|---|---|
| `--color-heading` / ivory band | `#efe9e6` | 55% | the light bands: body copy, cards, most inner pages |
| `--color-inverse` / teal-black | `#0b202a` | 32% | the dark bands: hero, impact, CTA, footer |
| `--color-sand-300` | `#d0c5c0` | 11% | the warm band that alternates with ivory |
| photography | — | 2% | hero and page headers |

Measured with `check_palette2.mjs`, which walks every band on eleven pages and
reports how much vertical space each colour occupies.

### The gradient

`--gradient-teal` runs behind ten sections — the hero overlay, the testimonials
band, the impact band and others. Its far end was `--color-brand-500`
(`#344952`): three steps lighter than every flat dark band on the site and
noticeably bluer, so the sections built on it drifted out of the palette and
read as a different colour family from the header sitting above them. It now
ends at `--color-brand-700` (`#1d323c`), which keeps the depth the design
wants without leaving the family.

There used to be a fourth colour: `--color-background`, the page behind everything,
was `#17181b` — a *cold* near-black belonging to no other part of the palette.
Where it showed (overscroll, any gap between bands) it read as a different
colour from the teal-black every dark section uses. It is now that same
teal-black, and the site is three colours.

---

## The text roles

Text has **two roles on each kind of band** — primary and muted — plus an
accent. They are named, and nothing writes outside them.

| Background | Primary | Muted | Accent |
|---|---|---|---|
| teal-black `#0b202a` | `--color-on-dark` `#e7e1dd` | `--color-on-dark-muted` `#bdb7b3` | `--color-accent-300` |
| ivory `#efe9e6` / white | `--color-inverse` `#0b202a` | `--color-ink-400` `#4a5c65` | `#7e5939` |
| sand `#d0c5c0` | `--color-inverse` `#0b202a` | `#3f4f58` | `#5f432b` |
| copper `#b9865a` | `--color-inverse` | — | — |

The same two values back the Bootstrap pages (`$ub-on-dark`,
`$ub-on-dark-muted`, `$ub-ink-muted`), so the whole site writes in one set.

**The sand row is the trap.** Its muted ink is a shade darker than ivory's,
because sand is a darker surface: `#4a5c65` is comfortable at 7.5:1 on ivory
and drops to 4.13:1 on sand, under the minimum. That step-down is stated once
per stylesheet rather than remembered at each call site — `.section.bg-light`
and `.pt-band-sand` in the SCSS, `.bg-sand-300` and `.bg-sand-200` in
`site.css`.

**A colour is either a surface or a text colour, never both.** That rule is
the whole lesson here: `--color-ivory` was the same value as
`--color-sand-300`, and two homepage bands were painted `bg-ivory` — a text
role used as a band, which put a fourth light into a three-colour palette.
`check_palette2.mjs` catches that: it reports what every band is painted in,
and the answer should stay at three colours plus photography.

### What it looked like before

`check_text_colours.mjs` lists every (text, background) pair actually painted,
grouped by whether the band is light or dark. It found:

| | Before | After |
|---|---|---|
| distinct light-on-dark text colours | **17** | **7** |
| distinct dark-on-light text colours | **24** | **10** |

Seventeen ways to write light-on-dark is what "the colours don't feel right"
actually is. Contrast could not see it: every one of them passed AA. What it
came from:

- **Two tokens with the same value and opposite jobs.** `--color-ivory`, used
  56 times as text on dark, was `#d0c5c0` — byte for byte `--color-sand-300`,
  a light *background* band. Body copy on every dark section was painted in a
  surface colour, which is why it read brown and muddy instead of light.

- **Muting by opacity instead of by colour.** `text-ivory` at 70, 75, 80, 85
  and 90 per cent; `text-foreground` at 80 and 85; `text-sand-300` at 80; and
  eighteen SCSS rules using `rgba(33, 37, 41, 0.7…)`. Each composites
  differently against whatever band sits behind it, so one intention became a
  dozen greys.

- **A cool grey in a warm palette.** The Bootstrap pages' muted-on-dark was
  `#c9cfd2`, a blue-grey, while the homepage's was warm. The same sentence on
  two pages was two different colours — visible, and impossible to attribute
  to anything until both were measured side by side.

- **Three inks for one job.** Section leads were written in `--color-ink-400`,
  `-500` and `-600` depending on the section.

The remaining seven and ten are the roles above plus the accents, button
labels, and the deliberately darker muted used on the sand band.

The sand row is the one that catches people out. `#7e5939` and `#4a5c65` are
both chosen against **white**, where they measure about 4.8:1 and 8.2:1. Sand
is several shades darker than white, and on it the same two colours drop to
**3.68:1 and 4.13:1** — under the minimum. Inside `.section.bg-light` and
`.pt-band-sand` each steps one shade down, which brings them to 5.35:1 and
5.03:1.

---

## The rule

**WCAG AA: 4.5:1 for body text, 3:1 for large text** (24px and above, or 18.66px
and above when bold).

Every page passes. `check_contrast.mjs` proves it: it walks every text node on
thirteen pages, resolves the colour actually painted behind it, and reports
anything below the threshold. It currently reports **zero**.

### What it was finding

| Was | Now | Where |
|---|---|---|
| 2.64:1 | 3.17:1 | award names on the copper marquee — greige on copper; now white |
| 3.68:1 | 5.35:1 | eyebrows and inline links on the sand band |
| 3.97:1 | 6.03:1 | package-card series line — a muted colour dimmed again by an opacity |
| 4.01:1 | 9.0:1 | the "Accreditation" label — same double-dimming |
| 4.13:1 | 5.03:1 | body copy on the sand band |
| 4.35:1 | 7.9:1 | the package finder's field labels on dark teal |
| 4.40:1 | 6.6:1 | the footer's copyright line and its dimmest links |

The pattern behind most of them: **a muted colour with an opacity on top of
it.** `text-muted-foreground/80` and `text-ivory/60` are each a deliberate
choice applied twice, and the second one is what takes it under the line.

---

## Running the check

```
node <scratchpad>/design/check_contrast.mjs
```

Needs a local server on `127.0.0.1:8129`.

`check_text_colours.mjs` is the companion: contrast answers "can this be
read", and that one answers "is the site written in one voice". A page can
pass AA everywhere and still look muddy.

Three things about the contrast check are worth knowing, because each was a
bug in it first:

- **`oklab()`.** Chromium reports anything Tailwind builds with `color-mix`
  — every `/60`, `/80` opacity utility — as `oklab(...)`. Reading those three
  numbers as red, green and blue turns a pale greige into near-black. The
  first version did exactly that and declared the entire footer unreadable.
  It now converts oklab to sRGB properly.

- **Text over photographs is skipped, not guessed at.** A hero photograph is
  usually painted by an absolutely-positioned sibling or a `::before`, not by
  an ancestor's own background, so walking up the tree finds the page colour
  and reports white-on-white. Anything with artwork behind it is excluded.

- **Translucent layers are composited.** A colour at 60% over a dark band is
  measured as what the eye sees, not as its own value.

It also found something that was not a colour problem at all: a heading on the
homepage reading `"before one — which would drop the counter out of"`. A Blade
comment had lost its opening `{{--` in an earlier commit, so its second line
rendered as page text in ivory on ivory — invisible — and the Hajj package
counter it documented had been deleted along with it. Both restored. A check
that walks every text node finds things no test was looking for.
