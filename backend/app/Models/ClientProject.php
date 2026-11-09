<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientProject extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'project_name',
        'app_name',
        'project_type',
        'lead_pic',
        'lead_pic_id',
        'github_url',
        'app_desc',
        'use_landing',
        'use_login',
        'use_register',
        'use_app_core',
        'landing_price',
        'login_price',
        'register_price',
        'landing_note',
        'login_note',
        'register_note',
        'landing_capabilities',
        'login_capabilities',
        'register_capabilities',
        'status',
        'grand_total',
        'paid_amount',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'use_landing' => 'boolean',
        'use_login' => 'boolean',
        'use_register' => 'boolean',
        'use_app_core' => 'boolean',
        'landing_price' => 'float',
        'login_price' => 'float',
        'register_price' => 'float',
        'landing_capabilities' => 'array',
        'login_capabilities' => 'array',
        'register_capabilities' => 'array',
        'grand_total' => 'float',
        'paid_amount' => 'float',
    ];

    /**
     * Default 10 capabilities for Landing Page.
     *
     * @param mixed $value
     * @return array<string, bool>
     */
    public function getLandingCapabilitiesAttribute($value): array
    {
        $default = [
            'has_create' => false,
            'has_read' => true,
            'has_update' => false,
            'has_delete' => false,
            'has_grafik' => false,
            'has_box_dashboard' => false,
            'has_import' => false,
            'has_export' => false,
            'has_filter' => false,
            'has_print' => false,
        ];
        return array_merge($default, is_array($value) ? $value : (json_decode((string) $value, true) ?: []));
    }

    /**
     * Default 10 capabilities for Login Page.
     *
     * @param mixed $value
     * @return array<string, bool>
     */
    public function getLoginCapabilitiesAttribute($value): array
    {
        $default = [
            'has_create' => true,
            'has_read' => true,
            'has_update' => true,
            'has_delete' => false,
            'has_grafik' => false,
            'has_box_dashboard' => false,
            'has_import' => false,
            'has_export' => false,
            'has_filter' => false,
            'has_print' => false,
        ];
        return array_merge($default, is_array($value) ? $value : (json_decode((string) $value, true) ?: []));
    }

    /**
     * Default 10 capabilities for Register Page.
     *
     * @param mixed $value
     * @return array<string, bool>
     */
    public function getRegisterCapabilitiesAttribute($value): array
    {
        $default = [
            'has_create' => true,
            'has_read' => true,
            'has_update' => false,
            'has_delete' => false,
            'has_grafik' => false,
            'has_box_dashboard' => false,
            'has_import' => false,
            'has_export' => false,
            'has_filter' => false,
            'has_print' => false,
        ];
        return array_merge($default, is_array($value) ? $value : (json_decode((string) $value, true) ?: []));
    }

    /**
     * Get the client that owns the project.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Get the lead PIC user for this project.
     *
     * @return BelongsTo<User, $this>
     */
    public function leadPicUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_pic_id');
    }

    /**
     * Get the assigned workers / team members for this project.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<User, $this>
     */
    public function workers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_project_workers', 'project_id', 'user_id')
            ->withPivot('role_in_project')
            ->withTimestamps();
    }

    /**
     * Get the configured roles for this project.
     *
     * @return HasMany<ProjectRole, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(ProjectRole::class, 'project_id')->orderBy('sort_order', 'asc');
    }

    /**
     * Get the database tables configured for this project.
     *
     * @return HasMany<ProjectDatabaseTable, $this>
     */
    public function databaseTables(): HasMany
    {
        return $this->hasMany(ProjectDatabaseTable::class, 'project_id')->orderBy('id', 'asc');
    }

    /**
     * Get the payments recorded for this project.
     *
     * @return HasMany<ClientProjectPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(ClientProjectPayment::class, 'project_id')->orderBy('payment_date', 'desc');
    }

    /**
     * Recalculate and update the project's paid amount from payment transactions.
     */
    public function recalculatePaidAmount(): float
    {
        $paid = (float) $this->payments()->sum('amount');
        $this->paid_amount = $paid;
        $this->save();

        return $paid;
    }

    /**
     * Recalculate and update the project's grand total.
     */
    public function recalculateGrandTotal(): float
    {
        $total = 0.0;
        if ($this->use_landing) {
            $total += (float) $this->landing_price;
        }
        if ($this->use_login) {
            $total += (float) $this->login_price;
        }
        if ($this->use_register) {
            $total += (float) $this->register_price;
        }

        // Sum all role menus
        foreach ($this->roles as $role) {
            $total += (float) $role->menus()->sum('price');
        }

        // Sum all database tables
        $total += (float) $this->databaseTables()->sum('price');

        $this->grand_total = $total;
        $this->save();

        return $total;
    }
}
