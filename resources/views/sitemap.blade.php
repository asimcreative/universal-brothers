<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ url('/') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ route('contact') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    @foreach($categories as $category)
    <url>
        <loc>{{ route('packages.category', $category->slug) }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    @endforeach
    @foreach($packages as $package)
    <url>
        <loc>{{ route('packages.show', [$package->category->slug, $package->slug]) }}</loc>
        <lastmod>{{ $package->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>{{ $package->is_featured ? '0.9' : '0.7' }}</priority>
    </url>
    @endforeach
</urlset>
