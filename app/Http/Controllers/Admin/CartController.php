<?php

namespace App\Http\Controllers\Admin;
use App\Models\Admin\Product;
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
            return [
                "id" => $product->id,
                "faName" => $product->name,
                "url" => $product->url,
                "price" => (int) $product->price,
                "image" => $product->cover,
                "count" => $count,
                "totalPrice" => (int) $price,
                "ratio" => $product->ratio,
                "offer" => $product->discount ?? 0,
                // "alertMessage" => $this->checkStockWarning($product, $count * $product->ratio),
            ];
        });

        $cardCount = $items->count();
        $cart->count = $cardCount;
        $cart->save();
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
                // 'alertMessage' => $i['alertMessage'],
            ];
        })->values();

        $amount = number_format($cart->total_price);

        return response()->json([
            'data' => [
                [
                    'cardCount' => $cardCount,
                    'amount' => (string) $amount,
                    'postPay' => "120,000",
                    'items' => $itemsForOutput
                ]
            ]
        ]);
         
    }

    public function update(Request $request)
    {
        
        
        $count = $request->count;
        $product = Product::findOrFail($request->id);
        if ($product->warehouseInventory < ($count * $product->ratio)) {

            return response()->json([
                'data' => null,
                'statusCode' => 401,
                'success' => true,
                'message' => 'تعداد انتخاب شده بیشتر از موجودی انبار است',
                'errors' => null
            ]);
        }
        $user_id = auth()->user()->id;
        $cart = Cart::firstOrCreate(
            ['user_id' => $user_id],
            ['count' => 0, 'total_price' => 0]
        );
        $ratio = $product->ratio;
        
        $total_price_for_product = $count * $product->price * $ratio;

        // بررسی اگر محصول قبلاً در سبد بود
        $existingProduct = $cart->products()->find($product->id);

        if ($existingProduct) {
            // update pivot
            $cart->products()->updateExistingPivot($product->id, [
                'quantity' => $count,
                'price' => $total_price_for_product,
            ]);
        } else {
            // اضافه کردن محصول جدید
            $cart->products()->attach($product->id, [
                'quantity' => $count,
                'price' => $total_price_for_product,
            ]);
        }

        // محاسبه count و total_price کل سبد
        $cartData = $cart->products()->get()->reduce(function ($carry, $item) {
            $carry['count'] += $item->pivot->quantity;
            $carry['total_price'] += $item->pivot->price;
            return $carry;
        }, ['count' => 0, 'total_price' => 0]);

        // $cart->count = $cartData['count'];
        $cart->total_price = $cartData['total_price'];
        $cart->save();

        // آماده سازی خروجی محصولات
        $items = $cart->products->map(function ($product) {
            $count = $product->pivot->quantity;
            $price = $product->pivot->price;
            return [
                "id" => $product->id,
                "faName" => $product->name,
                "url" => $product->url,
                "price" => (int) $product->price,
                "image" => $product->cover,
                "count" => $count,
                "totalPrice" => (int) $price,
                "ratio" => $product->ratio,
                "offer" => $product->discount ?? 0,
                // "alertMessage" => $this->checkStockWarning($product, $count * $product->ratio),
            ];
        });

        $cardCount = $items->count();
        $cart->count = $cardCount;
        $cart->save();
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
                // 'alertMessage' => $i['alertMessage'],
            ];
        })->values();

        $amount = number_format($cart->total_price);

        return response()->json([
            'data' => [
                [
                    'cardCount' => $cardCount,
                    'amount' => (string) $amount,
                    'postPay' => "120,000",
                    'items' => $itemsForOutput
                ]
            ]
        ]);
    }
    private function checkStockWarning($product, $count)
    {
        if ($product->warehouseInventory < ($count * $product->ratio)) {
          
            return response()->json([
                'data' => null,
                'statusCode' => 200,
                'success' => true,
                'message' => 'تعداد انتخاب شده بیشتر از موجودی انبار است',
                'errors' => null
            ]);
        }
        return null;
    }


    public function remove(Request $request){
        $user_id = auth()->user()->id;
        $cart = Cart::where("user_id", $user_id)->first();
        $cartProducts = $cart->products;
        if ($cartProducts->count() < 0) {
            $cart->delete();
            return response()->json([
                'data' => null,
                'statusCode' => 200,
                'success' => true,
                'message' => 'سبد خرید شما خالیست',
                'errors' => null
            ]);

        }
        $product = Product::find($request->id);
        $cart->products()->detach($product->id);
        $cart->load('products');
        if ($cart->products->count() == 0){
            $cart->delete();
            return response()->json([
                'data' => null,
                'statusCode' => 200,
                'success' => true,
                'message' => 'سبد خرید شما خالی شد',
                'errors' => null
            ]);
        }
        
        $cartData = $cart->products()->get()->reduce(function ($carry, $item) {
            $carry['count'] += $item->pivot->quantity;
            $carry['total_price'] += $item->pivot->price;
            return $carry;
        }, ['count' => 0, 'total_price' => 0]);

        // $cart->count = $cartData['count'];
        $cart->total_price = $cartData['total_price'];
        $cart->save();
        
        // آماده سازی خروجی محصولات
        $items = $cart->products->map(function ($product) {
            $count = $product->pivot->quantity;
            $price = $product->pivot->price;
            return [
                "id" => $product->id,
                "faName" => $product->name,
                "url" => $product->url,
                "price" => (int) $product->price,
                "image" => $product->cover,
                "count" => $count,
                "totalPrice" => (int) $price,
                "ratio" => $product->ratio,
                "offer" => $product->discount ?? 0,
                // "alertMessage" => $this->checkStockWarning($product, $count * $product->ratio),
            ];
        });

        $cardCount = $items->count();
        
        $cart->count = $cardCount;
        $cart->save();
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
                // 'alertMessage' => $i['alertMessage'],
            ];
        })->values();

        $amount = number_format($cart->total_price);

        return response()->json([
            'data' => [
                [
                    'cardCount' => $cardCount,
                    'amount' => (string) $amount,
                    'postPay' => "120,000",
                    'items' => $itemsForOutput
                ]
            ]
        ]);



    }
}
