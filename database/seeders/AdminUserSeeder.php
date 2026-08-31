<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;

/**
 * Local-development admin account only — a well-known email/password, never
 * a real credential. This must never seed on production: an earlier
 * security audit found `DatabaseSeeder` called this unconditionally, so a
 * routine `db:seed` on any environment would silently create a
 * super_admin/password backdoor (see FINAL_CODE_REVIEW quality gate,
 * Security Engineer finding). Guarded here rather than relying on a
 * deployment-checklist reminder, which is not an enforced control.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! App::environment(['local', 'testing'])) {
            return;
        }

        User::updateOrCreate(
            ['email' => 'admin@universalbrothers.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
