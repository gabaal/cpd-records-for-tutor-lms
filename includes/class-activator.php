<?php
/**
 * Activation routine.
 *
 * @package CPD_Records
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs once when the plugin is activated.
 */
class CPD_Records_Activator {

	/**
	 * Activate.
	 */
	public static function activate() {
		CPD_Records_Repository::create_table();
		update_option( 'cpd_records_version', CPD_RECORDS_VERSION );
	}
}
