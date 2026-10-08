<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeIntegrationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'employee_id' => $this->employeeId,
            'nik' => $this->nikNpwp,
            'full_name' => $this->fullName,
            'status_employee' => $this->statusEmployee,
            'job_position' => $this->jobPosition,
            'division' => $this->division,
            'department' => $this->department,
            'branch_name' => $this->branchName,
            'job_position_location' => $this->jobPositionLocation,
            'area_kerja' => $this->areaKerja,
            'lokasi_kerja' => $this->lokasiKerja,
        ];
    }
}
