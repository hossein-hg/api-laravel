<?php

namespace App\Http\Controllers;
use App\Http\Resources\BannerResource;
use App\Http\Resources\CategoryListResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\HomeProductResource;
use App\Http\Resources\SettingResource;
use App\Models\Admin\Banner;
use App\Models\Admin\Comment;
use App\Models\Admin\Group;
use App\Models\Admin\Offer;
use App\Models\Admin\Product;
use App\Models\Admin\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Resources\ProductListResource;
class HomeController extends Controller
{
    public function index(){
        $latest_products = Product::with('category', 'images', 'group', 'options', 'comments', 'colors', 'warranties', 'sizes', 'brands')
            ->orderBy('id', 'desc')
            ->take(6)
            ->get();

        $best_products = Product::with('category', 'images', 'group', 'options', 'comments', 'colors', 'warranties', 'sizes', 'brands')
            ->orderBy('id', 'desc')
            ->take(6)
            ->get();    
        
        $best_groups = Group::with('products')->where('level',2)->take(6)->orderBy('id','desc')->get();  
        $setting = Setting::find(1);
        $topBanners = Banner::where('type',1)->get();
        $banners = Banner::where('type',2)->get();
        $maxDiscount = Offer::where('start_time','<',Carbon::now())->where('end_time', '>', Carbon::now())->max('percent');
        $comments = Comment::with('user')->take(5)->get();
        $products = Product::with('category', 'images', 'group', 'options', 'comments', 'colors', 'warranties', 'sizes', 'brands')->whereIn('group_id', [1, 2, 3, 4, 5])->limit(5)->get();
        $offer_products = Product::with('category', 'images', 'group', 'options', 'comments', 'colors', 'warranties', 'sizes', 'brands')
            ->whereHas('offer', function ($q) {
                $now = now();
                $q->where('start_time', '<', $now)
                    ->where('end_time', '>', $now);
            })
            ->orderBy('id', 'desc')
            ->where('inventory',1)
            ->take(5)
            ->get();
        $data = [
            'data' => [
                'latestProducts'=>[
                      "title"=>"آخرین  های محصولات",
                      'image' => 'https://files.epyc.ir/images/latest.jpg',
                      "subTitle"=>"لیست آخرین محصولات جدید",
                      'description'=> 'فرهنگ پیشرو در زبان فارسی ایجاد کرد، در این صورت می توان امید داشت که تمام و دشواری م',
                      'products' => HomeProductResource::collection($latest_products)
                ],
                'popularProducts' => [
                    "title" => "محبوب ترین محصولات",
                    "subTitle" => "لیست محبوب ترین محصولات جدید",
                    'products' => HomeProductResource::collection($latest_products)
                ],
                'top_rated_products' => [
                    "title" => "برترین محصولات",
                    "subTitle" => "لیست آخرین محصولات جدید",
                    'image' => 'https://files.epyc.ir/images/latest.jpg',
                    'description' => 'فرهنگ پیشرو در زبان فارسی ایجاد کرد، در این صورت می توان امید داشت که تمام و دشواری م',
                    'products' => HomeProductResource::collection($latest_products)
                ],
                'categoryList' => [
                    "title"=> "برترین دسته بندی ها",
                    "subTitle"=> "لیست دسته بندی ها",
                    'categories' => CategoryListResource::collection($best_groups)
                ],
                'metadata' => new SettingResource($setting),
                'topCarousel'=>  BannerResource::collection($topBanners),
                'banners'=> BannerResource::collection($banners),
                'offerList' => HomeProductResource::collection($offer_products),
                'bestOffer' => [
                        'img'=>'https://files.epyc.ir/images/offer.jpg',
                        'title'=> 'اولین های در ایران زمین',
                        'subTitle'=> 'تخفیف ویژه برای شما',
                        'discount'=> 'تا '.$maxDiscount.' درصد تخفیف',
                        'products'=> HomeProductResource::collection($products),

                ],
                'bestOfferProducts' => [
                    
                    'title' => 'آخرین محصولات تخفیف دار',
                    'subTitle' => 'لیست آخرین محصولات جدید',
                    'products' => HomeProductResource::collection($products),

                ],
                'testimonials' => [
                        "title"=>"دیدگاه خریداران",
                        "subTitle"=>"این المان برای نمایش دیدگاه خریداران یا کاربران معمولی سایت است و شما میتوانید هرچیزی درون ان بنویسید و استفاده کنید . این المان اختصاصی قالب فروشگاهی مهرنوش است و به هیچ درون ان بنویسید و استفاده کنید . این المان اختصاصی قالب فروشگاهی مهرنوش است و به هیچ",
                        'comments' => CommentResource::collection($comments),

                ],
                'productList' => [
                     "title"=>"انواع محصولات",
                    "subTitle"=>"از هر نوع برند",
                    "description"=>"فرهنگ پیشرو در زبان فارسی ایجاد کرد، در این صورت می توان امید داشت که تمام و دشواری موج",
                    'products'=> HomeProductResource::collection($products),
                ],

                'bestProduct' => [
                    "title" => "بهترین محصولات ما",
                    "subTitle" => "فقط امروز تخفیف داریم",
                     "discount_description"=> "تا ۹۰ درصد تفیف ویژه",
                     'image'=> 'https://files.epyc.ir/images/best.png',
                    'products' => HomeProductResource::collection($best_products),
                ],
                
                
            ],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ];
        return response()->json($data);
    }
}
