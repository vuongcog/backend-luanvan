<?php

namespace App\Http\Controllers;

use App\Mail\MailOrder;
use App\Mail\PaymentSuccess;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class TestPaymentController extends Controller
{
    //
    public function donhang(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $id_user = $request->user()->id;
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
            $vnp_Returnurl = "http://127.0.0.1:8000/api/dohang/thanhtoan";
            // Email khách hàng
            $vnp_Inv_Email = $email;
            // Tài khoản và mật khẩu của SANBOX VN-PAY (Demo Test)
            $vnp_TmnCode = "T8ILNRAM";//Mã website tại VN-PAY
            $vnp_HashSecret = "FJ66ZAY77LPQAKET7R41EFWBCYMY99NN";//Chuỗi kí tự bí mật
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
            $returnData = array('code' => '00'
            , 'message' => 'Đặt hàng thành công! Kiểm tra email để xem hóa đơn đặt hàng'
            , 'data' => $vnp_Url);
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
                'payment_type' => 'THANH TOÁN VN PAY',
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
            return redirect($vnp_Url);
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
            return response()->json(['message', 'Đặt hàng thành công'], 200);
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
}
