<?php

namespace Tests\Feature;

use App\Listeners\VerifyRequiredPhpExtensions;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

/**
 * Guards both halves of the fix for issue #4.
 *
 * Production ran without ext-fileinfo and nothing noticed: composer had no
 * requirement to enforce, and /up answered "Application up" regardless. The
 * two halves are composer.json failing the deploy on a host that lacks an
 * extension, and /up failing when one disappears afterwards. These tests keep
 * the halves from drifting apart, which is the way this would quietly come
 * back — someone adds an extension to one list and not the other.
 */
class RequiredPhpExtensionsTest extends TestCase
{
    public function test_health_endpoint_is_up_when_the_required_extensions_are_present(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_the_check_is_actually_registered_on_the_health_event(): void
    {
        // Without this, a passing /up proves nothing: the endpoint would
        // answer 200 just as happily if the listener were never wired up, and
        // that false green is precisely the failure this whole change exists
        // to remove.
        $registered = Event::getRawListeners()[DiagnosingHealth::class] ?? [];

        $this->assertContains(
            VerifyRequiredPhpExtensions::class,
            $registered,
            'The extension check is not registered on DiagnosingHealth, so /up would report a healthy '
            .'application on a host missing a required extension.'
        );
    }

    public function test_the_required_extensions_are_actually_loaded_here(): void
    {
        foreach (VerifyRequiredPhpExtensions::REQUIRED as $extension) {
            $this->assertTrue(
                extension_loaded($extension),
                "This environment is missing the '{$extension}' extension, which the application requires."
            );
        }
    }

    public function test_composer_declares_every_extension_the_health_check_requires(): void
    {
        $require = json_decode(file_get_contents(base_path('composer.json')), true)['require'];

        foreach (VerifyRequiredPhpExtensions::REQUIRED as $extension) {
            $this->assertArrayHasKey(
                'ext-'.$extension,
                $require,
                "composer.json does not require ext-{$extension}, so a deploy to a host without it would "
                .'succeed and only fail later at runtime.'
            );
        }
    }

    public function test_the_listener_rejects_a_missing_extension(): void
    {
        $listener = new class extends VerifyRequiredPhpExtensions
        {
            public const REQUIRED = ['ub-not-a-real-extension'];
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ub-not-a-real-extension');

        $listener->handle(new DiagnosingHealth);
    }

    public function test_the_health_endpoint_goes_down_when_a_diagnosis_fails(): void
    {
        // Proves the other end of the wire: a DiagnosingHealth listener that
        // throws actually takes /up down, rather than the exception being
        // swallowed and the endpoint still reporting a healthy application.
        Event::listen(DiagnosingHealth::class, function (): void {
            throw new RuntimeException('Missing required PHP extension(s): ub-not-a-real-extension.');
        });

        $this->get('/up')->assertStatus(500);
    }
}
