<?php

namespace App\Http\Controllers;
use App\Http\Resources\CategoryListResource;
use App\Models\Admin\Group;
use App\Models\Admin\Product;
use Illuminate\Http\Request;
use App\Http\Resources\ProductListResource;
class HomeController extends Controller
{
    public function index(){
        $latest_products = Product::with('category', 'images', 'group', 'options', 'comments', 'colors', 'warranties', 'sizes', 'brands')
            ->orderBy('id', 'desc')
            ->take(2)
            ->get();

        $best_groups = Group::orderBy('id','desc')->get();  
        
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
                    'products' => CategoryListResource::collection($best_groups)
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
