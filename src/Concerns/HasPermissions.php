<?php

namespace Caffeinated\Shinobi\Concerns;

use Caffeinated\Shinobi\Facades\Shinobi;
use Caffeinated\Shinobi\Contracts\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasPermissions
{
    /**
     * Users can have many permissions
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(config('shinobi.models.permission'))->withTimestamps();
    }

    /**
     * The mothergoose check. Runs through each scenario provided
     * by Shinobi - checking for special flags, role permissions, and
     * individual user permissions; in that order.
     * 
     * @param  Permission|String  $permission
     * @return boolean
     */
    public function hasPermissionTo($permission): bool
    {
        // Check role flags
        if ((method_exists($this, 'hasPermissionRoleFlags') and $this->hasPermissionRoleFlags())) {
            return $this->hasPermissionThroughRoleFlag();
        }

        if ((method_exists($this, 'hasPermissionFlags') and $this->hasPermissionFlags())) {
            return $this->hasPermissionThroughFlag();
        }
        
        // Fetch permission if we pass through a string
        if (is_string($permission)) {
            try {
                $model = $this->getPermissionModel();

                $permission = $model->where('slug', $permission)->firstOrFail();
            } catch (\Exception $e) {
                // 
            }
        }
        
        // Check role permissions
        if (method_exists($this, 'hasPermissionThroughRole') and $this->hasPermissionThroughRole($permission)) {
            return $this->hasPermissionThroughRole($permission);
        }
        
        // Check user permission
        if ($this->hasPermission($permission)) {
            return true;
        }

        return false;
    }

    /**
     * Give the specified permissions to the model.
     * 
     * @param  array  $permissions
     * @return self
     */
    public function givePermissionTo(...$permissions): self
    {        
        $permissions = array_flatten($permissions);
        $permissions = $this->getPermissions($permissions);

        if (! $permissions) {
            return $this;
        }

        $this->permissions()->syncWithoutDetaching($permissions);

        return $this;
    }

    /**
     * Revoke the specified permissions from the model.
     * 
     * @param  array  $permissions
     * @return self
     */
    public function revokePermissionTo(...$permissions): self
    {
        $permissions = array_flatten($permissions);
        $permissions = $this->getPermissions($permissions);

        $this->permissions()->detach($permissions);

        return $this;
    }

    /**
     * Sync the specified permissions against the model.
     * 
     * @param  array  $permissions
     * @return self
     */
    public function syncPermissions(...$permissions): self
    {
        $permissions = array_flatten($permissions);
        $permissions = $this->getPermissions($permissions);

        $this->permissions()->sync($permissions);

        return $this;
    }

    /**
     * Get the specified permissions.
     * 
     * @param  array  $permissions
     * @return Permission
     */
    protected function getPermissions(array $permissions)
    {
        return array_map(function($permission) {
            $model = $this->getPermissionModel();

            if ($permission instanceof $model) {
                return $permission->id;
            }

            $permission = $model->where('slug', $permission)->first();

            return $permission->id;
        }, $permissions);
    }

    /**
     * Checks if the user has the given permission assigned.
     * 
     * @param  \Caffeinated\Shinobi\Models\Permission  $permission
     * @return boolean
     */
    protected function hasPermission($permission): bool
    {
        $model = $this->getPermissionModel();

        if ($permission instanceof $model) {
            $permission = $permission->slug;
        }

        return (bool) $this->permissions->where('slug', $permission)->count();
    }

    /**
     * Get the model instance responsible for permissions.
     * 
     * @return \Caffeinated\Shinobi\Contracts\Permission
     */
    protected function getPermissionModel(): Permission
    {
        return app()->make(config('shinobi.models.permission'));
    }
}