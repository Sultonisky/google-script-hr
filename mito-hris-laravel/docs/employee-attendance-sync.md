# Sinkronisasi profil karyawan HRIS ke Attendance

HRIS adalah sumber utama profil karyawan. HRIS mengirim perubahan ke endpoint
Attendance berikut dengan bearer token:

```http
POST {ATTENDANCE_API_BASE_URL}/api/v1/integrations/hris/employees
Authorization: Bearer <token>
Content-Type: application/json
Accept: application/json
```

Konfigurasikan URL HTTPS `ATTENDANCE_API_BASE_URL` dan token khusus
`ATTENDANCE_EMPLOYEE_PUSH_API_TOKEN` pada HRIS, serta token yang sama pada
Attendance. Gunakan token berbeda dari token sinkronisasi outsource. Jika URL
atau token tidak dikonfigurasi, push langsung dilewati. Timeout menggunakan
`ATTENDANCE_API_TIMEOUT`.

Setiap request berisi maksimum 100 orang. Hanya field profil berikut yang
dikirim; data PIN, foto, riwayat absensi, penugasan Attendance, tautan User
lokal, kredensial, dan field lain tidak dikirim:

```json
{
    "people": [
        {
            "employee_id": "2026101301",
            "nik": "317...",
            "full_name": "Nama Karyawan",
            "employment_status": "Contract",
            "join_date": "2026-10-13",
            "end_date": "2027-10-12",
            "job_position": "Staff",
            "department": "Human Resources",
            "division": "People",
            "branch": "MITO",
            "email": "nama@mito.id",
            "phone": "08..."
        }
    ],
    "dry_run": true
}
```

`end_date` berasal dari `Resign Date` jika terisi; jika tidak, nilainya berasal
dari `End Date (Contract)`. `email` memakai `Working Email`, dan `phone`
memakai `Mobile Phone`. Attendance wajib mempertahankan seluruh field yang
dikelolanya, termasuk PIN, foto, riwayat absensi, penugasan, dan relasi User
lokal. Respons berisi `success`, satu status per `employee_id`, dan hitungan
`processed`, `created`, `updated`, `skipped`, `conflict`, `would_create`, serta
`would_update`.

Push langsung setelah HRIS membuat atau mengubah profil bersifat best-effort
(termasuk create, import, edit, rotasi, offboarding, off contract, probation,
dan onboarding rekrutmen). Kegagalan Attendance dicatat sebagai warning dan
tidak membatalkan simpanan HRIS. Attendance menyimpan salinan profil lokal dan
mengatur PIN awal sendiri (`123456`); PIN tidak dikirim dari HRIS.

Untuk preview atau retry seluruh profil dari `EmployeeRepositoryInterface::getAll()`:

```sh
php artisan mito:employees-push-attendance
php artisan mito:employees-push-attendance --execute
```

Tanpa `--execute`, command mengirim `dry_run: true` dan tidak meminta Attendance
menulis data. Dengan `--execute`, Attendance menerima sinkronisasi aktif.
Command mengirim batch berisi paling banyak 100 karyawan.
