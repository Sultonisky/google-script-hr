<?php

namespace App\Support;

use App\DTOs\OutsourceEmployeeData;
use App\Services\RecruitmentService;
use Illuminate\Support\Collection;

/**
 * Shared search / duplicate-contact rules for every outsource repository driver.
 */
final class OutsourceEmployeeFilter
{
    /**
     * @param  Collection<int, OutsourceEmployeeData>  $rows
     * @return Collection<int, OutsourceEmployeeData>
     */
    public static function apply(Collection $rows, array $filters): Collection
    {
        if (!empty($filters['vendor'])) {
            $vendor = strtolower(trim((string) $filters['vendor']));
            $rows = $rows->filter(fn (OutsourceEmployeeData $e) => strtolower(trim((string) $e->vendor)) === $vendor);
        }

        if (!empty($filters['search'])) {
            $search = strtolower(trim((string) $filters['search']));
            $rows = $rows->filter(function (OutsourceEmployeeData $e) use ($search) {
                foreach ([$e->fullName, $e->outsourceId, $e->jobTitle, $e->vendor, $e->workLocation, $e->workCity, $e->costCenter] as $value) {
                    if (str_contains(strtolower((string) $value), $search)) {
                        return true;
                    }
                }

                return false;
            });
        }

        return $rows->values();
    }

    /**
     * @param  Collection<int, OutsourceEmployeeData>  $rows
     */
    public static function findByContact(Collection $rows, ?string $whatsappNumber, ?string $email): ?OutsourceEmployeeData
    {
        $phone = self::phoneKey($whatsappNumber);
        $email = strtolower(trim((string) $email));
        if ($phone === '' && $email === '') {
            return null;
        }

        return $rows->first(function (OutsourceEmployeeData $e) use ($phone, $email) {
            return ($phone !== '' && self::phoneKey($e->whatsappNumber) === $phone)
                || ($email !== '' && strtolower(trim((string) $e->email)) === $email);
        });
    }

    /**
     * Canonical digits (62xxxxxxxxx) so 0812…, 812…, +62812… compare equal.
     */
    public static function phoneKey(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if ($digits === '') {
            return '';
        }

        return preg_replace('/\D+/', '', (string) RecruitmentService::normalizePhone($digits)) ?? '';
    }
}
