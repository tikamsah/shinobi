<?php

namespace Tikamsah\Shinobi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tikamsah\Shinobi\Concerns\RefreshesPermissionCache;
use Tikamsah\Shinobi\Contracts\Permission as PermissionContract;

class Permission extends Model implements PermissionContract
{
    use RefreshesPermissionCache;
    
    /**
     * The attributes that are fillable via mass assignment.
     *
     * @var array
     */
    protected $fillable = ['name', 'slug', 'description'];

    /**
     * Create a new Permission instance.
     * 
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('shinobi.tables.permissions'));
    }

    /**
     * Permissions can belong to many roles.
     *
     * @return Model
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(config('shinobi.models.role'))->withTimestamps();
    }
}
