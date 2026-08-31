{{-- Single shared lightbox modal, reused by any page that renders
     `.gallery-item[data-lightbox-trigger]` elements (package gallery, Media
     page). Populated dynamically by app.js `initLightbox()` — never one
     modal per image, since a package/media gallery can have many items. --}}
<div class="modal fade" id="lightboxModal" tabindex="-1" aria-labelledby="lightboxModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-black">
            <div class="modal-header border-0">
                <h2 class="modal-title visually-hidden" id="lightboxModalLabel">Image preview</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 text-center">
                <img src="" alt="" class="img-fluid" id="lightboxModalImage">
            </div>
        </div>
    </div>
</div>
