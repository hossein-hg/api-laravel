<?php

namespace App\Http\Resources;
use Illuminate\Http\Request;
use App\Models\Admin\Product;
use App\Models\Admin\CompanyStock;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    
    public static $wrap = null;
    public function toArray(Request $request): array
    {
        
        $request_color = request()->query('color');
        $request_size = request()->query('size');
        $request_brand = request()->query('brand');
        $request_warranty = request()->query('warranty');
        $request_feature = request()->query('feature');
        $all_company = CompanyStock::where('product_id', $this->id)->get();
        // dd($request_color, $request_size, $request_brand);
        $query = CompanyStock::where('product_id', $this->id);

        if ($request_brand) {
            $query->where('brand', $request_brand);
        }

        if ($request_size) {
            $query->where('size', $request_size);
        }

        if ($request_color) {
            $query->where('color_code', $request_color);
        }

        if ($request_warranty) {
            $query->where('warranty', $request_warranty);
        }
        $inventory = $this->inventory;
        if ($this->type == 1){


        $companyStockFirst = CompanyStock::where('product_id', $this->id)->first();

        // dd($companyStockFirst);
        $existingProductInCompany = CompanyStock::where('product_id', $this->id)
            ->where(function ($query) use ($request_color) {
                if (!is_null($request_color)) {
                    $query->where('color_code', $request_color);
                }
            })
            ->where(function ($query) use ($request_size) {
                if (!is_null($request_size)) {

                    $query->where('size', $request_size);
                }
            })
            ->where(function ($query) use ($request_brand) {
                if (!is_null($request_brand)) {
                    $query->where('brand', $request_brand);
                }
            })
            ->first();
            if ($existingProductInCompany) {
                $color_name =  $existingProductInCompany->color_code ?? null;
                $brand_name = $existingProductInCompany->brand ?? null;
                $size_name =  $existingProductInCompany->size ?? null;
                $warranty_name =  $existingProductInCompany->warranty ?? null;
            }
            else{
                $color_name = $companyStockFirst ? $companyStockFirst->color_code : null;
                $brand_name = $companyStockFirst ? $companyStockFirst->brand : null;
                $size_name = $companyStockFirst ? $companyStockFirst->size : null;
                $warranty_name =  $existingProductInCompany->warranty ?? null;
            }
            
        
        
        $brands = 
        // $request_brand 
        // ?
        //  $query->distinct()->pluck('brand')->values()->toArray()
        //  :
          $all_company->pluck('brand')->unique()->toArray();
        // $colorsExists = CompanyStock::where('brand', $request_brand)->get();
        if ($request_brand){
                $brandObj = CompanyStock::where('brand',$request_brand)->value('brand');
                // dd($brandObj);
        }
        else{
                $brandObj = CompanyStock::where('brand', $brand_name)->value('brand');
        }
        $new_query = CompanyStock::where('product_id',$this->id);
        $colors = array_filter($new_query->where('product_id',$this->id)->where('brand',$brandObj)->distinct()->pluck('color_code')->values()->toArray());
        
        $sizes = array_filter($new_query->where('product_id',$this->id)->where('brand',$brandObj)->distinct()->pluck('size')->values()->toArray());
        $warranties = array_filter($new_query->where('product_id',$this->id)->where('brand',$brandObj)->distinct()->pluck('warranty')->values()->toArray());
       
            $count_company = $existingProductInCompany ? $existingProductInCompany->count() : 0;
            
            if ($this->type == 1 and $count_company == 0) {
                $inventory = 0;
            }
        } 
        $group = $this->group;
        $brandsAll = null;
        if ($group){
            $brandsAll = $group->brands();
        }
     
        $price_calculate = price_calculate($this,$request_color,$request_brand,$request_size);
        if ($request_feature){
            return [
                'id' => $this->id,
                'name' => trim($this->name),
                'brands'=> $brandsAll ? $brandsAll->get(['name','id']): null,
                'price' => $price_calculate['prices'],
                'oldPrice' => $price_calculate['oldPrices'],
            ];
        }
      
        
        $images = [];
        $parent = $group ? $group->parent : null;
        if ($this->images){
                foreach ($this->images as $image){
                    $images[] = [
                        'url'=> $image->path,
                        'delete'=> false,
                    ];
                }
        }
        
        $parent_id = $parent ? $parent->id : null;
        return [
            'id' => $this->id,
            'name' => trim($this->name),
            'en_name' => trim($this->en_name),
            'price' => $price_calculate['prices'],
            'oldPrice' => $price_calculate['oldPrices'],
            'cover' => $this->cover,
            'url' => $this->url,
            'inventory' => $inventory,
            'shortDescription' => trim($this->shortDescription),
            'description' => trim($this->description),  
            'salesCount'=> $this->salesCount,
            'countDown' => $this->activeOffer()['countDown'],
            'discount_start_time' => $this->offer ? $this->offer->start_time : null,
            'discount_end_time' => $this->offer ? $this->offer->end_time : null,
            'discount_percent' => $this->offer ? $this->offer->percent : 0,
            'warehouseInventory'=> $this->warehouseInventory,
            'satisfaction' => (int) $this->satisfaction,
            'additionalInformation'=> $this->additionalInformation,
            'images' => $this->images ? $images : null, 
            'categoryName' => $this->group ? trim($this->group->name) : null,
            'categoryPath' => $this->group ? trim($this->group->name) : null,
            'subCategory_id' => $this->group ? $this->group->id : null,
            'category_id' => $parent_id,
            'stars'=>$this->stars,
            'discount' => $this->activeOffer()['percent'],
            'tags' => $this->tags,
            'features' => $this->filtersWithSelectedOptions(),
            'ratio'=>$this->ratio,
            'comments'=> $this->comments->pluck('body'),
            'max_sell'=> $this->max_sell,
            'type'=> $this->type,
            'sizes'=> $sizes ?? null,
            'colors'=> $colors ?? null,
            'warranties'=> $warranties ?? null,
            'brands'=> $brands ?? null,
            'commentsCount'=>$this->comments()->count(),
            'related_products'=>Product::where('group_id',$this->group_id)->get()->except($this->id),
            'update' => $this->updated_at->format('Y-m-d H:i:s'),
            'defaults'=> ['color'=> $color_name ?? null, 'size'=> $size_name ?? null, 'brand'=> $brand_name ?? null, 'warranty'=> $warranty_name ?? null]
        ];
    }
}
