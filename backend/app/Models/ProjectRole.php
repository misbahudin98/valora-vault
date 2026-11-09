<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectRole extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'role_name',
        'sort_order',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get the project that owns the role.
     *
     * @return BelongsTo<ClientProject, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'project_id');
    }

    /**
     * Get the menus for the role.
     *
     * @return HasMany<ProjectMenu, $this>
     */
    public function menus(): HasMany
    {
        return $this->hasMany(ProjectMenu::class, 'project_role_id')->orderBy('sort_order', 'asc');
    }
}
