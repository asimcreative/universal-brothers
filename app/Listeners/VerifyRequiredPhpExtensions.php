<?php

namespace App\Listeners;

use Illuminate\Foundation\Events\DiagnosingHealth;
use RuntimeException;

/**
 * Fail the /up health check when a PHP extension this application needs is
 * missing from the host.
 *
 * Why this exists: production ran for weeks without `ext-fileinfo`, and
 * nothing anywhere reported it. `composer install` could not catch it because
 * laravel/framework does not list fileinfo among the seven extensions it
 * requires, and this application had declared none of its own. `/up` reported
 * "Application up" the whole time. The only symptom available to anyone was
 * that admin image uploads failed validation with a message that says nothing
 * about the real cause.
 *
 * composer.json now declares the requirement, which fails the deploy on a host
 * that lacks it. This is the second half: it catches an extension that goes
 * away *after* install — a server rebuild, a PHP version change, a package
 * removed by hand — where composer never runs again to notice.
 */
class VerifyRequiredPhpExtensions
{
    /**
     * Extensions this application needs that laravel/framework does NOT
     * already require on our behalf.
     *
     * The framework requires ctype, filter, hash, mbstring, openssl, session
     * and tokenizer, and composer enforces those at install time. Repeating
     * them here would add noise without adding a check, so this list holds
     * only what the framework leaves to us.
     */
    public const REQUIRED = [
        // Needed twice over by every admin image upload: the `image`
        // validation rule identifies the file through finfo, and
        // UploadedFile::store() derives the stored file's extension the same
        // way. Without it Symfony's guesser chain falls through to shelling
        // out to `file`, which cPanel normally blocks, so getMimeType()
        // returns null and the rule rejects a perfectly valid photograph.
        'fileinfo',
    ];

    public function handle(DiagnosingHealth $event): void
    {
        $missing = array_values(array_filter(
            static::REQUIRED,
            static fn (string $extension): bool => ! extension_loaded($extension),
        ));

        if ($missing !== []) {
            throw new RuntimeException(
                'Missing required PHP extension(s): '.implode(', ', $missing).'. '
                .'Admin image uploads cannot work until this is installed on the host.'
            );
        }
    }
}
