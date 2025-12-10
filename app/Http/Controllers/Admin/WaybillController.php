<?php

namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
use App\Models\Admin\RoleLog;
use App\Models\Admin\Waybill;
use App\Http\Controllers\Controller;
use App\Http\Requests\WaybillRequest;

class WaybillController extends Controller
{
    public function store(WaybillRequest $request){
       
        $input = $request->all();
        $input['code'] = $request->order_id;
        $waybill = Waybill::create($input);
        
        if($waybill){
            $user = auth()->user();
            $roleLog = new RoleLog();
            $roleLog->ip = $request->ip();
            $roleLog->user_id = $user->id;
            $userRole = $user->getJWTCustomClaims()['role'] ?? null;
            $roleLog->role = $userRole;
            $roleLog->loggable_type = "App\Models\Admin\Waybill";
            $roleLog->loggable_id = $request->order_id;
            $roleLog->description = "برای سفارش با آیدی " . $request->order_id . " حواله ثبت شد";
            $roleLog->save();
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
