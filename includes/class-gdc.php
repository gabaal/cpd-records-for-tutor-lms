<?php
/**
 * GDC Enhanced CPD rules and calculations.
 *
 * This class is deliberately pure PHP: it does not call WordPress functions,
 * so the rules can be unit-tested without a WordPress install.
 *
 * @package CPD_Records
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'CPD_RECORDS_TESTS' ) ) {
	exit;
}

/**
 * GDC CPD calculator.
 */
class CPD_Records_GDC {

	const REGULATOR = 'gdc';

	/**
	 * GDC roles, required hours and the month their CPD year starts.
	 *
	 * @return array
	 */
	public static function roles() {
		return array(
			'dentist'                    => array(
				'label'                => 'Dentist',
				'hours'                => 100,
				'cpd_year_start_month' => 1,
			),
			'therapist'                  => array(
				'label'                => 'Dental therapist',
				'hours'                => 75,
				'cpd_year_start_month' => 8,
			),
			'hygienist'                  => array(
				'label'                => 'Dental hygienist',
				'hours'                => 75,
				'cpd_year_start_month' => 8,
			),
			'orthodontic_therapist'      => array(
				'label'                => 'Orthodontic therapist',
				'hours'                => 75,
				'cpd_year_start_month' => 8,
			),
			'clinical_dental_technician' => array(
				'label'                => 'Clinical dental technician',
				'hours'                => 75,
				'cpd_year_start_month' => 8,
			),
			'dental_nurse'               => array(
				'label'                => 'Dental nurse',
				'hours'                => 50,
				'cpd_year_start_month' => 8,
			),
			'dental_technician'          => array(
				'label'                => 'Dental technician',
				'hours'                => 50,
				'cpd_year_start_month' => 8,
			),
		);
	}

	/**
	 * Get one role, or null.
	 *
	 * @param string $role_key Role key.
	 * @return array|null
	 */
	public static function role( $role_key ) {
		$roles = self::roles();
		return isset( $roles[ $role_key ] ) ? $roles[ $role_key ] : null;
	}

	/**
	 * The four GDC development outcomes.
	 *
	 * @return array
	 */
	public static function outcomes() {
		return array(
			'A' => 'Effective communication with patients, team and others',
			'B' => 'Effective management of self and others, teamwork and leadership',
			'C' => 'Maintenance and development of knowledge and skill within your field of practice',
			'D' => 'Maintaining skills, behaviours and attitudes that keep patient confidence',
		);
	}

	/**
	 * Validate a Y-m-d date.
	 *
	 * @param string $date Date string.
	 * @return bool
	 */
	public static function is_valid_date( $date ) {
		if ( ! is_string( $date ) || '' === $date ) {
			return false;
		}
		$parsed = DateTimeImmutable::createFromFormat( '!Y-m-d', $date );
		return $parsed && $parsed->format( 'Y-m-d' ) === $date;
	}

	/**
	 * Find the five-year cycle containing a date.
	 *
	 * @param string|DateTimeInterface $cycle_start First day of the learner's cycle.
	 * @param string|DateTimeInterface $as_of       Date to locate. Defaults to today.
	 * @return array|null Array with start, end and index.
	 */
	public static function cycle_bounds( $cycle_start, $as_of = null ) {
		$start = self::to_date( $cycle_start );
		$as_of = null === $as_of ? self::now() : self::to_date( $as_of );

		if ( ! $start || ! $as_of ) {
			return null;
		}

		if ( $as_of < $start ) {
			return array(
				'start' => $start,
				'end'   => $start->modify( '+5 years' )->modify( '-1 day' ),
				'index' => 0,
			);
		}

		$diff  = $start->diff( $as_of );
		$index = intdiv( (int) $diff->y, 5 );
		$cycle = $start->modify( '+' . ( $index * 5 ) . ' years' );

		// Guard against leap-day and partial-year edge cases.
		while ( $as_of < $cycle && $index > 0 ) {
			--$index;
			$cycle = $start->modify( '+' . ( $index * 5 ) . ' years' );
		}

		return array(
			'start' => $cycle,
			'end'   => $cycle->modify( '+5 years' )->modify( '-1 day' ),
			'index' => $index,
		);
	}

	/**
	 * Find the CPD year containing a date.
	 *
	 * @param string                   $role_key GDC role key.
	 * @param string|DateTimeInterface $as_of    Date to locate. Defaults to today.
	 * @return array|null Array with start and end.
	 */
	public static function cpd_year_bounds( $role_key, $as_of = null ) {
		$role  = self::role( $role_key );
		$as_of = null === $as_of ? self::now() : self::to_date( $as_of );

		if ( ! $role || ! $as_of ) {
			return null;
		}

		$month = (int) $role['cpd_year_start_month'];
		$year  = (int) $as_of->format( 'Y' );

		if ( (int) $as_of->format( 'n' ) < $month ) {
			--$year;
		}

		$start = new DateTimeImmutable( sprintf( '%04d-%02d-01', $year, $month ) );

		return array(
			'start' => $start,
			'end'   => $start->modify( '+1 year' )->modify( '-1 day' ),
		);
	}

	/**
	 * The next annual statement due date.
	 *
	 * GDC statements are due 28 days after the CPD year ends.
	 *
	 * @param string                   $role_key GDC role key.
	 * @param string|DateTimeInterface $as_of    Date to calculate from.
	 * @return DateTimeImmutable|null
	 */
	public static function annual_statement_due( $role_key, $as_of = null ) {
		$year = self::cpd_year_bounds( $role_key, $as_of );
		return $year ? $year['end']->modify( '+28 days' ) : null;
	}

	/**
	 * Summarise a learner's records for their current cycle.
	 *
	 * @param array                    $records     Rows with activity_date, hours and outcome_a..d.
	 * @param string                   $role_key    GDC role key.
	 * @param string|DateTimeInterface $cycle_start First day of the learner's cycle.
	 * @param string|DateTimeInterface $as_of       Date to calculate from. Defaults to today.
	 * @return array|null
	 */
	public static function summarise( $records, $role_key, $cycle_start, $as_of = null ) {
		$role        = self::role( $role_key );
		$cycle       = self::cycle_bounds( $cycle_start, $as_of );
		$as_of_date  = null === $as_of ? self::now() : self::to_date( $as_of );

		if ( ! $role || ! $cycle || ! $as_of_date ) {
			return null;
		}

		$required      = (int) $role['hours'];
		$total         = 0.0;
		$outcome_hours = array(
			'A' => 0.0,
			'B' => 0.0,
			'C' => 0.0,
			'D' => 0.0,
		);
		$by_year       = array();
		$in_cycle      = array();

		foreach ( (array) $records as $record ) {
			$record = (array) $record;
			$date   = self::to_date( isset( $record['activity_date'] ) ? $record['activity_date'] : null );

			if ( ! $date || $date < $cycle['start'] || $date > $cycle['end'] ) {
				continue;
			}

			$hours = isset( $record['hours'] ) ? max( 0.0, (float) $record['hours'] ) : 0.0;

			$in_cycle[] = $record;
			$total     += $hours;

			foreach ( array( 'A', 'B', 'C', 'D' ) as $outcome ) {
				$key = 'outcome_' . strtolower( $outcome );
				if ( ! empty( $record[ $key ] ) ) {
					$outcome_hours[ $outcome ] += $hours;
				}
			}

			$year = self::cpd_year_bounds( $role_key, $date );
			if ( $year ) {
				$key = $year['start']->format( 'Y-m-d' );
				if ( ! isset( $by_year[ $key ] ) ) {
					$by_year[ $key ] = array(
						'start' => $year['start'],
						'end'   => $year['end'],
						'hours' => 0.0,
					);
				}
				$by_year[ $key ]['hours'] += $hours;
			}
		}

		// Add empty CPD years so zero-hour years are visible and the two-year rule can fail.
		$last_date  = $as_of_date < $cycle['end'] ? $as_of_date : $cycle['end'];
		$first_year = self::cpd_year_bounds( $role_key, $cycle['start'] );
		$last_year  = self::cpd_year_bounds( $role_key, $last_date );

		if ( $first_year && $last_year ) {
			$cursor = $first_year['start'];
			while ( $cursor <= $last_year['start'] ) {
				$key = $cursor->format( 'Y-m-d' );
				if ( ! isset( $by_year[ $key ] ) ) {
					$by_year[ $key ] = array(
						'start' => $cursor,
						'end'   => $cursor->modify( '+1 year' )->modify( '-1 day' ),
						'hours' => 0.0,
					);
				}
				$cursor = $cursor->modify( '+1 year' );
			}
		}

		ksort( $by_year );
		$years = array_values( $by_year );

		$windows         = array();
		$two_year_pass   = true;
		$consecutive_zero = false;

		for ( $i = 0; $i < count( $years ) - 1; $i++ ) {
			$window_hours = $years[ $i ]['hours'] + $years[ $i + 1 ]['hours'];
			$pass         = $window_hours >= 10;
			$complete     = $years[ $i + 1 ]['end'] <= $as_of_date;

			$windows[] = array(
				'start'    => $years[ $i ]['start']->format( 'Y-m-d' ),
				'end'      => $years[ $i + 1 ]['end']->format( 'Y-m-d' ),
				'hours'    => $window_hours,
				'pass'     => $pass,
				'complete' => $complete,
			);

			// Only a completed two-year window can be judged.
			if ( $complete ) {
				if ( ! $pass ) {
					$two_year_pass = false;
				}

				if ( $years[ $i ]['hours'] <= 0 && $years[ $i + 1 ]['hours'] <= 0 ) {
					$consecutive_zero = true;
				}
			}
		}

		$due              = self::annual_statement_due( $role_key, $as_of_date );
		$complete_windows = array_filter(
			$windows,
			static function ( $window ) {
				return ! empty( $window['complete'] );
			}
		);

		return array(
			'regulator'            => self::REGULATOR,
			'role'                 => $role_key,
			'role_label'           => $role['label'],
			'required_hours'       => $required,
			'total_hours'          => $total,
			'remaining_hours'      => max( 0, $required - $total ),
			'percent'              => $required > 0 ? min( 100, round( $total / $required * 100, 1 ) ) : 0,
			'outcome_hours'        => $outcome_hours,
			'years'                => $years,
			'windows'              => $windows,
			'two_year_pass'        => $two_year_pass,
			'two_year_assessable'  => count( $complete_windows ) > 0,
			'consecutive_zero'     => $consecutive_zero,
			'cycle_start'          => $cycle['start']->format( 'Y-m-d' ),
			'cycle_end'            => $cycle['end']->format( 'Y-m-d' ),
			'annual_statement_due' => $due ? $due->format( 'Y-m-d' ) : '',
			'record_count'         => count( $in_cycle ),
			'records'              => $in_cycle,
		);
	}

	/**
	 * Today in the site timezone when WordPress is available.
	 *
	 * @return DateTimeImmutable
	 */
	private static function now() {
		if ( function_exists( 'current_datetime' ) ) {
			return current_datetime()->setTime( 0, 0, 0 );
		}

		return new DateTimeImmutable( 'today' );
	}

	/**
	 * Convert a date value to a midnight DateTimeImmutable.
	 *
	 * @param mixed $value Date string, DateTimeInterface, or null.
	 * @return DateTimeImmutable|null
	 */
	private static function to_date( $value ) {
		if ( $value instanceof DateTimeImmutable ) {
			return $value->setTime( 0, 0, 0 );
		}

		if ( $value instanceof DateTimeInterface ) {
			return DateTimeImmutable::createFromFormat( '!Y-m-d', $value->format( 'Y-m-d' ) );
		}

		if ( is_string( $value ) && '' !== $value ) {
			$parsed = DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
			if ( $parsed && $parsed->format( 'Y-m-d' ) === $value ) {
				return $parsed;
			}

			try {
				return ( new DateTimeImmutable( $value ) )->setTime( 0, 0, 0 );
			} catch ( Exception $e ) {
				return null;
			}
		}

		return null;
	}
}
