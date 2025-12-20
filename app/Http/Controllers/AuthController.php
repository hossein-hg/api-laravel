<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Admin\Credit;
use App\Models\Admin\UserCategory;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Auth\Otp;
use Carbon\Carbon;
use App\Services\SmsService;
use Tymon\JWTAuth\Exceptions\JWTException;
class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        
        $code = rand(100000, 999999);
       
        Otp::updateOrCreate(
            ['phone' => $request->phone],
            [
                'code' => $code,
                'name' => $request->name,
                'gender' => $request->gender,
                'expires_at' => Carbon::now()->addMinutes(2),
                'errors'=> null
            ]
        );
        $sms = new SmsService();
        $sms->sendWithPattern($code, $request->phone);
        return response()->json([
            'data' => null,
            'statusCode' => 200,
            'success' => true,
            'message' => 'رمز ورود به شماره شما ارسال شد',
            'errors' => null
        ]);
    }

    public function login(LoginRequest $request)
    {
        
        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json([
                 'data' => null,
                'statusCode'=> 422,
                'success'=>false,
                'message' => 'کاربری با این شماره تلفن یافت نشد ',
                 "errors"=> [
                    "phone" => [
                        "کاربری با این شماره تلفن یافت نشد."
                    ]
                 ],
            ], 422);
        }

        $code = rand(100000, 999999);
        $sms = new SmsService();
        $sms->sendWithPattern($code, $request->phone);
        Otp::updateOrCreate(
            ['phone' => $request->phone],
            [
                'code' => $code,
                'name' => $user->name,
                'gender' => $user->gender,
                'expires_at' => Carbon::now()->addMinutes(2),
                'user_id' => $user->id,
                'errors' => null
            ]
        );
        return response()->json([
            'data' => null,
            'statusCode'=> 200,
            'success'=>true,
            'message' => 'رمز ورود به شماره شما ارسال شد',
            'errors' => null
        ]);
     
    }

    public function getUser(Request $request)
    {
        try {
            $user = auth()->user();
            // dd($user);
            $roles = $user->getRoleNames();
            // dd($roles);    
            if (!$user) {
                return response()->json([
                    'data' => null,
                    'statusCode' => 401,
                    'success' => false,
                    'message' => 'توکن نامعتبر یا منقضی شده است.',
                    'errors' => null
                ], 401);
            }

            $remaining_credit = Credit::where('user_id', $user->id)->select('remaining_amount')->latest('id')->first();
            
            

            $category = $user->category ? $user->category->name : null;
            $user->categoryName = $category ?? '1';

            $user->remaining_credit = $remaining_credit ? number_format($remaining_credit->remaining_amount) : number_format($user->category->max_credit);
            return response()->json([
                'data' => [
                    'user' => $user  
                ],
                'statusCode' => 200,
                'success' => true,
                'message' => 'اطلاعات کاربر با موفقیت دریافت شد.',
                'errors' => null
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'data' => null,
                'statusCode' => 401,
                'success' => false,
                'message' => 'خطا در پردازش توکن.',
                'errors' => null
            ], 401);
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'code' => 'required|string'
        ]);
        
        $otp = Otp::where('phone', $request->phone)
            ->first();
     
        if (!$otp) {
            // OTP پیدا نشد، نمی‌توان attempts افزایش داد
            return response()->json([
                'data' => null,
                'statusCode' => 401,
                'message' => 'شماره یافت نشد !',
                'success' => true,
                'errors'=> null,
            ], 401);
        }
        if ($otp->isExpired()) {
            $otp->delete(); // حذف OTP منقضی
            return response()->json([
                'data' => null,
                'statusCode' => 503,
                'message' => 'لطفا دوباره شماره را وارد کنید',
                'success' => true,
                'errors' => null,
            ], 503);
        }
        
        if ($otp->maxAttemptsReached()) {
            $otp->delete();
            return response()->json([
                'data' => null,
                'statusCode' => 429,
                'message' =>  'لطفا دوباره شماره را وارد کنید',
                'success' => true,
                'errors' => null
                
            ], 429);
        }
        
        if ($otp->code !== $request->code) {
            $otp->incrementAttempts();
            // $remaining = 4 - $otp->attempts;
            return response()->json([
                'data' => null,
                'statusCode' => 401,
                'message' => 'رمز اشتباه است!',
                'success' => false,
                'errors' => null
            ], 401);
        }
        

        if ($otp->user_id){
            $user = User::find($otp->user_id);
        }
        else{
            $userCategory = UserCategory::where('name',"1")->first();
            $user = User::create([
                'name' => $otp->name,
                'phone' => $otp->phone,
                'gender' => $otp->gender,
                'category_id' => $userCategory ? $userCategory->id : "1",
            ]);
            $user->assignRole('user');

           
        }



       
      
        

       
       $category = $user->category ? $user->category->name : null;
       $user->categoryName = $category ?? 'یک'; 
        $otp->delete(); // حذف OTP پس از استفاده

        $token = JWTAuth::fromUser($user);

        

        return response()->json([
            'data'=>[
                'user' => $user,
                'token' => $token

            ],
            'statusCode'=> 200,
            'message'=> 'موفقیت آمیز',
            'success'=>true,
            'errors' => null
        ]);
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json([
            'data' => null,
            'statusCode' => 200,
            'message' => 'از حساب خارج شدید ',
            'success' => true,
            'errors' => null
        ]);
    }

   
}
