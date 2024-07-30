<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentSuccess extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $order;

    public function __construct($order)
    {
        //
        $this->order = $order;
    }

    public function build()
    {
        return $this->view('mails.success')
            ->subject('Thanh toán đơn hàng thành công!');
    }
}
