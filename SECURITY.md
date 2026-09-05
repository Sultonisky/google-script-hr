# Security Policy

## Project Status

MITO HRIS is a proprietary, internal application for MITO Group.

This project is **not open source** and is **not distributed under the MIT
License**. The MIT licenses of Laravel, PHP packages, JavaScript packages, or
other third-party dependencies do not grant permission to use, copy, modify,
or distribute the MITO HRIS application or its business data.

The source repository, application code, configuration, documentation,
credentials, Google Sheets data, Google Drive documents, and deployment details
are restricted to authorized personnel.

## Supported Versions

Only the currently deployed Laravel application under `mito-hris-laravel/` is
actively supported. Historical files and archived documentation are retained
for project history and are not supported runtime components.

## Reporting a Vulnerability

Do not report security vulnerabilities in public issues, pull requests, chat
channels, or other publicly accessible locations.

Authorized users should report suspected vulnerabilities through the project's
internal security or system owner channel. Include:

- A concise description of the issue
- The affected route, component, or configuration area
- Reproduction steps or a minimal proof of concept
- The potential impact
- Any relevant logs or screenshots, after removing credentials and personal data

Do not include passwords, service-account keys, access tokens, `.env` values,
Google credentials, or production personal data in a report.

## Response and Disclosure

Reports will be reviewed by the project owner or designated security contact.
Security fixes, validation, and disclosure decisions are handled internally.
Do not disclose a suspected vulnerability or proprietary project information
to third parties without written authorization.

## Security Responsibilities

Contributors and authorized maintainers must:

- Keep secrets out of Git and application logs.
- Preserve authentication, authorization, RBAC, domain isolation, and CSP
  protections.
- Avoid destructive tests against production Google Sheets or Drive resources.
- Treat candidate, employee, applicant, and MPR data as confidential.
- Keep deployment credentials and server access restricted.

## Contact

Security reports should be sent through the MITO Group internal security or
system owner channel. Do not publish a security report publicly.

## Last Updated

2026-09-05
