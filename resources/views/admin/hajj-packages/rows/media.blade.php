{{--
    One gallery photo or video. $i, $row

    A file input can never be pre-filled, so an existing photo is kept unless a
    new file is chosen — the hidden id tells the server which photo this row is.
--}}
@php $path = $row['image_path'] ?? null; @endphp
<div class="b-row b-row-media" data-row="media" data-index="{{ $i }}" data-image-field>
    <input type="hidden" name="media[{{ $i }}][id]" data-field="id" value="{{ $row['id'] ?? '' }}">
    <div>
        <label class="form-label" for="media-type-{{ $i }}">Photo of</label>
        <select id="media-type-{{ $i }}" name="media[{{ $i }}][media_type]" data-field="media_type" class="form-select" aria-label="Media type">
            @foreach(['gallery' => 'General / gallery', 'hotel' => 'Hotel', 'accommodation' => 'Room', 'aziziya' => 'Aziziya', 'other' => 'Other'] as $value => $label)
                <option value="{{ $value }}" @selected(($row['media_type'] ?? 'gallery') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" for="media-file-{{ $i }}">Photo {{ $path ? '(leave blank to keep current)' : '' }}</label>
        <input type="file" id="media-file-{{ $i }}" name="media[{{ $i }}][file]" accept="image/jpeg,image/png,image/webp" class="form-control @error("media.$i.file") is-invalid @enderror" aria-label="Media image">
        <x-admin.error :name="'media.'.$i.'.file'" />
        <div class="image-preview" @if(! $path) hidden @endif>
            <img src="{{ $path ? Storage::url($path) : '' }}" alt="Current photo" data-image-preview @if(! $path) hidden @endif>
        </div>
    </div>
    <div>
        <label class="form-label" for="media-alt-{{ $i }}">Describe the photo</label>
        <input type="text" id="media-alt-{{ $i }}" name="media[{{ $i }}][alt_text]" data-field="alt_text" value="{{ $row['alt_text'] ?? '' }}" class="form-control" placeholder="For screen readers and Google" aria-label="Media alt text">
    </div>
    <div>
        <label class="form-label" for="media-caption-{{ $i }}">Caption</label>
        <input type="text" id="media-caption-{{ $i }}" name="media[{{ $i }}][caption]" data-field="caption" value="{{ $row['caption'] ?? '' }}" class="form-control" aria-label="Media caption">
    </div>
    <x-admin.row-actions label="photo" />
    <div class="b-row-wide">
        <label class="visually-hidden" for="media-video-{{ $i }}">Video link</label>
        <input type="url" id="media-video-{{ $i }}" name="media[{{ $i }}][video_url]" data-field="video_url" value="{{ $row['video_url'] ?? '' }}" class="form-control form-control-sm @error("media.$i.video_url") is-invalid @enderror" placeholder="Video link instead of a photo (optional), e.g. https://www.youtube.com/…" aria-label="Media video URL">
    </div>
</div>
