<?php

namespace App\Http\Controllers\Admin;
use App\Http\Resources\AdminHomeResource;
use DB;
use App\Models\User;
use App\Models\Admin\Cart;
use App\Models\Admin\Size;
use App\Models\Admin\Brand;
use App\Models\Admin\Check;
use App\Models\Admin\Color;
use App\Models\Admin\Order;
use Illuminate\Support\Str;
use App\Models\Admin\Credit;
use Illuminate\Http\Request;
use App\Models\Admin\Address;
use App\Models\Admin\Product;
use App\Models\Admin\RoleLog;
use App\Services\OrderService;
use App\Models\Admin\CartProduct;
use App\Models\Admin\CompanyStock;
use App\Models\Admin\OrderComment;
use App\Models\Admin\OrderProduct;
use App\Http\Resources\LogResource;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\AddressResource;
use App\Http\Resources\OrderCollection;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\OrderCommentResource;
use App\Http\Resources\OrederProductResource;
use App\Http\Resources\OrderProductCollection;
use App\Http\Requests\Admin\Order\UploadCheckRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

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

    public function adminAll()
    {
        $orderService = new OrderService();
        $orders = $orderService->getOrdersByStatus(status: [1,2,3,4,5,6,7]);
        return new OrderCollection($orders);
    }
    public function addFromCart(Request $request){
           
            $cart = Cart::findOrFail($request->id);
            $user = auth()->user();
           
            $max_credit = $user->category ? $user->category->max_credit : 0;
            
           
        $credit_total_price = DB::table('cart_product')->where('cart_id', $cart->id)->where('pay_type', 'credit')->sum('price');
        $credit_amount = Credit::where('user_id', auth()->user()->id)
            ->latest('id')
            ->first();
        if ($credit_amount){
           
            if ($credit_amount->remaining_amount < $credit_total_price and $credit_total_price != 0){
         
                return response()->json([
                    'data' => null,
                    'message' => 'مبلغ سفارش اعتباری از اعتبار باقی مانده شما بیشتر است!',
                    'statusCode' => 422,
                    'success' => false,
                    'errors' => null
                ]);
            }
        }
        else{
            if ($max_credit < $credit_total_price and $credit_total_price != 0) {
                return response()->json([
                    'data' => null,
                    'message' => 'مبلغ سفارش اعتباری از اعتبار باقی مانده شما بیشتر است!',
                    'statusCode' => 422,
                    'success' => false,
                    'errors' => null
                ]);
            }
        }


        $order = Order::updateOrCreate(
            ['cart_id' => $cart->id],
            [
                'total_price' => $cart->total_price,
                'count' => $cart->count,
                'user_id' => $cart->user_id
            ]
        );
        // dd($credit_total_price);
            $cart_items = CartProduct::where('cart_id', $cart->id)->get();

        
            foreach ($cart_items as $item) {
                    $productId = $item->product_id;
                    $product = Product::find($productId);
                    $discount = $product->activeOffer()['percent'];
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
                        $order_item->discount = $discount;
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
            $roleLog->loggable_type = "App\Models\Admin\Order";
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


      
        $products = $order->products()->withPivot('quantity')->leftJoin('groups', 'products.group_id', '=', 'groups.id')->with('category')->select([
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



        $products = $order->products()->withPivot('quantity')->leftJoin('groups', 'products.group_id', '=', 'groups.id')->with('category')->select([
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



        $products = $order->products()->withPivot('quantity')->leftJoin('groups', 'products.group_id', '=', 'groups.id')->with('category')->select([
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
        
        $color = $request->color;
        $brand = $request->brand;
        $size = $request->size;
        $existingProductInPivot = OrderProduct::where('product_id', $product->id)->where('order_id', $orderId)
            ->where(function ($query) use ($color) {
                if (!is_null($color)) {
                    $query->where('color', $color);
                }
            })
            ->where(function ($query) use ($size) {
                if (!is_null($size)) {

                    $query->where('size', $size);
                }
            })
            ->where(function ($query) use ($brand) {
                if (!is_null($brand)) {
                    $query->where('brand', $brand);
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
            $roleLog->loggable_type = "App\Models\Admin\Order";
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
        $roleLog->loggable_type = "App\Models\Admin\Order";
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
            $roleLog->loggable_type = "App\Models\Admin\Check";
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
            $roleLog->loggable_type = "App\Models\Admin\Check";
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
        // dd($request->post('brand'));
        $user = $order->user;
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
        $existingProductInPivot = OrderProduct::where('product_id', $product->id)->where('order_id', $order->id)
            ->where(function ($query) use ($selectedColorGet) {
                if (!is_null($selectedColorGet)) {
                    $query->where('color', $selectedColorGet);
                }
            })
            ->where(function ($query) use ($selectedSizeGet) {
                if (!is_null($selectedSizeGet)) {

                    $query->where('size', $selectedSizeGet);
                }
            })
            ->where(function ($query) use ($selectedBrandGet) {
                if (!is_null($selectedBrandGet)) {
                    $query->where('brand', $selectedBrandGet);
                }
            })
            ->first();
        // dd($existingProductInPivot);
         
        $row = OrderProduct::where('product_id', $product->id)->where('order_id', $order->id)
            ->where(function ($query) use ($selectedOldColorGet) {
                if ($selectedOldColorGet != "null") {
                    $query->where('color', $selectedOldColorGet);
                } 
            })
            ->where(function ($query) use ($selectedOldSizeGet) {
                if ($selectedOldSizeGet != "null") {

                    $query->where('size', $selectedOldSizeGet);
                } 
            })
            ->where(function ($query) use ($selectedOldBrandGet) {
                if (($selectedOldBrandGet != "null")) {
                    $query->where('brand', $selectedOldBrandGet);
                } 
            })->first();
          
        if($existingProductInPivot and $row){
            
            if ($existingProductInPivot->id != $row->id) {
                
                $is_exist = true;
            }
        }    
        if($row){

            $all_company = CompanyStock::where('product_id', $product->id)->get();
            $brands = $all_company->pluck('brand')->unique()->toArray();
            $new_query = CompanyStock::where('product_id', $product->id);
            $brandObj = CompanyStock::where('brand', $selectedBrandGet)->value('brand');
            
            $colors = array_filter($new_query->where('product_id', $product->id)->where('brand', $brandObj)->distinct()->pluck('color_code')->values()->toArray());

            $sizes = array_filter($new_query->where('product_id', $product->id)->where('brand', $brandObj)->distinct()->pluck('size')->values()->toArray());
            $warranties = array_filter($new_query->where('product_id', $product->id)->where('brand', $brandObj)->distinct()->pluck('warranty')->values()->toArray());


            $previous_color = $row->color ?? null;
            $previous_size = $row->size ?? null;
            $previous_brand = $row->brand ?? null;
            $product_count = $row->quantity ?? null;
            $inventory = false;
            if ($selectedCount or $selectedCountGet) {
                if ($selectedCount and request()->getMethod() == 'POST') {
                    $row->quantity = $selectedCount;
                    $update = true;
                } else {
                    $inventory = $selectedCountGet * $product->ratio > $product->warehouseInventory ? true : false;
                }
            }

           
             if (request()->getMethod() == 'GET'){
                $calc_price = price_calculate($product, $selectedColorGet, $selectedBrandGet, $selectedSizeGet, selectedType: $request['selectedPrice'], count: $request->query('count'), user: $user);
             }
             else{
                
                $calc_price = price_calculate($product, $selectedColor, $selectedBrand, $selectedSize, selectedType: $request['selectedPrice'], count: $request->post('count'),user: $user);
             }
            
            if (request()->getMethod() == 'POST'):
                
                $row->product_price = $calc_price['number_one_product'];
                $old_total_product_price = $row->price;
                $row->price = $calc_price['number_total_product_price'];
                $row->color = $selectedColor;
                $row->size = $selectedSize;
                $row->brand = $selectedBrand;
                $row->save();
               
                // $new_product_price = $new_product_price + $diffSize + $diffBrand + $diffColor; // for one product 
                $new_total_price = ($order->total_price - $old_total_product_price + $calc_price['number_total_product_price']);


                $order->total_price = $new_total_price;
                $order->save();
                $user = auth()->user();
                $roleLog = new RoleLog();
                $roleLog->ip = $request->ip();
                $roleLog->user_id = $user->id;
                $userRole = $user->getJWTCustomClaims()['role'] ?? null;
                $roleLog->role = $userRole;
                $roleLog->loggable_type = "App\Models\Admin\Order";
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
                        'colors' => $colors,
                        'brands' => $brands,
                        'sizes' => $sizes,
                        'waranties' => $warranties,
                        'categoryName' => $product->group->name ?? '',
                        'selected_count' => $product_count,
                        'selected_color' => $previous_color,
                        'selected_brand' => $previous_brand,
                        'selected_size' => $previous_size,
                        'product_price' => $calc_price['one_product'],
                        'product_total_price' => $calc_price['total_price'],
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
      
   
        $all_request[] = [
            'id' => $request->id,
            'count' => $request->count,
        ];
        

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
        $existingProductInCompany = CompanyStock::where('product_id', $product->id)
            ->where(column: function ($query) use ($color) {
                if (!is_null($color)) {
                    $query->where('color_code', $color);
                }
            })
            ->where(function ($query) use ($size) {
                if (!is_null($size)) {

                    $query->where('size', $size);
                }
            })
            ->where(function ($query) use ($brand) {
                if (!is_null($brand)) {
                    $query->where('brand', $brand);
                }
            })
            ->first();
        $companyStockFirst = CompanyStock::where('product_id',$product->id)->first();

        $existingProductInPivot = OrderProduct::where('product_id', $product->id)->where('order_id', $order->id)
            ->where(function ($query) use ($color) {
                if (!is_null($color)) {
                    $query->where('color', $color);
                }
            })
            ->where(function ($query) use ($size) {
                if (!is_null($size)) {

                    $query->where('size', $size);
                }
            })
            ->where(function ($query) use ($brand) {
                if (!is_null($brand)) {
                    $query->where('brand', $brand);
                }
            })
            ->first();
        if ($existingProductInCompany) {
            $color_name = $existingProductInCompany->color_code ?? null;
            $brand_name = $existingProductInCompany->brand ?? null;
            $size_name = $existingProductInCompany->size ?? null;
            $warranty_name = $existingProductInCompany->warranty ?? null;
        } else {
            $color_name = $companyStockFirst ? $companyStockFirst->color_code : null;
            $brand_name = $companyStockFirst ? $companyStockFirst->brand : null;
            $size_name = $companyStockFirst ? $companyStockFirst->size : null;
            $warranty_name = $existingProductInCompany->warranty ?? null;
        }
        $exist = $existingProductInPivot ? true : false;    
        if ($request->getMethod() == 'POST' ){
            $inventory = true;
           
            $calc_price = price_calculate($product, $color, $brand, $size, $request['selectedPrice'], $count, $user);

            if ($existingProductInCompany) {
              
                if ($existingProductInCompany->count < ($count * $product->ratio)) {
                    $inventory = false;
                    if ($existingProductInPivot) {
                        $count = $existingProductInPivot->quantity;
                    }
                }

                if ($inventory) {
                    if ($existingProductInPivot) {

                       
                        $existingProductInPivot->quantity = $count;

                        $existingProductInPivot->price = $calc_price['number_total_product_price'];

                        $existingProductInPivot->color = $existingProductInCompany->color_code;
                        $existingProductInPivot->size = $existingProductInCompany->size;
                        $existingProductInPivot->brand = $existingProductInCompany->brand;
                        $existingProductInPivot->product_price = $calc_price['number_one_product'];
                        $existingProductInPivot->pay_type = $request['selectedPrice'];
                        $existingProductInPivot->save();
                    } else {

                        $new_cart_order_record = new OrderProduct();
                        $new_cart_order_record->order_id = $order->id;
                        $new_cart_order_record->quantity = $count;
                        $new_cart_order_record->ratio = $product->ratio;
                        $new_cart_order_record->price = $calc_price['number_total_product_price'];
                        $new_cart_order_record->color = $existingProductInCompany->color_code;
                        $new_cart_order_record->size = $existingProductInCompany->size;
                        $new_cart_order_record->brand = $existingProductInCompany->brand;
                        $new_cart_order_record->product_price = $calc_price['number_one_product'];
                        $new_cart_order_record->pay_type = $request['selectedPrice'];
                        $new_cart_order_record->product_id = $product->id;
                        $new_cart_order_record->save();

                    }
                } else {
                    if ($existingProductInPivot) {
                        // $existingProductInPivot->inventory = 0;
                        $existingProductInPivot->pay_type = $request['selectedPrice'];
                        $existingProductInPivot->save();
                        $existingProductInPivot->refresh();

                       $env = false;


                    }
                }



            } else {
               
                if ($product->warehouseInventory < ($count * $product->ratio)) {
                    $inventory = false;
                    if ($existingProductInPivot) {
                        $count = $existingProductInPivot->quantity;
                    }
                }


                if ($inventory) {
                    if ($existingProductInPivot) {
                    
                        $existingProductInPivot->quantity = $count;
                        $existingProductInPivot->price = $calc_price['number_total_product_price'];
                        $existingProductInPivot->product_price = $calc_price['number_one_product'];
                        $existingProductInPivot->pay_type = $request['selectedPrice'];
                        $existingProductInPivot->save();
                    } else {

                        $new_cart_order_record = new OrderProduct();
                        $new_cart_order_record->order_id = $order->id;
                        $new_cart_order_record->quantity = $count;
                        $new_cart_order_record->ratio = $product->ratio;
                        $new_cart_order_record->price = $calc_price['number_total_product_price'];

                        $new_cart_order_record->product_price = $calc_price['number_one_product'];
                        $new_cart_order_record->pay_type = $request['selectedPrice'];
                        $new_cart_order_record->product_id = $product->id;
                        $new_cart_order_record->save();

                    }
                } else {
                    if ($existingProductInPivot) {
                        // $existingProductInPivot->inventory = 0;
                        $existingProductInPivot->pay_type = $request['selectedPrice'];
                        $existingProductInPivot->save();
                        $existingProductInPivot->refresh();
                    }
                    else{
                        $env = false;
                    }
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
            $roleLog->loggable_type = "App\Models\Admin\Order";
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
            if ($existingProductInCompany){
                if ($existingProductInCompany->count < ($count * $product->ratio)) {

                    $env = true;
                    $count = $existingProductInCompany->count;
                }
            }
            else {
                if ($product->warehouseInventory < ($count * $product->ratio)) {

                    $env = true;
                    $count = $existingProductInCompany->count;
                }
            }
            $user = $order->user;
            $calc_price = price_calculate($product, $color, $brand, $size, $request['selectedPrice'], $count, $user);
            
            
            $selectedPrice = $request['selectedPrice'] ?? 'cash';
            $all_company = CompanyStock::where('product_id', $product->id)->get();
            $brands = $all_company->pluck('brand')->unique()->toArray();
            $new_query = CompanyStock::where('product_id', $product->id);
            if ($brand){
                $brandObj = CompanyStock::where('brand', $brand)->value('brand');
                // dd($brandObj);
            } else {
                $brandObj = CompanyStock::where('brand', $brand_name)->value('brand');
            }
           
            $colors = array_filter($new_query->where('product_id', $product->id)->where('brand', $brandObj)->distinct()->pluck('color_code')->values()->toArray());

            $sizes = array_filter($new_query->where('product_id', $product->id)->where('brand', $brandObj)->distinct()->pluck('size')->values()->toArray());
            $warranties = array_filter($new_query->where('product_id', $product->id)->where('brand', $brandObj)->distinct()->pluck('warranty')->values()->toArray());
            
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
                    'colors' => $colors,
                    'brands' => $brands,
                    'warranties' => $warranties,
                    'inventory'=> $product->inventory,
                    'existInOrder'=> $exist ? 1 : 0,
                    'sizes' => $sizes,
                    'categoryName' => $product->group->name ?? '',
                    'product_price' => $calc_price['prices'],
                    'old_product_price' => $calc_price['oldPrices'],
                    'product_total_price' => $calc_price['total_price'],
                    'alertMessage' => $env ? 'تعداد انتخابی بیشتر از تعداد موجود است' : '',
                    'defaults' => ['color' => $color_name ?? null, 'size' => $size_name ?? null, 'brand' => $brand_name ?? null, 'warranty' => $warranty_name ?? null]
                    
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
                $roleLog->loggable_type = "App\Models\Admin\Order";
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
        elseif ($request->status == 5){
            $user = auth()->user();
            $userRole = $user->getJWTCustomClaims()['role'] ?? null;
            if ($request->description) {
                $comment = new OrderComment();
                $validator = Validator::make($request->all(), [
                    'description' => 'required|string|max:100|min:5',
                ], [
                    'description.max' => 'حداقل تعداد حروف توضیحات باید 5 عدد باشد',
                    'description.min' => 'حداکثر تعداد حروف توضیحات باید 100 عدد باشد',
                    'description.string' => 'توضیحات باید شامل رشته باشد.',
                    'description.required' => 'توضیحات باید شامل رشته باشد.',
                ]);

                if ($validator->fails()) {
                    throw new HttpResponseException(response()->json([
                        'success' => false,
                        'message' => ' خطا اعتبارسنجی!',
                        'statusCode' => 422,
                        'errors' => [$validator->errors()->first()],
                        'data' => null
                    ], 422));
                }
                $comment->user_id = $user->id;

                $comment->role = $userRole;
                $comment->order_id = $order->id;
                $comment->description = $request->description;
                $comment->save();
            }

            $credit_total_price = DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'credit')->sum('price');
            $user = User::findOrFail($order->user_id);
            $credit = Credit::where('user_id', $user->id)->where('order_id', $order->id)->first();
            if (!$credit) {
                $credit = new Credit();
            }

            $credit->user_id = $user->id;
            $credit->category_id = $user->category ? $user->category->id : null;
            $max_credit = $user->category ? $user->category->max_credit : 0;
            $credit->amount_spent = $credit_total_price;
            $credit->remaining_amount = $max_credit - $credit_total_price;
            $credit->order_id = $order->id;
            $credit->status = 0;
            $credit->save();


            $allProductsInOrder = OrderProduct::where('order_id', $order->id)->get();
            foreach ($allProductsInOrder as $orderProduct) {
                $color = $orderProduct->color;
                $size = $orderProduct->size;
                $brand = $orderProduct->brand;
                $ratio = $orderProduct->ratio;
                $quantity = $orderProduct->quantity;
                
                $existingProductInCompany = CompanyStock::where('product_id', $orderProduct->product_id)
                    ->where(function ($query) use ($color) {
                        if (!is_null($color)) {
                            $query->where('color_code', $color);
                        }
                    })
                    ->where(function ($query) use ($size) {
                        if (!is_null($size)) {

                            $query->where('size', $size);
                        }
                    })
                    ->where(function ($query) use ($brand) {
                        if (!is_null($brand)) {
                            $query->where('brand', $brand);
                        }
                    })
                ->first();

                if ($existingProductInCompany){
                        $existingProductInCompany->count -= $ratio * $quantity;
                        $existingProductInCompany->save();
                    }
                else{
                        $product = Product::find($orderProduct->product_id);
                    if ($product){
                                $product->warehouseInventory -= $ratio * $quantity;

                                $product->save();
                     }
                    }
                   
            }

            $order->status = $request->status;
            $order->save();

            $roleLog = new RoleLog();
            $roleLog->ip = $request->ip();
            $roleLog->user_id = $user->id;
            $userRole = $user->getJWTCustomClaims()['role'] ?? null;
            $roleLog->role = $userRole;
            $roleLog->loggable_type = "App\Models\Admin\Order";
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
                        'errors' => [$validator->errors()->first()],
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
            $roleLog->loggable_type = "App\Models\Admin\Order";
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

        
        $products = $order->products()->withPivot('quantity')->leftJoin('groups', 'products.group_id', '=', 'groups.id')->with('category')->select([
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

        $products = $order->products()->withPivot('quantity')->leftJoin('groups', 'products.group_id', '=', 'groups.id')->with('category')->select([
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
        $check_total_price = number_format(DB::table('order_product')->where('order_id', $order->id)->where('pay_type', 'LIKE', '%day_%')->sum('price'));

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
                'type' => $item->type,
                'pay_type'=> $item->term_days
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

        $products = $order->products()
            ->withPivot('quantity')
            ->leftJoin('groups', 'products.group_id', '=', 'groups.id') // <-- LEFT JOIN
            ->with('category')
            ->select([
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
                'groups.name as category_name',
                'products.type',
            ])
            ->with('sizes', 'brands', 'colors')
            ->paginate(2);

        // dd($products);


        $address = $user->addresses->where('status', 1)->first() ?? null;
        $logs = RoleLog::where('loggable_id',$order->id)->get();
        $logs = $logs
            ->groupBy('role')
            ->map(function ($items) {
                return LogResource::collection($items);
        });      
        
        $comments = $order->comments;
          
        $data = [
            'data' => [
                'creditCount' => $credit_count,
                'checkesCount' => $check_count,
                'checkTotalPrice' => $check_total_price,
                'cashCount' => $cash_count,
                'checkes' => $checkes ?? null,
                'credit_total_price' => $credit_total_price,
                'cash_total_price' => $cash_total_price,
                'order' => new OrderResource($order),
                'products' => ['results'=> OrederProductResource::collection($products),
                                'hasPrevPage' => !$products->onFirstPage(), 
                                'hasNextPage' => $products->hasMorePages(), 
                                'page' => $products->currentPage(), 
                                'total_page' => $products->lastPage(), 
                            
                                'total_products' => $products->total(), 
                            ],
                
                'uploadedCheckes' => $checkesUploaded,
                'user' => [
                    'name' => $user->name ?? '',
                    'mobile' => $user->phone ?? '',
                    'category' => $user->category->name ?? "1",
                    'remaining_credit' => number_format(2000000),
                    "phone" => $user->phone,
                    "telephone" => $user->telephone,
                    "gender" => $user->gender,
                    "avatar" => $user->avatar,
                    "user_type" => $user->user_type,
                    "company_name" => $user->company_name,
                    "national_code" => $user->national_code,
                    "economic_code" => $user->economic_code,
                    "registration_number" => $user->registration_number,
                    "is_active" => $user->is_active == 1 ? true : false,
                    'created_at' => $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : '',
                    'address' => $address ? new AddressResource($address) : null,
                ],
                'logs'=> $logs,
                'comments'=> OrderCommentResource::collection($comments),
            ],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ];
        return response()->json($data);

    }


    public function comments(Order $order){
        $comment = OrderComment::where('order_id', $order->id)->latest()->first();
        return response()->json([
            'data'=> [
                'comment'=> new OrderCommentResource($comment),
            ],
             'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ]);
     
    }


    public function home(){
        $user = auth()->user();
        return new AdminHomeResource($user);
    }
}

