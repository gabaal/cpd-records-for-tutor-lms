<?php
/**
 * Plugin bootstrap.
 *
 * @package CPD_Records
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires the plugin components together.
 */
class CPD_Records_Plugin {

	/**
	 * Start the plugin.
	 */
	public static function init() {
		load_plugin_textdomain(
			'cpd-records-for-tutor-lms',
			false,
			dirname( plugin_basename( CPD_RECORDS_FILE ) ) . '/languages'
		);

		new CPD_Records_Course_Meta();
		new CPD_Records_Completion_Listener();
		new CPD_Records_User_Profile();
		new CPD_Records_Dashboard();
		new CPD_Records_Export();
		new CPD_Records_Admin();

		add_action( 'admin_init', array( __CLASS__, 'privacy_content' ) );
		add_action( 'admin_notices', array( __CLASS__, 'maybe_tutor_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( CPD_RECORDS_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Whether Tutor LMS is active.
	 *
	 * @return bool
	 */
	public static function tutor_active() {
		return function_exists( 'tutor' ) || defined( 'TUTOR_VERSION' );
	}

	/**
	 * Suggest privacy policy text.
	 */
	public static function privacy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = '<p>' . esc_html__(
			'CPD Records for Tutor LMS stores CPD records linked to your WordPress user account: the course completed, the completion date, the CPD hours assigned to the course, the development outcomes linked to it, and the learner\'s GDC role and cycle start date. It does not collect patient or health data. Administrators can export or delete these records.',
			'cpd-records-for-tutor-lms'
		) . '</p>';

		wp_add_privacy_policy_content( 'CPD Records for Tutor LMS', $content );
	}

	/**
	 * Warn admins on the Plugins screen when Tutor LMS is missing.
	 */
	public static function maybe_tutor_notice() {
		if ( self::tutor_active() ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'plugins' !== $screen->id ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>';
		esc_html_e(
			'CPD Records for Tutor LMS requires Tutor LMS to be installed and active. Course completion records will not be created until it is.',
			'cpd-records-for-tutor-lms'
		);
		echo '</p></div>';
	}

	/**
	 * Add a Records link on the Plugins screen.
	 *
	 * @param array $links Plugin action links.
	 * @return array
	 */
	public static function action_links( $links ) {
		$url = admin_url( 'tools.php?page=cpd-records' );

		$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Records', 'cpd-records-for-tutor-lms' ) . '</a>';

		return $links;
	}
}
