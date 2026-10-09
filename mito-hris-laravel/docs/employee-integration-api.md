# Employee Integration API

Attendance can read employee data from HRIS through the read-only API below.
The API uses the active HRIS employee repository. For real HRIS records, that
source must be configured to Google Sheets or PostgreSQL; the local driver is
only intended for development.

## Authentication

Send the shared integration secret as a bearer token:

```http
Authorization: Bearer <HRIS_EMPLOYEE_API_TOKEN>
Accept: application/json
```

Set `HRIS_EMPLOYEE_API_TOKEN` to the same long, random secret in the HRIS
environment used by the API. Requests without a valid token receive `401`.
If the secret is not configured in HRIS, the endpoint fails closed with `503`.
Serve these endpoints over HTTPS.

## List employees

```http
GET /api/v1/employees?page=1&per_page=50&search=treasury&work_location=Jakarta&work_area=Head%20Office
```

`per_page` defaults to `50` and is limited to `100`. Optional `search` filters
attendance-relevant employee ID, name, NIK, job position, division, department,
work location, and work area fields. Optional `work_location` and `work_area`
filters match those values case-insensitively and are applied before
pagination. Personal email and other unreturned data are not searchable
through this endpoint. Requests are limited to 60 per minute. The response
contains attendance-relevant fields only, plus the distinct, non-empty filter
options gathered from the active HRIS employee source:

```json
{
    "success": true,
    "data": [
        {
            "employee_id": "2020041501",
            "nik": "3174...",
            "full_name": "Employee Name",
            "status_employee": "Permanent (PKWTT)",
            "job_position": "Treasury Staff",
            "division": "Finance",
            "department": "Treasury",
            "branch_name": "MSI",
            "job_position_location": null,
            "area_kerja": null,
            "lokasi_kerja": null
        }
    ],
    "links": {
        "first": "https://hris.example.com/api/v1/employees?page=1",
        "last": "https://hris.example.com/api/v1/employees?page=1",
        "prev": null,
        "next": null
    },
    "filters": {
        "work_locations": ["Jakarta"],
        "work_areas": ["Head Office"]
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "links": [
            {
                "url": null,
                "label": "&laquo; Previous",
                "active": false
            },
            {
                "url": "https://hris.example.com/api/v1/employees?page=1",
                "label": "1",
                "active": true
            },
            {
                "url": null,
                "label": "Next &raquo;",
                "active": false
            }
        ],
        "path": "https://hris.example.com/api/v1/employees",
        "per_page": 50,
        "to": 1,
        "total": 1
    }
}
```

## Find an employee by NIK

```http
GET /api/v1/employees/by-nik/{16-digit-NIK}
```

Returns the same employee fields, or `404` when no matching NIK exists.
Invalid NIK values receive `422`. NIKs are compared exactly after the HRIS
repository's existing leading-apostrophe normalization.

The API is read-only and does not expose employee contact, identity-document,
banking, BPJS, address, or HR-note fields.

## Outsource payslips and incentives

The authenticated attendance-app backend can fetch payroll records by the
Outsource ID associated with the signed-in user:

```http
GET /api/v1/outsource/{outsource_id}/payslips
GET /api/v1/outsource/{outsource_id}/incentives
Authorization: Bearer <HRIS_OUTSOURCE_PAYROLL_API_TOKEN>
Accept: application/json
```

Set `HRIS_OUTSOURCE_PAYROLL_API_TOKEN` to a long, random secret in the HRIS
environment and the PWA backend. This credential is separate from
`HRIS_EMPLOYEE_API_TOKEN` and fails closed with `503` if it is not configured;
requests with a missing or invalid credential receive `401`.

Both endpoints return all available periods for that one Outsource ID, newest
first. Pass `?period=YYYY-MM` to request a single period. Invalid periods
receive `422`; a valid ID with no matching records returns an empty `data`
array. Amounts and HKE are JSON numbers, with missing numeric values returned
as `0`. The response contains only fields needed to display the payslip or
incentive; import filenames and operator metadata are not included. Requests
are read-only and limited to 60 per minute.

For example, a payslip response contains:

```json
{
    "success": true,
    "outsource_id": "DM20260001",
    "filters": {"period": "2026-09"},
    "data": [
        {
            "period": "2026-09",
            "outsource_id": "DM20260001",
            "full_name": "Employee Name",
            "vendor": "Damarindo",
            "hke": 24.5,
            "basic_salary": 3200000,
            "bpjs_kesehatan_deduction": 32000,
            "loan_deduction": 0,
            "take_home_pay": 3168000
        }
    ],
    "meta": {"count": 1}
}
```

The incentive response uses the same envelope and includes `period`,
`outsource_id`, `full_name`, `vendor`, `umk_amount`, and `incentive_amount` in
each data item.

The PWA backend must resolve the signed-in user's Outsource ID from its trusted
account mapping and must not accept an arbitrary ID from an unauthenticated
browser request. Keep the shared HRIS Bearer token only on the server; never
embed it in PWA browser code or return it to clients. Serve the integration
over HTTPS.
