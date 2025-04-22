<?php
namespace App\Http\Controllers\api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Info(
 *     title="سیستم احراز هویت API",
 *     version="1.0.0",
 *     description="نقاط پایانی API برای مدیریت و احراز هویت کاربران",
 *     @OA\Contact(
 *         email="admin@example.com",
 *         name="پشتیبانی API"
 *     ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="سرور API"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class ApiController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="ثبت نام کاربر جدید",
     *     description="ایجاد حساب کاربری جدید با اطلاعات ارائه شده",
     *     operationId="registerUser",
     *     tags={"احراز هویت"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="اطلاعات ثبت نام کاربر",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="محمد حسن"),
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="Password123"),
     *             @OA\Property(property="role", type="string", example="user", enum={"admin", "editor", "user"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="کاربر با موفقیت ثبت نام شد",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="کاربر با موفقیت ثبت نام شد"),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *             @OA\Property(property="type", type="string", example="user"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="خطای اعتبارسنجی",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطا در اعتبارسنجی"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="خطای سرور",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطا در ثبت نام کاربر"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function register(Request $request){
        try {
            // اعتبارسنجی با Validator
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'role' => 'sometimes|string|in:admin,editor,user'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'خطا در اعتبارسنجی',
                    'errors' => $validator->errors()
                ], 422);
            }

            // ایجاد کاربر جدید
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // اختصاص نقش به کاربر
            $roleName = $request->role ?? 'user';

            // بررسی و ایجاد نقش‌ها در صورت نیاز
            $this->ensureRolesExist();

            // جستجوی نقش
            $role = Role::where('name', $roleName)->first();

            if (!$role) {
                // اگر نقش درخواستی وجود نداشت، از نقش 'user' استفاده می‌کنیم
                $role = Role::where('name', 'user')->first();

                // اگر هنوز نقش یافت نشد، یک خطای واضح برگردان
                if (!$role) {
                    return response()->json([
                        'status' => false,
                        'message' => 'خطا در ثبت نام کاربر',
                        'error' => 'نقش‌های پیش‌فرض در سیستم تعریف نشده‌اند. لطفاً با مدیر سیستم تماس بگیرید.'
                    ], 500);
                }
            }

            $user->assignRole($role);

            // ایجاد توکن
            $token = $user->createToken("myToken")->accessToken;

            // اطلاعات بیشتر کاربر (با نقش‌ها)
            $userWithRoles = User::with('roles')->find($user->id);

            // تعیین نوع کاربر بر اساس نقش
            $type = $this->getUserType($userWithRoles);

            // ارسال پاسخ
            return response()->json([
                'status' => true,
                'message' => 'کاربر با موفقیت ثبت نام شد',
                'token' => $token,
                'type' => $type,
                'user' => $userWithRoles
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در ثبت نام کاربر',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * اطمینان از وجود نقش‌های پایه در سیستم
     * این متد نقش‌های اصلی را در صورت عدم وجود ایجاد می‌کند
     */
    private function ensureRolesExist()
    {
        // بررسی و ایجاد نقش admin
        if (!Role::where('name', 'admin')->exists()) {
            Role::create([
                'name' => 'admin',
                'display_name' => 'مدیر',
                'description' => 'دسترسی کامل به همه بخش‌ها'
            ]);
        }

        // بررسی و ایجاد نقش editor
        if (!Role::where('name', 'editor')->exists()) {
            Role::create([
                'name' => 'editor',
                'display_name' => 'ویرایشگر',
                'description' => 'دسترسی به ویرایش محتوا'
            ]);
        }

        // بررسی و ایجاد نقش user
        if (!Role::where('name', 'user')->exists()) {
            Role::create([
                'name' => 'user',
                'display_name' => 'کاربر عادی',
                'description' => 'دسترسی محدود به سیستم'
            ]);
        }

        // ایجاد مجوزهای اصلی اگر وجود ندارند
        $this->ensureBasicPermissionsExist();

        // اختصاص مجوزها به نقش admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $permissionNames = [
                'create-post', 'edit-post', 'delete-post',
                'create-user', 'edit-user', 'delete-user'
            ];

            foreach ($permissionNames as $permName) {
                $permission = Permission::where('name', $permName)->first();
                if ($permission) {
                    // استفاده از رابطه مستقیم به جای متد syncPermissions
                    if (!$adminRole->permissions()->where('permissions.id', $permission->id)->exists()) {
                        $adminRole->permissions()->attach($permission->id);
                    }
                }
            }
        }
    }

    /**
     * ایجاد مجوزهای اصلی سیستم در صورت عدم وجود
     */
    private function ensureBasicPermissionsExist()
    {
        $basicPermissions = [
            ['name' => 'create-post', 'display_name' => 'ایجاد پست', 'description' => 'امکان ایجاد پست جدید'],
            ['name' => 'edit-post', 'display_name' => 'ویرایش پست', 'description' => 'امکان ویرایش پست'],
            ['name' => 'delete-post', 'display_name' => 'حذف پست', 'description' => 'امکان حذف پست'],
            ['name' => 'create-user', 'display_name' => 'ایجاد کاربر', 'description' => 'امکان ایجاد کاربر جدید'],
            ['name' => 'edit-user', 'display_name' => 'ویرایش کاربر', 'description' => 'امکان ویرایش کاربر'],
            ['name' => 'delete-user', 'display_name' => 'حذف کاربر', 'description' => 'امکان حذف کاربر'],
        ];

        foreach ($basicPermissions as $permission) {
            if (!Permission::where('name', $permission['name'])->exists()) {
                Permission::create($permission);
            }
        }
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="ورود کاربر",
     *     description="احراز هویت کاربر و ارائه توکن دسترسی",
     *     operationId="loginUser",
     *     tags={"احراز هویت"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="اطلاعات ورود کاربر",
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ورود موفقیت‌آمیز",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="کاربر با موفقیت وارد شد"),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *             @OA\Property(property="type", type="string", example="user"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="ورود ناموفق",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="ایمیل یا رمز عبور نامعتبر است")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="خطای اعتبارسنجی",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطا در اعتبارسنجی"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="خطای سرور",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطا در ورود به سیستم"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function login(Request $request){
        try {
            // اعتبارسنجی درخواست
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'خطا در اعتبارسنجی',
                    'errors' => $validator->errors()
                ], 422);
            }

            // استفاده از Auth::attempt
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                $user = Auth::user();

                // بارگذاری روابط نقش‌ها
                $userWithRoles = User::with(['roles.permissions'])->find($user->id);

                $token = $user->createToken("myToken")->accessToken;
                $type = $this->getUserType($userWithRoles);

                return response()->json([
                    "status" => true,
                    "message" => "کاربر با موفقیت وارد شد",
                    "token" => $token,
                    "type" => $type,
                    "user" => $userWithRoles
                ]);
            }

            // اگر Auth::attempt ناموفق بود
            return response()->json([
                "status" => false,
                "message" => "ایمیل یا رمز عبور نامعتبر است"
            ], 401);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در ورود به سیستم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="خروج کاربر",
     *     description="لغو توکن دسترسی کاربر",
     *     operationId="logoutUser",
     *     tags={"احراز هویت"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="خروج موفقیت‌آمیز",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="کاربر با موفقیت خارج شد")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="خطای احراز هویت",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="توکن معتبر نیست یا ارائه نشده است")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="خطای سرور",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطا در خروج از سیستم"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function logout(Request $request){
        try {
            $user = auth()->user();
            $user->token()->revoke();

            return response()->json([
                'status' => true,
                'message' => 'کاربر با موفقیت خارج شد'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در خروج از سیستم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/profile",
     *     summary="دریافت پروفایل کاربر",
     *     description="اطلاعات پروفایل کاربر احراز هویت شده را برمی‌گرداند",
     *     operationId="getUserProfile",
     *     tags={"کاربر"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="دریافت موفقیت‌آمیز پروفایل",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="پروفایل کاربر با موفقیت دریافت شد"),
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="type", type="string", example="admin"),
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="خطای احراز هویت",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="توکن معتبر نیست یا ارائه نشده است")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="خطای سرور",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطا در دریافت پروفایل"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function profile(Request $request){
        try {
            // دریافت کاربر با نقش‌ها و مجوزها
            $user = User::with(['roles.permissions'])->find(auth()->id());

            // تعیین نوع کاربر بر اساس نقش
            $type = $this->getUserType($user);

            // استخراج نام نقش‌ها و مجوزها
            $roleNames = $user->roles->pluck('name')->toArray();

            $permissions = collect([]);
            foreach ($user->roles as $role) {
                $permissions = $permissions->merge($role->permissions);
            }
            $permissionNames = $permissions->unique('id')->pluck('name')->toArray();

            return response()->json([
                'status' => true,
                'message' => 'پروفایل کاربر با موفقیت دریافت شد',
                'id' => $user->id,
                'type' => $type,
                'user' => $user,
                'roles' => $roleNames,
                'permissions' => $permissionNames
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در دریافت پروفایل',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/refresh-token",
     *     summary="تازه‌سازی توکن دسترسی",
     *     description="صدور یک توکن دسترسی جدید برای کاربر احراز هویت شده",
     *     operationId="refreshToken",
     *     tags={"احراز هویت"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="تازه‌سازی موفقیت‌آمیز توکن",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="توکن با موفقیت تازه‌سازی شد"),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *             @OA\Property(property="type", type="string", example="admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="خطای احراز هویت",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="توکن معتبر نیست یا ارائه نشده است")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="خطای سرور",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطا در تازه‌سازی توکن"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function refreshToken(Request $request){
        try {
            $user = User::with('roles')->find(auth()->id());
            $token = $user->createToken('myToken')->accessToken;

            // تعیین نوع کاربر بر اساس نقش
            $type = $this->getUserType($user);

            return response()->json([
                'status' => true,
                'message' => 'توکن با موفقیت تازه‌سازی شد',
                'token' => $token,
                'type' => $type
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در تازه‌سازی توکن',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تعیین نوع کاربر بر اساس نقش‌های او
     *
     * @param User $user
     * @return string
     */
    private function getUserType($user)
    {
        if ($user->hasRole('admin')) {
            return 'admin';
        } elseif ($user->hasRole('editor')) {
            return 'editor';
        } else {
            return 'user';
        }
    }

    /**
     * @OA\Get(
     *     path="/api/permissions",
     *     summary="دریافت لیست مجوزهای کاربر",
     *     description="همه مجوزهای کاربر احراز هویت شده را برمی‌گرداند",
     *     operationId="getUserPermissions",
     *     tags={"کاربر"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="دریافت موفقیت‌آمیز مجوزها",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="مجوزهای کاربر با موفقیت دریافت شد"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="خطای احراز هویت",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="توکن معتبر نیست یا ارائه نشده است")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="خطای سرور",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطا در دریافت مجوزها"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function permissions(Request $request){
        try {
            // دریافت کاربر با نقش‌ها و مجوزها
            $user = User::with(['roles.permissions'])->find(auth()->id());

            // استخراج مجوزها
            $permissions = collect([]);
            foreach ($user->roles as $role) {
                $permissions = $permissions->merge($role->permissions);
            }
            $permissionNames = $permissions->unique('id')->pluck('name')->toArray();

            return response()->json([
                'status' => true,
                'message' => 'مجوزهای کاربر با موفقیت دریافت شد',
                'permissions' => $permissionNames
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در دریافت مجوزها',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/roles",
     *     summary="دریافت لیست نقش‌های کاربر",
     *     description="همه نقش‌های کاربر احراز هویت شده را برمی‌گرداند",
     *     operationId="getUserRoles",
     *     tags={"کاربر"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="دریافت موفقیت‌آمیز نقش‌ها",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="نقش‌های کاربر با موفقیت دریافت شد"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="خطای احراز هویت",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="توکن معتبر نیست یا ارائه نشده است")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="خطای سرور",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطا در دریافت نقش‌ها"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function roles(Request $request){
        try {
            // دریافت کاربر با نقش‌ها
            $user = User::with('roles')->find(auth()->id());

            // استخراج نقش‌ها
            $roleNames = $user->roles->pluck('name')->toArray();

            return response()->json([
                'status' => true,
                'message' => 'نقش‌های کاربر با موفقیت دریافت شد',
                'roles' => $roleNames
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در دریافت نقش‌ها',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/check-permission/{permission}",
     *     summary="بررسی دسترسی کاربر به یک مجوز",
     *     description="بررسی می‌کند که آیا کاربر دارای مجوز مشخص شده است یا خیر",
     *     operationId="checkUserPermission",
     *     tags={"کاربر"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="permission",
     *         in="path",
     *         required=true,
     *         description="نام مجوز برای بررسی",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="بررسی موفقیت‌آمیز",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="has_permission", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="کاربر دارای مجوز مورد نظر است")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="خطای احراز هویت",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="توکن معتبر نیست یا ارائه نشده است")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="خطای سرور",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطا در بررسی مجوز"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function checkPermission(Request $request, $permission){
        try {
            $user = auth()->user();
            $hasPermission = $user->hasPermission($permission);

            $message = $hasPermission
                ? 'کاربر دارای مجوز مورد نظر است'
                : 'کاربر دارای مجوز مورد نظر نیست';

            return response()->json([
                'status' => true,
                'has_permission' => $hasPermission,
                'message' => $message
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در بررسی مجوز',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
