<?php

namespace App\Http\Controllers;

use App\Http\Requests\InquiryRequest;
use App\Models\Inquiry;
use Illuminate\Http\RedirectResponse;

class InquiryController extends Controller
{
    public function store(InquiryRequest $request): RedirectResponse
    {
        $data = $request->safe();

        $hajjFields = ['room_type', 'cnic', 'passport_no', 'blood_group', 'next_of_kin_name', 'next_of_kin_contact'];
        $hajjDetails = array_filter($data->only($hajjFields));

        Inquiry::create([
            'name' => $data->name,
            'email' => $data->email,
            'phone' => $data->phone,
            'package_id' => $data->package_id,
            'package_category_id' => $data->package_category_id,
            'message' => $data->message,
            'hajj_details' => $hajjDetails === [] ? null : $hajjDetails,
            'source_page' => url()->previous(),
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', 'Thank you — your inquiry has been received. Our team will contact you shortly.');
    }
}
