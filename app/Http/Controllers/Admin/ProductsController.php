<?php

namespace App\Http\Controllers\Admin;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCollection;
use App\Models\Admin\Product;
use DB;
use Illuminate\Support\Facades\File;  // برای path helper
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public function index(Request $request){

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






        $query = Product::with('images','group','options','comments','colors','warranties','sizes','brands');
     

       
        if ($request->filled('search') && strlen($request->input('search')) >= 3) {
            
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('global_search') && strlen($request->input('global_search')) >= 3) {
            $search = $request->input('global_search');

            $query->where(function ($q) use ($search) {
                $q->whereHas('group', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })
                    ->orWhereHas('brands', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }


        if ($request->filled('filterCategory')) {
            $categories = explode(',', $request->filterCategory);

            $query->whereHas('group', function ($q) use ($categories) {
                $q->whereIn('name', $categories);
            });
        }

        if ($request->filled('filterMaxPrice')) {
            $maxPrice = (int) $request->input('filterMaxPrice');
            $query->where('price', '<=', $maxPrice);
        }

        if ($request->filled('filterMinPrice')) {
            $minPrice = (int) $request->input('filterMinPrice');
            $query->where('price', '>=', $minPrice);
        }

        if ($request->filled('filterInventory')) {
            $inventory = (int) $request->input('filterInventory');
            $query->where('inventory', '>=', $inventory);
        }

        if ($request->filled('filterBrand')) {
            $brands = explode(',', $request->filterBrand);

            $query->whereHas('brands', function ($q) use ($brands) {
                $q->whereIn('name', $brands);
            });
        }

        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'latest':
                    $query->orderBy('id', 'desc'); // جدیدترین
                    break;
                case 'max_price':
                    $query->orderBy('price', 'desc'); // قیمت صعودی
                    break;
                case 'min_price':
                    $query->orderBy('price', 'asc'); // قیمت نزولی
                    break;
                case 'best_seller':
                    $query->orderBy('salesCount', 'asc'); //  
                    break;
                case 'highest_score':
                    $query->orderBy('id', 'desc'); // قیمت نزولی
                    break;
                default:
                    $query->latest(); // پیش‌فرض: جدیدترین
            }
        } else {
            $query->latest(); // پیش‌فرض
        }

        // pagination
        $products = $query->paginate(perPage: 6);

        return new ProductCollection($products);
        
    }


    public function show(Product $product)
    {
        // dd($product);
        $data = [
            'data'=> ['product'=>new ProductResource($product)],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ];
        return response()->json($data);
     
    }

    public function latestProduct(){
        
      
        
    }



}
