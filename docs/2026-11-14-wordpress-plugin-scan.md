# WordPress Plugin Niche Scan — 14 November 2026

## What I scanned

- Queried the WordPress.org plugin API across **97 search terms** (payments, compliance, LMS, bookings, verticals, UK admin).
- Pulled detail on **48 shortlisted plugins**: active installs, star rating, last-updated date, support threads, age.
- Checked vendor pricing and sector evidence for the four finalists.
- Raw output: `results.txt`, `results2.txt`, `details.txt` in this folder. Scripts: `scan.js`, `scan2.js`, `details.js`.

Install counts are WordPress.org's buckets (e.g. "90,000"), not exact. Ratings are 0–100 on wp.org (60 = 3.0/5, 80 = 4.0/5).

## Headline findings

1. **The "abandoned popular plugin" arbitrage is mostly gone.** Across 60+ categories, the top plugins were nearly all updated within 0–6 months. The stale-and-popular exceptions were small: Timesheet by BestWebSoft (60 installs, 17 months, 54/100), Safety Exit (1,000, 13 months), UK Food Hygiene Rating (10 installs, 118 months). None is a business.

2. **Big demand + bad satisfaction is usually a trap, not an opening.** Examples: Event Tickets 70/236 at 90k installs, WooCommerce Tax 42/107 at 500k, Web Accessibility/Ally 58/163 at 500k, YITH Request a Quote 66/66 at 10k. These categories are support-heavy integrations where the dissatisfaction is structural. A solo developer inherits the support burden, not the margin.

3. **The clean gaps are missing add-ons inside healthy ecosystems, not missing plugins.** The strongest signal in the whole scan: CPD/CE credit tracking is a mature paid add-on market for LearnDash (Uncanny CE Credits $199–$499/yr, TeknoFlair CPD $99/yr) but **does not exist for Tutor LMS (90k installs) or LearnPress (60k installs)**. The only third-party option is Open CPD, a free certificate/badge issuer with under 10 installs that stores records outside WordPress.

4. **UK compliance is a moat only when the workflow recurs and the output format is regulator-defined.** Gift Aid and CPD qualify. Allergen labelling does not: DineKit already gives the matrix away free, and getting it wrong is a safety liability.

## Scorecard

Scores 1–5. "Weak competition" is inverted (5 = weak/absent). "Risk" is inverted (5 = low risk). Max 35.

| Niche | Demand | Weak competition | Monetisation | Buildability | Distribution | Moat | Risk | Total |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| **UK CPD records for Tutor LMS / LearnPress** | 4 | 4 | 5 | 3 | 4 | 4 | 3 | **27** |
| B2B RFQ / quote-to-order for WooCommerce | 4 | 2 | 3 | 3 | 5 | 2 | 4 | 23 |
| Gift Aid for membership / WooCommerce | 3 | 2 | 3 | 3 | 4 | 3 | 3 | 21 |
| Parish council document governance | 2 | 3 | 4 | 4 | 1 | 3 | 4 | 21 |
| UK postcode / address validation | 3 | 3 | 2 | 3 | 3 | 2 | 4 | 20 |
| Event tickets | 5 | 1 | 3 | 2 | 5 | 1 | 2 | 19 |
| Allergen matrix | 3 | 1 | 2 | 4 | 4 | 2 | 2 | 18 |
| Timesheets | 2 | 3 | 2 | 4 | 2 | 2 | 3 | 18 |

## Top pick: UK CPD records for Tutor LMS and LearnPress

### The gap, in evidence

- **Tutor LMS**: 90,000 installs, 88/100 rating, updated this month. Its official add-on library covers certificates, prerequisites, gradebook, reports, subscriptions — **no CPD/CE credit module**. A review of the ecosystem explicitly notes CE tracking on Tutor LMS is "less developed" than LearnDash and lists the missing pieces: partial credits, multiple credit types, regulatory reporting, expiry/renewal, AMS integration.
- **LearnPress**: 60,000 installs, 86/100 rating. The official Certificates add-on ($39) issues completion certificates but has **no credit accumulation or CPD tracking**.
- **LearnDash**: the incumbents live here. Uncanny CE Credits handles credits, rollover dates, CSV export, verification reports and PDF certificates, but is explicitly US-style CEU/state-rule focused. TeknoFlair adds off-platform claims and audit portals at $99/yr. Neither implements UK regulator formats.
- **Open CPD**: free wp.org plugin, but it is a certificate/badge issuer tied to an external Open CPD account, records live outside WordPress, and it has under 10 installs.

So the wedge is not "another certificate plugin". It is **the records layer**: credit accumulation, UK regulator categories, reflective evidence, and audit-ready exports, sitting on top of the LMS the training provider already uses.

### Who pays

UK training providers, professional bodies and employers running LearnDash/Tutor LMS/LearnPress who must prove learners' CPD to a regulator or accreditor. The buyer already pays for the LMS and often pays an agency for custom CPD modules. The pain is recurring: annual statements, 3-year or 5-year cycles, audit sampling.

### What v1 does

- Assign CPD hours/credits to courses, and optionally lessons/topics.
- Split hours by the categories regulators actually use:
  - **NMC**: 35 hours over 3 years, 20 participatory; five written reflective accounts on the approved form; Code mapping.
  - **GDC**: 100/75/50 hours over 5 years, verifiable, minimum 10 hours every 2 years; PDP; four development outcomes (A–D); annual and end-of-cycle statements.
  - **HCPC**: five CPD standards; at least two activity types; CPD profile for audit.
- Learner dashboard: totals, category split, progress against the selected regulator's cycle.
- Off-platform CPD logging with evidence upload and admin approval.
- Reflective account templates and a CPD profile/portfolio export (PDF + CSV).
- Certificate with hours, outcomes, provider, verification code.
- Expiry and cycle reminders.
- Admin reporting: per learner, per group, audit trail.

Start with **one** regulator preset (GDC is the most prescriptive and the dental sector is already familiar to you), then add NMC and HCPC. The architecture should be regulator-as-config, not hard-coded.

### Pricing

- Free core: credit tracking + dashboard for one LMS, one regulator preset.
- Pro: £99/yr single site, £199/yr 5 sites, £349/yr 25 sites.
- Anchors: TeknoFlair $99/yr, Uncanny $199–$499/yr, ThimPress certificates $39 one-off. A UK-specific records layer can sit at the top of that range because it replaces manual portfolio work, not just a certificate.

### Why it compounds

- Records are sticky: once a provider's learners have cycles, evidence and audit history in the plugin, switching cost rises every month.
- Annual renewal matches the CPD cycle; no one cancels mid-cycle.
- Each regulator preset and LMS integration widens the same product rather than starting a new one.
- Distribution is inbound: wp.org search for "CPD", LMS add-on directories, and SEO pages like "Tutor LMS CPD plugin" / "LearnPress CPD records" / "GDC CPD portfolio WordPress".

### Realistic economics

Honest range: 3–6 months to first paying customer, 50–150 Pro customers by month 18, £5k–£15k/yr. Not life-changing, but it compounds and the work is entirely building. The upside case is becoming the default CPD layer for UK WordPress LMS providers, which is a defensible £50k–£100k/yr business.

### What would kill it

- Tutor LMS or LearnPress ship native CPD tracking (they have the distribution; watch their changelogs).
- Open CPD expands from certificates into records and goes paid.
- UK training providers turn out to buy CPD records as a service from agencies, not as software.
- Regulator formats change and the maintenance burden grows faster than revenue.

Mitigation: start with Tutor LMS (largest gap, active ecosystem), keep the regulator layer configurable, and ship the free core on wp.org so install growth is the validation signal.

## Runner-up: B2B RFQ / quote-to-order for WooCommerce

Why it scored well: 25k+ installs across the category, very high search intent, and the category leader (YITH, 10k installs) is disliked — 3.3/5 from 66 reviews, with complaints about bugs, Elementor conflicts, disappearing images and support. The functional gaps are specific: quote-to-order conversion, variable products, role-based pricing, PDF quotes, and email deliverability.

Why it is second: NP Quote Request already has 4.6/5 from 87 reviews and 8k installs, so the "weak incumbent" story is only half true. It is a feature market with several competent entrants, and support load is higher because every quote plugin fights themes, checkout and email. Better volume, worse moat.

## Also considered and rejected

- **Gift Aid for membership/WooCommerce** — real gap (no MemberPress add-on; PMPro's is free but basic; WooCommerce options are dated or tied to SkyDonate; Tinwell charges £249+VAT). But Charitable and GiveWP bundle Gift Aid, free entrants are appearing (Donor Merchant, Cheerful Give, Swft), and the buyer is price-sensitive. A credible niche, not the best one.
- **Parish council document governance** — zero wp.org competition and 10k councils with transparency/accessibility duties. But clerks buy through specialist agencies and SaaS (CouncilPapers, Document Library Pro); wp.org search will not reach them. Distribution score 1.
- **Allergen matrix** — DineKit already gives it away free with the 14 statutory allergens and a printable matrix. Liability risk on top. No.
- **UK postcode/address validation** — thin wrappers around paid APIs (Ideal Postcodes, Postcodes4U); low price point, data licensing cost, weak moat.
- **Timesheets** — the stale incumbent is real, but SaaS (Clockify, Toggl, Harvest) owns the job.
- **Event tickets** — big demand, poor satisfaction, but crowded and support-heavy. The Events Calendar owns the category.

## Validation plan, no outreach

The scan validates the market shape, not the buyer. Cheapest test that fits "build, don't sell":

1. **Build the free core for Tutor LMS only** (2–3 weeks): credit fields on courses, learner dashboard, one regulator preset (GDC), CSV export. List it on wp.org.
2. **Instrument it**: installs, active installs after 30 days, support threads, and a "Pro coming" link.
3. **Decision point at 60 days**:
   - Go: 100+ installs and at least 5 people asking for regulator exports/PDF portfolios.
   - Pivot: installs but no Pro interest → the records layer is not worth paying for; try the RFQ route.
   - Kill: under 30 installs → distribution is too thin; stop.
4. **Then build Pro** (PDF portfolios, NMC/HCPC presets, reminders, multi-site licensing) and price at £99/yr.

This is the same "ship free, monetise Pro" model that built most solo plugin businesses. It costs hosting and time, not money, and the install graph is a real signal rather than an opinion.

## Sources

- WordPress.org plugin API (install counts, ratings, update dates): https://api.wordpress.org/plugins/info/1.2/
- Tutor LMS add-ons: https://tutorlms.com/addons/
- Tutor LMS ecosystem review noting CE gap: https://fatlabwebsupport.com/blog/website-support/tutor-lms-review/
- Uncanny Continuing Education Credits: https://www.uncannyowl.com/continuing-education-credits-for-learndash/
- TeknoFlair LearnDash CPD: https://teknoflair.com/learndash-continuing-professional-development/
- LearnPress Certificates add-on: https://thimpress.com/product/certificates-add-on-for-learnpress/
- Open CPD Certificates: https://wordpress.org/plugins/open-cpd-certificates/
- NMC CPD and reflective accounts: https://www.nmc.org.uk/revalidation/requirements/cpd/ and https://www.nmc.org.uk/revalidation/requirements/written-reflective-accounts/
- GDC CPD guidance: https://www.gdc-uk.org/docs/default-source/education-and-cpd/cpd/cpd-scheme/cpd-guidance-for-professionals.pdf
- HCPC CPD standards: https://www.hcpc-uk.org/standards/standards-of-continuing-professional-development/
- YITH RFQ reviews: https://wordpress.org/support/plugin/yith-woocommerce-request-a-quote/reviews/
- NP Quote reviews: https://wordpress.org/support/plugin/woo-rfq-for-woocommerce/reviews/
- Charitable Gift Aid: https://www.wpcharitable.com/extensions/charitable-gift-aid/
- GiveWP Gift Aid: https://givewp.com/downloads/plugins/give-gift-aid/readme.txt
- Tinwell UK comparison: https://tinwell.org/ledger/best-wordpress-donation-plugins-uk/
- Sky Gift Claim: https://skywebdesign.co.uk/products/sky-gift-claim/
- PMPro Gift Aid add-on: https://github.com/strangerstudios/pmpro-gift-aid/
- HMRC Gift Aid declarations: https://www.gov.uk/guidance/gift-aid-declarations-claiming-tax-back-on-donations
- Charity Commission sector data: https://register-of-charities.charitycommission.gov.uk/en/sector-data/sector-overview
- DineKit allergen menu: https://dinekit.co.uk/allergen-menu/
- CouncilPapers WordPress plugin: https://www.councilpapers.com/help/settings/wordpress-plugin
