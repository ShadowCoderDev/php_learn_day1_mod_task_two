<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
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
     * رابطه چند به چند مجوز با نقش‌ها
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * دریافت همه کاربرانی که این مجوز را دارند (از طریق نقش‌هایشان)
     */
    public function users()
    {
        $users = collect([]);

        $this->roles->each(function ($role) use ($users) {
            $role->users->each(function ($user) use ($users) {
                if (!$users->contains('id', $user->id)) {
                    $users->push($user);
                }
            });
        });

        return $users;
    }
}
