<?php

namespace App\Support\Library;

use App\Models\Hotel;
use App\Models\ItineraryTemplate;
use App\Models\MashaerLocation;
use App\Models\MealPlan;
use App\Models\NoteTemplate;
use App\Models\ServiceItem;
use App\Models\TransportOption;
use App\Models\UpgradeOption;

/**
 * Every kind of reusable package content, keyed by the URL segment used for it
 * under /admin/library/{type}.
 */
class LibraryRegistry
{
    public const CURRENCIES = ['USD' => 'US Dollar (USD)', 'SAR' => 'Saudi Riyal (SAR)', 'PKR' => 'Pakistani Rupee (PKR)'];

    /** @return array<string, LibraryType> */
    public static function all(): array
    {
        static $types = null;

        return $types ??= collect(self::definitions())->keyBy->key->all();
    }

    public static function find(string $key): ?LibraryType
    {
        return self::all()[$key] ?? null;
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /** @return list<LibraryType> */
    private static function definitions(): array
    {
        return [
            new LibraryType(
                key: 'hotels',
                model: Hotel::class,
                label: 'Hotels & Accommodation',
                singular: 'hotel',
                icon: 'bi-building',
                intro: 'Makkah and Madinah hotels, the Aziziya building, and Mina or Arafat camps. Add a hotel once, then pick it in any package.',
                fields: [
                    ['name' => 'name', 'label' => 'Hotel name', 'type' => 'text', 'required' => true, 'col' => 8, 'help' => 'The name customers see, e.g. "Swissotel Makkah".'],
                    ['name' => 'location', 'label' => 'Location', 'type' => 'select', 'required' => true, 'col' => 4, 'options' => Hotel::LOCATIONS, 'default' => 'makkah'],
                    ['name' => 'star_rating', 'label' => 'Star rating', 'type' => 'select', 'col' => 4, 'options' => ['1' => '1 star', '2' => '2 stars', '3' => '3 stars', '4' => '4 stars', '5' => '5 stars'], 'help' => 'Leave empty if the hotel has no official rating.'],
                    ['name' => 'address', 'label' => 'Address', 'type' => 'text', 'col' => 8],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'col' => 12, 'help' => 'Distance to the Haram, facilities, anything a customer would ask about.'],
                    ['name' => 'website_url', 'label' => 'Hotel website', 'type' => 'url', 'col' => 6, 'placeholder' => 'https://'],
                    ['name' => 'map_url', 'label' => 'Google Maps link', 'type' => 'url', 'col' => 6, 'placeholder' => 'https://maps.google.com/…'],
                    ['name' => 'cover_image', 'label' => 'Hotel photo', 'type' => 'image', 'col' => 6, 'help' => 'JPG, PNG or WebP, up to 4 MB. A wide (landscape) photo works best.'],
                    ['name' => 'notes', 'label' => 'Notes for administrators', 'type' => 'textarea', 'col' => 6, 'help' => 'Only visible here in the admin.'],
                    ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'col' => 3, 'default' => 0, 'help' => 'Lower numbers are listed first.'],
                    ['name' => 'is_active', 'label' => 'Active — can be picked in packages', 'type' => 'checkbox', 'col' => 9, 'default' => true],
                ],
                columns: [
                    ['field' => 'location', 'label' => 'Location', 'format' => 'option', 'options' => Hotel::LOCATIONS],
                    ['field' => 'star_rating', 'label' => 'Stars', 'format' => 'stars'],
                ],
                search: ['name', 'address'],
                filters: ['location' => ['label' => 'Location', 'options' => Hotel::LOCATIONS]],
                usageRelation: 'accommodations',
                syncMap: ['hotel_name' => 'name', 'star_rating' => 'star_rating'],
            ),

            new LibraryType(
                key: 'meal-plans',
                model: MealPlan::class,
                label: 'Meal Plans',
                singular: 'meal plan',
                icon: 'bi-cup-hot',
                intro: 'Standard meal arrangements such as half board or full board. Pick one for each hotel in a package.',
                fields: [
                    ['name' => 'name', 'label' => 'Meal plan name', 'type' => 'text', 'required' => true, 'col' => 8, 'help' => 'Shown to customers exactly as written, e.g. "Half board (breakfast & dinner)".'],
                    ['name' => 'is_included', 'label' => 'Included in the package price', 'type' => 'checkbox', 'col' => 4, 'default' => true],
                    ['name' => 'includes_breakfast', 'label' => 'Breakfast', 'type' => 'checkbox', 'col' => 4, 'default' => false],
                    ['name' => 'includes_lunch', 'label' => 'Lunch', 'type' => 'checkbox', 'col' => 4, 'default' => false],
                    ['name' => 'includes_dinner', 'label' => 'Dinner', 'type' => 'checkbox', 'col' => 4, 'default' => false],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'col' => 12],
                    ['name' => 'notes', 'label' => 'Notes for administrators', 'type' => 'textarea', 'col' => 12],
                    ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'col' => 3, 'default' => 0],
                    ['name' => 'is_active', 'label' => 'Active — can be picked in packages', 'type' => 'checkbox', 'col' => 9, 'default' => true],
                ],
                columns: [
                    ['field' => 'is_included', 'label' => 'Price', 'format' => 'included'],
                ],
                search: ['name', 'description'],
                usageRelation: 'accommodations',
                syncMap: ['meal_plan' => 'name'],
            ),

            new LibraryType(
                key: 'transport',
                model: TransportOption::class,
                label: 'Transport',
                singular: 'transport option',
                icon: 'bi-bus-front',
                intro: 'Airport transfers, city-to-city travel and Mashaer transport. Included legs and paid extras both live here.',
                fields: [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'col' => 8, 'help' => 'For finding it in the list, e.g. "Jeddah Airport to Makkah hotel".'],
                    ['name' => 'transport_type', 'label' => 'Type of transport', 'type' => 'select', 'required' => true, 'col' => 4, 'options' => TransportOption::TYPES, 'default' => 'airport_transfer'],
                    ['name' => 'from_location', 'label' => 'From', 'type' => 'text', 'col' => 6, 'placeholder' => 'e.g. Jeddah Airport'],
                    ['name' => 'to_location', 'label' => 'To', 'type' => 'text', 'col' => 6, 'placeholder' => 'e.g. Makkah Hotel'],
                    ['name' => 'is_included', 'label' => 'Included in the package price', 'type' => 'checkbox', 'col' => 12, 'default' => true, 'help' => 'Untick for an optional extra the customer pays for separately.'],
                    ['name' => 'price', 'label' => 'Extra price', 'type' => 'price', 'col' => 4, 'help' => 'Only for paid extras. Leave empty if included or priced on request.'],
                    ['name' => 'currency', 'label' => 'Currency', 'type' => 'select', 'col' => 4, 'options' => self::CURRENCIES, 'default' => 'USD'],
                    ['name' => 'price_basis', 'label' => 'Price is for', 'type' => 'text', 'col' => 4, 'placeholder' => 'e.g. per person, round trip'],
                    ['name' => 'notes', 'label' => 'Details shown to customers', 'type' => 'textarea', 'col' => 12, 'help' => 'e.g. "Bullet train Makkah to Madinah, or bus."'],
                    ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'col' => 3, 'default' => 0],
                    ['name' => 'is_active', 'label' => 'Active — can be picked in packages', 'type' => 'checkbox', 'col' => 9, 'default' => true],
                ],
                columns: [
                    ['field' => 'transport_type', 'label' => 'Type', 'format' => 'option', 'options' => TransportOption::TYPES],
                    ['field' => 'is_included', 'label' => 'Cost', 'format' => 'included'],
                    ['field' => 'price', 'label' => 'Extra price', 'format' => 'money'],
                ],
                search: ['name', 'from_location', 'to_location', 'notes'],
                filters: ['transport_type' => ['label' => 'Type', 'options' => TransportOption::TYPES]],
                usageRelation: 'packageRows',
                syncMap: [
                    'transport_type' => 'transport_type', 'from_location' => 'from_location', 'to_location' => 'to_location',
                    'is_included' => 'is_included', 'price' => 'price', 'currency' => 'currency',
                    'price_basis' => 'price_basis', 'notes' => 'notes',
                ],
            ),

            new LibraryType(
                key: 'inclusions',
                model: ServiceItem::class,
                label: 'Included Services',
                singular: 'included service',
                icon: 'bi-check2-circle',
                intro: 'What the package price covers. Write the sentence once and add it to any package.',
                fields: [
                    ['name' => 'title', 'label' => 'Short name', 'type' => 'text', 'required' => true, 'col' => 8, 'help' => 'For finding it in the list. Customers do not see this.'],
                    ['name' => 'category', 'label' => 'Group', 'type' => 'select', 'col' => 4, 'options' => ServiceItem::CATEGORIES, 'default' => 'other'],
                    ['name' => 'description', 'label' => 'Text shown to customers', 'type' => 'textarea', 'required' => true, 'col' => 12, 'max' => 2000],
                    ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'col' => 3, 'default' => 0],
                    ['name' => 'is_active', 'label' => 'Active — can be picked in packages', 'type' => 'checkbox', 'col' => 9, 'default' => true],
                ],
                columns: [
                    ['field' => 'category', 'label' => 'Group', 'format' => 'option', 'options' => ServiceItem::CATEGORIES],
                ],
                search: ['title', 'description'],
                filters: ['category' => ['label' => 'Group', 'options' => ServiceItem::CATEGORIES]],
                usageRelation: 'packageRows',
                syncMap: ['description' => 'description'],
                fixed: ['type' => 'inclusion'],
                subtitleField: 'description',
            ),

            new LibraryType(
                key: 'exclusions',
                model: ServiceItem::class,
                label: 'Not Included',
                singular: 'not-included item',
                icon: 'bi-x-circle',
                intro: 'What customers pay for separately, such as air tickets or the Qurbani cost.',
                fields: [
                    ['name' => 'title', 'label' => 'Short name', 'type' => 'text', 'required' => true, 'col' => 8, 'help' => 'For finding it in the list. Customers do not see this.'],
                    ['name' => 'category', 'label' => 'Group', 'type' => 'select', 'col' => 4, 'options' => ServiceItem::CATEGORIES, 'default' => 'other'],
                    ['name' => 'description', 'label' => 'Text shown to customers', 'type' => 'textarea', 'required' => true, 'col' => 12, 'max' => 2000],
                    ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'col' => 3, 'default' => 0],
                    ['name' => 'is_active', 'label' => 'Active — can be picked in packages', 'type' => 'checkbox', 'col' => 9, 'default' => true],
                ],
                columns: [
                    ['field' => 'category', 'label' => 'Group', 'format' => 'option', 'options' => ServiceItem::CATEGORIES],
                ],
                search: ['title', 'description'],
                filters: ['category' => ['label' => 'Group', 'options' => ServiceItem::CATEGORIES]],
                usageRelation: 'packageRows',
                syncMap: ['description' => 'description'],
                fixed: ['type' => 'exclusion'],
                subtitleField: 'description',
            ),

            new LibraryType(
                key: 'upgrades',
                model: UpgradeOption::class,
                label: 'Additional Options',
                singular: 'additional option',
                icon: 'bi-plus-circle',
                intro: 'Paid upgrades and extras a customer can add, such as a Kaaba-view room or an extra night in Madinah.',
                fields: [
                    ['name' => 'name', 'label' => 'Option name', 'type' => 'text', 'required' => true, 'col' => 8, 'help' => 'Shown to customers, e.g. "Kaaba View Supplement".'],
                    ['name' => 'is_included', 'label' => 'Already included in the price', 'type' => 'checkbox', 'col' => 4, 'default' => false],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'col' => 12],
                    ['name' => 'price', 'label' => 'Price', 'type' => 'price', 'col' => 4, 'help' => 'Leave empty for "price on request".'],
                    ['name' => 'currency', 'label' => 'Currency', 'type' => 'select', 'col' => 4, 'options' => self::CURRENCIES, 'default' => 'USD'],
                    ['name' => 'price_basis', 'label' => 'Price is for', 'type' => 'text', 'col' => 4, 'placeholder' => 'e.g. per person'],
                    ['name' => 'conditions', 'label' => 'Conditions', 'type' => 'textarea', 'col' => 12, 'help' => 'Any condition customers must know, e.g. "subject to availability".'],
                    ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'col' => 3, 'default' => 0],
                    ['name' => 'is_active', 'label' => 'Active — can be picked in packages', 'type' => 'checkbox', 'col' => 9, 'default' => true],
                ],
                columns: [
                    ['field' => 'price', 'label' => 'Price', 'format' => 'money'],
                    ['field' => 'price_basis', 'label' => 'Price is for'],
                ],
                search: ['name', 'description'],
                usageRelation: 'packageRows',
                syncMap: [
                    'name' => 'name', 'description' => 'description', 'price' => 'price', 'currency' => 'currency',
                    'price_basis' => 'price_basis', 'is_included' => 'is_included', 'notes' => 'conditions',
                ],
            ),

            new LibraryType(
                key: 'mashaer',
                model: MashaerLocation::class,
                label: 'Mina, Arafat & Muzdalifah',
                singular: 'Mashaer arrangement',
                icon: 'bi-geo-alt',
                intro: 'Camp, tent, meal and transport arrangements for the days of Hajj.',
                fields: [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'col' => 8, 'help' => 'For finding it in the list, e.g. "Mina — Maktab A, Zone 1".'],
                    ['name' => 'location', 'label' => 'Place', 'type' => 'select', 'required' => true, 'col' => 4, 'options' => MashaerLocation::LOCATIONS, 'default' => 'mina'],
                    ['name' => 'maktab', 'label' => 'Maktab', 'type' => 'text', 'col' => 4],
                    ['name' => 'category', 'label' => 'Category', 'type' => 'text', 'col' => 4, 'placeholder' => 'e.g. Category A'],
                    ['name' => 'zone', 'label' => 'Zone', 'type' => 'text', 'col' => 4, 'placeholder' => 'e.g. Zone 1'],
                    ['name' => 'tent_type', 'label' => 'Tent type', 'type' => 'text', 'col' => 4],
                    ['name' => 'accommodation_type', 'label' => 'Sleeping arrangement', 'type' => 'text', 'col' => 4, 'placeholder' => 'e.g. Sofa cum bed'],
                    ['name' => 'meal_plan', 'label' => 'Meals', 'type' => 'text', 'col' => 4],
                    ['name' => 'bathroom', 'label' => 'Bathroom', 'type' => 'text', 'col' => 4],
                    ['name' => 'air_conditioning', 'label' => 'Air conditioning', 'type' => 'text', 'col' => 4],
                    ['name' => 'transportation', 'label' => 'Transport', 'type' => 'text', 'col' => 4],
                    ['name' => 'other_services', 'label' => 'Other services', 'type' => 'richtext', 'profile' => 'basic', 'col' => 12],
                    ['name' => 'notes', 'label' => 'Notes shown to customers', 'type' => 'richtext', 'profile' => 'basic', 'col' => 12],
                    ['name' => 'description', 'label' => 'Description for administrators', 'type' => 'textarea', 'col' => 12, 'help' => 'Not copied into packages.'],
                    ['name' => 'cover_image', 'label' => 'Photo', 'type' => 'image', 'col' => 6, 'help' => 'JPG, PNG or WebP, up to 4 MB.'],
                    ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'col' => 3, 'default' => 0],
                    ['name' => 'is_active', 'label' => 'Active — can be picked in packages', 'type' => 'checkbox', 'col' => 3, 'default' => true],
                ],
                columns: [
                    ['field' => 'location', 'label' => 'Place', 'format' => 'option', 'options' => MashaerLocation::LOCATIONS],
                    ['field' => 'tent_type', 'label' => 'Tent'],
                ],
                search: ['name', 'maktab', 'zone', 'tent_type'],
                filters: ['location' => ['label' => 'Place', 'options' => MashaerLocation::LOCATIONS]],
                usageRelation: 'packageRows',
                syncMap: array_combine(MashaerLocation::FACT_FIELDS, MashaerLocation::FACT_FIELDS),
            ),

            new LibraryType(
                key: 'notes',
                model: NoteTemplate::class,
                label: 'Notes & Policies',
                singular: 'note',
                icon: 'bi-journal-text',
                intro: 'Standard notes and policies customers must read — ticket, Qurbani, hotel and room policies, terms and conditions. Notes for administrators only belong in a package\'s "Internal notes" box instead.',
                fields: [
                    ['name' => 'title', 'label' => 'Short name', 'type' => 'text', 'required' => true, 'col' => 8, 'help' => 'For finding it in the list. Customers do not see this.'],
                    ['name' => 'category', 'label' => 'Group', 'type' => 'select', 'required' => true, 'col' => 4, 'options' => NoteTemplate::CATEGORIES, 'default' => 'general'],
                    ['name' => 'heading', 'label' => 'Heading shown to customers', 'type' => 'text', 'col' => 12, 'help' => 'Optional. Leave empty for a note without a heading.'],
                    ['name' => 'content', 'label' => 'Note shown to customers', 'type' => 'richtext', 'profile' => 'basic', 'required' => true, 'col' => 12, 'max' => 5000],
                    ['name' => 'note_type', 'label' => 'Kind of note', 'type' => 'select', 'required' => true, 'col' => 4, 'options' => NoteTemplate::NOTE_TYPES, 'default' => 'general'],
                    ['name' => 'is_important', 'label' => 'Highlight as important', 'type' => 'checkbox', 'col' => 8, 'default' => false, 'help' => 'Important notes are shown first and highlighted.'],
                    ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'col' => 3, 'default' => 0],
                    ['name' => 'is_active', 'label' => 'Active — can be picked in packages', 'type' => 'checkbox', 'col' => 9, 'default' => true],
                ],
                columns: [
                    ['field' => 'category', 'label' => 'Group', 'format' => 'option', 'options' => NoteTemplate::CATEGORIES],
                ],
                search: ['title', 'content'],
                filters: ['category' => ['label' => 'Group', 'options' => NoteTemplate::CATEGORIES]],
                usageRelation: 'packageRows',
                syncMap: ['note_type' => 'note_type', 'title' => 'heading', 'content' => 'content', 'is_important' => 'is_important'],
                subtitleField: 'content',
            ),

            new LibraryType(
                key: 'journey-templates',
                model: ItineraryTemplate::class,
                label: 'Journey Plan Templates',
                singular: 'journey plan template',
                icon: 'bi-calendar-week',
                intro: 'Ready-made day-by-day plans. Apply one in a package\'s Journey Plan step and adjust the dates. A package keeps its own copy, so editing a template never changes an existing package.',
                fields: [
                    ['name' => 'name', 'label' => 'Template name', 'type' => 'text', 'required' => true, 'col' => 8, 'help' => 'e.g. "14 days — Madinah first".'],
                    ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'col' => 4, 'default' => 0],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'col' => 12],
                    ['name' => 'days', 'label' => 'Days', 'type' => 'days', 'col' => 12],
                    ['name' => 'is_active', 'label' => 'Active — can be applied in packages', 'type' => 'checkbox', 'col' => 12, 'default' => true],
                ],
                columns: [
                    ['field' => 'days', 'label' => 'Days', 'format' => 'count'],
                ],
                search: ['name', 'description'],
            ),
        ];
    }
}
