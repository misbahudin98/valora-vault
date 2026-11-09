<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterBillingRate extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'rate_key',
        'name',
        'description',
        'unit',
        'category',
        'price',
        'has_create',
        'has_read',
        'has_update',
        'has_delete',
        'has_grafik',
        'has_box_dashboard',
        'has_import',
        'has_export',
        'has_filter',
        'has_print',
        'has_notification',
        'has_custom_logic',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'float',
        'has_create' => 'boolean',
        'has_read' => 'boolean',
        'has_update' => 'boolean',
        'has_delete' => 'boolean',
        'has_grafik' => 'boolean',
        'has_box_dashboard' => 'boolean',
        'has_import' => 'boolean',
        'has_export' => 'boolean',
        'has_filter' => 'boolean',
        'has_print' => 'boolean',
        'has_notification' => 'boolean',
        'has_custom_logic' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the revision histories for this billing rate item.
     *
     * @return HasMany<BillingRateHistory, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(BillingRateHistory::class, 'rate_key', 'rate_key')->orderBy('created_at', 'desc');
    }

    /**
     * Helper to get rate price by key or fallback to a provided default.
     */
    public static function getRatePrice(string $key, float $default = 0.0): float
    {
        $rate = static::where('rate_key', $key)->where('is_active', true)->first();
        return $rate ? (float) $rate->price : $default;
    }
}
