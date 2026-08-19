# Changes

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
