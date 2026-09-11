<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    use HasFactory;

    protected $fillable = [
        'label', 'address', 'phone_primary', 'phone_secondary', 'whatsapp',
        'email', 'google_maps_embed', 'is_domestic', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_domestic' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Public Google Maps link for this office's address.
     *
     * Used as the href of the map facade on the Contact page, so the facade is
     * a genuinely working link before any JavaScript runs — a visitor with JS
     * disabled still gets to the map, just in a new tab instead of inline.
     * Built from the address with the same keyless query the stored embed uses
     * (see OfficeSeeder), so the two can never point at different places.
     */
    public function mapsUrl(): ?string
    {
        return $this->address
            ? 'https://www.google.com/maps?q='.rawurlencode($this->address)
            : null;
    }
}
