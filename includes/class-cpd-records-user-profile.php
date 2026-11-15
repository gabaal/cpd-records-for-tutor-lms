<?php
/**
 * GDC role and cycle start on the user profile.
 *
 * @package CPD_Records
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds CPD fields to user profiles.
 */
class CPD_Records_User_Profile {

	/**
	 * Hook in.
	 */
	public function __construct() {
		add_action( 'show_user_profile', array( $this, 'render' ) );
		add_action( 'edit_user_profile', array( $this, 'render' ) );
		add_action( 'personal_options_update', array( $this, 'save' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save' ) );
	}

	/**
	 * Render the profile section.
	 *
	 * @param WP_User $user User being edited.
	 */
	public function render( $user ) {
		$role        = get_user_meta( $user->ID, '_cpd_records_gdc_role', true );
		$cycle_start = get_user_meta( $user->ID, '_cpd_records_cycle_start', true );
		?>
		<h2><?php esc_html_e( 'CPD details', 'cpd-records-for-tutor-lms' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th>
					<label for="cpd-records-gdc-role"><?php esc_html_e( 'GDC role', 'cpd-records-for-tutor-lms' ); ?></label>
				</th>
				<td>
					<select name="cpd_records_gdc_role" id="cpd-records-gdc-role">
						<option value=""><?php esc_html_e( '— Select —', 'cpd-records-for-tutor-lms' ); ?></option>
						<?php foreach ( CPD_Records_GDC::roles() as $key => $role_data ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $role, $key ); ?>>
								<?php echo esc_html( $role_data['label'] . ' (' . $role_data['hours'] . ' hours)' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'Used to calculate the hours required in the five-year cycle.', 'cpd-records-for-tutor-lms' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="cpd-records-cycle-start"><?php esc_html_e( 'Cycle start date', 'cpd-records-for-tutor-lms' ); ?></label>
				</th>
				<td>
					<input
						type="date"
						name="cpd_records_cycle_start"
						id="cpd-records-cycle-start"
						value="<?php echo esc_attr( $cycle_start ); ?>"
					>
					<p class="description">
						<?php esc_html_e( 'The first day of your current five-year GDC CPD cycle. You can find this on eGDC / MyGDC.', 'cpd-records-for-tutor-lms' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php wp_nonce_field( 'cpd_records_profile', 'cpd_records_profile_nonce' ); ?>
		<?php
	}

	/**
	 * Save the profile section.
	 *
	 * @param int $user_id User ID.
	 */
	public function save( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		if ( ! isset( $_POST['cpd_records_profile_nonce'] ) ) {
			return;
		}

		$nonce = is_string( $_POST['cpd_records_profile_nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['cpd_records_profile_nonce'] ) )
			: '';
		if ( ! wp_verify_nonce( $nonce, 'cpd_records_profile' ) ) {
			return;
		}

		$role = isset( $_POST['cpd_records_gdc_role'] ) && is_string( $_POST['cpd_records_gdc_role'] )
			? sanitize_key( wp_unslash( $_POST['cpd_records_gdc_role'] ) )
			: '';

		if ( CPD_Records_GDC::role( $role ) ) {
			update_user_meta( $user_id, '_cpd_records_gdc_role', $role );
		} else {
			delete_user_meta( $user_id, '_cpd_records_gdc_role' );
		}

		$cycle_start = isset( $_POST['cpd_records_cycle_start'] ) && is_string( $_POST['cpd_records_cycle_start'] )
			? sanitize_text_field( wp_unslash( $_POST['cpd_records_cycle_start'] ) )
			: '';

		if ( CPD_Records_GDC::is_valid_date( $cycle_start ) ) {
			update_user_meta( $user_id, '_cpd_records_cycle_start', $cycle_start );
		} else {
			delete_user_meta( $user_id, '_cpd_records_cycle_start' );
		}
	}
}
