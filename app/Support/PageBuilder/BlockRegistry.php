<?php

namespace App\Support\PageBuilder;

use App\Models\Faq;
use App\Support\Content\RichText;
use Illuminate\Support\Str;

/**
 * Every kind of section the page builder offers.
 *
 * A definition says what the admin sees (name, icon, description, group), which
 * fields the section has, and the values a new section starts with. The admin
 * never sees these keys: labels and help text are the only thing on screen.
 *
 * Field types
 *   text        one line of text                    (max, placeholder)
 *   textarea    a few lines of plain text           (max, rows)
 *   richtext    formatted text in the editor        (profile: full | standard | basic)
 *   link        a web address or a page on this site
 *   video       a YouTube or Vimeo link
 *   image       an image from the media library     (alt: ask for a description)
 *   select      a choice from a list                (options, or options_source)
 *   icon        a choice from the curated icon list
 *   toggle      on / off
 *   number      a whole number                      (min, max)
 *   items       a repeating list of the fields in `fields` (min_items, max_items, item_label)
 *   hidden      managed by the builder itself
 *
 * `required` is enforced when publishing. A draft may be saved incomplete.
 * `show_when` hides a field in the form unless another field has a given value.
 */
class BlockRegistry
{
    public const GROUPS = [
        'banners' => 'Banners',
        'content' => 'Text and images',
        'lists' => 'Cards and figures',
        'packages' => 'Packages',
        'company' => 'Company and trust',
        'media' => 'Video and photos',
        'contact' => 'Contact and news',
        'layout' => 'Buttons and spacing',
    ];

    public const ICONS = [
        'bi-moon-stars' => 'Pilgrimage (moon and stars)',
        'bi-shield-check' => 'Trust (shield)',
        'bi-award' => 'Award',
        'bi-people' => 'Group of people',
        'bi-person-heart' => 'Personal care',
        'bi-airplane' => 'Flight',
        'bi-building' => 'Hotel',
        'bi-bus-front' => 'Transport',
        'bi-cup-hot' => 'Meals',
        'bi-geo-alt' => 'Location',
        'bi-calendar-check' => 'Dates',
        'bi-clock' => 'Time',
        'bi-passport' => 'Visa and passport',
        'bi-wallet2' => 'Payment',
        'bi-telephone' => 'Phone',
        'bi-envelope' => 'Email',
        'bi-chat-heart' => 'Support',
        'bi-globe2' => 'Worldwide',
        'bi-compass' => 'Guidance',
        'bi-book' => 'Learning',
        'bi-camera' => 'Sightseeing',
        'bi-check2-circle' => 'Tick',
        'bi-star' => 'Star',
        'bi-heart' => 'Heart',
    ];

    private const BACKGROUNDS = ['white' => 'White', 'cream' => 'Light cream', 'navy' => 'Dark blue'];

    private static ?array $definitions = null;

    /** @return array<string, array> */
    public static function all(): array
    {
        return self::$definitions ??= self::build();
    }

    public static function find(?string $type): ?array
    {
        return $type === null ? null : (self::all()[$type] ?? null);
    }

    /** Types an admin can add from the section library (linked saved sections are inserted differently). */
    public static function libraryTypes(): array
    {
        return array_filter(self::all(), fn (array $d) => empty($d['internal']));
    }

    /** @return array<string, array{label: string, blocks: array}> */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::GROUPS as $key => $label) {
            $blocks = array_filter(self::libraryTypes(), fn (array $d) => $d['group'] === $key);

            if ($blocks !== []) {
                $groups[$key] = ['label' => $label, 'blocks' => $blocks];
            }
        }

        return $groups;
    }

    /** The values a new section of this type starts with. */
    public static function defaults(string $type): array
    {
        $definition = self::find($type);

        if (! $definition) {
            return [];
        }

        $data = [];

        foreach ($definition['fields'] as $field) {
            $data[$field['name']] = $field['default'] ?? self::emptyValue($field);
        }

        return array_replace($data, $definition['starter'] ?? []);
    }

    public static function emptyValue(array $field): mixed
    {
        return match ($field['type']) {
            'toggle' => false,
            'items' => [],
            'image' => ['path' => null, 'alt' => ''],
            'number' => null,
            default => '',
        };
    }

    /** Options for a select field, including lists read from the database. */
    public static function options(array $field): array
    {
        if (($field['type'] ?? null) === 'icon') {
            return self::ICONS;
        }

        if (($field['options_source'] ?? null) === 'faq_categories') {
            $categories = Faq::query()->whereNotNull('category')->where('category', '!=', '')
                ->distinct()->orderBy('category')->pluck('category');

            return ['all' => 'All questions'] + $categories->mapWithKeys(fn ($c) => [$c => ucwords(str_replace(['-', '_'], ' ', $c))])->all();
        }

        return $field['options'] ?? [];
    }

    /** One line describing a section's content, for its collapsed card in the builder. */
    public static function summary(string $type, array $data): string
    {
        foreach (['heading', 'left_heading', 'button_text'] as $key) {
            if (filled($data[$key] ?? null) && is_string($data[$key])) {
                return (string) $data[$key];
            }
        }

        foreach (['content', 'left_content', 'subheading', 'intro', 'text'] as $key) {
            if (filled($data[$key] ?? null) && is_string($data[$key])) {
                return Str::limit(RichText::toPlainText($data[$key]), 90);
            }
        }

        foreach (['slides', 'cards', 'items', 'images', 'buttons'] as $key) {
            if (! empty($data[$key]) && is_array($data[$key])) {
                return count($data[$key]).' '.Str::plural('item', count($data[$key]));
            }
        }

        return '';
    }

    private static function build(): array
    {
        return [
            'hero' => [
                'name' => 'Page banner',
                'icon' => 'bi-card-image',
                'group' => 'banners',
                'description' => 'A large opening banner with a heading, a short line of text, a photo and buttons.',
                'fields' => [
                    self::eyebrow('e.g. Hajj 2027'),
                    self::heading(required: true),
                    ['name' => 'subheading', 'label' => 'Text under the heading', 'type' => 'textarea', 'max' => 300, 'rows' => 2],
                    ['name' => 'image', 'label' => 'Background photo', 'type' => 'image', 'alt' => false,
                        'help' => 'A wide landscape photo works best, at least 1600 pixels wide. Leave empty to use the page banner image or a standard photo of the Haram.'],
                    ...self::button('button', 'Button'),
                    ...self::button('second_button', 'Second button'),
                    ['name' => 'size', 'label' => 'Banner height', 'type' => 'select', 'options' => ['standard' => 'Standard', 'tall' => 'Tall'], 'default' => 'standard', 'col' => 6],
                    ['name' => 'align', 'label' => 'Text position', 'type' => 'select', 'options' => ['left' => 'Left', 'center' => 'Centre'], 'default' => 'left', 'col' => 6],
                ],
                'starter' => ['heading' => 'Your journey, looked after at every step'],
            ],

            'hero_slider' => [
                'name' => 'Banner slider',
                'icon' => 'bi-images',
                'group' => 'banners',
                'description' => 'A banner that changes between several photos, each with its own heading and button.',
                'fields' => [
                    ['name' => 'source', 'label' => 'Slides to show', 'type' => 'select', 'default' => 'slides',
                        'options' => ['slides' => 'The slides I add below', 'homepage' => 'The homepage slides (managed under Homepage Sliders)']],
                    ['name' => 'slides', 'label' => 'Slides', 'type' => 'items', 'item_label' => 'Slide', 'min_items' => 1, 'max_items' => 6,
                        'show_when' => ['source' => 'slides'],
                        'fields' => [
                            ['name' => 'image', 'label' => 'Photo', 'type' => 'image', 'required' => true, 'alt' => false],
                            ['name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'max' => 120, 'required' => true],
                            ['name' => 'text', 'label' => 'Text', 'type' => 'textarea', 'max' => 250, 'rows' => 2],
                            ...self::button('button', 'Button'),
                        ]],
                    ['name' => 'autoplay', 'label' => 'Move to the next slide automatically', 'type' => 'toggle', 'default' => true],
                ],
            ],

            'text' => [
                'name' => 'Text',
                'icon' => 'bi-text-paragraph',
                'group' => 'content',
                'description' => 'Paragraphs, headings, lists, links, tables and images, written like a document.',
                'fields' => [
                    self::eyebrow(),
                    self::heading(),
                    ['name' => 'content', 'label' => 'Text', 'type' => 'richtext', 'profile' => 'full', 'required' => true, 'max' => 60000],
                    ['name' => 'width', 'label' => 'Width', 'type' => 'select', 'options' => ['reading' => 'Comfortable reading width', 'wide' => 'Full width'], 'default' => 'reading', 'col' => 6],
                    self::background(),
                ],
            ],

            'text_image' => [
                'name' => 'Text with image on the right',
                'icon' => 'bi-layout-text-sidebar-reverse',
                'group' => 'content',
                'description' => 'Text on the left and a photo on the right. On phones the photo appears under the text.',
                'fields' => self::textImageFields(),
            ],

            'image_text' => [
                'name' => 'Image on the left with text',
                'icon' => 'bi-layout-sidebar',
                'group' => 'content',
                'description' => 'A photo on the left and text on the right. On phones the photo appears first.',
                'fields' => self::textImageFields(),
            ],

            'two_columns' => [
                'name' => 'Two columns of text',
                'icon' => 'bi-layout-split',
                'group' => 'content',
                'description' => 'Two side-by-side blocks of text, for example “Our mission” and “Our vision”.',
                'fields' => [
                    self::eyebrow(),
                    self::heading(),
                    ['name' => 'left_heading', 'label' => 'Left column heading', 'type' => 'text', 'max' => 120, 'col' => 6],
                    ['name' => 'right_heading', 'label' => 'Right column heading', 'type' => 'text', 'max' => 120, 'col' => 6],
                    ['name' => 'left_content', 'label' => 'Left column text', 'type' => 'richtext', 'profile' => 'standard', 'required' => true, 'max' => 20000],
                    ['name' => 'right_content', 'label' => 'Right column text', 'type' => 'richtext', 'profile' => 'standard', 'required' => true, 'max' => 20000],
                    self::background(),
                ],
            ],

            'company_story' => [
                'name' => 'Story with company details',
                'icon' => 'bi-journal-richtext',
                'group' => 'company',
                'description' => 'The company story beside a photo and the licence and registration details from Site Settings.',
                'fields' => [
                    self::eyebrow('e.g. The Beginning'),
                    self::heading(),
                    ['name' => 'content', 'label' => 'Story', 'type' => 'richtext', 'profile' => 'full', 'required' => true, 'max' => 60000],
                    self::image(required: false, help: 'Leave empty to use a standard photo of Al-Masjid an-Nabawi.'),
                    ['name' => 'caption_label', 'label' => 'Small label on the photo', 'type' => 'text', 'max' => 60, 'col' => 6, 'default' => 'Serving the Guests of Allah'],
                    ['name' => 'caption_title', 'label' => 'Line on the photo', 'type' => 'text', 'max' => 80, 'col' => 6, 'default' => 'Makkah & Madinah, every season'],
                    ['name' => 'show_facts', 'label' => 'Show parent group, brand, licence and registration numbers', 'type' => 'toggle', 'default' => true,
                        'help' => 'These come from Site Settings, so they stay the same everywhere on the website.'],
                ],
                'starter' => ['eyebrow' => 'Our Story'],
            ],

            'cards' => [
                'name' => 'Cards',
                'icon' => 'bi-grid-3x2-gap',
                'group' => 'lists',
                'description' => 'Two to nine small cards in rows of three, each with an icon, a title, a sentence and an optional link.',
                'fields' => [
                    self::eyebrow(),
                    self::heading(),
                    self::intro(),
                    ['name' => 'cards', 'label' => 'Cards', 'type' => 'items', 'item_label' => 'Card', 'min_items' => 1, 'max_items' => 9, 'fields' => self::cardFields()],
                    self::background(),
                ],
                'starter' => [
                    'heading' => 'Why travel with us',
                    'cards' => [
                        ['icon' => 'bi-shield-check', 'title' => 'Trusted service', 'text' => '', 'link_text' => '', 'link' => ''],
                        ['icon' => 'bi-person-heart', 'title' => 'Personal care', 'text' => '', 'link_text' => '', 'link' => ''],
                        ['icon' => 'bi-compass', 'title' => 'Guidance throughout', 'text' => '', 'link_text' => '', 'link' => ''],
                    ],
                ],
            ],

            'stats' => [
                'name' => 'Figures',
                'icon' => 'bi-bar-chart',
                'group' => 'lists',
                'description' => 'Large numbers with a label, such as years of experience and pilgrims served.',
                'fields' => [
                    self::eyebrow(),
                    self::heading(),
                    self::intro(),
                    ['name' => 'source', 'label' => 'Figures to show', 'type' => 'select', 'default' => 'company',
                        'options' => ['company' => 'The company figures from Site Settings (years, pilgrims served, awards)', 'custom' => 'My own figures, added below']],
                    ['name' => 'items', 'label' => 'Figures', 'type' => 'items', 'item_label' => 'Figure', 'min_items' => 1, 'max_items' => 6,
                        'show_when' => ['source' => 'custom'],
                        'fields' => [
                            ['name' => 'value', 'label' => 'Number', 'type' => 'text', 'max' => 20, 'required' => true, 'placeholder' => 'e.g. 20+', 'col' => 4],
                            ['name' => 'label', 'label' => 'What it counts', 'type' => 'text', 'max' => 60, 'required' => true, 'placeholder' => 'e.g. Years of experience', 'col' => 8],
                        ]],
                    self::background('navy'),
                ],
                'starter' => ['heading' => 'Our Experience'],
            ],

            'services' => [
                'name' => 'Services',
                'icon' => 'bi-grid',
                'group' => 'lists',
                'description' => 'The services you offer, each with an icon, a short description and a link to find out more.',
                'fields' => [
                    self::eyebrow(),
                    self::heading(),
                    self::intro(),
                    ['name' => 'items', 'label' => 'Services', 'type' => 'items', 'item_label' => 'Service', 'min_items' => 1, 'max_items' => 9, 'fields' => self::cardFields()],
                    self::background('cream'),
                ],
                'starter' => [
                    'heading' => 'Our Services',
                    'items' => [
                        ['icon' => 'bi-moon-stars', 'title' => 'Hajj', 'text' => '', 'link_text' => 'Hajj services', 'link' => '/hajj-services'],
                        ['icon' => 'bi-compass', 'title' => 'Umrah', 'text' => '', 'link_text' => 'Umrah services', 'link' => '/umrah-services'],
                        ['icon' => 'bi-globe2', 'title' => 'Tourism', 'text' => '', 'link_text' => 'Tour packages', 'link' => '/tourism'],
                    ],
                ],
            ],

            'packages_hajj' => self::packageBlock('hajj', 'Hajj packages', 'bi-moon-stars'),
            'packages_umrah' => self::packageBlock('umrah', 'Umrah packages', 'bi-compass'),
            'packages_tourism' => self::packageBlock('tourism', 'Tour packages', 'bi-globe2'),

            'testimonials' => [
                'name' => 'Testimonials',
                'icon' => 'bi-chat-quote',
                'group' => 'company',
                'description' => 'What pilgrims say, taken from the testimonials you manage under Testimonials.',
                'fields' => [
                    self::eyebrow(),
                    self::heading(),
                    self::intro(),
                    ['name' => 'count', 'label' => 'How many to show', 'type' => 'select', 'options' => ['3' => '3', '6' => '6'], 'default' => '3', 'col' => 6],
                    ['name' => 'include_video', 'label' => 'Include video testimonials', 'type' => 'toggle', 'default' => true, 'col' => 6],
                    ['name' => 'show_all', 'label' => 'Show a “Read all testimonials” button', 'type' => 'toggle', 'default' => true],
                    self::background('cream'),
                ],
                'starter' => ['heading' => 'What Our Pilgrims Say'],
            ],

            'faqs' => [
                'name' => 'Questions and answers',
                'icon' => 'bi-question-circle',
                'group' => 'contact',
                'description' => 'Frequently asked questions that open when clicked, from the questions managed under FAQs.',
                'fields' => [
                    self::eyebrow(),
                    self::heading(),
                    self::intro(),
                    ['name' => 'category', 'label' => 'Questions to show', 'type' => 'select', 'options_source' => 'faq_categories', 'default' => 'all', 'col' => 6],
                    ['name' => 'count', 'label' => 'How many', 'type' => 'select', 'options' => ['5' => '5', '10' => '10', 'all' => 'All'], 'default' => '5', 'col' => 6],
                    ['name' => 'show_all', 'label' => 'Show a “See all questions” button', 'type' => 'toggle', 'default' => true],
                    self::background(),
                ],
                'starter' => ['heading' => 'Frequently Asked Questions'],
            ],

            'awards' => [
                'name' => 'Awards and recognition',
                'icon' => 'bi-trophy',
                'group' => 'company',
                'description' => 'The awards you manage under Awards.',
                'fields' => [
                    self::eyebrow(),
                    self::heading(),
                    self::intro(),
                    ['name' => 'count', 'label' => 'How many to show', 'type' => 'select', 'options' => ['3' => '3', '6' => '6', 'all' => 'All'], 'default' => '6', 'col' => 6],
                    ['name' => 'show_all', 'label' => 'Show a “View all awards” button', 'type' => 'toggle', 'default' => true, 'col' => 6],
                    self::background(),
                ],
                'starter' => ['eyebrow' => 'Recognized for Excellence', 'heading' => 'Awards & Recognition'],
            ],

            'affiliations' => [
                'name' => 'Affiliations and logos',
                'icon' => 'bi-diagram-3',
                'group' => 'company',
                'description' => 'The organisations you are affiliated with, from Affiliations.',
                'fields' => [
                    self::eyebrow(),
                    self::heading(),
                    self::intro(),
                    ['name' => 'show_all', 'label' => 'Show a “View all affiliations” button', 'type' => 'toggle', 'default' => true],
                    self::background('cream'),
                ],
                'starter' => ['eyebrow' => 'Trusted Institutions', 'heading' => 'Affiliations'],
            ],

            'video' => [
                'name' => 'Video',
                'icon' => 'bi-play-btn',
                'group' => 'media',
                'description' => 'A YouTube or Vimeo video that plays on the page.',
                'fields' => [
                    self::eyebrow(),
                    self::heading(),
                    self::intro(),
                    ['name' => 'video_url', 'label' => 'Video link', 'type' => 'video', 'required' => true,
                        'placeholder' => 'https://www.youtube.com/watch?v=…',
                        'help' => 'Open the video on YouTube or Vimeo, copy the address from the browser and paste it here.'],
                    ['name' => 'caption', 'label' => 'Caption under the video', 'type' => 'text', 'max' => 200],
                    self::background(),
                ],
            ],

            'gallery' => [
                'name' => 'Photo gallery',
                'icon' => 'bi-grid-3x3-gap',
                'group' => 'media',
                'description' => 'A grid of photos that open larger when clicked.',
                'fields' => [
                    self::eyebrow(),
                    self::heading(),
                    self::intro(),
                    ['name' => 'source', 'label' => 'Photos to show', 'type' => 'select', 'default' => 'chosen',
                        'options' => ['chosen' => 'The photos I choose below', 'website' => 'The latest photos from the Media Gallery']],
                    ['name' => 'images', 'label' => 'Photos', 'type' => 'items', 'item_label' => 'Photo', 'min_items' => 1, 'max_items' => 24,
                        'show_when' => ['source' => 'chosen'],
                        'fields' => [
                            ['name' => 'image', 'label' => 'Photo', 'type' => 'image', 'required' => true, 'alt' => true],
                            ['name' => 'caption', 'label' => 'Caption (optional)', 'type' => 'text', 'max' => 160],
                        ]],
                    ['name' => 'count', 'label' => 'How many photos', 'type' => 'select', 'options' => ['6' => '6', '9' => '9', '12' => '12'], 'default' => '9', 'col' => 6,
                        'show_when' => ['source' => 'website']],
                    ['name' => 'columns', 'label' => 'Photos per row', 'type' => 'select', 'options' => ['3' => '3', '4' => '4'], 'default' => '3', 'col' => 6],
                    self::background(),
                ],
            ],

            'cta' => [
                'name' => 'Call-to-action banner',
                'icon' => 'bi-megaphone',
                'group' => 'banners',
                'description' => 'A short, eye-catching invitation with one or two buttons, such as “Book your Hajj”.',
                'fields' => [
                    self::heading(required: true),
                    ['name' => 'text', 'label' => 'Text', 'type' => 'textarea', 'max' => 300, 'rows' => 2],
                    ...self::button('button', 'Button', required: true),
                    ...self::button('second_button', 'Second button'),
                    ['name' => 'style', 'label' => 'Colour', 'type' => 'select', 'options' => ['navy' => 'Dark blue', 'gold' => 'Gold', 'cream' => 'Light cream'], 'default' => 'navy', 'col' => 6],
                    ['name' => 'image', 'label' => 'Background photo (optional)', 'type' => 'image', 'alt' => false, 'col' => 6,
                        'help' => 'Only used with the dark blue colour.'],
                ],
                'starter' => [
                    'heading' => 'Ready to begin your journey?',
                    'text' => 'Speak to our team about Hajj, Umrah and tour packages.',
                    'button_text' => 'Contact us',
                    'button_link' => '/contact',
                ],
            ],

            'contact_form' => [
                'name' => 'Enquiry form',
                'icon' => 'bi-ui-checks',
                'group' => 'contact',
                'description' => 'A form visitors fill in to contact you. Messages arrive under Inquiries.',
                'fields' => [
                    self::eyebrow(),
                    self::heading(),
                    self::intro(),
                    ['name' => 'form', 'label' => 'Form', 'type' => 'select', 'default' => 'contact',
                        'options' => ['contact' => 'General contact form (name, email, phone, message)', 'inquiry' => 'Package enquiry form']],
                    ['name' => 'show_details', 'label' => 'Show the phone number, email and office address beside the form', 'type' => 'toggle', 'default' => true],
                    self::background('cream'),
                ],
                'starter' => ['heading' => 'Send Us a Message'],
            ],

            'offices' => [
                'name' => 'Office locations',
                'icon' => 'bi-geo',
                'group' => 'contact',
                'description' => 'Your offices with address, phone and opening details, from Offices.',
                'fields' => [
                    self::eyebrow(),
                    self::heading(),
                    self::intro(),
                    ['name' => 'show_map', 'label' => 'Show the map for each office that has one', 'type' => 'toggle', 'default' => false],
                    self::background(),
                ],
                'starter' => ['heading' => 'Visit Our Offices'],
            ],

            'news' => [
                'name' => 'Latest news',
                'icon' => 'bi-newspaper',
                'group' => 'contact',
                'description' => 'The newest articles you publish under News.',
                'fields' => [
                    self::eyebrow(),
                    self::heading(),
                    self::intro(),
                    ['name' => 'count', 'label' => 'How many articles', 'type' => 'select', 'options' => ['3' => '3', '6' => '6'], 'default' => '3', 'col' => 6],
                    ['name' => 'show_all', 'label' => 'Show a “More news” button', 'type' => 'toggle', 'default' => true, 'col' => 6],
                    self::background(),
                ],
                'starter' => ['heading' => 'Latest News'],
            ],

            'buttons' => [
                'name' => 'Buttons',
                'icon' => 'bi-hand-index',
                'group' => 'layout',
                'description' => 'One to four buttons in a row, for example “Download brochure” and “Call us”.',
                'fields' => [
                    ['name' => 'align', 'label' => 'Position', 'type' => 'select', 'options' => ['left' => 'Left', 'center' => 'Centre', 'right' => 'Right'], 'default' => 'center', 'col' => 6],
                    self::background(),
                    ['name' => 'buttons', 'label' => 'Buttons', 'type' => 'items', 'item_label' => 'Button', 'min_items' => 1, 'max_items' => 4,
                        'fields' => [
                            ['name' => 'text', 'label' => 'Button text', 'type' => 'text', 'max' => 40, 'required' => true, 'col' => 6],
                            ['name' => 'link', 'label' => 'Button link', 'type' => 'link', 'required' => true, 'col' => 6],
                            ['name' => 'style', 'label' => 'Style', 'type' => 'select', 'options' => ['primary' => 'Dark blue', 'gold' => 'Gold', 'outline' => 'Outline'], 'default' => 'primary', 'col' => 6],
                            ['name' => 'new_tab', 'label' => 'Open in a new tab', 'type' => 'toggle', 'default' => false, 'col' => 6],
                        ]],
                ],
                'starter' => ['buttons' => [['text' => 'Contact us', 'link' => '/contact', 'style' => 'primary', 'new_tab' => false]]],
            ],

            'spacer' => [
                'name' => 'Space or divider',
                'icon' => 'bi-distribute-vertical',
                'group' => 'layout',
                'description' => 'Extra space between two sections, with or without a thin line.',
                'fields' => [
                    ['name' => 'size', 'label' => 'Amount of space', 'type' => 'select', 'options' => ['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'], 'default' => 'medium', 'col' => 6],
                    ['name' => 'line', 'label' => 'Show a thin line', 'type' => 'toggle', 'default' => false, 'col' => 6],
                ],
            ],

            'saved_block' => [
                'name' => 'Linked saved section',
                'icon' => 'bi-link-45deg',
                'group' => 'layout',
                'internal' => true,
                'description' => 'Shows the current content of a saved section. Editing the saved section changes every page that links to it.',
                'fields' => [
                    ['name' => 'block_id', 'label' => 'Saved section', 'type' => 'hidden', 'required' => true],
                ],
            ],
        ];
    }

    private static function packageBlock(string $category, string $name, string $icon): array
    {
        return [
            'name' => $name,
            'icon' => $icon,
            'group' => 'packages',
            'category' => $category,
            'description' => "Package cards for published {$name}, always showing their current prices and details.",
            'fields' => [
                self::eyebrow(),
                self::heading(),
                self::intro(),
                ['name' => 'count', 'label' => 'How many packages', 'type' => 'select', 'options' => ['3' => '3', '6' => '6', '9' => '9'], 'default' => '3', 'col' => 6],
                ['name' => 'order', 'label' => 'Which packages first', 'type' => 'select', 'default' => 'featured', 'col' => 6,
                    'options' => ['featured' => 'Featured packages first', 'price' => 'Lowest price first', 'newest' => 'Newest first']],
                ['name' => 'show_all', 'label' => 'Show a “View all packages” button', 'type' => 'toggle', 'default' => true],
                self::background(),
            ],
            'starter' => ['heading' => $name === 'Tour packages' ? 'Tour Packages' : ucwords($name)],
        ];
    }

    private static function textImageFields(): array
    {
        return [
            self::eyebrow(),
            self::heading(),
            ['name' => 'content', 'label' => 'Text', 'type' => 'richtext', 'profile' => 'standard', 'required' => true, 'max' => 20000],
            self::image(required: true),
            ['name' => 'caption', 'label' => 'Caption under the photo (optional)', 'type' => 'text', 'max' => 160],
            ...self::button('button', 'Button'),
            self::background(),
        ];
    }

    private static function cardFields(): array
    {
        return [
            ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon', 'default' => 'bi-star', 'col' => 4],
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'max' => 80, 'required' => true, 'col' => 8],
            ['name' => 'text', 'label' => 'Text', 'type' => 'textarea', 'max' => 400, 'rows' => 2],
            ['name' => 'link_text', 'label' => 'Link text (optional)', 'type' => 'text', 'max' => 40, 'col' => 6, 'placeholder' => 'e.g. Find out more'],
            ['name' => 'link', 'label' => 'Link (optional)', 'type' => 'link', 'col' => 6],
        ];
    }

    private static function eyebrow(string $placeholder = 'e.g. Why choose us'): array
    {
        return ['name' => 'eyebrow', 'label' => 'Small label above the heading (optional)', 'type' => 'text', 'max' => 60, 'placeholder' => $placeholder, 'col' => 6];
    }

    private static function heading(bool $required = false): array
    {
        return ['name' => 'heading', 'label' => $required ? 'Heading' : 'Heading (optional)', 'type' => 'text', 'max' => 120, 'required' => $required, 'col' => 6];
    }

    private static function intro(): array
    {
        return ['name' => 'intro', 'label' => 'Short introduction (optional)', 'type' => 'textarea', 'max' => 400, 'rows' => 2];
    }

    private static function image(bool $required, ?string $help = null): array
    {
        return ['name' => 'image', 'label' => 'Photo', 'type' => 'image', 'required' => $required, 'alt' => true,
            'help' => $help ?? 'JPG, PNG or WebP. A landscape photo at least 1200 pixels wide looks best.'];
    }

    private static function background(string $default = 'white'): array
    {
        return ['name' => 'background', 'label' => 'Background colour', 'type' => 'select', 'options' => self::BACKGROUNDS, 'default' => $default, 'col' => 6];
    }

    /** A button's text and link, which are only required together. */
    private static function button(string $prefix, string $label, bool $required = false): array
    {
        return [
            ['name' => "{$prefix}_text", 'label' => "{$label} text".($required ? '' : ' (optional)'), 'type' => 'text', 'max' => 40, 'required' => $required, 'col' => 6, 'pair' => "{$prefix}_link"],
            ['name' => "{$prefix}_link", 'label' => "{$label} link", 'type' => 'link', 'required' => $required, 'col' => 6, 'pair' => "{$prefix}_text"],
        ];
    }
}
