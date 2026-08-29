# Changes

## Unreleased

- Component offering group membership is now kept in sync: when
  `offering_level = courseoffering` and `sync_groups` is on, users enrolled
  in a ComponentOffering are added to its Moodle group after the course's
  own enrolments are processed, and withdrawn/cancelled/deleted enrolments
  are removed from the group. Only applies to a user who also holds a
  CourseOffering-level enrolment; a component-only enrolment is skipped.
- Fixed the data sync silently matching zero offerings when
  `datasync_academic_session` is configured with a `schoolYear` (or any
  parent) session while offerings are tagged with a child `semester`
  session: the configured session now also accepts offerings tagged with
  any of its descendant sessions.

## 1.0.0 (2026-08-29)

First stable release. The plugin is certified by 1EdTech as an Edu-API v1.0
conformant consumer:
https://site.imsglobal.org/certifications/3punt/moodle-eduapi

- Plugin maturity raised to stable.
- Supported Moodle range declared and CI-verified as 4.5 to 5.2 (PHP 8.3).
- Greek (el) language pack added; English, Spanish and Catalan updated.
- Clarified the `root_url` setting help: use the `servers` URL from the
  provider's OpenAPI discovery document, not the discovery hostname.
- GPL-3.0 licence file added; README links the mock Edu-API provider used for
  development and testing.

No functional changes to the synchronisation since 0.1.0. No database
changes; upgrading only bumps the version.

## 0.1.0 (2026-08-18)

Initial release.

- Full 1EdTech Edu-API v1p0 synchronisation: organizations to course
  categories (nested hierarchy), offerings to courses and groups, persons to
  Moodle users (configurable matching, optional automatic creation), and
  enrolments with per-role and per-enrollment-status mapping.
- OAuth2 Client Credentials Grant authentication, with proactive token
  refresh and a single automatic retry on a 401 response.
- Fault-tolerant pagination: a transient failure fetching one page of a
  collection no longer aborts the whole synchronisation run.
- `eduapi_offering_webhook` web service to trigger an on-demand sync of a
  single offering.
- Full Privacy API provider (metadata, export, delete, bulk userlist).
- `full_sync` scheduled task (disabled by default).
- PHPUnit test suite and CI pipeline (phplint, codechecker, PHPUnit) across
  PHP 8.2/8.3 and PostgreSQL/MariaDB.

No breaking changes (first release).
