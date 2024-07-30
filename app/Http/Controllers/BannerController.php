<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {

        //
        $bannerQuery = Banner::query();
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $bannerQuery->where('banner_title', 'like', '%' . $searchTerm . '%');
        }
        if ($request->has('sort_by') && $request->has('sort_direction')) {
            $sortBy = $request->input('sort_by');
            $sortDirection = $request->input('sort_direction');
        }
        if (in_array($sortBy, ['created_at', 'status']) && in_array($sortDirection, ['asc', 'desc'])) {
            $bannerQuery->orderBy($sortBy, $sortDirection);
        }
        $perpage = $request->input('perpage', 10);
        $banner = $bannerQuery->paginate($perpage);
        $totalPages = ceil($banner->total() / $banner->perPage());
        return response()->json([
            'total_blogs' => $banner->total(),
            'current_page' => $banner->currentPage(),
            'per_page' => $banner->perPage(),
            'total_pages' => $totalPages,
            'last_page' => $banner->lastPage(),
            'next_page_url' => $banner->nextPageUrl(),
            'prev_page_url' => $banner->previousPageUrl(),
            'data' => $banner->items(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        //
        $validate = [
            'banner_title' => 'required|string|unique:banners,banner_title',
            'banner_des' => 'string|nullable',
            'banner_image' => 'file|nullable',
            'sort' => 'nullable',
            'status' => 'nullable|string|in:active,inactive',
        ];
        // kiem tra du lieu input -> validate
        $validator = Validator::make($request->all(), $validate);
        // thong bao neu du lieu khong hop le
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        // ten thu muc luu tru du an
        $path = 'img';
        if ($request->hasFile('banner_image')) {
            $get_image = $request->file('banner_image');
            $new_image = $get_image->getClientOriginalName();
            // kiem tra file da ton tai trong thu muc hay chua
            if (!File::exists(public_path($path . '/' . $new_image))) {
                // chua ton tai -> uploads hinh anh vao thu muc du an
                $get_image->move(public_path($path), $new_image);
            }
            // thiet lap duong dan hinh anh == http://127.0.0.1:8000/img/tenhinhanh
            $banner_image = url($path . '/' . $new_image);
        } else {
            $banner_image = null;
        }
        $data = [
            'banner_title' => $request->input('banner_title'),
            'banner_image' => $banner_image,
            'banner_des' => $request->input('banner_des'),
            'sort' => $request->input('sort'),
            'status' => $request->input('status')
        ];
        $banner = Banner::create($data);
        if ($banner) {
            return response()->json(['success' => 'Banner created successfully', 'Banner' => $banner], 201);
        } else {
            return response()->json(['error' => 'Banner creation failed'], 500);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        //
        $banner = Banner::find($id);
        if ($banner) {
            return response()->json(['data' => $banner]);
        } else {
            return response()->json(['message' => 'Banner not found']);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        //
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json(['error' => 'Banner not found'], 404);
        }
        $validate = [
            'banner_title' => 'required|string|unique:banners,banner_title,' . $id,
            'banner_des' => 'string|nullable',
            'banner_image' => 'file|nullable',
            'sort' => 'nullable',
            'status' => 'nullable|string',
        ];
        // kiem tra du lieu input -> validate
        $validator = Validator::make($request->all(), $validate);
        // thong bao neu du lieu khong hop le
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        // neu co hinh anh tai len -> cap nhat avatar
        $path = 'img';
        if ($request->hasFile('banner_image')) {
            $get_image = $request->file('banner_image');
            $new_image = $get_image->getClientOriginalName();
            // kiem tra file da ton tai trong thu muc chua
            if (!File::exists(public_path($path . '/' . $new_image))) {
                $get_image->move(public_path($path), $new_image);
            }
            $banner_image = url($path . '/' . $new_image);
        } else {
            $banner_image = $banner->banner_image;
        }
        $banner->banner_title = $request->input('banner_title');
        $banner->banner_image = $banner_image;
        $banner->banner_des = $request->input('banner_des');
        $banner->sort = $request->input('sort');
        $banner->status = $request->input('status');
        $banner->save();
        return response()->json(['message' => 'Banner updated successfully', 'blog' => $banner], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        //
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json(['error' => 'Banner not found'], 404);
        }
        $banner->delete();
        return response()->json(['message' => 'Banner deleted successfully']);
    }
}