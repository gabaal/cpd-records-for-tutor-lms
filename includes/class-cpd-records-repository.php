<?php
/**
 * CPD record storage.
 *
 * @package CPD_Records
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// This class is the repository for the plugin's own custom table, so direct
// $wpdb queries and the absence of an object cache are intentional.
// phpcs:disable WordPress.DB.DirectDatabaseQuery

/**
 * Repository for the custom CPD records table.
 */
class CPD_Records_Repository {

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'cpd_records';
	}

	/**
	 * Create the table.
	 */
	public static function create_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table           = self::table();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			course_id bigint(20) unsigned NOT NULL DEFAULT 0,
			regulator varchar(20) NOT NULL DEFAULT 'gdc',
			activity_title varchar(255) NOT NULL DEFAULT '',
			activity_date date NOT NULL,
			hours decimal(6,2) NOT NULL DEFAULT 0.00,
			outcome_a tinyint(1) NOT NULL DEFAULT 0,
			outcome_b tinyint(1) NOT NULL DEFAULT 0,
			outcome_c tinyint(1) NOT NULL DEFAULT 0,
			outcome_d tinyint(1) NOT NULL DEFAULT 0,
			source varchar(20) NOT NULL DEFAULT 'tutor_lms',
			evidence_url varchar(500) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'approved',
			dedupe_key varchar(100) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY dedupe_key (dedupe_key),
			KEY user_date (user_id, activity_date),
			KEY course_id (course_id)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Insert a record.
	 *
	 * @param array $data Record data.
	 * @return int|false Insert ID, or false.
	 */
	public static function create( $data ) {
		global $wpdb;

		$now = current_time( 'mysql' );

		$row = wp_parse_args(
			$data,
			array(
				'user_id'        => 0,
				'course_id'      => 0,
				'regulator'      => 'gdc',
				'activity_title' => '',
				'activity_date'  => current_time( 'Y-m-d' ),
				'hours'          => 0,
				'outcome_a'      => 0,
				'outcome_b'      => 0,
				'outcome_c'      => 0,
				'outcome_d'      => 0,
				'source'         => 'tutor_lms',
				'evidence_url'   => '',
				'status'         => 'approved',
				'dedupe_key'     => '',
				'created_at'     => $now,
				'updated_at'     => $now,
			)
		);

		$row['user_id']        = absint( $row['user_id'] );
		$row['course_id']      = absint( $row['course_id'] );
		$row['regulator']      = sanitize_key( $row['regulator'] );
		$row['activity_title'] = sanitize_text_field( $row['activity_title'] );
		$row['activity_title'] = function_exists( 'mb_substr' )
			? mb_substr( $row['activity_title'], 0, 255 )
			: substr( $row['activity_title'], 0, 255 );
		$row['activity_date']  = CPD_Records_GDC::is_valid_date( $row['activity_date'] ) ? $row['activity_date'] : current_time( 'Y-m-d' );
		$row['hours']          = max( 0, min( 1000, round( (float) $row['hours'], 2 ) ) );
		$row['outcome_a']      = empty( $row['outcome_a'] ) ? 0 : 1;
		$row['outcome_b']      = empty( $row['outcome_b'] ) ? 0 : 1;
		$row['outcome_c']      = empty( $row['outcome_c'] ) ? 0 : 1;
		$row['outcome_d']      = empty( $row['outcome_d'] ) ? 0 : 1;
		$row['source']         = sanitize_key( $row['source'] );
		$row['evidence_url']   = substr( esc_url_raw( $row['evidence_url'] ), 0, 500 );
		$row['status']         = sanitize_key( $row['status'] );
		$row['dedupe_key']     = substr( sanitize_text_field( $row['dedupe_key'] ), 0, 100 );

		if ( '' === $row['dedupe_key'] ) {
			$row['dedupe_key'] = 'manual:' . $row['user_id'] . ':' . wp_generate_uuid4();
		}

		$inserted = $wpdb->insert(
			self::table(),
			$row,
			array(
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%f',
				'%d',
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);

		return false === $inserted ? false : (int) $wpdb->insert_id;
	}

	/**
	 * Insert a record unless one with the same dedupe key exists.
	 *
	 * @param array $data Record data.
	 * @return int|false
	 */
	public static function create_if_missing( $data ) {
		if ( ! empty( $data['dedupe_key'] ) ) {
			$existing = self::find_by_dedupe( $data['dedupe_key'] );
			if ( $existing ) {
				return (int) $existing['id'];
			}
		}

		return self::create( $data );
	}

	/**
	 * Find a record by dedupe key.
	 *
	 * @param string $key Dedupe key.
	 * @return array|null
	 */
	public static function find_by_dedupe( $key ) {
		global $wpdb;

		if ( '' === $key ) {
			return null;
		}

		$table = self::table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE dedupe_key = %s LIMIT 1', $table, $key ),
			ARRAY_A
		);

		return $row ? $row : null;
	}

	/**
	 * Get a learner's records, newest first.
	 *
	 * @param int         $user_id User ID.
	 * @param string|null $from    Optional start date (Y-m-d).
	 * @param string|null $to      Optional end date (Y-m-d).
	 * @return array
	 */
	public static function get_for_user( $user_id, $from = null, $to = null ) {
		global $wpdb;

		$table  = self::table();
		$sql    = 'SELECT * FROM %i WHERE user_id = %d';
		$params = array( $table, absint( $user_id ) );

		if ( $from && CPD_Records_GDC::is_valid_date( $from ) ) {
			$sql     .= ' AND activity_date >= %s';
			$params[] = $from;
		}

		if ( $to && CPD_Records_GDC::is_valid_date( $to ) ) {
			$sql     .= ' AND activity_date <= %s';
			$params[] = $to;
		}

		$sql .= ' ORDER BY activity_date DESC, id DESC';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Placeholders are assembled above and passed through prepare().
		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );
	}

	/**
	 * Get records across all learners.
	 *
	 * @param int $limit   Number of rows.
	 * @param int $offset  Offset.
	 * @param int $user_id Optional learner filter.
	 * @return array
	 */
	public static function get_all( $limit = 100, $offset = 0, $user_id = 0 ) {
		global $wpdb;

		$table  = self::table();
		$where  = '';
		$params = array( $table );

		if ( $user_id ) {
			$where    = 'WHERE user_id = %d';
			$params[] = absint( $user_id );
		}

		$params[] = max( 1, absint( $limit ) );
		$params[] = absint( $offset );

		$sql = 'SELECT * FROM %i ' . $where . ' ORDER BY activity_date DESC, id DESC LIMIT %d OFFSET %d';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Placeholders are assembled above and passed through prepare().
		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );
	}

	/**
	 * Count records.
	 *
	 * @param int $user_id Optional learner filter.
	 * @return int
	 */
	public static function count_all( $user_id = 0 ) {
		global $wpdb;

		$table = self::table();

		if ( $user_id ) {
			return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE user_id = %d', $table, absint( $user_id ) ) );
		}

		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table ) );
	}

	/**
	 * Delete one record.
	 *
	 * @param int $id Record ID.
	 * @return bool
	 */
	public static function delete( $id ) {
		global $wpdb;
		return false !== $wpdb->delete( self::table(), array( 'id' => absint( $id ) ), array( '%d' ) );
	}

	/**
	 * Drop the table. Used only by the opt-in uninstall routine.
	 */
	public static function drop_table() {
		global $wpdb;
		$table = self::table();
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table ) );
	}
}
