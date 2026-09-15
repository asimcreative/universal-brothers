@extends('layouts.admin')

@section('title', 'Inquiry from '.$inquiry->name)
@section('guide', 'enquiries')

@section('actions')
    <a href="{{ route('admin.inquiries.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to Inquiries</a>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">Enquiry Details</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $inquiry->name }}</dd>
                        <dt class="col-sm-3">Email</dt><dd class="col-sm-9"><a href="mailto:{{ $inquiry->email }}">{{ $inquiry->email }}</a></dd>
                        <dt class="col-sm-3">Phone</dt><dd class="col-sm-9">{{ $inquiry->phone }}</dd>
                        <dt class="col-sm-3">Package</dt><dd class="col-sm-9">{{ $inquiry->package?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Category</dt><dd class="col-sm-9">{{ $inquiry->category?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Message</dt><dd class="col-sm-9">{{ $inquiry->message ?? '—' }}</dd>
                        <dt class="col-sm-3">Source Page</dt><dd class="col-sm-9"><small class="text-muted">{{ $inquiry->source_page }}</small></dd>
                        <dt class="col-sm-3">Submitted</dt><dd class="col-sm-9">{{ $inquiry->created_at->format('d M Y H:i') }}</dd>
                    </dl>

                    @if($inquiry->hajj_details)
                        <hr>
                        <h3 class="form-section-title h6">Hajj Application Details</h3>
                        <dl class="row mb-0">
                            @foreach($inquiry->hajj_details as $key => $value)
                                <dt class="col-sm-3 text-capitalize">{{ str_replace('_', ' ', $key) }}</dt>
                                <dd class="col-sm-9">{{ $value }}</dd>
                            @endforeach
                        </dl>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Status</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.inquiries.update', $inquiry) }}">
                        @csrf @method('PUT')
                        <label for="inquiry-status" class="form-label">Update Status</label>
                        <select name="status" id="inquiry-status" class="form-select mb-3">
                            @foreach(['new', 'contacted', 'closed'] as $status)
                                <option value="{{ $status }}" {{ $inquiry->status === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary w-100">Update Status</button>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.inquiries.destroy', $inquiry) }}" data-confirm="This cannot be undone." data-confirm-title="Delete this inquiry?" data-confirm-button="Delete">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger w-100"><i class="bi bi-trash me-1" aria-hidden="true"></i>Delete Inquiry</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
