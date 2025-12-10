<?php

namespace App\Http\Controllers\Admin;
use App\Models\Admin\CheckRules;
use App\Models\Admin\Group;
use DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserCategoryCollection;
use App\Models\Admin\UserCategory;
use Illuminate\Http\Request;

class UserCategoryController extends Controller
{
    public function index(){
        
        $query = UserCategory::query()->with('checkRules');
        $userCategories = $query->paginate(10);
       
        return new UserCategoryCollection($userCategories);
    }

    public function store(Request $request){
        
        $trans = DB::transaction(function () use ($request) {
            $count = UserCategory::count() + 1;
            $count = (string) $count;
            $category = UserCategory::create([
                'name' => $count,
                'max_credit' => $request->max_credit,
                'percent' => $request->percent,
            ]);
            
            
            
            $checkes = $request->checks;

            foreach ($checkes as $key => $value) {
                $values = array_values($value);
                
                $keys = array_keys($value);
                
                $firstValue = $keys[1];
                
                
                switch ($firstValue) {
                    case 'day_30_percent':
                        
                        if ($values[0]) {
                            $check_rule = new CheckRules();
                            $check_rule->category_user_id = $category->id;
                            $check_rule->percent = $values[1];
                            $check_rule->term_days = 30;
                            $check_rule->name = "30 روزه";
                            $check_rule->save();

                        }
                    case 'day_45_percent':
                           
                            if($values[0]){
                            $check_rule = new CheckRules();
                                $check_rule->category_user_id = $category->id;
                                $check_rule->percent = $values[1];
                                $check_rule->term_days = 45;
                                $check_rule->name = "45 روزه";
                                $check_rule->save();
                            
                            }
                            break;
                       
                    case 'day_60_percent':
                           
                        if ($values[0]) {
                            
                            $check_rule = new CheckRules();
                            $check_rule->category_user_id = $category->id;
                            $check_rule->percent = $values[1];
                            $check_rule->term_days = 60;
                            $check_rule->name = "60 روزه";
                            $check_rule->save();

                        }
                        break;
                    case 'day_75_percent':
                           
                        if ($values[0]) {
                           
                            $check_rule = new CheckRules();
                            $check_rule->category_user_id = $category->id;
                            $check_rule->percent = $values[1];
                            $check_rule->term_days = 75;
                            $check_rule->name = "75 روزه";
                            $check_rule->save();
                     

                        }
                        break;
                    case 'day_90_percent':
                       
                        if ($values[0]) {
                            $check_rule = new CheckRules();
                            $check_rule->category_user_id = $category->id;
                            $check_rule->percent = $values[1];
                            $check_rule->term_days = 90;
                            $check_rule->name = "90 روزه";
                            $check_rule->save();

                        }
                        break;
                    case 'day_120_percent':

                        if ($values[0]) {
                            $check_rule->category_user_id = $category->id;
                            $check_rule->percent = $values[1];
                            $check_rule->name = "120 روزه";
                            $check_rule->term_days = 120;
                            $check_rule->save();

                        }
                    
                    case 'day_180_percent':

                        if ($values[0]) {
                            $check_rule->category_user_id = $category->id;
                            $check_rule->percent = $values[1];
                            $check_rule->term_days = 180;
                            $check_rule->name = "180 روزه";
                            $check_rule->save();

                        }
                        break;

                    

                   
                }
            }
           


        });


            return response()->json([
                'data' => null,
                'statusCode' => 200,
                'success' => true,
                'message' => 'موفقیت آمیز',
                'errors' => null
            ]);       
        
        
       


       
    }


    public function update(Request $request){
        $trans = DB::transaction(function () use ($request) {
            $category = UserCategory::findOrFail($request->id);
            $category->update([
                'name' => $request->category_name,
                'max_credit' => $request->max_credit,
                'percent' => $request->percent,
            ]);



            $checkes = $request->checks;

            foreach ($checkes as $key => $value) {
                $values = array_values($value);

                $keys = array_keys($value);

                $firstValue = $keys[1];


                switch ($firstValue) {
                    case 'day_30_percent':
                        $check_rule = CheckRules::where('category_user_id', $category->id)->where('term_days', '30')->first();
                        if ($values[0]) {
                           
                            if (!$check_rule) {
                                $check_rule = new CheckRules();   
                            }
                            $check_rule->category_user_id = $category->id;
                            $check_rule->percent = $values[1];
                            $check_rule->term_days = 30;
                            $check_rule->name = "30 روزه";
                            $check_rule->save();

                        }
                        else{
                            if($check_rule){
                                $check_rule->delete();
                            }
                            
                        }
                    case 'day_45_percent':
                        $check_rule = CheckRules::where('category_user_id', $category->id)->where('term_days', '45')->first();
                        if ($values[0]) {
                           
                            if (!$check_rule) {
                                $check_rule = new CheckRules();
                            }
                            $check_rule->category_user_id = $category->id;
                            $check_rule->percent = $values[1];
                            $check_rule->term_days = 45;
                            $check_rule->name = "45 روزه";
                            $check_rule->save();

                        } else {
                            if ($check_rule) {
                                $check_rule->delete();
                            }
                        }
                        break;

                    case 'day_60_percent':
                        
                        $check_rule = CheckRules::where('category_user_id', $category->id)->where('term_days', '60')->first();
                        if ($values[0]) {
                            
                            if (!$check_rule) {
                                $check_rule = new CheckRules();
                            }
                            $check_rule->category_user_id = $category->id;
                            $check_rule->percent = $values[1];
                            $check_rule->term_days = 60;
                            $check_rule->name = "60 روزه";
                            $check_rule->save();

                        } else {
                            if ($check_rule) {
                                $check_rule->delete();
                            }
                        }
                        break;
                    case 'day_75_percent':
                        $check_rule = CheckRules::where('category_user_id', $category->id)->where('term_days', '75')->first();
                        if ($values[0]) {
                            
                            if (!$check_rule) {
                                $check_rule = new CheckRules();
                            }
                            $check_rule->category_user_id = $category->id;
                            $check_rule->percent = $values[1];
                            $check_rule->term_days = 75;
                            $check_rule->name = "75 روزه";
                            $check_rule->save();


                        } else {
                            if ($check_rule) {
                                $check_rule->delete();
                            }
                        }
                        break;
                    case 'day_90_percent':
                        $check_rule = CheckRules::where('category_user_id', $category->id)->where('term_days', '90')->first();
                        if ($values[0]) {
                            
                            
                            if (!$check_rule) {
                                $check_rule = new CheckRules();
                            }
                            $check_rule->category_user_id = $category->id;
                            $check_rule->percent = $values[1];
                            $check_rule->term_days = 90;
                            $check_rule->name = "90 روزه";
                            $check_rule->save();

                        } else {
                            if ($check_rule) {
                                $check_rule->delete();
                            }
                        }
                        break;
                    case 'day_120_percent':
                        $check_rule = CheckRules::where('category_user_id', $category->id)->where('term_days', '120')->first();
                        if ($values[0]) {
                           
                            
                           
                            if (!$check_rule) {
                                $check_rule = new CheckRules();
                            }
                            $check_rule->category_user_id = $category->id;
                            $check_rule->percent = $values[1];
                            $check_rule->name = "120 روزه";
                            $check_rule->term_days = 120;
                            $check_rule->save();

                        } else {
                            if ($check_rule) {
                                $check_rule->delete();
                            }
                        }
                        break;
                    case 'day_180_percent':
                        $check_rule = CheckRules::where('category_user_id', $category->id)->where('term_days', '180')->first();
                        if ($values[0]) {
                            
                            if (!$check_rule) {
                                $check_rule = new CheckRules();
                            }
                            $check_rule->category_user_id = $category->id;
                            $check_rule->percent = $values[1];
                            $check_rule->term_days = 180;
                            $check_rule->name = "180 روزه";
                            $check_rule->save();

                        } else {
                            if ($check_rule) {
                                $check_rule->delete();
                            }
                        }
                        break;

                }
            }
        });

        
            return response()->json([
                'data' => null,
                'statusCode' => 200,
                'success' => true,
                'message' => 'موفقیت آمیز',
                'errors' => null
            ]);

       

    }


    public function delete(Request $request){
        $category = UserCategory::findOrFail($request->id);
        $category->checkRules()->delete();
        $delete = $category->delete();
        
            return response()->json([
                'data' => null,
                'statusCode' => 200,
                'success' => true,
                'message' => 'موفقیت آمیز',
                'errors' => null
            ]);

       
    }
}
