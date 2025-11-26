<?php

namespace App\Http\Resources;
use App\Models\Admin\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = auth()->user();
        $price = (int) $this->price;
        $price = $this->price * $this->ratio;
        if ($user) {

            $category = $user->category;

            $checkes = $category->checkRules;
            $checksList = [];

            foreach ($checkes as $item) {

                if ($item) {
                    $checksList['day_' . $item->term_days] = $price + (($price * $item->percent) / 100);

                    $checksListOld['day_' . $item->term_days] = $this->activeOffer()['percent'] > 0 ? $price + (($price * $item->percent) / 100) : 0;
                    $checksListOld['day_' . $item->term_days] = number_format($checksListOld['day_' . $item->term_days]);
                    $checksList['day_' . $item->term_days] = $this->activeOffer()['percent'] > 0 ? $checksList['day_' . $item->term_days] * ((100 - $this->activeOffer()['percent']) / 100) : $checksList['day_' . $item->term_days];
                    $checksList['day_' . $item->term_days] = number_format($checksList['day_' . $item->term_days]);
                }
            }

            $prices = [];
            $prices['cash'] = $price;
            $oldPrices['cash'] = $this->activeOffer()['percent'] > 0 ? $price : 0;
            $prices['cash'] = $this->activeOffer()['percent'] > 0 ? $price * ((100 - $this->activeOffer()['percent']) / 100) : $price;
            $prices['credit'] = $price + (($price * $category->percent) / 100);
            $oldPrices['credit'] = $this->activeOffer()['percent'] > 0 ? $price + (($price * $category->percent) / 100) : 0;
            $prices['credit'] = $prices['cash'] + (($prices['cash'] * $category->percent) / 100);
            $prices['checkes'] = $checksList;
            $oldPrices['checkes'] = $checksListOld;
            $prices['credit'] = number_format($prices['credit']);
            $oldPrices['credit'] = number_format($oldPrices['credit']);

        } else {
            $prices['cash'] = number_format($price);
            $oldPrices['cash'] = $this->activeOffer()['percent'] > 0 ? $price : 0;

            $prices['cash'] = $this->activeOffer()['percent'] > 0 ? $price * ((100 - $this->activeOffer()['percent']) / 100) : $price;

        }

        $cash = $this->price;
        $cash = number_format($cash);
        $price = (int) $this->price;

        // $price = $this->activeOffer()['percent'] > 0 ? $price * ((100 - $this->activeOffer()['percent']) / 100) : $price;



        $price = (string) number_format($price);



        $prices['cash'] = number_format($prices['cash']);


        $oldPrices['cash'] = number_format($oldPrices['cash']);

        return [
            'id' => $this->id,
            'name' => trim($this->name),
            'price' => $price,
            'oldPrice' => $price,
            'cover' => $this->cover,
            'url' => $this->url,
            'inventory' => $this->inventory,
            'shortDescription' => trim($this->shortDescription),
            'description' => trim($this->description),
            'salesCount' => $this->salesCount,
            'countDown' => $this->activeOffer()['countDown'],
            'warehouseInventory' => $this->warehouseInventory,
            'satisfaction' => (int) $this->satisfaction,
            'additionalInformation' => trim($this->additionalInformation),
            'images' => $this->whenLoaded('images', fn() => $this->images->pluck('path')),
            'categoryName' => $this->whenLoaded('group', fn() => trim($this->group->name)),
            'categoryPath' => $this->whenLoaded('group', fn() => trim($this->group->name)),
            'stars' => $this->stars,
            'discount' => $this->activeOffer()['percent'],
            'tags' => $this->tags,
            'features' => $this->filtersWithSelectedOptions(),
            'ratio' => $this->ratio,
            'comments' => $this->whenLoaded('comments', fn() => $this->comments->pluck('body')),
            'warranties' => $this->whenLoaded('warranties', fn() => $this->warranties->pluck('name')),
            'sizes' => $this->whenLoaded('sizes', fn() => $this->sizes->pluck('size')),
            'colors' => $this->whenLoaded('colors', fn() => $this->colors->pluck('color')),
            'brands' => $this->whenLoaded('brands', fn() => $this->brands->pluck('name')),
            'commentsCount' => $this->whenLoaded('comments', fn(): mixed => $this->comments()->count()),
            'related_products' => Product::where('group_id', $this->group_id)->get()->except($this->id)->pluck('name'),
            'update' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
