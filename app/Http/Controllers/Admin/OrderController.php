<?php

namespace App\Http\Controllers\Admin;
use App\Http\Requests\Admin\Order\UploadCheckRequest;
use App\Http\Resources\AddressResource;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrederProductResource;
use App\Http\Resources\ProductResource;
use App\Models\Admin\Cart;
use App\Http\Controllers\Controller;
use App\Models\Admin\Check;
use App\Models\Admin\Color;
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
        $orders = $query->paginate(2);
        return new OrderCollection($orders);
    }

    public function all()
    {
        
        $query = Order::with('products', 'user');
        $orders = $query->paginate(10);
        
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
                ];
                if ($order->products()->where('products.id', $productId)->exists()) {
                    $order->products()->updateExistingPivot($productId, $attributes);
                } else {
                    $order->products()->attach($productId, $attributes);
                }
            }
            $cart->delete();
            return response()->json([
                'data'=> null,
                'message' => 'سبد خرید به سفارشات اضافه شد',
                'statusCode' => 200,
                'success' => true,
                'errors' => null
            ]);

    }

    public function show(Order $order){
       
        $user = auth()->user();
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
        ])->paginate(10);

        
        $addresse = $user->addresses ?? null;

        
        $data = [
            'data' => [
                'creditCount' => $credit_count,
                'checkesCount' => $check_count,
                'cashCount' => $cash_count,
                'checkes' => $checkes ?? null,
                'credit_total_price' => $credit_total_price,
                'cash_total_price' => $cash_total_price,
                'order' => new OrderResource( $order ) ,
                'products' => new OrderProductCollection($products),
                'user'=> [
                    'name'=> $user->name,
                    'mobile'=> $user->phone,
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
            'order_product.color',
            'order_product.price as total_price',
            'products.name',
            'products.price',
            'products.id',
            'products.ratio',
            'products.cover',
            'groups.name as category_name'
            
        ])->with('sizes','brands','colors')->paginate(2);

            
        $addresse = $user->addresses ?? null;


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
                    'addresses' => AddressResource::collection($addresse),

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
           
               
            'products' => new OrderProductCollection($products),
            'order'=> [
                'total_price'=> $total_price,
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
            
            // نام منحصربه‌فرد برای فایل (برای جلوگیری از تداخل)
            $filename = Str::uuid() . '-' . $payType ."-".$user->id. "." . $extension;
            
            // ذخیره مستقیم در public/images
            $path = $image->move(public_path('images'.DIRECTORY_SEPARATOR.'checkes'), $filename);
            $relativePath = 'images'.DIRECTORY_SEPARATOR.'checkes'.DIRECTORY_SEPARATOR. $filename;
           
            $check = Check::create([
                'category_user_id' => $userCategoryId,
                'user_id'=> $user->id,
                'term_days'=> $payType,
                'image'=> $relativePath,
                'order_id'=> $request->order_id
            ]);
            return response()->json([
                
                'data' => [
                    'url' => 'https://files.epyc.ir/' . $relativePath,
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

            $check = Check::create([
                'category_user_id' => $userCategoryId,
                'user_id' => $user->id,
                'term_days' => $payType,
                'image' => $relativePath,
                'status'=> 1,
                'order_id' => $request->order_id    
            ]);
            return response()->json([
                
                'data' => [
                    'url' => 'https://files.epyc.ir/' . $relativePath,
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
        $selectedColor = $request->input('color') ?? null;
        $selectedColor = $request->input('color') ?? null;
        $selectedSize = $request->input('size') ?? null;
        $selectedBrand = $request->input('brand') ?? null;
        $selectedCount = $request->input('count') ?? null;
        
        if($selectedColor){
            $color = Color::where('product_id', $product->id)->where('color', $selectedColor)->first();
            $row = DB::table('order_product')
                ->where('order_id', $order->id)
                ->where('product_id', $product->id)
                ->first();
            $previous_color = $row->color ?? null;
            $previous_product_price = $row->price ?? null;    
            $previous_color_price = Color::where('product_id',$product->id)->where('color', $previous_color)->value('price');
            
            dd($previous_product_price);
            
        }
        dd($selectedCount);    
    }
}

