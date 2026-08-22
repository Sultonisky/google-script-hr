<?php

namespace App\Events;

use App\DTOs\CandidateData;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CandidateApplied
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CandidateData $candidate
    ) {}
}
