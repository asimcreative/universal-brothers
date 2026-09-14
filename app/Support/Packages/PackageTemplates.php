<?php

namespace App\Support\Packages;

use App\Models\Package;
use App\Models\PackageTemplate;
use DomainException;

/**
 * Saving a package as a template, and filling a draft from one.
 *
 * A template carries content only (see PackageFormState::forTemplate). Applying
 * one replaces a draft's content sections and fills any basic field the draft
 * has left empty; it never touches the draft's title, code, web address,
 * status, photos or internal notes.
 *
 * A published package cannot have a template applied. Replacing a live
 * package's prices and hotels in one click is exactly the accident templates
 * must not make possible — unpublish first, check the result, publish again.
 */
class PackageTemplates
{
    public function __construct(private HajjPackageWriter $writer) {}

    public function saveFromPackage(Package $package, string $name, ?string $description = null): PackageTemplate
    {
        return PackageTemplate::create([
            'name' => $name,
            'description' => $description,
            'payload' => PackageFormState::forTemplate(PackageFormState::fromPackage($package)),
            'source_package_id' => $package->id,
            'sort_order' => (int) PackageTemplate::max('sort_order') + 1,
            'is_active' => true,
        ]);
    }

    public function applyToDraft(Package $package, PackageTemplate $template): void
    {
        if ($package->isPublished()) {
            throw new DomainException('This package is live on the website. Move it to draft before applying a template, so its published prices and hotels are not replaced by accident.');
        }

        $state = PackageFormState::fromPackage($package);
        $payload = PackageFormState::forTemplate($template->payload ?? []);

        foreach (PackageFormState::SECTIONS as $section) {
            $state[$section] = $payload[$section];
        }

        foreach (PackageFormState::TEMPLATE_BASICS as $field) {
            if (blank($package->{$field}) && filled($payload[$field] ?? null)) {
                $package->{$field} = $payload[$field];
            }
        }

        $package->save();

        $this->writer->syncNested($package, $state);
    }
}
