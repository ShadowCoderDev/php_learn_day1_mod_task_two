<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ریست جدول‌ها
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Role::truncate();
        Permission::truncate();
        \DB::table('role_user')->truncate();
        \DB::table('permission_role')->truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ایجاد مجوزها
        $permissions = [
            [
                'name' => 'create-post',
                'display_name' => 'ایجاد پست',
                'description' => 'امکان ایجاد پست جدید'
            ],
            [
                'name' => 'edit-post',
                'display_name' => 'ویرایش پست',
                'description' => 'امکان ویرایش پست'
            ],
            [
                'name' => 'delete-post',
                'display_name' => 'حذف پست',
                'description' => 'امکان حذف پست'
            ],
            [
                'name' => 'create-user',
                'display_name' => 'ایجاد کاربر',
                'description' => 'امکان ایجاد کاربر جدید'
            ],
            [
                'name' => 'edit-user',
                'display_name' => 'ویرایش کاربر',
                'description' => 'امکان ویرایش کاربر'
            ],
            [
                'name' => 'delete-user',
                'display_name' => 'حذف کاربر',
                'description' => 'امکان حذف کاربر'
            ],
            [
                'name' => 'assign-role',
                'display_name' => 'اختصاص نقش',
                'description' => 'امکان اختصاص نقش به کاربر'
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // ایجاد نقش‌ها
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'مدیر',
            'description' => 'دسترسی کامل به همه بخش‌ها'
        ]);

        $editorRole = Role::create([
            'name' => 'editor',
            'display_name' => 'ویرایشگر',
            'description' => 'دسترسی به ویرایش محتوا'
        ]);

        $userRole = Role::create([
            'name' => 'user',
            'display_name' => 'کاربر عادی',
            'description' => 'دسترسی محدود به سیستم'
        ]);

        // اختصاص مجوزها به نقش‌ها
        $adminRole->syncPermissions(Permission::all());

        $editorRole->syncPermissions([
            'create-post',
            'edit-post'
        ]);

        $userRole->syncPermissions([]);

        // اختصاص نقش به کاربر ادمین اولیه
        $admin = User::where('email', 'admin@example.com')->first();
        if (!$admin) {
            $admin = User::create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
            ]);
        }
        $admin->assignRole($adminRole);

        // اختصاص نقش به یک کاربر عادی
        $user = User::where('email', 'user@example.com')->first();
        if (!$user) {
            $user = User::create([
                'name' => 'User',
                'email' => 'user@example.com',
                'password' => bcrypt('password'),
            ]);
        }
        $user->assignRole($userRole);
    }
}
