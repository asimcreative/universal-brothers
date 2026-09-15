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

    /**
     * The Google Maps player address for this office, or null.
     *
     * The field used to hold whatever was pasted — usually a whole <iframe>
     * tag — and the Contact page printed it as raw HTML. Only the address of a
     * real Google Maps embed is ever used now; the frame around it is written
     * by the site itself.
     */
    public function mapEmbedUrl(): ?string
    {
        return self::normalizeMapEmbed($this->google_maps_embed);
    }

    /**
     * Accepts what an admin copies from Google Maps ("Share → Embed a map"
     * code, or just its address) and returns the embed address, or null when
     * the value is not a Google Maps embed.
     */
    public static function normalizeMapEmbed(?string $value): ?string
    {
        $value = trim(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5));

        if ($value === '') {
            return null;
        }

        if (stripos($value, '<iframe') !== false) {
            if (! preg_match('/\ssrc\s*=\s*["\']([^"\']+)["\']/i', $value, $m)) {
                return null;
            }
            $value = trim($m[1]);
        }

        $parts = parse_url($value);
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);

        $isGoogle = ($parts['scheme'] ?? '') === 'https' && in_array($host, ['www.google.com', 'google.com', 'maps.google.com'], true);
        $isEmbed = str_starts_with($path, '/maps/embed') || (str_starts_with($path, '/maps') && ($query['output'] ?? null) === 'embed');

        return $isGoogle && $isEmbed && ! preg_match('/[\s"\'<>]/', $value) ? $value : null;
    }

    /** A map made from the address alone, for offices without a pasted embed. */
    public function addressEmbedUrl(): ?string
    {
        return $this->address ? 'https://www.google.com/maps?q='.rawurlencode($this->address).'&output=embed' : null;
    }
}
