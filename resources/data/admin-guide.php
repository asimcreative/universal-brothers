<?php

/*
|--------------------------------------------------------------------------
| Admin guide content
|--------------------------------------------------------------------------
|
| Everything the help centre (/admin/guide), the "Need help?" panels and the
| dashboard onboarding say, in one version-controlled file. Edit the words
| here; no code change is needed. Rules for writers:
|
| - Plain business language. No "database", "model", "JSON", "slug" and so on.
|   If a technical idea is unavoidable, explain it the way you would to a new
|   member of office staff.
| - Use real examples from Universal Brothers (UB001, Dar Al Taqwa, the
|   Kaaba View Supplement) so the guide matches what the admin actually sees.
| - `links` point at admin pages by route name; a link whose route does not
|   exist is simply not shown.
|
| Each section:
|   title, group, icon, summary      shown in the guide index
|   why                              why this part of the admin matters
|   steps                            what to do, in order
|   example                          a worked example
|   tips                             safe-editing advice for this area
|   faqs                             [question, answer] pairs
|   links                            [label, route, params]
|
*/

return [

    'groups' => [
        'start' => 'Getting started',
        'packages' => 'Hajj packages',
        'reusable' => 'Reusable information',
        'website' => 'Website sections',
        'leads' => 'Enquiries, settings and the assistant',
        'publishing' => 'Publishing safely',
    ],

    'sections' => [

        'dashboard' => [
            'title' => 'The admin dashboard',
            'video' => 'dashboard',
            'group' => 'start',
            'icon' => 'bi-speedometer2',
            'summary' => 'Your starting point: how many packages are live, what needs attention, and shortcuts to everyday tasks.',
            'why' => 'The dashboard shows real numbers straight from the website, so you can see at a glance whether anything is waiting for you — an unanswered enquiry, a live package with missing prices, or a draft you have not finished.',
            'steps' => [
                'Check the four Hajj package cards: total, published (live), drafts and featured. Click any card to open that list.',
                'Look at "Needs attention". It lists live packages that are no longer complete and enquiries nobody has answered.',
                'Use "Unfinished drafts" to continue a package exactly where you stopped.',
                'Use "Quick actions" for the most common jobs, such as creating a Hajj package or viewing enquiries.',
                '"Recent activity" shows who published, archived or deleted something, and when.',
            ],
            'example' => 'The dashboard shows "12 Published Packages" and "4 New Inquiries". Click "New Inquiries" to see the four people waiting for a reply.',
            'tips' => [
                'Every number is counted live — nothing on the dashboard is an estimate.',
            ],
            'faqs' => [
                ['Why does a package appear under "Needs attention"?', 'It is live on the website but is missing something a customer needs, such as a room price. Click "Fix" to open the right step.'],
            ],
            'links' => [['Open the dashboard', 'admin.dashboard', []]],
        ],

        'website-content' => [
            'title' => 'Managing website content',
            'video' => 'navigation',
            'group' => 'start',
            'icon' => 'bi-window',
            'summary' => 'Where each part of the public website is edited, and how the menu is organised.',
            'why' => 'Knowing where things live saves time and avoids editing the wrong page.',
            'steps' => [
                'Hajj packages, templates and reusable information are at the top of the menu.',
                '"Umrah & Tourism" holds the other package types.',
                '"Website Content" covers pages (such as About Us), news, FAQs, testimonials, the media gallery and homepage sliders.',
                '"Company" covers awards, affiliations, offices and site settings.',
                'Use the "View website" icon at the top right to open the public site in a new tab and check your change.',
            ],
            'example' => 'To change the About Us text, open Website Content → Pages, click "Edit" beside About Us, change the text and save.',
            'tips' => [
                'Most lists have a search box and filters at the top. Use them before scrolling.',
                'Deleting always asks you to confirm first. Read the message — it tells you what will happen.',
            ],
            'faqs' => [
                ['I saved a change but the website looks the same.', 'Refresh the website page. If the item has an "Active" or "Published" switch, make sure it is on.'],
            ],
            'links' => [['Pages', 'admin.pages.index', []], ['Homepage sliders', 'admin.sliders.index', []]],
        ],

        'hajj-packages' => [
            'title' => 'Hajj packages — creating one step by step',
            'video' => 'new-package',
            'group' => 'packages',
            'icon' => 'bi-moon-stars',
            'summary' => 'The package builder walks you through fourteen steps, from the title to publishing. You can stop and continue later at any time.',
            'why' => 'A Hajj package holds a lot of information — options, prices in three currencies, hotels, the journey, Mina and Arafat, what is included. The builder splits it into small steps so nothing is missed.',
            'steps' => [
                'Open Hajj Packages → Add Hajj Package. To save typing, choose "From a template", or open a similar package and choose More → Duplicate.',
                'Step 1, Basic information: the title, the package code (for example UB025) and the number of days.',
                'Step 2, Package setup: arrival in Madinah or Jeddah, shifting or non-shifting, and whether Aziziya is included.',
                'Step 3, Hotel options: say "Yes" if customers choose between hotels, then name each option (Option A, Option B…).',
                'Step 4, Room prices: in each option\'s box add the room types and their prices in USD, SAR and PKR.',
                'Step 5, Hotels: pick each hotel from your saved list.',
                'Step 6, Journey plan: the day-by-day plan. Apply a template or copy it, then fill the dates.',
                'Steps 7 to 11: Mina, Arafat and Muzdalifah; transport and meals; what is included and not included; additional options; notes.',
                'Step 12, Photos & search engines: the main photo and how the package appears on Google.',
                'Step 13, Review: check the whole package. Warnings tell you what is missing; click "Edit" beside a section to fix it.',
                'Step 14, Save, preview & publish: preview it as a visitor would, then publish when everything is ready.',
            ],
            'example' => 'UB001 "Executive Platinum Intercon / Fairmont — Medinah First" is 13 days, starts in Madinah, and has Option A (Dar Al Tawhid Intercontinental) and Option B (Fairmont Clock Tower), each with its own Quad, Triple and Double prices.',
            'tips' => [
                'Press "Save draft" whenever you stop. Drafts are never shown on the website, and the package reopens on the step where you saved it.',
                'The checklist on the left shows what is finished. You do not have to work in order.',
                'The builder keeps a copy of unsaved typing in this browser. If the page closes by accident, it offers to restore it.',
            ],
            'faqs' => [
                ['Why can I not publish?', 'A live package needs a title, a code, the number of days, at least one hotel, and a price for every option. The Review step lists exactly what is missing.'],
                ['Can I change a package after publishing?', 'Yes. Open it, change it and press "Save changes". Live changes appear on the website straight away, so preview first if you are unsure.'],
            ],
            'links' => [['Add a Hajj package', 'admin.hajj-packages.create', []], ['All Hajj packages', 'admin.hajj-packages.index', []]],
        ],

        'package-options' => [
            'title' => 'Package options (A, B, C)',
            'video' => 'package-options',
            'group' => 'packages',
            'icon' => 'bi-signpost-split',
            'summary' => 'An option is a different hotel choice for the same package, with its own prices.',
            'why' => 'Customers often choose between two Makkah hotels for the same journey. Options keep each hotel\'s prices together so a price is never shown against the wrong hotel.',
            'steps' => [
                'In Step 3 choose "Yes — customers choose an option". Option A and Option B are added for you.',
                'Give each option a name customers will recognise — usually its hotel, for example "Fairmont Clock Tower".',
                'Press "Add option" for Option C.',
                'In Room prices and Hotels, each option now has its own coloured box. Enter that option\'s prices and hotels inside its box.',
                'Things that are the same for every option (for example the Madinah hotel) go in the grey "for every option" box.',
            ],
            'example' => 'UB001: Option A — Dar Al Tawhid Intercontinental, Triple US$22,450. Option B — Fairmont Clock Tower, Quad US$16,300. Both stay at Dar Al Taqwa in Madinah, so that hotel goes in the "for every option" box.',
            'tips' => [
                'Removing an option also removes the prices and hotels in its box. The builder tells you how many before it does.',
                'Renaming an option\'s letter moves its prices and hotels with it.',
            ],
            'faqs' => [
                ['My package has only one set of hotels.', 'Choose "No — one set of hotels". Everything then goes in the single box.'],
            ],
            'links' => [],
        ],

        'room-pricing' => [
            'title' => 'Room types and prices',
            'video' => 'room-types',
            'group' => 'packages',
            'icon' => 'bi-cash-coin',
            'summary' => 'Prices per person for each room type, in US dollars, Saudi riyals and Pakistani rupees.',
            'why' => 'Visitors switch between USD, SAR and PKR on the package page. Each currency shows exactly what you enter here — nothing is converted automatically.',
            'steps' => [
                'In Step 4, inside the right option\'s box, press "Add Quad, Triple & Double", or "Add room type" for one at a time.',
                'Choose the room type by name: Quad, Triple, Double, Sharing room, Twin, Single, or "Other" to type your own.',
                'Type each price you have. Leave a currency empty if the brochure does not give it — never guess.',
                'Untick "Available" for a room type that is sold out or not offered, instead of deleting it.',
                'Check the price summary under the boxes before moving on.',
            ],
            'example' => 'Quad Sharing — USD 16,300, SAR 59,500, PKR 4,640,000. Kaaba-view rooms are not a room type: add "Kaaba View Supplement" (US$2,200 per person) in Step 10, Additional options.',
            'tips' => [
                'The "From" price on the website is worked out from the cheapest available USD price.',
                'Type numbers only, without commas or currency signs.',
            ],
            'faqs' => [
                ['Where do supplements go?', 'Kaaba-view and other extra-cost choices go in Step 10, Additional options, with their own price and conditions.'],
            ],
            'links' => [],
        ],

        'hotels' => [
            'title' => 'Hotels and accommodation',
            'video' => 'hotels',
            'group' => 'reusable',
            'icon' => 'bi-building',
            'summary' => 'Hotels are saved once and picked in any package. A package can still adjust the name for itself.',
            'why' => 'The same hotels appear in many packages. Saving them once keeps names and star ratings consistent, and shows how many packages use each hotel.',
            'steps' => [
                'In Step 5 press "Add hotel" in the right box, choose the city, then pick the hotel from "Hotel from your list".',
                'The name and stars fill in for you. You can change the name for this package only — the saved hotel is not changed.',
                'Use "Find a hotel" to search the list and see each hotel\'s details before choosing.',
                'Missing hotel? Press "New hotel". It is saved to your list and picked straight away.',
                'To change a hotel for every package, open Reusable Content → Hotels & Accommodation.',
            ],
            'example' => 'Dar Al Taqwa (Madinah, 5 stars) is used in 7 packages. Pick it once per package; if its details change, edit it in the hotel list and update those 7 packages in one go.',
            'tips' => [
                '"Shared information" is the saved hotel. "Package-specific information" is what a single package shows. Editing inside a package changes only that package.',
                'A hotel used by any package cannot be deleted. Archive it instead — packages keep their details.',
            ],
            'faqs' => [
                ['I edited a hotel in the list but a package still shows the old name.', 'Packages keep their own copy until you choose to update them. Open the hotel, click "Where it is used" and press "Update packages".'],
            ],
            'links' => [['Hotels & Accommodation', 'admin.library.index', ['type' => 'hotels']]],
        ],

        'itinerary' => [
            'title' => 'Journey plan (itinerary)',
            'video' => 'itinerary',
            'group' => 'packages',
            'icon' => 'bi-calendar-week',
            'summary' => 'The day-by-day plan: the date, the Islamic date, where pilgrims are, where they stay and how they travel.',
            'why' => 'Pilgrims read the journey plan to understand the whole trip. A complete, correctly dated plan prevents many phone calls.',
            'steps' => [
                'In Step 6, start faster: choose a journey template, or "Copy from another package".',
                'Otherwise press "Add day". Each new day gets the next day number and the next date.',
                'For each day enter the English date (use the calendar), the Islamic date as written in the brochure, the city, where they stay, and transport if they travel.',
                'Use the arrows to reorder days, the copy button to duplicate a day, and "Renumber" to number days 1, 2, 3… again.',
                'Use "Fill dates" to set every date at once from the first day.',
            ],
            'example' => 'Day 8 — 14 May 2027 — 08 Zil Hajj — To Mina — Stay: Zone 1 near Jamarat, Category A — Transport: private luxury bus.',
            'tips' => [
                'The Review step warns if the plan is shorter than the package length or the dates are out of order.',
                'Save a plan you use often with "Save as template".',
            ],
            'faqs' => [
                ['The brochure says 14 days but lists 13.', 'Enter what the brochure prints. The Review step will point it out so you can confirm with the office.'],
            ],
            'links' => [['Journey templates', 'admin.library.index', ['type' => 'journey-templates']]],
        ],

        'mashaer' => [
            'title' => 'Mina, Arafat and Muzdalifah',
            'video' => 'mashaer',
            'group' => 'reusable',
            'icon' => 'bi-geo-alt',
            'summary' => 'The arrangements for the days of Hajj: the camp, tent, meals and transport.',
            'why' => 'Mina (the tent city), Arafat (the day of standing) and Muzdalifah (the night under the sky) are the heart of Hajj. Pilgrims want to know exactly what is arranged.',
            'steps' => [
                'In Step 7, for each place, choose a saved arrangement to fill the card.',
                'Change anything that is different for this package. That change applies to this package only.',
                'Leave a card empty if the package does not describe that place.',
                'To change an arrangement for every package, edit it under Reusable Content → Mina, Arafat & Muzdalifah.',
            ],
            'example' => 'Mina — Maktab A, Category A, Zone 1, sofa cum bed, full board buffet. Arafat — air-conditioned marquee with meals and hot and cold drinks.',
            'tips' => [
                'Shared arrangements show how many packages use them. Editing a shared arrangement does not change those packages until you choose to update them.',
            ],
            'faqs' => [
                ['I changed a Mina card in one package. Did every package change?', 'No. The card in a package is that package\'s own copy. Only editing the saved arrangement under Reusable Content, and then pressing "Update packages", changes other packages.'],
                ['Muzdalifah has nothing special arranged.', 'Leave the Muzdalifah card empty. The package page then simply does not show it.'],
            ],
            'links' => [['Mina, Arafat & Muzdalifah', 'admin.library.index', ['type' => 'mashaer']]],
        ],

        'transport' => [
            'title' => 'Transport',
            'video' => 'transport-meals',
            'group' => 'reusable',
            'icon' => 'bi-bus-front',
            'summary' => 'Airport transfers, travel between cities and Mashaer transport — included or at extra cost.',
            'why' => 'Customers compare what transport is included. Clear "included" and "extra cost" lines prevent disputes.',
            'steps' => [
                'In Step 8 press "Add saved transport" and tick what this package includes.',
                'Or press "Add your own" for something unique.',
                'Untick "In price" for a paid extra, then enter its price, currency and what the price covers.',
            ],
            'example' => 'Included: group arrival transfer by bus from Jeddah or Madinah Hajj Terminal to the hotel. Extra: private car from Jeddah Airport to the Makkah hotel — US$165 per person, round trip.',
            'tips' => ['The same saved transport line cannot be added twice to one package.'],
            'faqs' => [
                ['How do I show a private car as an extra?', 'Add the transport line, untick "In price", then enter the price, the currency and what it covers, for example "per person, round trip".'],
            ],
            'links' => [['Transport', 'admin.library.index', ['type' => 'transport']]],
        ],

        'meals' => [
            'title' => 'Meal plans',
            'video' => 'transport-meals',
            'group' => 'reusable',
            'icon' => 'bi-cup-hot',
            'summary' => 'Standard meal arrangements such as half board or full board, set for each hotel.',
            'why' => 'Meals differ between hotels and camps. Showing them per hotel answers one of the most common customer questions.',
            'steps' => [
                'In Step 8, choose a meal plan and press "Apply to all" to set it on every Makkah and Madinah hotel at once.',
                'Or set it hotel by hotel in Step 5 with the "Meal plan" list.',
                'Check the summary — every hotel should show a meal plan.',
            ],
            'example' => 'Half board (breakfast & dinner) for Dar Al Taqwa and Swissotel; full board buffet for the Aziziya building.',
            'tips' => ['You can still adjust the meal wording for one hotel in one package.'],
            'faqs' => [
                ['One hotel has a different meal plan from the rest.', 'Use "Apply to all" first, then change that one hotel in Step 5. The Review step lists any hotel still without a meal plan.'],
            ],
            'links' => [['Meal plans', 'admin.library.index', ['type' => 'meal-plans']]],
        ],

        'inclusions' => [
            'title' => 'Included services',
            'video' => 'inclusions',
            'group' => 'reusable',
            'icon' => 'bi-check2-circle',
            'summary' => 'The list of what the package price covers.',
            'why' => 'This list is what customers compare between companies. Saved lines keep the wording identical across packages.',
            'steps' => [
                'In Step 9, in "Included in this package", press "Add saved" and tick the lines that apply.',
                'Press "Add your own" for anything unique to this package.',
                'Use the arrows to put the most important lines first.',
            ],
            'example' => '"Ziyarat in Madinah with guidance", "Religious guide book", "Meet & assist at the airport Jeddah/Madinah Hajj Terminal".',
            'tips' => ['A line that is already in the package is greyed out, so it cannot be added twice.'],
            'faqs' => [
                ['A saved line is almost right for this package.', 'Add it, then change the wording in the package. The badge changes to "Changed for this package only" and the saved line stays as it was.'],
            ],
            'links' => [['Included services', 'admin.library.index', ['type' => 'inclusions']]],
        ],

        'exclusions' => [
            'title' => 'Not included',
            'video' => 'exclusions',
            'group' => 'reusable',
            'icon' => 'bi-x-circle',
            'summary' => 'What customers pay for separately.',
            'why' => 'Being clear about what is not included is as important as what is.',
            'steps' => [
                'In Step 9, in "Not included in this package", press "Add saved" or "Add your own".',
                'Include approximate costs where the brochure gives them.',
            ],
            'example' => '"Airline ticket (approx. PKR 335,000 from Karachi)", "Qurbani actual cost (approx. US$200)".',
            'tips' => [],
            'faqs' => [
                ['Should the air ticket be listed?', 'Yes, whenever the price does not include it. Customers expect to see the ticket, visa fee and Qurbani under "Not included".'],
            ],
            'links' => [['Not included', 'admin.library.index', ['type' => 'exclusions']]],
        ],

        'upgrades' => [
            'title' => 'Additional options (upgrades)',
            'video' => 'upgrades',
            'group' => 'reusable',
            'icon' => 'bi-plus-square',
            'summary' => 'Extra services customers may choose at extra cost.',
            'why' => 'Upgrades such as a Kaaba-view room or an extra night add value and must show a clear price and conditions.',
            'steps' => [
                'In Step 10 press "Add saved option", or "Add your own".',
                'Enter the price, the currency, and what the price is for (for example "per person").',
                'Leave the price empty for "price on request".',
                'Add any conditions, such as "subject to availability".',
            ],
            'example' => 'Kaaba View Supplement — US$2,200 per person. Additional Madinah night, Double — US$850 per night per person.',
            'tips' => [],
            'faqs' => [
                ['Should a Kaaba-view room be a room type?', 'No. Add it here as an additional option with its own price, so the room prices stay comparable between packages.'],
            ],
            'links' => [['Additional options', 'admin.library.index', ['type' => 'upgrades']]],
        ],

        'notes' => [
            'title' => 'Notes and policies',
            'video' => 'notes',
            'group' => 'reusable',
            'icon' => 'bi-journal-text',
            'summary' => 'What customers must read before booking — and a private box for your team.',
            'why' => 'Notes set expectations: ticket and Qurbani, room retention, price changes. Internal notes let the office keep reminders next to the package without customers seeing them.',
            'steps' => [
                'In Step 11 press "Add saved note" for standard notes, or "Write a note" for this package only.',
                'Switch on "Important" for policies customers must not miss. They are shown first and highlighted.',
                'Use the "Internal notes" box for anything only staff should see.',
            ],
            'example' => 'Important policy: "Ticket & Qurbani not included." Package note: "Makkah Tower rooms have stairs." Internal note: "Brochure lists 13 days under a 14-day heading — confirm with the office."',
            'tips' => [
                'Internal notes are never shown on the website and are never given to the AI assistant.',
                'Never put private information (supplier prices, phone numbers of staff) in customer notes.',
            ],
            'faqs' => [
                ['Where do reusable notes come from?', 'Reusable Content → Notes & Policies. Write a note there once and add it to any package.'],
            ],
            'links' => [['Notes & policies', 'admin.library.index', ['type' => 'notes']]],
        ],

        'templates' => [
            'title' => 'Package templates and copying',
            'video' => 'templates',
            'group' => 'packages',
            'icon' => 'bi-files',
            'summary' => 'Start a new package from a template, duplicate a package, or copy one part from another package.',
            'why' => 'Most new packages are very similar to an existing one. Copying saves hours of typing and avoids mistakes.',
            'steps' => [
                'Duplicate: in Hajj Packages, open a package\'s "…" menu → Duplicate. A draft copy is made with a new code; the original is not changed.',
                'Save as template: open a finished package → More → Save as template.',
                'Start from a template: Hajj Packages → From a template, or Package Templates → Use.',
                'Apply a template to a draft: More → Apply a template. This replaces the draft\'s content, so it is only possible on drafts.',
                'Copy one part: in any step press "Copy from another package", choose the package and tick what to copy.',
            ],
            'example' => 'To create UB026 like UB024 but with different dates: duplicate UB024, change the title and code, use "Fill dates" in the journey plan, check prices, then publish.',
            'tips' => [
                'Every copy asks you to confirm and tells you exactly what will be replaced.',
                'Templates never contain the package code, web address, photos or internal notes.',
            ],
            'faqs' => [
                ['Will editing a template change packages made from it?', 'No. Each package keeps its own copy.'],
            ],
            'links' => [['Package templates', 'admin.package-templates.index', []]],
        ],

        'media' => [
            'title' => 'Photos and images',
            'video' => 'images',
            'group' => 'packages',
            'icon' => 'bi-images',
            'summary' => 'The main package photo, the sharing image, gallery photos, and photos for hotels and camps.',
            'why' => 'Good photos build trust. Descriptions ("alt text") help visitors using screen readers and help Google.',
            'steps' => [
                'In Step 12 upload the main photo. It appears at the top of the package page and on package cards.',
                'Optionally upload a social sharing image — shown when the page is shared on WhatsApp or Facebook.',
                'Add more photos with "Add photo" and describe each one.',
                'Hotel and Mina/Arafat photos are added to the saved hotel or arrangement under Reusable Content.',
            ],
            'example' => 'Main photo: a wide photograph of the Haram at dusk, 1600 × 900 pixels, under 4 MB. Description: "Masjid al-Haram at sunset".',
            'tips' => [
                'Use JPG, PNG or WebP. Wide (landscape) photos look best.',
                'Without a main photo the website shows a suitable stock photograph.',
            ],
            'faqs' => [
                ['My photo was refused.', 'Check the size (main photo up to 4 MB, gallery photos up to 8 MB) and the type (JPG, PNG or WebP). Resize a large phone photo before uploading.'],
            ],
            'links' => [['Media gallery', 'admin.media.index', []]],
        ],

        'faqs' => [
            'title' => 'FAQs',
            'group' => 'website',
            'icon' => 'bi-question-circle',
            'summary' => 'The frequently asked questions shown on the FAQs page. The AI assistant also uses them.',
            'why' => 'A clear answer on the website saves a phone call, and the assistant answers from the same text.',
            'steps' => [
                'Open Website Content → FAQs → New FAQ.',
                'Choose the category (for example Hajj), write the question the way a customer asks it, and a short, clear answer.',
                'Keep "Active" on to show it.',
            ],
            'example' => 'Question: "Is the airline ticket included in the Hajj package price?" Answer: "No. The ticket is booked separately; the approximate fare is listed under Not included on each package."',
            'tips' => ['After changing FAQs, press "Rebuild knowledge index" on the AI Assistant page so the assistant uses the new answer.'],
            'faqs' => [
                ['The assistant still gives the old answer.', 'Press "Rebuild knowledge index" on the AI Assistant page after changing an FAQ.'],
                ['How do I hide an FAQ for now?', 'Open it and switch off "Active". It stays saved and can be shown again later.'],
            ],
            'links' => [['FAQs', 'admin.faqs.index', []]],
        ],

        'awards' => [
            'title' => 'Awards',
            'group' => 'website',
            'icon' => 'bi-trophy',
            'summary' => 'Recognitions the company has received, shown on the Awards page.',
            'why' => 'Awards reassure first-time customers.',
            'steps' => [
                'Open Company → Awards → New.',
                'Enter the award name, the awarding organisation and the year, and upload the logo or certificate image.',
                'Use "Sort Order" to decide which appears first.',
            ],
            'example' => 'Award name, awarding organisation, year 2026, and a clear image of the certificate.',
            'tips' => ['Only add awards you can show proof of.'],
            'faqs' => [
                ['How do I hide an award without deleting it?', 'Open it and switch off "Active", or give it a later sort order so it appears last.'],
            ],
            'links' => [['Awards', 'admin.awards.index', []]],
        ],

        'affiliations' => [
            'title' => 'Affiliations',
            'group' => 'website',
            'icon' => 'bi-diagram-3',
            'summary' => 'Memberships and registrations, such as IATA or the Ministry of Religious Affairs.',
            'why' => 'Affiliations show customers the company is registered and recognised.',
            'steps' => [
                'Open Company → Affiliations → New.',
                'Enter the organisation name, the year, an optional link and the logo.',
            ],
            'example' => 'IATA — logo — link to the IATA website.',
            'tips' => [],
            'faqs' => [
                ['The logo looks stretched.', 'Upload a logo with a transparent or white background and some empty space around it. Square or wide logos both work.'],
            ],
            'links' => [['Affiliations', 'admin.affiliations.index', []]],
        ],

        'testimonials' => [
            'title' => 'Testimonials',
            'group' => 'website',
            'icon' => 'bi-chat-quote',
            'summary' => 'What pilgrims say about their journey — written quotes or videos.',
            'why' => 'Real experiences from past pilgrims are the most persuasive content on the website.',
            'steps' => [
                'Open Website Content → Testimonials → New.',
                'Enter the pilgrim\'s name, the quote, the rating, and optionally the package they travelled on.',
                'For a video testimonial, paste the YouTube embed link.',
            ],
            'example' => 'Name: Haseeb Jawed. Quote: a sentence about the Hajj experience. Rating: 5.',
            'tips' => ['Only publish testimonials the pilgrim agreed to share.'],
            'faqs' => [
                ['Can I show which package the pilgrim travelled on?', 'Yes. Type it in the package box, for example "UB001 — Platinum Hajj". On video testimonials it is shown under the pilgrim\'s name.'],
            ],
            'links' => [['Testimonials', 'admin.testimonials.index', []]],
        ],

        'news' => [
            'title' => 'News and the media gallery',
            'group' => 'website',
            'icon' => 'bi-newspaper',
            'summary' => 'News articles, plus the photo and video gallery.',
            'why' => 'Regular news (for example new Hajj policies or flight dates) keeps the website current.',
            'steps' => [
                'News: Website Content → News → New. Add a title, a short excerpt, the article and a cover image, then switch on "Active".',
                'Gallery: Website Content → Media Gallery → New. Choose image or video and upload or paste the link.',
            ],
            'example' => 'News title: "Hajj 2027 registration open". Excerpt: one sentence. Cover image: the Haram.',
            'tips' => ['Write the excerpt as one clear sentence — it is shown in news lists.'],
            'faqs' => [
                ['An article should stop showing on the website.', 'Open it and switch off "Active". It stays saved so you can show it again later.'],
            ],
            'links' => [['News', 'admin.news.index', []], ['Media gallery', 'admin.media.index', []]],
        ],

        'enquiries' => [
            'title' => 'Enquiries (leads)',
            'group' => 'leads',
            'icon' => 'bi-envelope',
            'summary' => 'Messages from the contact form, package enquiry forms and the AI assistant.',
            'why' => 'Every enquiry is a potential pilgrim. Answering quickly matters.',
            'steps' => [
                'Open Enquiries → Inquiries. New ones are marked "New" and counted in the menu.',
                'Click "View" to read the enquiry: name, phone, email, the package they asked about and their message.',
                'After contacting them, change the status to "Contacted", and later "Closed".',
            ],
            'example' => 'An enquiry about UB011 from the package page — call the number given, then set the status to "Contacted".',
            'tips' => ['Do not delete enquiries you have not answered.'],
            'faqs' => [
                ['An enquiry came from the chat assistant. Is it different?', 'No. It is handled the same way. The message shows what the visitor asked the assistant.'],
            ],
            'links' => [['Inquiries', 'admin.inquiries.index', []]],
        ],

        'settings' => [
            'title' => 'Website settings',
            'group' => 'leads',
            'icon' => 'bi-gear',
            'summary' => 'Company details shown across the website: name, tagline, licence numbers, social links and figures such as years in operation.',
            'why' => 'These details appear in the footer, the About section and search results. Keeping them correct matters for trust and for licensing.',
            'steps' => [
                'Open Company → Site Settings.',
                'Change the value you need, for example the Hajj registration number or the Facebook link.',
                'Press "Save Settings" at the bottom.',
            ],
            'example' => '"years_in_operation" — 20. "government_license_no" — as printed on the licence.',
            'tips' => [
                'Fields for secret keys stay empty on screen. Leave them empty to keep the current value.',
                'Only change integration keys (analytics, reCAPTCHA) if you know the new value is correct.',
            ],
            'faqs' => [
                ['I saved a secret key field empty. Did I delete the key?', 'No. An empty secret field keeps the current value. Type a new value only when you want to replace it.'],
            ],
            'links' => [['Site settings', 'admin.settings.index', []]],
        ],

        'ai-assistant' => [
            'title' => 'AI assistant settings',
            'group' => 'leads',
            'icon' => 'bi-stars',
            'summary' => 'The chat assistant on the website that answers visitors\' questions from your packages, FAQs and pages.',
            'why' => 'The assistant answers questions at any hour and collects enquiries. Its settings control what it is called, how it speaks and how much it can be used.',
            'steps' => [
                'Open AI Assistant. "General" controls the name, welcome message and whether it is shown on the website.',
                '"Safety & limits" sets how many messages one visitor can send per day (currently 50).',
                'After changing packages, FAQs or pages, press "Rebuild knowledge index" so the assistant knows the latest information.',
                'Use "AI Test Panel" to ask it a question yourself before visitors do.',
                '"AI Conversations" shows what visitors asked.',
            ],
            'example' => 'Ask the test panel: "What is the price of UB010 in PKR?" and check it quotes every option.',
            'tips' => [
                'Prices are always read from the package itself, so the assistant never quotes an old price.',
                'Internal notes are never given to the assistant.',
            ],
            'faqs' => [
                ['The assistant says it cannot answer right now.', 'The account may be out of credit or the daily limit reached. Check the AI Assistant page for the status message.'],
            ],
            'links' => [['AI assistant', 'admin.ai.index', []], ['AI test panel', 'admin.ai.test', []]],
        ],

        'preview-publishing' => [
            'title' => 'Preview and publishing',
            'video' => 'review',
            'group' => 'publishing',
            'icon' => 'bi-globe2',
            'summary' => 'The difference between saving a draft, previewing and publishing — and how to take a package off the website.',
            'why' => 'Publishing puts a package in front of customers straight away. Previewing first catches mistakes.',
            'steps' => [
                'Save draft: saves your work. Nothing appears on the website.',
                'Preview: opens the package exactly as a visitor would see it, with a yellow "Preview" bar. Only logged-in administrators can open it, and the link stops working after an hour.',
                'Publish: puts the package on the website. It is refused until the Review step shows no problems.',
                'To take a live package down, use More → Move to draft, or Archive to hide it from the list as well.',
            ],
            'example' => 'Finish UB025, press "Save & preview", check prices in all three currencies, then press "Publish".',
            'tips' => [
                'A live package cannot be deleted. Move it to draft or archive it first.',
                'Archived packages can be restored from the Archived tab.',
            ],
            'faqs' => [
                ['The preview link says it has expired.', 'Preview links work for one hour. Press "Save & preview" again for a new link.'],
                ['Why is the Publish button greyed out?', 'The Review step still shows a red problem, such as an option without a room price. Fix it and the button becomes available.'],
            ],
            'links' => [['Hajj packages', 'admin.hajj-packages.index', []]],
        ],

        'safe-editing' => [
            'title' => 'Safe editing rules',
            'video' => 'reusable-information',
            'group' => 'publishing',
            'icon' => 'bi-shield-check',
            'summary' => 'Simple rules that keep the website correct and nothing lost.',
            'why' => 'A few habits prevent almost every mistake.',
            'steps' => [
                'Work on a draft or a duplicate when making big changes to a live package.',
                'Preview before publishing.',
                'Leave a price empty rather than guessing it.',
                'Read confirmation messages — they say exactly what will change.',
                'Change shared information (hotels, notes, transport) in Reusable Content, then review the packages that use it before updating them.',
                'Archive instead of deleting whenever you might need something again.',
                'Keep staff-only information in Internal notes, never in customer notes.',
                'Log out on shared computers.',
            ],
            'example' => 'The Swissotel name changes: edit it once in Hotels, open "Where it is used", check the list of live packages, then press "Update packages".',
            'tips' => [],
            'faqs' => [
                ['I changed something by mistake.', 'Packages: reopen the package and correct it. Deleted items: archived items can be restored; ask a developer if something was deleted.'],
            ],
            'links' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Guided tour
    |--------------------------------------------------------------------------
    | `target` is a data-tour key placed on a page element. On phones, targets
    | inside the menu open the menu first.
    */
    'tour' => [
        ['target' => 'dashboard', 'title' => 'Your dashboard', 'text' => 'Live numbers for your packages and enquiries, plus anything that needs attention. Every card opens the matching list.'],
        ['target' => 'sidebar', 'title' => 'The menu', 'text' => 'Everything in the admin is in this menu, grouped by purpose. On a phone, open it with the menu button at the top left.', 'menu' => true],
        ['target' => 'nav-hajj', 'title' => 'Hajj packages', 'text' => 'Create, edit, preview and publish Hajj packages. The step-by-step builder guides you through each package.', 'menu' => true],
        ['target' => 'nav-reusable', 'title' => 'Reusable information', 'text' => 'Hotels, transport, meals, notes and more — saved once and picked in any package. Each item shows how many packages use it.', 'menu' => true],
        ['target' => 'nav-templates', 'title' => 'Templates', 'text' => 'Start new packages from a template instead of typing everything again.', 'menu' => true],
        ['target' => 'nav-enquiries', 'title' => 'Enquiries', 'text' => 'Messages from visitors. The red number shows enquiries waiting for a reply.', 'menu' => true],
        ['target' => 'nav-settings', 'title' => 'Website settings', 'text' => 'Company details, licence numbers and social links shown across the website.', 'menu' => true],
        ['target' => 'nav-ai', 'title' => 'AI assistant', 'text' => 'The website chat assistant: its name, daily limits, a test panel and what visitors asked.', 'menu' => true],
        ['target' => 'help', 'title' => 'Help is always here', 'text' => 'Open the guide from this icon any time. Every step of the package builder also has a "Need help?" panel.'],
    ],
];
