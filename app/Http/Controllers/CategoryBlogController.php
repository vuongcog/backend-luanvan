<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryBlogController extends Controller
{
    public function __construct(ValidatorFactory $validator)
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
        $this->validator = $validator;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        //
        $blogQuery = BlogCategory::query();
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $blogQuery->where('name', 'like', '%' . $searchTerm . '%');
        }
        if ($request->has('sort_by') && $request->has('sort_direction')) {
            $sortBy = $request->input('sort_by');
            $sortDirection = $request->input('sort_direction');
        }
        if (in_array($sortBy, ['name', 'created_at', 'status']) && in_array($sortDirection, ['asc', 'desc'])) {
            $blogQuery->orderBy($sortBy, $sortDirection);
        }
        $blogs = $blogQuery->paginate(10);
        $totalPages = ceil($blogs->total() / $blogs->perPage());
        return response()->json([
            'total_blogs' => $blogs->total(),
            'current_page' => $blogs->currentPage(),
            'per_page' => $blogs->perPage(),
            'total_pages' => $totalPages,
            'last_page' => $blogs->lastPage(),
            'next_page_url' => $blogs->nextPageUrl(),
            'prev_page_url' => $blogs->previousPageUrl(),
            'data' => $blogs->items(),
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
            'name' => 'required|string|unique:blog_categories,name',
            'slug' => 'string|nullable',
            'status' => 'nullable|string',
        ];
        // kiem tra du lieu input -> validate
        $validator = Validator::make($request->all(), $validate);
        // thong bao neu du lieu khong hop le
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        $data = [
            'name' => $request->input('name'),
            'slug' => Str::slug($request->input('name')),
            'status' => $request->input('status', 'active')
        ];
        $blogcategory = BlogCategory::create($data);
        if ($blogcategory) {
            return response()->json(['success' => 'Category blog created successfully', 'category' => $blogcategory], 201);
        } else {
            return response()->json(['error' => 'Category blog creation failed'], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        $blogcategory = BlogCategory::findOrFail($id);
        if (!$blogcategory) {
            return response()->json(['error' => 'Category blog not found'], 404);
        }
        $validate = [
            'name' => 'required|string|unique:blog_categories,name,' . $id,
            'slug' => 'string|nullable',
            'status' => 'nullable|string',
        ];
        // kiem tra du lieu input -> validate
        $validator = Validator::make($request->all(), $validate);
        // thong bao neu du lieu khong hop le
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        $blogcategory->name = $request->input('name');
        $blogcategory->slug = Str::slug($request->input('name'));
        $blogcategory->status = $request->input('status', 'active');
        $blogcategory->save();
        return response()->json(['message' => 'Category blog updated successfully', 'Blog category' => $blogcategory], 201);

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
        $category = BlogCategory::find($id);
        if (!$category) {
            return response()->json(['error' => 'Category blog not found'], 404);
        }
        if ($category->blogs) {
            return response()->json(['error' => 'This category blog has blog !'], 422);
        }
        // xoa nguoi dung
        $category->delete();
        return response()->json(['message' => 'Category blog deleted successfully'], 201);
    }
}
