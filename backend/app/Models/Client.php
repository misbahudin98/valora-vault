<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'industry',
    ];

    /**
     * Get the projects for the client.
     *
     * @return HasMany<ClientProject, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(ClientProject::class, 'client_id');
    }

    /**
     * Scope a query to search clients by name or contact.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Client> $query
     * @param string $term
     * @return \Illuminate\Database\Eloquent\Builder<Client>
     */
    public function scopeSearch($query, string $term)
    {
        $escaped = addcslashes(trim($term), '%_\\');

        return $query->where(function ($q) use ($escaped) {
            $q->where('name', 'like', "%{$escaped}%")
              ->orWhere('contact_person', 'like', "%{$escaped}%")
              ->orWhere('phone', 'like', "%{$escaped}%")
              ->orWhere('industry', 'like', "%{$escaped}%");
        });
    }
}
