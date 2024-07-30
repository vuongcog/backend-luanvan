<!-- resources/views/emails/order/paid.blade.php -->

<p>Xin chào {{ $order->customer}},</p>

<p>Đơn hàng của bạn (Mã đơn hàng: {{ $order->order_code }}) đã được tạo thành công.</p>
<p>Vui lòng thanh toán số tiền ({{$order->order_total_prices}}) để hoàn tất đơn hàng để hoàn tất đơn đặt hàng.</p>
<p>Cảm ơn bạn đã mua hàng từ chúng tôi.</p>

<p>Trân trọng,</p>
<p>Đội ngũ hỗ trợ khách hàng</p>
