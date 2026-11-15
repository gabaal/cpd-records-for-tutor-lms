# CPD Records for Tutor LMS — v1 spec

Status: free core build started 2026-11-14.
Working title: **CPD Records for Tutor LMS**. Slug: `cpd-records-for-tutor-lms`.

## 1. Problem

UK dental professionals must keep an audit-ready CPD record for their GDC five-year cycle. Training providers using WordPress LMSs issue certificates, but certificates are not a CPD record: they do not total hours against the regulator's requirement, split by development outcome, flag the two-year minimum, or produce the annual statement evidence.

Tutor LMS (90k installs) and LearnPress (60k) have no CPD credit tracking. LearnDash has paid add-ons (Uncanny CE Credits $199–$499/yr, TeknoFlair CPD $99/yr), but both are US/CEU-first and do not implement GDC rules.

## 2. Buyer and user

- **Buyer:** UK training provider, professional body or employer running Tutor LMS, who needs learners' CPD to be credible and audit-ready.
- **User:** dental professional taking courses; needs a simple record and a progress view.
- **Admin:** training provider staff; assigns CPD hours to courses and exports records.

## 3. v1 scope (free core)

In scope:

1. **GDC preset** — roles, required hours, CPD year boundaries, development outcomes A–D.
2. **Course credit fields** — CPD hours and development outcomes on each Tutor LMS course.
3. **Automatic records** — when a learner completes a course, write a CPD record once.
4. **Learner dashboard** — shortcode showing cycle progress, hours by outcome, two-year rule status, and recent records.
5. **Learner setup** — choose GDC role and cycle start date from the dashboard (admins can override on the user profile).
6. **CSV export** — learner exports their own records; admin exports all.
7. **Admin records page** — recent records, learner lookup, export.
8. **Uninstall preference** — optionally delete data on uninstall.

Out of scope for v1 (Pro):

- Manual/off-platform CPD entries and evidence uploads.
- Reflective accounts (NMC form, GDC reflection log).
- PDF portfolio export.
- NMC and HCPC presets.
- Email reminders and annual statement workflow.
- Multi-site licensing and agency reporting.
- LMSs other than Tutor LMS (LearnPress next).

## 4. GDC rules implemented

Source: GDC Enhanced CPD guidance and CPD guidance (2024–2025).

| Role | Hours per 5-year cycle | CPD year |
|---|---:|---|
| Dentist | 100 | 1 Jan – 31 Dec |
| Dental therapist, dental hygienist, orthodontic therapist, clinical dental technician | 75 | 1 Aug – 31 Jul |
| Dental nurse, dental technician | 50 | 1 Aug – 31 Jul |

- Cycle: five years from the learner's own cycle start date (from eGDC/MyGDC), not a global date.
- Minimum: at least **10 hours in every two consecutive CPD years**; no two consecutive zero-hour years.
- Each activity must link to at least one development outcome:
  - **A** — Effective communication with patients, team and others.
  - **B** — Effective management of self and others, teamwork and leadership.
  - **C** — Maintenance and development of knowledge and skill within your field of practice.
  - **D** — Maintaining skills, behaviours and attitudes that keep patient confidence.
- Annual statement: due 28 days after the CPD year ends (28 Jan for dentists, 28 Aug for DCPs).
- Records retained five years after the cycle ends (the plugin does not delete automatically).

## 5. Data model

Custom table `{prefix}cpd_records`:

| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | |
| user_id | bigint unsigned | learner |
| course_id | bigint unsigned | 0 for future manual entries |
| regulator | varchar(20) | `gdc` |
| activity_title | varchar(255) | course title at completion |
| activity_date | date | completion date |
| hours | decimal(6,2) | CPD hours |
| outcome_a..d | tinyint(1) | 0/1 |
| source | varchar(20) | `tutor_lms`, later `manual` |
| evidence_url | varchar(500) | reserved for Pro |
| status | varchar(20) | `approved`, later `pending` |
| dedupe_key | varchar(100) unique | `tutor:{user_id}:{course_id}` |
| created_at / updated_at | datetime | |

User meta:

- `_cpd_records_gdc_role` — role key.
- `_cpd_records_cycle_start` — `Y-m-d`.

Course meta:

- `_cpd_records_hours` — float.
- `_cpd_records_outcomes` — array of `A`/`B`/`C`/`D`.

Options:

- `cpd_records_version`
- `cpd_records_delete_on_uninstall`

## 6. Integration

- Hook: `tutor_course_complete_after` (Tutor LMS 4.2.1, `CourseModel::complete_course`), signature `( $course_id, $user_id )`.
- Course post type: `tutor()->course_post_type` (default `courses`), with a `function_exists( 'tutor' )` guard.
- Idempotency: unique `dedupe_key`; a retake does not create a second record in v1.

## 7. Screens

1. **Course edit** — side meta box "CPD credit (GDC)": hours (step 0.25) and outcome checkboxes. Course list column shows hours.
2. **User profile** — "CPD details" section: GDC role and cycle start date.
3. **Dashboard shortcode** — `[cpd_records_dashboard]` and `[tutor_cpd_dashboard]`:
   - setup card if role/cycle start missing;
   - progress bar and stat tiles;
   - two-year rule status;
   - outcomes breakdown;
   - records table;
   - "Download CSV" button.
4. **Tools → CPD Records** — recent records, learner filter, export all, uninstall preference.

## 8. Security and privacy

- Nonces on course meta, profile save, dashboard setup, and both exports.
- Capability checks: `edit_post` for course meta, `edit_user` for profile, `manage_options` for all-records export/admin page.
- `$wpdb->prepare` for every query with variables.
- Escape all output; sanitize all input.
- CSV injection guard: prefix cells beginning with `=`, `+`, `-`, `@`, tab or CR with an apostrophe.
- Data stored: user ID, course ID, dates, hours, outcomes. No health data.
- Privacy policy suggestion via `wp_add_privacy_policy_content`.
- Uninstall keeps data unless the admin opts in to deletion.

## 9. Accessibility

- Semantic table with `<caption>` and `<th scope>`.
- Progress bar has `role="progressbar"`, `aria-valuenow/min/max` and a text equivalent.
- Status uses text, not colour alone.
- Form controls have visible labels.

## 10. Success metrics and kill criteria

Test: free core listed on WordPress.org, Tutor LMS only, GDC only.

- **Go:** 100+ installs in 60 days and at least five requests for portfolio export / external CPD entry.
- **Pivot:** installs but no Pro interest → the records layer is not the pain; try the RFQ route.
- **Kill:** under 30 installs in 60 days.

## 11. Build plan

- Week 1: plugin scaffold, table, GDC calculator and tests, course meta, completion listener.
- Week 2: dashboard, setup flow, CSV export, admin page, readme, manual test on a local WordPress + Tutor LMS install.
- Week 3: submission polish (readme, screenshots, i18n, privacy), list on WordPress.org.

## 12. Open questions

- Should a retake create a new record? (v1: no; revisit with providers.)
- Should learners self-select their role, or should an admin approve it? (v1: self-select, admin override.)
- Does the buyer want employer/group reporting in the free core? (v1: no.)
- GDC cycle start is per learner; most providers will need a bulk import. (Pro.)
