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
            $product_price = $product->pivot->product_price;
            $color = $product->pivot->color;
            $size = $product->pivot->size;
            $brand = $product->pivot->brand;
            return [
                "id" => $product->id,
                "faName" => $product->name,
                "url" => $product->url,
                "price" => $product_price,
                "cover" => $product->cover,
                "count" => $count,
                "totalPrice" => (int) $price,
                "ratio" => $product->ratio,
                "color" => $color ?? null,
                "size" => $size ?? null,
                "brand" => $brand ?? null,
                'selectedPrice' => $product->pivot->pay_type,
                "discount" => $product->discount ?? 0,
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
                'color' => $i['color'],
                'size' => $i['size'],
                'brand' => $i['brand'],
                'selectedPrice' => $i['selectedPrice'],
                'discount' => $i['discount'],
                // 'alertMessage' => $i['alertMessage'],
            ];
        })->values();

        $amount = number_format($cart->total_price);

        $credit_count = DB::table('cart_product')->where('cart_id',$cart->id)->where('pay_type', 'credit')->count();
        $cash_count = DB::table('cart_product')->where('cart_id', $cart->id)->where('pay_type', 'cash')->count();
        $check_count = DB::table('cart_product')->where('cart_id', $cart->id)->where('pay_type', 'LIKE', '%day_%')->count();
        $credit_total_price = number_format(DB::table('cart_product')->where('cart_id', $cart->id)->where('pay_type', 'credit')->sum('price'));
        $cash_total_price = number_format(DB::table('cart_product')->where('cart_id', $cart->id)->where('pay_type', 'cash')->sum('price'));


        $result = DB::table('cart_product')->where('cart_id', $cart->id)->where('pay_type', 'LIKE', '%day_%')
            ->select('pay_type', DB::raw('COUNT(*) as total'), DB::raw('SUM(price) as total_price'))
            ->groupBy('pay_type')
            ->get();

        $checkes = $result->map(function ($item) {
            return [

                'pay_type' => $item->pay_type,
                'total_price' => number_format($item->total_price)
            ];
        });
        return response()->json([
            'data' => 
                [
                    'id' => $cart->id,
                    'cartCount' => $cardCount,
                    'amount' => (string) $amount,
                    'postPay' => "120,000",
                    'creditCount' => $credit_count,
                    'checkesCount' => $check_count,
                    'cashCount' => $cash_count,
                    'checkes' => $checkes ?? null,
                    'credit_total_price' => $credit_total_price,
                    'cash_total_price' => $cash_total_price,
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

         foreach ($request->all() as  $req) {
            $validatedValues = ['credit', 'cash'];
            $selectedPrice = $req['selectedPrice'] ?? '';
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
       
        foreach ($request->all() as $key => $request) {
            
                $product = Product::findOrFail($request['id']);

                $count = $request['count'];

                $all_request[] = [
                    'id'=> $request['id'],
                    'count'=> $request['count'],
                ]; 

                $color = $request['color'] ?? null;
                $productColors = $product->colors;
                $selectedColor = null;
                if ($color) {
                        if ($productColors){
                            $selectedColor = $productColors->where('color', $color)->first();
                        }

                }
               
                $size = $request['size'] ?? null;
                $productSizes = $product->sizes;
                $selectedSize = null;
                if ($size) {
                    
                    if ($productSizes) {
                    $selectedSize = $productSizes->where('size', $size)->first();
                        
                    }
                }
                $brand = $request['brand'] ?? null;
                $productBrands = $product->brands;
                $selectedBrand = null;
                if ($brand) {
                    
                  
                    if ($productBrands) {
                        $selectedBrand = $productBrands->where('name', $brand)->first();
                    }
                }
           
            
            $inventory = true;
               
                
            $user_id = auth()->user()->id;
            $cart = Cart::firstOrCreate(
                ['user_id' => $user_id],
                ['count' => 0, 'total_price' => 0]
            );
           
            $existingProduct = $cart->products()->find($product->id);
            
                if ($product->warehouseInventory < ($count * $product->ratio)) {
                    $inventory = false;
                    if ($existingProduct) {
                            $count = $existingProduct->quantity;
                    }
                    else{
                        $count = 1;
                    }
                }
           
            $new_price = price($product, $selectedColor , $selectedBrand , $selectedSize, $request['selectedPrice'], $count);
            
            if ($inventory){
             

                if ($existingProduct) {
                    $cart->products()->updateExistingPivot($product->id, [
                        'quantity' => $count,
                        'ratio' => $product->ratio,
                        'price' => $new_price['number_total_product_price'], 
                        'color'=> $color ?? null,                      
                        'size'=> $size ?? null,
                        'brand' => $brand ?? null,
                        'product_price' => $new_price['number_one_product'],
                        'pay_type'=> $request['selectedPrice']                      
                    ]);
                } else {
                    $cart->products()->attach($product->id, [
                        'quantity' => $count,
                        'ratio' => $product->ratio,
                        'price' => $new_price['number_total_product_price'],
                        'color' => $color ?? null,
                        'size' => $size ?? null,
                        'brand' => $brand ?? null,
                        'product_price' => $new_price['number_one_product'],
                        'pay_type' => $request['selectedPrice']

                    ]);
                }

            }
            else{
                if ($existingProduct) {
                    
                    $cart->products()->updateExistingPivot($product->id, [
                        'inventory' => 0,
                        'pay_type' => $request['selectedPrice']
                       
                    ]);
                }
                else{
                
                    $cart->products()->attach($product->id, [
                        'quantity' => 1,
                        'price' => $new_price['number_total_product_price'],
                        'ratio' => $product->ratio,
                        'inventory' => 0,
                        'color' => $color ?? null,
                        'size' => $size ?? null,
                        'product_price' => $new_price['number_one_product'],
                        'pay_type' => $request['selectedPrice']

                    ]);
                }     
            }  
            
        }
        
        $reqMap = collect($all_request)->keyBy('id');
        
        // آماده سازی خروجی محصولات
        $items = $cart->products->map(function ($product, $index) use ($reqMap) {
            // dd($index);
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
                'color'=> $color,
                'size'=> $size,
                'brand'=> $brand,
                'selectedPrice'=> $product->pivot->pay_type,
                "alertMessage" => $inventory == 0 ? 'تعداد انتخابی بیشتر از تعداد موجود است' : '',
            ];
        });


        $cardCount = $items->where('inventory', '>', 0)->where('count', '<>', 0)->count();
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
                'color' => $i['color'],
                'size' => $i['size'],
                'brand' => $i['brand'],
                'selectedPrice' => $i['selectedPrice'],
                'alertMessage' => $i['alertMessage'],
            ];
        })->values();





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

        DB::table('cart_product')->where('cart_id', $cart->id)->where('quantity', 0)->where('inventory',0)->delete();
        DB::table('cart_product')->where('cart_id', $cart->id)->where('quantity', '<>',0)->update([
            'inventory'=>1
        ]);
       
        $credit_count = DB::table('cart_product')->where('cart_id', $cart->id)->where('pay_type','credit')->count();
        $cash_count = DB::table('cart_product')->where('cart_id', $cart->id)->where('pay_type','cash')->count();
        $check_count = DB::table('cart_product')->where('cart_id', $cart->id)->where('pay_type','LIKE','%day_%')->count();
        $credit_total_price = number_format(DB::table('cart_product')->where('cart_id', $cart->id)->where('pay_type', 'credit')->sum('price'));
        $cash_total_price = number_format(DB::table('cart_product')->where('cart_id', $cart->id)->where('pay_type', 'cash')->sum('price'));
        $result = DB::table('cart_product')->where('cart_id', $cart->id)->where('pay_type', 'LIKE', '%day_%')
            ->select('pay_type', DB::raw('COUNT(*) as total'), DB::raw('SUM(price) as total_price'))
            ->groupBy('pay_type')
            ->get();
       
        $checkes = $result->map(function ($item) {
            return [
                
                'pay_type'=> $item->pay_type,
                'total_price' => number_format($item->total_price)
            ];
        });
         
       
        
        // $checksCount = 
        $countCart = DB::table('cart_product')->where('cart_id', $cart->id)->count();
        $cart->count = $countCart;
        $cart->save();

        $cart->total_price == 0 ? $cart->delete() : null;
       
        $cart = $cart->fresh();
        
        if (!$cart){
            return response()->json([
                'data' =>
                    null
                ,
                'message' => 'سبد خرید خالی است',
                'statusCode' => 200,
                'success' => true,
                'errors' => null
            ]);
        }
        return response()->json([
            'data' => 
                [
                    'id'=> $cart->id,
                    'cartCount' => $countCart,
                    'amount' => (string) $amount,
                    'postPay' => "120,000",
                    'creditCount'=> $credit_count,
                    'checkesCount'=> $check_count,
                    'cashCount'=> $cash_count,
                    'checkes'=>$checkes ?? null,
                    'credit_total_price'=> $credit_total_price,
                    'cash_total_price'=> $cash_total_price,
                    'items' => $itemsForOutput,
                    
                ]
                ,
            'message' => 'محصول اضافه شد',
            'statusCode' => 200,
            'success' => true,
            'errors' => null
        ]);
    }
  


    public function remove(Request $request){
        $user_id = auth()->user()->id;
        $cart = Cart::where("user_id", $user_id)->first();
        if (!$cart){
            return response()->json([
                'data' => null,
                'statusCode' => 200,
                'success' => true,
                'message' => 'سبد خرید شما خالیست',
                'errors' => null
            ]);
        }
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



        $cartData = $cart->products()->get()->reduce(function ($carry, $item) {
            $carry['count'] += $item->pivot->quantity;
            $carry['total_price'] += $item->pivot->price;
            return $carry;
        }, ['count' => 0, 'total_price' => 0]);

        // $cart->count = $cartData['count'];
        $cart->total_price = $cartData['total_price'];
        $cart->save();
        $amount = number_format($cart->total_price);

        DB::table('cart_product')->where('cart_id', $cart->id)->where('inventory', 0)->delete();
        DB::table('cart_product')->where('cart_id', $cart->id)->where('quantity', '<>', 0)->update([
            'inventory' => 1
        ]);

        $credit_count = DB::table('cart_product')->where('cart_id', $cart->id)->where('pay_type', 'credit')->count();
        $cash_count = DB::table('cart_product')->where('cart_id', $cart->id)->where('pay_type', 'cash')->count();
        $check_count = DB::table('cart_product')->where('cart_id', $cart->id)->where('pay_type', 'LIKE', '%day_%')->count();
        $credit_total_price = number_format(DB::table('cart_product')->where('cart_id', $cart->id)->where('pay_type', 'credit')->sum('price'));
        $cash_total_price = number_format(DB::table('cart_product')->where('cart_id', $cart->id)->where('pay_type', 'cash')->sum('price'));
        $result = DB::table('cart_product')->where('cart_id', $cart->id)->where('pay_type', 'LIKE', '%day_%')
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
        $countCart = DB::table('cart_product')->where('cart_id', $cart->id)->count();
        
        // آماده سازی خروجی محصولات
        $items = $cart->products->map(function ($product) {
            $count = $product->pivot->quantity;
            $price = $product->pivot->price;
            $color = $product->pivot->color;
            $size = $product->pivot->size;
            return [
                "id" => $product->id,
                "faName" => $product->name,
                "url" => $product->url,
                "price" => (int) $price,
                "cover" => $product->cover,
                "count" => $count,
                "totalPrice" => (int) $price,
                "ratio" => $product->ratio,
                "color" => $color ?? null,
                "size" => $size ?? null,
                "discount" => $product->discount ?? 0,
                'selectedPrice' => $product->pivot->pay_type,
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
                'color' => $i['color'],
                'size' => $i['size'],
                'selectedPrice' => $i['selectedPrice'],
                
                'discount' => $i['discount'],
                // 'alertMessage' => $i['alertMessage'],
            ];
        })->values();

        $amount = number_format($cart->total_price);

        return response()->json([
            'data' => 
                [
                'id' => $cart->id,
                'cartCount' => $countCart,
                'amount' => (string) $amount,
                'postPay' => "120,000",
                'creditCount' => $credit_count,
                'checkesCount' => $check_count,
                'cashCount' => $cash_count,
                'checkes' => $checkes ?? null,
                'credit_total_price' => $credit_total_price,
                'cash_total_price' => $cash_total_price,
                'items' => $itemsForOutput,
                ]
                ,
              'message' => 'محصول حذف شد',
                'statusCode' => 200,
                'success' => true,
                'errors' => null
        ]);



    }
}
