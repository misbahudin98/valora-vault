<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientProjectPayment extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_no',
        'recorded_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'float',
        'payment_date' => 'date:Y-m-d',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'receipt_number',
    ];

    /**
     * Get official receipt number (Nomor Kuitansi Resmi).
     */
    public function getReceiptNumberAttribute(): string
    {
        $year = $this->payment_date ? $this->payment_date->format('Y') : date('Y');

        return sprintf('KWT-%s-%04d', $year, $this->id);
    }

    /**
     * Get the client project that owns this payment.
     *
     * @return BelongsTo<ClientProject, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'project_id');
    }
}
