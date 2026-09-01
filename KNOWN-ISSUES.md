# Known issues

## Edu-API operations not consumed (certification scope)

The plugin is certified by 1EdTech as an Edu-API v1.0 conformant *consumer*.
A consumer is certified for the operations it actually consumes, so the scope
below is intentional, not a defect.

The plugin's sync model is scope-and-collection driven (organizations, then a
selected academic session, then per-offering enrollment collections). It never
performs per-id lookups of academic sessions, enrollments or affiliations, and
templates are out of scope. As a result these spec operations are not called:

| Operation | Why it is not consumed |
|---|---|
| `getAllCourseTemplates`, `getCourseTemplateById`, `getAllComponentTemplates`, `getComponentTemplateById` | Templates are out of scope. There is no Moodle mapping for them yet (see the offering entities' docblocks). |
| `getAllEnrollments`, `getEnrollmentById` | Enrollments are fetched per offering via `getAllEnrollmentsForCourseOffering` / `getAllEnrollmentsForComponentOffering`. A global fetch or a by-id lookup is not needed by the sync. |
| `getAcademicSessionById` | The academic session scope is resolved from the full `getAllAcademicSessions` collection (used for the school-year to child-semester expansion), so the by-id lookup is unused. |
| `getAllAffiliations`, `getAffiliationById` | `Affiliation` (person + organization + role) has no Moodle equivalent and is not converted. |

Operations that ARE consumed include `getAllOrganizations`, `getOrganizationById`,
`getAllAcademicSessions`, `getAllPersons`, `getPersonById`, `getAllCourseOfferings`,
`getCourseOfferingById`, `getAllComponentOfferings`, `getComponentOfferingById`,
`getAllEnrollmentsForCourseOffering` and `getAllEnrollmentsForComponentOffering`.

Widening the certification profile to the operations above would require
consuming them for a real purpose (a feature that uses them, or exercising them
in the conformance harness). Templates and affiliations would first need a
decided Moodle mapping. This is left out until a deployment or client requires a
broader conformance profile.

## Real vendor validation

Beyond the 1EdTech consumer conformance server, the plugin has not yet been
validated against a production Edu-API vendor with live institutional data.
