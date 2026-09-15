{{--
    Search and sharing fields with live previews of the Google result and the
    social sharing card. Used by the page builder; values are plain text.

    Props
      values      array with meta_title, meta_description, focus_keyword, canonical_url,
                  og_title, og_description, og_image, noindex
      fallbackTitle, fallbackDescription   what search engines see when a field is left empty
      url         the page's public address, for the preview
--}}
@props(['values' => [], 'fallbackTitle' => '', 'fallbackDescription' => '', 'url' => ''])

@php($v = fn (string $key) => old($key, $values[$key] ?? ''))

<div class="seo-fields" data-seo-fields data-fallback-title="{{ $fallbackTitle }}" data-fallback-description="{{ $fallbackDescription }}">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="mb-3">
                <label for="seo-meta-title" class="form-label">Title in search results</label>
                <input type="text" id="seo-meta-title" name="meta_title" value="{{ $v('meta_title') }}" maxlength="255" data-char-count="60" data-seo="title"
                       @class(['form-control', 'is-invalid' => $errors->has('meta_title')]) placeholder="{{ $fallbackTitle }}" aria-describedby="seo-meta-title-help">
                <div class="d-flex justify-content-between gap-2">
                    <div class="form-help" id="seo-meta-title-help">Leave empty to use the page title. About 50–60 characters shows in full on Google.</div>
                    <span class="char-count" data-char-count-for="seo-meta-title"></span>
                </div>
                <x-admin.error name="meta_title" />
            </div>

            <div class="mb-3">
                <label for="seo-meta-description" class="form-label">Description in search results</label>
                <textarea id="seo-meta-description" name="meta_description" rows="3" maxlength="255" data-char-count="160" data-seo="description"
                          @class(['form-control', 'is-invalid' => $errors->has('meta_description')]) aria-describedby="seo-meta-description-help">{{ $v('meta_description') }}</textarea>
                <div class="d-flex justify-content-between gap-2">
                    <div class="form-help" id="seo-meta-description-help">One or two sentences that make someone want to click. Around 150–160 characters. Leave empty to use the start of the page text.</div>
                    <span class="char-count" data-char-count-for="seo-meta-description"></span>
                </div>
                <x-admin.error name="meta_description" />
            </div>

            <div class="mb-3">
                <label for="seo-focus-keyword" class="form-label">Main search phrase (optional)</label>
                <input type="text" id="seo-focus-keyword" name="focus_keyword" value="{{ $v('focus_keyword') }}" maxlength="100" data-seo="keyword"
                       @class(['form-control', 'is-invalid' => $errors->has('focus_keyword')]) placeholder="e.g. Hajj packages from Karachi" aria-describedby="seo-focus-keyword-help seo-keyword-check">
                <div class="form-help" id="seo-focus-keyword-help">The words you most want this page to be found for. Only used to give you tips below; it is not added to the page.</div>
                <ul class="seo-checks list-unstyled small mb-0 mt-2" id="seo-keyword-check" data-seo-checks aria-live="polite"></ul>
                <x-admin.error name="focus_keyword" />
            </div>

            <div class="form-check form-switch mb-3">
                <input type="hidden" name="noindex" value="0">
                <input class="form-check-input" type="checkbox" role="switch" id="seo-noindex" name="noindex" value="1" @checked((bool) $v('noindex')) aria-describedby="seo-noindex-help">
                <label class="form-check-label fw-semibold" for="seo-noindex">Hide this page from search engines</label>
                <div class="form-help" id="seo-noindex-help">Visitors with the link can still open it. Use this for thank-you pages or pages not ready to be found on Google.</div>
            </div>

            <details class="seo-advanced" @if($errors->hasAny(['canonical_url', 'og_title', 'og_description', 'og_image.path'])) open @endif>
                <summary>Sharing on WhatsApp and Facebook, and advanced options</summary>
                <div class="pt-3">
                    <div class="mb-3">
                        <label for="seo-og-title" class="form-label">Title when shared</label>
                        <input type="text" id="seo-og-title" name="og_title" value="{{ $v('og_title') }}" maxlength="255" data-seo="og-title" class="form-control" placeholder="Same as the search title">
                        <x-admin.error name="og_title" />
                    </div>
                    <div class="mb-3">
                        <label for="seo-og-description" class="form-label">Description when shared</label>
                        <textarea id="seo-og-description" name="og_description" rows="2" maxlength="300" data-seo="og-description" class="form-control" placeholder="Same as the search description">{{ $v('og_description') }}</textarea>
                        <x-admin.error name="og_description" />
                    </div>
                    <x-admin.image-picker name="og_image" label="Image when shared" :value="['path' => old('og_image.path', $values['og_image'] ?? null), 'alt' => '']" :alt="false"
                        help="A landscape photo, ideally 1200 × 630 pixels. Leave empty to use the page banner image." class="mb-3" />
                    <div>
                        <label for="seo-canonical" class="form-label">Main address of this content (canonical link)</label>
                        <input type="text" id="seo-canonical" name="canonical_url" value="{{ $v('canonical_url') }}" maxlength="255" @class(['form-control', 'is-invalid' => $errors->has('canonical_url')]) placeholder="https://…" aria-describedby="seo-canonical-help">
                        <div class="form-help" id="seo-canonical-help">Only needed when the same content also exists at another address. Leave empty in almost every case.</div>
                        <x-admin.error name="canonical_url" />
                    </div>
                </div>
            </details>
        </div>

        <div class="col-lg-5">
            <div class="seo-preview-card" aria-label="Google search result preview">
                <span class="seo-preview-label">How it may look on Google</span>
                <div class="seo-google">
                    <span class="seo-google-url" data-seo-preview="url">{{ $url }}</span>
                    <span class="seo-google-title" data-seo-preview="title">{{ $v('meta_title') ?: $fallbackTitle }}</span>
                    <span class="seo-google-description" data-seo-preview="description">{{ $v('meta_description') ?: $fallbackDescription }}</span>
                </div>
            </div>
            <div class="seo-preview-card mt-3" aria-label="Social sharing preview">
                <span class="seo-preview-label">How it may look when shared</span>
                <div class="seo-social">
                    <div class="seo-social-image" data-seo-preview="image" @if($values['og_image'] ?? null) style="background-image: url('{{ \Illuminate\Support\Facades\Storage::disk('public')->url($values['og_image']) }}')" @endif></div>
                    <div class="seo-social-body">
                        <span class="seo-social-site">{{ parse_url(config('app.url'), PHP_URL_HOST) }}</span>
                        <span class="seo-social-title" data-seo-preview="og-title">{{ $v('og_title') ?: ($v('meta_title') ?: $fallbackTitle) }}</span>
                        <span class="seo-social-description" data-seo-preview="og-description">{{ $v('og_description') ?: ($v('meta_description') ?: $fallbackDescription) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
