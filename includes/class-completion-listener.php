<?php
/**
 * Writes CPD records when a Tutor LMS course is completed.
 *
 * @package CPD_Records
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Listens for Tutor LMS course completion.
 */
class CPD_Records_Completion_Listener {

	/**
	 * Hook in.
	 */
	public function __construct() {
		add_action( 'tutor_course_complete_after', array( $this, 'record' ), 10, 2 );
	}

	/**
	 * Create a CPD record for a completed course.
	 *
	 * Fired by Tutor LMS CourseModel::complete_course().
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   Learner ID.
	 */
	public function record( $course_id, $user_id ) {
		$course_id = absint( $course_id );
		$user_id   = absint( $user_id );

		if ( ! $course_id || ! $user_id ) {
			return;
		}

		if ( ! apply_filters( 'cpd_records_should_record_completion', true, $course_id, $user_id ) ) {
			return;
		}

		$hours = (float) get_post_meta( $course_id, '_cpd_records_hours', true );

		if ( $hours <= 0 ) {
			return;
		}

		$saved    = get_post_meta( $course_id, '_cpd_records_outcomes', true );
		$saved    = is_array( $saved ) ? $saved : array();
		$allowed  = array_keys( CPD_Records_GDC::outcomes() );
		$outcomes = array_intersect( $allowed, $saved );

		$title = wp_strip_all_tags( get_the_title( $course_id ) );
		if ( '' === $title ) {
			/* translators: %d: course ID. */
			$title = sprintf( __( 'Course #%d', 'cpd-records-for-tutor-lms' ), $course_id );
		}

		$data = array(
			'user_id'        => $user_id,
			'course_id'      => $course_id,
			'regulator'      => CPD_Records_GDC::REGULATOR,
			'activity_title' => $title,
			'activity_date'  => current_time( 'Y-m-d' ),
			'hours'          => $hours,
			'outcome_a'      => in_array( 'A', $outcomes, true ) ? 1 : 0,
			'outcome_b'      => in_array( 'B', $outcomes, true ) ? 1 : 0,
			'outcome_c'      => in_array( 'C', $outcomes, true ) ? 1 : 0,
			'outcome_d'      => in_array( 'D', $outcomes, true ) ? 1 : 0,
			'source'         => 'tutor_lms',
			'status'         => 'approved',
			'dedupe_key'     => 'tutor:' . $user_id . ':' . $course_id,
		);

		/**
		 * Filter the record before it is saved.
		 *
		 * Return an empty array to skip the record.
		 *
		 * @param array $data      Record data.
		 * @param int   $course_id Course ID.
		 * @param int   $user_id   Learner ID.
		 */
		$data = apply_filters( 'cpd_records_completion_data', $data, $course_id, $user_id );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return;
		}

		CPD_Records_Repository::create_if_missing( $data );
	}
}
