<?php

namespace App\Console\Commands;

use App\Models\Package;
use App\Models\PackageTemplate;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Makes the separate training-recording database safe to film, or tidies it
 * up afterwards. Refuses to run against any other database, so it can never
 * touch production, the E2E testing database or a developer's own data.
 *
 *   php artisan training:recording prepare --env=recording
 *   php artisan training:recording cleanup --env=recording
 *
 * prepare: removes every enquiry, AI conversation and activity entry (so no
 *   personal information can appear on screen), removes any earlier training
 *   package, and creates the "Training Admin" account used in the video. The
 *   password comes from TRAINING_RECORDER_PASSWORD and is never printed.
 * cleanup: removes the training packages and their uploaded photos.
 */
class PrepareTrainingRecording extends Command
{
    protected $signature = 'training:recording {action : prepare or cleanup}';

    protected $description = 'Prepare or clean the separate training-recording database used to film the admin video training';

    public const DATABASE_FILE = 'training-recording.sqlite';

    public const ADMIN_EMAIL = 'training-admin@example.test';

    public function handle(): int
    {
        if (! $this->isRecordingDatabase()) {
            $this->error('Refused: this command only runs against database/'.self::DATABASE_FILE.' (use --env=recording).');

            return self::FAILURE;
        }

        return match ($this->argument('action')) {
            'prepare' => $this->prepare(),
            'cleanup' => $this->cleanup(),
            default => $this->invalid(),
        };
    }

    private function prepare(): int
    {
        $password = (string) env('TRAINING_RECORDER_PASSWORD', '');
        if (strlen($password) < 16) {
            $this->error('Set TRAINING_RECORDER_PASSWORD (16+ characters) for the training admin account.');

            return self::FAILURE;
        }

        foreach (['ai_messages', 'ai_conversations', 'inquiries', 'admin_activities', 'admin_training_progress', 'admin_guide_completions'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        $this->removeTrainingPackages();

        User::query()->where('email', '!=', self::ADMIN_EMAIL)->update(['is_active' => false]);
        User::updateOrCreate(['email' => self::ADMIN_EMAIL], [
            'name' => 'Training Admin',
            'password' => Hash::make($password),
            'role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ])->forceFill(['tour_status' => 'not_started', 'tour_step' => 0, 'onboarding_dismissed_at' => null])->save();

        $this->info('Training-recording database prepared: no enquiries, conversations or activity; one Training Admin account.');

        return self::SUCCESS;
    }

    private function cleanup(): int
    {
        $removed = $this->removeTrainingPackages();
        $this->info("Removed {$removed} training ".str('package')->plural($removed).' and their photos.');

        return self::SUCCESS;
    }

    /**
     * Training packages are the ones whose code starts with TRN, and training
     * templates end in "(Training)" (see resources/data/training-demo-package.json).
     */
    private function removeTrainingPackages(): int
    {
        PackageTemplate::query()->where('name', 'like', '%(Training)')->delete();

        $packages = Package::withTrashed()->with('media')->where('code', 'like', 'TRN%')->get();

        foreach ($packages as $package) {
            $files = array_filter(array_merge([$package->cover_image, $package->social_image], $package->media->pluck('image_path')->all()));
            Storage::disk('public')->delete($files);
            $package->forceDelete();
        }

        return $packages->count();
    }

    private function isRecordingDatabase(): bool
    {
        $connection = config('database.default');

        return config("database.connections.{$connection}.driver") === 'sqlite'
            && basename((string) config("database.connections.{$connection}.database")) === self::DATABASE_FILE;
    }

    private function invalid(): int
    {
        $this->error('Action must be "prepare" or "cleanup".');

        return self::INVALID;
    }
}
