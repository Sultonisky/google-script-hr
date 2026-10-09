# Attendance-to-HRIS outsource directory sync

Attendance sends outsource Person List IDs and names to HRIS. HRIS creates
only IDs that do not already exist; an existing ID is reported as `skipped`
and none of its HRIS-managed personalia is changed. This endpoint is safe to
retry.

```http
POST /api/v1/outsource/persons/sync
Authorization: Bearer <HRIS_OUTSOURCE_SYNC_API_TOKEN>
Content-Type: application/json
Accept: application/json
```

Use a separate long random `HRIS_OUTSOURCE_SYNC_API_TOKEN` in both
applications. Do not reuse any other integration token. The endpoint accepts
up to 100 people per request and only accepts `outsource_id` and `full_name`:

```json
{
    "people": [
        {"outsource_id": "DM20260001", "full_name": "Employee Name"}
    ],
    "dry_run": true
}
```

`dry_run` is optional and defaults to `false`. Use it to preview `would_create`
versus `skipped` records without writing. Normal requests return each ID with
status `created` or `skipped`; if an ID exists with a different normalized
name, it returns `conflict` without changing the HRIS record. The response
includes summary counts. Invalid payloads receive `422`; invalid credentials
receive `401`; an unset HRIS token fails closed with `503`. Requests are
limited to 60 per minute and must use HTTPS.

HRIS is the master for outsource persons; Attendance no longer creates Person
List records. This endpoint is only used for the one-time reconciliation of
existing Attendance records: run `php artisan hris:sync-outsource-persons` on
the Attendance backend to preview, then add `--execute` to create only the
missing HRIS IDs. No Attendance update is sent to HRIS, and no HRIS personalia
is sent back to Attendance by this flow. Review all `CONFLICT` entries manually
before using the Attendance record, because its ID already identifies a
different person in HRIS.

## Resolving an ID collision

Attendance IDs are kept as-is. If an HRIS ID belongs to a different person
than the Attendance record with the same ID, move the HRIS person to a parked
ID listed in `hris.outsource.reserved_ids` (the generator never continues
numbering from parked IDs):

```sh
php artisan mito:outsource-reassign-id DM20260129 DM20261000
php artisan mito:outsource-reassign-id DM20260129 DM20261000 --execute --reason="Bentrok ID dengan Attendance"
```

The first call only previews the rows per table. With `--execute`, the person,
payslips, incentives (when those tables are migrated), employee documents,
archived document files, and audit logs move to the new ID in one transaction,
and an `id_reassigned` audit entry
is written. Drive files keep their original file names. Then run the
Attendance reconciliation above so HRIS creates the Attendance person under the
freed ID.

## HRIS-to-Attendance push (new outsource persons)

Whenever HRIS creates an outsource person (HR "Tambah Outsource", the public
outsource form, or new rows from Import Excel), it sends the Outsource ID and
name to the Attendance backend:

```http
POST {ATTENDANCE_API_BASE_URL}/api/v1/integrations/hris/outsource-persons
Authorization: Bearer <ATTENDANCE_OUTSOURCE_PUSH_API_TOKEN>
```

The payload, limits, `dry_run`, and per-ID statuses (`created`, `skipped`,
`conflict`, `would_create`) mirror the endpoint above. Attendance creates a
missing ID as `inactive`, without cabang or pin assignments, with the default
login PIN `123456`; an administrator activates the person after assigning a
cabang. Existing Attendance records, including soft-deleted ones, are never
changed; a different name or a deleted record is reported as `conflict`.

Configure `ATTENDANCE_API_BASE_URL` (HTTPS) and a separate long random
`ATTENDANCE_OUTSOURCE_PUSH_API_TOKEN` in HRIS, and the same token in the
Attendance backend. When either value is empty, HRIS skips the push.

The push is best effort: if Attendance is unreachable, the HRIS record is still
saved and a warning is logged. Retry or backfill with:

```sh
php artisan mito:outsource-push-attendance
php artisan mito:outsource-push-attendance --execute
```

The first call only previews; `--execute` creates the missing IDs in batches of
100 and is safe to repeat.

HR users with `manage_outsource` can also use **Sync ke Attendance** on the
Outsource list to preview all HRIS Outsource IDs and names first. The preview is
a dry-run and shows which IDs are missing, already exist, or conflict. The user
must explicitly confirm before HRIS submits the actual sync. Attendance creates
only missing IDs; existing people are left unchanged. The result reports
created, skipped, and conflicting IDs. In the modal, a conflict means that the
same ID already exists in Attendance with a different name, or its Attendance
record has been soft-deleted; the row explains which case was detected. A name
mismatch means an active Attendance person has a different name from HRIS. If
the ID is confirmed to belong to the same person and the difference is only a
typo, have an Attendance administrator correct its name to match HRIS, then
preview again. A deleted-record conflict means the ID still belongs to a
soft-deleted Attendance record; have an administrator review its history and
decide how to resolve it. Neither case means the sync changed or lost data:
Attendance does not create, overwrite, or restore conflicting rows. Do not
change an ID just to clear the conflict.
