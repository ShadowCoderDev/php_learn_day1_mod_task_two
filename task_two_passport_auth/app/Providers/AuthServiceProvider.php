<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Permission;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // ثبت gates برای همه مجوزها
        $this->registerPermissions();

        // تعریف گیت برای چک کردن نقش ادمین
        Gate::define('admin', function (User $user) {
            return $user->hasRole('admin');
        });
    }

    /**
     * ثبت همه مجوزها به عنوان Gates
     */
    protected function registerPermissions()
    {
        try {
            // دریافت همه مجوزها از دیتابیس
            $permissions = Permission::all();

            foreach ($permissions as $permission) {
                Gate::define($permission->name, function (User $user) use ($permission) {
                    return $user->hasPermission($permission);
                });
            }
        } catch (\Exception $e) {
            // ممکن است جدول هنوز وجود نداشته باشد (در هنگام مایگریشن اولیه)
            report($e);
        }
    }
}
