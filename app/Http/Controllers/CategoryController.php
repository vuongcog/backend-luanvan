<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
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
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        //
        $categoryQuery = Category::query();
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $categoryQuery->where('category_name', 'like', '%' . $searchTerm . '%');
        }
        if ($request->has('sort_by') && $request->has('sort_direction')) {
            $sortBy = $request->input('sort_by');
            $sortDirection = $request->input('sort_direction');
        }
        if (in_array($sortBy, ['category_name', 'created_at', 'status']) && in_array($sortDirection, ['asc', 'desc'])) {
            $categoryQuery->orderBy($sortBy, $sortDirection);
        }
        $perpage = $request->input('perpage', 10);
        $categories = $categoryQuery->paginate($perpage);
        $totalPages = ceil($categories->total() / $categories->perPage());
        return response()->json([
            'total_categories' => $categories->total(),
            'current_page' => $categories->currentPage(),
            'per_page' => $categories->perPage(),
            'total_pages' => $totalPages,
            'last_page' => $categories->lastPage(),
            'next_page_url' => $categories->nextPageUrl(),
            'prev_page_url' => $categories->previousPageUrl(),
            'data' => $categories->items(),
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
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        // xac dinh cac truong du lieu
        $validate = [
            'category_name' => 'required|string|unique:categories,category_name',
            'category_slug' => 'string|nullable',
            'category_des' => 'string|nullable',
            'status' => 'nullable|string',
        ];
        // kiem tra du lieu input -> validate
        $validator = Validator::make($request->all(), $validate);
        // thong bao neu du lieu khong hop le
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        $categoryData = [
            'category_name' => $request->input('category_name'),
            'category_slug' => Str::slug($request->input('category_name')),
            'category_des' => $request->input('category_des'),
            'status' => $request->input('status', 'active')
        ];
        $category = Category::create($categoryData);
        if ($category) {
            return response()->json(['success' => 'Category created successfully', 'category' => $category], 201);
        } else {
            return response()->json(['error' => 'Category creation failed'], 500);
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
        $category = Category::find($id);
        return response()->json(['data' => $category]);
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
    public function update(Request $request, int $id)
    {
        //
        $category = Category::findOrFail($id);
        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }
        // xac dinh cac truong du lieu
        $validate = [
            'category_name' => 'required|string|unique:categories,category_name,' . $id,
            'category_slug' => 'string|nullable',
            'category_des' => 'string|nullable',
            'status' => 'nullable|string'
        ];
        // kiem tra du lieu input -> validate
        $validator = Validator::make($request->all(), $validate);
        // thong bao neu du lieu khong hop le
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        $category->category_name = $request->input('category_name');
        $category->category_slug = Str::slug($request->input('category_name'));
        $category->category_des = $request->input('category_des');
        $category->status = $request->input('status', 'active');
        $category->save();
        return response()->json(['message' => 'Category updated successfully', 'category' => $category], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): \Illuminate\Http\JsonResponse
    {
        //
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }
        if ($category->products) {
            return response()->json(['error' => 'This category has products !'], 422);
        }
        // xoa nguoi dung
        $category->delete();
        return response()->json(['message' => 'Category deleted successfully'], 201);
    }
}
