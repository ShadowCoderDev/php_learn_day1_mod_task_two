<?php
namespace App\Http\Controllers\api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

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
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="سرور API"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     required={"name", "email", "password"},
 *     @OA\Property(property="id", type="integer", format="int64", description="شناسه کاربر"),
 *     @OA\Property(property="name", type="string", description="نام کاربر"),
 *     @OA\Property(property="email", type="string", format="email", description="ایمیل کاربر"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="تاریخ ایجاد"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="تاریخ آخرین بروزرسانی")
 * )
 *
 * @OA\Schema(
 *     schema="UserRegisterRequest",
 *     required={"name", "email", "password", "password_confirmation"},
 *     @OA\Property(property="name", type="string", example="محمد حسن", description="نام کاربر"),
 *     @OA\Property(property="email", type="string", format="email", example="user@example.com", description="ایمیل کاربر"),
 *     @OA\Property(property="password", type="string", format="password", example="Password123", description="رمز عبور کاربر"),
 *     @OA\Property(property="password_confirmation", type="string", format="password", example="Password123", description="تایید رمز عبور")
 * )
 *
 * @OA\Schema(
 *     schema="LoginRequest",
 *     required={"email", "password"},
 *     @OA\Property(property="email", type="string", format="email", example="user@example.com", description="ایمیل کاربر"),
 *     @OA\Property(property="password", type="string", format="password", example="Password123", description="رمز عبور کاربر")
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     @OA\Property(property="status", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="عملیات با موفقیت انجام شد")
 * )
 *
 * @OA\Schema(
 *     schema="TokenResponse",
 *     @OA\Property(property="status", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="عملیات با موفقیت انجام شد"),
 *     @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     @OA\Property(property="status", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="پیام خطا")
 * )
 *
 * @OA\Schema(
 *     schema="UserProfileResponse",
 *     @OA\Property(property="user", ref="#/components/schemas/User"),
 *     @OA\Property(property="message", type="string", example="پروفایل کاربر با موفقیت دریافت شد"),
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="status", type="boolean", example=true)
 * )
 *
 * @group احراز هویت کاربر
 *
 * APIs برای احراز هویت کاربر
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
     *         @OA\JsonContent(ref="#/components/schemas/UserRegisterRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="کاربر با موفقیت ثبت نام شد",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="خطای اعتبارسنجی",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="داده های ارائه شده نامعتبر است."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="این ایمیل قبلا ثبت شده است."))
     *             )
     *         )
     *     )
     * )
     */
    public function register(Request $request){
       
        // validation
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);
        // User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);
        // response
        return response()->json([
            'status' => true,
            'message' => 'کاربر با موفقیت ثبت نام شد'],
            201
        );
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
     *         @OA\JsonContent(ref="#/components/schemas/LoginRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="کاربر با موفقیت وارد شد",
     *         @OA\JsonContent(ref="#/components/schemas/TokenResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="غیرمجاز - اطلاعات نامعتبر",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function login(Request $request){
        // validation
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
        // check user by "email" value  
        $user = User::where("email", $request->email)->first();
        if (!empty($user)){
            if (Hash::check($request->password, $user->password))
            {
                $token = $user->createToken("myToken")->accessToken;
                return response()->json([
                    "status" => true,
                    "message" => "کاربر با موفقیت وارد شد",
                    "token" => $token
                ]);
            }
            else{
                return response()->json([
                    "status" => false,
                    "message" => "رمز عبور مطابقت ندارد"
                ])->setStatusCode(401);
               
            }
        }else{
            return response()->json([
                "status" => false,
                "message" => "ایمیل نامعتبر است"
            ])->setStatusCode(401);
        }
        
        if (auth()->attempt($request->only('email', 'password'))) {
            $user = auth()->user();
            $token = $user->createToken('Personal Access Token')->accessToken;
            return response()->json(['token' => $token], 200);
        }
       
        // return response
        return response()->json(['error' => 'غیرمجاز'], 401);
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
     *         description="کاربر با موفقیت خارج شد",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="احراز هویت نشده",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="احراز هویت نشده.")
     *         )
     *     )
     * )
     */
    public function logout(){
        $user = auth()->user();
        $user->token()->revoke();
        return response()->json([
            'message' => 'کاربر با موفقیت خارج شد',
            'status' => true,
        ], 200);
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
     *         description="پروفایل کاربر با موفقیت دریافت شد",
     *         @OA\JsonContent(ref="#/components/schemas/UserProfileResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="احراز هویت نشده",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="احراز هویت نشده.")
     *         )
     *     )
     * )
     */
    public function profile(){
        // get user
        $user = auth()->user();
        return response()->json([
            'user' => $user,
            'message' => 'پروفایل کاربر با موفقیت دریافت شد',
            'id' => $user->id,
            'status' => true,
        ])->setStatusCode(200);
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
     *         description="توکن با موفقیت تازه‌سازی شد",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="توکن با موفقیت تازه‌سازی شد")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="احراز هویت نشده",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="احراز هویت نشده.")
     *         )
     *     )
     * )
     */
    public function refreshToken(){
        $user = auth()->user();
        $token = $user->createToken('Personal Access Token')->accessToken;
        return response()->json([
            'token' => $token,
            'status' => true,
            'message' => 'توکن با موفقیت تازه‌سازی شد',
        ], 200);
    }
}