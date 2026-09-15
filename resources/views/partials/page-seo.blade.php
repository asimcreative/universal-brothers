{{-- Search and sharing tags for a CMS page, from the values set in the admin's "Search & sharing" panel. Needs $seo. --}}
@if(filled($seo['canonical'] ?? null))
    @section('canonical', $seo['canonical'])
@endif
@if(filled($seo['og_title'] ?? null))
    @section('og_title', $seo['og_title'])
@endif
@if(filled($seo['og_description'] ?? null))
    @section('og_description', $seo['og_description'])
@endif
@if(filled($seo['og_image'] ?? null) && ($ubOgImage = \App\Support\PageBuilder\Block::imageUrl($seo['og_image'])))
    @section('og_image', url($ubOgImage))
@endif
@if(! empty($seo['noindex']))
    @section('robots', 'noindex, nofollow')
@endif
