<?php

namespace App\Models;

use App\Models\Concerns\IsLibraryRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A reusable customer-facing note or policy. Admin-only notes never live here:
 * they belong in `packages.internal_notes`, which nothing public reads.
 */
class NoteTemplate extends Model
{
    use IsLibraryRecord;

    public const LIBRARY_TITLE_COLUMN = 'title';

    public const CATEGORIES = [
        'general' => 'General Hajj notes',
        'hotel_policy' => 'Hotel policies',
        'room_policy' => 'Room policies',
        'transport_policy' => 'Transport policies',
        'visa' => 'Visa notes',
        'ticket' => 'Ticket notes',
        'qurbani' => 'Qurbani notes',
        'makkah_stay' => 'Makkah stay notes',
        'aziziya_stay' => 'Aziziya stay notes',
        'customer_instructions' => 'Important customer instructions',
        'terms' => 'Terms and conditions',
        'pricing' => 'Pricing notes',
    ];

    /** The values `package_notes.note_type` accepts, in plain words. */
    public const NOTE_TYPES = [
        'general' => 'General',
        'important' => 'Important',
        'pricing' => 'Prices',
        'accommodation' => 'Hotels & rooms',
        'booking' => 'Booking',
        'travel' => 'Travel',
        'disclaimer' => 'Terms & conditions',
    ];

    protected $fillable = ['title', 'heading', 'category', 'note_type', 'content', 'is_important', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_important' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function packageRows(): HasMany
    {
        return $this->hasMany(PackageNote::class);
    }
}
