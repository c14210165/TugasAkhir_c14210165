<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LoanSubmittedNotification extends Notification
{
    use Queueable;

    protected $itemType;
    protected $submittedBy;

    public function __construct($itemType, $submittedBy)
    {
        $this->itemType = $itemType;
        $this->submittedBy = $submittedBy;
    }

    public function via($notifiable)
    {
        return ['database']; // hanya simpan ke database
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Permohonan Baru',
            'message' => "Permohonan barang {$this->itemType} diajukan oleh {$this->submittedBy}.",
            'type' => 'submission',
        ];
    }
}
