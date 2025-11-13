<?php

use App\Http\Controllers\Admin\ProductsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('verify-otp', [AuthController::class, 'verifyOtp']);

Route::resource('product',ProductsController::class);



























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
                    "icon" => '<svg xmlns="http://www.w3.org/2000/svg" width={24} height={24} viewBox="0 0 24 24">
        <g
          fill="none"
          stroke="currentColor"
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth="1.5"
        >
          <path d="m17.674 11.408l-1.905 5.716a.6.6 0 0 1-.398.385L3.693 20.981a.6.6 0 0 1-.74-.765L6.745 8.842a.6.6 0 0 1 .34-.365l5.387-2.218a.6.6 0 0 1 .653.13l4.404 4.405a.6.6 0 0 1 .145.614M3.296 20.602l6.364-6.364" />
          <path
            fill="currentColor"
            d="m18.403 3.182l2.364 2.364a1.846 1.846 0 1 1-2.61 2.61l-2.365-2.364a1.846 1.846 0 0 1 2.61-2.61"
          />
          <path d="M11.781 12.116a1.5 1.5 0 1 0-2.121 2.121a1.5 1.5 0 0 0 2.121-2.121" />
        </g>
      </svg>',
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
