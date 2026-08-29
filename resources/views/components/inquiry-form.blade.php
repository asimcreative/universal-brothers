@props(['package' => null, 'category' => null, 'title' => 'Send an Inquiry'])

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <h3 class="h5 mb-3">{{ $title }}</h3>
        <form method="POST" action="{{ route('inquiries.store') }}">
            @csrf
            @if($package)
                <input type="hidden" name="package_id" value="{{ $package->id }}">
            @endif
            @if($category)
                <input type="hidden" name="package_category_id" value="{{ $category->id }}">
            @endif

            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone / WhatsApp</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea name="message" rows="3" class="form-control">{{ old('message') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-send me-1"></i>Submit Inquiry
            </button>
        </form>
    </div>
</div>
