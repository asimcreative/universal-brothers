<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HajjPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $packageId = $this->route('package')?->id;
        $currencyRule = Rule::in(['USD', 'PKR', 'SAR']);

        return [
            'package_series_id' => ['nullable', 'exists:package_series,id'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('packages', 'code')->ignore($packageId)->where(fn ($q) => $q->whereNull('deleted_at'))],
            'name' => ['required', 'string', 'max:255'],
            'package_type' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('packages', 'slug')->ignore($packageId)->where(fn ($q) => $q->whereNull('deleted_at'))],
            'summary' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:60'],
            'duration_label' => ['nullable', 'string', 'max:255'],
            'medinah_first' => ['nullable', 'boolean'],
            'is_shifting' => ['nullable', 'boolean'],
            'season_year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'season_label' => ['nullable', 'string', 'max:255'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'is_featured' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],

            'inclusions_text' => ['nullable', 'string'],
            'exclusions_text' => ['nullable', 'string'],

            // --- Nested repeater arrays (see FINAL_CODE_REVIEW_HAJJ_REDESIGN.md H-1) ---
            'variants' => ['nullable', 'array'],
            'variants.*.code' => ['nullable', 'string', 'max:10'],
            'variants.*.label' => ['nullable', 'string', 'max:255'],

            'itinerary' => ['nullable', 'array'],
            'itinerary.*.day_number' => ['nullable', 'integer', 'min:1'],
            'itinerary.*.date_gregorian' => ['nullable', 'date'],
            'itinerary.*.date_hijri_label' => ['nullable', 'string', 'max:255'],
            'itinerary.*.city' => ['nullable', 'string', 'max:255'],
            'itinerary.*.accommodation_a' => ['nullable', 'string', 'max:255'],
            'itinerary.*.accommodation_b' => ['nullable', 'string', 'max:255'],
            'itinerary.*.notes' => ['nullable', 'string'],

            'accommodations' => ['nullable', 'array'],
            // Required, not nullable: package_accommodations.location is a
            // DB-level NOT NULL enum — a blank/absent value would otherwise
            // pass validation cleanly and then throw a raw QueryException
            // inside the sync transaction instead of a normal per-field
            // validation error (release-gate QA finding).
            'accommodations.*.location' => ['required', Rule::in(['makkah', 'medinah', 'aziziya', 'mina', 'arafat'])],
            'accommodations.*.variant_code' => ['nullable', 'string', 'max:10'],
            'accommodations.*.hotel_name' => ['nullable', 'string', 'max:255'],
            'accommodations.*.star_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'accommodations.*.meal_plan' => ['nullable', 'string', 'max:255'],
            'accommodations.*.distance_note' => ['nullable', 'string', 'max:255'],
            'accommodations.*.nights' => ['nullable', 'integer', 'min:0', 'max:60'],
            'accommodations.*.notes' => ['nullable', 'string'],

            'room_options' => ['nullable', 'array'],
            'room_options.*.variant_code' => ['nullable', 'string', 'max:10'],
            'room_options.*.sharing_type' => ['nullable', 'string', 'max:255'],
            'room_options.*.occupancy' => ['nullable', 'integer', 'min:1', 'max:20'],
            'room_options.*.display_label' => ['nullable', 'string', 'max:255'],
            'room_options.*.price_basis' => ['nullable', 'string', 'max:255'],
            'room_options.*.price_pkr' => ['nullable', 'numeric', 'min:0'],
            'room_options.*.price_sar' => ['nullable', 'numeric', 'min:0'],
            'room_options.*.price_usd' => ['nullable', 'numeric', 'min:0'],
            'room_options.*.is_available' => ['nullable', 'boolean'],
            'room_options.*.notes' => ['nullable', 'string'],

            'aziziya.status' => ['nullable', Rule::in(['included', 'not_included', 'optional', 'not_applicable'])],
            'aziziya.accommodation_name' => ['nullable', 'string', 'max:255'],
            'aziziya.location_note' => ['nullable', 'string', 'max:255'],
            'aziziya.walk_distance' => ['nullable', 'string', 'max:255'],
            'aziziya.duration_days' => ['nullable', 'integer', 'min:0', 'max:30'],
            'aziziya.average_occupancy' => ['nullable', 'integer', 'min:1', 'max:20'],
            'aziziya.description' => ['nullable', 'string'],
            'aziziya.notes' => ['nullable', 'string'],

            'aziziya_room_options' => ['nullable', 'array'],
            'aziziya_room_options.*.variant_code' => ['nullable', 'string', 'max:10'],
            'aziziya_room_options.*.sharing_type' => ['nullable', 'string', 'max:255'],
            'aziziya_room_options.*.occupancy' => ['nullable', 'integer', 'min:1', 'max:20'],
            'aziziya_room_options.*.display_label' => ['nullable', 'string', 'max:255'],
            'aziziya_room_options.*.pricing_type' => ['nullable', Rule::in(['included', 'supplement', 'optional', 'upgrade', 'on_request'])],
            'aziziya_room_options.*.price_basis' => ['nullable', 'string', 'max:255'],
            'aziziya_room_options.*.price_pkr' => ['nullable', 'numeric', 'min:0'],
            'aziziya_room_options.*.price_sar' => ['nullable', 'numeric', 'min:0'],
            'aziziya_room_options.*.price_usd' => ['nullable', 'numeric', 'min:0'],
            'aziziya_room_options.*.description' => ['nullable', 'string'],
            'aziziya_room_options.*.notes' => ['nullable', 'string'],

            'aziziya_services' => ['nullable', 'array'],
            'aziziya_services.*.name' => ['nullable', 'string', 'max:255'],
            'aziziya_services.*.description' => ['nullable', 'string'],
            'aziziya_services.*.is_included' => ['nullable', 'boolean'],
            'aziziya_services.*.price' => ['nullable', 'numeric', 'min:0'],
            'aziziya_services.*.currency' => ['nullable', $currencyRule],
            'aziziya_services.*.price_basis' => ['nullable', 'string', 'max:255'],
            'aziziya_services.*.notes' => ['nullable', 'string'],

            'mashaer.mina.maktab' => ['nullable', 'string', 'max:255'],
            'mashaer.mina.category' => ['nullable', 'string', 'max:255'],
            'mashaer.mina.zone' => ['nullable', 'string', 'max:255'],
            'mashaer.mina.tent_type' => ['nullable', 'string', 'max:255'],
            'mashaer.mina.accommodation_type' => ['nullable', 'string', 'max:255'],
            'mashaer.mina.meal_plan' => ['nullable', 'string', 'max:255'],
            'mashaer.mina.bathroom' => ['nullable', 'string', 'max:255'],
            'mashaer.mina.air_conditioning' => ['nullable', 'string', 'max:255'],
            'mashaer.mina.transportation' => ['nullable', 'string', 'max:255'],
            'mashaer.mina.other_services' => ['nullable', 'string'],
            'mashaer.mina.notes' => ['nullable', 'string'],
            'mashaer.arafat.maktab' => ['nullable', 'string', 'max:255'],
            'mashaer.arafat.category' => ['nullable', 'string', 'max:255'],
            'mashaer.arafat.tent_type' => ['nullable', 'string', 'max:255'],
            'mashaer.arafat.meal_plan' => ['nullable', 'string', 'max:255'],
            'mashaer.arafat.bathroom' => ['nullable', 'string', 'max:255'],
            'mashaer.arafat.air_conditioning' => ['nullable', 'string', 'max:255'],
            'mashaer.arafat.other_services' => ['nullable', 'string'],
            'mashaer.arafat.notes' => ['nullable', 'string'],

            'transportation' => ['nullable', 'array'],
            'transportation.*.from_location' => ['nullable', 'string', 'max:255'],
            'transportation.*.to_location' => ['nullable', 'string', 'max:255'],
            'transportation.*.transport_type' => ['nullable', 'string', 'max:255'],
            'transportation.*.is_included' => ['nullable', 'boolean'],
            'transportation.*.price' => ['nullable', 'numeric', 'min:0'],
            'transportation.*.currency' => ['nullable', $currencyRule],
            'transportation.*.price_basis' => ['nullable', 'string', 'max:255'],
            'transportation.*.notes' => ['nullable', 'string'],

            'notes' => ['nullable', 'array'],
            'notes.*.note_type' => ['nullable', Rule::in(['general', 'pricing', 'accommodation', 'booking', 'travel', 'important', 'disclaimer'])],
            'notes.*.title' => ['nullable', 'string', 'max:255'],
            'notes.*.content' => ['nullable', 'string'],
            'notes.*.is_important' => ['nullable', 'boolean'],

            'upgrades' => ['nullable', 'array'],
            'upgrades.*.name' => ['nullable', 'string', 'max:255'],
            'upgrades.*.description' => ['nullable', 'string'],
            'upgrades.*.price' => ['nullable', 'numeric', 'min:0'],
            'upgrades.*.currency' => ['nullable', $currencyRule],
            'upgrades.*.price_basis' => ['nullable', 'string', 'max:255'],
            'upgrades.*.is_included' => ['nullable', 'boolean'],
            'upgrades.*.notes' => ['nullable', 'string'],

            'media' => ['nullable', 'array'],
            'media.*.id' => ['nullable', 'integer'],
            'media.*.media_type' => ['nullable', Rule::in(['gallery', 'hotel', 'accommodation', 'aziziya', 'other'])],
            'media.*.file' => ['nullable', 'image', 'max:8192'],
            'media.*.video_url' => ['nullable', 'url', 'max:500'],
            'media.*.alt_text' => ['nullable', 'string', 'max:255'],
            'media.*.caption' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Cross-field checks the per-field rules above can't express: variant
     * codes must be unique within this submission, and every variant_code
     * referenced by an accommodation/room-option/Aziziya-room-option row
     * must match one actually defined in the Variants section. Without
     * this, a duplicate or typo'd code previously either crashed the sync
     * with an unhandled unique-constraint violation (duplicate) or
     * silently resolved to "applies to every variant" (typo) — see
     * FINAL_CODE_REVIEW_HAJJ_REDESIGN.md C-1/H-2.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $codes = collect($this->input('variants', []))
                ->pluck('code')
                ->filter(fn ($code) => filled($code))
                ->map(fn ($code) => strtoupper(trim($code)));

            $duplicates = $codes->duplicates()->unique();
            if ($duplicates->isNotEmpty()) {
                $validator->errors()->add('variants', 'Variant codes must be unique — found duplicate: '.$duplicates->implode(', ').'.');
            }

            $validCodes = $codes->unique();
            foreach (['accommodations', 'room_options', 'aziziya_room_options'] as $group) {
                foreach ($this->input($group, []) as $i => $row) {
                    $code = $row['variant_code'] ?? null;
                    if (blank($code) || $validCodes->contains(strtoupper(trim($code)))) {
                        continue;
                    }
                    $validator->errors()->add("{$group}.{$i}.variant_code", "\"{$code}\" doesn't match any variant code defined in the Package Variants section above.");
                }
            }
        });
    }
}
