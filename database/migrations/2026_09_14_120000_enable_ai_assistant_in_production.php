<?php

use App\Models\AiSetting;
use App\Support\Ai\AiConfig;
use Illuminate\Database\Migrations\Migration;

/**
 * Switch the AI assistant on for the live site.
 *
 * It shipped disabled on purpose (d823afb): the code landing should not put an
 * assistant in front of visitors or start spending before anyone decided to.
 * That decision has now been made — the client funded the OpenAI account on
 * 14 September 2026, the key was verified against the live API with correct,
 * database-grounded answers in English, Roman Urdu and Arabic, and the client
 * asked for it to be turned on.
 *
 * A migration rather than an admin toggle because this deploy runs
 * `migrate --force` and nothing else, and there is no production admin session
 * available to flip the switch by hand. It is the same route every production
 * data change in this project has taken.
 *
 * Production only. Local, testing and CI environments keep their own settings:
 * enabling the flags everywhere would put the widget on every page of every
 * test run.
 *
 * Runs once, like any migration. If the client later switches the assistant
 * off from the admin, nothing here will switch it back on.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $settings = AiSetting::current();

        $settings->forceFill([
            'is_enabled' => true,
            'public_enabled' => true,
        ])->save();

        AiConfig::flush();
    }

    public function down(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        // Reversing this one IS safe and is exactly what "undo" should mean:
        // it only hides the assistant again, and loses no data.
        AiSetting::current()->forceFill([
            'is_enabled' => false,
            'public_enabled' => false,
        ])->save();

        AiConfig::flush();
    }
};
