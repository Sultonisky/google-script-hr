# Employee Integration API

Attendance reads employee data from the active HRIS employee repository through
these read-only endpoints:

```http
GET /api/v1/employees?page=1&per_page=25&search=treasury&work_location=Jakarta&work_area=Head%20Office
GET /api/v1/employees/by-nik/{16-digit-NIK}
```

## Authentication and configuration

Send the integration secret as a Bearer token:

```http
Authorization: Bearer <integration-secret>
Accept: application/json
```

Set `HRIS_EMPLOYEE_API_TOKEN` in the HRIS environment to the same long, random
secret as `HRIS_API_TOKEN` in the Attendance backend. Requests without a valid
token receive `401`; if the HRIS token is not configured, the endpoint fails
closed with `503`. Keep the secret server-side and serve production requests
over HTTPS.

The API reads from the active HRIS employee repository. Configure the HRIS
data driver to the intended production source (PostgreSQL or Google Sheets);
the local driver is for development and tests.

## Employee list

`per_page` defaults to 25 and is limited to 100. Optional `search` matches
employee ID, name, NIK, job position, division, department, work location, and
work area. Optional `work_location` and `work_area` filters match
case-insensitively before pagination. The response contains distinct work
locations and work areas from the full employee source for Attendance filter
options. The endpoint is rate limited to 30 requests per minute.

## Lookup by NIK

The lookup validates that NIK has 16 digits, then compares it exactly after
normalizing the leading apostrophe used by some spreadsheet values. Invalid
NIK receives `422`; an unknown NIK receives `404`.

Both endpoints return only employee ID, NIK, name, status, job position,
division, department, branch, job-position location, work area, and work
location. Contact details, identity documents, banking, BPJS, addresses, and HR
notes are not exposed. The API is read-only.
