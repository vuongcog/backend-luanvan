<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
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
        $productQuery = Product::query();
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $productQuery->where('product_name', 'like', '%' . $searchTerm . '%');
        }
        if ($request->has('sort_by') && $request->has('sort_direction')) {
            $sortBy = $request->input('sort_by');
            $sortDirection = $request->input('sort_direction');
        }
        if (in_array($sortBy, ['product_name', 'unit_prices', 'created_at', 'status']) && in_array($sortDirection, ['asc', 'desc'])) {
            $productQuery->orderBy($sortBy, $sortDirection);
        }
        $perpage = $request->input('perpage', 10);
        $products = $productQuery->paginate($perpage);
        $totalPages = ceil($products->total() / $products->perPage());
        return response()->json([
            'total_products' => $products->total(),
            'current_page' => $products->currentPage(),
            'per_page' => $products->perPage(),
            'total_pages' => $totalPages,
            'last_page' => $products->lastPage(),
            'next_page_url' => $products->nextPageUrl(),
            'prev_page_url' => $products->previousPageUrl(),
            'data' => $products->items(),
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
            'id_category' => 'required',
            'product_name' => 'required|string|unique:products,product_name',
            'product_slug' => 'string|nullable',
            'product_image' => 'file|nullable',
            'product_des' => 'nullable',
            'product_info' => 'nullable',
            'quantity' => 'nullable|numeric|min:0',
            'unit_prices' => 'nullable|numeric|min:0',
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
        if ($request->hasFile('product_image')) {
            $get_image = $request->file('product_image');
            $new_image = $get_image->getClientOriginalName();
            // kiem tra file da ton tai trong thu muc hay chua
            if (!File::exists(public_path($path . '/' . $new_image))) {
                // chua ton tai -> uploads hinh anh vao thu muc du an
                $get_image->move(public_path($path), $new_image);
            }
            // thiet lap duong dan hinh anh == http://127.0.0.1:8000/img/tenhinhanh
            $product_image = url($path . '/' . $new_image);
        } else {
            $product_image = null;
        }
        $data = [
            'id_category' => $request->input('id_category'),
            'product_name' => $request->input('product_name'),
            'product_slug' => Str::slug($request->input('product_name')),
            'product_image' => $product_image,
            'product_des' => $request->input('product_des'),
            'product_info' => $request->input('product_info'),
            'quantity' => $request->input('quantity'),
            'unit_prices' => $request->input('unit_prices'),
            'status' => $request->input('status'),
        ];
        $product = Product::create($data);
        if ($product) {
            return response()->json(['success' => 'Product created successfully', 'Product' => $product], 201);
        } else {
            return response()->json(['error' => 'Product creation failed'], 500);
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
        $product = Product::find($id);
        if ($product) {
            return response()->json(['data' => $product]);
        } else {
            return response()->json(['message' => 'Product not found'], 404);
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
        // kiem tra san pham co ton tai khong
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }
        $validate = [
            'id_category' => 'required',
            'product_name' => 'required|string|unique:products,product_name,' . $id,
            'product_slug' => 'string|nullable',
            'product_image' => 'file|nullable',
            'product_des' => 'nullable',
            'product_info' => 'nullable',
            'quantity' => 'nullable|numeric|min:0',
            'unit_prices' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:active,inactive',
        ];
        // kiem tra du lieu input -> validate
        $validator = Validator::make($request->all(), $validate);
        // thong bao neu du lieu khong hop le
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        // neu co hinh anh tai len -> cap nhat avatar
        $product_image = null;
        $path = 'img';
        if ($request->hasFile('product_image')) {
            $get_image = $request->file('product_image');
            $new_image = $get_image->getClientOriginalName();
            // kiem tra file da ton tai trong thu muc chua
            if (!File::exists(public_path($path . '/' . $new_image))) {
                $get_image->move(public_path($path), $new_image);
            }
            $product_image = url($path . '/' . $new_image);
        } else {
            $product_image = $product->product_image;
        }
        $product->id_category = $request->input('id_category');
        $product->product_name = $request->input('product_name');
        $product->product_slug = Str::slug($request->input('product_name'));
        $product->product_image = $product_image;
        $product->product_des = $request->input('product_des');
        $product->product_info = $request->input('product_info');
        $product->quantity = $request->input('quantity');
        $product->unit_prices = $request->input('unit_prices');
        $product->status = $request->input('status');
        $product->save();
        return response()->json(['message' => 'Product updated successfully', 'product' => $product], 201);
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
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }
}
