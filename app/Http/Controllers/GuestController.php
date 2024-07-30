<?php

namespace App\Http\Controllers;

use App\Mail\MailOrder;
use App\Mail\PaymentSuccess;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Feedback;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class GuestController extends Controller
{
    // --- SẢN PHẨM --- //
    public function sanpham(): \Illuminate\Http\JsonResponse
    {
        $productQuery = Product::query();
        $products = $productQuery->paginate(10);
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

    public function chitietsanpham($slug): \Illuminate\Http\JsonResponse
    {
        $product = Product::where('product_slug', $slug)->first();
        if (!$product) {
            return response()->json(['error' => 'This products not exists !'], 404);
        }
        return response()->json(['product' => $product]);
    }

    // -- DANH MUC SAN PHAM -- //
    public function danhmuc()
    {
        $danhmuc = Category::with('products')->get();
        return response()->json([
            'danhmuc' => $danhmuc
        ]);
    }

    public function sanphamtheodanhmuc($id_cat)
    {
        $category = Category::with('products')->findOrFail($id_cat);
        if (!$category) {
            return response()->json([
                'message' => 'Không tìm thấy danh mục'
            ], 404);
        }
        $products = $category->products()->paginate(10);
        $totalPages = ceil($products->total() / $products->perPage());
        return response()->json([
            'total_products' => $products->total(),
            'current_page' => $products->currentPage(),
            'per_page' => $products->perPage(),
            'total_pages' => $totalPages,
            'last_page' => $products->lastPage(),
            'next_page_url' => $products->nextPageUrl(),
            'prev_page_url' => $products->previousPageUrl(),
            'data' => $products,
        ]);
    }

    // -- BAI VIET -- //
    public function baiviet()
    {
        $baiviet = Blog::all();
        return response()->json([
            'baiviet' => $baiviet
        ]);
    }

    public function chitietbaiviet($slug)
    {
        $baiviet = Blog::where('blog_slug', $slug)->with('user')->first(); // Lấy bài viết dựa trên slug
        if (!$baiviet) {
            return response()->json(['error' => 'Bài viết không tồn tại'], 404);
        }
        $baiviet->view = $baiviet->view + 1;
        $baiviet->save();
        $related_blogs = Blog::where('id_cat', $baiviet->id_cat)
            ->where('id', '!=', $baiviet->id)
            ->take(10)
            ->get();
        return response()->json([
            'bai-viet' => $baiviet,
            'bai-viet-lien-quan' => $related_blogs
        ]);
    }

    // -- DANH MUC BAI VIET -- //
    public function danhmucbaiviet()
    {
        $danhmucbaiviet = BlogCategory::with('blogs')->get();
        return response()->json([
            'danh-muc-bai-viet' => $danhmucbaiviet
        ]);
    }

    public function baiviettheodanhmuc($id_cat)
    {
        $category_blog = BlogCategory::with('blogs')->findOrFail($id_cat);
        if (!$category_blog) {
            return response()->json([
                'message' => 'Không tìm thấy danh mục'
            ], 404);
        }
        $blogs = $category_blog->blogs()->paginate(10);
        $totalPages = ceil($blogs->total() / $blogs->perPage());
        return response()->json([
            'total_posts' => $blogs->total(),
            'current_page' => $blogs->currentPage(),
            'per_page' => $blogs->perPage(),
            'total_pages' => $totalPages,
            'last_page' => $blogs->lastPage(),
            'next_page_url' => $blogs->nextPageUrl(),
            'prev_page_url' => $blogs->previousPageUrl(),
            'data' => $blogs,
        ]);
    }

    // -- GIO HANG -- //
    public function giohang()
    {
        if (auth()->check()) {
            $giohang = Cart::where('id_user', auth()->user()->id)->first();
            if ($giohang) {
                return response()->json([
                    'giohang' => Cart::with('items.product')->find($giohang->id),
                ]);
            } else {
                return response()->json([
                    'message' => 'Không tìm thấy giỏ hàng'
                ], 404);
            }
        } else {
            return response()->json([
                'message' => 'Không tìm thấy giỏ hàng'
            ], 404);
        }
    }

    public function themgiohang(Request $request)
    {
        $id_product = $request->input('id_prd');
        $quantity = $request->input('quantity');
        $product = Product::find($id_product);
        if ($quantity > $product->quantity) {
            $quantity = $product->quantity;
        }
        if (auth()->check()) {
            $user_id = auth()->user()->id;
            $cart = Cart::where('id_user', $user_id)->first();
            if (!$cart) {
                $cart = new Cart();
                $cart->id_user = $user_id;
                $cart->cart_total = 0.0;
                $cart->save();
            }
            $cartItem = CartItem::where('id_cart', $cart->id)
                ->where('id_product', $id_product)
                ->first();
            if ($cartItem) {
                if (($cartItem->quantity + $quantity) > $product->quantity) {
                    $cartItem->quantity = $product->quantity;
                    $cartItem->save();
                } else {
                    $cartItem->quantity += $quantity;
                    $cartItem->save();
                }
            } else {
                $product = Product::find($id_product);
                if ($product) {
                    $cartItem = new CartItem();
                    $cartItem->id_cart = $cart->id;
                    $cartItem->id_product = $id_product;
                    $cartItem->unit_prices = $product->unit_prices;
                    $cartItem->quantity = $quantity;
                    $cartItem->save();
                } else {
                    return response()->json([
                        'message' => 'Sản phẩm không tồn tại'
                    ], 404);
                }
            }
            $cartTotal = CartItem::where('id_cart', $cart->id)->sum(DB::raw('unit_prices * quantity'));
            $cart->cart_total = $cartTotal;
            $cart->save();

            return response()->json([
                'message' => 'Đã thêm sản phẩm vào giỏ hàng',
                'giohang' => Cart::with('items.product')->find($cart->id)
            ]);
        } else {
            return response()->json([
                'message' => 'Người dùng chưa xác thực'
            ], 401);
        }
    }

    public function capnhatgiohang(Request $request)
    {
        $id_product = $request->input('id_prd');
        $quantity = $request->input('quantity');
        $product = Product::find($id_product);
        if ($quantity > $product->quantity) {
            $quantity = $product->quantity;
        }
        if (auth()->check()) {
            $cart = Cart::where('id_user', auth()->user()->id)->first();
            if ($cart) {
                $cartItem = CartItem::where('id_cart', $cart->id)
                    ->where('id_product', $id_product)
                    ->first();
                if ($cartItem) {
                    if ($quantity > 0) {
                        $cartItem->quantity = $quantity;
                        $cartItem->save();
                    } else {
                        $cartItem->delete();
                    }
                    $cartTotal = CartItem::where('id_cart', $cart->id)->sum(DB::raw('unit_prices * quantity'));
                    $cart->cart_total = $cartTotal;
                    $cart->save();
                    return response()->json([
                        'message' => 'Đã cập nhật giỏ hàng',
                        'gio-hang' => Cart::with('items.product')->find($cart->id)
                    ]);
                } else {
                    return response()->json([
                        'message' => 'Không tìm thấy sản phẩm trong giỏ hàng'
                    ], 404);
                }
            } else {
                return response()->json([
                    'message' => 'Không tìm thấy giỏ hàng của người dùng'
                ], 404);
            }
        } else {
            return response()->json([
                'message' => 'Người dùng chưa xác thực'
            ], 401);
        }
    }

    public function xoagiohang($id_product)
    {
        if (auth()->check()) {
            $cart = Cart::where('id_user', auth()->user()->id)->first();
            if ($cart) {
                $cartItem = CartItem::where('id_cart', $cart->id)
                    ->where('id_product', $id_product)
                    ->first();
                if ($cartItem) {
                    $cartItem->delete();
                    $cartTotal = CartItem::where('id_cart', $cart->id)->sum(DB::raw('unit_prices * quantity'));
                    $cart->cart_total = $cartTotal;
                    $cart->save();
                    $updatedCart = Cart::with('items.product')->find($cart->id);
                    return response()->json([
                        'message' => 'Đã xóa sản phẩm khỏi giỏ hàng',
                        'gio-hang' => $updatedCart
                    ]);
                } else {
                    return response()->json([
                        'message' => 'Sản phẩm không tồn tại trong giỏ hàng'
                    ], 404);
                }
            } else {
                return response()->json([
                    'message' => 'Không tìm thấy giỏ hàng của người dùng'
                ], 404);
            }
        } else {
            return response()->json([
                'message' => 'Người dùng chưa xác thực'
            ], 401);
        }
    }


    // -- DON HANG -- //
    public function donhang(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $id_user = $user->id;
        } else {
            $id_user = null;
        }
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'payment' => 'required',
            'items' => 'required|array'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        $firstName = $request->input('first_name');
        $lastName = $request->input('last_name');
        $customerName = $firstName . ' ' . $lastName;
        $items = $request->input('items');
        $email = $request->input('email');
        $phone = $request->input('phone');
        $notes = $request->input('notes');
        $address = $request->input('address');
        $payment = $request->input('payment');
        $total_oder = 0;
        foreach ($items as $item) {
            $total_oder += $item['quantity'] * $item['unit_prices'];
        }
        if ($payment === 'vnpay') {
            // Tạo CODE đơn hàng tự động
            $randomNumber = mt_rand(999, 100000);
            $orderCode_VNPAY = "VNPAY-ORDER-" . $randomNumber;
            // Cấu hình VN-PAY
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
            date_default_timezone_set('Asia/Ho_Chi_Minh');
            // URL giao diện thanh toán VN-PAY
            $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
            // URL trả về khi thanh toán thành công
            $vnp_Returnurl = "http://127.0.0.1:8000/api/donhang/thanhtoan";
            // Email khách hàng
            $vnp_Inv_Email = $email;
            // Tài khoản và mật khẩu của SANBOX VN-PAY (Demo Test)
            $vnp_TmnCode = "T8ILNRAM";//Mã website tại VN-PAY
            $vnp_HashSecret = "FJ66ZAY77LPQAKET7R41EFWBCYMY99NN";//Chuỗi kí tự bí mật
            // Cấu hình thông tin thanh toán đặt vé
            $vnp_TxnRef = $orderCode_VNPAY; //Mã đơn hàng. Trong thực tế Merchant cần insert đơn hàng vào DB và gửi mã này sang VNPAY
            $vnp_OrderInfo = 'Thanh toán đơn hàng - ' . $orderCode_VNPAY;// Thông tin đơn hàng
            $vnp_Amount = $total_oder * 100; // Tổng tiền cần thanh toán
            $vnp_Locale = 'vn'; // việt nam
            $vnp_OrderType = 'payment';
            // tạo mảng chứa thông tin thanh toán VN-PAY
            $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
            $inputData = array(
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => $vnp_TmnCode,
                "vnp_Amount" => $vnp_Amount,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_Inv_Email" => $vnp_Inv_Email,
                "vnp_IpAddr" => $vnp_IpAddr,
                "vnp_Locale" => $vnp_Locale,
                "vnp_OrderInfo" => $vnp_OrderInfo,
                "vnp_OrderType" => $vnp_OrderType,
                "vnp_ReturnUrl" => $vnp_Returnurl,
                "vnp_TxnRef" => $vnp_TxnRef,
            );

            if (isset($vnp_BankCode) && $vnp_BankCode != "") {
                $inputData['vnp_BankCode'] = $vnp_BankCode;
            }

            if (isset($vnp_Bill_State) && $vnp_Bill_State != "") {
                $inputData['vnp_Bill_State'] = $vnp_Bill_State;
            }
            ksort($inputData);
            $query = "";
            $i = 0;
            $hashdata = "";
            foreach ($inputData as $key => $value) {
                if ($i == 1) {
                    $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
                } else {
                    $hashdata .= urlencode($key) . "=" . urlencode($value);
                    $i = 1;
                }
                $query .= urlencode($key) . "=" . urlencode($value) . '&';
            }

            $vnp_Url = $vnp_Url . "?" . $query;

            if (isset($vnp_HashSecret)) {
                $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);//
                $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
            }
            $returnData = array(['code' => '00'
                , 'data' => $vnp_Url]);
            if (isset($_POST['redirect'])) {
                header('Location: ' . $vnp_Url);
                die();
            } else {
                echo json_encode($returnData);
            }
            $order = Order::create([
                'order_code' => $orderCode_VNPAY,
                'customer' => $customerName,
                'payment_status' => 'chưa thanh toán',
                'email' => $email,
                'id_user' => $id_user,
                'order_total_prices' => $total_oder,
                'delivery_address' => $address,
                'phone' => $phone,
                'payment_type' => 'THANH TOÁN VNPAY',
                'order_notes' => $notes,
                'status' => 'ordered'
            ]);
            if ($order) {
                $id_oder = $order->id;
                foreach ($items as $item) {
                    $product = Product::find($item['id_product']);
                    if ($item['quantity'] > $product->quantity) {
                        $update_product_quantity = $product->quantity;
                    } elseif ($product->quantity <= 0) {
                        return response()->json(['message' => 'Sản phẩm không khả dụng! Vui lòng thử lại sau']);
                    } else {
                        $update_product_quantity = $item['quantity'];
                    }
                    $order_items = OrderItem::create([
                        'id_order' => $id_oder,
                        'id_product' => $item['id_product'],
                        'quantity' => $update_product_quantity,
                        'unit_prices' => $item['unit_prices']
                    ]);
                }
                if ($order_items) {
                    $get_order_item = OrderItem::all()->where('id_order', $id_oder);
                    foreach ($get_order_item as $product) {
                        $id_product = $product->id_product;
                        $quantity_product = Product::all()->where('id', $id_product)->first();
                        // gán số lượng sản phẩm
                        $quantity = $quantity_product->quantity;
                        // gán số lượng sản phẩm mới sau khi tạo đơn hàng
                        $new_quantity = $quantity - $product->quantity;
                        // cập nhật sản phẩm
                        DB::table('products')->where('id', '=', $id_product)
                            ->update([
                                'quantity' => $new_quantity
                            ]);
                        Mail::to($email)->send(new MailOrder($order));
                    }
                }
            }
        } elseif ($payment === 'cod') {
            $randomNumber = mt_rand(99999, 1000000);
            $orderCode_COD = "COD-ORDER- " . $randomNumber;
            $order = Order::create([
                'order_code' => $orderCode_COD,
                'payment_status' => 'chưa thanh toán',
                'customer' => $customerName,
                'email' => $email,
                'id_user' => $id_user,
                'order_total_prices' => $total_oder,
                'delivery_address' => $address,
                'phone' => $phone,
                'payment_type' => 'THANH TOÁN KHI NHẬN HÀNG',
                'order_notes' => $notes,
                'status' => 'ordered'
            ]);
            if ($order) {
                $id_oder = $order->id;
                foreach ($items as $item) {
                    $order_items = OrderItem::create([
                        'id_order' => $id_oder,
                        'id_product' => $item['id_product'],
                        'quantity' => $item['quantity'],
                        'unit_prices' => $item['unit_prices']
                    ]);
                }
                if ($order_items) {
                    $get_order_item = OrderItem::all()->where('id_order', $id_oder);
                    foreach ($get_order_item as $product) {
                        $id_product = $product->id_product;
                        $quantity_product = Product::all()->where('id', $id_product)->first();
                        $quantity = $quantity_product->quantity;
                        $new_quantity = $quantity - $product->quantity;
                        DB::table('products')->where('id', '=', $id_product)
                            ->update([
                                'quantity' => $new_quantity
                            ]);
                    }
                }
            }
            Mail::to($email)->send(new MailOrder($order));
            return response()->json(['message' => 'Đặt hàng thành công'], 200);
        }
    }

    public function responeVNPAY(Request $request): \Illuminate\Http\JsonResponse
    {
        if ($request->vnp_ResponseCode === "00") {
            DB::table('orders')->where('order_code', '=', $request->vnp_TxnRef)
                ->update(['payment_status' => 'thanh toán thành công']);
            $order = Order::where('order_code', $request->vnp_TxnRef)->first();
            Mail::to($order->email)->send(new PaymentSuccess($order));
            return response()->json('message', 'Thanh toán đơn hàng thành công');
        } else {
            return response()->json('message', 'Thanh toán đơn hàng thất bại');
        }
    }

    public function timkiem(Request $request)
    {
        $result = Product::query();
        if ($request->has('product_name')) {
            $result->where('product_name', 'LIKE', '%' . $request->input('product_name') . '%');
        }
        if ($request->has('category_name')) {
            $categoryName = $request->input('category_name');
            $result->whereHas('category', function ($query) use ($categoryName) {
                $query->where('category_name', $categoryName);
            });
        }
        if ($request->has('price_from') && $request->has('price_to')) {
            $priceFrom = $request->input('price_from');
            $priceTo = $request->input('price_to');
            $result->whereBetween('unit_prices', [$priceFrom, $priceTo]);
        }
        $products = $result->paginate(10);
        $totalPages = ceil($products->total() / $products->perPage());
        return response()->json([
            'total_posts' => $products->total(),
            'current_page' => $products->currentPage(),
            'per_page' => $products->perPage(),
            'total_pages' => $totalPages,
            'last_page' => $products->lastPage(),
            'next_page_url' => $products->nextPageUrl(),
            'prev_page_url' => $products->previousPageUrl(),
            'data' => $products,
        ]);
    }

//    public function taikhoan(Request $request)
//    {
//        $user = $request->user();
//        if (!$user) {
//            return response()->json([
//                'message' => 'Không tìm thấy người dùng'
//            ], 404);
//        }
//
//        $client_urls = [];
//        $admin_urls = [];
//
//        if ($user->hasRole(['admin', 'staff', 'manager'])) {
//            // User routes
//            $admin_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/user',
//                'des' => 'Xem tất cả người dùng'
//            ];
//            $admin_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/user/{id}',
//                'des' => 'Xem thông tin người dùng theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'POST',
//                'url' => env('APP_URL') . '/api/user',
//                'des' => 'Tạo mới người dùng'
//            ];
//            $admin_urls[] = [
//                'method' => 'PUT',
//                'url' => env('APP_URL') . '/api/user/{id}',
//                'des' => 'Cập nhật người dùng theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'PATCH',
//                'url' => env('APP_URL') . '/api/user/{id}',
//                'des' => 'Cập nhật một phần thông tin người dùng theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'DELETE',
//                'url' => env('APP_URL') . '/api/user/{id}',
//                'des' => 'Xóa người dùng theo ID'
//            ];
//
//            // Category routes
//            $admin_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/category',
//                'des' => 'Xem tất cả danh mục'
//            ];
//            $admin_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/category/{id}',
//                'des' => 'Xem danh mục theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'POST',
//                'url' => env('APP_URL') . '/api/category',
//                'des' => 'Tạo mới danh mục'
//            ];
//            $admin_urls[] = [
//                'method' => 'PUT',
//                'url' => env('APP_URL') . '/api/category/{id}',
//                'des' => 'Cập nhật danh mục theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'PATCH',
//                'url' => env('APP_URL') . '/api/category/{id}',
//                'des' => 'Cập nhật một phần thông tin danh mục theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'DELETE',
//                'url' => env('APP_URL') . '/api/category/{id}',
//                'des' => 'Xóa danh mục theo ID'
//            ];
//
//            // Product routes
//            $admin_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/product',
//                'des' => 'Xem tất cả sản phẩm'
//            ];
//            $admin_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/product/{id}',
//                'des' => 'Xem sản phẩm theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'POST',
//                'url' => env('APP_URL') . '/api/product',
//                'des' => 'Tạo mới sản phẩm'
//            ];
//            $admin_urls[] = [
//                'method' => 'PUT',
//                'url' => env('APP_URL') . '/api/product/{id}',
//                'des' => 'Cập nhật sản phẩm theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'PATCH',
//                'url' => env('APP_URL') . '/api/product/{id}',
//                'des' => 'Cập nhật một phần thông tin sản phẩm theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'DELETE',
//                'url' => env('APP_URL') . '/api/product/{id}',
//                'des' => 'Xóa sản phẩm theo ID'
//            ];
//
//            // Blog category routes
//            $admin_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/blog/category',
//                'des' => 'Xem tất cả danh mục blog'
//            ];
//            $admin_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/blog/category/{id}',
//                'des' => 'Xem danh mục blog theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'POST',
//                'url' => env('APP_URL') . '/api/blog/category',
//                'des' => 'Tạo mới danh mục blog'
//            ];
//            $admin_urls[] = [
//                'method' => 'PUT',
//                'url' => env('APP_URL') . '/api/blog/category/{id}',
//                'des' => 'Cập nhật danh mục blog theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'PATCH',
//                'url' => env('APP_URL') . '/api/blog/category/{id}',
//                'des' => 'Cập nhật một phần thông tin danh mục blog theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'DELETE',
//                'url' => env('APP_URL') . '/api/blog/category/{id}',
//                'des' => 'Xóa danh mục blog theo ID'
//            ];
//
//            // Blog routes
//            $admin_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/blog',
//                'des' => 'Xem tất cả bài viết blog'
//            ];
//            $admin_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/blog/{id}',
//                'des' => 'Xem bài viết blog theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'POST',
//                'url' => env('APP_URL') . '/api/blog',
//                'des' => 'Tạo mới bài viết blog'
//            ];
//            $admin_urls[] = [
//                'method' => 'PUT',
//                'url' => env('APP_URL') . '/api/blog/{id}',
//                'des' => 'Cập nhật bài viết blog theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'PATCH',
//                'url' => env('APP_URL') . '/api/blog/{id}',
//                'des' => 'Cập nhật một phần thông tin bài viết blog theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'DELETE',
//                'url' => env('APP_URL') . '/api/blog/{id}',
//                'des' => 'Xóa bài viết blog theo ID'
//            ];
//
//            // Order routes
//            $admin_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/order',
//                'des' => 'Xem tất cả đơn hàng'
//            ];
//
//            // Coupon routes
//            $admin_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/coupon',
//                'des' => 'Xem tất cả mã giảm giá'
//            ];
//            $admin_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/coupon/{id}',
//                'des' => 'Xem mã giảm giá theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'POST',
//                'url' => env('APP_URL') . '/api/coupon',
//                'des' => 'Tạo mới mã giảm giá'
//            ];
//            $admin_urls[] = [
//                'method' => 'PUT',
//                'url' => env('APP_URL') . '/api/coupon/{id}',
//                'des' => 'Cập nhật mã giảm giá theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'PATCH',
//                'url' => env('APP_URL') . '/api/coupon/{id}',
//                'des' => 'Cập nhật một phần thông tin mã giảm giá theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'DELETE',
//                'url' => env('APP_URL') . '/api/coupon/{id}',
//                'des' => 'Xóa mã giảm giá theo ID'
//            ];
//
//            // Banner routes
//            $admin_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/banner',
//                'des' => 'Xem tất cả banner'
//            ];
//            $admin_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/banner/{id}',
//                'des' => 'Xem banner theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'POST',
//                'url' => env('APP_URL') . '/api/banner',
//                'des' => 'Tạo mới banner'
//            ];
//            $admin_urls[] = [
//                'method' => 'PUT',
//                'url' => env('APP_URL') . '/api/banner/{id}',
//                'des' => 'Cập nhật banner theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'PATCH',
//                'url' => env('APP_URL') . '/api/banner/{id}',
//                'des' => 'Cập nhật một phần thông tin banner theo ID'
//            ];
//            $admin_urls[] = [
//                'method' => 'DELETE',
//                'url' => env('APP_URL') . '/api/banner/{id}',
//                'des' => 'Xóa banner theo ID'
//            ];
//            // Tài khoản
//            $admin_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/taikhoan/thongtin-taikhoan',
//                'des' => 'Xem thông tin tài khoản'
//            ];
//            $admin_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/taikhoan/thongtin-nguoidung',
//                'des' => 'Xem thông tin dữ liệu liên quan của người dùng'
//            ];
//            // Sản phẩm
//            $client_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/sanpham',
//                'des' => 'Xem danh sách sản phẩm'
//            ];
//            $client_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/chitietsanpham/{slug}',
//                'des' => 'Xem chi tiết sản phẩm theo slug'
//            ];
//            $client_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/sanpham-danhmuc/{id_category}',
//                'des' => 'Xem sản phẩm theo danh mục'
//            ];
//
//            // Danh mục
//            $client_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/danhmuc',
//                'des' => 'Xem danh sách danh mục'
//            ];
//
//            // Bài viết
//            $client_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/baiviet',
//                'des' => 'Xem danh sách bài viết'
//            ];
//            $client_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/chitietbaiviet/{slug}',
//                'des' => 'Xem chi tiết bài viết theo slug'
//            ];
//            $client_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/baiviet-danhmuc/{id_category}',
//                'des' => 'Xem bài viết theo danh mục'
//            ];
//            // Danh mục bài viết
//            $client_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/danhmucbaiviet',
//                'des' => 'Xem danh sách danh mục bài viết'
//            ];
//        } elseif ($user->hasRole('customer')) {
//            // Đơn hàng
//            $client_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/donhang',
//                'des' => 'Xem danh sách đơn hàng'
//            ];
//            $client_urls[] = [
//                'method' => 'POST',
//                'url' => env('APP_URL') . '/api/donhang/thanhtoan',
//                'des' => 'Thanh toán đơn hàng'
//            ];
//            // Giỏ hàng
//            $client_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/giohang',
//                'des' => 'Xem giỏ hàng'
//            ];
//            $client_urls[] = [
//                'method' => 'POST',
//                'url' => env('APP_URL') . '/api/giohang/themgiohang',
//                'des' => 'Thêm sản phẩm vào giỏ hàng'
//            ];
//            $client_urls[] = [
//                'method' => 'PUT',
//                'url' => env('APP_URL') . '/api/giohang/capnhatgiohang',
//                'des' => 'Cập nhật giỏ hàng'
//            ];
//            $client_urls[] = [
//                'method' => 'DELETE',
//                'url' => env('APP_URL') . '/api/giohang/xoagiohang/{id_product}',
//                'des' => 'Xóa sản phẩm khỏi giỏ hàng'
//            ];
//            // Tài khoản
//            $client_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/taikhoan/thongtin-taikhoan',
//                'des' => 'Xem thông tin tài khoản'
//            ];
//            $client_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/taikhoan/thongtin-nguoidung',
//                'des' => 'Xem thông tin dữ liệu liên quan của người dùng'
//            ];
//            // Sản phẩm
//            $client_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/sanpham',
//                'des' => 'Xem danh sách sản phẩm'
//            ];
//            $client_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/chitietsanpham/{slug}',
//                'des' => 'Xem chi tiết sản phẩm theo slug'
//            ];
//            $client_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/sanpham-danhmuc/{id_category}',
//                'des' => 'Xem sản phẩm theo danh mục'
//            ];
//            // Danh mục
//            $client_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/danhmuc',
//                'des' => 'Xem danh sách danh mục'
//            ];
//            // Bài viết
//            $client_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/baiviet',
//                'des' => 'Xem danh sách bài viết'
//            ];
//            $client_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/chitietbaiviet/{slug}',
//                'des' => 'Xem chi tiết bài viết theo slug'
//            ];
//            $client_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/baiviet-danhmuc/{id_category}',
//                'des' => 'Xem bài viết theo danh mục'
//            ];
//            // Danh mục bài viết
//            $client_urls[] = [
//                'method' => 'GET',
//                'url' => env('APP_URL') . '/api/danhmucbaiviet',
//                'des' => 'Xem danh sách danh mục bài viết'
//            ];
//        }
//        return response()->json([
//            'thong_tin_nguoi_dung' => $user,
//            'duoc_phep_truy_cap' => [
//                'quan-tri-vien' => $admin_urls,
//                'khach-hang' => $client_urls,
//            ]
//        ]);
//    }
    public function taikhoan(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'message' => 'Không tìm thấy người dùng'
            ], 404);
        }

        $client_urls = [];
        $admin_urls = [];

        if ($user->hasRole(['admin', 'staff', 'manager'])) {
            // User routes
            $admin_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem tất cả người dùng'
            ];
            $admin_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem thông tin người dùng theo ID'
            ];
            $admin_urls[] = [
                'method' => 'POST',
                'url' => true,
                'des' => 'Tạo mới người dùng'
            ];
            $admin_urls[] = [
                'method' => 'PUT',
                'url' => true,
                'des' => 'Cập nhật người dùng theo ID'
            ];
            $admin_urls[] = [
                'method' => 'PATCH',
                'url' => true,
                'des' => 'Cập nhật một phần thông tin người dùng theo ID'
            ];
            $admin_urls[] = [
                'method' => 'DELETE',
                'url' => true,
                'des' => 'Xóa người dùng theo ID'
            ];

            // Category routes
            $admin_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem tất cả danh mục'
            ];
            $admin_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem danh mục theo ID'
            ];
            $admin_urls[] = [
                'method' => 'POST',
                'url' => true,
                'des' => 'Tạo mới danh mục'
            ];
            $admin_urls[] = [
                'method' => 'PUT',
                'url' => true,
                'des' => 'Cập nhật danh mục theo ID'
            ];
            $admin_urls[] = [
                'method' => 'PATCH',
                'url' => true,
                'des' => 'Cập nhật một phần thông tin danh mục theo ID'
            ];
            $admin_urls[] = [
                'method' => 'DELETE',
                'url' => true,
                'des' => 'Xóa danh mục theo ID'
            ];

            // Product routes
            $admin_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem tất cả sản phẩm'
            ];
            $admin_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem sản phẩm theo ID'
            ];
            $admin_urls[] = [
                'method' => 'POST',
                'url' => true,
                'des' => 'Tạo mới sản phẩm'
            ];
            $admin_urls[] = [
                'method' => 'PUT',
                'url' => true,
                'des' => 'Cập nhật sản phẩm theo ID'
            ];
            $admin_urls[] = [
                'method' => 'PATCH',
                'url' => true,
                'des' => 'Cập nhật một phần thông tin sản phẩm theo ID'
            ];
            $admin_urls[] = [
                'method' => 'DELETE',
                'url' => true,
                'des' => 'Xóa sản phẩm theo ID'
            ];

            // Blog category routes
            $admin_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem tất cả danh mục blog'
            ];
            $admin_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem danh mục blog theo ID'
            ];
            $admin_urls[] = [
                'method' => 'POST',
                'url' => true,
                'des' => 'Tạo mới danh mục blog'
            ];
            $admin_urls[] = [
                'method' => 'PUT',
                'url' => true,
                'des' => 'Cập nhật danh mục blog theo ID'
            ];
            $admin_urls[] = [
                'method' => 'PATCH',
                'url' => true,
                'des' => 'Cập nhật một phần thông tin danh mục blog theo ID'
            ];
            $admin_urls[] = [
                'method' => 'DELETE',
                'url' => true,
                'des' => 'Xóa danh mục blog theo ID'
            ];

            // Blog routes
            $admin_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem tất cả bài viết blog'
            ];
            $admin_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem bài viết blog theo ID'
            ];
            $admin_urls[] = [
                'method' => 'POST',
                'url' => true,
                'des' => 'Tạo mới bài viết blog'
            ];
            $admin_urls[] = [
                'method' => 'PUT',
                'url' => true,
                'des' => 'Cập nhật bài viết blog theo ID'
            ];
            $admin_urls[] = [
                'method' => 'PATCH',
                'url' => true,
                'des' => 'Cập nhật một phần thông tin bài viết blog theo ID'
            ];
            $admin_urls[] = [
                'method' => 'DELETE',
                'url' => true,
                'des' => 'Xóa bài viết blog theo ID'
            ];

            // Order routes
            $admin_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem tất cả đơn hàng'
            ];

            // Coupon routes
            $admin_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem tất cả mã giảm giá'
            ];
            $admin_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem mã giảm giá theo ID'
            ];
            $admin_urls[] = [
                'method' => 'POST',
                'url' => true,
                'des' => 'Tạo mới mã giảm giá'
            ];
            $admin_urls[] = [
                'method' => 'PUT',
                'url' => true,
                'des' => 'Cập nhật mã giảm giá theo ID'
            ];
            $admin_urls[] = [
                'method' => 'PATCH',
                'url' => true,
                'des' => 'Cập nhật một phần thông tin mã giảm giá theo ID'
            ];
            $admin_urls[] = [
                'method' => 'DELETE',
                'url' => true,
                'des' => 'Xóa mã giảm giá theo ID'
            ];

            // Banner routes
            $admin_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem tất cả banner'
            ];
            $admin_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem banner theo ID'
            ];
            $admin_urls[] = [
                'method' => 'POST',
                'url' => true,
                'des' => 'Tạo mới banner'
            ];
            $admin_urls[] = [
                'method' => 'PUT',
                'url' => true,
                'des' => 'Cập nhật banner theo ID'
            ];
            $admin_urls[] = [
                'method' => 'PATCH',
                'url' => true,
                'des' => 'Cập nhật một phần thông tin banner theo ID'
            ];
            $admin_urls[] = [
                'method' => 'DELETE',
                'url' => true,
                'des' => 'Xóa banner theo ID'
            ];
            // Tài khoản
            $admin_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem thông tin tài khoản'
            ];
            $admin_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem thông tin dữ liệu liên quan của người dùng'
            ];
            // Sản phẩm
            $client_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem danh sách sản phẩm'
            ];
            $client_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem chi tiết sản phẩm theo slug'
            ];
            $client_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem sản phẩm theo danh mục'
            ];

            // Danh mục
            $client_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem danh sách danh mục'
            ];

            // Bài viết
            $client_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem danh sách bài viết'
            ];
            $client_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem chi tiết bài viết theo slug'
            ];
            $client_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem bài viết theo danh mục'
            ];
            // Danh mục bài viết
            $client_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem danh sách danh mục bài viết'
            ];
        } elseif ($user->hasRole('customer')) {
            // Đơn hàng
            $client_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem danh sách đơn hàng'
            ];
            $client_urls[] = [
                'method' => 'POST',
                'url' => true,
                'des' => 'Thanh toán đơn hàng'
            ];
            // Giỏ hàng
            $client_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem giỏ hàng'
            ];
            $client_urls[] = [
                'method' => 'POST',
                'url' => true,
                'des' => 'Thêm sản phẩm vào giỏ hàng'
            ];
            $client_urls[] = [
                'method' => 'PUT',
                'url' => true,
                'des' => 'Cập nhật giỏ hàng'
            ];
            $client_urls[] = [
                'method' => 'DELETE',
                'url' => true,
                'des' => 'Xóa sản phẩm khỏi giỏ hàng'
            ];
            // Tài khoản
            $client_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem thông tin tài khoản'
            ];
            $client_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem thông tin dữ liệu liên quan của người dùng'
            ];
            // Sản phẩm
            $client_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem danh sách sản phẩm'
            ];
            $client_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem chi tiết sản phẩm theo slug'
            ];
            $client_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem sản phẩm theo danh mục'
            ];
            // Danh mục
            $client_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem danh sách danh mục'
            ];
            // Bài viết
            $client_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem danh sách bài viết'
            ];
            $client_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem chi tiết bài viết theo slug'
            ];
            $client_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem bài viết theo danh mục'
            ];
            // Danh mục bài viết
            $client_urls[] = [
                'method' => 'GET',
                'url' => true,
                'des' => 'Xem danh sách danh mục bài viết'
            ];
        }

        return response()->json([
            'thong_tin_nguoi_dung' => $user,
            'duoc_phep_truy_cap' => [
                'quan-tri-vien' => $admin_urls,
                'khach-hang' => $client_urls,
            ]
        ]);
    }

    public function thongtinnguoidung(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Không tìm thấy người dùng'], 404);
        }

        $query = User::with(['orders' => function ($orderQuery) use ($request) {
            if ($request->has('search')) {
                $searchTerm = $request->input('search');
                $orderQuery->where('order_code', 'like', '%' . $searchTerm . '%');
            }

            if ($request->has('sort_by') && $request->has('sort_direction')) {
                $sortBy = $request->input('sort_by');
                $sortDirection = $request->input('sort_direction');

                if (in_array($sortBy, ['order_code', 'order_total_prices', 'created_at', 'status']) &&
                    in_array($sortDirection, ['asc', 'desc'])) {
                    $orderQuery->orderBy($sortBy, $sortDirection);
                }
            }

            $orderQuery->with(['items' => function ($itemQuery) {
                $itemQuery->with('products');
            }]);
        }]);

        $user_with_orders_and_items = $query->where('id', $user->id)->first();

        if (!$user_with_orders_and_items) {
            return response()->json(['message' => 'Không tìm thấy thông tin đơn hàng của người dùng'], 404);
        }

        return response()->json(['thong-tin-nguoi-dung' => $user_with_orders_and_items]);
    }

    public function tracuudonhang(Request $request)
    {
        $order_code = $request->input('order_code');
        $order = Order::where('order_code', $order_code)->first();
        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }
        return response()->json($order);
    }

    public function xem_danh_gia($id_product)
    {
        $product = Product::find($id_product);
        if (!$product) {
            return response()->json(['message' => 'Không tìm thấy sản phẩm'], 404);
        }
        $ratedByUsers = $product->ratedByUsers()->withPivot('rate', 'comment')->get();

        return response()->json($ratedByUsers);
    }

    public function danhgia(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'id_user' => 'required|exists:users,id',
            'id_product' => 'required|exists:products,id',
            'rate' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);
        if ($validatedData->fails()) {
            return response()->json(['error' => $validatedData->errors()], 422);
        }
        $hasOrdered = Order::where('id_user', $validatedData['id_user'])
            ->whereHas('items', function ($query) use ($validatedData) {
                $query->where('id_product', $validatedData['id_product']);
            })->exists();
        if (!$hasOrdered) {
            return response()->json([
                'message' => 'Người dùng cần phải mua sản phẩm này trước khi được phép đánh giá.'
            ], 403);
        }
        $feedback = Feedback::updateOrCreate(
            ['id_user' => $validatedData['id_user'], 'id_product' => $validatedData['id_product']],
            ['rate' => $validatedData['rate'], 'comment' => $validatedData['comment']]
        );
        return response()->json($feedback, 201);
    }

    public function xem_ma_giam_gia()
    {
        $coupons = Coupon::where('status', 'active')
            ->where('coupon_start_date', '<=', now())
            ->where('coupon_end_date', '>=', now())
            ->where(function ($query) {
                $query->whereNull('coupon_quantity')
                    ->orWhere('coupon_quantity', '>', 0);
            })
            ->get();
        return response()->json(['ma_giam_gia_co_hieu_luc' => $coupons]);
    }
}