<?php

namespace App\Http\Controllers\Admin;
use App\Models\Admin\Product;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use App\Models\Admin\Cart;
class CartController extends Controller
{
    public function index(){
        
        $cart = Cart::where("user_id", auth()->user()->id)->first();
        if (!$cart) {
            return response()->json([
                'data' => null,
                'message' => 'سبد خرید خالی است',
                'statusCode' => 200,
                'success' => true,
                'errors' => null,
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
                "cover" => $product->cover,
                "count" => $count,
                "totalPrice" => (int) $price,
                "ratio" => $product->ratio,
                "discount" => $product->discount ?? 0,
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
                'cover' => $i['cover'],
                'count' => (int) $i['count'],
                'totalPrice' => (string) number_format($i['totalPrice']),
                'ratio' => $i['ratio'],
                'discount' => $i['discount'],
                // 'alertMessage' => $i['alertMessage'],
            ];
        })->values();

        $amount = number_format($cart->total_price);

        return response()->json([
            'data' => 
                [
                    'cartCount' => $cardCount,
                    'amount' => (string) $amount,
                    'postPay' => "120,000",
                    'items' => $itemsForOutput
                ]
                ,
            'statusCode' => 200,
            'success' => true,
            'message' => 'تمام ایتم های سبدخرید',
            'errors' => null
        ]);
         
    }

    public function update(Request $request)
    {
        
        foreach ($request->all() as $key => $request) {
                $inventory = true;
                $count = $request['count'];
           
                $product = Product::findOrFail($request['id']);
                $discount = $product->activeOffer()['percent'];
                $price = $product->price;
                if ($discount > 0){
                     $price = $price * ((100 - $discount)/100);
                }
                if ($product->warehouseInventory < ($count * $product->ratio)) {
                    $inventory = false;
                    // return response()->json([
                    //     'data' => null,
                    //     'statusCode' => 401,
                    //     'success' => true,
                    //     'message' => 'تعداد انتخاب شده بیشتر از موجودی انبار است',
                    //     'errors' => null
                    // ]);
            }
            $user_id = auth()->user()->id;
            $cart = Cart::firstOrCreate(
                ['user_id' => $user_id],
                ['count' => 0, 'total_price' => 0]
            );
            $existingProduct = $cart->products()->find($product->id);
            if ($inventory){
                
                
                $ratio = $product->ratio;

                $total_price_for_product = $count * $price * $ratio;

               

                if ($existingProduct) {
                    $cart->products()->updateExistingPivot($product->id, [
                        'quantity' => $count,
                        'price' => $total_price_for_product,                       
                    ]);
                } else {
                    $cart->products()->attach($product->id, [
                        'quantity' => $count,
                        'price' => $total_price_for_product,
                    ]);
                }

            }
            else{
                if (!$existingProduct) {
                    $cart->products()->attach($product->id, [
                        'quantity' => 0,
                        'price' => 0,
                        'inventory'=> 0   
                    ]);
                }
                else{
                    $cart->products()->updateExistingPivot($product->id, [
                        'inventory' => 0
                    ]);
                }
            }
            // آماده سازی خروجی محصولات
            $items = $cart->products->map(function ($product)  {
                $count = $product->pivot->quantity;
                $inventory = $product->pivot->inventory;
                $total_price = $product->pivot->price;
                $price = $product->price;
                $discount = $product->activeOffer()['percent'];
                if ($discount > 0) {
                    $price = $price * ((100 - $discount) / 100);
                }
                return [
                    "id" => $product->id,
                    "faName" => $product->name,
                    "url" => $product->url,
                    "price" => (int) $price,
                    "cover" => $product->cover,
                    "count" => $count,
                    "totalPrice" => (int) $total_price,
                    "ratio" => $product->ratio,
                    "discount" => $discount,
                    "inventory" => $inventory,
                    "alertMessage" => $inventory == 0 ? 'تعداد انتخابی بیشتر از تعداد موجود است':'',
                ];
            });
           
            
            $cardCount = $items->where('inventory','>',0)->where('count','<>',0)->count();
            $cart->count = $cardCount;
            $cart->save();
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
                    'alertMessage' => $i['alertMessage'],
                ];
            })->values();
            // DB::table('cart_product')->where('product_id', $product->id)->update([
            //     'inventory' => 1
            // ]);

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
        $amount = number_format($cart->total_price);

        DB::table('cart_product')->where('quantity', 0)->where('inventory',0)->delete();
        DB::table('cart_product')->where('quantity', '<>',0)->update([
            'inventory'=>1
        ]);
        $countCart = DB::table('cart_product')->where('cart_id', $cart->id)->count();
        $cart->count = $countCart;
        $cart->save();
        return response()->json([
            'data' => 
                [
                    'cartCount' => $cardCount,
                    'amount' => (string) $amount,
                    'postPay' => "120,000",
                    'items' => $itemsForOutput
                ]
                ,
            'message' => 'محصول اضافه شد',
            'statusCode' => 200,
            'success' => true,
            'errors' => null
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
        if ($cartProducts->count() == 0) {
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
                "cover" => $product->cover,
                "count" => $count,
                "totalPrice" => (int) $price,
                "ratio" => $product->ratio,
                "discount" => $product->discount ?? 0,
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
                'cover' => $i['cover'],
                'count' => (int) $i['count'],
                'totalPrice' => (string) number_format($i['totalPrice']),
                'ratio' => $i['ratio'],
                'discount' => $i['discount'],
                // 'alertMessage' => $i['alertMessage'],
            ];
        })->values();

        $amount = number_format($cart->total_price);

        return response()->json([
            'data' => 
                [
                    'cartCount' => $cardCount,
                    'amount' => (string) $amount,
                    'postPay' => "120,000",
                    'items' => $itemsForOutput
                ]
                ,
              'message' => 'محصول حذف شد',
                'statusCode' => 200,
                'success' => true,
                'errors' => null
        ]);



    }
}
