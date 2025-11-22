<?php

namespace App\Http\Controllers\Admin;
use App\Models\Admin\Cart;
use App\Http\Controllers\Controller;
use App\Models\Admin\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function addFromCart(Request $request){
           
            $cart = Cart::findOrFail($request->id);
            
            $order = Order::updateOrCreate(
                ['user_id' => $cart->user_id], 
                [ 
                    'total_price' => $cart->total_price,
                    'count' => $cart->count,
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
}
