<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A package template is package content without an identity, so it is
 * validated with exactly the builder's content rules plus its own name.
 */
class PackageTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'template_name' => ['required', 'string', 'max:255'],
            'template_description' => ['nullable', 'string', 'max:2000'],
            'template_active' => ['nullable', 'boolean'],
        ], HajjPackageRequest::contentRules());
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => HajjPackageRequest::validateOptionCodes($validator, $this->all()));
    }

    public function attributes(): array
    {
        return ['template_name' => 'template name'];
    }
}
