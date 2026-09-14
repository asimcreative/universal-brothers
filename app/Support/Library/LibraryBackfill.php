<?php

namespace App\Support\Library;

use App\Models\Hotel;
use App\Models\MashaerLocation;
use App\Models\MealPlan;
use App\Models\NoteTemplate;
use App\Models\PackageCategory;
use App\Models\ServiceItem;
use App\Models\TransportOption;
use App\Models\UpgradeOption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Builds the reusable library out of the content the Hajj packages already
 * repeat, and links each package row to the library record it matches.
 *
 * Runs from a migration (existing databases, including production) and from
 * DatabaseSeeder (fresh installs, where the migration ran before any package
 * existed). It is safe to run any number of times:
 *
 * - it only touches package rows whose link column is still empty;
 * - it finds an existing library record with identical values before creating
 *   one, so a second run creates nothing;
 * - it never changes a value a visitor sees, with ONE deliberate exception,
 *   moveInternalNotes(), documented there.
 *
 * Only live (not deleted) Hajj packages are read, so the Hajj library is not
 * filled with tourism inclusions or with content from deleted packages.
 */
class LibraryBackfill
{
    /**
     * Text that was written about the brochure for the people maintaining the
     * data, not for customers. It was stored as a package's description AND as
     * one of its notes, so it appeared twice on the live package page and was
     * handed to the AI assistant as a package fact.
     */
    public const INTERNAL_NOTE_PREFIXES = [
        'Brochure inconsistency preserved as printed',
        'Brochure names this',
    ];

    /**
     * Unused seed hotels whose names the client's current brochure replaced
     * (see HajjBrochureCorrectionSeeder). Archived — not deleted — so they no
     * longer appear in the package builder but can be restored.
     */
    private const SUPERSEDED_HOTEL_SLUGS = ['taibah-front-medinah', 'abraaj-tower-swiss-maqam'];

    /** @var array<string, int> */
    private array $counts = [];

    /**
     * @return array<string, int> what was created or linked, for the log
     */
    public function run(): array
    {
        $this->counts = [];

        $hajjId = PackageCategory::where('slug', 'hajj')->value('id');

        DB::transaction(function () use ($hajjId) {
            $this->syncHotelLocations();

            if ($hajjId === null) {
                return;
            }

            $this->moveInternalNotes($hajjId);
            $this->linkHotels($hajjId);
            $this->linkMealPlans($hajjId);
            $this->linkTransport($hajjId);
            $this->linkServiceItems($hajjId);
            $this->linkUpgrades($hajjId);
            $this->linkMashaer($hajjId);
            $this->linkNotes($hajjId);
            $this->archiveSupersededHotels();
        });

        return $this->counts;
    }

    /**
     * Normalises a hotel name so the brochure's printed variants match the
     * seeded master record: "Dar Al Tawhid Intercontinental Makkah" and
     * "Dar Al Tawhid Intercontinental", "Makkah Tower (Hajar Tower)" and
     * "Makkah Tower", "Al Aqeeq / Dallah Taibah / Similar" and
     * "Al Aqeeq / Dallah Taibah".
     */
    public static function normaliseHotelName(string $name): string
    {
        $value = Str::lower($name);
        $value = preg_replace('/\(.*?\)/u', ' ', $value);
        $value = preg_replace('/★+/u', ' ', $value);
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value);
        $value = preg_replace('/\bsimilar\b/u', ' ', $value);
        $value = trim(preg_replace('/\s+/u', ' ', $value));
        $value = preg_replace('/\s(makkah|medinah|madinah)$/u', '', $value);

        return trim($value);
    }

    private function count(string $key, int $by = 1): void
    {
        $this->counts[$key] = ($this->counts[$key] ?? 0) + $by;
    }

    /** Seeded hotels have a free-text `city`; the library filters on `location`. */
    private function syncHotelLocations(): void
    {
        foreach (Hotel::all() as $hotel) {
            $fromCity = match (Str::lower(trim((string) $hotel->city))) {
                'makkah', 'mecca' => 'makkah',
                'medinah', 'madinah', 'medina' => 'medinah',
                'aziziya', 'aziziyah' => 'aziziya',
                'mina' => 'mina',
                'arafat' => 'arafat',
                default => null,
            };

            // `location` defaulted to "makkah" when the column was added, so
            // only that default is corrected — a location chosen in the admin
            // is never overwritten.
            if ($fromCity && $fromCity !== 'makkah' && $hotel->location === 'makkah') {
                $hotel->forceFill(['location' => $fromCity])->saveQuietly();
            }
        }
    }

    /**
     * The one change here that alters what a visitor sees — on purpose.
     *
     * Moves brochure-provenance commentary out of the public description and
     * notes into `internal_notes`, which only administrators see. Matched on
     * the exact prefixes the data audit wrote, so a customer-facing note can
     * never be caught by it. The text is kept word for word.
     */
    private function moveInternalNotes(int $hajjId): void
    {
        $packages = DB::table('packages')
            ->where('package_category_id', $hajjId)
            ->whereNull('deleted_at')
            ->get(['id', 'description', 'internal_notes']);

        foreach ($packages as $package) {
            $internal = [];

            if ($package->description && self::isInternalNote($package->description)) {
                $internal[] = trim($package->description);
                DB::table('packages')->where('id', $package->id)->update(['description' => null]);
                $this->count('descriptions moved to internal notes');
            }

            $notes = DB::table('package_notes')->where('package_id', $package->id)->get(['id', 'content']);
            foreach ($notes as $note) {
                if (self::isInternalNote($note->content)) {
                    $internal[] = trim($note->content);
                    DB::table('package_notes')->where('id', $note->id)->delete();
                    $this->count('notes moved to internal notes');
                }
            }

            $existing = (string) $package->internal_notes;
            $additions = collect($internal)
                ->unique()
                ->reject(fn ($text) => str_contains($existing, $text))
                ->values();

            if ($additions->isNotEmpty()) {
                DB::table('packages')->where('id', $package->id)->update([
                    'internal_notes' => trim($existing."\n\n".$additions->implode("\n\n")),
                ]);
            }
        }
    }

    public static function isInternalNote(?string $text): bool
    {
        $text = ltrim((string) $text);

        foreach (self::INTERNAL_NOTE_PREFIXES as $prefix) {
            if (str_starts_with($text, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function hajjRows(string $table, int $hajjId)
    {
        return DB::table($table)
            ->join('packages', 'packages.id', '=', "{$table}.package_id")
            ->where('packages.package_category_id', $hajjId)
            ->whereNull('packages.deleted_at');
    }

    private function linkHotels(int $hajjId): void
    {
        $rows = $this->hajjRows('package_accommodations', $hajjId)
            ->whereNull('package_accommodations.hotel_id')
            ->orderBy('package_accommodations.id')
            ->get(['package_accommodations.id', 'package_accommodations.location', 'package_accommodations.hotel_name', 'package_accommodations.star_rating']);

        foreach ($rows as $row) {
            if (blank($row->hotel_name)) {
                continue;
            }

            $key = self::normaliseHotelName($row->hotel_name);
            $hotel = Hotel::orderBy('id')->get()
                ->first(fn (Hotel $h) => self::normaliseHotelName($h->name) === $key
                    && in_array($h->location, [$row->location, 'other'], true));

            if (! $hotel) {
                $hotel = Hotel::create([
                    'name' => trim($row->hotel_name),
                    'slug' => $this->uniqueHotelSlug($row->hotel_name),
                    'city' => Hotel::LOCATIONS[$row->location] ?? Str::headline($row->location),
                    'location' => $row->location,
                    'star_rating' => $row->star_rating,
                    'sort_order' => (int) Hotel::max('sort_order') + 1,
                    'is_active' => true,
                ]);
                $this->count('hotels created');
            }

            DB::table('package_accommodations')->where('id', $row->id)->update(['hotel_id' => $hotel->id]);
            $this->count('hotel links');
        }
    }

    private function uniqueHotelSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'hotel';
        $slug = $base;
        $i = 2;

        while (Hotel::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    private function linkMealPlans(int $hajjId): void
    {
        $rows = $this->hajjRows('package_accommodations', $hajjId)
            ->whereNull('package_accommodations.meal_plan_id')
            ->whereNotNull('package_accommodations.meal_plan')
            ->get(['package_accommodations.id', 'package_accommodations.meal_plan']);

        foreach ($rows as $row) {
            if (blank($row->meal_plan)) {
                continue;
            }

            $plan = $this->mealPlanFor($row->meal_plan);
            DB::table('package_accommodations')->where('id', $row->id)->update(['meal_plan_id' => $plan->id]);
            $this->count('meal plan links');
        }

        // Mina/Arafat meal wording is library material too, even though the
        // Mashaer rows hold it as text rather than a link.
        $this->hajjRows('package_mashaer_details', $hajjId)
            ->whereNotNull('package_mashaer_details.meal_plan')
            ->distinct()
            ->pluck('package_mashaer_details.meal_plan')
            ->filter()
            ->each(fn ($text) => $this->mealPlanFor($text));
    }

    private function mealPlanFor(string $text): MealPlan
    {
        $name = trim($text);
        $existing = MealPlan::where('name', $name)->first();

        if ($existing) {
            return $existing;
        }

        $lower = Str::lower($name);
        $fullBoard = str_contains($lower, 'full board');
        $halfBoard = str_contains($lower, 'half board');

        $this->count('meal plans created');

        return MealPlan::create([
            'name' => $name,
            'includes_breakfast' => $fullBoard || $halfBoard || str_contains($lower, 'breakfast'),
            'includes_lunch' => $fullBoard || str_contains($lower, 'lunch'),
            'includes_dinner' => $fullBoard || $halfBoard || str_contains($lower, 'dinner'),
            'is_included' => true,
            'sort_order' => (int) MealPlan::max('sort_order') + 1,
        ]);
    }

    private function linkTransport(int $hajjId): void
    {
        $fields = ['transport_type', 'from_location', 'to_location', 'is_included', 'price', 'currency', 'price_basis', 'notes'];

        $rows = $this->hajjRows('package_transportation', $hajjId)
            ->whereNull('package_transportation.transport_option_id')
            ->orderBy('package_transportation.sort_order')
            ->get(array_merge(['package_transportation.id'], array_map(fn ($f) => "package_transportation.{$f}", $fields)));

        foreach ($rows as $row) {
            $values = collect($fields)->mapWithKeys(fn ($f) => [$f => $row->{$f}])->all();
            $option = $this->findByValues(TransportOption::class, $values);

            if (! $option) {
                $route = trim(collect([$row->from_location, $row->to_location])->filter()->implode(' → '));
                $option = TransportOption::create($values + [
                    'name' => Str::limit($route ?: (TransportOption::TYPES[$row->transport_type] ?? Str::headline($row->transport_type)), 120, '…'),
                    'sort_order' => (int) TransportOption::max('sort_order') + 1,
                ]);
                $this->count('transport options created');
            }

            DB::table('package_transportation')->where('id', $row->id)->update(['transport_option_id' => $option->id]);
            $this->count('transport links');
        }
    }

    private function linkServiceItems(int $hajjId): void
    {
        $rows = $this->hajjRows('package_features', $hajjId)
            ->whereNull('package_features.service_item_id')
            ->orderBy('package_features.sort_order')
            ->get(['package_features.id', 'package_features.type', 'package_features.description']);

        foreach ($rows as $row) {
            if (blank($row->description)) {
                continue;
            }

            $item = ServiceItem::where('type', $row->type)->where('description', $row->description)->first();

            if (! $item) {
                $item = ServiceItem::create([
                    'type' => $row->type,
                    'title' => Str::limit(trim($row->description), 90, '…'),
                    'description' => trim($row->description),
                    'category' => $this->serviceCategory($row->description),
                    'sort_order' => (int) ServiceItem::where('type', $row->type)->max('sort_order') + 1,
                ]);
                $this->count($row->type === 'inclusion' ? 'included services created' : 'not-included items created');
            }

            DB::table('package_features')->where('id', $row->id)->update(['service_item_id' => $item->id]);
            $this->count('service links');
        }
    }

    private function serviceCategory(string $text): string
    {
        $lower = Str::lower($text);

        return match (true) {
            str_contains($lower, 'qurbani') => 'qurbani',
            str_contains($lower, 'ticket') || str_contains($lower, 'visa') => 'visa_ticket',
            str_contains($lower, 'mina') || str_contains($lower, 'arafat') || str_contains($lower, 'muzdalifah') || str_contains($lower, 'mashaer') => 'mashaer',
            str_contains($lower, 'bus') || str_contains($lower, 'transfer') || str_contains($lower, 'train') => 'transport',
            str_contains($lower, 'meal') || str_contains($lower, 'breakfast') => 'meals',
            str_contains($lower, 'accommodation') || str_contains($lower, 'hotel') => 'accommodation',
            str_contains($lower, 'guid') || str_contains($lower, 'training') || str_contains($lower, 'ziyarat') => 'guidance',
            default => 'other',
        };
    }

    private function linkUpgrades(int $hajjId): void
    {
        $fields = ['name', 'description', 'price', 'currency', 'price_basis', 'is_included', 'notes'];

        $rows = $this->hajjRows('package_upgrades', $hajjId)
            ->whereNull('package_upgrades.upgrade_option_id')
            ->orderBy('package_upgrades.sort_order')
            ->get(array_merge(['package_upgrades.id'], array_map(fn ($f) => "package_upgrades.{$f}", $fields)));

        foreach ($rows as $row) {
            $values = [
                'name' => $row->name,
                'description' => $row->description,
                'price' => $row->price,
                'currency' => $row->currency,
                'price_basis' => $row->price_basis,
                'is_included' => $row->is_included,
                'conditions' => $row->notes,
            ];

            $option = $this->findByValues(UpgradeOption::class, $values);

            if (! $option) {
                $option = UpgradeOption::create($values + ['sort_order' => (int) UpgradeOption::max('sort_order') + 1]);
                $this->count('additional options created');
            }

            DB::table('package_upgrades')->where('id', $row->id)->update(['upgrade_option_id' => $option->id]);
            $this->count('additional option links');
        }
    }

    private function linkMashaer(int $hajjId): void
    {
        $fields = array_merge(['location'], MashaerLocation::FACT_FIELDS);

        $rows = $this->hajjRows('package_mashaer_details', $hajjId)
            ->whereNull('package_mashaer_details.mashaer_location_id')
            ->get(array_merge(['package_mashaer_details.id'], array_map(fn ($f) => "package_mashaer_details.{$f}", $fields)));

        foreach ($rows as $row) {
            $values = collect($fields)->mapWithKeys(fn ($f) => [$f => $row->{$f}])->all();
            $record = $this->findByValues(MashaerLocation::class, $values);

            if (! $record) {
                $detail = collect([$row->maktab ? "Maktab {$row->maktab}" : null, $row->zone, $row->tent_type])->filter()->implode(', ');
                $record = MashaerLocation::create($values + [
                    'name' => Str::limit((MashaerLocation::LOCATIONS[$row->location] ?? Str::headline($row->location)).($detail ? " — {$detail}" : ''), 120, '…'),
                    'sort_order' => (int) MashaerLocation::max('sort_order') + 1,
                ]);
                $this->count('mashaer arrangements created');
            }

            DB::table('package_mashaer_details')->where('id', $row->id)->update(['mashaer_location_id' => $record->id]);
            $this->count('mashaer links');
        }
    }

    private function linkNotes(int $hajjId): void
    {
        $rows = $this->hajjRows('package_notes', $hajjId)
            ->whereNull('package_notes.note_template_id')
            ->orderBy('package_notes.sort_order')
            ->get(['package_notes.id', 'package_notes.note_type', 'package_notes.title', 'package_notes.content', 'package_notes.is_important']);

        foreach ($rows as $row) {
            if (blank($row->content) || self::isInternalNote($row->content)) {
                continue;
            }

            $template = NoteTemplate::where('note_type', $row->note_type)
                ->where('content', $row->content)
                ->where('is_important', (bool) $row->is_important)
                ->get()
                ->first(fn (NoteTemplate $t) => (string) $t->heading === (string) $row->title);

            if (! $template) {
                $template = NoteTemplate::create([
                    'title' => Str::limit(trim(($row->title ? trim($row->title, '"').': ' : '').$row->content), 70, '…'),
                    'heading' => $row->title,
                    'category' => $this->noteCategory($row->note_type, $row->content),
                    'note_type' => $row->note_type,
                    'content' => $row->content,
                    'is_important' => (bool) $row->is_important,
                    'sort_order' => (int) NoteTemplate::max('sort_order') + 1,
                ]);
                $this->count('notes created');
            }

            DB::table('package_notes')->where('id', $row->id)->update(['note_template_id' => $template->id]);
            $this->count('note links');
        }
    }

    private function noteCategory(string $noteType, string $content): string
    {
        $lower = Str::lower($content);

        return match (true) {
            str_contains($lower, 'qurbani') => 'qurbani',
            str_contains($lower, 'aziziya') => 'aziziya_stay',
            str_contains($lower, 'makkah') => 'makkah_stay',
            str_contains($lower, 'room') => 'room_policy',
            $noteType === 'pricing' => 'pricing',
            $noteType === 'disclaimer' => 'terms',
            default => 'general',
        };
    }

    private function archiveSupersededHotels(): void
    {
        $archived = Hotel::whereIn('slug', self::SUPERSEDED_HOTEL_SLUGS)
            ->where('is_active', true)
            ->whereDoesntHave('accommodations')
            ->update(['is_active' => false]);

        if ($archived) {
            $this->count('superseded hotels archived', $archived);
        }
    }

    /**
     * Exact match on every copied value. Decimal columns are compared as
     * numbers so "165" and "165.00" are the same price.
     *
     * @param  class-string<Model>  $model
     */
    private function findByValues(string $model, array $values)
    {
        return $model::query()->get()->first(function ($record) use ($values) {
            foreach ($values as $field => $value) {
                $current = $record->getRawOriginal($field);

                if (in_array($field, ['price'], true)) {
                    if (($current === null) !== ($value === null) || ($current !== null && (float) $current !== (float) $value)) {
                        return false;
                    }

                    continue;
                }

                if (in_array($field, ['is_included'], true)) {
                    if ((bool) $current !== (bool) $value) {
                        return false;
                    }

                    continue;
                }

                if ((string) $current !== (string) $value) {
                    return false;
                }
            }

            return true;
        });
    }
}
