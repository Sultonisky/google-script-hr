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
        $nik = trim(ltrim(trim((string) $this->nikNpwp), "'"));

        return [
            'employee_id' => $this->employeeId,
            'nik' => $nik !== '' ? $nik : null,
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
