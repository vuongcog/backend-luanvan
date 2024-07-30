<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
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
        $blogQuery = Coupon::query();
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $blogQuery->where('coupon_code', 'like', '%' . $searchTerm . '%');
        }
        if ($request->has('sort_by') && $request->has('sort_direction')) {
            $sortBy = $request->input('sort_by');
            $sortDirection = $request->input('sort_direction');
        }
        if (in_array($sortBy, ['coupon_code', 'discount_value','created_at', 'status']) && in_array($sortDirection, ['asc', 'desc'])) {
            $blogQuery->orderBy($sortBy, $sortDirection);
        }
        $perpage = $request->input('perpage', 10);
        $coupons = $blogQuery->paginate($perpage);
        $totalPages = ceil($coupons->total() / $coupons->perPage());
        return response()->json([
            'total_blogs' => $coupons->total(),
            'current_page' => $coupons->currentPage(),
            'per_page' => $coupons->perPage(),
            'total_pages' => $totalPages,
            'last_page' => $coupons->lastPage(),
            'next_page_url' => $coupons->nextPageUrl(),
            'prev_page_url' => $coupons->previousPageUrl(),
            'data' => $coupons->items(),
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
            'discount_value' => 'required',
            'coupon_start_date' => 'date|required',
            'coupon_end_date' => 'date|required',
            'coupon_quantity' => 'required',
            'status' => 'required|string',
        ];
        // kiem tra du lieu input -> validate
        $validator = Validator::make($request->all(), $validate);
        // thong bao neu du lieu khong hop le
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        $data = [
            'coupon_code' => 'MAGIAMGIA-' . rand(999, 10000),
            'discount_value' => $request->input('discount_value'),
            'coupon_start_date' => Carbon::createFromFormat('d-m-Y', $request->input('coupon_start_date'))->format('Y-m-d'),
            'coupon_end_date' => Carbon::createFromFormat('d-m-Y', $request->input('coupon_end_date'))->format('Y-m-d'),
            'coupon_quantity' => $request->input('coupon_quantity'),
            'status' => $request->input('status'),
        ];
        $coupon = Coupon::create($data);
        if ($coupon) {
            return response()->json(['success' => 'Coupon created successfully', 'Coupon' => $coupon], 201);
        } else {
            return response()->json(['error' => 'Coupon creation failed'], 500);
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
        $coupon = Coupon::find($id);
        if ($coupon) {
            return response()->json(['data' => $coupon]);
        } else {
            return response()->json(['message' => 'Coupon not found']);
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
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return response()->json(['error' => 'Coupon not found'], 404);
        }
    
        $validate = [
            'discount_value' => 'required',
            'coupon_start_date' => 'required|date_format:d-m-Y',
            'coupon_end_date' => 'required|date_format:d-m-Y',
            'coupon_quantity' => 'numeric',
            'status' => 'nullable|string|in:active,inactive',
        ];
    
        $validator = Validator::make($request->all(), $validate);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
    
        $coupon->discount_value = $request->input('discount_value');
        $coupon->coupon_start_date = Carbon::createFromFormat('d-m-Y', $request->input('coupon_start_date'))->format('Y-m-d');
        $coupon->coupon_end_date = Carbon::createFromFormat('d-m-Y', $request->input('coupon_end_date'))->format('Y-m-d');
        $coupon->coupon_quantity = $request->input('coupon_quantity');
        $coupon->status = $request->input('status');
    
        $coupon->save();
    
        return response()->json(['message' => 'Coupon updated successfully', 'coupon' => $coupon], 200);
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
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return response()->json(['error' => 'Coupon not found'], 404);
        }
        $coupon->delete();
        return response()->json(['message' => 'Coupon deleted successfully']);
    }
}