# Implementation Plan

[Overview]
Change the logout redirect destination from the Login page to the Landing page, so after session clearing and audit log submission, the user lands on the public landing page instead of the login form.

[Types]
No type changes required.

[Files]

- `js/auth.html` — Modify the `handleLogout` function to change redirect URL from Login to Landing page

[Functions]

- **Modified: `handleLogout(e)`** in `js/auth.html`
  - Current behavior: After clearing session and writing audit log, redirects to `loginUrl` (`?page=login`)
  - New behavior: After clearing session and writing audit log, redirects to Landing page (`?page=landing` or base URL)
  - Add a new `landingUrl` variable derived from `baseUrl`
  - Change `window.top.location.replace(loginUrl)` → `window.top.location.replace(landingUrl)`

[Classes]
No class changes.

[Dependencies]
No new dependencies.

[Testing]

1. Click logout button in topbar
2. Verify session is cleared (attempt accessing dashboard → should fail)
3. Verify audit log entry is written to Audit_Log sheet
4. Verify redirect goes to Landing page, not Login page

[Implementation Order]

1. Modify `handleLogout` in `js/auth.html` to redirect to Landing page
