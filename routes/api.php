<?php

use App\Http\Controllers\Admin\CartController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductsController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\JWTOptional;
use App\Models\Admin\Group;
use App\Models\Admin\Product;
use App\Models\Admin\Offer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);
Route::post('verify-otp', [AuthController::class, 'verifyOtp']);

// Route::resource('product',ProductsController::class);
Route::get('product', [ProductsController::class, 'index'])->middleware(JWTOptional::class);
Route::get('product/{product:name}',[ProductsController::class,'show'])->middleware(JWTOptional::class);


Route::get('home', [HomeController::class, 'index']);


Route::middleware('auth:api')->group(function () {
    Route::get('getUser', [AuthController::class, 'getUser']);
    Route::get('cart', [CartController::class, 'index']);
    Route::post('cart/update', [CartController::class, 'update']);
    Route::post('cart/remove', [CartController::class, 'remove']);

    Route::post('order/add-from-cart', [OrderController::class, 'addFromCart']);
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/all', [OrderController::class, 'all'])->middleware(CheckRole::class.':expert-sale');

    Route::get('order/details/{order}', [OrderController::class, 'show']);
    Route::get('order/sale-details/{order}', [OrderController::class, 'saleShow'])->middleware(CheckRole::class . ':expert-sale');
    Route::post('order/product-delete', [OrderController::class, 'saleProductDelete'])->middleware(CheckRole::class . ':expert-sale');
    Route::match(['get','post'],'order/product-edit', [OrderController::class, 'saleProductEdit'])->middleware(CheckRole::class . ':expert-sale');
    Route::post('order/delete', [OrderController::class, 'delete'])->middleware(CheckRole::class . ':expert-sale');
    Route::post('order/add-product', [OrderController::class, 'addProduct'])->middleware(CheckRole::class . ':expert-sale');
    Route::post('order/upload-checkes', [OrderController::class,'uploadCheckes']);




});

Route::get('roles',function(){
    // Role::create([
    //     'name' => 'orders admin',
        
    // ]);

    // Role::create([
    //     'name' => 'finance admin',
        
    // ]);

    // Role::create([
    //     'name' => 'warehouse admin',
       
    // ]);

    $user = User::findOrFail(36);
    $user->assignRole('orders admin', 'finance admin','warehouse admin');
});


Route::get('test',function(){
    // $now = strtotime('2025-11-16');
    // $offer = Offer::find(6);
    
    // dd(strtotime($offer->end_time));
    // $product = Product::findOrFail(6);
    // $threeDaysLater = $now + (3 * 24 * 60 * 60); // 3 روز * 24 ساعت * 60 دقیقه * 60 ثانیه

    // // تبدیل به میلی‌ثانیه
    // $milliseconds = $threeDaysLater * 1000;
    // // $product->tags = ['سامسونگ', 'ال‌جی'];
    // // $product->save();
    // return $milliseconds;

    $user = User::findOrFail(17);
    dd($user->category->checkRules);
    
    
});





























Route::get('get-footer',function(){
    return response()->json([
        "data" => [
            "features" => [
                "title" => "برخی  از ویژگی های ما",
                "items" => [
                  [
        'title' => 'ارسال رایگان کالا',
        'subtitle' => 'برای بالای 2 میلیون',
        'icon' => '<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><g id="Delivery_Truck"><g><path d="M21.47,11.185l-.51-1.52a2.5,2.5,0,0,0-2.03-1.05H14.03V6.565a2.5,2.5,0,0,0-2.5-2.5H4.56a2.507,2.507,0,0,0-2.5,2.5v9.94a1.5,1.5,0,0,0,1.5,1.5H4.78a2.242,2.242,0,0,0,4.44,0h5.56a2.242,2.242,0,0,0,4.44,0h1.22a1.5,1.5,0,0,0,1.5-1.5v-3.87A2.508,2.508,0,0,0,21.47,11.185ZM7,18.935a1.25,1.25,0,1,1,1.25-1.25A1.25,1.25,0,0,1,7,18.935Zm6.03-1.93H9.15a2.257,2.257,0,0,0-4.3,0H3.56a.5.5,0,0,1-.5-.5V6.565a1.5,1.5,0,0,1,1.5-1.5h6.97a1.5,1.5,0,0,1,1.5,1.5ZM17,18.935a1.25,1.25,0,1,1,1.25-1.25A1.25,1.25,0,0,1,17,18.935Zm3.94-2.43a.5.5,0,0,1-.5.5H19.15a2.257,2.257,0,0,0-4.3,0h-.82v-7.3h4.38a1.516,1.516,0,0,1,1.22.63l1.03,1.43a1.527,1.527,0,0,1,.28.87Z"></path><path d="M18.029,12.205h-2a.5.5,0,0,1,0-1h2a.5.5,0,0,1,0,1Z"></path></g></g></svg>',
    ],
    [
        'title' => 'بازگشت مبلغ پرداختی',
        'subtitle' => 'برای اولین سفارش',
        'icon' => '<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 256 256" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M242.12,63.39a4,4,0,0,0-3.88-.2c-44.37,21.68-75.77,11.64-109,1s-67.71-21.67-115,1.42A4,4,0,0,0,12,69.21v120a4,4,0,0,0,5.76,3.6c44.37-21.68,75.77-11.64,109-1,18.86,6,38.08,12.19,59.8,12.19,16.61,0,34.69-3.6,55.18-13.61a4,4,0,0,0,2.24-3.6v-120A4,4,0,0,0,242.12,63.39ZM236,184.27c-43.19,20.27-74.1,10.38-106.78-.08-18.86-6-38.08-12.18-59.8-12.18-15,0-31.28,3-49.42,10.94V71.73c43.19-20.27,74.1-10.38,106.78.08C158.7,82,191.67,92.57,236,73.05ZM128,100a28,28,0,1,0,28,28A28,28,0,0,0,128,100Zm0,48a20,20,0,1,1,20-20A20,20,0,0,1,128,148ZM52,96v48a4,4,0,0,1-8,0V96a4,4,0,0,1,8,0Zm152,64V112a4,4,0,0,1,8,0v48a4,4,0,0,1-8,0Z"></path></svg>',
    ],
    [
        'title' => 'حریم خصوصی',
        'subtitle' => 'عدم ذخیره اطلاعات',
        'icon' => '<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><g id="Umbrella"><path d="M12.5,4.06v-.5a.509.509,0,0,0-.15-.35.483.483,0,0,0-.7,0,.491.491,0,0,0-.15.35v.5a8.41,8.41,0,0,0-7.88,7.82.976.976,0,0,0,.27.74,1.029,1.029,0,0,0,.74.32H11.5v5.22a1.653,1.653,0,0,1-.62,1.54A1.528,1.528,0,0,1,8.5,18.54a.5.5,0,0,0-1,0,2.433,2.433,0,0,0,2.43,2.4,2.45,2.45,0,0,0,2.57-2.29c.08-1.39,0-2.81,0-4.2V12.94h6.87a1.029,1.029,0,0,0,.74-.32.976.976,0,0,0,.27-.74A8.41,8.41,0,0,0,12.5,4.06Zm6.87,7.88-14.75.01a7.4,7.4,0,0,1,14.76-.02C19.38,11.94,19.38,11.94,19.37,11.94Z"></path></g></svg>',
    ],
    [
        'title' => 'تضمین کیفیت',
        'subtitle' => 'ضمانت برگشت کالا',
        'icon' => '<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><g id="Beaker_1"><path d="M19.447,18.645l-.51-1.52a17.9,17.9,0,0,0-4.02-6.66,1.493,1.493,0,0,1-.42-1.04V3.065H15a.5.5,0,0,0,0-1H9a.5.5,0,0,0,0,1h.5v6.36a1.484,1.484,0,0,1-.41,1.04,17.9,17.9,0,0,0-4.02,6.66l-.52,1.52a2.5,2.5,0,0,0,2.37,3.29h10.16a2.5,2.5,0,0,0,2.37-3.29Zm-9.64-7.49a2.477,2.477,0,0,0,.69-1.73V3.065h3v6.36a2.486,2.486,0,0,0,.7,1.73,16.907,16.907,0,0,1,3.01,4.38H6.787A16.943,16.943,0,0,1,9.807,11.155Zm8.49,9.16a1.507,1.507,0,0,1-1.22.62H6.917a1.5,1.5,0,0,1-1.42-1.98l.51-1.52q.15-.45.33-.9h11.32q.18.45.33.9l.51,1.52A1.5,1.5,0,0,1,18.3,20.315Z"></path></g></svg>',
    ],
                ]
            ],
            "description" => [
                "image" => "https://webeto.co/css/NewUser/img/webeto_logo.webp",  // placeholder – مسیر واقعی تصویر رو بذار، مثل '/images/desc.jpg'
                "content" => "لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است"
            ],
            
            'links' => [
                [
                    'title' => 'دسته بندی ها',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M5 10.2V5.8a.8.8 0 0 1 .8-.8h4.4a.8.8 0 0 1 .8.8v4.4a.8.8 0 0 1-.8.8H5.8a.8.8 0 0 1-.8-.8m8 0V5.8a.8.8 0 0 1 .8-.8h4.4a.8.8 0 0 1 .8.8v4.4a.8.8 0 0 1-.8.8h-4.4a.8.8 0 0 1-.8-.8m0 8v-4.4a.8.8 0 0 1 .8-.8h4.4a.8.8 0 0 1 .8.8v4.4a.8.8 0 0 1-.8.8h-4.4a.8.8 0 0 1-.8-.8m-8 0v-4.4a.8.8 0 0 1 .8-.8h4.4a.8.8 0 0 1 .8.8v4.4a.8.8 0 0 1-.8.8H5.8a.8 0 0 1-.8-.8"/></svg>',
                    'links' => [
                        [
                            'title' => 'سبد خرید',
                            'url' => '/cart',
                        ],
                        [
                            'title' => 'پروفایل',
                            'url' => '/profile',
                        ],
                    ],
                ],
            ],

            'links_2' => [
                [
                    'title' => 'منوی کاربردی',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M5 10.2V5.8a.8.8 0 0 1 .8-.8h4.4a.8.8 0 0 1 .8.8v4.4a.8.8 0 0 1-.8.8H5.8a.8.8 0 0 1-.8-.8m8 0V5.8a.8.8 0 0 1 .8-.8h4.4a.8.8 0 0 1 .8.8v4.4a.8.8 0 0 1-.8.8h-4.4a.8.8 0 0 1-.8-.8m0 8v-4.4a.8.8 0 0 1 .8-.8h4.4a.8.8 0 0 1 .8.8v4.4a.8.8 0 0 1-.8.8h-4.4a.8.8 0 0 1-.8-.8m-8 0v-4.4a.8.8 0 0 1 .8-.8h4.4a.8.8 0 0 1 .8.8v4.4a.8.8 0 0 1-.8.8H5.8a.8 0 0 1-.8-.8"/></svg>',
                    'links' => [
                        [
                            'title' => 'درباره ما',
                            'url' => '/about-us',
                        ],
                        [
                            'title' => 'قوانین و مقررات',
                            'url' => '/privacy-policy',
                        ],
                    ],
                ],
            ],

            'links_3' => [
                [
                    'title' => 'سایر لینک ها',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M5 10.2V5.8a.8.8 0 0 1 .8-.8h4.4a.8.8 0 0 1 .8.8v4.4a.8.8 0 0 1-.8.8H5.8a.8.8 0 0 1-.8-.8m8 0V5.8a.8.8 0 0 1 .8-.8h4.4a.8.8 0 0 1 .8.8v4.4a.8.8 0 0 1-.8.8h-4.4a.8.8 0 0 1-.8-.8m0 8v-4.4a.8.8 0 0 1 .8-.8h4.4a.8.8 0 0 1 .8.8v4.4a.8.8 0 0 1-.8.8h-4.4a.8.8 0 0 1-.8-.8m-8 0v-4.4a.8.8 0 0 1 .8-.8h4.4a.8.8 0 0 1 .8.8v4.4a.8.8 0 0 1-.8.8H5.8a.8 0 0 1-.8-.8"/></svg>',
                    'links' => [
                        [
                            'title' => 'درباره ما',
                            'url' => '/about-us',
                        ],
                        [
                            'title' => 'سوالات متداول',
                            'url' => '/faq',
                        ],
                    ],
                ],
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
            "long_description" => "لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است و برای شرایط فعلی تکنولوژی مورد نیاز و کاربردهای متنوع با هدف بهبود ابزارهای کاربردی می باشد", 
            "address" => " مشهد، سناباد، خیایان سنایی، پلاک 310", 
            "socialMedias" => [
                [
                    'icon' => '<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M336 96c21.2 0 41.3 8.4 56.5 23.5S416 154.8 416 176v160c0 21.2-8.4 41.3-23.5 56.5S357.2 416 336 416H176c-21.2 0-41.3-8.4-56.5-23.5S96 357.2 96 336V176c0-21.2 8.4-41.3 23.5-56.5S154.8 96 176 96h160m0-32H176c-61.6 0-112 50.4-112 112v160c0 61.6 50.4 112 112 112h160c61.6 0 112-50.4 112-112V176c0-61.6-50.4-112-112-112z"></path><path d="M360 176c-13.3 0-24-10.7-24-24s10.7-24 24-24c13.2 0 24 10.7 24 24s-10.8 24-24 24zM256 192c35.3 0 64 28.7 64 64s-28.7 64-64 64-64-28.7-64-64 28.7-64 64-64m0-32c-53 0-96 43-96 96s43 96 96 96 96-43 96-96-43-96-96-96z"></path></svg>',
                    'link' => 'https://instagram.com/amir',
                ],
                [
                    'icon' => '<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 448 512" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M446.7 98.6l-67.6 318.8c-5.1 22.5-18.4 28.1-37.3 17.5l-103-75.9-49.7 47.8c-5.5 5.5-10.1 10.1-20.7 10.1l7.4-104.9 190.9-172.5c8.3-7.4-1.8-11.5-12.9-4.1L117.8 284 16.2 252.2c-22.1-6.9-22.5-22.1 4.6-32.7L418.2 66.4c18.4-6.9 34.5 4.1 28.5 32.2z"></path></svg>',
                    'link' => 'https://telegram.com/amir',
                ],
                [
                    'icon' => '<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 448 512" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"></path></svg>',
                    'link' => 'https://whatsapp.com/amir',
                ],
                [
                    'icon' => '<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"></path></svg>',
                    'link' => 'https://x.com/amir',
                ],
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
    $categories = Group::all();
    $array = [];
    foreach ($categories as $category){
        $arr = [
            'id' => $category->id,
            'faName' => $category->name,
            'path' => $category->name,

        ];
        array_push($array, $arr);
    }
   
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
                    'id' => 1,
                    'faName' => 'خانه',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M80 212v236a16 16 0 0 0 16 16h96V328a24 24 0 0 1 24-24h80a24 24 0 0 1 24 24v136h96a16 16 0 0 0 16-16V212"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M480 256L266.89 52c-5-5.28-16.69-5.34-21.78 0L32 256m368-77V64h-48v69"/></svg>',
                    'path' => '/',
                ],
                
                [
                    'id' => 2,
                    'faName' => 'لیست محصولات',
                    'path' => '/product/list',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024"><path fill="currentColor" fill-rule="evenodd" d="M464 144c8.837 0 16 7.163 16 16v304c0 8.836-7.163 16-16 16H160c-8.837 0-16-7.164-16-16V160c0-8.837 7.163-16 16-16zm-52 68H212v200h200zm493.333 87.686c6.248 6.248 6.248 16.379 0 22.627l-181.02 181.02c-6.248 6.248-16.378 6.248-22.627 0l-181.019-181.02c-6.248-6.248-6.248-16.379 0-22.627l181.02-181.02c6.248-6.248 16.378-6.248 22.627 0zm-84.853 11.313L713 203.52L605.52 311L713 418.48zM464 544c8.837 0 16 7.164 16 16v304c0 8.837-7.163 16-16 16H160c-8.837 0-16-7.163-16-16V560c0-8.836 7.163-16 16-16zm-52 68H212v200h200zm452-68c8.837 0 16 7.164 16 16v304c0 8.837-7.163 16-16 16H560c-8.837 0-16-7.163-16-16V560c0-8.836 7.163-16 16-16zm-52 68H612v200h200z"/></svg>',
                    'children' => [
                        [
                            
                            $array,
                            
                            'id' => 1,
                            'faName' => 'خودروها',
                            'path' => 'cars',
                            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 28"><path fill="currentColor" d="M8.25 16.5a1.25 1.25 0 1 0 0-2.5a1.25 1.25 0 0 0 0 2.5M21 15.25a1.25 1.25 0 1 1-2.5 0a1.25 1.25 0 0 1 2.5 0m-9.25 1.25a.75.75 0 0 0 0 1.5h4.5a.75.75 0 0 0 0-1.5zm-6.523-7l-.28 1.119A2.75 2.75 0 0 0 3 13.25v9A2.75 2.75 0 0 0 5.75 25h1a2.75 2.75 0 0 0 2.75-2.75v-1h9v1A2.75 2.75 0 0 0 21.25 25h1A2.75 2.75 0 0 0 25 22.25v-9a2.75 2.75 0 0 0-1.947-2.63l-.28-1.12h.977a.75.75 0 0 0 0-1.5h-1.352l-.602-2.41a3.75 3.75 0 0 0-3.638-2.84H9.842a3.75 3.75 0 0 0-3.638 2.84L5.602 8H4.25a.75.75 0 0 0 0 1.5zm4.615-5.25h8.316a2.25 2.25 0 0 1 2.183 1.704l1.136 4.546H6.523L7.66 5.954A2.25 2.25 0 0 1 9.842 4.25M20 22.25v-1h3.5v1c0 .69-.56 1.25-1.25 1.25h-1c-.69 0-1.25-.56-1.25-1.25m-12-1v1c0 .69-.56 1.25-1.25 1.25h-1c-.69 0-1.25-.56-1.25-1.25v-1zM5.75 12h16.5c.69 0 1.25.56 1.25 1.25v6.5h-19v-6.5c0-.69.56-1.25 1.25-1.25"/></svg>',
                            'children' => [
                                [
                                    'id' => 1,
                                    'faName' => 'سمند',
                                    'path' => 'samand',
                                ],
                                [
                                    'id' => 2,
                                    'faName' => 'پیکان',
                                    'path' => 'peykan',
                                ],
                                [
                                    'id' => 3,
                                    'faName' => 'مگان',
                                    'path' => 'megan',
                                ],
                                [
                                    'id' => 4,
                                    'faName' => 'آردی',
                                    'path' => 'rd',
                                ],
                                [
                                    'id' => 5,
                                    'faName' => 'ساندرو',
                                    'path' => 'sandro',
                                ],
                                [
                                    'id' => 6,
                                    'faName' => 'تیبا',
                                    'path' => 'tiba',
                                ],
                            ],
                        ],
                        [
                            'id' => 2,
                            'faName' => 'برندها',
                            'path' => 'brands',
                            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M21 12a9 9 0 1 1-18 0a9 9 0 0 1 18 0"/><path d="m9 15l1.5-4.5L15 9l-1.5 4.5z"/></g></svg>',
                            'children' => [
                                [
                                    'id' => 1,
                                    'faName' => 'بوش',
                                    'path' => 'bosch',
                                ],
                                [
                                    'id' => 2,
                                    'faName' => 'اس ان آر',
                                    'path' => 'snr',
                                ],
                                [
                                    'id' => 3,
                                    'faName' => 'اورجینال',
                                    'path' => 'orginal',
                                ],
                                [
                                    'id' => 4,
                                    'faName' => 'قایم',
                                    'path' => 'qaem',
                                ],
                                [
                                    'id' => 5,
                                    'faName' => 'شوبرت هلند',
                                    'path' => 'schubert',
                                ],
                                [
                                    'id' => 6,
                                    'faName' => 'ایساکو',
                                    'path' => 'isaco',
                                ],
                            ],
                        ],
                        [
                            'id' => 3,
                            'faName' => 'بخش ها',
                            'path' => 'sections',
                            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="4"><path d="M31 31h9a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H19a2 2 0 0 0-2 2v9"/><path d="M17 17H8a2 2 0 0 0-2 2v21a2 2 0 0 0 2 2h21a2 2 0 0 0 2-2v-9"/><rect width="14" height="14" x="17" y="17" rx="2"/></g></svg>',
                            'children' => [
                                [
                                    'id' => 1,
                                    'faName' => 'سیستم انتقال نیرو',
                                    'path' => 'power-transmission',
                                ],
                                [
                                    'id' => 2,
                                    'faName' => 'بلبرینگ',
                                    'path' => 'bearing',
                                ],
                                [
                                    'id' => 3,
                                    'faName' => 'سیستم تمیز کننده',
                                    'path' => 'clean-system',
                                ],
                                [
                                    'id' => 4,
                                    'faName' => 'شیلنگ، لوله، کابل',
                                    'path' => 'cable',
                                ],
                                [
                                    'id' => 5,
                                    'faName' => 'سیستم تهویه',
                                    'path' => 'ventilation-system',
                                ],
                                [
                                    'id' => 6,
                                    'faName' => 'کاسه نمد و اورینگ',
                                    'path' => 'oring',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 3,
                    'faName' => 'پرداخت نقدی',
                    'path' => '/pay',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.5"><path d="M19 20H5a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2Z"/><path fill="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M16.5 14a.5.5 0 1 1 0-1a.5.5 0 0 1 0 1"/><path d="M18 7V5.603a2 2 0 0 0-2.515-1.932l-11 2.933A2 2 0 0 0 3 8.537V9"/></g></svg>',
                ],
                [
                    'id' => 4,
                    'faName' => 'درباره ما',
                    'path' => '/about-us',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M9 12c1.93 0 3.5-1.57 3.5-3.5S10.93 5 9 5S5.5 6.57 5.5 8.5S7.07 12 9 12m0-5c.83 0 1.5.67 1.5 1.5S9.83 10 9 10s-1.5-.67-1.5-1.5S8.17 7 9 7m0 6.75c-2.34 0-7 1.17-7 3.5V18c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-.75c0-2.33-4.66-3.5-7-3.5M4.34 17c.84-.58 2.87-1.25 4.66-1.25s3.82.67 4.66 1.25zm11.7-3.19c1.16.84 1.96 1.96 1.96 3.44V19h3c.55 0 1-.45 1-1v-.75c0-2.02-3.5-3.17-5.96-3.44M15 12c1.93 0 3.5-1.57 3.5-3.5S16.93 5 15 5c-.54 0-1.04.13-1.5.35c.63.89 1 1.98 1 3.15s-.37 2.26-1 3.15c.46.22.96.35 1.5.35"/></svg>',
                ],
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



