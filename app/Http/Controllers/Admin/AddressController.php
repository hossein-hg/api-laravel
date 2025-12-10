<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddressRequest;
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

    public function store(AddressRequest $request){
        
       
        $address = Address::create([
            'first_name'=> $request->firstName,
            'last_name'=> $request->lastName,
            'province'=> $request->province,
            'city'=> $request->city,
            'address'=> $request->address,
            'mobile'=> $request->mobile,
            'phone'=> $request->phone,
            'code'=> $request->postalCode,
            'email'=> $request->email,
            'description'=> $request->description,
            'user_id'=> auth()->user()->id

        ]);
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

    public function update(AddressRequest $request)
    {
        
        $address = Address::findOrFail($request->id);

        $address->update([
            'first_name' => $request->firstName,
            'last_name' => $request->lastName,
            'province' => $request->province,
            'city' => $request->city,
            'address' => $request->address,
            'mobile' => $request->mobile,
            'phone' => $request->phone,
            'code' => $request->postalCode,
            'email' => $request->email,
            'description' => $request->description,
            'user_id' => auth()->user()->id

        ]);
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
