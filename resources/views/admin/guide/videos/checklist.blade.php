@extends('layouts.admin')

@section('title', 'Hajj Package Checklist')
@section('subtitle', 'Tick each item while you build a package. Print it, or download it to keep a copy.')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> / <a href="{{ route('admin.guide.index') }}">Guide</a> / <a href="{{ route('admin.training.index') }}">Video Training</a> / <span>Package checklist</span>
@endsection

@section('actions')
    <button type="button" class="btn btn-primary" data-print-checklist><i class="bi bi-printer me-1" aria-hidden="true"></i>Print checklist</button>
    <a href="{{ route('admin.training.checklist.download') }}" class="btn btn-outline-primary"><i class="bi bi-download me-1" aria-hidden="true"></i>Download checklist</a>
@endsection

@section('content')
    <div class="training-checklist card">
        <div class="card-body">
            <div class="training-checklist-head">
                <strong>Universal Brothers — Hajj package creation checklist</strong>
                <span>Package code: ______________</span>
                <span>Checked by: ______________</span>
                <span>Date: ______________</span>
            </div>

            <div class="training-checklist-groups">
                @foreach($checklist as $group => $items)
                    <fieldset class="training-checklist-group">
                        <legend>{{ $group }}</legend>
                        @foreach($items as $item)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="check-{{ $loop->parent->index }}-{{ $loop->index }}">
                                <label class="form-check-label" for="check-{{ $loop->parent->index }}-{{ $loop->index }}">{{ $item }}</label>
                            </div>
                        @endforeach
                    </fieldset>
                @endforeach
            </div>

            <p class="form-help mb-0 mt-3 d-print-none">Ticks on this page are not saved — it is a working sheet. The builder's own checklist, on the left of every package, is saved with the package.</p>
        </div>
    </div>
@endsection
