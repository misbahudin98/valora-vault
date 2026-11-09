<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDatabaseTable extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'table_name',
        'price',
        'is_done',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'float',
        'is_done' => 'boolean',
    ];

    /**
     * Get the project that owns the database table.
     *
     * @return BelongsTo<ClientProject, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'project_id');
    }
}
