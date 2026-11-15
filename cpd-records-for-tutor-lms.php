<?php
/**
 * Plugin Name:       CPD Records for Tutor LMS
 * Plugin URI:        https://example.com/cpd-records-for-tutor-lms
 * Description:       Track GDC CPD hours and development outcomes for Tutor LMS courses.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            CPD Records
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cpd-records-for-tutor-lms
 * Domain Path:       /languages
 *
 * @package CPD_Records
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CPD_RECORDS_VERSION', '0.1.0' );
define( 'CPD_RECORDS_FILE', __FILE__ );
define( 'CPD_RECORDS_DIR', plugin_dir_path( __FILE__ ) );
define( 'CPD_RECORDS_URL', plugin_dir_url( __FILE__ ) );

require_once CPD_RECORDS_DIR . 'includes/class-gdc.php';
require_once CPD_RECORDS_DIR . 'includes/class-repository.php';
require_once CPD_RECORDS_DIR . 'includes/class-activator.php';
require_once CPD_RECORDS_DIR . 'includes/class-course-meta.php';
require_once CPD_RECORDS_DIR . 'includes/class-completion-listener.php';
require_once CPD_RECORDS_DIR . 'includes/class-user-profile.php';
require_once CPD_RECORDS_DIR . 'includes/class-dashboard.php';
require_once CPD_RECORDS_DIR . 'includes/class-export.php';
require_once CPD_RECORDS_DIR . 'includes/class-admin.php';
require_once CPD_RECORDS_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'CPD_Records_Activator', 'activate' ) );

add_action( 'plugins_loaded', array( 'CPD_Records_Plugin', 'init' ) );
