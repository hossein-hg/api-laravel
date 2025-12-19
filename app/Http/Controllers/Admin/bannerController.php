<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BannerRequest;
use App\Http\Resources\BannerResource;
use App\Models\Admin\Banner;
use Illuminate\Http\Request;

class bannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $banners = Banner::all();
        return response()->json([
            'data'=> [
                "results"=> BannerResource::collection($banners),
                'statusCode' => 200,
                'success' => true,
                'message' => 'موفقیت آمیز',
                'errors' => null
            ],

        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BannerRequest $request)
    {
        $banner = new Banner();
        $banner->name = $request->name;
        $banner->link = $request->link;
        $banner->type = $request->type;
        $banner->showtime = $request->showTime;
        $banner->image = $request->image;
        $banner->save();
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
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
       
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BannerRequest $request)
    {
        $banner = Banner::findOrFail($request->id);
        $banner->name = $request->name;
        $banner->link = $request->link;
        $banner->type = $request->type;
        $banner->showtime = $request->showTime;
        $banner->image = $request->image;
        $banner->save();
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
    public function delete()
    {
        $banner = Banner::findOrFail(request()->id);
        $banner->delete();
        return response()->json([
            'data' => null,
            'statusCode' => 200,
            'success' => true,
            'message' => 'موفقیت آمیز',
            'errors' => null
        ]);
    }
}
