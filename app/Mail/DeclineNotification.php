<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeclineNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $itemName;
    public $rejectedBy;
    public $rejectionReason;
    public $rejectedAt;

    public function __construct($itemName, $rejectedBy, $rejectionReason, $rejectedAt)
    {
        $this->itemName = $itemName;
        $this->rejectedBy = $rejectedBy;
        $this->rejectionReason = $rejectionReason;
        $this->rejectedAt = $rejectedAt;
    }

    public function build()
    {
        return $this->subject('Penolakan Permohonan Barang')
                    ->view('emails.decline');
    }
}
