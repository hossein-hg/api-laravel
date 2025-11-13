<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCollection;
use App\Models\Admin\Product;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public function index(){
        $products = Product::with('category:id,name,path','images','group','options')  // eager load relations
            ->paginate(perPage: 2);

        

        // $product->options()->attach(1, ['filter_id' => 1]);
      
        return new ProductCollection($products);
    }
}
