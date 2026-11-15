<?php
/**
 * Learner CPD dashboard shortcode.
 *
 * @package CPD_Records
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the learner dashboard and handles its setup form.
 */
class CPD_Records_Dashboard {

	/**
	 * Hook in.
	 */
	public function __construct() {
		add_shortcode( 'cpd_records_dashboard', array( $this, 'render' ) );
		add_shortcode( 'tutor_cpd_dashboard', array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue' ) );
		add_action( 'admin_post_cpd_records_save_profile', array( $this, 'handle_save_profile' ) );
	}

	/**
	 * Enqueue the stylesheet when the shortcode is present in the post content.
	 */
	public function maybe_enqueue() {
		if ( ! is_singular() ) {
			return;
		}

		$post = get_post();
		if ( ! $post ) {
			return;
		}

		if (
			has_shortcode( $post->post_content, 'cpd_records_dashboard' ) ||
			has_shortcode( $post->post_content, 'tutor_cpd_dashboard' )
		) {
			$this->enqueue();
		}
	}

	/**
	 * Enqueue the stylesheet.
	 */
	private function enqueue() {
		wp_enqueue_style(
			'cpd-records-dashboard',
			CPD_RECORDS_URL . 'assets/css/dashboard.css',
			array(),
			CPD_RECORDS_VERSION
		);
	}

	/**
	 * Save the learner's role and cycle start from the dashboard setup form.
	 */
	public function handle_save_profile() {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'You must be logged in to save your CPD details.', 'cpd-records-for-tutor-lms' ) );
		}

		check_admin_referer( 'cpd_records_save_profile' );

		$user_id = get_current_user_id();

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

		$redirect = wp_get_referer();
		wp_safe_redirect( $redirect ? $redirect : home_url( '/' ) );
		exit;
	}

	/**
	 * Render the dashboard.
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Enclosed content.
	 * @param string $tag     Shortcode tag.
	 * @return string
	 */
	public function render( $atts = array(), $content = '', $tag = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Shortcode callback signature.
		if ( ! is_user_logged_in() ) {
			$login_url = wp_login_url( get_permalink() );
			return '<div class="cpd-records cpd-records--notice"><p>' . sprintf(
				/* translators: %s: login URL. */
				wp_kses_post( __( 'Please <a href="%s">log in</a> to view your CPD record.', 'cpd-records-for-tutor-lms' ) ),
				esc_url( $login_url )
			) . '</p></div>';
		}

		$this->enqueue();

		$user_id     = get_current_user_id();
		$role        = get_user_meta( $user_id, '_cpd_records_gdc_role', true );
		$cycle_start = get_user_meta( $user_id, '_cpd_records_cycle_start', true );
		$records     = CPD_Records_Repository::get_for_user( $user_id );

		$summary = null;
		if ( CPD_Records_GDC::role( $role ) && CPD_Records_GDC::is_valid_date( $cycle_start ) ) {
			$summary = CPD_Records_GDC::summarise( $records, $role, $cycle_start );
		}

		ob_start();
		?>
		<div class="cpd-records">
			<div class="cpd-records__header">
				<h2><?php esc_html_e( 'My CPD record', 'cpd-records-for-tutor-lms' ); ?></h2>
				<p class="cpd-records__subtitle">
					<?php esc_html_e( 'GDC Enhanced CPD. Records are created when you complete a course with CPD hours assigned.', 'cpd-records-for-tutor-lms' ); ?>
				</p>
			</div>

			<?php if ( ! $summary ) : ?>
				<?php $this->render_setup( $role, $cycle_start ); ?>
			<?php else : ?>
				<?php $this->render_summary( $summary ); ?>
				<?php $this->render_records( $summary, $user_id ); ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Setup card shown when role or cycle start is missing.
	 *
	 * @param string $role        Current role key.
	 * @param string $cycle_start Current cycle start.
	 */
	private function render_setup( $role, $cycle_start ) {
		?>
		<div class="cpd-records__card">
			<h3><?php esc_html_e( 'Set up your CPD record', 'cpd-records-for-tutor-lms' ); ?></h3>
			<p>
				<?php esc_html_e( 'Choose your GDC role and the first day of your current five-year CPD cycle. You can find the date on eGDC / MyGDC.', 'cpd-records-for-tutor-lms' ); ?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cpd_records_save_profile">
				<?php wp_nonce_field( 'cpd_records_save_profile' ); ?>

				<p>
					<label for="cpd-records-setup-role"><?php esc_html_e( 'GDC role', 'cpd-records-for-tutor-lms' ); ?></label>
					<select name="cpd_records_gdc_role" id="cpd-records-setup-role" required>
						<option value=""><?php esc_html_e( '— Select —', 'cpd-records-for-tutor-lms' ); ?></option>
						<?php foreach ( CPD_Records_GDC::roles() as $key => $role_data ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $role, $key ); ?>>
								<?php echo esc_html( $role_data['label'] . ' (' . $role_data['hours'] . ' hours)' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>

				<p>
					<label for="cpd-records-setup-cycle"><?php esc_html_e( 'Cycle start date', 'cpd-records-for-tutor-lms' ); ?></label>
					<input
						type="date"
						name="cpd_records_cycle_start"
						id="cpd-records-setup-cycle"
						value="<?php echo esc_attr( $cycle_start ); ?>"
						required
					>
				</p>

				<p>
					<button type="submit" class="cpd-records__button">
						<?php esc_html_e( 'Save my CPD details', 'cpd-records-for-tutor-lms' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Progress, stats, rule status and outcomes.
	 *
	 * @param array $summary Summary from CPD_Records_GDC::summarise().
	 */
	private function render_summary( $summary ) {
		$percent = (float) $summary['percent'];
		?>
		<div class="cpd-records__stats">
			<div class="cpd-records__stat">
				<span class="cpd-records__stat-value"><?php echo esc_html( number_format_i18n( $summary['total_hours'], 2 ) ); ?></span>
				<span class="cpd-records__stat-label"><?php esc_html_e( 'hours recorded', 'cpd-records-for-tutor-lms' ); ?></span>
			</div>
			<div class="cpd-records__stat">
				<span class="cpd-records__stat-value"><?php echo esc_html( number_format_i18n( $summary['required_hours'] ) ); ?></span>
				<span class="cpd-records__stat-label"><?php esc_html_e( 'hours required', 'cpd-records-for-tutor-lms' ); ?></span>
			</div>
			<div class="cpd-records__stat">
				<span class="cpd-records__stat-value"><?php echo esc_html( number_format_i18n( $summary['remaining_hours'], 2 ) ); ?></span>
				<span class="cpd-records__stat-label"><?php esc_html_e( 'hours remaining', 'cpd-records-for-tutor-lms' ); ?></span>
			</div>
		</div>

		<div
			class="cpd-records__progress"
			role="progressbar"
			aria-valuemin="0"
			aria-valuemax="100"
			aria-valuenow="<?php echo esc_attr( $percent ); ?>"
			aria-label="<?php esc_attr_e( 'CPD hours completed', 'cpd-records-for-tutor-lms' ); ?>"
		>
			<span class="cpd-records__progress-bar" style="width: <?php echo esc_attr( $percent ); ?>%"></span>
		</div>
		<p class="cpd-records__progress-text">
			<?php
			printf(
				/* translators: 1: recorded hours, 2: required hours, 3: percentage. */
				esc_html__( '%1$s of %2$s hours (%3$s%%)', 'cpd-records-for-tutor-lms' ),
				esc_html( number_format_i18n( $summary['total_hours'], 2 ) ),
				esc_html( number_format_i18n( $summary['required_hours'] ) ),
				esc_html( number_format_i18n( $percent, 1 ) )
			);
			?>
		</p>

		<div class="cpd-records__meta">
			<p>
				<strong><?php esc_html_e( 'Cycle:', 'cpd-records-for-tutor-lms' ); ?></strong>
				<?php echo esc_html( $this->format_date( $summary['cycle_start'] ) ); ?>
				&ndash;
				<?php echo esc_html( $this->format_date( $summary['cycle_end'] ) ); ?>
				&middot;
				<strong><?php esc_html_e( 'Role:', 'cpd-records-for-tutor-lms' ); ?></strong>
				<?php echo esc_html( $summary['role_label'] ); ?>
			</p>
			<?php if ( ! empty( $summary['annual_statement_due'] ) ) : ?>
				<p>
					<strong><?php esc_html_e( 'Next annual statement:', 'cpd-records-for-tutor-lms' ); ?></strong>
					<?php echo esc_html( $this->format_date( $summary['annual_statement_due'] ) ); ?>
				</p>
			<?php endif; ?>
		</div>

		<?php $this->render_rule( $summary ); ?>

		<h3><?php esc_html_e( 'Hours by development outcome', 'cpd-records-for-tutor-lms' ); ?></h3>
		<ul class="cpd-records__outcomes">
			<?php foreach ( CPD_Records_GDC::outcomes() as $key => $label ) : ?>
				<li>
					<span class="cpd-records__outcome-key"><?php echo esc_html( $key ); ?></span>
					<span class="cpd-records__outcome-label"><?php echo esc_html( $label ); ?></span>
					<strong><?php echo esc_html( number_format_i18n( $summary['outcome_hours'][ $key ], 2 ) ); ?> h</strong>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Two-year minimum status.
	 *
	 * @param array $summary Summary.
	 */
	private function render_rule( $summary ) {
		$class = $summary['two_year_pass'] ? 'is-pass' : 'is-fail';
		?>
		<div class="cpd-records__rule <?php echo esc_attr( $class ); ?>">
			<strong><?php esc_html_e( 'Two-year minimum:', 'cpd-records-for-tutor-lms' ); ?></strong>
			<?php if ( ! $summary['two_year_assessable'] ) : ?>
				<?php esc_html_e( 'Not yet assessable. Record at least two CPD years.', 'cpd-records-for-tutor-lms' ); ?>
			<?php elseif ( $summary['two_year_pass'] ) : ?>
				<?php esc_html_e( 'Every two-year window has at least 10 hours.', 'cpd-records-for-tutor-lms' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'At least one two-year window has fewer than 10 hours.', 'cpd-records-for-tutor-lms' ); ?>
			<?php endif; ?>
		</div>

		<?php if ( $summary['consecutive_zero'] ) : ?>
			<div class="cpd-records__rule is-fail">
				<strong><?php esc_html_e( 'Zero-hour years:', 'cpd-records-for-tutor-lms' ); ?></strong>
				<?php esc_html_e( 'Two consecutive CPD years have no hours recorded.', 'cpd-records-for-tutor-lms' ); ?>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Records table and export action.
	 *
	 * @param array $summary Summary.
	 * @param int   $user_id Current user ID.
	 */
	private function render_records( $summary, $user_id ) {
		$export_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'  => 'cpd_records_export',
					'user_id' => $user_id,
				),
				admin_url( 'admin-post.php' )
			),
			'cpd_records_export_' . $user_id
		);
		?>
		<div class="cpd-records__records-header">
			<h3><?php esc_html_e( 'Records in this cycle', 'cpd-records-for-tutor-lms' ); ?></h3>
			<a class="cpd-records__button" href="<?php echo esc_url( $export_url ); ?>">
				<?php esc_html_e( 'Download CSV', 'cpd-records-for-tutor-lms' ); ?>
			</a>
		</div>

		<?php if ( empty( $summary['records'] ) ) : ?>
			<p class="cpd-records__empty">
				<?php esc_html_e( 'No CPD records in this cycle yet. Complete a course with CPD hours assigned.', 'cpd-records-for-tutor-lms' ); ?>
			</p>
		<?php else : ?>
			<table class="cpd-records__table">
				<caption class="screen-reader-text">
					<?php esc_html_e( 'CPD records in the current cycle', 'cpd-records-for-tutor-lms' ); ?>
				</caption>
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Date', 'cpd-records-for-tutor-lms' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Activity', 'cpd-records-for-tutor-lms' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Hours', 'cpd-records-for-tutor-lms' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Outcomes', 'cpd-records-for-tutor-lms' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $summary['records'] as $record ) : ?>
						<tr>
							<td><?php echo esc_html( $this->format_date( $record['activity_date'] ) ); ?></td>
							<td><?php echo esc_html( $record['activity_title'] ); ?></td>
							<td><?php echo esc_html( number_format_i18n( (float) $record['hours'], 2 ) ); ?></td>
							<td><?php echo esc_html( $this->outcomes_label( $record ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
	}

	/**
	 * Format a Y-m-d date for display.
	 *
	 * @param string $date Date.
	 * @return string
	 */
	private function format_date( $date ) {
		$timestamp = strtotime( $date );
		return $timestamp ? date_i18n( get_option( 'date_format' ), $timestamp ) : $date;
	}

	/**
	 * Comma-separated outcome letters for a record.
	 *
	 * @param array $record Record.
	 * @return string
	 */
	private function outcomes_label( $record ) {
		$outcomes = array();

		foreach ( array( 'A', 'B', 'C', 'D' ) as $key ) {
			if ( ! empty( $record[ 'outcome_' . strtolower( $key ) ] ) ) {
				$outcomes[] = $key;
			}
		}

		return $outcomes ? implode( ', ', $outcomes ) : '—';
	}
}
