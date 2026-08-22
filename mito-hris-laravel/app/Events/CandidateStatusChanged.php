<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CandidateStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $recruitmentId,
        public string $oldStatus,
        public string $newStatus,
        public ?string $notes = null,
        public ?string $user = null
    ) {}
}
