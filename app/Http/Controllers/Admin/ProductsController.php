<?php

namespace App\Http\Controllers\Admin;
use App\Http\Requests\AddFeatureRequest;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Models\Admin\CompanyStock;
use App\Models\Admin\Image;
use App\Models\Admin\Offer;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCollection;
use App\Models\Admin\Product;
use DB;
use Illuminate\Support\Facades\File;  // برای path helper
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public function index(Request $request)
    {

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


        $query = Product::with('images', 'group', 'options', 'comments', 'colors', 'warranties', 'sizes', 'brands');



        if ($request->filled('search') && strlen($request->input('search')) >= 3) {

            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('global_search') && strlen($request->input('global_search')) >= 3) {
            $search = $request->input('global_search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")  // اضافه کردن سرچ روی نام product
                    ->orWhereHas('group', function ($q2) use ($search) {
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
            $query->whereRaw('price * ratio <= ?', [$maxPrice]);
        }

        if ($request->filled('filterMinPrice')) {
            $minPrice = (int) $request->input('filterMinPrice');
            $query->whereRaw('price * ratio >= ?', [$minPrice]);
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
        $products = $query->paginate(perPage: 2);

        return new ProductCollection($products);



    }


    public function show(Product $product)
    {
        
        // dd($product);
     
        $data = [
            'data' => ['product' => new ProductResource($product)],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ];
        return response()->json($data);

    }

    public function store(ProductRequest $request)
    {
        $product = new Product();

        $product->name = $request->name;
        $product->en_name = $request->en_name;
        $product->group_id = $request->subCategory_id;
        $product->price = $request->price;
        $product->description = $request->description;
        $product->inventory = $request->inventory;
        $product->ratio = $request->ratio;
        $product->warehouseInventory = $request->warehouseInventory;
        $product->cover = $request->cover;
        $product->tags = $request->tags;
        $product->type = $request->type;
        $product->shortDescription = e($request->shortDescription);
        $product->additionalInformation = e($request->additionalInformation);
        $product->max_sell = $request->max_sell;
        $product->stars = 5;
        $product->save();
        if ($request->filled('images')) {
            foreach ($request->images as $imageRequest) {

                $count = Image::where('product_id', $request->id)->count();
           
                   
                    $image = new Image();
                    $image->product_id = $product->id;
                    $image->path = $imageRequest;
                    $image->save();
                   

            }
        }

        if ($request->filled('discount')) {
            $offer = new Offer();
            $offer->percent = $request->discount;
            $offer->product_id = $product->id;
            $offer->start_time = $request->discount_start_time;
            $offer->end_time = $request->discount_end_time;
            $offer->save();
        }

        return response()->json([
            'data' => [
                'type' => $product->type,
                'id' => $product->id,
                'en_name' => $product->en_name,
            ],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ]);

    }

    public function update(ProductRequest $request)
    {

        $product = Product::findOrFail($request->id);
        $product->name = $request->name;
        $product->en_name = $request->en_name;
        $product->group_id = $request->subCategory_id;
        $product->price = $request->price;
        $product->description = $request->description;
        $product->inventory = $request->inventory;
        $product->ratio = $request->ratio;
        $product->warehouseInventory = $request->warehouseInventory;
        $product->cover = $request->cover;
        $product->tags = $request->tags;
        $product->type = $request->type;
        $product->shortDescription = e($request->shortDescription);
        $product->additionalInformation = e($request->additionalInformation);
        $product->max_sell = $request->max_sell;
        $product->save();
        if ($request->filled('images')) {
            //    dd(count($request->images));
            foreach ($request->images as $imageRequest) {
                
                $quantity = Image::where('product_id', $request->id)->count();
                $image = Image::where('product_id', $request->id)->where('path', $imageRequest['url'])->first();
                
                if ($image) {

                    $image->product_id = $request->id;
                    $image->path = $imageRequest['url'];
                    if ($imageRequest['delete']){
                     
                        $image->path = null;
                    }
                    $image->save();

                }

                if ($image) {
                    if (!$image->path) {
                        $image->delete();
                    }

                }
              

                if (!$image and $quantity < 4) {
                    $image = new Image();
                    $image->product_id = $request->id;
                    $image->path = $imageRequest['url'];
                    $image->save();
                }


            }
        }

        if ($request->filled('discount')) {
            $offer = Offer::where('product_id', $request->id)->first();
            if (!$offer) {
                $offer = new Offer();
            }
            $offer->percent = $request->discount;
            $offer->product_id = $product->id;
            $offer->start_time = $request->discount_start_time;
            $offer->end_time = $request->discount_end_time;
            $offer->save();
        }

        return response()->json([
            'data' => [
                'type' => $product->type,
                'id' => $product->id,
                'en_name' => $product->en_name,
            ],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ]);

    }

    public function delete(Request $request)
    {
        $product = Product::findOrFail($request->id);
        $product->delete();
        return response()->json([
            'data' => null,
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ]);

    }


    public function addFeatures(AddFeatureRequest $request)
    {
        Product::findOrFail($request->id);
        $company = CompanyStock::where('product_id', $request->id)->delete();
        foreach ($request->rows as $row) {
            $company = new CompanyStock();
            $company->product_id = $request->id;
            if ($row['color']) {
                $company->color_name = $row['color']['name'];
                $company->color_code = $row['color']['code'];
            }
            $company->size = $row['size'];
            if ($row['color']) {
                $company->brand = $row['brand']['name'];
                $company->brand_id = $row['brand']['id'];
            }
            $company->warranty = $row['warranty'];
            $company->count = $row['count'];
            $company->accCode = $row['accCode'];
            $company->price = $row['price'];
            $company->save();
        }

        return response()->json([
            'data' => null,
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ]);
    }

    public function features($id)
    {
        
        $product = Product::findOrFail($id);
        $group = $product->group;
        $group_id = $group ? $group->id : 0;
        $brands = $group->brands->pluck('id');
      
        // dd($group);
        $companies = CompanyStock::where('product_id', $product->id)->whereIn('brand_id',$brands)->get();
        return response()->json([
            'data' => [
                'id' => $product->id,
                'rows' => CompanyResource::collection($companies),
            ],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ]);
    }




}
