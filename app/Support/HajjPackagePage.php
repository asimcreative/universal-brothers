<?php

namespace App\Support;

use App\Models\Package;
use Illuminate\Support\Collection;

/**
 * Everything the Hajj package detail view needs, built one way for both the
 * public page and the admin's draft preview — so a preview can never drift
 * from what visitors will actually see once the package is published.
 */
class HajjPackagePage
{
    public const RELATIONS = [
        'itineraryDays', 'inclusions', 'exclusions', 'series', 'category',
        'variants', 'accommodations.variant', 'roomOptions.variant',
        'aziziya.roomOptions.variant', 'aziziya.services', 'mashaerDetails',
        'transportation', 'packageNotes', 'upgrades', 'media',
    ];

    /**
     * @return array{package: Package, related: Collection, hajj: HajjPackagePresenter}
     */
    public static function data(Package $package): array
    {
        $package->loadMissing(self::RELATIONS);

        // All of the page's grouping and derivation lives in one view model so
        // the template stays declarative and the same logic serves every Hajj
        // package — see HajjPackagePresenter for why the twelve packages cannot
        // share a hard-coded layout.
        $hajj = new HajjPackagePresenter($package);

        $related = Package::published()
            ->notArchived()
            ->where('package_category_id', $package->package_category_id)
            ->where('id', '!=', $package->id)
            ->orderBy('is_featured', 'desc')
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        foreach ($related as $relatedPackage) {
            $relatedPackage->setRelation('category', $package->category);
        }

        return compact('package', 'related', 'hajj');
    }
}
