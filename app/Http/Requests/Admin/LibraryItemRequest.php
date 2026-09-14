<?php

namespace App\Http\Requests\Admin;

use App\Support\Library\LibraryRegistry;
use App\Support\Library\LibraryType;
use Illuminate\Foundation\Http\FormRequest;

/** Validation for any library record, built from its LibraryType fields. */
class LibraryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function libraryType(): LibraryType
    {
        return LibraryRegistry::find((string) $this->route('type')) ?? abort(404);
    }

    public function rules(): array
    {
        $rules = $this->libraryType()->rules();

        foreach ($this->libraryType()->fields as $field) {
            if ($field['type'] === 'image') {
                $rules["remove_{$field['name']}"] = ['nullable', 'boolean'];
            }
        }

        return $rules;
    }

    public function attributes(): array
    {
        return collect($this->libraryType()->fields)
            ->mapWithKeys(fn ($field) => [$field['name'] => strtolower($field['label'])])
            ->all();
    }
}
