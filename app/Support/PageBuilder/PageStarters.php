<?php

namespace App\Support\PageBuilder;

/**
 * Ready-made starting layouts for a new page, so an admin begins with a sensible
 * set of sections instead of an empty screen. Every starter uses the sections'
 * own default wording; no business facts are made up — company figures,
 * packages, testimonials and FAQs come from the data the admin already manages.
 */
class PageStarters
{
    public static function all(): array
    {
        return [
            'blank' => [
                'name' => 'Simple page',
                'icon' => 'bi-file-earmark-text',
                'description' => 'One text section. Good for policies, terms and short information pages.',
                'types' => ['text'],
            ],
            'information' => [
                'name' => 'Information page',
                'icon' => 'bi-layout-text-window',
                'description' => 'A banner, a text section, a text-and-photo section and a call to action.',
                'types' => ['hero', 'text', 'text_image', 'cta'],
            ],
            'service' => [
                'name' => 'Service page',
                'icon' => 'bi-grid-1x2',
                'description' => 'A banner, your services, packages, testimonials, questions and an enquiry form.',
                'types' => ['hero', 'services', 'packages_hajj', 'testimonials', 'faqs', 'contact_form'],
            ],
            'company' => [
                'name' => 'Company page',
                'icon' => 'bi-building',
                'description' => 'The company story with licence details, figures, awards and affiliations.',
                'types' => ['company_story', 'stats', 'awards', 'affiliations', 'cta'],
            ],
        ];
    }

    public static function sections(string $starter, string $title): array
    {
        $types = self::all()[$starter]['types'] ?? ['text'];

        return array_map(function (string $type) use ($title) {
            $data = BlockRegistry::defaults($type);

            if ($type === 'hero') {
                $data['heading'] = $title;
            }

            return ['id' => SectionValidator::newId(), 'type' => $type, 'visible' => true, 'data' => $data];
        }, $types);
    }
}
