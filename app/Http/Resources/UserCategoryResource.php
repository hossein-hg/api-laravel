<?php

namespace App\Http\Resources;

use App\Models\Admin\CheckRules;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $checkes = $this->checkRules;
        $terms = [30 => 30, 45=> 45, 60=> 60, 75=> 75, 90=> 90, 120=> 120, 180=> 180];
        $allCheckes = CheckRules::all();
        $response = [];
        // dd($checkes,$this->id);
        foreach ($checkes as $key1 =>$check) {
            $key = "day_{$check->term_days}";
            $percentKey = "day_{$check->term_days}_percent";
            $daysKey = "day_{$check->term_days}";
            $response[$percentKey] = $check->percent;
            $response[$daysKey] = true;
            
            foreach ($terms as $key => $term) {
                
                if ($check->term_days != $term){
                    
                    $percentKey = "day_{$term}_percent";
                    if(!isset($response[$percentKey])){
                            $response[$percentKey] = 0;
                    }
                    
                    $daysKey = "day_{$term}";
                    if (!isset($response[$daysKey])) {
                        $response[$daysKey] = false;
                    }
                    
                }
            }
        }



        return array_merge([
            'category_name' => $this->name,
            'max_credit' => number_format($this->max_credit),
            'percent' => $this->percent,
        ], $response);
    }
}
