<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * رابطه چند به چند نقش با کاربران
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * رابطه چند به چند نقش با مجوزها
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * افزودن مجوز به نقش
     */
    public function givePermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        $this->permissions()->syncWithoutDetaching($permission);

        return $this;
    }

    /**
     * حذف مجوز از نقش
     */
    public function revokePermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        $this->permissions()->detach($permission);

        return $this;
    }

    /**
     * جایگزینی همه مجوزهای نقش
     */
    public function syncPermissions($permissions)
    {
        if (is_array($permissions)) {
            $permissionIds = [];

            foreach ($permissions as $permission) {
                if (is_string($permission)) {
                    $p = Permission::where('name', $permission)->firstOrFail();
                    $permissionIds[] = $p->id;
                } else {
                    $permissionIds[] = $permission->id;
                }
            }

            $this->permissions()->sync($permissionIds);

            return $this;
        }

        return $this->syncPermissions([$permissions]);
    }
}
