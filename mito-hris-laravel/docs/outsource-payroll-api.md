# Outsource Payroll API

The read-only API exposes imported outsource payslips and incentives for the
Attendance PWA backend. It does not expose source-file metadata or allow writes.

## Authentication

Configure a dedicated secret with `HRIS_OUTSOURCE_PAYROLL_API_TOKEN`. Send it
with each request as a bearer token:

```http
Authorization: Bearer <token>
```

The API returns `503` when the token is not configured and `401` when the
request has no valid token. This credential is separate from
`HRIS_EMPLOYEE_API_TOKEN`.

Keep this credential only in server-side environment configuration; never
embed or return it in browser code. The Attendance backend must resolve the
signed-in user's outsource ID from its trusted account mapping rather than
accepting an arbitrary ID from a browser request. Serve integration requests
over HTTPS.

## Endpoints

### Payslips

```http
GET /api/v1/outsource/{outsourceId}/payslips
GET /api/v1/outsource/{outsourceId}/payslips?period=2026-09
```

### Incentives

```http
GET /api/v1/outsource/{outsourceId}/incentives
GET /api/v1/outsource/{outsourceId}/incentives?period=2026-09
```

`period` is optional and must use `YYYY-MM`; invalid periods return `422`.
Results are scoped to the requested outsource ID and sorted newest period first.
Amounts are returned as numbers, with missing numeric values represented as
`0`. A valid ID with no matching records returns an empty `data` array.
Requests are read-only and limited to 60 per minute.

Successful responses use this shape:

```json
{
  "success": true,
  "outsource_id": "DM20260001",
  "filters": {
    "period": "2026-09"
  },
  "data": [],
  "meta": {
    "count": 0
  }
}
```

Payslip records include `period`, `outsource_id`, `full_name`, `vendor`, `hke`,
`basic_salary`, `bpjs_kesehatan_deduction`, `loan_deduction`, and
`take_home_pay`. Incentive records include `period`, `outsource_id`,
`full_name`, `vendor`, `umk_amount`, and `incentive_amount`.
