@extends('layouts.admin')

@section('title', 'New Saved Section')
@section('subtitle', 'Choose the kind of section to create. You can insert it into any page afterwards.')
@section('guide', 'reusable-sections')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> / <a href="{{ route('admin.content-blocks.index') }}">Saved sections</a> / <span>New</span>
@endsection

@section('content')
    @foreach($groups as $group)
        <x-admin.panel :title="$group['label']" class="mb-4">
            <div class="pb-library-grid">
                @foreach($group['blocks'] as $type => $definition)
                    <a href="{{ route('admin.content-blocks.create', ['type' => $type]) }}" class="pb-library-card">
                        <span class="pb-library-icon" aria-hidden="true"><i class="bi {{ $definition['icon'] }}"></i></span>
                        <span class="pb-library-text">
                            <strong>{{ $definition['name'] }}</strong>
                            <small>{{ $definition['description'] }}</small>
                        </span>
                    </a>
                @endforeach
            </div>
        </x-admin.panel>
    @endforeach
@endsection
