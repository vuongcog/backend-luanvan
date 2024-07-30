<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    // hien thi tat ca tai khoan nguoi dung
    public function index(Request $request)
    {
        $usersQuery = User::query();
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $usersQuery->where('name', 'like', '%' . $searchTerm . '%');
        }
        if ($request->has('sort_by') && $request->has('sort_direction')) {
            $sortBy = $request->input('sort_by');
            $sortDirection = $request->input('sort_direction');
        }
        if (in_array($sortBy, ['email', 'name', 'created_at', 'status']) && in_array($sortDirection, ['asc', 'desc'])) {
            $usersQuery->orderBy($sortBy, $sortDirection);
        }
        $perPage = $request->input('perpage', 10);
        $users = $usersQuery->paginate($perPage);
        $totalPages = ceil($users->total() / $users->perPage());
        return response()->json([
            'total_users' => $users->total(),
            'current_page' => $users->currentPage(),
            'per_page' => $users->perPage(),
            'total_pages' => $totalPages,
            'last_page' => $users->lastPage(),
            'next_page_url' => $users->nextPageUrl(),
            'prev_page_url' => $users->previousPageUrl(),
            'data' => $users->items(),
        ]);
    }

    // hien thi tai khoan nguoi dung theo id
    public function show($id): \Illuminate\Http\JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        return response()->json($user);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        // xac dinh cac truong du lieu
        $validate = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|max:255|nullable',
            'phone_num' => 'string|max:255|nullable',
            'address' => 'string|max:255|nullable',
            'url_avatar' => 'nullable',
        ];
        // kiem tra du lieu input -> validate
        $validator = Validator::make($request->all(), $validate);
        // thong bao neu du lieu khong hop le
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        // ten thu muc luu tru du an
        $path = 'img';
        if ($request->hasFile('avatar')) {
            $get_image = $request->file('avatar');
            $new_image = $get_image->getClientOriginalName();
            // kiem tra file da ton tai trong thu muc hay chua
            if (!File::exists(public_path($path . '/' . $new_image))) {
                // chua ton tai -> uploads hinh anh vao thu muc du an
                $get_image->move(public_path($path), $new_image);
            }
            // thiet lap duong dan hinh anh == http://127.0.0.1:8000/img/tenhinhanh
            $url_avatar = url($path . '/' . $new_image);
        } else {
            // avatar == null (neu khong co hinh anh nao tai len)
            $url_avatar = null;
        }
        // tao tai khoan nguoi dung
        $userData = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
            'role' => $request->input('role'),
            'address' => $request->input('address'),
            'phone_num' => $request->input('phone_num'),
            'url_avatar' => $url_avatar
        ];
        $user = User::create($userData);
        if ($user) {
            return response()->json(['success' => 'User created successfully', 'user' => $user], 201);
        } else {
            return response()->json(['error' => 'User creation failed'], 500);
        }
    }

    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        // kiem tra nguoi dung co ton tai khong
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        // xac dinh cac truong du lieu
        $validate = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,// kiem tra email la duy nhat
            'role' => 'required|string|max:255|nullable',
            'phone_num' => 'string|max:255|nullable',
            'address' => 'string|max:255|nullable',
            'url_avatar' => 'nullable',
            'status' => 'nullable|string|in:active,inactive',
        ];
        // kiem tra du lieu input -> validate
        $validator = Validator::make($request->all(), $validate);
        // thong bao neu du lieu khong hop le
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        // neu co hinh anh tai len -> cap nhat avatar
        $url_avatar = null;
        $path = 'img';
        if ($request->hasFile('avatar')) {
            $get_image = $request->file('avatar');
            $new_image = $get_image->getClientOriginalName();
            // kiem tra file da ton tai trong thu muc chua
            if (!File::exists(public_path($path . '/' . $new_image))) {
                $get_image->move(public_path($path), $new_image);
            }
            $url_avatar = url($path . '/' . $new_image);
        } else {
            $url_avatar = $user->url_avatar;
        }
        // cap nhat nguoi dung
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->role = $request->input('role');
        $user->phone_num = $request->input('phone_num');
        $user->address = $request->input('address');
        $user->url_avatar = $url_avatar;
        $user->status = $request->input('status');
        $user->save();
        return response()->json(['message' => 'User updated successfully', 'user' => $user], 201);
    }

    // xoa tai khoan
    public function destroy($id): \Illuminate\Http\JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        // xoa nguoi dung
        $user->delete();
        return response()->json(['message' => 'User deleted successfully'], 201);
    }
}
