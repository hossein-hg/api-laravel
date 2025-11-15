<?php

namespace App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCollection;
use App\Models\Admin\Product;
use DB;
use Illuminate\Support\Facades\File;  // برای path helper
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public function index(){

        // $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
        //     <g fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5">
        //         <path d="m17.674 11.408l-1.905 5.716a.6.6 0 0 1-.398.385L3.693 20.981a.6.6 0 0 1-.74-.765L6.745 8.842a.6.6 0 0 1 .34-.365l5.387-2.218a.6.6 0 0 1 .653.13l4.404 4.405a.6.6 0 0 1 .145.614M3.296 20.602l6.364-6.364" />
        //         <path fill="currentColor" d="m18.403 3.182l2.364 2.364a1.846 1.846 0 1 1-2.61 2.61l-2.365-2.364a1.846 1.846 0 0 1 2.61-2.61" />
        //         <path d="M11.781 12.116a1.5 1.5 0 1 0-2.121 2.121a1.5 1.5 0 0 0 2.121-2.121" />
        //     </g>
        // </svg>';
        // $fileName =  'icon.svg';
        // $path = 'svgs/' . $fileName;  // folder svgs
        // // Storage::disk('public')->put($path, $svgContent);  // ذخیره content
        // $publicPath = public_path('svgs/' . $fileName);
        // File::ensureDirectoryExists(public_path('svgs/'));
        // file_put_contents($publicPath, $svgContent);
        // $url = asset('svgs/' . $fileName);  // http://your-site.com/svgs/icon.svg        
        // return response()->json([
        //     'message' => 'SVG ذخیره شد',
        //     'path' => $path,
        //     'url' => asset($url),
        //     'full_content' => $svgContent  // اختیاری، برای چک
        // ]);






        $products = Product::with('tags','category','images','group','options','comments','colors','warranties','sizes','brands')  // eager load relations
            ->paginate(perPage: 2);
        // $product = Product::find(6);
        // dd($product->colors);
        return new ProductCollection($products);
    }
}
