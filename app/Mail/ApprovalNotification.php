<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ApprovalNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $itemName;
    public $approvedBy;
    public $approvedAt;

    public function __construct($itemName, $approvedBy, $approvedAt)
    {
        $this->itemName = $itemName;
        $this->approvedBy = $approvedBy;
        $this->approvedAt = $approvedAt;
    }

    public function build()
    {
        return $this->subject('Notifikasi Persetujuan Barang')
                    ->view('emails.approval');
    }
}
