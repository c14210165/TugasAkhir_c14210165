<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class LoanRequested implements ShouldBroadcast
{
    use SerializesModels;

    public $loanData;

    public function __construct($loanData)
    {
        $this->loanData = $loanData;
    }

    // Tentukan channel broadcast, misalnya channel publik "loans"
    public function broadcastOn()
    {
        return new Channel('loans');
    }

    // Event name opsional, bisa custom
    public function broadcastAs()
    {
        return 'loan.requested';
    }
}
