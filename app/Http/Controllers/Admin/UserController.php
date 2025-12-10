<?php

namespace App\Http\Controllers\Admin;
use App\Http\Requests\AddUserRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query();
        if ($request->filled('name')) {
            $name =  $request->input('name');
              
            $query->where('name', 'LIKE', "%".$name."%");
        }
        if ($request->filled('phone')) {
            $name = $request->input('phone');

            $query->where('phone', 'LIKE', "%" . $name . "%");
        }
        if ($request->filled('telephone')) {
            $name = $request->input('telephone');

            $query->where('telephone', 'LIKE', "%" . $name . "%");
        }
        if ($request->filled('gender')) {
            $name = $request->input('gender');

            $query->where('gender', 'LIKE', "%" . $name . "%");
        }
        if ($request->filled('user_type')) {
            $name = $request->input('user_type');

            $query->where('user_type', 'LIKE', "%" . $name . "%");
        }
        if ($request->filled('company_name')) {
            $name = $request->input('company_name');

            $query->where('company_name', 'LIKE', "%" . $name . "%");
        }
        if ($request->filled('natinal_code')) {
            $name = $request->input('natinal_code');

            $query->where('natinal_code', 'LIKE', "%" . $name . "%");
        }
        if ($request->filled('economic_code')) {
            $name = $request->input('economic_code');

            $query->where('economic_code', 'LIKE', "%" . $name . "%");
        }
        if ($request->filled('registration_number')) {
            $name = $request->input('registration_number');

            $query->where('registration_number', 'LIKE', "%" . $name . "%");
        }
        if ($request->filled('is_active')) {
            $name = $request->input('is_active');

            $query->where('is_active', 'LIKE', "%" . $name . "%");
        }

        if ($request->filled('category')) {
        
            $query->whereHas('group', function ($q) use ($request) {
                $name =$request->category;
                $q->where('name', 'LIKE', "%" . $name . "%");
            });
        }

        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'name':
                    $query->orderBy('name', 'desc'); // جدیدترین
                    break;
                case 'phone':
                    $query->orderBy('phone', 'desc'); // قیمت صعودی
                    break;
                case 'telephone':
                    $query->orderBy('telephone', 'asc'); // قیمت نزولی
                    break;
                case 'gender':
                    $query->orderBy('gender', 'asc'); //  
                    break;
                case 'user_type':
                    $query->orderBy('user_type', 'desc'); // قیمت نزولی
                    break;
                case 'company_name':
                    $query->orderBy('company_name', 'desc'); // قیمت نزولی
                    break;
                
                case 'natinal_code':
                    $query->orderBy('natinal_code', 'desc'); // قیمت نزولی
                    break;
                case 'economic_code':
                    $query->orderBy('economic_code', 'desc'); // قیمت نزولی
                    break;
                case 'registration_number':
                    $query->orderBy('registration_number', 'desc'); // قیمت نزولی
                    break;
               
                

                default:
                    $query->latest(); // پیش‌فرض: جدیدترین
            }
        } else {
            $query->latest(); // پیش‌فرض
        }

        $users = $query->paginate(10);
        return new UserCollection($users);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AddUserRequest $request)
    {
        $user = User::create([
            'name'=> $request->name,
            'phone'=> $request->phone,
            'telephone'=> $request->telephone,
            'gender'=> $request->gender,
            'category_id'=> $request->category_id,
            'user_type'=> $request->user_type,
            'company_name'=> $request->company_name,
            'national_code'=> $request->national_code,
            'economic_code'=> $request->economic_code,
            'registration_number'=> $request->registration_number,
        ]);
        return response()->json([
            'data' => null,
            'statusCode' => 200,
            'success' => true,
            'message' => 'موفقیت آمیز',
            'errors' => null
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
       
        return response()->json([
            'data'=> [
                'user'=> new UserResource($user),
            ],
            'statusCode' => 200,
            'message' => 'موفقیت آمیز',
            'success' => true,
            'errors' => null,
            ]);
       
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AddUserRequest $request)
    {
        $user = User::findOrFail($request->id);

        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'telephone' => $request->telephone,
            'gender' => $request->gender,
            'category_id' => $request->category_id,
            'user_type' => $request->user_type,
            'company_name' => $request->company_name,
            'national_code' => $request->national_code,
            'economic_code' => $request->economic_code,
            'registration_number' => $request->registration_number,
            'is_active' => $request->is_active,
        ]);
        return response()->json([
            'data' => null,
            'statusCode' => 200,
            'success' => true,
            'message' => 'موفقیت آمیز',
            'errors' => null
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
       
        $user = User::findOrFail(request()->id);
        $user->delete();
        return response()->json([
            'data' => null,
            'statusCode' => 200,
            'success' => true,
            'message' => 'موفقیت آمیز',
            'errors' => null
        ]);
    }
}
