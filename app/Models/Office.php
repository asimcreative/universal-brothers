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
}
