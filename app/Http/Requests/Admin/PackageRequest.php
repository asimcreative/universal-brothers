<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $packageId = $this->route('package')?->id;

        return [
            'package_category_id' => ['required', 'exists:package_categories,id'],
            'package_series_id' => ['nullable', 'exists:package_series,id'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('packages', 'code')->ignore($packageId)->where(fn ($query) => $query->whereNull('deleted_at'))],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('packages', 'slug')->ignore($packageId)->where(fn ($query) => $query->whereNull('deleted_at'))],
            'summary' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:60'],
            'duration_label' => ['nullable', 'string', 'max:255'],
            'is_shifting' => ['nullable', 'boolean'],
            'has_aziziya' => ['nullable', 'boolean'],
            'season_year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'season_label' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', Rule::in(['USD', 'PKR'])],
            'starting_price' => ['nullable', 'numeric', 'min:0'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'is_featured' => ['nullable', 'boolean'],
            'is_seasonal' => ['nullable', 'boolean'],
            'is_promotional' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],

            'inclusions_text' => ['nullable', 'string'],
            'exclusions_text' => ['nullable', 'string'],

            'itinerary' => ['nullable', 'array'],
            'itinerary.*.day_number' => ['required_with:itinerary.*.city', 'integer', 'min:1'],
            'itinerary.*.date_gregorian' => ['nullable', 'date'],
            'itinerary.*.date_hijri_label' => ['nullable', 'string', 'max:255'],
            'itinerary.*.city' => ['nullable', 'string', 'max:255'],
            'itinerary.*.accommodation_a' => ['nullable', 'string', 'max:255'],
            'itinerary.*.accommodation_b' => ['nullable', 'string', 'max:255'],

            'tiers' => ['nullable', 'array'],
            'tiers.*.label' => ['nullable', 'string', 'max:255'],
            'tiers.*.prices' => ['nullable', 'array'],
            'tiers.*.prices.sharing' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.prices.quad' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.prices.triple' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.prices.double' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
