<?php

namespace App\Http\Resources;

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
        return [
            'data' => [
                'results'=> ProductListResource::collection($this->collection),
                'hasPrevPage' => !$this->onFirstPage(),  // true اگر صفحه قبلی وجود داشته باشه
                'hasNextPage' => $this->hasMorePages(),  // true اگر صفحه بعدی وجود داشته باشه
                'page' => $this->currentPage(),  // current page (duplicate با current_page؟ اگر نه، تغییر بده)
                'total_page' => $this->lastPage(),  // کل صفحات
                'current_page' => $this->currentPage(),  // صفحه فعلی
                'total_products' => $this->total(),  // کل محصولات
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
