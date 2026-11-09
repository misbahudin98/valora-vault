<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMenu extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_role_id',
        'menu_name',
        'menu_desc',
        'module_type',
        'pic',
        'pic_user_id',
        'price',
        'price_note',
        'is_done',
        'completed_date',
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
        'sort_order',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'float',
        'is_done' => 'boolean',
        'completed_date' => 'date:Y-m-d',
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
        'sort_order' => 'integer',
    ];

    /**
     * Get the project role that owns the menu.
     *
     * @return BelongsTo<ProjectRole, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(ProjectRole::class, 'project_role_id');
    }

    /**
     * Get the assigned user for this menu PIC.
     *
     * @return BelongsTo<User, $this>
     */
    public function picUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }
}
