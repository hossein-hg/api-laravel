<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Models\Admin\Brand;
use App\Models\Admin\Group;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\GroupResource;
use App\Http\Resources\GroupCollection;

class GroupController extends Controller
{
   public function index(){
       $groups = Group::whereNull('parent_id')->get();
        return new GroupCollection($groups);
   }

   public function store(StoreGroupRequest $request){
        $group = new Group();
        $group->image = $request->image;
        $group->name = $request->name;
        $group->parent_id = $request->parent_id;
        $group->url = $request->name;
        if($request->parent){
            $group->level = 2;

        }
        else{
            $group->level = 1;
        }
        $group->save();
        if ($request->brands){
            foreach ($request->brands as $brand){
                $brandObj = new Brand();
                $brandObj->group_id = $group->id;
              
                $brandObj->name = $brand['fa_name'];
                $brandObj->en_name = $brand['en_name'];
                $brandObj->save();

                
            }
        }
        return [
                'data' => null,
                'statusCode' => 200,
                'message' => 'موفقیت آمیز',
                'success' => true,
                'errors' => null,
        ];
    
   }


    public function update(UpdateGroupRequest $request)
    {
       
        $group = Group::findOrFail( $request->id );
        if ($request->filled('image')) {
            $group->image = $request->image;
        }
        $group->name = $request->name;
        $group->parent_id = $request->parent_id;
        $group->url = $request->name;
       
        if ($request->parent) {
            $group->level = 2;
        } else {
            $group->level = 1;
        }

        $group->save();
        return [
            'data' => null,
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ];

    }

    public function destroy(Request $request){
        $group = Group::findOrFail( $request->id );
        $group->delete();
        return [
            'data' => null,
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ];

    }

    public function show(Group $group){
        return response()->json([
            'data' => [
                'user' => new GroupResource($group),
            ],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ]);
    }

    public function uploadImage(Request $request){
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp'],
        ]);
        
            $image = $request->file('image');
            $mimeType = $image->getMimeType();
            $extension = explode('/', $mimeType)[1];

            $filename = Str::uuid() . '-' . $extension;
            $path = $image->move(public_path('images'), $filename);
            $relativePath = 'images' . DIRECTORY_SEPARATOR  . $filename;

            return response()->json([

                'data' => [
                    'url' => 'https://files.epyc.ir/' . $relativePath,
                    
                ],
                'statusCode' => 200,
                'success' => true,
                'message' => 'موفقیت آمیز',
                'errors' => null
            ]);
        
    }

}
