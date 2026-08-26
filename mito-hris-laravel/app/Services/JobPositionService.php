<?php

namespace App\Services;

class JobPositionService
{
    public function getPositions(): array
    {
        return config('job_positions.positions', []);
    }

    public function getPositionNames(): array
    {
        return array_column($this->getPositions(), 0);
    }

    public function getLevel(string $position): ?string
    {
        $map = config('job_positions.get_level');
        return is_callable($map) ? $map($position) : null;
    }

    public function getFamily(string $position): ?string
    {
        $map = config('job_positions.get_family');
        return is_callable($map) ? $map($position) : null;
    }

    public function getPositionData(string $position): ?array
    {
        foreach ($this->getPositions() as $p) {
            if ($p[0] === $position) {
                return ['position' => $p[0], 'family' => $p[1], 'level' => $p[2]];
            }
        }
        return null;
    }
}