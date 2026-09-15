{{-- Space or divider. $data --}}
<div class="pb-spacer pb-spacer--{{ in_array($data['size'] ?? 'medium', ['small', 'medium', 'large'], true) ? $data['size'] : 'medium' }}" aria-hidden="true">
    @if(! empty($data['line']))
        <div class="container"><hr class="pb-spacer-line"></div>
    @endif
</div>
