<?php
/**
 * CSV exports.
 *
 * @package CPD_Records
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles learner and admin CSV downloads.
 */
class CPD_Records_Export {

	/**
	 * Hook in.
	 */
	public function __construct() {
		add_action( 'admin_post_cpd_records_export', array( $this, 'handle' ) );
	}

	/**
	 * Route the export request.
	 */
	public function handle() {
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		$user_id = isset( $_GET['user_id'] ) && is_scalar( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
		$nonce   = isset( $_GET['_wpnonce'] ) && is_string( $_GET['_wpnonce'] )
			? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) )
			: '';

		if ( $user_id ) {
			if ( ! wp_verify_nonce( $nonce, 'cpd_records_export_' . $user_id ) ) {
				wp_die( esc_html__( 'The export link has expired. Please try again.', 'cpd-records-for-tutor-lms' ) );
			}

			if (
				get_current_user_id() !== $user_id &&
				! current_user_can( 'edit_user', $user_id ) &&
				! current_user_can( 'manage_options' )
			) {
				wp_die( esc_html__( 'You are not allowed to export these records.', 'cpd-records-for-tutor-lms' ) );
			}

			$records  = CPD_Records_Repository::get_for_user( $user_id );
			$filename = 'cpd-records-user-' . $user_id . '-' . current_time( 'Y-m-d' ) . '.csv';
		} else {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You are not allowed to export these records.', 'cpd-records-for-tutor-lms' ) );
			}

			if ( ! wp_verify_nonce( $nonce, 'cpd_records_export_all' ) ) {
				wp_die( esc_html__( 'The export link has expired. Please try again.', 'cpd-records-for-tutor-lms' ) );
			}

			$records  = CPD_Records_Repository::get_all( 5000, 0 );
			$filename = 'cpd-records-all-' . current_time( 'Y-m-d' ) . '.csv';
		}

		$this->send( $records, sanitize_file_name( $filename ) );
	}

	/**
	 * Stream a CSV.
	 *
	 * @param array  $records  Records.
	 * @param string $filename Download filename.
	 */
	private function send( $records, $filename ) {
		nocache_headers();

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$output = fopen( 'php://output', 'w' );

		if ( false === $output ) {
			wp_die( esc_html__( 'Could not create the export file.', 'cpd-records-for-tutor-lms' ) );
		}

		fputcsv(
			$output,
			array(
				'User ID',
				'Name',
				'Email',
				'GDC role',
				'Activity',
				'Date',
				'Hours',
				'Outcome A',
				'Outcome B',
				'Outcome C',
				'Outcome D',
				'Source',
				'Evidence URL',
				'Status',
			),
			',',
			'"',
			''
		);

		foreach ( $records as $record ) {
			$user = get_userdata( (int) $record['user_id'] );

			fputcsv(
				$output,
				array_map(
					array( $this, 'safe_cell' ),
					array(
						$record['user_id'],
						$user ? $user->display_name : '',
						$user ? $user->user_email : '',
						$this->role_label( (int) $record['user_id'] ),
						$record['activity_title'],
						$record['activity_date'],
						$record['hours'],
						$record['outcome_a'] ? 'Yes' : 'No',
						$record['outcome_b'] ? 'Yes' : 'No',
						$record['outcome_c'] ? 'Yes' : 'No',
						$record['outcome_d'] ? 'Yes' : 'No',
						$record['source'],
						$record['evidence_url'],
						$record['status'],
					)
				),
				',',
				'"',
				''
			);
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- CSV is streamed to php://output, not the filesystem.
		exit;
	}

	/**
	 * GDC role label for a user.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	private function role_label( $user_id ) {
		$role = get_user_meta( $user_id, '_cpd_records_gdc_role', true );
		$data = CPD_Records_GDC::role( $role );
		return $data ? $data['label'] : '';
	}

	/**
	 * Prevent spreadsheet formula injection.
	 *
	 * @param mixed $value Cell value.
	 * @return string
	 */
	private function safe_cell( $value ) {
		$value = (string) $value;

		if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $value;
		}

		return $value;
	}
}
