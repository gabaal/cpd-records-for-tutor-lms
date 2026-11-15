=== CPD Records for Tutor LMS ===
Contributors: gabaal
Tags: cpd, tutor lms, gdc, dental, cpd record, cpd tracker
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track GDC CPD hours and development outcomes for Tutor LMS courses.

== Description ==

CPD Records for Tutor LMS turns course completions into an audit-ready GDC CPD record.

Assign CPD hours and development outcomes to a Tutor LMS course. When a learner completes it, the plugin creates one CPD record for that learner and course. Learners see their progress towards the GDC five-year requirement, hours by development outcome, the two-year minimum status, and a CSV export.

This free version supports the General Dental Council (GDC) Enhanced CPD scheme:

* 100 hours per five-year cycle for dentists.
* 75 hours for dental therapists, dental hygienists, orthodontic therapists and clinical dental technicians.
* 50 hours for dental nurses and dental technicians.
* At least 10 hours in every two consecutive CPD years.
* Development outcomes A–D.
* Annual statement dates (28 January for dentists, 28 August for DCPs).

The plugin stores records in its own database table. It does not send data to any external service.

== Installation ==

1. Install and activate Tutor LMS.
2. Upload the `cpd-records-for-tutor-lms` folder to `/wp-content/plugins/`, or install the ZIP through Plugins → Add New.
3. Activate **CPD Records for Tutor LMS**.
4. Edit a Tutor LMS course and set **CPD hours** and **development outcomes** in the “CPD credit (GDC)” box.
5. Create a page containing `[cpd_records_dashboard]` (or `[tutor_cpd_dashboard]`).
6. Learners open that page, choose their GDC role and cycle start date, and see their record.

== Frequently Asked Questions ==

= Does this work without Tutor LMS? =

The CPD records table, dashboard and exports work, but course completions cannot be recorded without Tutor LMS.

= Can a learner record CPD from outside Tutor LMS? =

Not in this version. Off-platform entries, evidence uploads, reflective accounts and PDF portfolios are planned for the Pro version.

= What happens on uninstall? =

Records are kept by default. An administrator can opt in to deletion under Tools → CPD Records → Settings.

= Is any data sent to an external service? =

No.

= Does it support NMC or HCPC? =

Not yet. This version implements the GDC scheme only.

== Screenshots ==

1. The learner dashboard: progress towards the five-year requirement, hours by development outcome, the two-year minimum status, and the records in the cycle.
2. The CPD credit box on a Tutor LMS course, with CPD hours and development outcomes.
3. The admin records page, with learner filtering and the full CSV export.
4. The setup form where a learner chooses their GDC role and cycle start date.

== Changelog ==

= 0.1.0 =
* First free core: GDC course credits, automatic records, learner dashboard, CSV exports, admin records page.
