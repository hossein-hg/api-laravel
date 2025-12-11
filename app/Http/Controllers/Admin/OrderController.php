<?php

namespace App\Http\Controllers\Admin;
use DB;
use App\Models\User;
use App\Models\Admin\Cart;
use App\Models\Admin\Size;
use App\Models\Admin\Brand;
use App\Models\Admin\Check;
use App\Models\Admin\Color;
use App\Models\Admin\Order;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Admin\Address;
use App\Models\Admin\Product;
use App\Models\Admin\RoleLog;
use App\Models\Admin\CartProduct;
use App\Models\Admin\OrderComment;
use App\Models\Admin\OrderProduct;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\AddressResource;
use App\Http\Resources\OrderCollection;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\OrderProductCollection;
use App\Http\Requests\Admin\Order\UploadCheckRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\OrderService;

class OrderController extends Controller
{


    

    public function index(){

       
        $orderService = new OrderService();
        $orders = $orderService->getOrdersByStatus('index');
        return new OrderCollection($orders);
    }

    public function all()
    {
        $orderService = new OrderService();
        $orders = $orderService->getOrdersByStatus(status:[1]);
        return new OrderCollection($orders);
    }

    public function financialAll()
    {
        $orderService = new OrderService();
        $orders = $orderService->getOrdersByStatus(status: [2, 3, 4]);
        return new OrderCollection($orders);
    }

    public function warehouseAll(){
        $orderService = new OrderService();
        $orders = $orderService->getOrdersByStatus(status: [6]);
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
            $cart_items = CartProduct::where('cart_id', $cart->id)->get();

        
            foreach ($cart_items as $item) {
                    $productId = $item->product_id;
                    $size = $item->size;
                    $color = $item->color;
                    $brand = $item->brand;
                    $order_item = OrderProduct::where('product_id', $productId)->where('size',$size)->where('color', $color)->where('brand', $brand)->where('order_id',$order->id)->first();
                    
                    if(!$order_item){

                        $order_item = new OrderProduct();
                        $order_item->product_id = $productId;
                        $order_item->order_id = $order->id;
                        $order_item->quantity = $item->quantity;
                        $order_item->brand = $brand;
                        $order_item->size = $size;
                        $order_item->color = $color;
                        $order_item->discount = $item->discount;
                        $order_item->pay_type = $item->pay_type;
                        $order_item->product_price = $item->product_price;
                        $order_item->init_price = $item->product_price;
                        $order_item->price = $item->price;
                        $order_item->ratio = $item->ratio;
                        $order_item->save();
                    }
            }
            
            $cart->delete();
            $user = auth()->user();
            $roleLog = new RoleLog();
            $roleLog->ip = $request->ip();
            $roleLog->user_id = $user->id;
            $userRole = $user->getJWTCustomClaims()['role'] ?? null;
            $roleLog->role = $userRole;
            $roleLog->loggable_type = "App\Admin\Order";
            $roleLog->loggable_id = $order->id;
            $roleLog->description = "محصولات از سبد خرید به سفارش منتقل شد";
            $roleLog->save();
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
        $result = DB::table('order_product')
            ->where('order_id', $order->id)
            ->whereIn('pay_type', ['day_30', 'day_60', 'day_90', 'day_120', 'day_45', 'day_75', 'day_180']) // فقط day_* ها
            ->select('pay_type', DB::raw('count(distinct pay_type) as total'))
            ->groupBy('pay_type')
            ->get();
        
        // جمع کل day_* ها
        $check_count = $result->sum('total');
        $selected_address = $user->addresses->where('status',1)->first()->id ?? [];
        $checkes = $order->checkes;


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
        
        if ($order->checkes()->count() > 0){
           
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
        }
        else{
            $checkesUploaded = [];
        }
        
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
        
        
        $result = DB::table('order_product')
            ->where('order_id', $order->id)
            ->whereIn('pay_type', ['day_30', 'day_60', 'day_90', 'day_120', 'day_45', 'day_75', 'day_180']) // فقط day_* ها
            ->select('pay_type', DB::raw('count(distinct pay_type) as total'))
            ->groupBy('pay_type')
            ->get();
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
            'order_product.pay_type',
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
        $result = DB::table('order_product')
            ->where('order_id', $order->id)
            ->whereIn('pay_type', ['day_30', 'day_60', 'day_90', 'day_120', 'day_45', 'day_75', 'day_180']) // فقط day_* ها
            ->select('pay_type', DB::raw('count(distinct pay_type) as total'))
            ->groupBy('pay_type')
            ->get();

        // جمع کل day_* ها
        $check_count = $result->sum('total');
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
        $result = DB::table('order_product')
            ->where('order_id', $order->id)
            ->whereIn('pay_type', ['day_30', 'day_60', 'day_90', 'day_120', 'day_45', 'day_75', 'day_180']) // فقط day_* ها
            ->select('pay_type', DB::raw('count(distinct pay_type) as total'))
            ->groupBy('pay_type')
            ->get();

        // جمع کل day_* ها
        $check_count = $result->sum('total');
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
        $result = DB::table('order_product')
            ->where('order_id', $order->id)
            ->whereIn('pay_type', ['day_30', 'day_60', 'day_90', 'day_120', 'day_45', 'day_75', 'day_180']) // فقط day_* ها
            ->select('pay_type', DB::raw('count(distinct pay_type) as total'))
            ->groupBy('pay_type')
            ->get();

        // جمع کل day_* ها
        $check_count = $result->sum('total');
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
        $selectedColor = null;
        if ($request->color){
                $selectedColor = Color::where('color', $request->color)->where('product_id',$product->id)->first();
        }
        $selectedBrand = null;
        if ($request->brand) {
            $selectedBrand = Brand::where('name', $request->brand)->where('product_id', $product->id)->first();
        }
        $selectedSize = null;
        if ($request->size) {
            $selectedSize = Size::where('size', $request->size)->where('product_id', $product->id)->first();
        }
        $getColor =  $selectedColor->color ?? null;
        $getSize = $selectedSize->size ?? null;
        $getBrand = $selectedBrand->name ?? null;
        // dd($getSize,$getColor, $getBrand, $product->id, $orderId);
        $existingProductInPivot = OrderProduct::where('product_id', $product->id)->where('order_id', $orderId)
            ->where(function ($query) use ($getColor) {
                if (is_null($getColor)) {
                    $query->whereNull('color');
                } else {
                    $query->where('color', $getColor);
                }
            })
            ->where(function ($query) use ($getSize) {
                if (is_null($getSize)) {

                    $query->whereNull('size');
                } else {
                    $query->where('size', $getSize);
                }
            })
            ->where(function ($query) use ($getBrand) {
                if (is_null($getBrand)) {
                    $query->whereNull('brand');
                } else {
                    $query->where('brand', $getBrand);
                }
            })
            ->first();
           
            
        if ($existingProductInPivot) {
            $user = auth()->user();
            $roleLog = new RoleLog();
            $roleLog->ip = $request->ip();
            $roleLog->user_id = $user->id;
            $userRole = $user->getJWTCustomClaims()['role'] ?? null;
            $roleLog->role = $userRole;
            $roleLog->loggable_type = "App\Admin\Order";
            $roleLog->loggable_id = $orderId;
            $roleLog->description = "محصول با نام  ".$product->name ."  از  سفارش حذف شد ";
            $roleLog->save();
            // dd($roleLog);
            $existingProductInPivot->delete();
            $total_price = DB::table('order_product')->where('order_id', $orderId)->sum('price');
            $count = DB::table('order_product')->where('order_id', $orderId)->count();

            Order::findOrFail($orderId)->update([
                'total_price' => $total_price,
                'count' => $count,
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

                'data' => [
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
            
        }else{
            $data = [

                'data' => [
                   
                ],

                'statusCode' => 404,
                'message' => 'محصول یافت نشد',
                'success' => false,
                'errors' => null,
            ];
            return response()->json($data);
        }
         
       

        
        
        
        

    }

    public function delete(Request $request){
        $order = Order::findOrFail($request->id);
        
        DB::table('order_product')->where('order_id', $order->id)->delete();
        $order->delete();
        $user = auth()->user();
        $roleLog = new RoleLog();
        $roleLog->ip = $request->ip();
        $roleLog->user_id = $user->id;
        $userRole = $user->getJWTCustomClaims()['role'] ?? null;
        $roleLog->role = $userRole;
        $roleLog->loggable_type = "App\Admin\Order";
        $roleLog->loggable_id = $order->id;
        $roleLog->description = "سفارش با ایدی  " . $order->id . "   حذف شد ";
        $roleLog->save();
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
            $success = false;
            if (!$check){
                return response()->json([

                    'data' => null,
                    'statusCode' => 500,
                    'success' => false,
                    'message' => 'عکس اپلود نشد!',
                    'errors' => null
                ]);
            }
            $user = auth()->user();
            $roleLog = new RoleLog();
            $roleLog->ip = $request->ip();
            $roleLog->user_id = $user->id;
            $userRole = $user->getJWTCustomClaims()['role'] ?? null;
            $roleLog->role = $userRole;
            $roleLog->loggable_type = "App\Admin\Check";
            $roleLog->loggable_id = $check->id;
            $roleLog->description = "نصویر چک آپلود شد";
            $roleLog->save();
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
            $success = false;
            if (!$check) {
                return response()->json([

                    'data' => null,
                    'statusCode' => 500,
                    'success' => false,
                    'message' => 'عکس اپلود نشد!',
                    'errors' => null
                ]);
            }
            $user = auth()->user();
            $roleLog = new RoleLog();
            $roleLog->ip = $request->ip();
            $roleLog->user_id = $user->id;
            $userRole = $user->getJWTCustomClaims()['role'] ?? null;
            $roleLog->role = $userRole;
            $roleLog->loggable_type = "App\Admin\Check";
            $roleLog->loggable_id = $check->id;
            $roleLog->description = "نصویر تایید چک آپلود شد";
            $roleLog->save();
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

        $selectedOldColorGet = $request->get('oldColor') ?? null;
        $selectedOldSizeGet = $request->get('oldSize') ?? null;
        $selectedOldBrandGet = $request->get('oldBrand') ?? null;

        $is_exist = false;
        // dd($selectedOldColorGet, $selectedColorGet, $selectedOldBrandGet, $selectedBrandGet, $selectedOldSizeGet,$selectedSizeGet);
        if  ($selectedOldColorGet == $selectedColorGet and $selectedOldBrandGet == $selectedBrandGet and $selectedOldSizeGet == $selectedSizeGet ) {
            // dd('hidsdsds');
            // $is_exist = true;
        }
        // dd($is_exist);


        $productColors = $product->colors;
        $selectColor = null;
        if($productColors){
            $selectColor = $productColors->where('color', $selectedColorGet)->first();
        }

        $productSizes = $product->sizes;
        $selectSize = null;
        if ($productSizes) {
                $selectSize = $productSizes->where('size', $selectedSizeGet)->first();

        }

        $productBrands = $product->brands;
        $selectBrand = null;
        if ($productBrands) {
            $selectBrand = $productBrands->where('name', $selectedBrandGet)->first();

        }
        
        
        $getColor = $selectColor ? $selectColor->color : null;
        $getSize = $selectSize ? $selectSize->size : null;
        $getBrand = $selectBrand ? $selectBrand->name : null;
        
        $existingProductInPivot = OrderProduct::where('product_id', $product->id)->where('order_id',$order->id)
            ->where(function ($query) use ($getColor) {
                if (is_null($getColor)) {
                    $query->whereNull('color');
                } else {
                    $query->where('color', $getColor);
                }
            })
            ->where(function ($query) use ($getSize) {
                if (is_null($getSize)) {

                    $query->whereNull('size');
                } else {
                    $query->where('size', $getSize);
                }
            })
            ->where(function ($query) use ($getBrand) {
                if (is_null($getBrand)) {
                    $query->whereNull('brand');
                } else {
                    $query->where('brand', $getBrand);
                }
            })->first();


        
        $selectColorOld = null;
        if ($productColors) {
            $selectColorOld = $productColors->where('color', $selectedOldColorGet)->first();
        }

        
        $selectSizeOld = null;
        if ($productSizes) {
            $selectSizeOld = $productSizes->where('size', $selectedOldSizeGet)->first();

        }

        
        $selectBrandOld = null;
        if ($productBrands) {
            $selectBrandOld = $productBrands->where('name', $selectedOldBrandGet)->first();

        }

        $getColorOld = $selectColorOld ? $selectColorOld->color : null;
        $getSizeOld = $selectSizeOld ? $selectSizeOld->size : null;
        $getBrandOld = $selectBrandOld ? $selectBrandOld->name : null;

        $row = OrderProduct::where('product_id', $product->id)->where('order_id', $order->id)
            ->where(function ($query) use ($getColorOld) {
                if (is_null($getColorOld)) {
                    $query->whereNull('color');
                } else {
                    $query->where('color', $getColorOld);
                }
            })
            ->where(function ($query) use ($getSizeOld) {
                if (is_null($getSizeOld)) {

                    $query->whereNull('size');
                } else {
                    $query->where('size', $getSizeOld);
                }
            })
            ->where(function ($query) use ($getBrandOld) {
                if (is_null($getBrandOld)) {
                    $query->whereNull('brand');
                } else {
                    $query->where('brand', $getBrandOld);
                }
            })->first();

        // $row = OrderProduct::
        //     where('order_id', $order->id)
        //     ->where('product_id', $product->id)->where('color',$request->oldColor)->where('brand',$request->oldBrand)->where('size',$request->oldSize)
        //     ->first();
        if($existingProductInPivot and $row){
            // dd($existingProductInPivot->id, $row->id);
            // dd('hi');
            if ($existingProductInPivot->id != $row->id) {
                // dd('hi');
                $is_exist = true;
            }
        }    
        

        if($row){
            $previous_color = $row->color ?? null;
            $previous_size = $row->size ?? null;
            $previous_brand = $row->brand ?? null;
            $previous_product_price = (int) $row->init_price ?? null;
            $product_count = $row->quantity ?? null;

            $product_type = $row->pay_type;
            $new_product_price = (int) $previous_product_price;



            $update = false;


            $new_color_price = 0;
            $diffColor = 0;
            $selectedColorObj = null;
            if ($selectedColor or $selectedColorGet) {



                $previous_color_price = Color::where('product_id', $product->id)->where('color', $previous_color)->value('price');
                if ($previous_color_price) {
                    if ($selectedColor) {
                        $selectedColorObj = Color::where('product_id', $product->id)->where('color', $selectedColor)->first();
                    } else {
                        $selectedColorObj = Color::where('product_id', $product->id)->where('color', $selectedColorGet)->first();
                    }
                    if($selectedColorObj){
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



            }


            $new_size_price = 0;
            $diffSize = 0;
            $selectedSizeObj = null;
            if ($selectedSize or $selectedSizeGet) {
                $previous_size_price = Size::where('product_id', $product->id)->where('size', $previous_size)->value('price');
                if ($previous_size_price) {
                    if ($previous_size_price) {
                        if ($selectedSize) {
                            
                            $selectedSizeObj = Size::where('product_id', $product->id)->where('size', $selectedSize)->first();
                        } else {
                            
                            $selectedSizeObj = Size::where('product_id', $product->id)->where('size', $selectedSizeGet)->first();
                        }
                        if($selectedSizeObj){
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
            }

            $new_brand_price = 0;
            $diffBrand = 0;
            $selectedBrandObj = null;
            if ($selectedBrand or $selectedBrandGet) {
                $previous_brand_price = Brand::where('product_id', $product->id)->where('name', $previous_brand)->value('price');
                if ($selectedBrand) {
                    $selectedBrandObj = Brand::where('product_id', $product->id)->where('name', $selectedBrand)->first();
                } else {
                    $selectedBrandObj = Brand::where('product_id', $product->id)->where('name', $selectedBrandGet)->first();
                }
                if ($selectedBrandObj){
                    $selected_brand_price = $selectedBrandObj->price;
                    $oldPrice = $previous_brand_price * $row->ratio * $product_count;
                    $newPrice = $selected_brand_price * $row->ratio * $product_count;
                    $new_brand_price = $previous_product_price - ($oldPrice) + ($selected_brand_price * $row->ratio * $product_count);

                    $diffBrand = -($previous_product_price - $new_brand_price);

                    if (request()->getMethod() == 'POST'):
                        $row->brand = $selectedBrandObj->name;
                        $update = true;
                    endif;
                    $new_product_price += $diffBrand;
                }
                


            }

            $new_total_product_price = $new_product_price;
            $inventory = false;

            if ($selectedCount or $selectedCountGet) {


                if ($selectedCount and request()->getMethod() == 'POST') {

                    $new_total_product_price = $new_product_price * $selectedCount;

                    $row->quantity = $selectedCount;
                    $update = true;
                } else {

                    $inventory = $selectedCountGet * $product->ratio > $product->warehouseInventory ? true : false;

                    $new_total_product_price = $new_product_price * $selectedCountGet;


                }


            }

            $new_price = price($product, $selectedColorObj, $selectedBrandObj, $selectedSizeObj, $product_type, $selectedCountGet);


            if ($update):
                $row->product_price = $new_price['number_one_product'];
                $old_total_product_price = $row->price;
                $row->price = $new_price['number_total_product_price'];
                $row->save();
                // $new_product_price = $new_product_price + $diffSize + $diffBrand + $diffColor; // for one product 
                $new_total_price = ($order->total_price - $old_total_product_price + $new_price['number_total_product_price']);


                $order->total_price = $new_total_price;
                $order->save();
                $user = auth()->user();
                $roleLog = new RoleLog();
                $roleLog->ip = $request->ip();
                $roleLog->user_id = $user->id;
                $userRole = $user->getJWTCustomClaims()['role'] ?? null;
                $roleLog->role = $userRole;
                $roleLog->loggable_type = "App\Admin\Order";
                $roleLog->loggable_id = $order->id;
                $roleLog->description = "محصول با نام  " . $product->name . "  در سفارش  ویرایش شد ";
                $roleLog->save();
            endif;
            if (request()->getMethod() == 'GET') {
                return [
                    'data' => [
                        'name' => $product->name,
                        'cover' => $product->cover,
                        'ratio' => $product->ratio,
                        'size' => $request->query('size'),
                        'color' => $request->query('color'),
                        'brand' => $request->query('brand'),
                        'count' => $request->query('count'),
                        'colors' => $product->colors->pluck('color'),
                        'brands' => $product->brands->pluck('name'),
                        'sizes' => $product->sizes->pluck('size'),
                        'categoryName' => $product->group->name ?? '',
                        'selected_count' => $product_count,
                        'selected_color' => $previous_color,
                        'selected_brand' => $previous_brand,
                        'selected_size' => $previous_size,
                        'product_price' => $new_price['one_product'],
                        'product_total_price' => $new_price['total_price'],
                        'alertMessage' => $inventory ? 'تعداد انتخابی بیشتر از تعداد موجود است' : '',
                        'existInOrder' => $is_exist ? 1 : 0,

                    ],

                    'statusCode' => 200,
                    'success' => true,
                    'message' => 'موفقیت آمیز',
                    'errors' => null

                ];
            } else {
                return [
                    'data' => null,

                    'statusCode' => 200,
                    'success' => true,
                    'message' => 'موفقیت آمیز',
                    'errors' => null

                ];
            }

        } else {
            return [
                'data' => null,

                'statusCode' => 404,
                'success' => false,
                'message' => 'محصول یافت نشد',
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
        $getColor = $selectedColor ? $selectedColor->color : null;
        $getSize = $selectedSize ? $selectedSize->size : null;
        $getBrand = $selectedBrand ? $selectedBrand->name : null;
        $existingProductInPivot = OrderProduct::where('product_id', $product->id)->where('order_id', $order->id)
            ->where(function ($query) use ($getColor) {
                if (is_null($getColor)) {
                    $query->whereNull('color');
                } else {
                    $query->where('color', $getColor);
                }
            })
            ->where(function ($query) use ($getSize) {
                if (is_null($getSize)) {

                    $query->whereNull('size');
                } else {
                    $query->where('size', $getSize);
                }
            })
            ->where(function ($query) use ($getBrand) {
                if (is_null($getBrand)) {
                    $query->whereNull('brand');
                } else {
                    $query->where('brand', $getBrand);
                }
            })
            ->first();

        $exist = $existingProductInPivot ? true : false;    
        if ($request->getMethod() == 'POST' ){
            $inventory = true;
           
            
            if ($product->warehouseInventory < ($count * $product->ratio)) {
                $inventory = false;
                if ($existingProductInPivot) {
                    $count = $existingProductInPivot->quantity;
                } else {
                    $count = 1;
                }
            }
            $new_price = price($product, $selectedColor, $selectedBrand, $selectedSize, $request['selectedPrice'], $count);
            if ($inventory) {
                if ($existingProductInPivot) {

                } else {
                   
                    $new_cart_order_record = new OrderProduct();
                    $new_cart_order_record->order_id = $order->id;
                    $new_cart_order_record->quantity = $count;
                    $new_cart_order_record->ratio = $product->ratio;
                    $new_cart_order_record->price = $new_price['number_total_product_price'];
                    $new_cart_order_record->color = $selectedColor ? $selectedColor->color : null;
                    $new_cart_order_record->size = $selectedSize ? $selectedSize->size : null;
                    $new_cart_order_record->brand = $selectedBrand ? $selectedBrand->name : null;
                    $new_cart_order_record->product_price = $new_price['number_one_product'];
                    $new_cart_order_record->pay_type = $request['selectedPrice'];
                    $new_cart_order_record->product_id = $product->id;
                    $new_cart_order_record->init_price = $new_price['number_one_product'];
                    $new_cart_order_record->save();
                }
            } else {
                if ($existingProductInPivot) {
                    $existingProductInPivot->inventory = 0;
                    $existingProductInPivot->pay_type = $request['selectedPrice'];
                    $existingProductInPivot->save();
                    
                } else {
                    $env = false;
                    
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
            $user = auth()->user();
            $roleLog = new RoleLog();
            $roleLog->ip = $request->ip();
            $roleLog->user_id = $user->id;
            $userRole = $user->getJWTCustomClaims()['role'] ?? null;
            $roleLog->role = $userRole;
            $roleLog->loggable_type = "App\Admin\Order";
            $roleLog->loggable_id = $order->id;
            $roleLog->description = "محصول با نام  " . $product->name . "  به سفارش  اضافه شد ";
            $roleLog->save();
        
        return response()->json([
            'data' =>
                [
                    
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
        $result = DB::table('order_product')
            ->where('order_id', $order->id)
            ->whereIn('pay_type', ['day_30', 'day_60', 'day_90', 'day_120', 'day_45', 'day_75', 'day_180']) // فقط day_* ها
            ->select('pay_type', DB::raw('count(distinct pay_type) as total'))
            ->groupBy('pay_type')
            ->get();

        
        $result = DB::table('order_product')
            ->where('order_id', $order->id)
            ->whereIn('pay_type', ['day_30', 'day_60', 'day_90', 'day_120', 'day_45', 'day_75', 'day_180']) // فقط day_* ها
            ->select('pay_type', DB::raw('count(distinct pay_type) as total'))
            ->groupBy('pay_type')
            ->get();

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
            $result = DB::table('order_product')
                ->where('order_id', $order->id)
                ->whereIn('pay_type', ['day_30', 'day_60', 'day_90', 'day_120','day_45','day_75','day_180']) // فقط day_* ها
                ->select('pay_type', DB::raw('count(distinct pay_type) as total'))
                ->groupBy('pay_type')
                ->get();

            // جمع کل day_* ها
            $total = $result->sum('total');
            
            if ($total * 2 == $totalUploaded){
                $order->status = $request->status;
                $order->save();
                $roleLog = new RoleLog();
                $roleLog->ip = $request->ip();
                $roleLog->user_id = $user->id;
                $userRole = $user->getJWTCustomClaims()['role'] ?? null;
                $roleLog->role = $userRole;
                $roleLog->loggable_type = "App\Admin\Order";
                $roleLog->loggable_id = $order->id;
                $roleLog->description = "وضعیت سفارش تغییر کرد ";
                $roleLog->save();
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
            // $order->description = $request->description;
            $user = auth()->user();
            $userRole = $user->getJWTCustomClaims()['role'] ?? null;
            if ($request->description){
                $comment = new OrderComment();
                $validator = Validator::make($request->all(), [
                    'description' => 'nullable|string|max:100|min:5',
                ], [
                    'description.max' => 'حداقل تعداد حروف توضیحات باید 5 عدد باشد',
                    'description.min' => 'حداکثر تعداد حروف توضیحات باید 100 عدد باشد',
                    'description.string' => 'توضیحات باید شامل رشته باشد.',  
                ]);

                if ($validator->fails()) {
                    throw new HttpResponseException(response()->json([
                        'success' => false,
                        'message' => ' خطا اعتبارسنجی!',
                        'statusCode' => 422,
                        'errors' => $validator->errors(),
                        'data' => null
                    ], 422));
                }
                $comment->user_id = $user->id;

                $comment->role = $userRole;
                $comment->order_id = $order->id;
                $comment->description = $request->description;
                $comment->save();
            }
            
            $order->status = $request->status;
            $order->save();
            $roleLog = new RoleLog();
            $roleLog->ip = $request->ip();
            $roleLog->user_id = $user->id;
            $userRole = $user->getJWTCustomClaims()['role'] ?? null;
            $roleLog->role = $userRole;
            $roleLog->loggable_type = "App\Admin\Order";
            $roleLog->loggable_id = $order->id;
            $roleLog->description = "وضعیت سفارش تغییر کرد ";
            $roleLog->save();
            $result = DB::table('order_product')
                ->where('order_id', $order->id)
                ->whereIn('pay_type', ['day_30', 'day_60', 'day_90', 'day_120', 'day_45', 'day_75', 'day_180']) // فقط day_* ها
                ->select('pay_type', DB::raw('count(distinct pay_type) as total'))
                ->groupBy('pay_type')
                ->get();

            // جمع کل day_* ها
            $check_count = $result->sum('total');

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
        $result = DB::table('order_product')
            ->where('order_id', $order->id)
            ->whereIn('pay_type', ['day_30', 'day_60', 'day_90', 'day_120', 'day_45', 'day_75', 'day_180']) // فقط day_* ها
            ->select('pay_type', DB::raw('count(distinct pay_type) as total'))
            ->groupBy('pay_type')
            ->get();

        // جمع کل day_* ها
        $check_count = $result->sum('total');
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
        if ($check_count > 0) {
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
        } else {
            $checkesUploaded = [];
        }

        
        $products = $order->products()->withPivot('quantity')->join('groups', 'products.group_id', '=', 'groups.id')->with('category')->select([
            'order_product.quantity',
            'order_product.size',
            'order_product.color',
            'order_product.brand',
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
        $result = DB::table('order_product')
            ->where('order_id', $order->id)
            ->whereIn('pay_type', ['day_30', 'day_60', 'day_90', 'day_120', 'day_45', 'day_75', 'day_180']) // فقط day_* ها
            ->select('pay_type', DB::raw('count(distinct pay_type) as total'))
            ->groupBy('pay_type')
            ->get();

        // جمع کل day_* ها
        $check_count = $result->sum('total');
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
            'order_product.brand',
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

    public function allDetails(Order $order){
        
    }
}

