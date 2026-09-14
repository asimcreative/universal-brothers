<?php

namespace App\Support\Packages;

/**
 * The room types the builder offers by name. Each one sets the three values a
 * room row stores — `sharing_type` (a stable key the public filters use),
 * `occupancy`, and `display_label` — so they can never disagree.
 *
 * Keys and labels match the rows the live brochure packages already hold
 * ("quad" / "Quad Sharing" and so on), so every existing row is recognised.
 * Anything else is shown as "Other" with its own label, never rewritten.
 */
class RoomTypes
{
    public const ALL = [
        'quad' => ['label' => 'Quad Sharing', 'occupancy' => 4],
        'triple' => ['label' => 'Triple Sharing', 'occupancy' => 3],
        'double' => ['label' => 'Double Sharing', 'occupancy' => 2],
        'sharing_room' => ['label' => 'Sharing Room', 'occupancy' => null],
        'twin' => ['label' => 'Twin Sharing', 'occupancy' => 2],
        'single' => ['label' => 'Single Room', 'occupancy' => 1],
    ];

    /** The builder's choice for a stored row: a known key, "custom", or null for a new row. */
    public static function match(?string $sharingType, ?string $displayLabel): ?string
    {
        if (blank($sharingType) && blank($displayLabel)) {
            return null;
        }

        $type = self::ALL[$sharingType] ?? null;

        if ($type && (blank($displayLabel) || strcasecmp(trim($displayLabel), $type['label']) === 0)) {
            return $sharingType;
        }

        return 'custom';
    }
}
