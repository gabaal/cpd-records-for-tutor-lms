# CPD Records for Tutor LMS

Free core of a GDC CPD tracker for Tutor LMS. Built 2026-11-14.

The plugin assigns CPD hours and development outcomes to a Tutor LMS course, writes a CPD record when a learner completes it, and gives each learner a dashboard with cycle progress, hours by outcome, the two-year minimum status, and CSV export.

See `SPEC.md` for the product scope and `docs/2026-11-14-wordpress-plugin-scan.md` for the market scan behind it.

## What is built

- GDC roles, required hours, CPD-year boundaries, outcomes A–D, annual statement dates.
- Course meta box: CPD hours and development outcomes.
- Automatic record on `tutor_course_complete_after` (Tutor LMS 4.2.1), idempotent per learner and course.
- Learner dashboard shortcode: `[cpd_records_dashboard]` or `[tutor_cpd_dashboard]`.
- Learner setup form for GDC role and cycle start date.
- User profile fields for the same values, editable by admins.
- CSV export: learner's own records, or all records for admins.
- Admin records page under Tools → CPD Records.
- Uninstall preference: keep data by default, optional deletion.
- Privacy policy suggestion, nonces, capability checks, `$wpdb->prepare`, CSV injection guard.

## Requirements

- WordPress 6.4+
- PHP 7.4+
- Tutor LMS (tested against 4.2.1)

## Install

Copy the `cpd-records-for-tutor-lms` folder into `wp-content/plugins/`, or install the packaged ZIP, then activate it. The folder name must be `cpd-records-for-tutor-lms` for a WordPress.org release.

1. Edit a Tutor LMS course and set **CPD hours** and **development outcomes** in the “CPD credit (GDC)” box.
2. Create a page containing `[cpd_records_dashboard]`.
3. Learners open the page, choose their GDC role and cycle start date, and see their record.

## Tests

The tests need a PHP 8.4 CLI (`php` below; in the original workspace the portable build was `../plugin-scan/tools/php84/php.exe`).

```bash
# Pure GDC calculator tests
php tests/run.php

# Class-loading and hook smoke test
php tests/smoke.php

# Syntax check
find . -name "*.php" -print0 | while IFS= read -r -d '' f; do
  php -l "$f" || exit 1
done
```

Current state: **48 calculator tests passing, 7 smoke checks passing.**

## Coding standards

The plugin is checked with PHPCS and the WordPress Coding Standards (WPCS 3.4) using `phpcs.xml.dist`.

```bash
composer global require squizlabs/php_codesniffer wp-coding-standards/wpcs:^3.4
phpcs
```

Current state: **clean against the full `WordPress` standard.**

## Local WordPress test site

`wp-test/` contains a disposable WordPress install running on SQLite, with Tutor LMS 4.2.1 and this plugin active.

```bash
cd wp-test

# Serve the site
./serve.sh
# http://localhost:8080

# Run WP-CLI against it
./wp.sh plugin list
./wp.sh eval 'global $wpdb; echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cpd_records");'
```

Demo accounts:

- Admin: `admin` / `password123`
- Learner: `learner` / `learner123`
- Dashboard page: `/my-cpd-record/` (page ID 11)

The SQLite database is at `wp-test/db/test.sqlite`. The test site uses SQLite only for local testing; production should use MySQL/MariaDB.

Verified end to end on 2026-11-14:

- Plugin activation creates `wp_cpd_records`.
- A real `Tutor\Models\CourseModel::mark_course_as_completed()` call writes a record with the course hours and outcomes.
- A duplicate completion does not create a second record.
- A course with no CPD hours is skipped.
- The dashboard renders for a dentist and a dental hygienist with the correct required hours.
- A learner with no role sees the setup form.
- The learner CSV export and admin all-records export produce the expected rows.
- A course titled `=SUM(1,1)` is exported as `'=SUM(1,1)` (formula injection guard).
- The course meta box registers on the `courses` post type.
- The dashboard page renders over HTTP (login prompt for logged-out visitors).

## Package

```powershell
powershell -NoProfile -File package.ps1
```

Creates `dist/cpd-records-for-tutor-lms-0.1.0.zip` with the plugin folder at the ZIP root.

## File map

```
cpd-records-for-tutor-lms.php   plugin header, constants, bootstrap
includes/class-cpd-records-gdc.php          GDC rules and pure-PHP calculator
includes/class-cpd-records-repository.php   custom table and queries
includes/class-cpd-records-activator.php    table creation
includes/class-cpd-records-course-meta.php  course hours/outcomes meta box and list column
includes/class-cpd-records-completion-listener.php  Tutor completion hook
includes/class-cpd-records-user-profile.php GDC role and cycle start on the profile
includes/class-cpd-records-dashboard.php    learner shortcode and setup form
includes/class-cpd-records-export.php       learner and admin CSV exports
includes/class-cpd-records-admin.php        Tools page, records list, uninstall setting
includes/class-cpd-records-plugin.php       wiring, privacy text, plugin links
assets/css/dashboard.css        dashboard styles (responsive, dark mode)
tests/run.php                   GDC calculator tests
tests/smoke.php                 class-loading and hook tests
phpcs.xml.dist                  PHPCS configuration (WordPress standard)
tools/make-assets.py            generates the WordPress.org icons and banner
wporg-assets/                   WordPress.org listing art (not packaged in the ZIP)
```

## Not in this version (Pro roadmap)

- Manual/off-platform CPD entries and evidence uploads.
- Reflective accounts (NMC form, GDC reflection log).
- PDF portfolio export.
- NMC and HCPC presets.
- Email reminders and annual statement workflow.
- Multi-site licensing and agency reporting.
- LearnPress support.

## Before a WordPress.org submission

- Confirm the `Contributors:` line in `readme.txt` matches your WordPress.org username (currently `gabaal`).
- Upload the listing art in `wporg-assets/` to the WordPress.org repository's top-level `assets/` directory (SVN, not trunk): `icon-128x128.png`, `icon-256x256.png`, `banner-772x250.png`, `banner-1544x500.png`.
- Upload `screenshot-1.png` to `screenshot-4.png` from `wporg-assets/` to the same SVN `assets/` directory. The captions in `readme.txt` describe them in order.
- Run `phpcs` with the WordPress Coding Standards (see “Coding standards” above) before tagging a release.
- Test on a real MySQL install with Tutor LMS, including the block editor and classic editor course screens.
- Decide whether hours without an outcome should block saving (currently it warns).
