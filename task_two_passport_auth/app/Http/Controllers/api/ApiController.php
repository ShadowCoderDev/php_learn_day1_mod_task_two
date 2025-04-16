<?php
namespace App\Http\Controllers\api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
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
     *             @OA\Property(property="role", type="string", example="user", enum={"admin", "user"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="کاربر با موفقیت ثبت نام شد",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="کاربر با موفقیت ثبت نام شد"),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *             @OA\Property(property="type", type="string", example="type2"),
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
                'role' => 'sometimes|in:admin,user'
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
                'role' => $request->role ?? 'user',
            ]);
            
            // ایجاد توکن
            $token = $user->createToken("myToken")->accessToken;
            
            // تعیین نوع کاربر بر اساس نقش
            $type = ($user->role === 'admin') ? 'type1' : 'type2';
            
            // ارسال پاسخ
            return response()->json([
                'status' => true,
                'message' => 'کاربر با موفقیت ثبت نام شد',
                'token' => $token,
                'type' => $type,
                'user' => $user
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
     *             @OA\Property(property="type", type="string", example="type2"),
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
            
            // روش اول: استفاده از Auth::attempt
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                $user = Auth::user();
                $token = $user->createToken("myToken")->accessToken;
                $type = ($user->role === 'admin') ? 'type1' : 'type2';
                
                return response()->json([
                    "status" => true,
                    "message" => "کاربر با موفقیت وارد شد",
                    "token" => $token,
                    "type" => $type,
                    "user" => $user
                ]);
            }
            
            // اگر Auth::attempt ناموفق بود، پیام خطا را نمایش می‌دهیم
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
     *             @OA\Property(property="type", type="string", example="type2"),
     *             @OA\Property(property="user", type="object")
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
            
            
            // دریافت کاربر
            $user = auth()->user();
            
            // تعیین نوع کاربر بر اساس نقش
            $type = ($user->role === 'admin') ? 'type1' : 'type2';
            
            return response()->json([
                'status' => true,
                'message' => 'پروفایل کاربر با موفقیت دریافت شد',
                'id' => $user->id,
                'type' => $type,
                'user' => $user
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
     *             @OA\Property(property="type", type="string", example="type2")
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
            
            
            $user = auth()->user();
            $token = $user->createToken('myToken')->accessToken;
            
            // تعیین نوع کاربر بر اساس نقش
            $type = ($user->role === 'admin') ? 'type1' : 'type2';
            
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
}