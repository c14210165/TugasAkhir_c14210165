<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewForwardedLoanNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $itemName;
    public $forwardedBy;
    public $forwardedAt;

    public function __construct($itemName, $forwardedBy, $forwardedAt)
    {
        $this->itemName = $itemName;
        $this->forwardedBy = $forwardedBy;
        $this->forwardedAt = $forwardedAt;
    }

    public function build()
    {
        return $this->subject('Permohonan Baru Diteruskan ke PTIK')
                    ->view('emails.forwarded');
    }
}
