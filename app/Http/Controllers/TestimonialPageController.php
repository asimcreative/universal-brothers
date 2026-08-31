<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\View\View;

class TestimonialPageController extends Controller
{
    public function index(): View
    {
        $testimonials = Testimonial::where('is_active', true)->orderBy('sort_order')->get();

        $videoTestimonials = $testimonials->filter(fn (Testimonial $testimonial) => filled($testimonial->video_url));
        $textTestimonials = $testimonials->filter(fn (Testimonial $testimonial) => blank($testimonial->video_url));

        return view('testimonials', compact('videoTestimonials', 'textTestimonials'));
    }
}
