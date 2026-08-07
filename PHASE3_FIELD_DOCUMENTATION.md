# Phase 3: Complete Recruitment Portal Enhancement — Field Documentation

## 1. Newly Added Fields (Frontend Only)

These fields were added to the frontend form but do NOT exist in the current spreadsheet.
They will be submitted to Apps Script but may be ignored until the spreadsheet is updated.

| Field ID | Name Attribute | Type | Purpose |
|----------|---------------|------|---------|
| `province` | `province` | select | Province auto-detected from NIK or manually selected |
| `district` | `district` | select | District auto-detected from NIK or manually selected |

> **Note:** The `city` field already exists in the spreadsheet but was previously a free-text input.
> It is now a dropdown populated from the embedded region dataset.

---

## 2. Existing Fields (Unchanged IDs and Names)

All existing field IDs and name attributes remain unchanged from the original implementation.

| Field ID | Name Attribute | Type | Spreadsheet Column | Status |
|----------|---------------|------|-------------------|--------|
| `full_name` | `full_name` | text | Nama Lengkap | ✅ Existing |
| `nik` | `nik` | text | NIK | ✅ Existing |
| `birth_date` | `birth_date` | text | Tanggal Lahir | ✅ Existing |
| `age` | `age` | number | Usia | ✅ Existing |
| `gender` | `gender` | select | Jenis Kelamin | ✅ Existing |
| `marital_status` | `marital_status` | select | Status Pernikahan | ✅ Existing |
| `email` | `email` | email | Email | ✅ Existing |
| `phone` | `phone` | tel | No. HP | ✅ Existing |
| `address` | `address` | textarea | Alamat | ✅ Existing |
| `city` | `city` | select | Kota | ✅ Existing (was text, now dropdown) |
| `position_applied` | `position_applied` | select | Posisi Dilamar | ✅ Existing |
| `education` | `education` | select | Pendidikan | ✅ Existing |
| `work_experience` | `work_experience` | select | Pengalaman Kerja | ✅ Existing |
| `last_company` | `last_company` | text | Perusahaan Terakhir | ✅ Existing |
| `current_employment_status` | `current_employment_status` | select | Status Bekerja | ✅ Existing |
| `available_to_join` | `available_to_join` | select | Kesediaan Bergabung | ✅ Existing |
| `expected_salary` | `expected_salary` | text | Ekspektasi Gaji | ✅ Existing |
| `recruitment_source` | `recruitment_source` | select | Sumber Info | ✅ Existing |
| `agreement` | `agreement` | checkbox | — | ✅ Existing (not stored) |

---

## 3. Spreadsheet Columns to Add Later (Future Production Migration)

> **IMPORTANT:** Do NOT add these columns now. The spreadsheet will be updated manually later.
> These are documented here for planning purposes only.

| # | Column Name | Data Type | Source Field | Notes |
|---|------------|-----------|-------------|-------|
| 1 | `Provinsi` | Text | `province` | Province code (e.g., "32") or name |
| 2 | `Kecamatan` | Text | `district` | District name or code |

### Migration Steps (When Ready)

1. Open the Google Spreadsheet
2. Go to `data_kandidat` sheet
3. Add column `Provinsi` after `Kota`
4. Add column `Kecamatan` after `Provinsi`
5. Update `SHEET_HEADERS` in `Kode.gs` to include new columns
6. Update `simpanDataKandidat()` in `Kode.gs` to map `province` and `district` fields
7. Use `ensureExtraHeaders_()` pattern to safely add columns without breaking existing data

---

## 4. Expanded Dropdown Options (No Schema Changes)

These dropdowns were expanded with more options but use the same field IDs and names.
No spreadsheet changes are required — values are stored as text.

| Field | Original Options | New Options |
|-------|-----------------|-------------|
| `marital_status` | 3 | 4 (added Widowed) |
| `position_applied` | 8 | 42 (corporate positions) |
| `education` | 5 | 12 (detailed education levels) |
| `work_experience` | 5 | 7 (expanded ranges) |
| `current_employment_status` | 4 | 10 (detailed statuses) |
| `available_to_join` | 4 | 10 (detailed timeframes) |
| `recruitment_source` | 8 | 16 (modern sources) |

---

## 5. UI/UX Enhancements (No Backend Impact)

| Feature | Implementation |
|---------|---------------|
| NIK moved to first field | Visual reorder only, same ID/name |
| Smart birth date | Auto-slash DD/MM/YYYY formatting |
| Auto age | Calculated from birth date, readonly field |
| Phone +62 prefix | Display prefix, submits as `62xxxxxxxxxx` |
| Salary Rp prefix | Display formatted, submits as numeric |
| Sticky progress bar | Counts only VALID required fields |
| NIK parser | Extracts birth date, gender, province, city |
| Dukcapil notice | Info box clarifying no verification |
| Smooth scrolling | CSS `scroll-behavior:smooth` |
| Prevent double submit | `isSubmitting` flag |
| Autocomplete attributes | `name`, `email`, `tel`, `organization` |
| Validation improvement | Red message hides immediately on valid |

---

## 6. Compatibility Verification

| Check | Status |
|-------|--------|
| Apps Script (`simpanDataKandidat`) | ✅ Unchanged |
| `Kode.gs` | ✅ Unchanged |
| `google.script.run` | ✅ Unchanged |
| Spreadsheet mapping | ✅ Unchanged (new fields ignored by backend) |
| Existing field IDs | ✅ All preserved |
| Existing name attributes | ✅ All preserved |
| Dashboard.html | ✅ Unchanged |
| No `fetch()` calls | ✅ Confirmed |
| No external JSON loading | ✅ Confirmed (REGIONS embedded) |
| Breaking Changes | **None** |