<?php

namespace App\Http\Resources;

use App\Models\Admin\Brand;
use App\Models\Admin\Category;
use App\Models\Admin\Group;
use App\Models\Admin\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $minPrice = Product::min('price');
        $maxPrice = Product::max('price');

        return [
            'data' => [
                'results'=> ProductListResource::collection($this->collection),
                'hasPrevPage' => !$this->onFirstPage(), 
                'hasNextPage' => $this->hasMorePages(), 
                'page' => $this->currentPage(), 
                'total_page' => $this->lastPage(), 
               
                'total_products' => $this->total(), 
                'categories'=> CategoryResource::collection(Group::all()),
                'price'=> [
                    'min_price'=> (int)$minPrice,
                    'max_price'=> (int)$maxPrice,
                ],
                'brands'=> BrandResource::collection(Brand::all()),
            ],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ];
    }

    public function paginationInformation($request, $paginated, $default)
    {
        unset($default['links']);
        unset($default['meta']);

        return $default;
    }
}
