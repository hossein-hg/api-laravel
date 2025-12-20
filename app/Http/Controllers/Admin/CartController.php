<?php

namespace App\Http\Controllers\Admin;
use DB;
use App\Models\Admin\Cart;
use Illuminate\Http\Request;
use App\Models\Admin\Product;
use App\Models\Admin\CartProduct;
use App\Models\Admin\CompanyStock;
use App\Http\Controllers\Controller;

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
            $warranty = $product->pivot->warranty;
            $sizes = $product->sizes->pluck('size');
            $colors = $product->colors->pluck('color');
            $brands = $product->sizes->pluck('name');
            $warranties = $product->warranties->pluck('name');
            return [
                "id" => $product->id,
                "faName" => $product->name,
                "en_name" => $product->en_name,
                "url" => $product->url,
                "price" => $product_price,
                "cover" => $product->cover,
                "count" => $count,
                "totalPrice" => (int) $price,
                "ratio" => $product->ratio,
                "color" => $color ?? null,
                "size" => $size ?? null,
                "brand" => $brand ?? null,
                "warranty" => $warranty ?? null,
                'selectedPrice' => $product->pivot->pay_type,
                "discount" => $product->activeOffer()['percent'] ?? 0,
                "brands"=> $brands,
                "colors"=> $colors,
                "sizes"=> $sizes,
                "warranties"=> $warranties,
            ];
        });

        $cardCount = $items->count();
        $cart->count = $cardCount;
        $cart->save();
        $itemsForOutput = $items->map(function ($i) {
            return [
                'id' => $i['id'],
                'faName' => $i['faName'],
                'en_name' => $i['en_name'],
                'url' => $i['url'],
                'price' => (string) number_format($i['price']),
                'cover' => $i['cover'],
                'count' => (int) $i['count'],
                'totalPrice' => (string) number_format($i['totalPrice']),
                'ratio' => $i['ratio'],
                'color' => $i['color'],
                'size' => $i['size'],
                'brand' => $i['brand'],
                'warranty' => $i['warranty'],
                'sizes' => $i['sizes'],
                'brands' => $i['brands'],
                'warranties' => $i['warranties'],
                'colors' => $i['colors'],
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
                $color = $request['color'] ?? null;
                $size = $request['size'] ?? null;
                $brand = $request['brand'] ?? null;
                $warranty = $request['warranty'] ?? null;
                $count = $request['count'];
        $product = Product::findOrFail($request['id']);
        $existingProductInCompany = CompanyStock::where('product_id', $product->id)
            ->where(function ($query) use ($color) {
                if ( $color != 'null') {
                    $query->where('color_code', $color);
                }
            })
            ->where(function ($query) use ($size) {
                if ($size != 'null' and $size != null) {

                    $query->where('size', $size);
                }
            })
            ->where(function ($query) use ($brand) {
                if ($brand != 'null' and $brand != null) {
                    $query->where('brand', $brand);
                }
            })
            ->where(function ($query) use ($warranty) {
                if ($warranty != 'null' and $warranty != null) {
                    $query->where('warranty', $warranty);
                }
            })
            ->first();
        $user_id = auth()->user()->id;
        $cart = Cart::firstOrCreate(
            ['user_id' => $user_id],
            ['count' => 0, 'total_price' => 0]
        );
        $existingProductInPivot = CartProduct::where('product_id', $product->id)->where('cart_id', $cart->id)
            ->where(function ($query) use ($color) {
                if ($color != 'null' and $color != null) {
                    $query->where('color', $color);
                } 
            })
            ->where(function ($query) use ($size) {
                if ($size != 'null' and $size != null) {

                    $query->where('size', $size);
                } 
            })
            ->where(function ($query) use ($brand) {
                if ($brand != 'null' and $brand != null) {
                    $query->where('brand', $brand);
                } 
            })
            ->where(function ($query) use ($warranty) {
                if ($warranty != 'null' and $warranty != null) {
                    $query->where('warranty', $warranty);
                } 
            })
            ->first();
            
            $inventory = true;
            $is_exist = false;
            
            $calc_price = price_calculate($product, $color, $brand, $size, $warranty, $request['selectedPrice'], $count);
      
            if ($existingProductInCompany){
         
                if ($existingProductInCompany->count < ($count * $product->ratio)) {
                    $inventory = false;
                    if ($existingProductInPivot) {
                        $count = $existingProductInPivot->quantity;
                    }
                }
               
                if($inventory){
                    if ($existingProductInPivot) {
                        $is_exist = true;

                        $existingProductInPivot->quantity = $count;

                        $existingProductInPivot->price = $calc_price['number_total_product_price'];
                        
                        $existingProductInPivot->color = $existingProductInCompany->color_code;
                        $existingProductInPivot->size = $existingProductInCompany->size;
                        $existingProductInPivot->brand = $existingProductInCompany->brand;
                        $existingProductInPivot->warranty = $existingProductInCompany->warranty;
                        $existingProductInPivot->product_price = $calc_price['number_one_product'];
                        $existingProductInPivot->pay_type = $request['selectedPrice'];
                        $existingProductInPivot->save();
                    } else {

                        $new_cart_order_record = new CartProduct();
                        $new_cart_order_record->cart_id = $cart->id;
                        $new_cart_order_record->quantity = $count;
                        $new_cart_order_record->ratio = $product->ratio;
                        $new_cart_order_record->price = $calc_price['number_total_product_price'];
                        $new_cart_order_record->color = $existingProductInCompany->color_code;
                        $new_cart_order_record->size = $existingProductInCompany->size;
                        $new_cart_order_record->brand = $existingProductInCompany->brand;
                        $new_cart_order_record->warranty = $existingProductInCompany->warranty;
                        $new_cart_order_record->product_price = $calc_price['number_one_product'];
                        $new_cart_order_record->pay_type = $request['selectedPrice'];
                        $new_cart_order_record->product_id = $product->id;
                        $new_cart_order_record->save();
                    
                    }
                }
                else{
                    if ($existingProductInPivot) {
                        $existingProductInPivot->inventory = 0;
                        $existingProductInPivot->pay_type = $request['selectedPrice'];
                        $existingProductInPivot->save();
                        $existingProductInPivot->refresh();
                     
                        $is_exist = true;
                        

                    }
                }


      
            }
            else{
                
                if ($product->warehouseInventory < ($count * $product->ratio)) {
                    $inventory = false;
                    if ($existingProductInPivot) {
                        $count = $existingProductInPivot->quantity;
                    }
                }
       

                if ($inventory) {
                    if ($existingProductInPivot) {
                        $is_exist = true;
                        $existingProductInPivot->quantity = $count;
                        $existingProductInPivot->price = $calc_price['number_total_product_price'];
                        $existingProductInPivot->product_price = $calc_price['number_one_product'];
                        $existingProductInPivot->pay_type = $request['selectedPrice'];
                        $existingProductInPivot->save();
                    } else {

                        $new_cart_order_record = new CartProduct();
                        $new_cart_order_record->cart_id = $cart->id;
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
                        $existingProductInPivot->inventory = 0;
                        $existingProductInPivot->pay_type = $request['selectedPrice'];
                        $existingProductInPivot->save();
                        $existingProductInPivot->refresh();

                        $is_exist = true;


                    }
                }

                
            }
           
            
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
        
        
        
        return  response()->json([
            'data'=>[
                'existInCart' => $is_exist,

                "alertMessage" => $inventory == 0 ? 'تعداد انتخابی بیشتر از تعداد موجود است' : '',
            ],
            'statusCode' => 200,
            'success' => true,
            'message' => 'موفقیت آمیز',
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

        // $cart->products()->detach($product->id);
        
        $color = $request->color;
        $size = $request->size;
        $brand = $request->brand;
        $warranty = $request->warranty;
        $existingProductInPivot = CartProduct::where('product_id', $product->id)->where('cart_id', $cart->id)
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
            ->where(function ($query) use ($warranty) {
                if (!is_null($warranty)) {
                    $query->where('warranty', $warranty);
                }
            })
            ->first();
        if ($existingProductInPivot){
            $existingProductInPivot->delete();
        }
        
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
        

        DB::table('cart_product')->where('cart_id', $cart->id)->where('inventory', 0)->delete();
        DB::table('cart_product')->where('cart_id', $cart->id)->where('quantity', '<>', 0)->update([
            'inventory' => 1
        ]);

        
        $result = DB::table('cart_product')->where('cart_id', $cart->id)->where('pay_type', 'LIKE', '%day_%')
            ->select('pay_type', DB::raw('COUNT(*) as total'), DB::raw('SUM(price) as total_price'))
            ->groupBy('pay_type')
            ->get();

        



        // $checksCount = 
        
        
        // آماده سازی خروجی محصولات
        $items = $cart->products->map(function ($product) {
            $count = $product->pivot->quantity;
            $price = $product->pivot->price;
            $color = $product->pivot->color;
            $size = $product->pivot->size;
            return [
                "id" => $product->id,
                "faName" => $product->name,
                "en_name" => $product->en_name,
                "url" => $product->url,
                "price" => (int) $price,
                "cover" => $product->cover,
                "count" => $count,
                "totalPrice" => (int) $price,
                "ratio" => $product->ratio,
                "color" => $color ?? null,
                "size" => $size ?? null,
                "discount" => $product->activeOffer()['percent'] ?? 0,
                'selectedPrice' => $product->pivot->pay_type,
            ];
        });

        $cardCount = $items->count();
       
        $cart->count = $cardCount;
        $cart->save();
        

        return response()->json([
            'data' => 
                []
                ,
              'message' => 'محصول حذف شد',
              'statusCode' => 200,
                'success' => true,
                'errors' => null
        ]);



    }
}
