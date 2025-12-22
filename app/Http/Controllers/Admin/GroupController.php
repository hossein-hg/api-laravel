<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\Brand;
use App\Models\Admin\Group;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\GroupResource;
use App\Http\Resources\GroupCollection;
use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class GroupController extends Controller
{
   public function index(){
       $groups = Group::with('children','brands','parent')->paginate(10);
    
        return new GroupCollection($groups);
   }

    public function all()
    {
        $groups = Group::whereNull('parent_id')->with('children', 'brands')->get();

        // return  GroupResource::collection($groups);
        return [
             'data' => [
                'results' => GroupResource::collection($groups),
                
            ],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ];
        
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
        if ($request->brands) {
            $brnads = Brand::where('group_id', $group->id)->delete();
            foreach ($request->brands as $brand) {
               
                    $brandObj = new Brand();
                    $brandObj->group_id = $group->id;
                    $brandObj->name = $brand['fa_name'];
                    $brandObj->en_name = $brand['en_name'];
                    $brandObj->save();
            }
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
        
        $group = Group::findOrFail( $request->id);
        $groups = Group::where('parent_id',$group->id)->delete();
        $brnads = Brand::where('group_id', $group->id)->delete();
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
        $group->load([
            'children.brands'
        ]);
        return response()->json([
            'data' => [
                'category' => new GroupResource($group),
            ],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
        ]);
    }

    public function uploadImage(Request $request){
        
        $validator = Validator::make($request->all(), [
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp'],
        ], [
            'image.required' => 'تصویر الزامی است.',
            'image.image' => 'فایل ارسالی باید تصویر باشد.',
            'image.mimes' => 'فرمت تصویر مجاز نیست.',
        ]);
        if ($validator->fails()) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => ' خطا اعتبارسنجی!',
                'statusCode' => 422,
                'errors' => [$validator->errors()->first()],
                'data' => null
            ], 422));
        }
            $image = $request->file('image');
            $mimeType = $image->getMimeType();
            $extension = explode('/', $mimeType)[1];

            $filename = Str::uuid() . '-' . $extension;
            $path = $image->move(public_path('images'), $filename);
            $relativePath = 'images' . DIRECTORY_SEPARATOR  . $filename;
            if (!$path) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 500,
                    'message' => 'ذخیره تصویر انجام نشد',
                   
                ], 500);
            }
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
