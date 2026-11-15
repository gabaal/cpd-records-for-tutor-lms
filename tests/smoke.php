<?php
/**
 * Class-loading smoke test. No WordPress install required.
 *
 * Run: php tests/smoke.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'CPD_RECORDS_VERSION', '0.1.0' );
define( 'CPD_RECORDS_FILE', __DIR__ . '/../cpd-records-for-tutor-lms.php' );
define( 'CPD_RECORDS_DIR', __DIR__ . '/../' );
define( 'CPD_RECORDS_URL', 'http://example.test/wp-content/plugins/cpd-records-for-tutor-lms/' );

$GLOBALS['cpd_test_hooks'] = array();

function add_action( ...$args ) {
	$GLOBALS['cpd_test_hooks'][] = array( 'action', $args );
}

function add_filter( ...$args ) {
	$GLOBALS['cpd_test_hooks'][] = array( 'filter', $args );
}

function add_shortcode( ...$args ) {
	$GLOBALS['cpd_test_hooks'][] = array( 'shortcode', $args );
}

function load_plugin_textdomain( ...$args ) {
	return true;
}

function plugin_basename( $file ) {
	return basename( dirname( $file ) ) . '/' . basename( $file );
}

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

CPD_Records_Plugin::init();

$hooks = $GLOBALS['cpd_test_hooks'];
$tags  = array_map(
	static function ( $hook ) {
		return $hook[1][0];
	},
	$hooks
);

$expected = array(
	'tutor_course_complete_after',
	'cpd_records_dashboard',
	'tutor_cpd_dashboard',
	'admin_post_cpd_records_export',
	'admin_post_cpd_records_save_profile',
	'admin_menu',
	'admin_init',
);

$failed = 0;

foreach ( $expected as $tag ) {
	if ( ! in_array( $tag, $tags, true ) ) {
		echo "FAIL missing hook: {$tag}\n";
		++$failed;
	} else {
		echo "PASS hook: {$tag}\n";
	}
}

echo $failed ? "\n{$failed} smoke checks failed\n" : "\nSmoke test passed\n";

exit( $failed ? 1 : 0 );
