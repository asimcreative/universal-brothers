@extends('layouts.app')

@section('title', $category->name . ' Packages | Universal Brothers')
@section('meta_description', 'Browse real ' . $category->name . ' packages from Universal Brothers — IATA-registered Hajj, Umrah and Tourism operator.')

@section('content')
    <div class="bg-primary text-white py-5">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-light">Home</a></li>
                    <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $category->name }}</li>
                </ol>
            </nav>
            <h1>{{ $category->name }} Packages</h1>
            @if($category->description)<p class="lead mb-0">{{ $category->description }}</p>@endif
        </div>
    </div>

    <div class="container py-5">
        @if($series->isNotEmpty())
            <div class="mb-4 d-flex flex-wrap gap-2">
                <a href="{{ route('packages.category', $category->slug) }}" class="btn btn-sm {{ request('series') ? 'btn-outline-primary' : 'btn-primary' }}">All</a>
                @foreach($series as $s)
                    <a href="{{ route('packages.category', [$category->slug, 'series' => $s->slug]) }}" class="btn btn-sm {{ request('series') === $s->slug ? 'btn-primary' : 'btn-outline-primary' }}">{{ $s->name }}</a>
                @endforeach
            </div>
        @endif

        @if($packages->isEmpty())
            <div class="alert alert-info">
                No {{ strtolower($category->name) }} packages are published yet. Please check back soon or <a href="{{ route('contact') }}">contact us</a> for the latest availability.
            </div>
        @else
            <div class="row g-4">
                @foreach($packages as $package)
                    <div class="col-md-6 col-lg-4">
                        <x-package-card :package="$package" />
                    </div>
                @endforeach
            </div>
            <div class="mt-5">{{ $packages->links() }}</div>
        @endif
    </div>
@endsection
