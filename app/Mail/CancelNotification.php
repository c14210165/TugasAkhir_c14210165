<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class CancelNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $itemName;
    public $cancelledBy;
    public $cancelledAt;

    public function __construct($itemName, $cancelledBy, $cancelledAt)
    {
        $this->itemName = $itemName;
        $this->cancelledBy = $cancelledBy;
        $this->cancelledAt = $cancelledAt;
    }

    public function build()
    {
        return $this->subject('Pembatalan Permohonan Peminjaman')
                    ->view('emails.cancel');
    }
}
