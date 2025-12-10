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
        if ($checkes->count() > 0) {
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

        }  
        else{
                $response = [
                    "day_90_percent"=> 0,
                    "day_90"=> false,
                    "day_30_percent"=> 0,
                    "day_30"=> false,
                    "day_45_percent"=> 0,
                    "day_45"=> false,
                    "day_60_percent"=> 0,
                    "day_60"=> false,
                    "day_75_percent"=> 0,
                    "day_75"=> false,
                    "day_120_percent"=> 0,
                    "day_120"=> false,
                    "day_180_percent"=> 0,
                    "day_180"=> false
                ];
        } 

        return array_merge([
            'id' => $this->id,
            'category_name' => $this->name,
            'max_credit' => number_format($this->max_credit),
            'percent' => $this->percent,
        ], $response);
    }
}
