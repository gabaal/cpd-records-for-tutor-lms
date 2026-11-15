<?php
/**
 * Course CPD fields.
 *
 * @package CPD_Records
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds CPD hours and outcomes to Tutor LMS courses.
 */
class CPD_Records_Course_Meta {

	/**
	 * Hook in.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'maybe_outcome_notice' ) );

		$post_type = $this->course_post_type();
		add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_column' ) );
		add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'render_column' ), 10, 2 );
	}

	/**
	 * Tutor LMS course post type, with a safe fallback.
	 *
	 * @return string
	 */
	private function course_post_type() {
		if ( function_exists( 'tutor' ) ) {
			$tutor = tutor();
			if ( is_object( $tutor ) && ! empty( $tutor->course_post_type ) ) {
				return (string) $tutor->course_post_type;
			}
		}

		return 'courses';
	}

	/**
	 * Register the meta box.
	 */
	public function add_meta_box() {
		add_meta_box(
			'cpd_records_course',
			__( 'CPD credit (GDC)', 'cpd-records-for-tutor-lms' ),
			array( $this, 'render' ),
			$this->course_post_type(),
			'side',
			'default'
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @param WP_Post $post Course post.
	 */
	public function render( $post ) {
		wp_nonce_field( 'cpd_records_course', 'cpd_records_course_nonce' );

		$hours    = get_post_meta( $post->ID, '_cpd_records_hours', true );
		$outcomes = get_post_meta( $post->ID, '_cpd_records_outcomes', true );
		$outcomes = is_array( $outcomes ) ? $outcomes : array();
		?>
		<p>
			<label for="cpd-records-hours"><strong><?php esc_html_e( 'CPD hours', 'cpd-records-for-tutor-lms' ); ?></strong></label>
			<input
				type="number"
				id="cpd-records-hours"
				name="cpd_records_hours"
				value="<?php echo esc_attr( '' === $hours ? '' : $hours ); ?>"
				min="0"
				max="1000"
				step="0.25"
				class="widefat"
			>
		</p>
		<p><strong><?php esc_html_e( 'Development outcomes', 'cpd-records-for-tutor-lms' ); ?></strong></p>
		<?php foreach ( CPD_Records_GDC::outcomes() as $key => $label ) : ?>
			<p>
				<label>
					<input
						type="checkbox"
						name="cpd_records_outcomes[]"
						value="<?php echo esc_attr( $key ); ?>"
						<?php checked( in_array( $key, $outcomes, true ) ); ?>
					>
					<?php echo esc_html( $key . ' — ' . $label ); ?>
				</label>
			</p>
		<?php endforeach; ?>
		<p class="description">
			<?php esc_html_e( 'The learner earns these hours when they complete the course. One record is created per learner and course.', 'cpd-records-for-tutor-lms' ); ?>
		</p>
		<?php
	}

	/**
	 * Save the meta box.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save( $post_id, $post ) {
		if ( ! isset( $_POST['cpd_records_course_nonce'] ) ) {
			return;
		}

		$nonce = is_string( $_POST['cpd_records_course_nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['cpd_records_course_nonce'] ) )
			: '';
		if ( ! wp_verify_nonce( $nonce, 'cpd_records_course' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! $post instanceof WP_Post || $post->post_type !== $this->course_post_type() ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw_hours = isset( $_POST['cpd_records_hours'] ) && is_scalar( $_POST['cpd_records_hours'] )
			? wp_unslash( $_POST['cpd_records_hours'] )
			: 0;
		$hours     = max( 0, min( 1000, round( (float) $raw_hours, 2 ) ) );

		if ( $hours > 0 ) {
			update_post_meta( $post_id, '_cpd_records_hours', $hours );
		} else {
			delete_post_meta( $post_id, '_cpd_records_hours' );
		}

		$allowed  = array_keys( CPD_Records_GDC::outcomes() );
		$outcomes = isset( $_POST['cpd_records_outcomes'] ) ? (array) wp_unslash( $_POST['cpd_records_outcomes'] ) : array();
		$outcomes = array_intersect( $allowed, array_map( 'sanitize_text_field', $outcomes ) );
		$outcomes = array_values( $outcomes );

		if ( $outcomes ) {
			update_post_meta( $post_id, '_cpd_records_outcomes', $outcomes );
		} else {
			delete_post_meta( $post_id, '_cpd_records_outcomes' );
		}
	}

	/**
	 * Warn when a course has hours but no development outcome.
	 */
	public function maybe_outcome_notice() {
		$screen = get_current_screen();

		if ( ! $screen || 'post' !== $screen->base || $screen->post_type !== $this->course_post_type() ) {
			return;
		}

		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $post_id ) {
			return;
		}

		$hours    = (float) get_post_meta( $post_id, '_cpd_records_hours', true );
		$outcomes = get_post_meta( $post_id, '_cpd_records_outcomes', true );

		if ( $hours > 0 && empty( $outcomes ) ) {
			echo '<div class="notice notice-warning"><p>';
			esc_html_e(
				'This course has CPD hours but no development outcome selected. Each GDC activity should link to at least one development outcome.',
				'cpd-records-for-tutor-lms'
			);
			echo '</p></div>';
		}
	}

	/**
	 * Add a CPD hours column to the course list.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_column( $columns ) {
		$new = array();

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['cpd_hours'] = __( 'CPD hours', 'cpd-records-for-tutor-lms' );
			}
		}

		if ( ! isset( $new['cpd_hours'] ) ) {
			$new['cpd_hours'] = __( 'CPD hours', 'cpd-records-for-tutor-lms' );
		}

		return $new;
	}

	/**
	 * Render the CPD hours column.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public function render_column( $column, $post_id ) {
		if ( 'cpd_hours' !== $column ) {
			return;
		}

		$hours = get_post_meta( $post_id, '_cpd_records_hours', true );

		echo '' === $hours ? '&mdash;' : esc_html( number_format_i18n( (float) $hours, 2 ) );
	}
}
