<?php

namespace App\Support;

use App\Models\PackageCategory;
use Illuminate\Support\Collection;

/**
 * The site's navigation, in one place.
 *
 * Before this, the menu existed twice: once in the Bootstrap header for the
 * fifteen inner pages and once, flattened, in the header the homepage uses.
 * The two had already drifted — the inner pages carried a mega menu with
 * twenty-odd deep links that the homepage's bar simply did not have, so the
 * same site offered two different menus depending on which page you were on.
 *
 * Both headers are now one partial reading this tree, which means a menu
 * change is made once and cannot land on half the site.
 *
 * Shape:
 *
 *     ['label' => …, 'url' => …]                     a plain link
 *     ['label' => …, 'url' => …, 'links'   => [ … ]] a short dropdown
 *     ['label' => …, 'url' => …, 'columns' => [ … ]] a mega panel
 *
 * A branch whose category is not active is dropped rather than rendered
 * empty, which is why every group is built behind a check.
 */
class SiteNavigation
{
    /** Where the client's registration system lives. Not ours, hence absolute. */
    public const REGISTER_URL = 'https://hums.akhg.com.pk/HajiReg/HajiLead';

    /**
     * @param  Collection<int, PackageCategory>|null  $categories
     * @return array<int, array<string, mixed>>
     */
    public static function tree(?Collection $categories = null): array
    {
        $categories ??= app('nav-categories');

        $hajj = $categories->firstWhere('slug', 'hajj');
        $umrah = $categories->firstWhere('slug', 'umrah');
        $tourism = $categories->firstWhere('slug', 'tourism');

        $items = [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'About Us', 'url' => url('/about-us')],
        ];

        // Hajj & Umrah. The panel is the inner pages' mega menu, unchanged in
        // content: the same section anchors, in the same order.
        if ($hajj || $umrah) {
            $columns = [];

            if ($hajj) {
                $columns[] = [
                    'title' => 'UB Hajj Services',
                    'url' => route('hajj-services'),
                    'links' => [
                        ['label' => 'Hajj Packages', 'url' => route('packages.category', 'hajj')],
                        ['label' => 'How to Apply', 'url' => route('hajj-services').'#how-to-apply'],
                        ['label' => 'Hajj Process', 'url' => route('hajj-services').'#hajj-process'],
                        ['label' => 'Hajj Guidance', 'url' => route('hajj-services').'#hajj-guidance'],
                        ['label' => 'Accommodation & Transport', 'url' => route('hajj-services').'#accommodation-transport'],
                        ['label' => 'Next Flight Date', 'url' => route('hajj-services').'#next-flight-date'],
                        ['label' => 'Register Now', 'url' => self::REGISTER_URL, 'external' => true],
                        ['label' => 'FAQs', 'url' => route('hajj-services').'#faqs'],
                    ],
                ];
            }

            if ($umrah) {
                $columns[] = [
                    'title' => 'UB Umrah Services',
                    'url' => route('umrah-services'),
                    'links' => [
                        ['label' => 'Umrah Packages', 'url' => route('packages.category', 'umrah')],
                        ['label' => 'How to Apply', 'url' => route('umrah-services').'#how-to-apply'],
                        ['label' => 'Umrah Process', 'url' => route('umrah-services').'#umrah-process'],
                        ['label' => 'Umrah Guidance', 'url' => route('umrah-services').'#umrah-guidance'],
                        ['label' => 'Accommodation & Transport', 'url' => route('umrah-services').'#accommodation-transport'],
                        ['label' => 'Next Flight Date', 'url' => route('umrah-services').'#next-flight-date'],
                        ['label' => 'FAQs', 'url' => route('faqs')],
                    ],
                ];
            }

            $items[] = [
                'label' => 'Hajj &amp; Umrah',
                'url' => $hajj ? route('hajj-services') : route('umrah-services'),
                'columns' => $columns,
                'feature' => $hajj ? [
                    'eyebrow' => 'Hajj 2027 &middot; 1448 AH',
                    'title' => 'You focus on your Ibadah.<br>We focus on the journey.',
                    'cta' => ['label' => 'View Hajj 2027 Packages', 'url' => route('packages.category', 'hajj')],
                ] : null,
            ];
        }

        if ($tourism) {
            $items[] = [
                'label' => 'Tourism',
                'url' => route('packages.category', 'tourism'),
                'links' => [
                    ['label' => 'Domestic Tourism', 'url' => route('packages.category', ['tourism', 'series' => 'domestic'])],
                    ['label' => 'International Tourism', 'url' => route('packages.category', ['tourism', 'series' => 'international'])],
                ],
            ];
        }

        return array_merge($items, [
            ['label' => 'Awards &amp; Recognition', 'url' => route('awards')],
            ['label' => 'Affiliations', 'url' => route('affiliations')],
            ['label' => 'Media', 'url' => route('media')],
            ['label' => 'Testimonials', 'url' => route('testimonials')],
            ['label' => 'FAQs', 'url' => route('faqs')],
            ['label' => 'Contact', 'url' => route('contact')],
        ]);
    }

    /**
     * The footer's service columns.
     *
     * Kept beside the header's tree for the same reason: the footer existed
     * twice too, and the Bootstrap one carried the deeper links — the process
     * anchors, the FAQ anchors, the domestic and international tour splits —
     * while the homepage's showed a five-line summary. This is the union, so
     * nothing a visitor could reach before became unreachable.
     *
     * @param  Collection<int, PackageCategory>|null  $categories
     * @return array<int, array{title: string, links: array<int, array{0: string, 1: string}>}>
     */
    public static function footerServices(?Collection $categories = null): array
    {
        $categories ??= app('nav-categories');

        $groups = [];

        if ($categories->firstWhere('slug', 'hajj')) {
            $groups[] = ['title' => 'Hajj', 'links' => [
                ['Hajj 2027', route('hajj-services')],
                ['Hajj Packages', route('packages.category', 'hajj')],
                ['How to Apply', route('hajj-services').'#how-to-apply'],
                ['Hajj Process', route('hajj-services').'#hajj-process'],
                ['Hajj FAQs', route('hajj-services').'#faqs'],
            ]];
        }

        if ($categories->firstWhere('slug', 'umrah')) {
            $groups[] = ['title' => 'Umrah', 'links' => [
                ['Umrah Services', route('umrah-services')],
                ['Umrah Packages', route('packages.category', 'umrah')],
                ['How to Apply', route('umrah-services').'#how-to-apply'],
                ['Umrah Process', route('umrah-services').'#umrah-process'],
                ['Umrah FAQs', route('faqs')],
            ]];
        }

        if ($categories->firstWhere('slug', 'tourism')) {
            $groups[] = ['title' => 'Tourism', 'links' => [
                ['All Tour Packages', route('packages.category', 'tourism')],
                ['Pakistan Tours', route('packages.category', ['tourism', 'series' => 'domestic'])],
                ['International Tours', route('packages.category', ['tourism', 'series' => 'international'])],
            ]];
        }

        return $groups;
    }
}
