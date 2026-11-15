<?php
/**
 * Pure-PHP tests for the GDC calculator.
 *
 * Run: php tests/run.php
 */

define( 'CPD_RECORDS_TESTS', true );

require_once __DIR__ . '/../includes/class-gdc.php';

$passed = 0;
$failed = 0;

/**
 * Assert strict equality.
 *
 * @param string $label    Test label.
 * @param mixed  $expected Expected value.
 * @param mixed  $actual   Actual value.
 */
function check( $label, $expected, $actual ) {
	global $passed, $failed;

	if ( $expected === $actual ) {
		++$passed;
		echo "PASS {$label}\n";
		return;
	}

	++$failed;
	echo "FAIL {$label}\n";
	echo '  expected: ' . var_export( $expected, true ) . "\n";
	echo '  actual:   ' . var_export( $actual, true ) . "\n";
}

/**
 * Assert approximate float equality.
 *
 * @param string $label    Test label.
 * @param float  $expected Expected value.
 * @param float  $actual   Actual value.
 */
function check_float( $label, $expected, $actual ) {
	global $passed, $failed;

	if ( is_numeric( $actual ) && abs( $expected - (float) $actual ) < 0.001 ) {
		++$passed;
		echo "PASS {$label}\n";
		return;
	}

	++$failed;
	echo "FAIL {$label}\n";
	echo '  expected: ' . var_export( $expected, true ) . "\n";
	echo '  actual:   ' . var_export( $actual, true ) . "\n";
}

// Roles and hours.
$roles = CPD_Records_GDC::roles();
check( 'dentist requires 100', 100, $roles['dentist']['hours'] );
check( 'therapist requires 75', 75, $roles['therapist']['hours'] );
check( 'dental nurse requires 50', 50, $roles['dental_nurse']['hours'] );
check( 'dentist CPD year starts in January', 1, $roles['dentist']['cpd_year_start_month'] );
check( 'DCP CPD year starts in August', 8, $roles['dental_nurse']['cpd_year_start_month'] );
check( 'unknown role returns null', null, CPD_Records_GDC::role( 'unknown' ) );

// Cycle bounds.
$cycle = CPD_Records_GDC::cycle_bounds( '2025-08-01', '2026-11-14' );
check( 'cycle start', '2025-08-01', $cycle['start']->format( 'Y-m-d' ) );
check( 'cycle end', '2030-07-31', $cycle['end']->format( 'Y-m-d' ) );

$cycle = CPD_Records_GDC::cycle_bounds( '2025-08-01', '2031-01-01' );
check( 'next cycle start', '2030-08-01', $cycle['start']->format( 'Y-m-d' ) );
check( 'next cycle end', '2035-07-31', $cycle['end']->format( 'Y-m-d' ) );

$cycle = CPD_Records_GDC::cycle_bounds( '2025-08-01', '2024-01-01' );
check( 'before cycle uses first cycle', '2025-08-01', $cycle['start']->format( 'Y-m-d' ) );

// CPD year bounds.
$year = CPD_Records_GDC::cpd_year_bounds( 'dental_nurse', '2026-11-14' );
check( 'DCP CPD year start', '2026-08-01', $year['start']->format( 'Y-m-d' ) );
check( 'DCP CPD year end', '2027-07-31', $year['end']->format( 'Y-m-d' ) );

$year = CPD_Records_GDC::cpd_year_bounds( 'dentist', '2026-11-14' );
check( 'dentist CPD year start', '2026-01-01', $year['start']->format( 'Y-m-d' ) );
check( 'dentist CPD year end', '2026-12-31', $year['end']->format( 'Y-m-d' ) );

$year = CPD_Records_GDC::cpd_year_bounds( 'dental_nurse', '2026-07-31' );
check( 'DCP CPD year before August', '2025-08-01', $year['start']->format( 'Y-m-d' ) );

// Annual statement dates.
$due = CPD_Records_GDC::annual_statement_due( 'dentist', '2026-11-14' );
check( 'dentist annual statement', '2027-01-28', $due->format( 'Y-m-d' ) );

$due = CPD_Records_GDC::annual_statement_due( 'dental_nurse', '2026-11-14' );
check( 'DCP annual statement', '2027-08-28', $due->format( 'Y-m-d' ) );

// Summary: passing two-year windows and outcomes.
$records = array(
	array(
		'activity_date' => '2025-09-10',
		'hours'         => 12,
		'outcome_a'     => 1,
		'outcome_c'     => 1,
	),
	array(
		'activity_date' => '2026-10-01',
		'hours'         => 8,
		'outcome_b'     => 1,
	),
	array(
		'activity_date' => '2027-01-15',
		'hours'         => 10,
		'outcome_c'     => 1,
		'outcome_d'     => 1,
	),
);

$summary = CPD_Records_GDC::summarise( $records, 'dentist', '2025-01-01', '2027-06-01' );

check_float( 'summary total', 30.0, $summary['total_hours'] );
check( 'summary required', 100, $summary['required_hours'] );
check_float( 'summary remaining', 70.0, $summary['remaining_hours'] );
check_float( 'summary percent', 30.0, $summary['percent'] );
check_float( 'outcome A hours', 12.0, $summary['outcome_hours']['A'] );
check_float( 'outcome B hours', 8.0, $summary['outcome_hours']['B'] );
check_float( 'outcome C hours', 22.0, $summary['outcome_hours']['C'] );
check_float( 'outcome D hours', 10.0, $summary['outcome_hours']['D'] );
check( 'summary records', 3, $summary['record_count'] );
check( 'two-year rule pass', true, $summary['two_year_pass'] );
check( 'two-year rule assessable', true, $summary['two_year_assessable'] );
check( 'no consecutive zero years', false, $summary['consecutive_zero'] );
check( 'cycle start in summary', '2025-01-01', $summary['cycle_start'] );
check( 'cycle end in summary', '2029-12-31', $summary['cycle_end'] );
check( 'annual statement in summary', '2028-01-28', $summary['annual_statement_due'] );

// Summary: failing two-year window.
$records = array(
	array(
		'activity_date' => '2025-06-01',
		'hours'         => 5,
	),
	array(
		'activity_date' => '2026-06-01',
		'hours'         => 4,
	),
);

$summary = CPD_Records_GDC::summarise( $records, 'dentist', '2025-01-01', '2026-12-01' );
check( 'in-progress window is not judged', true, $summary['two_year_pass'] );
check( 'in-progress window is not assessable', false, $summary['two_year_assessable'] );
check( 'in-progress window hours', 9.0, $summary['windows'][0]['hours'] );

$summary = CPD_Records_GDC::summarise( $records, 'dentist', '2025-01-01', '2027-01-15' );
check( 'two-year rule fails at 9 hours', false, $summary['two_year_pass'] );
check( 'two-year rule is assessable', true, $summary['two_year_assessable'] );
check( 'window hours', 9.0, $summary['windows'][0]['hours'] );

// Summary: consecutive zero years.
$summary = CPD_Records_GDC::summarise( array(), 'dentist', '2025-01-01', '2027-01-15' );
check( 'empty records fail two-year rule', false, $summary['two_year_pass'] );
check( 'consecutive zero years', true, $summary['consecutive_zero'] );
check( 'empty record count', 0, $summary['record_count'] );

// Summary: records outside the cycle are excluded.
$records = array(
	array(
		'activity_date' => '2024-12-31',
		'hours'         => 50,
	),
	array(
		'activity_date' => '2025-06-01',
		'hours'         => 10,
	),
);

$summary = CPD_Records_GDC::summarise( $records, 'dentist', '2025-01-01', '2026-12-01' );
check_float( 'outside-cycle record excluded', 10.0, $summary['total_hours'] );
check( 'outside-cycle record count', 1, $summary['record_count'] );

// Date validation.
check( 'valid date', true, CPD_Records_GDC::is_valid_date( '2026-11-14' ) );
check( 'invalid date', false, CPD_Records_GDC::is_valid_date( '2026-02-30' ) );
check( 'invalid format', false, CPD_Records_GDC::is_valid_date( '14/11/2026' ) );
check( 'empty date', false, CPD_Records_GDC::is_valid_date( '' ) );

echo "\n{$passed} passed, {$failed} failed\n";

exit( $failed > 0 ? 1 : 0 );
