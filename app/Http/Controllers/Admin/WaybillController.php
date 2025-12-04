<?php

namespace App\Http\Controllers\Admin;
use App\Models\Admin\Waybill;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WaybillController extends Controller
{
    public function store(Request $request){
        $input = $request->all();
        $input['code'] = $request->order_id;
        $waybill = Waybill::create($input);
        
        if($waybill){
            return response()->json([
                'data' => null,
                'statusCode' => 200,
                'success' => true,
                'message' => 'موفقیت آمیز',
                'errors' => null
            ]);
        }
        else{
            return response()->json([
                'data' => null,
                'statusCode' => 500,
                'success' => false,
                'message' => 'خطا',
                'errors' => null
            ]);
        }
    }
}
