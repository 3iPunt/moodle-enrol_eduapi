# Edu-API (`enrol_eduapi`)

Enrolment method that syncs organizations, courses, users and enrolments from
an external provider compatible with the 1EdTech Edu-API v1p0 specification.
It does not modify Moodle core or the theme: it is an automatic enrolment
plugin, with no manual enrolment interface.

## Certification

<a href="https://site.imsglobal.org/certifications/3punt/moodle-eduapi"><img src="https://site.imsglobal.org/sites/default/files/media/images/logo_ims/1edtech_trusted-apps-certified.svg" alt="1EdTech Certified" border="0" width="160"></a>

The plugin is certified by 1EdTech as an **Edu-API v1.0** conformant consumer.
Product listing: <https://site.imsglobal.org/certifications/3punt/moodle-eduapi>

The certification logo is used under the 1EdTech logo licence and links to the
product's entry in the 1EdTech TrustEd Apps Directory, as required by the
1EdTech Certification Process and Branding Guidelines.

## What it does

- Syncs the provider's organizations as Moodle course categories, preserving
  their hierarchy.
- Syncs courses (and, depending on configuration, groups) from the provider's
  offerings.
- Syncs users, matching them against existing Moodle accounts using the
  configured criteria.
- Syncs enrolments and each user's role in each course.

## How it works

- Synchronisation is automatic: it runs through a scheduled task and requires
  no manual intervention from teachers or students.
- The connection to the provider authenticates with OAuth2 (Client
  Credentials Grant); credentials are configured once in the plugin settings.
- The plugin never deletes courses or users: when an element disappears at
  the source, it is unenrolled, suspended or left untouched depending on
  configuration.
- If the plugin is disabled it stops syncing, but the courses, users and
  enrolments it already created remain.

## Requirements

- Moodle 4.5 or later.
- No other plugins required.
- Network access from the Moodle server to the configured Edu-API provider.

## Installation

1. Copy the code into `enrol/eduapi/`.
2. Complete the installation from **Site administration › Notifications**
   (or via CLI: `php admin/cli/upgrade.php --non-interactive`).
3. Purge caches (**Site administration › Development › Purge caches** or
   `php admin/cli/purge_caches.php`).

## Settings

Full configuration lives under **Site administration › Plugins › Enrolments
› Edu-API**:

| Block | What it configures |
|---|---|
| **Connection** | The provider's `token_url`, `root_url`, `clientid` and `secret` (OAuth2 Client Credentials), with a test-connection page. `root_url` must be the `servers` URL declared in the provider's OpenAPI discovery document (for example `https://example.org/ims/eduapi/base/v1p0`), not the hostname of the discovery document itself. |
| **Education offering** | Which offering level becomes a course (`CourseOffering` or `ComponentOffering`; the level not chosen becomes groups) and the shortname attribute. |
| **User matching** | Moodle field vs Edu-API attribute, with persistent mapping and an option to create unmatched users. |
| **Role mapping** | A Moodle role (or "Do not enrol") for each `RoleTypeEnum` value. |
| **Enrollment statuses** | An action for each of the 17 `EnrollmentStatusEnum` values (active / suspended / unenrol / ignore). |
| **Sync scope** | Organizations and academic session to sync, inactive exclusion and keeping existing courses. |

The full synchronisation is run by the `full_sync` scheduled task (disabled
by default), and the `eduapi_offering_webhook` web service triggers an
on-demand sync of a single offering.

## Uninstalling

The plugin uninstalls cleanly from **Site administration › Plugins**; the
user mapping table (`enrol_eduapi_user_map`) is removed, but courses,
categories, users and enrolments already created in Moodle remain.

## 🛠️ Development (optional)

```bash
# Unit tests
vendor/bin/phpunit --filter enrol_eduapi
```

To develop or test without a real Edu-API provider, use
[3iPunt/mock-eduapi](https://github.com/3iPunt/mock-eduapi): a zero-dependency
mock Edu-API v1p0 REST provider with OAuth2 Client Credentials, seed data and
an interactive dashboard. It ships its own `docker-compose.yml` and documents
the exact `token_url`/`root_url`/credentials values to configure this plugin
against it.

## 🙏 Acknowledgements

The architecture of this plugin (OAuth2 client, container and factories,
entity/collection pipeline, converters, and the test-connection flow) is
modelled on [enrol_oneroster](https://github.com/moodlehq/moodle-enrol_oneroster)
by Andrew Nicols and Moodle HQ. Several features (the per-offering webhook,
the JSON progress trace, inactive record exclusion, per-item fault tolerance)
were inspired by the
[universis-project fork](https://github.com/universis-project/moodle-enrol_oneroster)
of that plugin. Both are released under the GNU GPL v3 or later, as is this
plugin. The code here is an independent implementation for the Edu-API v1p0
specification, not a fork.

## 📄 Licence

[GNU GPL v3 or later](https://www.gnu.org/copyleft/gpl.html) — 2026 [Tresipunt](https://tresipunt.com) (contacte@tresipunt.com)

---

<p align="center">
  <a href="https://tresipunt.com"><img src="pix/tresipunt_logo.svg" alt="Tresipunt" width="120" height="30"></a>
</p>
