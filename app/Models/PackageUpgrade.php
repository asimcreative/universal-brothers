<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageUpgrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id', 'upgrade_option_id', 'name', 'description', 'price', 'currency',
        'price_pkr', 'price_sar', 'price_usd',
        'price_basis', 'is_included', 'notes', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_included' => 'boolean',
            'price' => 'decimal:2',
            'price_pkr' => 'decimal:2',
            'price_sar' => 'decimal:2',
            'price_usd' => 'decimal:2',
        ];
    }


    /**
     * This row's price in one currency, or null if that brochure does not
     * publish it.
     *
     * Never converted. The three brochures print their own figures — quad
     * and triple nights are both US$600 and yet PKR 168,600 and PKR 169,400
     * — so a rate would produce numbers the client never quoted. A null
     * means the price is genuinely absent from that list, and the page says
     * so rather than showing a figure in a currency nobody asked for.
     */
    public function priceIn(?string $currency = null): ?float
    {
        $value = $this->{\App\Support\Currency::column($currency ?? \App\Support\Currency::current())};

        return $value === null ? null : (float) $value;
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function upgradeOption(): BelongsTo
    {
        return $this->belongsTo(UpgradeOption::class);
    }
}
