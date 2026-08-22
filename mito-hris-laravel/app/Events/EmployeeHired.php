<?php

namespace App\Events;

use App\DTOs\EmployeeData;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeHired
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public EmployeeData $employee,
        public ?string $recruitmentId = null,
        public ?string $user = null
    ) {}
}
