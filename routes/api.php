<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
Route::get('get-footer',function(){
    return response()->json([
        "data" => [
            "features" => [
                "title" => "برخی  از ویژگی های ما",
                "features" => [
                    [
                        "title" => "ارسال رایگان",
                        "subtitle" => "برای  بالای ۲ میلیون",
                        "icon" => "svg tag"
                    ]
                    // می‌تونی features بیشتری اضافه کنی
                ]
            ],
            "description" => [
                "image" => "https://webeto.co/uploads/Global/Webeto317.png",  // placeholder – مسیر واقعی تصویر رو بذار، مثل '/images/desc.jpg'
                "content" => "لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است"
            ],
            "links" => [
                [
                    "title" => "دسته بندی ها",  // تصحیح "tilte" به "title"
                    "icon" => "svg tag",
                    "links" => [
                        [
                            "title" => "سبد خرید",
                            "url" => "https://webeto.com"  // placeholder – URL واقعی، مثل '/cart'
                        ]
                        // لینک‌های بیشتری اضافه کن
                    ]
                ]
                // دسته‌بندی‌های بیشتری اضافه کن
            ],
            "slogans" => [
                [
                    "title" => "عنوان شعار",  // تصحیح "tilte" به "title"
                    "icon" => "svg tag"
                ]
                // شعارهای بیشتری اضافه کن
            ],
            "badge" => "اینماد",
            "badge_2" => "ستاد ساماندهی",
            "email" => "example@gmail.com",  // placeholder – مثل 'info@example.com'
            "phone" => "0915000000",  // placeholder – مثل '+989123456789'
            "long_description" => "توضیحات",  // placeholder – متن طولانی واقعی
            "address" => "مشهد - سناباد",  // placeholder – آدرس واقعی
            "socialMedias" => [
                [
                    "icon" => "svg tag",
                    "link" => "https://instagram.com/example"  // placeholder – URL واقعی، مثل 'https://instagram.com/example'
                ]
                // رسانه‌های اجتماعی بیشتری اضافه کن
            ]
                ],
         'statusCode'=> 200,
            'message'=> 'موفقیت آمیز',
            'success'=>true,
            'errors' => null

    ]);
});
Route::get('get-header', function(){
    return response()->json([
       
        "data" => [
            "notifications" => [
                [
                    "image" => "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRm03Bmg5NO9FCDdXibk7PlmYiN_otc_uuxCQ&s",  // placeholder – مسیر واقعی تصویر، مثل '/images/notification1.jpg'
                    "summary" => "خلاصه",     // placeholder – خلاصه نوتیفیکیشن
                    "title" => "عنوان",       // placeholder – عنوان نوتیفیکیشن
                    "link" => "url string"     // placeholder – لینک واقعی، مثل '/notifications/1'
                ]
                // نوتیفیکیشن‌های بیشتری اضافه کن
            ],
            "menu" => [
                [
                    "id" => "string |number",  // placeholder – مثل 1 یا 'main-menu'
                    "faName" => "خدمات ما",
                    "icon" => "svg tag",
                    "path" => null,  // null چون children داره
                    "children" => [
                        [
                            "id" => 1,  // placeholder – مثل 1.1
                            "faName" => "خدمت ۱",
                            "icon" => "svg tag",
                            "path" => "https://webeto.com",  // null چون subchildren داره
                            "children" => [
                                [
                                    "id" => 2,  // placeholder – مثل 1.1.1
                                    "faName" => "زیر خدمت ۱",
                                    "path" => "https://webeto.com",  // لینک واقعی، مثل '/services/sub1'
                                    "icon" => "svg tag"
                                ]
                                // زیرمنوهای بیشتری اضافه کن
                            ]
                        ]
                        // children بیشتری اضافه کن
                    ]
                ]
                // منوهای بیشتری اضافه کن
            ],
            "siteData" => [
                "logo" => "https://webeto.co/uploads/Global/Webeto317.png",  // placeholder – مسیر لوگو، مثل '/images/logo.png'
                "faName" => "وبتو",
                "enName" => "WEBETO",
                "url" => "https://webeto.com"  // placeholder – URL سایت، مثل 'https://webeto.com'
            ]
            ],
        'statusCode' => 200,
        'message' => 'موفقیت آمیز',
        'success' => true,
        'errors' => null
    ]);
});
Route::middleware('auth:api')->get('getUser', [AuthController::class, 'getUser']);
