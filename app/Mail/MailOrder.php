<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailOrder extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($order)
    {
        //
        $this->order = $order;
    }

    public function build()
    {
        return $this->view('mails.order')
            ->subject('Đặt đơn hàng thành công ! Vui lòng thanh toán để hoàn tất đơn hàng.');
    }
}
