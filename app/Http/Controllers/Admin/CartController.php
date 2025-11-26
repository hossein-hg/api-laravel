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

        $credit_count = DB::table('cart_product')->where('pay_type', 'credit')->count();
        $cash_count = DB::table('cart_product')->where('pay_type', 'cash')->count();
        $check_count = DB::table('cart_product')->where('pay_type', 'LIKE', '%day_%')->count();
        $credit_total_price = number_format(DB::table('cart_product')->where('pay_type', 'credit')->sum('price'));
        $cash_total_price = number_format(DB::table('cart_product')->where('pay_type', 'cash')->sum('price'));


        $result = DB::table('cart_product')->where('pay_type', 'LIKE', '%day_%')
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
       
        foreach ($request->all() as $key => $request) {

            $validatedValues = ['credit', 'cash'];

            $selectedPrice = $request['selectedPrice'] ?? '';

            if (!in_array($selectedPrice, $validatedValues) && !preg_match('/^day_\d+$/', $selectedPrice)) {
                return response()->json([
                    'data' => null,
                    'statusCode' => 404,
                    'success' => false,
                    'message' => 'مقدار وارد شده صحیح نیست!',
                    'errors' => null
                ]); 
            }
                $check = substr($request['selectedPrice'], 0, 3);
                $product = Product::findOrFail($request['id']);
                $price = $product->price * $product->ratio;
                $count = $request['count'];
                $user = auth()->user();
                $category = $user->category;
                if ($request['selectedPrice'] == 'credit') {
                    
                    $max_credit = $category->max_credit;
                    $remainder_credit = $max_credit;
                    $price = $price + (($price * $category->percent) / 100);
                    $total_price = $price * $count;
                    
                    if($price * $count >= $remainder_credit){
                        
                    }
                    else{

                    }    

                    
                   
                }
                if ($check == 'day'){
                    $check = substr($request['selectedPrice'], 4);
                    $checkRules = $category->checkRules;

                    $check = $checkRules->where('term_days',$check)->first();
                    if ($check) {
                          
                        $price = $price + (($price * $check->percent) / 100);
                    }
                    
                }
               
                $all_request[] = [
                    'id'=> $request['id'],
                    'count'=> $request['count'],
                ];
                
                

                $color = $request['color'] ?? null;
                if ($color) {
                    $productColors = $product->colors;
                    if ($productColors){
                        $selectedColor = $productColors->where('color', $color)->first();
                        if ($selectedColor) {
                            $increasePrice = $selectedColor->price * $product->ratio;
                             $price += $increasePrice;
                           
                        }
                       
                    }
                    
                }
          
                $size = $request['size'] ?? null;
                if ($size) {
                    $productSizes = $product->sizes;
                    if ($productSizes) {
                        $selectedSize = $productSizes->where('size', $size)->first();
                        if ($selectedSize) {
                            $increasePrice = $selectedSize->price * $product->ratio;
                            $price += $increasePrice;

                        }

                    }

                }
                $brand = $request['brand'] ?? null;
               
                if ($brand) {
                    $productBrands = $product->brands;
                  
                    if ($productBrands) {
                        $selectedBrand = $productBrands->where('name', $brand)->first();
                      
                        if ($selectedBrand) {
                            $increasePrice = $selectedBrand->price * $product->ratio;
                            $price += $increasePrice;

                        }

                    }

                }
                $inventory = true;
                
                
                

                $discount = $product->activeOffer()['percent'];
                
                if ($discount > 0){
                     $price = $price * ((100 - $discount)/100);
                }
                if ($product->warehouseInventory < ($count * $product->ratio)) {
                    $inventory = false;
                   
            }
            $user_id = auth()->user()->id;
            $cart = Cart::firstOrCreate(
                ['user_id' => $user_id],
                ['count' => 0, 'total_price' => 0]
            );
            $ratio = $product->ratio;
            $existingProduct = $cart->products()->find($product->id);
            if ($inventory){
                $total_price_for_product = $count * $price;

                if ($existingProduct) {
                    $cart->products()->updateExistingPivot($product->id, [
                        'quantity' => $count,
                        'ratio' => $product->ratio,
                        'price' => $total_price_for_product, 
                        'color'=> $color ?? null,                      
                        'size'=> $size ?? null,
                        'brand' => $brand ?? null,
                        'product_price' => $price,
                        'pay_type'=> $request['selectedPrice']                      
                    ]);
                } else {
                    $cart->products()->attach($product->id, [
                        'quantity' => $count,
                        'ratio' => $product->ratio,
                        'price' => $total_price_for_product,
                        'color' => $color ?? null,
                        'size' => $size ?? null,
                        'brand' => $brand ?? null,
                        'product_price' => $price,
                        'pay_type' => $request['selectedPrice']

                    ]);
                }

            }
            else{
                if ($existingProduct) {
                    
                    // $cart->products()->attach($product->id, [
                    //     'quantity' => 0,
                    //     'price' => 0,
                    //     'inventory'=> 0   
                    // ]);
                    $cart->products()->updateExistingPivot($product->id, [
                        'inventory' => 0,
                       
                        // 'quantity'=>$request['count']
                    ]);
                }
                else{
                
                    $cart->products()->attach($product->id, [
                        'quantity' => 1,
                        'price' => $price,
                        'ratio' => $product->ratio,
                        'inventory' => 0,
                        'color' => $color ?? null,
                        'size' => $size ?? null,
                        'product_price' => $price,
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
        // DB::table('cart_product')->where('product_id', $product->id)->update([
        //     'inventory' => 1
        // ]);




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
       
        $credit_count = DB::table('cart_product')->where('pay_type','credit')->count();
        $cash_count = DB::table('cart_product')->where('pay_type','cash')->count();
        $check_count = DB::table('cart_product')->where('pay_type','LIKE','%day_%')->count();
        $credit_total_price = number_format(DB::table('cart_product')->where('pay_type', 'credit')->sum('price'));
        $cash_total_price = number_format(DB::table('cart_product')->where('pay_type', 'cash')->sum('price'));
        $result = DB::table('cart_product')->where('pay_type', 'LIKE', '%day_%')
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
                
                'discount' => $i['discount'],
                // 'alertMessage' => $i['alertMessage'],
            ];
        })->values();

        $amount = number_format($cart->total_price);

        return response()->json([
            'data' => 
                [
                    'id' => $cart->id,
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
