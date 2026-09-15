<?php

namespace App\Http\Requests\Admin;

use App\Models\MashaerLocation;
use App\Models\Package;
use App\Support\Packages\PackageCompleteness;
use App\Support\Packages\PackageDuplicator;
use App\Support\Packages\PackageFormState;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the package builder.
 *
 * The builder's save buttons send an `_intent`, and the package's status is
 * derived from it rather than typed by the admin:
 *
 *   draft    → saved as a draft (hidden from the website)
 *   publish  → published, but only if PackageCompleteness finds no problems
 *   save / continue / preview → keeps whatever status the package already has
 *
 * A submission with no intent keeps the posted `status`, so anything posting
 * the previous form still behaves as it did.
 */
class HajjPackageRequest extends FormRequest
{
    public const INTENTS = ['draft', 'publish', 'save', 'continue', 'preview'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $package = $this->route('package');
        $intent = $this->input('_intent');

        $status = match ($intent) {
            'draft' => 'draft',
            'publish' => 'published',
            'save', 'continue', 'preview' => $package instanceof Package ? ($package->status ?: 'draft') : 'draft',
            default => $this->input('status'),
        };

        // The web address is generated from the title when left empty, so an
        // admin never has to understand or invent one.
        $slug = $this->input('slug');
        if (blank($slug) && filled($this->input('name'))) {
            $slug = PackageDuplicator::uniqueSlug($this->input('name'), $package instanceof Package ? $package->id : null);
        }

        $this->merge(array_filter([
            'status' => $status,
            'slug' => $slug,
            'code' => filled($this->input('code')) ? strtoupper(trim($this->input('code'))) : $this->input('code'),
        ], fn ($value) => $value !== null));
    }

    public function rules(): array
    {
        $packageId = $this->route('package')?->id;

        return array_merge([
            '_intent' => ['nullable', Rule::in(self::INTENTS)],
            '_step' => ['nullable', Rule::in(array_keys(PackageCompleteness::STEPS))],
            '_reviewed' => ['nullable', 'boolean'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('packages', 'code')->ignore($packageId)->where(fn ($q) => $q->whereNull('deleted_at'))],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('packages', 'slug')->ignore($packageId)->where(fn ($q) => $q->whereNull('deleted_at'))],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'internal_notes' => ['nullable', 'string', 'max:10000'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'social_image' => ['nullable', 'image', 'max:4096'],
            'remove_cover_image' => ['nullable', 'boolean'],
            'remove_social_image' => ['nullable', 'boolean'],

            'media' => ['nullable', 'array'],
            'media.*.id' => ['nullable', 'integer'],
            'media.*.media_type' => ['nullable', Rule::in(['gallery', 'hotel', 'accommodation', 'aziziya', 'other'])],
            'media.*.file' => ['nullable', 'image', 'max:8192'],
            'media.*.video_url' => ['nullable', 'url', 'max:500'],
            'media.*.alt_text' => ['nullable', 'string', 'max:255'],
            'media.*.caption' => ['nullable', 'string', 'max:255'],
        ], self::contentRules());
    }

    /**
     * Everything that describes a package's content — shared with
     * PackageTemplateRequest, because a template is exactly this content
     * without an identity.
     */
    public static function contentRules(): array
    {
        $currencyRule = Rule::in(['USD', 'PKR', 'SAR']);

        $rules = [
            'package_series_id' => ['nullable', 'exists:package_series,id'],
            'package_type' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:20000'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:60'],
            'duration_label' => ['nullable', 'string', 'max:255'],
            'medinah_first' => ['nullable', 'boolean'],
            'is_shifting' => ['nullable', 'boolean'],
            'season_year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'season_label' => ['nullable', 'string', 'max:255'],

            'inclusions_text' => ['nullable', 'string'],
            'exclusions_text' => ['nullable', 'string'],
            'inclusions' => ['nullable', 'array', 'max:100'],
            'inclusions.*.service_item_id' => ['nullable', 'integer', 'exists:service_items,id'],
            'inclusions.*.description' => ['nullable', 'string', 'max:2000'],
            'exclusions' => ['nullable', 'array', 'max:100'],
            'exclusions.*.service_item_id' => ['nullable', 'integer', 'exists:service_items,id'],
            'exclusions.*.description' => ['nullable', 'string', 'max:2000'],

            'variants' => ['nullable', 'array', 'max:10'],
            'variants.*.code' => ['nullable', 'string', 'max:10'],
            'variants.*.label' => ['nullable', 'string', 'max:255'],

            'itinerary' => ['nullable', 'array', 'max:60'],
            'itinerary.*.day_number' => ['nullable', 'integer', 'min:1', 'max:60'],
            'itinerary.*.date_gregorian' => ['nullable', 'date'],
            'itinerary.*.date_hijri_label' => ['nullable', 'string', 'max:255'],
            'itinerary.*.city' => ['nullable', 'string', 'max:255'],
            'itinerary.*.accommodation_a' => ['nullable', 'string', 'max:255'],
            'itinerary.*.accommodation_b' => ['nullable', 'string', 'max:255'],
            'itinerary.*.transport' => ['nullable', 'string', 'max:255'],
            'itinerary.*.notes' => ['nullable', 'string', 'max:2000'],

            'accommodations' => ['nullable', 'array', 'max:50'],
            // Required, not nullable: package_accommodations.location is a
            // NOT NULL enum — a blank value must be a normal validation error,
            // not a QueryException inside the save transaction.
            'accommodations.*.location' => ['required', Rule::in(['makkah', 'medinah', 'aziziya', 'mina', 'arafat'])],
            'accommodations.*.variant_code' => ['nullable', 'string', 'max:10'],
            'accommodations.*.hotel_id' => ['nullable', 'integer', 'exists:hotels,id'],
            'accommodations.*.hotel_name' => ['nullable', 'string', 'max:255'],
            'accommodations.*.star_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'accommodations.*.meal_plan' => ['nullable', 'string', 'max:255'],
            'accommodations.*.meal_plan_id' => ['nullable', 'integer', 'exists:meal_plans,id'],
            'accommodations.*.distance_note' => ['nullable', 'string', 'max:255'],
            'accommodations.*.nights' => ['nullable', 'integer', 'min:0', 'max:60'],
            'accommodations.*.notes' => ['nullable', 'string', 'max:2000'],

            'room_options' => ['nullable', 'array', 'max:60'],
            'room_options.*.variant_code' => ['nullable', 'string', 'max:10'],
            'room_options.*.sharing_type' => ['nullable', 'string', 'max:255'],
            'room_options.*.occupancy' => ['nullable', 'integer', 'min:1', 'max:20'],
            'room_options.*.display_label' => ['nullable', 'string', 'max:255'],
            'room_options.*.price_basis' => ['nullable', 'string', 'max:255'],
            'room_options.*.price_pkr' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'room_options.*.price_sar' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'room_options.*.price_usd' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'room_options.*.is_available' => ['nullable', 'boolean'],
            'room_options.*.notes' => ['nullable', 'string', 'max:2000'],

            'aziziya' => ['nullable', 'array'],
            'aziziya.status' => ['nullable', Rule::in(['included', 'not_included', 'optional', 'not_applicable'])],
            'aziziya.accommodation_name' => ['nullable', 'string', 'max:255'],
            'aziziya.location_note' => ['nullable', 'string', 'max:255'],
            'aziziya.walk_distance' => ['nullable', 'string', 'max:255'],
            'aziziya.duration_days' => ['nullable', 'integer', 'min:0', 'max:30'],
            'aziziya.average_occupancy' => ['nullable', 'integer', 'min:1', 'max:20'],
            'aziziya.description' => ['nullable', 'string', 'max:5000'],
            'aziziya.notes' => ['nullable', 'string', 'max:5000'],

            'aziziya_room_options' => ['nullable', 'array', 'max:30'],
            'aziziya_room_options.*.variant_code' => ['nullable', 'string', 'max:10'],
            'aziziya_room_options.*.sharing_type' => ['nullable', 'string', 'max:255'],
            'aziziya_room_options.*.occupancy' => ['nullable', 'integer', 'min:1', 'max:20'],
            'aziziya_room_options.*.display_label' => ['nullable', 'string', 'max:255'],
            'aziziya_room_options.*.pricing_type' => ['nullable', Rule::in(['included', 'supplement', 'optional', 'upgrade', 'on_request'])],
            'aziziya_room_options.*.price_basis' => ['nullable', 'string', 'max:255'],
            'aziziya_room_options.*.price_pkr' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'aziziya_room_options.*.price_sar' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'aziziya_room_options.*.price_usd' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'aziziya_room_options.*.description' => ['nullable', 'string', 'max:2000'],
            'aziziya_room_options.*.notes' => ['nullable', 'string', 'max:2000'],

            'aziziya_services' => ['nullable', 'array', 'max:50'],
            'aziziya_services.*.name' => ['nullable', 'string', 'max:255'],
            'aziziya_services.*.description' => ['nullable', 'string', 'max:2000'],
            'aziziya_services.*.is_included' => ['nullable', 'boolean'],
            'aziziya_services.*.price' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'aziziya_services.*.currency' => ['nullable', $currencyRule],
            'aziziya_services.*.price_basis' => ['nullable', 'string', 'max:255'],
            'aziziya_services.*.notes' => ['nullable', 'string', 'max:2000'],

            'mashaer' => ['nullable', 'array'],

            'transportation' => ['nullable', 'array', 'max:50'],
            'transportation.*.transport_option_id' => ['nullable', 'integer', 'exists:transport_options,id'],
            'transportation.*.from_location' => ['nullable', 'string', 'max:255'],
            'transportation.*.to_location' => ['nullable', 'string', 'max:255'],
            'transportation.*.transport_type' => ['nullable', 'string', 'max:255'],
            'transportation.*.is_included' => ['nullable', 'boolean'],
            'transportation.*.price' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'transportation.*.currency' => ['nullable', $currencyRule],
            'transportation.*.price_basis' => ['nullable', 'string', 'max:255'],
            'transportation.*.notes' => ['nullable', 'string', 'max:2000'],

            'notes' => ['nullable', 'array', 'max:60'],
            'notes.*.note_template_id' => ['nullable', 'integer', 'exists:note_templates,id'],
            'notes.*.note_type' => ['nullable', Rule::in(['general', 'pricing', 'accommodation', 'booking', 'travel', 'important', 'disclaimer'])],
            'notes.*.title' => ['nullable', 'string', 'max:255'],
            'notes.*.content' => ['nullable', 'string', 'max:5000'],
            'notes.*.is_important' => ['nullable', 'boolean'],

            'upgrades' => ['nullable', 'array', 'max:50'],
            'upgrades.*.upgrade_option_id' => ['nullable', 'integer', 'exists:upgrade_options,id'],
            'upgrades.*.name' => ['nullable', 'string', 'max:255'],
            'upgrades.*.description' => ['nullable', 'string', 'max:2000'],
            'upgrades.*.price' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'upgrades.*.currency' => ['nullable', $currencyRule],
            'upgrades.*.price_basis' => ['nullable', 'string', 'max:255'],
            'upgrades.*.is_included' => ['nullable', 'boolean'],
            'upgrades.*.notes' => ['nullable', 'string', 'max:2000'],
        ];

        foreach (PackageFormState::MASHAER_LOCATIONS as $location) {
            $rules["mashaer.{$location}"] = ['nullable', 'array'];
            $rules["mashaer.{$location}.mashaer_location_id"] = ['nullable', 'integer', 'exists:mashaer_locations,id'];
            foreach (MashaerLocation::FACT_FIELDS as $field) {
                $rules["mashaer.{$location}.{$field}"] = in_array($field, ['other_services', 'notes'], true)
                    ? ['nullable', 'string', 'max:5000']
                    : ['nullable', 'string', 'max:255'];
            }
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            self::validateOptionCodes($validator, $this->all());

            if ($validator->errors()->isNotEmpty() || $this->input('status') !== 'published') {
                return;
            }

            foreach (PackageCompleteness::problems($this->all()) as $problem) {
                $validator->errors()->add("publish.{$problem['step']}", $problem['message']);
            }
        });
    }

    /**
     * Option codes must be unique, and every row that belongs to an option
     * must name one that exists. Without this a duplicate code crashed the
     * save on a unique constraint, and a typo silently made a price apply to
     * every option (FINAL_CODE_REVIEW_HAJJ_REDESIGN.md C-1/H-2).
     */
    public static function validateOptionCodes(Validator $validator, array $input): void
    {
        $codes = collect($input['variants'] ?? [])
            ->pluck('code')
            ->filter(fn ($code) => filled($code))
            ->map(fn ($code) => strtoupper(trim($code)));

        $duplicates = $codes->duplicates()->unique();
        if ($duplicates->isNotEmpty()) {
            $validator->errors()->add('variants', 'Each hotel option needs its own letter — '.$duplicates->implode(', ').' is used more than once.');
        }

        $validCodes = $codes->unique();
        foreach (['accommodations', 'room_options', 'aziziya_room_options'] as $group) {
            foreach ($input[$group] ?? [] as $i => $row) {
                $code = $row['variant_code'] ?? null;
                if (blank($code) || $validCodes->contains(strtoupper(trim($code)))) {
                    continue;
                }
                $validator->errors()->add("{$group}.{$i}.variant_code", "Option \"{$code}\" does not exist. Add it in the Hotel options step, or choose another option.");
            }
        }
    }

    public function attributes(): array
    {
        return [
            'name' => 'package title',
            'code' => 'package code',
            'slug' => 'web address',
            'duration_days' => 'number of days',
            'season_year' => 'Hajj year',
            'cover_image' => 'main photo',
            'social_image' => 'social sharing image',
            'meta_title' => 'search engine title',
            'meta_description' => 'search engine description',
            'accommodations.*.location' => 'hotel location',
            'accommodations.*.hotel_name' => 'hotel name',
            'room_options.*.price_usd' => 'USD price',
            'room_options.*.price_sar' => 'SAR price',
            'room_options.*.price_pkr' => 'PKR price',
            'itinerary.*.date_gregorian' => 'English date',
            'media.*.file' => 'photo',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Another package already uses this web address. Change the title or the web address.',
            'code.unique' => 'Another package already uses this package code.',
            'itinerary.*.date_gregorian.date' => 'Enter the English date as a real date (use the date picker).',
            'cover_image.max' => 'The main photo must be 4 MB or smaller.',
            'media.*.file.max' => 'Each photo must be 8 MB or smaller.',
        ];
    }
}
