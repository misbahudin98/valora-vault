<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingRateHistory extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'rate_key',
        'item_name',
        'old_price',
        'new_price',
        'reason',
        'updated_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'old_price' => 'float',
        'new_price' => 'float',
    ];

    /**
     * Get the master billing rate definition.
     *
     * @return BelongsTo<MasterBillingRate, $this>
     */
    public function masterRate(): BelongsTo
    {
        return $this->belongsTo(MasterBillingRate::class, 'rate_key', 'rate_key');
    }
}
