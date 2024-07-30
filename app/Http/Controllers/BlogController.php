<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BlogController extends Controller
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
        $blogQuery = Blog::query();
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $blogQuery->where('blog_title', 'like', '%' . $searchTerm . '%');
        }
         if ($request->has('sort_by') && $request->has('sort_direction')) {
            $sortBy = $request->input('sort_by');
            $sortDirection = $request->input('sort_direction');
        }
        if (in_array($sortBy, ['blog_title', 'created_at', 'status']) && in_array($sortDirection, ['asc', 'desc'])) {
            $blogQuery->orderBy($sortBy, $sortDirection);
        }
        $perpage = $request->input('perpage', 10);
        $blogs = $blogQuery->paginate($perpage);
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
            'id_cat' => 'required',
            'blog_title' => 'required|string|unique:blogs,blog_title',
            'blog_slug' => 'string|nullable',
            'blog_image' => 'file|nullable',
            'content' => 'nullable',
            'status' => 'nullable|string',
        ];
        // kiem tra du lieu input -> validate
        $validator = Validator::make($request->all(), $validate);
        // thong bao neu du lieu khong hop le
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        // ten thu muc luu tru du an
        $path = 'img';
        if ($request->hasFile('blog_image')) {
            $get_image = $request->file('blog_image');
            $new_image = $get_image->getClientOriginalName();
            // kiem tra file da ton tai trong thu muc hay chua
            if (!File::exists(public_path($path . '/' . $new_image))) {
                // chua ton tai -> uploads hinh anh vao thu muc du an
                $get_image->move(public_path($path), $new_image);
            }
            // thiet lap duong dan hinh anh == http://127.0.0.1:8000/img/tenhinhanh
            $blog_image = url($path . '/' . $new_image);
        } else {
            $blog_image = null;
        }
        $data = [
            'id_cat' => $request->input('id_cat'),
            'blog_title' => $request->input('blog_title'),
            'blog_slug' => Str::slug($request->input('blog_title')),
            'blog_image' => $blog_image,
            'content' => $request->input('content'),
            'status' => $request->input('status'),
        ];
        $blog = Blog::create($data);
        if ($blog) {
            return response()->json(['success' => 'Blog created successfully', 'Blog' => $blog], 201);
        } else {
            return response()->json(['error' => 'Blog creation failed'], 500);
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
        $blog = Blog::find($id);
        if ($blog) {
            return response(['data' => $blog]);
        } else {
            return response(['message' => 'Blog not found']);
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
        $blog = Blog::find($id);
        if (!$blog) {
            return response()->json(['error' => 'Blog not found'], 404);
        }
        $validate = [
            'id_cat' => 'required',
            'blog_title' => 'required|string|unique:blogs,blog_title,' . $id,
            'blog_slug' => 'string|nullable',
            'blog_image' => 'file',
            'content' => 'nullable',
            'status' => 'nullable|string|in:active,inactive',
        ];
        // kiem tra du lieu input -> validate
        $validator = Validator::make($request->all(), $validate);
        // thong bao neu du lieu khong hop le
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        // neu co hinh anh tai len -> cap nhat
        $blog_image = null;
        $path = 'img';
        if ($request->hasFile('blog_image')) {
            $get_image = $request->file('blog_image');
            $new_image = $get_image->getClientOriginalName();
            // kiem tra file da ton tai trong thu muc chua
            if (!File::exists(public_path($path . '/' . $new_image))) {
                $get_image->move(public_path($path), $new_image);
            }
            $blog_image = url($path . '/' . $new_image);
        } else {
            $blog_image = $blog->blog_image;
        }
        $blog->id_cat = $request->input('id_cat');
        $blog->blog_title = $request->input('blog_title');
        $blog->blog_slug = Str::slug($request->input('blog_title'));
        $blog->blog_image = $blog_image;
        $blog->content = $request->input('content');
        $blog->status = $request->input('status');
        $blog->save();
        return response()->json(['message' => 'Blog updated successfully', 'blog' => $blog], 201);
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
        $blog = Blog::find($id);
        if (!$blog) {
            return response()->json(['error' => 'Blog not found'], 404);
        }
        $blog->delete();
        return response()->json(['message' => 'Blog deleted successfully']);
    }
}
