<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'package_id' => ['nullable', 'exists:packages,id'],
            'package_category_id' => ['nullable', 'exists:package_categories,id'],
            'message' => ['nullable', 'string', 'max:2000'],

            'room_type' => ['nullable', 'in:sharing,quad,triple,double'],
            'cnic' => ['nullable', 'string', 'max:30'],
            'passport_no' => ['nullable', 'string', 'max:30'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'next_of_kin_name' => ['nullable', 'string', 'max:255'],
            'next_of_kin_contact' => ['nullable', 'string', 'max:50'],
        ];
    }
}
