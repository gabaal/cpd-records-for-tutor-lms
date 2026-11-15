<?php
/**
 * Admin records page and settings.
 *
 * @package CPD_Records
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tools screen for records and export.
 */
class CPD_Records_Admin {

	/**
	 * Hook in.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add the Tools page.
	 */
	public function menu() {
		add_management_page(
			__( 'CPD Records', 'cpd-records-for-tutor-lms' ),
			__( 'CPD Records', 'cpd-records-for-tutor-lms' ),
			'manage_options',
			'cpd-records',
			array( $this, 'render' )
		);
	}

	/**
	 * Register the uninstall preference.
	 */
	public function register_settings() {
		register_setting(
			'cpd_records_settings',
			'cpd_records_delete_on_uninstall',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
				'default'           => '0',
			)
		);

		add_settings_section(
			'cpd_records_main',
			__( 'Data', 'cpd-records-for-tutor-lms' ),
			'__return_false',
			'cpd_records_settings'
		);

		add_settings_field(
			'cpd_records_delete_on_uninstall',
			__( 'Delete data on uninstall', 'cpd-records-for-tutor-lms' ),
			array( $this, 'render_delete_field' ),
			'cpd_records_settings',
			'cpd_records_main'
		);
	}

	/**
	 * Sanitize the checkbox.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	public function sanitize_checkbox( $value ) {
		return '1' === (string) $value ? '1' : '0';
	}

	/**
	 * Render the uninstall checkbox.
	 */
	public function render_delete_field() {
		$value = get_option( 'cpd_records_delete_on_uninstall', '0' );
		?>
		<label>
			<input
				type="checkbox"
				name="cpd_records_delete_on_uninstall"
				value="1"
				<?php checked( $value, '1' ); ?>
			>
			<?php esc_html_e( 'Delete all CPD records and user settings when the plugin is uninstalled.', 'cpd-records-for-tutor-lms' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Leave this off if you might reinstall the plugin or need to keep the records.', 'cpd-records-for-tutor-lms' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the records page.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page        = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$per_page    = 50;
		$user_filter = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
		$total       = CPD_Records_Repository::count_all( $user_filter );
		$records     = CPD_Records_Repository::get_all( $per_page, ( $page - 1 ) * $per_page, $user_filter );

		$export_url = wp_nonce_url(
			add_query_arg( 'action', 'cpd_records_export', admin_url( 'admin-post.php' ) ),
			'cpd_records_export_all'
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'CPD Records', 'cpd-records-for-tutor-lms' ); ?></h1>

			<?php if ( ! CPD_Records_Plugin::tutor_active() ) : ?>
				<div class="notice notice-warning">
					<p>
						<?php esc_html_e( 'Tutor LMS is not active. Course completion records will not be created until it is installed and activated.', 'cpd-records-for-tutor-lms' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<p>
				<?php esc_html_e( 'Add CPD hours and development outcomes to a course, then learners earn a record when they complete it.', 'cpd-records-for-tutor-lms' ); ?>
			</p>

			<form method="get">
				<input type="hidden" name="page" value="cpd-records">
				<label for="cpd-records-user-filter"><?php esc_html_e( 'Learner ID', 'cpd-records-for-tutor-lms' ); ?></label>
				<input
					type="number"
					name="user_id"
					id="cpd-records-user-filter"
					value="<?php echo esc_attr( $user_filter ? $user_filter : '' ); ?>"
					min="1"
					class="small-text"
				>
				<?php submit_button( __( 'Filter', 'cpd-records-for-tutor-lms' ), 'secondary', 'cpd-records-filter', false ); ?>
				<a class="button" href="<?php echo esc_url( $export_url ); ?>">
					<?php esc_html_e( 'Export all CSV', 'cpd-records-for-tutor-lms' ); ?>
				</a>
			</form>

			<p>
				<?php
				printf(
					/* translators: %s: number of records. */
					esc_html__( '%s records', 'cpd-records-for-tutor-lms' ),
					esc_html( number_format_i18n( $total ) )
				);
				?>
			</p>

			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Date', 'cpd-records-for-tutor-lms' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Learner', 'cpd-records-for-tutor-lms' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Activity', 'cpd-records-for-tutor-lms' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Hours', 'cpd-records-for-tutor-lms' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Outcomes', 'cpd-records-for-tutor-lms' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Source', 'cpd-records-for-tutor-lms' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $records ) ) : ?>
						<tr>
							<td colspan="6"><?php esc_html_e( 'No records yet.', 'cpd-records-for-tutor-lms' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $records as $record ) : ?>
							<?php $user = get_userdata( (int) $record['user_id'] ); ?>
							<tr>
								<td><?php echo esc_html( $record['activity_date'] ); ?></td>
								<td>
									<?php if ( $user ) : ?>
										<?php echo esc_html( $user->display_name ); ?><br>
										<span class="description"><?php echo esc_html( $user->user_email ); ?></span>
									<?php else : ?>
										<?php echo esc_html( $record['user_id'] ); ?>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $record['activity_title'] ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (float) $record['hours'], 2 ) ); ?></td>
								<td><?php echo esc_html( $this->outcomes_label( $record ) ); ?></td>
								<td><?php echo esc_html( $record['source'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<?php
			$total_pages = max( 1, (int) ceil( $total / $per_page ) );

			if ( $total_pages > 1 ) {
				echo '<div class="tablenav"><div class="tablenav-pages">';
				echo wp_kses_post(
					paginate_links(
						array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'current'   => $page,
							'total'     => $total_pages,
							'prev_text' => '&laquo;',
							'next_text' => '&raquo;',
						)
					)
				);
				echo '</div></div>';
			}
			?>

			<h2><?php esc_html_e( 'Settings', 'cpd-records-for-tutor-lms' ); ?></h2>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'cpd_records_settings' );
				do_settings_sections( 'cpd_records_settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
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
