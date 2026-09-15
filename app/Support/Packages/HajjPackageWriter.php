<?php

namespace App\Support\Packages;

use App\Models\Hotel;
use App\Models\MashaerLocation;
use App\Models\MealPlan;
use App\Models\NoteTemplate;
use App\Models\Package;
use App\Models\ServiceItem;
use App\Models\TransportOption;
use App\Models\UpgradeOption;
use App\Support\Content\RichText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Writes a Hajj package's nested content from a PackageFormState-shaped array.
 *
 * Moved out of Admin\HajjPackageController so the builder, duplication,
 * template application and the library's "update linked packages" action all
 * save through one path, with one set of rules:
 *
 * - The whole replacement runs in ONE transaction. Several sections are
 *   delete-then-recreate, and three child tables cascade from variants; a
 *   failure halfway must not leave a live package with its prices already gone
 *   (FINAL_CODE_REVIEW_HAJJ_REDESIGN.md C-1).
 * - Rows reference a package option by its code ("A", "B", "C"), resolved
 *   case-insensitively; HajjPackageRequest rejects unknown or duplicate codes
 *   before this runs, so an unmatched code here means "every option".
 * - A row that names a library record but leaves its own fields empty is
 *   filled from that record. The builder copies the values itself, so this
 *   matters for templates and anything else that only sends the link.
 *
 * Media is not handled here: it needs the uploaded files, so the controller
 * owns it.
 */
class HajjPackageWriter
{
    public function syncNested(Package $package, array $data): void
    {
        DB::transaction(fn () => $this->write($package, $data));
    }

    private function write(Package $package, array $data): void
    {
        $package->variants()->delete();
        $variantIdsByCode = [];
        foreach (array_values($data['variants'] ?? []) as $i => $row) {
            if (blank($row['code'] ?? null)) {
                continue;
            }
            $variant = $package->variants()->create([
                'code' => strtoupper(trim($row['code'])),
                'label' => $row['label'] ?? null,
                'sort_order' => $i,
            ]);
            $variantIdsByCode[strtoupper(trim($row['code']))] = $variant->id;
        }
        $resolveVariant = fn (?string $code) => blank($code) ? null : ($variantIdsByCode[strtoupper(trim($code))] ?? null);

        // `has_aziziya` is the flat flag the listing badge and public filters
        // read: "the base package includes Aziziya", not "an optional Aziziya
        // upgrade is offered" (every Non-Aziziya package offers one).
        $package->has_aziziya = (($data['aziziya']['status'] ?? null) === 'included');
        $package->save();

        $this->writeItinerary($package, $data['itinerary'] ?? []);
        $this->writeAccommodations($package, $data['accommodations'] ?? [], $resolveVariant);
        $this->writeRoomOptions($package, $data['room_options'] ?? [], $resolveVariant);
        $this->writeAziziya($package, $data, $resolveVariant);
        $this->writeMashaer($package, $data['mashaer'] ?? []);
        $this->writeTransportation($package, $data['transportation'] ?? []);
        $this->writeNotes($package, $data['notes'] ?? []);
        $this->writeUpgrades($package, $data['upgrades'] ?? []);
        $this->writeFeatures($package, 'inclusion', $data);
        $this->writeFeatures($package, 'exclusion', $data);

        $this->refreshStartingPrice($package);
    }

    public function refreshStartingPrice(Package $package): void
    {
        $lowestUsd = $package->roomOptions()->where('is_available', true)->whereNotNull('price_usd')->min('price_usd');

        if ($lowestUsd !== null) {
            $package->forceFill(['starting_price' => $lowestUsd])->save();
        }
    }

    private function writeItinerary(Package $package, array $rows): void
    {
        $package->itineraryDays()->delete();

        foreach (array_values($rows) as $i => $row) {
            if (blank($row['city'] ?? null) && blank($row['accommodation_a'] ?? null) && blank($row['date_hijri_label'] ?? null) && blank($row['notes'] ?? null) && blank($row['transport'] ?? null)) {
                continue;
            }
            $package->itineraryDays()->create([
                'day_number' => filled($row['day_number'] ?? null) ? (int) $row['day_number'] : $i + 1,
                'date_gregorian' => $row['date_gregorian'] ?? null,
                'date_hijri_label' => $row['date_hijri_label'] ?? null,
                'city' => $row['city'] ?? null,
                'accommodation_a' => $row['accommodation_a'] ?? null,
                'accommodation_b' => $row['accommodation_b'] ?? null,
                'transport' => $row['transport'] ?? null,
                'notes' => $row['notes'] ?? null,
            ]);
        }
    }

    private function writeAccommodations(Package $package, array $rows, \Closure $resolveVariant): void
    {
        $package->accommodations()->delete();
        $hotels = Hotel::whereIn('id', collect($rows)->pluck('hotel_id')->filter())->get()->keyBy('id');
        $mealPlans = MealPlan::whereIn('id', collect($rows)->pluck('meal_plan_id')->filter())->get()->keyBy('id');

        foreach (array_values($rows) as $i => $row) {
            $hotel = $hotels->get($row['hotel_id'] ?? null);
            $mealPlan = $mealPlans->get($row['meal_plan_id'] ?? null);
            $name = filled($row['hotel_name'] ?? null) ? $row['hotel_name'] : $hotel?->name;

            if (blank($name)) {
                continue;
            }

            $package->accommodations()->create([
                'variant_id' => $resolveVariant($row['variant_code'] ?? null),
                'hotel_id' => $hotel?->id,
                'location' => $row['location'] ?? $hotel?->location ?? 'makkah',
                'hotel_name' => $name,
                'star_rating' => filled($row['star_rating'] ?? null) ? $row['star_rating'] : $hotel?->star_rating,
                'meal_plan' => filled($row['meal_plan'] ?? null) ? $row['meal_plan'] : $mealPlan?->name,
                'meal_plan_id' => $mealPlan?->id,
                'distance_note' => $row['distance_note'] ?? null,
                'nights' => $row['nights'] ?? null,
                'notes' => $row['notes'] ?? null,
                'sort_order' => $i,
            ]);
        }
    }

    private function writeRoomOptions(Package $package, array $rows, \Closure $resolveVariant): void
    {
        $package->roomOptions()->delete();

        foreach (array_values($rows) as $i => $row) {
            $row['sharing_type'] = self::sharingType($row);
            if (blank($row['sharing_type'])) {
                continue;
            }
            $package->roomOptions()->create([
                'variant_id' => $resolveVariant($row['variant_code'] ?? null),
                'sharing_type' => $row['sharing_type'],
                'occupancy' => $row['occupancy'] ?? null,
                'display_label' => filled($row['display_label'] ?? null) ? $row['display_label'] : Str::headline($row['sharing_type']),
                'price_basis' => filled($row['price_basis'] ?? null) ? $row['price_basis'] : 'per_person',
                'price_pkr' => self::price($row['price_pkr'] ?? null),
                'price_sar' => self::price($row['price_sar'] ?? null),
                'price_usd' => self::price($row['price_usd'] ?? null),
                'is_available' => ! empty($row['is_available']),
                'notes' => $row['notes'] ?? null,
                'sort_order' => $i,
            ]);
        }
    }

    private function writeAziziya(Package $package, array $data, \Closure $resolveVariant): void
    {
        $package->aziziya()->delete();
        $azData = $data['aziziya'] ?? [];

        if (blank($azData['status'] ?? null)) {
            return;
        }

        $aziziya = $package->aziziya()->create([
            'status' => $azData['status'],
            'accommodation_name' => $azData['accommodation_name'] ?? null,
            'location_note' => $azData['location_note'] ?? null,
            'walk_distance' => $azData['walk_distance'] ?? null,
            'duration_days' => $azData['duration_days'] ?? null,
            'average_occupancy' => $azData['average_occupancy'] ?? null,
            'description' => RichText::clean($azData['description'] ?? null, 'basic'),
            'notes' => RichText::clean($azData['notes'] ?? null, 'basic'),
        ]);

        foreach (array_values($data['aziziya_room_options'] ?? []) as $i => $row) {
            $row['sharing_type'] = self::sharingType($row);
            if (blank($row['sharing_type'])) {
                continue;
            }
            $aziziya->roomOptions()->create([
                'variant_id' => $resolveVariant($row['variant_code'] ?? null),
                'sharing_type' => $row['sharing_type'],
                'occupancy' => $row['occupancy'] ?? null,
                'display_label' => filled($row['display_label'] ?? null) ? $row['display_label'] : Str::headline($row['sharing_type']),
                'pricing_type' => filled($row['pricing_type'] ?? null) ? $row['pricing_type'] : 'included',
                'price_basis' => filled($row['price_basis'] ?? null) ? $row['price_basis'] : 'per_person',
                'price_pkr' => self::price($row['price_pkr'] ?? null),
                'price_sar' => self::price($row['price_sar'] ?? null),
                'price_usd' => self::price($row['price_usd'] ?? null),
                'description' => $row['description'] ?? null,
                'notes' => $row['notes'] ?? null,
                'sort_order' => $i,
            ]);
        }

        foreach (array_values($data['aziziya_services'] ?? []) as $i => $row) {
            if (blank($row['name'] ?? null)) {
                continue;
            }
            $price = self::price($row['price'] ?? null);
            $aziziya->services()->create([
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'is_included' => ! empty($row['is_included']),
                'price' => $price,
                // A genuine 0 is still a price and keeps its currency
                // (FINAL_CODE_REVIEW_HAJJ_REDESIGN.md M-1).
                'currency' => $price !== null ? ($row['currency'] ?? 'USD') : null,
                'price_basis' => $row['price_basis'] ?? null,
                'notes' => $row['notes'] ?? null,
                'sort_order' => $i,
            ]);
        }
    }

    private function writeMashaer(Package $package, array $mashaer): void
    {
        $package->mashaerDetails()->delete();
        $records = MashaerLocation::whereIn('id', collect($mashaer)->pluck('mashaer_location_id')->filter())->get()->keyBy('id');

        foreach (PackageFormState::MASHAER_LOCATIONS as $order => $location) {
            $row = $mashaer[$location] ?? [];
            $record = $records->get($row['mashaer_location_id'] ?? null);
            $facts = collect(MashaerLocation::FACT_FIELDS)->mapWithKeys(fn ($f) => [$f => in_array($f, ['other_services', 'notes'], true)
                ? RichText::clean($row[$f] ?? null, 'basic')
                : ($row[$f] ?? null)]);

            if ($facts->filter(fn ($v) => filled($v))->isEmpty()) {
                if (! $record) {
                    continue;
                }
                $facts = collect(MashaerLocation::FACT_FIELDS)->mapWithKeys(fn ($f) => [$f => $record->{$f}]);
            }

            $package->mashaerDetails()->create(array_merge($facts->all(), [
                'location' => $location,
                'mashaer_location_id' => $record?->id,
                'sort_order' => $order,
            ]));
        }
    }

    private function writeTransportation(Package $package, array $rows): void
    {
        $package->transportation()->delete();
        $options = TransportOption::whereIn('id', collect($rows)->pluck('transport_option_id')->filter())->get()->keyBy('id');

        foreach (array_values($rows) as $i => $row) {
            $option = $options->get($row['transport_option_id'] ?? null);

            if (blank($row['transport_type'] ?? null) && $option) {
                $row = array_merge($row, [
                    'transport_type' => $option->transport_type, 'from_location' => $option->from_location,
                    'to_location' => $option->to_location, 'is_included' => $option->is_included,
                    'price' => $option->price, 'currency' => $option->currency,
                    'price_basis' => $option->price_basis, 'notes' => $option->notes,
                ]);
            }

            if (blank($row['transport_type'] ?? null)) {
                continue;
            }

            $price = self::price($row['price'] ?? null);
            $package->transportation()->create([
                'transport_option_id' => $option?->id,
                'from_location' => $row['from_location'] ?? null,
                'to_location' => $row['to_location'] ?? null,
                'transport_type' => $row['transport_type'],
                'is_included' => ! empty($row['is_included']),
                'price' => $price,
                'currency' => $price !== null ? ($row['currency'] ?? 'USD') : null,
                'price_basis' => $row['price_basis'] ?? null,
                'notes' => $row['notes'] ?? null,
                'sort_order' => $i,
            ]);
        }
    }

    private function writeNotes(Package $package, array $rows): void
    {
        $package->packageNotes()->delete();
        $templates = NoteTemplate::whereIn('id', collect($rows)->pluck('note_template_id')->filter())->get()->keyBy('id');

        foreach (array_values($rows) as $i => $row) {
            $template = $templates->get($row['note_template_id'] ?? null);

            $row['content'] = RichText::clean($row['content'] ?? null, 'basic');

            if (blank($row['content'] ?? null) && $template) {
                $row = array_merge($row, [
                    'note_type' => $template->note_type, 'title' => $template->heading,
                    'content' => $template->content, 'is_important' => $template->is_important,
                ]);
            }

            if (blank($row['content'] ?? null)) {
                continue;
            }

            $package->packageNotes()->create([
                'note_template_id' => $template?->id,
                'note_type' => filled($row['note_type'] ?? null) ? $row['note_type'] : 'general',
                'title' => $row['title'] ?? null,
                'content' => $row['content'],
                'is_important' => ! empty($row['is_important']),
                'sort_order' => $i,
            ]);
        }
    }

    private function writeUpgrades(Package $package, array $rows): void
    {
        $package->upgrades()->delete();
        $options = UpgradeOption::whereIn('id', collect($rows)->pluck('upgrade_option_id')->filter())->get()->keyBy('id');

        foreach (array_values($rows) as $i => $row) {
            $option = $options->get($row['upgrade_option_id'] ?? null);

            if (blank($row['name'] ?? null) && $option) {
                $row = array_merge($row, [
                    'name' => $option->name, 'description' => $option->description, 'price' => $option->price,
                    'currency' => $option->currency, 'price_basis' => $option->price_basis,
                    'is_included' => $option->is_included, 'notes' => $option->conditions,
                ]);
            }

            if (blank($row['name'] ?? null)) {
                continue;
            }

            $price = self::price($row['price'] ?? null);
            $package->upgrades()->create([
                'upgrade_option_id' => $option?->id,
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'price' => $price,
                'currency' => $price !== null ? ($row['currency'] ?? 'USD') : null,
                'price_basis' => $row['price_basis'] ?? null,
                'is_included' => ! empty($row['is_included']),
                'notes' => $row['notes'] ?? null,
                'sort_order' => $i,
            ]);
        }
    }

    /**
     * Included / not-included lines. The builder sends rows (`inclusions`),
     * which carry their library link and their order; a plain one-per-line
     * text (`inclusions_text`) is still accepted for anything posting the old
     * form. The same sentence twice in one list is kept once.
     */
    private function writeFeatures(Package $package, string $type, array $data): void
    {
        $key = $type === 'inclusion' ? 'inclusions' : 'exclusions';
        $relation = $type === 'inclusion' ? $package->inclusions() : $package->exclusions();

        if (! array_key_exists($key, $data) && ! array_key_exists("{$key}_text", $data)) {
            return;
        }

        $rows = array_key_exists($key, $data)
            ? collect($data[$key] ?? [])
            : collect(preg_split('/\r\n|\r|\n/', (string) ($data["{$key}_text"] ?? '')))->map(fn ($line) => ['description' => $line]);

        $items = ServiceItem::whereIn('id', $rows->pluck('service_item_id')->filter())->get()->keyBy('id');

        $relation->delete();

        $seen = [];
        $order = 0;
        foreach ($rows as $row) {
            $item = $items->get($row['service_item_id'] ?? null);
            $description = trim((string) (filled($row['description'] ?? null) ? $row['description'] : $item?->description));

            if ($description === '') {
                continue;
            }

            $fingerprint = Str::lower(preg_replace('/\s+/u', ' ', $description));
            if (isset($seen[$fingerprint])) {
                continue;
            }
            $seen[$fingerprint] = true;

            $relation->create([
                'type' => $type,
                'service_item_id' => $item?->id,
                'description' => $description,
                'sort_order' => $order++,
            ]);
        }
    }

    /**
     * A room row names its type by a stable key; when only a label was given
     * ("Family room"), the key is made from it so the row is never dropped.
     */
    private static function sharingType(array $row): ?string
    {
        if (filled($row['sharing_type'] ?? null)) {
            return $row['sharing_type'];
        }

        return filled($row['display_label'] ?? null) ? Str::snake(Str::lower(trim($row['display_label']))) : null;
    }

    /** Blank means "no price". A literal "0" is a real price. */
    private static function price($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
