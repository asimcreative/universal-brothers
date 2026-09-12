# Image Asset Audit

**Purpose:** to make replacing the demo photography with Universal Brothers'
own official imagery a mechanical job, not an archaeology exercise.

The full per-file register — source URL, licence, author, where each photograph
is used — is generated at [`../data/image-assets.md`](../data/image-assets.md).
This document is the *decision* layer: what is temporary, what it stands for,
and what the client still has to supply.

---

## 1. Where the site actually started

Before this pass, the database held **zero images**. Every one of the nine image
columns was empty across every row:

| Table | Column | Rows | With an image |
|---|---|---|---|
| `sliders` | `image` | 0 | 0 |
| `packages` | `cover_image` | 106 | **0** |
| `hotels` | `cover_image` | 9 | **0** |
| `package_media` | `image_path` | 0 | 0 |
| `awards` | `image` | 7 | **0** |
| `affiliations` | `logo` | 10 | **0** |
| `testimonials` | `photo` | 4 | **0** |
| `news_articles` | `cover_image` | 0 | 0 |
| `pages` | `featured_image` | 4 | **0** |

So every visual on the public site was a CSS-generated geometric composition.
That is the condition this work was asked to fix.

---

## 2. TEMPORARY DEMO IMAGES

**45 photographs**, all real photographs of the real places, all correctly
licensed and traceable to a source page. They are in `public/images/photos/`
and listed in the register.

They are grouped by what they are standing in for:

### 2a. Destination photography — likely to survive

These show a **place**, and the place is genuinely where the package goes. Even
after the client supplies their own photography, these remain factually correct
and may simply be upgraded rather than replaced.

- Makkah: `kaaba-tawaf`, `haram-dusk`, `haram-courtyard`, `haram-panorama`, `kaaba-close`, `makkah-skyline`
- Madinah: `nabawi-aerial`, `nabawi-dome`, `quba-mosque`
- Mashaer: `mina-tents`, `arafat`, `muzdalifah`, `jamarat`
- Travel: `jeddah-airport`, `haramain-train`, `saudia-aircraft`
- Pakistan tours: `hunza-*`, `skardu-*`, `malam-jabba`, `fairy-meadows`, `karakoram-highway`, `kaghan-naran`, `islamabad-faisal`, `karachi`
- International tours: `dubai`, `turkey-*`, `maldives`, `jordan-petra`, `egypt-pyramids`, `thailand`, `singapore`, `malaysia`, `indonesia-bali`, `sri-lanka`, `south-africa`, `hong-kong`, `china-wall`, `azerbaijan-baku`, `europe`, `europe-alps`

### 2b. Standing in for something we do NOT have — must be replaced

| Slot | What is shown now | What it is standing in for | Who must supply it |
|---|---|---|---|
| Hajj/Umrah package cover | A photograph of the **city** the package arrives in | A photograph of the actual package/group | Universal Brothers |
| Makkah accommodation card | Makkah skyline / Masjid al-Haram | **The actual hotel** (Dar Al Tawhid, Fairmont, Swissotel, Makkah Tower, voco …) | Hotels, or UB's own photography |
| Madinah accommodation card | Masjid an-Nabawi / Madinah | **The actual hotel** (Dar Al Taqwa, Hilton, Taibah Front …) | Hotels, or UB |
| Aziziya | Makkah skyline | The actual Aziziya building | Universal Brothers |
| About page aside | Masjid an-Nabawi | **Real office / team / group photography** | Universal Brothers |
| Contact page banner | Frere Hall, Karachi | The actual office, or Karachi imagery UB is happy with | Universal Brothers |
| Awards page | Generated seals + a Makkah banner | **Actual award certificates / trophies / photos** | Universal Brothers |
| Affiliations page | Generated seals + a banner | **Actual organisation logos** (IATA, TAAP, FPCCI …) | UB, under its membership terms |
| Media page | A Mina banner | **Actual news photos, gallery images, video thumbnails** | Universal Brothers |
| Testimonials | Generated avatars | **Real pilgrim photos**, with their permission | Universal Brothers |

### The rule that was applied throughout

> **Photograph the destination and the journey. Never claim a specific property.**

A photograph of Masjid al-Haram on a Makkah package is true of every Makkah
package. A stock photograph of a luxury hotel room captioned as "Dar Al Tawhid
Intercontinental" would be a fabricated business fact, and the whole project has
been built on not doing that. So accommodation cards show the **city**, captioned
as the city, and the hotel's real name, star rating, nights and distance sit as
text beside the image — all of it from the database.

---

## 3. OFFICIAL / FINAL IMAGES

**Currently: none.** No Universal Brothers photography exists in the repository
or the database.

### How replacement works (no code change required)

`App\Support\SiteImagery::resolve()` already prefers an uploaded CMS image over
the library, everywhere. So for each slot the client only has to upload through
the existing admin:

| To replace | Upload to |
|---|---|
| A package's card + hero | Admin → Packages → *package* → Cover Image |
| A hotel image | Admin → Hotels → *hotel* → Cover Image |
| Award images | Admin → Awards → *award* → Image |
| Affiliation logos | Admin → Affiliations → *affiliation* → Logo |
| Testimonial photos | Admin → Testimonials → *testimonial* → Photo |
| Homepage hero | Admin → Sliders (a slider row takes over the hero entirely) |
| About page image | Admin → Pages → About Us → Featured Image |
| Package gallery | Admin → Packages → *package* → Media |

No template edits, no redeploy. The library only fills slots that are still empty.

> **Blocker:** the production server is missing PHP's `fileinfo` extension, which
> Laravel's file handling uses for MIME detection. Admin image uploads are
> therefore at risk **on production**. Installing `ea-php84-php-fileinfo` needs
> root/WHM access. This must be resolved before the client starts uploading
> official photography, or the uploads will fail on the live site.

---

## 4. Open items

1. **Attribution block not yet placed.** 38 of the 45 photographs are CC BY or
   CC BY-SA, which require the photographer to be credited. A `.photo-credits`
   style exists but is not yet rendered in any template. If any of these images
   ship to production, a credits block or colophon must carry the list in
   `image-assets.md`. Deliberately not added yet, because it depends on which
   images survive the demo.
2. **Hotel photography is the biggest visible gap.** The Hajj detail page is the
   main conversion path and its accommodation cards are the one place a customer
   most wants to see the actual property.
3. **No video.** The brief allows a cinematic hero video; no legitimately
   licensed one was sourced, so the hero uses a high-resolution photograph.
4. **Page weight is image-dominated.** The homepage is 1.93 MB fully scrolled, of
   which 1.11 MB is photography. That is normal for an image-led travel site but
   is the obvious next optimisation if it matters: the renditions are WebP at
   quality 68 across 1440/1000/560, and the single largest file is the homepage
   hero at 415 kB. AVIF would cut it further at the cost of a build dependency.
5. **Dev-database test residue.** The local development database contains dozens
   of `Playwright E2E Test Package` and `Journey D Package` rows left over from
   before the E2E suite was pointed at `.env.testing`. They are not in production
   and not in the testing database, but they do pollute local visual QA. Worth a
   cleanup pass.

---

## 5. Verification performed

| Check | Result |
|---|---|
| All public pages render with real photography | 60 pages, 177 photograph references, 0 problems |
| Files referenced actually exist on disk | 0 missing |
| Placeholder vocabulary ("Photo Coming Soon", etc.) | 0 occurrences |
| `<img>` without an `alt` attribute | 0 |
| Broken / overflowing / distorted images | 0 at 1600, 768 and 390px across 6 page types |
| Horizontal page scroll introduced | none |
| WCAG AA contrast after adding photo-backed text | 596 pairs sampled, 0 below AA |
| Page weight with a cold cache (1440px, fully scrolled) | home 1.93 MB · Hajj listing 1.36 MB · Hajj detail 1.30 MB · tourism 1.22 MB |
| Library total weight | 45 photographs, 135 files, 11.9 MB (WebP at 1440/1000/560) |
