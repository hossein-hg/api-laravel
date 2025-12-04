<?php

namespace App\Http\Controllers\Admin;
use App\Http\Requests\Admin\Order\UploadCheckRequest;
use App\Http\Resources\AddressResource;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrederProductResource;
use App\Http\Resources\ProductResource;
use App\Models\Admin\Address;
use App\Models\Admin\Brand;
use App\Models\User;
use App\Models\Admin\Cart;
use App\Http\Controllers\Controller;
use App\Models\Admin\Check;
use App\Models\Admin\Color;
use App\Models\Admin\OrderProduct;
use App\Models\Admin\Size;
use App\Models\Admin\Order;
use App\Models\Admin\Product;
use Illuminate\Support\Str;
use DB;
use Illuminate\Http\Request;
use App\Http\Resources\OrderProductCollection;
class OrderController extends Controller
{


    

    public function index(){
       
        $query = Order::with('products','user')->where('user_id', auth()->user()->id);
        $orders = $query->orderBy('id','desc')->paginate(2);
        return new OrderCollection($orders);
    }

    public function all()
    {
        $user = auth()->user();
        
        $query = Order::with('products', 'user');
        
        $orders = $query->orderBy('id', 'desc')->where('status',1)->paginate(10);

        return new OrderCollection($orders);
    }

    public function financialAll()
    {
       
        $query = Order::with('products', 'user');

        $orders = $query->orderBy('id', 'desc')->whereIn('status', [2,3,4])->paginate(10);

        return new OrderCollection($orders);
    }

    public function warehouseAll(){
        

        $query = Order::with('products', 'user');

        $orders = $query->orderBy('id', 'desc')->whereIn('status', [6])->paginate(10);

        return new OrderCollection($orders);
    }
    public function addFromCart(Request $request){
           
            $cart = Cart::findOrFail($request->id);
            
            $order = Order::updateOrCreate(
                ['cart_id' => $cart->id], 
                [ 
                    'total_price' => $cart->total_price,
                    'count' => $cart->count,
                    'user_id'=> $cart->user_id
                ]
            );
            foreach($cart->products as $item){
                $productId = $item->id;
                $attributes = [
                    'order_id' => $order->id,
                    'product_id' => $productId, 
                    'quantity' => $item->pivot->quantity,
                    'price' => $item->pivot->price,
                    'ratio' => $item->ratio,
                    'discount' => $item->discount,
                    'size' => $item->pivot->size,
                    'color' => $item->pivot->color,
                    'brand' => $item->pivot->brand,
                    'pay_type' => $item->pivot->pay_type,
                    'product_price' => $item->pivot->product_price,
                    'init_price'=> $item->pivot->product_price,
                ];
                if ($order->products()->where('products.id', $productId)->exists()) {
                    $order->products()->updateExistingPivot($productId, $attributes);
                } else {
                    $order->products()->attach($productId, $attributes);
                }
            }
            $cart->delete();
            return response()->json([
                'data'=> [
                    'order_id'=> $order->id
                ],
                'message' => 'سبد خرید به سفارشات اضافه شد',
                'statusCode' => 200,
                'success' => true,
                'errors' => null
            ]);

    }

    public function show(Order $order){
       
        $user = auth()->user();
        $selected_address = $user->addresses->where('status',1)->first()->id ?? [];
        $checkes = $order->checkes;
        $pivot = OrderProduct::where('order_id', $order->id)->get();
        $result = OrderProduct::where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')
            ->select('pay_type', DB::raw('COUNT(*) as total'), DB::raw('SUM(price) as total_price'))
            ->groupBy('pay_type')
            ->get();

        $checkes = $result->map(function ($item) {
            return [

                'pay_type' => $item->pay_type,
                'total_price' => number_format($item->total_price)
            ];
        });
        $checkesUploaded = $result->map(function ($item) use ($order) {
            $uploadedCheckes = Check::where('term_days', $item->pay_type)->where('order_id', $order->id)->get([
                'image',
                'type'
            ])->map(function ($row) {
                $row->image = 'https://files.epyc.ir/' . $row->image;
                return $row;
            });
            return [

                'pay_type' => $item->pay_type,
                'total_price' => number_format($item->total_price),
                'uploadedCheckes' => $uploadedCheckes,

            ];
        });
        $userId = auth()->user()->id;
        $orderUserId = $order->user_id;
        if ($userId != $orderUserId){
            return response()->json([
                'data' => null,
                'statusCode' => 404,
                'message' => "سفارش مورد نظر یافت نشد!",
                'success' => false,
                'errors' => null
            ]);
        }
        $credit_count = DB::table('order_product')->where('order_id',$order->id)->where('pay_type', 'credit')->count();
        $cash_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'cash')->count();
        $check_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')->count();
        $credit_total_price = number_format(DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'credit')->sum('price'));
        $cash_total_price = number_format(DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'cash')->sum('price'));
        $result = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')
            ->select('pay_type', DB::raw('COUNT(*) as total'), DB::raw('SUM(price) as total_price'))
            ->groupBy('pay_type')
            ->get();
        $checkes = $result->map(function ($item) {
            return [

                'pay_type' => $item->pay_type,
                'total_price' => number_format($item->total_price)
            ];
        });    
        $products = $order->products()->withPivot('quantity')->select([
            'order_product.quantity',
            'order_product.price as total_price',
            'products.name',
            'order_product.product_price',
            'products.id',
            'products.ratio',
            'products.cover',
            'order_product.size',
            'order_product.color',
            'order_product.brand',
        ])->paginate(10);

        
        $addresse = $user->addresses ?? null;

        
        $data = [
            'data' => [
                'creditCount' => $credit_count,
                'checkesCount' => $check_count,
                'uploadedCheckes'=> $checkesUploaded,
                'cashCount' => $cash_count,
                'checkes' => $checkes ?? null,
                'credit_total_price' => $credit_total_price,
                'cash_total_price' => $cash_total_price,
                'order' => new OrderResource( $order),
                'products' => new OrderProductCollection($products),
                'user'=> [
                    'name'=> $user->name,
                    'mobile'=> $user->phone,
                    'selected_address' => $selected_address,
                    'addresses'=> AddressResource::collection($addresse),
                    
                ]
            
        ],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ];
        return response()->json($data);   
    }


    public function saleShow(Order $order){
       
        $user = $order->user;
        
        $credit_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'credit')->count();
        $cash_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'cash')->count();
        $check_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')->count();
        $credit_total_price = number_format(DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'credit')->sum('price'));
        $cash_total_price = number_format(DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'cash')->sum('price'));
        $result = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')
            ->select('pay_type', DB::raw('COUNT(*) as total'), DB::raw('SUM(price) as total_price'))
            ->groupBy('pay_type')
            ->get();
        $checkes = $result->map(function ($item) {
            return [

                'pay_type' => $item->pay_type,
                'total_price' => number_format($item->total_price)
            ];
        });


       
        $products = $order->products()->withPivot('quantity')->join('groups', 'products.group_id', '=', 'groups.id')->with('category')->select([
            'order_product.quantity',
            'order_product.size',
            'order_product.brand',
            'order_product.color',
            'order_product.product_price',
            'order_product.price as total_price',
            'products.name',
            'products.price',
            'products.id',
            'products.ratio',
            'products.cover',
            'groups.name as category_name'
            
        ])->with('sizes','brands','colors')->paginate(2);

       
        $address = $user->addresses->where('status',1)->first() ?? null;
       
        $data = [
            'data' => [
                'creditCount' => $credit_count,
                'checkesCount' => $check_count,
                'cashCount' => $cash_count,
                'checkes' => $checkes ?? null,
                'credit_total_price' => $credit_total_price,
                'cash_total_price' => $cash_total_price,
                'order' => new OrderResource($order),
                'products' => new OrderProductCollection($products),
                'user' => [
                    'name' => $user->name ?? '',
                    'mobile' => $user->phone ?? '',
                    'address' => $address ? new AddressResource($address) : null,
                ]
            ],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ];
        return response()->json($data);

    }

    public function financialShow(Order $order){
        $user = $order->user;

        $credit_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'credit')->count();
        $cash_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'cash')->count();
        $check_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')->count();
        $credit_total_price = number_format(DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'credit')->sum('price'));
        $cash_total_price = number_format(DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'cash')->sum('price'));
        $result = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')
            ->select('pay_type', DB::raw('COUNT(*) as total'), DB::raw('SUM(price) as total_price'))
            ->groupBy('pay_type')
            ->get();
        $checkes = $result->map(function ($item) {
            return [

                'pay_type' => $item->pay_type,
                'total_price' => number_format($item->total_price)
            ];
        });



        $products = $order->products()->withPivot('quantity')->join('groups', 'products.group_id', '=', 'groups.id')->with('category')->select([
            'order_product.quantity',
            'order_product.size',
            'order_product.brand',
            'order_product.color',
            'order_product.product_price',
            'order_product.price as total_price',
            'products.name',
            'products.price',
            'products.id',
            'products.ratio',
            'products.cover',
            'groups.name as category_name'

        ])->with('sizes', 'brands', 'colors')->paginate(2);


        $address = $user->addresses->where('status', 1)->first() ?? null;

        $data = [
            'data' => [
                'creditCount' => $credit_count,
                'checkesCount' => $check_count,
                'cashCount' => $cash_count,
                'checkes' => $checkes ?? null,
                'credit_total_price' => $credit_total_price,
                'cash_total_price' => $cash_total_price,
                'order' => new OrderResource($order),
                'products' => new OrderProductCollection($products),
                'user' => [
                    'name' => $user->name ?? '',
                    'mobile' => $user->phone ?? '',
                    'address' => $address ? new AddressResource($address) : null,
                ]
            ],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ];
        return response()->json($data);

    }

    public function warehouseShow(Order $order){
        $user = $order->user;

        $credit_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'credit')->count();
        $cash_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'cash')->count();
        $check_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')->count();
        $credit_total_price = number_format(DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'credit')->sum('price'));
        $cash_total_price = number_format(DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'cash')->sum('price'));
        $result = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')
            ->select('pay_type', DB::raw('COUNT(*) as total'), DB::raw('SUM(price) as total_price'))
            ->groupBy('pay_type')
            ->get();
        $checkes = $result->map(function ($item) {
            return [

                'pay_type' => $item->pay_type,
                'total_price' => number_format($item->total_price)
            ];
        });



        $products = $order->products()->withPivot('quantity')->join('groups', 'products.group_id', '=', 'groups.id')->with('category')->select([
            'order_product.quantity',
            'order_product.size',
            'order_product.brand',
            'order_product.color',
            'order_product.product_price',
            'order_product.price as total_price',
            'products.name',
            'products.price',
            'products.id',
            'products.ratio',
            'products.cover',
            'groups.name as category_name'

        ])->with('sizes', 'brands', 'colors')->paginate(2);


        $address = $user->addresses->where('status', 1)->first() ?? null;

        $data = [
            'data' => [
                'creditCount' => $credit_count,
                'checkesCount' => $check_count,
                'cashCount' => $cash_count,
                'checkes' => $checkes ?? null,
                'credit_total_price' => $credit_total_price,
                'cash_total_price' => $cash_total_price,
                'order' => new OrderResource($order),
                'products' => new OrderProductCollection($products),
                'user' => [
                    'name' => $user->name ?? '',
                    'mobile' => $user->phone ?? '',
                    'address' => $address ? new AddressResource($address) : null,
                ]
            ],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ];
        return response()->json($data);
    }

    public function saleProductDelete(Request $request){
       
        $orderId = $request->order_id;
        $product = Product::findOrFail($request->product_id);
       
        DB::table('order_product')->where('order_id',$orderId)->where('product_id',$product->id)->delete();

        $total_price = DB::table('order_product')->where('order_id',$orderId)->sum('price');
        $count = DB::table('order_product')->where('order_id',$orderId)->count();
        
        Order::findOrFail($orderId)->update([
            'total_price'=> $total_price,
            'count'=> $count,
        ]); 
        $order = Order::findOrFail($orderId);
        $total_price = $order->total_price;
        $order->count == 0 ? $order->delete() : null;
           
        $products = $order->products()->withPivot('quantity')->select([
            'order_product.quantity',
            'order_product.price as total_price',
            'products.name',
            'products.price',
            'products.id',
            'products.ratio',
            'products.cover',
            'order_product.size',
            'order_product.color',
        ])->paginate(10);

        
        $data = [
           
            'data'=> [
                'products' => new OrderProductCollection($products),
                'order' => [
                        'total_price' => $total_price,
                ],
            ],      
            
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ];
        
        return response()->json($data);

    }

    public function delete(Request $request){
        $order = Order::findOrFail($request->id);
        
        DB::table('order_product')->where('order_id', $order->id)->delete();
        $order->delete();
        $query = Order::with('products', 'user');
        $orders = $query->paginate(10);

        return new OrderCollection($orders);
    }

    public function uploadCheckes(UploadCheckRequest $request){
        
        $payType = $request->input(key: 'pay_type');
        $user = auth()->user();
        $userCategoryId = $user->category->id;

        if ($request->hasFile('check_image')){
            $image = $request->file('check_image');
            $mimeType = $image->getMimeType();
            $extension = explode('/', $mimeType)[1];
            
            $filename = Str::uuid() . '-' . $payType ."-".$user->id. "." . $extension;
            
            $path = $image->move(public_path('images'.DIRECTORY_SEPARATOR.'checkes'), $filename);
            $relativePath = 'images'.DIRECTORY_SEPARATOR.'checkes'.DIRECTORY_SEPARATOR. $filename;
            $check = Check::updateOrCreate(
                [
                    'order_id' => $request->order_id,
                    'term_days' => $payType,
                    'type' => 'check_image'
                ],
                [
                    'category_user_id' => $userCategoryId,
                    'user_id' => $user->id,
                    'image' => $relativePath,
                ]
            );
            return response()->json([
                
                'data' => [
                    'url' => 'https://files.epyc.ir/' . $relativePath,
                    'term_days'=> $check->term_days,
                    'type'=> 'check_image',
                ],
                'statusCode' => 200,
                'success' => true,
                'message' => 'موفقیت آمیز',
                'errors' => null
            ]);
            
           
        }

        if ($request->hasFile('check_submit_image')) {
            $image = $request->file('check_submit_image');
            $mimeType = $image->getMimeType();
            $extension = explode('/', $mimeType)[1];

            $filename = Str::uuid() . '-' . $payType . "-" . $user->id . "." . $extension;

            $path = $image->move(public_path('images' . DIRECTORY_SEPARATOR . 'checkes'), $filename);
            $relativePath = 'images' . DIRECTORY_SEPARATOR . 'checkes' . DIRECTORY_SEPARATOR . $filename;

            $check = Check::updateOrCreate(
                [
                    'order_id' => $request->order_id,
                    'term_days' => $payType,
                    'type' => 'check_submit_image'
                ],
                [
                    'category_user_id' => $userCategoryId,
                    'user_id' => $user->id,
                    'image' => $relativePath,
                ]
            );
            return response()->json([
                
                'data' => [
                    'url' => 'https://files.epyc.ir/' . $relativePath,
                    'term_days' => $check->term_days,
                    'type' => 'check_submit_image',
                ],
                'statusCode' => 200,
                'success' => true,
                'message' => 'موفقیت آمیز',
                'errors' => null
            ]);


        }
    }


    public function saleProductEdit(Request $request){
        
        
        $order = Order::findOrFail($request->order_id);
        $product = Product::findOrFail($request->product_id);
        $selectedColor = $request->post('color') ?? null;
        $selectedSize = $request->post('size') ?? null;
        $selectedBrand = $request->post('brand') ?? null;
        $selectedCount = $request->post('count') ?? null;

        $selectedColorGet = $request->get('color') ?? null;
        $selectedSizeGet = $request->get('size') ?? null;
        $selectedBrandGet = $request->get('brand') ?? null;
        $selectedCountGet = $request->get('count') ?? null;


        $row = OrderProduct::
            where('order_id', $order->id)
            ->where('product_id', $product->id)
            ->first();
        $previous_color = $row->color ?? null;
        $previous_size = $row->size ?? null;
        $previous_brand = $row->brand ?? null;
        $previous_product_price = (int)$row->init_price ?? null;
        $product_count = $row->quantity ?? null;
        $product_type = $row->pay_type;
        $update = false;
        $new_product_price = (int) $previous_product_price;

        $new_color_price= 0;
        $diffColor = 0;
        $selectedColorObj = null;
        if($selectedColor or $selectedColorGet){
           
            
            
            $previous_color_price = Color::where('product_id',$product->id)->where('color', $previous_color)->value('price');
            if($previous_color_price){
                if ($selectedColor) {
                    $selectedColorObj = Color::where('product_id', $product->id)->where('color', $selectedColor)->first();
                } else {
                    $selectedColorObj = Color::where('product_id', $product->id)->where('color', $selectedColorGet)->first();
                }
                $selected_color_price = $selectedColorObj->price;
                $oldPrice = $previous_color_price * $row->ratio * $product_count;
                $new_color_price = $previous_product_price - ($oldPrice) + ($selected_color_price * $row->ratio * $product_count);
                $diffColor = -($previous_product_price - $new_color_price);
                if (request()->getMethod() == 'POST'):
                    $row->color = $selectedColorObj->color;
                    $update = true;
                endif;


                $new_product_price += $diffColor;
            }
           
           

        }

      
        $new_size_price = 0;
        $diffSize = 0;
        $selectedSizeObj = null;
        if ($selectedSize or $selectedSizeGet) {
            $previous_size_price = Size::where('product_id', $product->id)->where('size', $previous_size)->value('price');
            if($previous_size_price){
                if ($previous_size_price) {
                    if ($selectedSize) {
                        $selectedSizeObj = Size::where('product_id', $product->id)->where('size', $selectedSize)->first();
                    } else {
                        $selectedSizeObj = Size::where('product_id', $product->id)->where('size', $selectedSizeGet)->first();
                    }
                    $selected_size_price = $selectedSizeObj->price;
                    $oldPrice = $previous_size_price * $row->ratio * $product_count;
                    $newPrice = $selected_size_price * $row->ratio * $product_count;

                    $new_size_price = $previous_product_price - ($oldPrice) + ($selected_size_price * $row->ratio * $product_count);
                    $diffSize = -($previous_product_price - $new_size_price);

                    if (request()->getMethod() == 'POST') {
                        $row->size = $selectedSizeObj->size;
                        $update = true;
                    }
                    $new_product_price += $diffSize;
            }
           
            }
           
           

        }

        $new_brand_price = 0;
        $diffBrand = 0;
        $selectedBrandObj = null;
        if ($selectedBrand or $selectedBrandGet) {
            $previous_brand_price = Brand::where('product_id', $product->id)->where('name', $previous_brand)->value('price');
            if ($selectedBrand){
                $selectedBrandObj = Brand::where('product_id', $product->id)->where('name', $selectedBrand)->first();
            }
            else{
                $selectedBrandObj = Brand::where('product_id', $product->id)->where('name', $selectedBrandGet)->first();
            }
            $selected_brand_price = $selectedBrandObj->price;
            $oldPrice = $previous_brand_price * $row->ratio * $product_count;
            $newPrice = $selected_brand_price * $row->ratio * $product_count;
            $new_brand_price = $previous_product_price - ($oldPrice) + ($selected_brand_price * $row->ratio * $product_count);
           
            $diffBrand =  -($previous_product_price - $new_brand_price);
           
            if (request()->getMethod() == 'POST'):$row->brand = $selectedBrandObj->name;
                $update = true; endif;
            $new_product_price += $diffBrand;
            

        }

        $new_total_product_price = $new_product_price;
        $inventory = false;
        
        if ($selectedCount or $selectedCountGet) {
            
            
            if ($selectedCount and request()->getMethod() == 'POST'){
               
                $new_total_product_price = $new_product_price * $selectedCount;
                
                $row->quantity = $selectedCount;
                $update = true; 
            } else {

                $inventory = $selectedCountGet * $product->ratio > $product->warehouseInventory ? true : false;
                
                $new_total_product_price = $new_product_price * $selectedCountGet;
                

            }
            
           
        }
        
        $new_price = price($product,$selectedColorObj, $selectedBrandObj, $selectedSizeObj,$product_type,$selectedCountGet);
  
      
        if ($update):
            $row->product_price = $new_price['number_one_product'];
            $old_total_product_price = $row->price;
            $row->price = $new_price['number_total_product_price'];
            $row->save();
            // $new_product_price = $new_product_price + $diffSize + $diffBrand + $diffColor; // for one product 
            $new_total_price = ($order->total_price - $old_total_product_price + $new_price['number_total_product_price']);
            

            $order->total_price = $new_total_price;
            $order->save();
        endif;
        if (request()->getMethod() == 'GET') {
            return [
                'data'=> [
                    'name'=> $product->name,
                    'cover'=> $product->cover,
                    'ratio'=> $product->ratio,
                    'size'=> $request->query('size'),
                    'color'=> $request->query('color'),
                    'brand'=> $request->query('brand'),
                    'count'=> $request->query('count'),
                    'colors'=> $product->colors->pluck('color'),
                    'brands'=> $product->brands->pluck('name'),
                    'sizes'=> $product->sizes->pluck('size'),
                    'categoryName'=> $product->group->name ?? '',
                    'selected_count'=> $product_count,
                    'selected_color'=> $previous_color,
                    'selected_brand'=> $previous_brand,
                    'selected_size'=> $previous_size,
                    'product_price'=> $new_price['one_product'],
                    'product_total_price'=> $new_price['total_price'],
                    'alertMessage'=> $inventory ? 'تعداد انتخابی بیشتر از تعداد موجود است' : '',

                ],

                'statusCode' => 200,
                'success' => true,
                'message' => 'موفقیت آمیز',
                'errors' => null

            ];
        }
        else{
            return [
                'data' => null,

                'statusCode' => 200,
                'success' => true,
                'message' => 'موفقیت آمیز',
                'errors' => null

            ];
        }
        
        
    }

    public function addProduct(Request $request){
        
        $product = Product::findOrFail($request->id);
        
        $count = $request->count;
        $color = $request->color ?? null;
        $size = $request->size ?? null;
        $brand = $request->brand ?? null;
        $order = Order::findOrFail($request->order_id);
        $validatedValues = ['credit', 'cash'];

        $existingProduct = $order->products()->find($product->id);
        $exist = $existingProduct ? true : false;
        $user = $order->user;
        $category = $user->category;
        $price = $product->price * $product->ratio;
        $all_request[] = [
            'id' => $request->id,
            'count' => $request->count,
        ];

        $productColors = $product->colors;
        $selectedColor = null;
        if ($color) {
            if ($productColors) {
                $selectedColor = $productColors->where('color', $color)->first();  
            }
        }

        $productSizes = $product->sizes;
        $selectedSize = null;
        if ($size) {
            if ($productSizes) {
                $selectedSize = $productSizes->where('size', $size)->first();
            }
        }
        $productBrands = $product->brands;
        $selectedBrand = null;
        if ($brand) {
            if ($productBrands) {
                $selectedBrand = $productBrands->where('name', $brand)->first();
            }
        }
        
        $selectedPrice = $request['selectedPrice'] ?? 'cash';
        if ($request->getMethod() == 'POST') {
        if (!in_array($selectedPrice, $validatedValues) && !preg_match('/^day_\d+$/', $selectedPrice)) {
            return response()->json([
                'data' => null,
                'statusCode' => 404,
                'success' => false,
                'message' => 'مقدار وارد شده صحیح نیست!',
                'errors' => null
            ]);
        }
        }
        $env = false;    
        if ($request->getMethod() == 'POST' ){
            $inventory = true;
            if ($product->warehouseInventory < ($count * $product->ratio)) {
                $inventory = false;
                if ($existingProduct) {
                    $count = $existingProduct->quantity;
                } else {
                    $count = 1;
                }
            }
            $new_price = price($product, $selectedColor, $selectedBrand, $selectedSize, $request['selectedPrice'], $count);
            if ($inventory) {
                if ($existingProduct) {

                } else {
                    $order->products()->attach($product->id, [
                        'quantity' => $count,
                        'ratio' => $product->ratio,
                        'price' => $new_price['number_total_product_price'],
                        'color' => $color ?? null,
                        'size' => $size ?? null,
                        'brand' => $brand ?? null,
                        'product_price' => $new_price['number_one_product'],
                        'init_price' => $new_price['number_one_product'],
                        'pay_type' => $request['selectedPrice'],
                        'order_id'=> $order->id,
                    ]);
                }
            } else {
                if ($existingProduct) {

                    $order->products()->updateExistingPivot($product->id, [
                        'inventory' => 0,
                        'pay_type' => $request['selectedPrice']
                    ]);
                } else {
                    $env = false;
                    $order->products()->attach($product->id, [
                        'quantity' => 1,
                        'price' => $new_price['number_total_product_price'],
                        'ratio' => $product->ratio,
                        'inventory' => 1,
                        'color' => $color ?? null,
                        'size' => $size ?? null,
                        'product_price' => $new_price['number_one_product'],
                        'init_price' => $new_price['number_one_product'],
                        'pay_type' => $request['selectedPrice'],
                        'order_id'=> $order->id,
                    ]);
                }
            }
        $reqMap = collect($all_request)->keyBy('id');
        $items = $order->products->map(function ($product, $index) use ($reqMap,$env) {
            $count = $product->pivot->quantity;
            $inventory = $product->pivot->inventory;
            $total_price = $product->pivot->price;
            $color = $product->pivot->color;

            $product_price = $product->pivot->product_price;
            $size = $product->pivot->size;
            $brand = $product->pivot->brand;
            $price = $product->price;
            $discount = $product->activeOffer()['percent'];
            if ($discount > 0) {
                $price = $price * ((100 - $discount) / 100);
            }
            return [
                "id" => $product->id,
                "faName" => $product->name,
                "url" => $product->url,
                "price" => (int) $product_price,
                "cover" => $product->cover,
                "count" => $count,
                "totalPrice" => (int) $total_price,
                "ratio" => $product->ratio,
                "discount" => $discount,
                "inventory" => $inventory,
                'color' => $color,
                'size' => $size,
                'brand' => $brand,
                'selectedPrice' => $product->pivot->pay_type,
                "alertMessage" => $env  ? 'تعداد انتخابی بیشتر از تعداد موجود است' : '',
            ];
        });

        $orderCount = $items->where('inventory', '>', 0)->where('count', '<>', 0)->count();
        $order->count = $orderCount;
        $order->save();
        $itemsForOutput = $items->map(function ($i) {
            return [
                'id' => $i['id'],
                'faName' => $i['faName'],
                'url' => $i['url'],
                'price' => (string) number_format($i['price']),
                'cover' => $i['cover'],
                'count' => (int) $i['count'],
                'totalPrice' => (string) number_format($i['totalPrice']),
                'ratio' => $i['ratio'],
                'discount' => $i['discount'],
                'inventory' => $i['inventory'],
                'color' => $i['color'],
                'size' => $i['size'],
                'brand' => $i['brand'],
                'selectedPrice' => $i['selectedPrice'],
                'alertMessage' => $i['alertMessage'],
            ];
        })->values();
        // DB::table('cart_product')->where('product_id', $product->id)->update([
        //     'inventory' => 1
        // ]);


        DB::table('order_product')->where('order_id', $order->id)->where('inventory', 0)->delete();

        DB::table('order_product')->where('order_id', $order->id)->where('quantity', '<>', 0)->update([
            'inventory' => 1
        ]);

        // محاسبه count و total_price کل سبد
        $orderData = $order->products()->get()->reduce(function ($carry, $item) {
            $carry['count'] += $item->pivot->quantity;
            $carry['total_price'] += $item->pivot->price;
            return $carry;
        }, ['count' => 0, 'total_price' => 0]);

        // $cart->count = $cartData['count'];
        $order->total_price = $orderData['total_price'];
        $order->save();
        $amount = number_format($order->total_price);
        
        

        $credit_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'credit')->count();
        $cash_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'cash')->count();
        $check_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')->count();
        $credit_total_price = number_format(DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'credit')->sum('price'));
        $cash_total_price = number_format(DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'cash')->sum('price'));
        $result = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')
            ->select('pay_type', DB::raw('COUNT(*) as total'), DB::raw('SUM(price) as total_price'))
            ->groupBy('pay_type')
            ->get();

        $checkes = $result->map(function ($item) {
            return [

                'pay_type' => $item->pay_type,
                'total_price' => number_format($item->total_price)
            ];
        });



        // $checksCount = 
        $countOrder = DB::table('order_product')->where('order_id', $order->id)->count();
        $order->count = $countOrder;
        $order->save();

        // $order->total_price == 0 ? $order->delete() : null;

        // $order = $order->fresh();

        // if (!$order) {
        //     return response()->json([
        //         'data' =>
        //             null
        //         ,
        //         'message' => 'سبد خرید خالی است',
        //         'statusCode' => 200,
        //         'success' => true,
        //         'errors' => null
        //     ]);
        // }
        return response()->json([
            'data' =>
                [
                    // 'id' => $order->id,
                    // 'orderCount' => $countOrder,
                    // 'amount' => (string) $amount,
                    // 'postPay' => "120,000",
                    // 'creditCount' => $credit_count,
                    // 'checkesCount' => $check_count,
                    // 'cashCount' => $cash_count,
                    // 'checkes' => $checkes ?? null,
                    // 'credit_total_price' => $credit_total_price,
                    // 'cash_total_price' => $cash_total_price,
                    // 'items' => $itemsForOutput,

                ]
            ,
            'message' => 'محصول اضافه شد',
            'statusCode' => 200,
            'success' => true,
            'errors' => null
        ]);
        }
        else{
            if ($product->warehouseInventory < ($count * $product->ratio)) {

                $env = true;
            }

            
            $selectedSize = $productSizes->where('size', $size)->first();
            $selectedColor = $productColors->where('color', $color)->first();
            $selectedBrand = $productBrands->where('name', $brand)->first();
            $selectedPrice = $request['selectedPrice'] ?? 'cash';
            $pricess = price($product, $selectedColor, $selectedBrand, $selectedSize, $selectedPrice, $count);
            
            if ($selectedPrice){
                if (!in_array($selectedPrice, $validatedValues) && !preg_match('/^day_\d+$/', $selectedPrice)) {
                    return response()->json([
                        'data' => null,
                        'statusCode' => 404,
                        'success' => false,
                        'message' => 'مقدار وارد شده صحیح نیست!',
                        'errors' => null
                    ]);
                }
                
                
               
                // $total_product_price = 
            }
            
            

            return [
                'data' => [
                    'name' => $product->name,
                    'cover' => $product->cover,
                    'ratio' => $product->ratio,
                    'discount' => $product->activeOffer()['percent'],
                    'size' => $request->query('size'),
                    'color' => $request->query('color'),
                    'brand' => $request->query('brand'),
                    'count' => $request->query('count'),
                    'colors' => $product->colors->pluck('color'),
                    'brands' => $product->brands->pluck('name'),
                    'inventory'=> $product->inventory,
                    'existInOrder'=> $exist ? 1 : 0,
                    'sizes' => $product->sizes->pluck('size'),
                    'categoryName' => $product->group->name ?? '',
                    'product_price' => $pricess['prices'],
                    'old_product_price' => $pricess['oldPrices'],
                    'product_total_price' => $pricess['total_price'],
                    'alertMessage' => $env ? 'تعداد انتخابی بیشتر از تعداد موجود است' : '',
                    
                ],

                'statusCode' => 200,
                'success' => true,
                'message' => 'موفقیت آمیز',
                'errors' => null

            ];
            

        }

        
    }

    public function changeStatus(Request $request){
        $order = Order::findOrFail($request->order_id);
        $user = User::findOrFail($order->user_id);
        $check_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')->count();
        if ($request->status == 4){
            $address_id = $request->address_id;
            if (!$address_id) {
                return response()->json([
                    'data' => null,
                    'statusCode' => 500,
                    'success' => false,
                    'message' => 'فیلد address_id الزامی است',
                    'errors' => null
                ]);
            }
            Address::where('user_id',$user->id)->update([
                'status'=> 0
            ]);
            $address = Address::findOrFail($address_id);

            $address->status = 1;
            $address->save();
            $result = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')
                ->select('pay_type', DB::raw('COUNT(*) as total'), DB::raw('SUM(price) as total_price'))
                ->groupBy('pay_type')
                ->get();
            $checkesUploaded = $result->map(function ($item) use ($order) {
                $uploadedCheckes = Check::where('term_days', $item->pay_type)->where('order_id', $order->id)->get([
                    'image',
                    'type'
                ]);
                return [
                    'pay_type' => $item->pay_type,
                    'total_price' => number_format($item->total_price),
                    'uploadedCheckes' => $uploadedCheckes,

                ];
            });
            $totalUploaded = $checkesUploaded->sum(function ($item) {
                return $item['uploadedCheckes']->count();
            });
            if ($check_count * 2 == $totalUploaded){
                $order->status = $request->status;
                $order->save();
                return response()->json([
                    'data' => null,
                    'statusCode' => 200,
                    'success' => true,
                    'message' => 'وضعیت سفارش تغییر کرد',
                    'errors' => null
                ]);
            }
            else{
                return response()->json([
                    'data' => null,
                    'statusCode' => 500,
                    'success' => false,
                    'message' => 'خطا',
                    'errors' => null
                ]);
            }
            
            
        }
        else{
            $order->description = $request->description;
            $order->status = $request->status;
            $order->save();
            $check_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')->count();

            return response()->json([
                'data' => null,
                'statusCode' => 200,
                'success' => true,
                'message' => 'وضعیت سفارش تغییر کرد',
                'errors' => null
            ]);
        }
        
        
    }


    public function FinalApproval(Order $order){
        $user = $order->user;

        $credit_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'credit')->count();
        $cash_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'cash')->count();
        $check_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')->count();
        $credit_total_price = number_format(DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'credit')->sum('price'));
        $cash_total_price = number_format(DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'cash')->sum('price'));
        $result = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')
            ->select('pay_type', DB::raw('COUNT(*) as total'), DB::raw('SUM(price) as total_price'))
            ->groupBy('pay_type')
            ->get();
        $check_total_price = number_format(DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')->sum('price'));
        
        $checkes = $result->map(function ($item) {
            
            return [

                'pay_type' => $item->pay_type,
                'total_price' => number_format($item->total_price),
               

            ];
        });
        $uploadedCheckes = Check::where('order_id', $order->id)->where('user_id', $user->id)->get();
        $uploadedCheckes = $uploadedCheckes->map(function ($item) {
            return [
                'id' => $item->id,
                'image' => 'https://files.epyc.ir/' . $item->image,
                'type' => $item->type
            ];
        });
        $checkesUploaded = $result->map(function ($item) use ($order) {
            $uploadedCheckes = Check::where('term_days', $item->pay_type)->where('order_id', $order->id)->get([
                'image',
                'type'
            ])->map(function ($row) {
                $row->image = 'https://files.epyc.ir/' . $row->image;
                return $row;
            });
            return [
                'pay_type' => $item->pay_type,
                'total_price' => number_format($item->total_price),
                'uploadedCheckes' => $uploadedCheckes,

            ];
        });

        
        $products = $order->products()->withPivot('quantity')->join('groups', 'products.group_id', '=', 'groups.id')->with('category')->select([
            'order_product.quantity',
            'order_product.size',
            'order_product.color',
            'order_product.pay_type',
            'order_product.product_price',
            'order_product.price as total_price',
            'products.name',
            'products.price',
            'products.id',
            'products.ratio',
            'products.cover',
            'groups.name as category_name'

        ])->with('sizes', 'brands', 'colors')->paginate(2);

        
        $address = $user->addresses->where('status', 1)->first() ?? null;


        $data = [
            'data' => [
                'creditCount' => $credit_count,
                'checkesCount' => $check_count,
                'cashCount' => $cash_count,
                'checkTotalPrice' => $check_total_price,
                'checkes' => $checkes ?? null,
                'credit_total_price' => $credit_total_price,
                'cash_total_price' => $cash_total_price,
                'order' => new OrderResource($order),
                'products' => new OrderProductCollection($products),
                'uploadedCheckes'=> $checkesUploaded,
                'user' => [
                    'name' => $user->name ?? '',
                    'mobile' => $user->phone ?? '',
                    'category' => $user->category->name ?? "1",
                    'address' => $address ? new AddressResource($address) : null,
                    'remaining_credit' => number_format(2000000),
                ]
            ],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ];
        return response()->json($data);
    }

    public function initialApproval(Order $order){
        $user = $order->user;
       
        $credit_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'credit')->count();
        $cash_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'cash')->count();
        $check_count = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')->count();
        $credit_total_price = number_format(DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'credit')->sum('price'));
        $check_total_price = number_format(DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE','%day_%')->sum('price'));
        
        $cash_total_price = number_format(DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'cash')->sum('price'));
        $result = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')
            ->select('pay_type', DB::raw('COUNT(*) as total'), DB::raw('SUM(price) as total_price'))
            ->groupBy('pay_type')
            ->get();


        $checkes = $result->map(function ($item) {
            return [

                'pay_type' => $item->pay_type,
                'total_price' => number_format($item->total_price)
            ];
        });
        $uploadedCheckes = Check::where('order_id', $order->id)->where('user_id', $user->id)->get();

        $uploadedCheckes = $uploadedCheckes->map(function ($item) {
            return [
                'id' => $item->id,
                'image' => 'https://files.epyc.ir/' . $item->image,
                'type' => $item->type
            ];
        });

        $products = $order->products()->withPivot('quantity')->join('groups', 'products.group_id', '=', 'groups.id')->with('category')->select([
            'order_product.quantity',
            'order_product.size',
            'order_product.color',
            'order_product.pay_type',
            'order_product.product_price',
            'order_product.price as total_price',
            'products.name',
            'products.price',
            'products.id',
            'products.ratio',
            'products.cover',
            'groups.name as category_name'

        ])->with('sizes', 'brands', 'colors')->paginate(2);


        $address = $user->addresses->where('status', 1)->first() ?? null;

        $data = [
            'data' => [
                'creditCount' => $credit_count,
                'checkesCount' => $check_count,
                'checkTotalPrice'=> $check_total_price,
                'cashCount' => $cash_count,
                'checkes' => $checkes ?? null,
                'credit_total_price' => $credit_total_price,
                'cash_total_price' => $cash_total_price,
                'order' => new OrderResource($order),
                'products' => new OrderProductCollection($products),
                'uploadedCheckes' => $uploadedCheckes,
                'user' => [
                    'name' => $user->name ?? '',
                    'mobile' => $user->phone ?? '',
                    'category'=> $user->category->name ?? "1",
                    'remaining_credit'=> number_format(2000000),
                    'address' => $address ? new AddressResource($address) : null,
                ]
            ],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ];
        return response()->json($data);

    }


    public function download(Request $request){
        $link = $request->input(key: 'image');
        $check = Check::where('image',$link)->first() ?? null;
        if ($check) {
            return response()->download(
                public_path($link),
                basename($link),
                [
                    'Content-Type' => 'application/octet-stream',
                    'Content-Disposition' => 'attachment; filename="' . basename($link) . '"'
                ]
            );
        }

        
    }
}

