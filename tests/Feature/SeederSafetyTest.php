<?php

namespace Tests\Feature;

use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

/**
 * Regression for a HIGH finding from the release-gate security audit:
 * `AdminUserSeeder` was called unconditionally from `DatabaseSeeder`, so a
 * routine `php artisan db:seed` on ANY environment (including production)
 * would silently create a well-known super_admin/"password" account with
 * zero enforcement — the only prior safeguard was a deployment-checklist
 * reminder, which is not a code control. Proves the seeder now refuses to
 * run outside local/testing.
 */
class SeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_seeder_does_not_run_outside_local_or_testing(): void
    {
        App::shouldReceive('environment')->with(['local', 'testing'])->andReturn(false);

        (new AdminUserSeeder)->run();

        $this->assertDatabaseMissing('users', ['email' => 'admin@universalbrothers.test']);
    }

    public function test_admin_user_seeder_runs_in_testing_environment(): void
    {
        // The real test environment (this suite itself runs under 'testing')
        // — proves the guard doesn't block the legitimate local/CI dev workflow.
        (new AdminUserSeeder)->run();

        $this->assertDatabaseHas('users', ['email' => 'admin@universalbrothers.test', 'role' => 'super_admin']);
    }
}
