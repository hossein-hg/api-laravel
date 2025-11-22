<?php

namespace App\Http\Controllers\Admin;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrederProductResource;
use App\Http\Resources\ProductResource;
use App\Models\Admin\Cart;
use App\Http\Controllers\Controller;
use App\Models\Admin\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{

    public function index(){
        
        $query = Order::with('products','user');
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
        $products = $order->products()->withPivot('quantity')->get([
            'order_product.quantity',
            'order_product.price as total_price',
            'products.name',
            'products.price',
            'products.id',
            'products.ratio',
        ]);

        
        $addresse = $user->addresses[0] ?? null;

        
        $data = [
            'data' => [
                'order' => new OrderResource( $order ) ,
                'products'=> OrederProductResource::collection($products),
                'user'=> [
                    'name'=> $user->name,
                    'mobile'=> $user->phone,
                    'address'=> $addresse,

                ]
                


        ],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ];
        return response()->json($data);   
    }
}
