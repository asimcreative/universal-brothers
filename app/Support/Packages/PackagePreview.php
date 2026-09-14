<?php

namespace App\Support\Packages;

use App\Models\Package;
use Illuminate\Support\Facades\URL;

/**
 * Draft preview links. Signed and expiring, and the route also requires an
 * admin session — a leaked link alone shows nothing.
 */
class PackagePreview
{
    public const LIFETIME_MINUTES = 60;

    public static function url(Package $package): string
    {
        return URL::temporarySignedRoute('admin.hajj-packages.preview', now()->addMinutes(self::LIFETIME_MINUTES), ['package' => $package]);
    }
}
