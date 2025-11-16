<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\Cart;
class CartController extends Controller
{
    public function index(){
        
        $cart = Cart::where("user_id", auth()->user()->id)->first();
        if (!$cart) {
            return response()->json([
                'data' => [],
                'message' => 'سبد خرید خالی است'
            ]);
        }

        $items = $cart->products->map(function ($product) {

            $count = $product->pivot->quantity;
        
            $price = $product->pivot->price;
            $priceInt = (int) $product->price;
            return [
                    "id" => $product->id,
                    "faName" => $product->name,
                    "url" => $product->url,
                    "price" => $priceInt,
                    "image" => $product->cover,
                    "count" => $count,
                    "totalPrice" => (int) $price * $product->ratio,
                    "ratio" => $product->ratio,
                    "offer" => $product->discount ?? 0,
                    "alertMessage" => $this->checkStockWarning($product, $count * $product->ratio),
                 
            ];
        });
        
        $cardCount = $items->count();
        $itemsForOutput = $items->map(function ($i) {
            return [
                'id' => $i['id'],
                'faName' => $i['faName'],
                'url' => $i['url'],
                'price' => (string) number_format($i['price']),   
                'image' => $i['image'],
                'count' => (int) $i['count'],                              
                'totalPrice' => (string) number_format($i['totalPrice']), 
                'ratio' => $i['ratio'],
                'offer' => $i['offer'],
                'alertMessage' => $i['alertMessage'],
            ];
        })->values();
        // مجموع قیمت‌ها
        $amount = $items->sum('totalPrice');
        $amount = number_format($amount);

        return response()->json([
            'data' => [
                [
                    'cardCount' => $cardCount,
                    'amount' => (string) $amount,
                    'postPay' => "120,000", // اگر پست ثابت است
                    'items' => $itemsForOutput
                ]
            ]
        ]);
         
    }

   

    private function checkStockWarning($product, $count)
    {
        if ($product->warehouseInventory < $count) {
            return "تعداد انتخاب شده بیشتر از موجودی انبار است";
        }
        return null;
    }
}
