<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewSubmissionNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $itemName;
    public $submittedBy;
    public $submittedAt;

    public function __construct($itemName, $submittedBy, $submittedAt)
    {
        $this->itemName = $itemName;
        $this->submittedBy = $submittedBy;
        $this->submittedAt = $submittedAt;
    }

    public function build()
    {
        return $this->subject('Permohonan Baru Diajukan')
                    ->view('emails.submission');
    }
}
