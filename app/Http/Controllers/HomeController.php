<?php

namespace App\Http\Controllers;
use App\Http\Resources\BannerResource;
use App\Http\Resources\CategoryListResource;
use App\Http\Resources\CommentResource;
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
            ->take(2)
            ->get();

        $best_groups = Group::with('products')->orderBy('id','desc')->get();  
        $setting = Setting::find(1);
        $topBanners = Banner::where('type',1)->get();
        $banners = Banner::where('type',2)->get();
        $maxDiscount = Offer::where('start_time','<',Carbon::now())->where('end_time', '>', Carbon::now())->max('percent');
        $comments = Comment::take(5)->get();
        $products = Product::with('category', 'images', 'group', 'options', 'comments', 'colors', 'warranties', 'sizes', 'brands')->whereIn('group_id', [1, 2, 3, 4, 5])->limit(5)->get();
        $offer_products = Product::with('category', 'images', 'group', 'options', 'comments', 'colors', 'warranties', 'sizes', 'brands')
            ->whereHas('offer', function ($q) {
                $now = now();
                $q->where('start_time', '<', $now)
                    ->where('end_time', '>', $now);
            })
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();
        $data = [
            'data' => [
                'latestProducts'=>[
                      "title"=>"آخرین  های محصولات",
                      "subTitle"=>"لیست آخرین محولات جدید",
                      'products' => ProductListResource::collection($latest_products)
                ],
                'categoryList' => [
                    "title"=> "برترین دسته بندی ها",
                    "subTitle"=> "لیست دسته بندی ها",
                    'categories' => CategoryListResource::collection($best_groups)
                ],
                'metadata' => new SettingResource($setting),
                'topCarousel'=>  BannerResource::collection($topBanners),
                'banners'=> BannerResource::collection($banners),
                'offerList' => ProductListResource::collection($offer_products),
                'bestOffer' => [
                        'img'=>'https://files.epyc.ir/images/offer.jpg',
                        'title'=> 'اولین های در ایران زمین',
                        'subTitle'=> 'تخفیف ویژه برای شما',
                        'discount'=> 'تا '.$maxDiscount.' درصد تخفیف',
                        'product'=> ProductListResource::collection($offer_products),

                ],
                'testimonials' => [
                        "title"=>"دیدگاه خریداران",
                        "subTitle"=>"لیست دسته بندی ها",
                        'comments' => CommentResource::collection($comments),

                ],
                'productList' => ProductListResource::collection($products),
            ],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ];
        return response()->json($data);
    }
}
