<?php

use App\Models\AiSetting;
use App\Support\Ai\AiConfig;
use Illuminate\Database\Migrations\Migration;

/**
 * Cap the live assistant at 50 messages per visitor per day.
 *
 * The assistant went live with no daily cap, protected only by 12 messages a
 * minute — which one determined script can sustain around the clock. At the
 * measured ~2,900 prompt tokens a message that is enough to spend most of the
 * $10 the client had just added within a day or two. The client asked for 50
 * per visitor, adjustable from the admin.
 *
 * Only moves the value off 0, and only in production. If the client has
 * already typed a number into the portal, that number is theirs and stands.
 * Runs once, so a later choice of 0 (no limit) in the portal is not reversed.
 */
return new class extends Migration
{
    private const LIMIT = 50;

    public function up(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $settings = AiSetting::current();

        if ((int) $settings->daily_message_limit !== 0) {
            return;
        }

        $settings->forceFill(['daily_message_limit' => self::LIMIT])->save();

        AiConfig::flush();
    }

    public function down(): void
    {
        // Deliberately not reversed: rolling back would reopen unlimited
        // spend on the client's OpenAI account.
    }
};
