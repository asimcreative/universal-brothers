<?php

/*
|--------------------------------------------------------------------------
| "Need help?" panels in the Hajj package builder
|--------------------------------------------------------------------------
|
| One entry per builder step, answering the five questions a new admin asks:
| what is this for, what do I enter, is it required, an example, and what
| happens after saving. Plain language only — see resources/data/admin-guide.php
| for the writing rules. `guide` links the panel to the full guide section and
| `video` to the matching Video Training chapter (admin-video-training.php).
|
*/

return [
    'basics' => [
        'purpose' => 'This section identifies the package and controls how it appears on the website — the heading, the code your office uses, and how long it lasts.',
        'enter' => 'The package title customers will see, a unique package code, the number of days and the Hajj year. A short description helps on package cards.',
        'required' => 'The title is needed to save a draft. The title, code and number of days are needed before the package can be published.',
        'example' => 'Title "Executive Platinum Swissotel — Madinah First", code UB004, 14 days, Hajj year 2027.',
        'after' => 'Nothing appears on the website until you publish. The web address is made from the title automatically.',
        'guide' => 'hajj-packages',
        'video' => 'basic-information',
    ],
    'setup' => [
        'purpose' => 'These choices describe the shape of the journey and decide which later steps apply.',
        'enter' => 'Where pilgrims arrive, whether they keep the same Makkah hotel (non-shifting) or move (shifting), and whether an Aziziya stay is included, optional or not offered.',
        'required' => 'Not required to publish, but customers compare packages by these choices, so set them.',
        'example' => 'UB015 "Flex 14": arrives in Madinah, shifting, Aziziya included.',
        'after' => 'Choosing Aziziya "Included" or "Optional upgrade" shows the Aziziya details card in the Hotels step.',
        'guide' => 'hajj-packages',
        'video' => 'package-settings',
    ],
    'options' => [
        'purpose' => 'A package option is a different hotel or service choice available to the customer for the same journey, with its own prices.',
        'enter' => 'Choose whether customers pick between hotels. If yes, give each option a letter and a name, usually its hotel.',
        'required' => 'Only if the package really offers a choice. Every option you add needs its own room price before publishing.',
        'example' => 'Option A — Dar Al Tawhid Intercontinental. Option B — Fairmont Clock Tower.',
        'after' => 'Each option gets its own coloured box in Room prices and Hotels, so its prices and hotels never mix with another option\'s.',
        'guide' => 'package-options',
        'video' => 'package-options',
    ],
    'pricing' => [
        'purpose' => 'Room prices are what customers pay per person. Visitors switch between US dollars, Saudi riyals and Pakistani rupees on the package page, and each shows exactly what you type here.',
        'enter' => 'For each option, the room types offered (Quad, Triple, Double, Sharing room or your own) and their prices in each currency you have.',
        'required' => 'At least one available room type with a price, and a price for every option, before publishing.',
        'example' => 'Option B, Quad Sharing — USD 16,300 · SAR 59,500 · PKR 4,640,000.',
        'after' => 'The lowest available USD price becomes the "From" price on the website. Empty currencies show as "N/A" when a visitor switches to them.',
        'guide' => 'room-pricing',
        'video' => 'room-types',
    ],
    'hotels' => [
        'purpose' => 'This section tells visitors where they will stay during the journey.',
        'enter' => 'For each city and option, pick the hotel from your saved list, then check the stars, meal plan and nights.',
        'required' => 'At least one hotel before publishing.',
        'example' => 'For every option: Madinah — Dar Al Taqwa, 5 stars, half board, 3 nights. Option A: Makkah — Dar Al Tawhid Intercontinental, 4 nights.',
        'after' => 'Hotels appear in the Accommodation section of the package page. A name you change here changes this package only; the saved hotel stays the same.',
        'guide' => 'hotels',
        'video' => 'hotels',
    ],
    'journey' => [
        'purpose' => 'Add the journey day by day so visitors can understand the complete travel plan.',
        'enter' => 'For each day: day number, English date, Islamic date, city or place, where pilgrims stay, transport if they travel, and a short description.',
        'required' => 'Not required to publish, but the Review step warns if the plan is missing or shorter than the package.',
        'example' => 'Day 8 · 14/05/2027 · 08 Zil Hajj · To Mina · Zone 1 near Jamarat, Category A · Private luxury bus.',
        'after' => 'Days appear in order in the Itinerary section of the package page.',
        'guide' => 'itinerary',
        'video' => 'itinerary',
    ],
    'mashaer' => [
        'purpose' => 'Mina is the tent city where pilgrims stay for the days of Hajj, Arafat is where they spend the day of standing, and Muzdalifah is where they spend the night under the sky. This step describes what is arranged at each.',
        'enter' => 'Pick a saved arrangement for each place, then change anything different for this package.',
        'required' => 'Not required, but pilgrims look for these details.',
        'example' => 'Mina — Maktab A, Category A, Zone 1, sofa cum bed, full board buffet.',
        'after' => 'Shown in the "Mina & Arafat" section of the package page. Changes here apply to this package only.',
        'guide' => 'mashaer',
        'video' => 'mashaer',
    ],
    'transport' => [
        'purpose' => 'Transport shows how pilgrims travel and what costs extra. Meal plans show what they eat at each hotel.',
        'enter' => 'Add saved transport lines, mark paid extras with their price, and set a meal plan for the hotels.',
        'required' => 'Not required, but strongly recommended.',
        'example' => 'Included: Makkah → Madinah by bullet train or bus. Extra: Jeddah Airport → Makkah hotel by car, US$165 per person, round trip.',
        'after' => 'Transport appears in the Transportation section; meal plans appear beside each hotel.',
        'guide' => 'transport',
        'video' => 'transport-meals',
    ],
    'services' => [
        'purpose' => 'These two lists tell customers exactly what the price covers and what they pay for separately.',
        'enter' => 'Add saved lines to each list, or write your own. Put the most important first.',
        'required' => 'Not required, but customers compare packages by these lists.',
        'example' => 'Included: "Ziyarat in Madinah with guidance". Not included: "Airline ticket (approx. PKR 335,000 from Karachi)".',
        'after' => 'Shown in the "What\'s included" section. The same line cannot appear twice.',
        'guide' => 'inclusions',
        'video' => 'inclusions',
    ],
    'extras' => [
        'purpose' => 'These are additional services customers may choose at extra cost.',
        'enter' => 'The option name, a short description, the price and currency (or leave the price empty for "on request"), and any conditions.',
        'required' => 'Optional.',
        'example' => 'Kaaba View Supplement — US$2,200 per person — subject to availability.',
        'after' => 'Shown in the Upgrades section of the package page.',
        'guide' => 'upgrades',
        'video' => 'upgrades',
    ],
    'notes' => [
        'purpose' => 'Notes customers must read before booking, and a private place for staff notes.',
        'enter' => 'Saved notes from your library, notes written for this package, "Important" policies, and internal notes for your team.',
        'required' => 'Not required, but ticket, Qurbani and price-change notes are expected on every Hajj package.',
        'example' => 'Important policy: "Ticket & Qurbani not included." Internal: "Confirm the Makkah hotel contract by March."',
        'after' => 'Customer notes appear under "Important Notes", important ones first. Internal notes are never shown on the website and never given to the AI assistant.',
        'guide' => 'notes',
        'video' => 'notes',
    ],
    'media' => [
        'purpose' => 'Photos make the package page trustworthy; the search engine fields decide how it appears on Google and when shared.',
        'enter' => 'A main photo, optional gallery photos with descriptions, and optionally a Google title and description.',
        'required' => 'Not required. Without a main photo the website uses a suitable stock photograph; the Review step reminds you.',
        'example' => 'Main photo: the Haram at dusk, landscape, under 4 MB. Google title: "Executive Platinum Swissotel — Hajj 2027 | Universal Brothers".',
        'after' => 'The main photo appears at the top of the package page and on package cards.',
        'guide' => 'media',
        'video' => 'images',
    ],
    'review' => [
        'purpose' => 'One page with the whole package, so you can check everything before customers see it.',
        'enter' => 'Nothing to enter. Read each section and use "Edit" beside anything that needs changing.',
        'required' => 'Problems in red must be fixed before publishing. Amber advice is worth checking but does not stop publishing.',
        'example' => '"Option B has no hotel yet" — click Edit beside Hotels, add the hotel, come back to Review.',
        'after' => 'Once you have seen the review with no red problems, saving marks "Final review completed" in the checklist.',
        'guide' => 'preview-publishing',
        'video' => 'review',
    ],
    'publish' => [
        'purpose' => 'The three ways to finish: keep it as a draft, see it as a visitor, or put it on the website.',
        'enter' => 'Choose an action.',
        'required' => 'Publishing needs the Review step to show no red problems.',
        'example' => 'Press "Preview package", check the prices in USD, SAR and PKR, close the preview and press "Publish package".',
        'after' => 'A published package appears on the Hajj page straight away. You can move it back to draft at any time.',
        'guide' => 'preview-publishing',
        'video' => 'publish',
    ],
];
