<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The header and footer show a social icon for every platform that has a link,
 * and nothing for the ones that do not. Until now only Facebook had a row, so
 * Facebook was the only icon an admin could ever produce.
 *
 * The settings screen lists whatever rows exist, so creating the rows is all it
 * takes to put them on the form. They are created empty on purpose: an empty
 * value renders no icon, so nothing appears on the site until someone pastes a
 * real address in.
 */
return new class extends Migration
{
    /** Ordered as they should appear on the site and on the settings form. */
    private const PLATFORMS = [
        'social_facebook',
        'social_instagram',
        'social_youtube',
        'social_tiktok',
        'social_linkedin',
        'social_x',
        'social_threads',
        'social_pinterest',
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::PLATFORMS as $key) {
            // insertOrIgnore, not updateOrCreate: running this twice must never
            // wipe a link an admin has already saved. Facebook already has a
            // row and a real value, and it has to survive this untouched.
            DB::table('site_settings')->insertOrIgnore([
                'key' => $key,
                'value' => '',
                'group' => 'social',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // One row predates this and carries `group = 'general'`, which would
        // strand it in a different part of the settings form from the rest of
        // the social links. Only the grouping is touched; the value is not.
        DB::table('site_settings')
            ->where('key', 'like', 'social\_%')
            ->where('group', '!=', 'social')
            ->update(['group' => 'social', 'updated_at' => $now]);
    }

    public function down(): void
    {
        // Only the rows this migration could have added, and only while they
        // are still empty — never a link somebody has since filled in.
        DB::table('site_settings')
            ->whereIn('key', array_diff(self::PLATFORMS, ['social_facebook']))
            ->where(fn ($q) => $q->whereNull('value')->orWhere('value', ''))
            ->delete();
    }
};
