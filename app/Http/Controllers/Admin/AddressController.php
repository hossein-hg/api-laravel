<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Models\Admin\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(){
        $user = auth()->user();
        $addresses = Address::where('user_id',$user->id)->orderBy('id','desc')->get();
        return response()->json([
            'data'=> [
                'addresses'=> AddressResource::collection($addresses),
            ],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ]);

    }

    public function store(Request $request){
        $inputs = $request->all();
        $inputs['user_id'] = auth()->user()->id;
        $address = Address::create($inputs);
        if($address){
            return response()->json([
                'data' => null,
                'statusCode' => 200,
                'message' => 'موفقیت آمیز',
                'success' => true,
                'errors' => null,
            ]);
        }
    }

    public function update(Request $request)
    {
        $inputs = $request->all();
        $address = Address::findOrFail($inputs['id']);
       
        $inputs['user_id'] = auth()->user()->id;
        $address->update($inputs);
        if ($address) {
            return response()->json([
                'data' => null,
                'statusCode' => 200,
                'message' => 'موفقیت آمیز',
                'success' => true,
                'errors' => null,
            ]);
        }
    }

    public function delete(Request $request){
        $address = Address::findOrFail($request->id);
        $address->delete();
        return response()->json([
            'data' => null,
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ]);
    }



}
