<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Redirections\CSV_Import_Export_Redirections;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMath\Helpers\Param;

defined( 'ABSPATH' ) || exit;

/**
 * CSV Import Export Redirections class.
 *
 * @codeCoverageIgnore
 */
class CSV_Import_Export_Redirections {

	use Hooker;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		$this->filter( 'rank_math/redirections/page_title_actions', 'change_export_button_label', 20 );
		$this->filter( 'rank_math/redirections/export_tabs', 'add_import_tab', 20 );
		$this->action( 'rank_math/redirections/import_tab_content', 'add_import_tab_content', 20 );
		$this->action( 'rank_math/redirections/export_tab_content', 'add_export_tab_content', 20 );

		$this->action( 'admin_init', 'maybe_do_import', 99 );
		$this->action( 'admin_init', 'maybe_do_export', 110 );
		$this->action( 'admin_init', 'maybe_cancel_import', 120 );

		$this->action( 'wp_ajax_csv_import_redirections_progress', 'csv_import_progress' );
		$this->action( 'admin_enqueue_scripts', 'enqueue' );

		$this->action( 'admin_head-rank-math_page_rank-math-redirections', 'add_help_tab', 20 );

		Import_Background_Process::get();
	}

	/**
	 * Add instructions in contextual help.
	 *
	 * @return void
	 */
	public function add_help_tab() {
		$screen = get_current_screen();

		$content = '<ul class="description"><li>';
		// Translators: placeholder is a comma-separated list of columns.
		$content .= sprintf( esc_html__( 'Use the following columns in the CSV file (the order does not matter): %s', 'rank-math-pro' ), '<code>id, source, matching, destination, type, category, status, ignore</code>' );
		$content .= '</li><li>';
		// Translators: placeholders are column names.
		$content .= sprintf( esc_html__( 'Only the %1$s and the %2$s columns are required, the others are optional.', 'rank-math-pro' ), '<code>source</code>', '<code>destination</code>' );
		$content .= '</li><li>';
		// Translators: placeholder 1 is the column name, placeholder 2 is the possible column value ("case").
		$content .= sprintf( esc_html__( 'The %1$s column may contain the value %2$s, or nothing.', 'rank-math-pro' ), '<code>ignore</code>', '<code>case</code>' );
		$content .= '</li><li>';
		// Translators: placeholder is the column name.
		$content .= sprintf( esc_html__( 'If the numeric ID is specified in the %s column, then the redirection will be edited. If it is not set or empty, then a new redirection will be created.', 'rank-math-pro' ), '<code>id</code>' );
		$content .= '</li><li>';
		// Translators: placeholder is the filter name.
		$content .= sprintf( esc_html__( 'If an imported redirection differs from an existing redirection (or another imported redirection) only by the source value, then those redirections will be merged into a single redirection with multiple sources. You can change this behavior with the %s filter hook.', 'rank-math-pro' ), '<code>rank_math/admin/csv_import_redirection_update</code>' );
		$content .= '</li><li>';
		// Translators: 1 is the command name, 2 is the column name.
		$content .= sprintf( esc_html__( 'Use %1$s (case-sensitive) as the value for the %2$s column to delete a redirection.', 'rank-math-pro' ), '<code>DELETE</code>', '<code>destination</code>' );
		$content .= '</li><li>';
		// Translators: placeholder is a link to the KB article.
		$content .= sprintf( esc_html__( 'For more information, please see %s.', 'rank-math-pro' ), '<a href="https://rankmath.com/kb/how-to-manage-redirects-via-csv/" target="_blank">' . __( 'our Knowledge Base article', 'rank-math-pro' ) . '</a>' );
		$content .= '</li></ul>';

		$screen->add_help_tab(
			[
				'id'      => 'csv_import_redirections',
				'title'   => __( 'CSV Import', 'rank-math-pro' ),
				'content' => $content,
			]
		);
	}

	/**
	 * Add Import tab in redirections import-export panel.
	 *
	 * @param array $tabs Original tabs.
	 * @return array
	 */
	public function add_import_tab( $tabs ) {
		$tabs_new           = [];
		$tabs_new['import'] = [
			'name'  => __( 'Import', 'rank-math-pro' ),
			'icon'  => 'rm-icon-import',
			'class' => 'active-tab',
		];

		if ( isset( $tabs['export']['class'] ) ) {
			$tabs['export']['class'] = str_replace( 'active-tab', '', $tabs['export']['class'] );
		}

		return array_merge( $tabs_new, $tabs );
	}

	/**
	 * Output contents for the Import tab.
	 *
	 * @return void
	 */
	public function add_import_tab_content() {
		$import_in_progress = (bool) get_option( 'rank_math_csv_import_redirections' );
		?>
		<div class="rank-math-redirections-csv-import">
		<?php if ( ! $import_in_progress ) : ?>
					<p><label for="csv-import-me"><strong><?php esc_html_e( 'CSV File', 'rank-math-pro' ); ?></label></strong><p>
					<input type="file" name="csv-redirections-import-me" id="csv-redirections-import-me" value="" accept=".csv">
					<br>
					<span class="validation-message"><?php esc_html_e( 'Please select a CSV file to import.', 'rank-math-pro' ); ?></span>
					<p class="description">
						<?php // Translators: placeholder is a comma-separated list of columns. ?>
						<?php printf( esc_html__( 'Import a CSV file to create or update redirections. The file must include at least the following columns: %s', 'rank-math-pro' ), '<code>source, destination</code>' ); ?>
						<button type="button" id="rank-math-contextual-help-link" class="button button-small show-settings"><?php esc_html_e( 'More details', 'rank-math-pro' ); ?></button>
					</p>
				<?php else : ?>
					<div id="csv-import-redirections-progress-details">
						<?php self::import_progress_details(); ?>
					</div>
				<?php endif; ?>

			<div class="csv-export-footer">
			<?php wp_nonce_field( 'rank_math_pro_csv_import_redirections' ); ?>
					<input type="hidden" name="object_id" value="csv-import-redirections-plz">
					<input type="hidden" name="action" value="wp_handle_upload">
					<?php if ( $import_in_progress ) : ?>
						<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'rank_math_cancel_csv_import_redirections' => 1 ] ), 'rank_math_cancel_csv_import_redirections' ) ); ?>" id="csv-import-cancel" class="button button-link-delete csv-import-redirections-cancel"><?php esc_html_e( 'Cancel Import', 'rank-math-pro' ); ?></a>
					<?php else : ?>
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Import CSV', 'rank-math-pro' ); ?></button>
					<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Output extra content for the Export tab.
	 *
	 * @return void
	 */
	public function add_export_tab_content() {
		?>
		<div class="rank-math-redirections-csv-export">
			<h4><?php esc_html_e( 'Export CSV', 'rank-math-pro' ); ?></h4>
			<input type="checkbox" class="cmb2-option" name="include_deactivated" id="include_deactivated" value="1" checked="checked"> <label for="include_deactivated"><?php esc_html_e( 'Include deactivated redirections', 'rank-math-pro' ); ?></label>

			<div class="csv-export-footer">
				<?php wp_nonce_field( 'rank_math_pro_csv_export_redirections', '_wpnonce', true, true ); ?>
				<button type="submit" class="button button-secondary" id="export-redirections-csv" name="rank-math-redirections-export" value="csv"><?php esc_html_e( 'Export CSV', 'rank-math-pro' ); ?></button>
				<span class="input-loading"></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Change "Export" button label to "Import & Export".
	 *
	 * @param array $buttons Original buttons array.
	 * @return array
	 */
	public function change_export_button_label( $buttons ) {
		$buttons['import_export']['label'] = __( 'Import & Export', 'rank-math-pro' );
		return $buttons;
	}

	/**
	 * Check if current screen is Status & Tools > Import / Export.
	 *
	 * @return bool
	 */
	public function is_redirections_screen() {
		return is_admin() && ! wp_doing_ajax() && isset( $_GET['page'] ) && 'rank-math-redirections' === $_GET['page']; // phpcs:ignore
	}

	/**
	 * Add notice after import is started.
	 *
	 * @return void
	 */
	public function add_notice() {
		if ( ! $this->is_redirections_screen() ) {
			return;
		}

		Helper::add_notification( esc_html__( 'CSV import is in progress...', 'rank-math-pro' ), [ 'type' => 'success' ] );
	}

	/**
	 * Start export if requested and allowed.
	 *
	 * @return void
	 */
	public function maybe_do_export() {
		if ( ! is_admin() || Param::post( 'rank-math-redirections-export' ) !== 'csv' ) {
			return;
		}
		if ( ! wp_verify_nonce( isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '', 'rank_math_pro_csv_export_redirections' ) ) {
			wp_die( esc_html__( 'Invalid nonce.', 'rank-math-pro' ) );
		}
		if ( ! current_user_can( 'export' ) || ! current_user_can( 'rank_math_redirections' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to export redirections on this site.', 'rank-math-pro' ) );
		}

		$settings = [
			'include_deactivated' => (bool) ! empty( $_POST['include_deactivated'] ),
		];
		$exporter = new Exporter( $settings );
		$exporter->process_export();
	}

	/**
	 * Start import if requested and allowed.
	 *
	 * @return void
	 */
	public function maybe_do_import() {
		if ( ! is_admin() || empty( $_POST['object_id'] ) || 'csv-import-redirections-plz' !== $_POST['object_id'] ) {
			return;
		}
		if ( empty( $_FILES['csv-redirections-import-me'] ) || empty( $_FILES['csv-redirections-import-me']['name'] ) ) {
			wp_die( esc_html__( 'Please select a file to import.', 'rank-math-pro' ) );
		}
		if ( ! wp_verify_nonce( isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '', 'rank_math_pro_csv_import_redirections' ) ) {
			wp_die( esc_html__( 'Invalid nonce.', 'rank-math-pro' ) );
		}
		if ( ! current_user_can( 'import' ) || ! current_user_can( 'rank_math_redirections' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to import redirections on this site.', 'rank-math-pro' ) );
		}

		// Rename file.
		$info = pathinfo( $_FILES['csv-redirections-import-me']['name'] );
		$_FILES['csv-redirections-import-me']['name'] = uniqid( 'rm-csv-redirections-' ) . ( ! empty( $info['extension'] ) ? '.' . $info['extension'] : '' );

		// Handle file.
		$this->filter( 'upload_mimes', 'allow_csv_upload' );
		$file = wp_handle_upload( $_FILES['csv-redirections-import-me'], [ 'test_form' => false ] );
		$this->remove_filter( 'upload_mimes', 'allow_csv_upload', 10 );
		if ( ! $this->validate_file( $file ) ) {
			return false;
		}

		$settings = [
			'no_overwrite' => ! empty( $_POST['no_overwrite'] ),
		];

		$importer = new Importer();
		$importer->start( $file['file'], $settings );
	}

	/**
	 * Allow CSV file upload.
	 *
	 * @param array $types    Mime types keyed by the file extension regex corresponding to those types.
	 * @return array
	 */
	public function allow_csv_upload( $types ) {
		$types['csv'] = 'text/csv';

		return $types;
	}

	/**
	 * Check if uploaded file is valid CSV or not.
	 *
	 * @param mixed $file File data array or object.
	 * @return bool
	 */
	public function validate_file( $file ) {
		if ( is_wp_error( $file ) ) {
			Helper::add_notification( esc_html__( 'CSV could not be imported:', 'rank-math-pro' ) . ' ' . $file->get_error_message(), [ 'type' => 'error' ] );
			return false;
		}

		if ( isset( $file['error'] ) ) {
			Helper::add_notification( esc_html__( 'CSV could not be imported:', 'rank-math-pro' ) . ' ' . $file['error'], [ 'type' => 'error' ] );
			return false;
		}

		if ( ! isset( $file['file'] ) ) {
			Helper::add_notification( esc_html__( 'CSV could not be imported: Upload failed.', 'rank-math-pro' ), [ 'type' => 'error' ] );
			return false;
		}

		if ( ! isset( $file['type'] ) || 'text/csv' !== $file['type'] ) {
			\unlink( $file['file'] );
			Helper::add_notification( esc_html__( 'CSV could not be imported: File type error.', 'rank-math-pro' ), [ 'type' => 'error' ] );
			return false;
		}

		return true;
	}

	/**
	 * Get import/export CSV columns.
	 *
	 * @return array
	 */
	public static function get_columns() {
		$columns = [
			'id',
			'source',
			'matching',
			'destination',
			'type',
			'category',
			'status',
		];

		/**
		 * Filter columns array.
		 */
		return apply_filters( 'rank_math/admin/csv_export_redirections_columns', $columns );
	}

	/**
	 * Get object types.
	 *
	 * @return array
	 */
	public static function get_possible_object_types() {
		$object_types = [
			'post' => __( 'Posts', 'rank-math-pro' ),
			'term' => __( 'Terms', 'rank-math-pro' ),
			'user' => __( 'Users', 'rank-math-pro' ),
		];

		/**
		 * Filter object types array.
		 */
		return apply_filters( 'rank_math/admin/csv_export_redirections_object_types', $object_types );
	}

	/**
	 * Check if cancel request is valid.
	 *
	 * @return void
	 */
	public static function maybe_cancel_import() {
		if ( ! is_admin() || empty( $_GET['rank_math_cancel_csv_import_redirections'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '', 'rank_math_cancel_csv_import_redirections' ) ) {
			Helper::add_notification( esc_html__( 'Import could not be canceled: invalid nonce. Please try again.', 'rank-math-pro' ), [ 'type' => 'error' ] );
			wp_safe_redirect( remove_query_arg( 'rank_math_cancel_csv_import_redirections' ) );
			exit;
		}
		if ( ! current_user_can( 'import' ) ) {
			Helper::add_notification( esc_html__( 'Import could not be canceled: you are not allowed to import content to this site.', 'rank-math-pro' ), [ 'type' => 'error' ] );
			wp_safe_redirect( remove_query_arg( 'rank_math_cancel_csv_import_redirections' ) );
			exit;
		}

		self::cancel_import();
	}

	/**
	 * Cancel import.
	 *
	 * @param bool $silent Import silently.
	 * @return void
	 */
	public static function cancel_import( $silent = false ) {
		$file_path = get_option( 'rank_math_csv_import_redirections' );

		delete_option( 'rank_math_csv_import_redirections' );
		delete_option( 'rank_math_csv_import_redirections_total' );
		delete_option( 'rank_math_csv_import_redirections_status' );
		delete_option( 'rank_math_csv_import_redirections_settings' );
		Import_Background_Process::get()->cancel_process();

		if ( ! $file_path ) {
			if ( ! $silent ) {
				Helper::add_notification( esc_html__( 'Import could not be canceled.', 'rank-math-pro' ), [ 'type' => 'error' ] );
			}

			wp_safe_redirect( remove_query_arg( 'rank_math_cancel_csv_import_redirections' ) );
			exit;
		}

		unlink( $file_path );
		if ( ! $silent ) {
			Helper::add_notification(
				__( 'CSV import canceled.', 'rank-math-pro' ),
				[
					'type'    => 'success',
					'classes' => 'is-dismissible',
				]
			);
		}
		wp_safe_redirect( remove_query_arg( 'rank_math_cancel_csv_import_redirections' ) );
		exit;
	}

	/**
	 * Show import progress via AJAX.
	 *
	 * @return void
	 */
	public function csv_import_progress() {
		check_ajax_referer( 'rank_math_csv_progress' );
		if ( ! current_user_can( 'import' ) ) {
			exit( '0' );
		}

		self::import_progress_details();
		exit;
	}

	/**
	 * Output import progress details.
	 *
	 * @return void
	 */
	public static function import_progress_details() {
		$import_in_progress = (bool) get_option( 'rank_math_csv_import_redirections' );
		if ( $import_in_progress ) {
			$total_lines     = (int) get_option( 'rank_math_csv_import_redirections_total' );
			$remaining_items = Import_Background_Process::get()->count_remaining_items();
			$progress        = $total_lines ? ( $total_lines - $remaining_items + 1 ) / $total_lines * 100 : 0;
			?>
			<p><?php esc_html_e( 'Import in progress...', 'rank-math-pro' ); ?></p>
			<p class="csv-import-redirections-status">
				<?php // Translators: placeholders represent count like 15/36. ?>
				<?php printf( esc_html__( 'Items processed: %1$s/%2$s', 'rank-math-pro' ), absint( min( $total_lines, $total_lines - $remaining_items + 1 ) ), absint( $total_lines ) ); ?>
			</p>
			<div id="csv-import-redirections-progress-bar">
				<div class="total">
					<div class="progress-bar" style="width: <?php echo absint( $progress ); ?>%;"></div>
				</div>
				<input type="hidden" id="csv-import-redirections-progress-value" value="<?php echo absint( $progress ); ?>">
			</div>
			<?php
		} else {
			$status = (array) get_option( 'rank_math_csv_import_redirections_status', [] );

			$classes = 'import-finished';
			if ( ! empty( $status['errors'] ) ) {
				$classes .= ' import-errors';
			}

			$message = self::get_import_complete_message();
			?>
				<p class="<?php echo esc_attr( $classes ); ?>"><?php echo wp_kses_post( $message ); ?></p>
			<?php
		}
	}

	/**
	 * Get status message after import is complete.
	 *
	 * @return string
	 */
	public static function get_import_complete_message() {
		$status        = (array) get_option( 'rank_math_csv_import_redirections_status', [] );
		$imported_rows = is_countable( $status['imported_rows'] ) ? (array) $status['imported_rows'] : [];
		$message       = sprintf(
			// Translators: placeholder is the number of rows imported.
			__( 'CSV import completed. Successfully imported %d rows.', 'rank-math-pro' ),
			count( $imported_rows )
		);

		if ( ! empty( $status['errors'] ) ) {
			$message  = __( 'CSV import completed.', 'rank-math-pro' ) . ' ';
			$message .= sprintf(
				// Translators: placeholder is the number of rows imported.
				__( 'Imported %d rows.', 'rank-math-pro' ) . ' ',
				count( $imported_rows )
			);

			if ( ! empty( $status['errors'] ) ) {
				$message .= __( 'One or more errors occured while importing: ', 'rank-math-pro' ) . '<br>';
				$message .= '<code>' . join( '</code><br><code>', $status['errors'] ) . '</code><br>';
			}
			if ( ! empty( $status['failed_rows'] ) ) {
				$message .= '<br>' . __( 'The following lines could not be imported: ', 'rank-math-pro' ) . '<br>';
				$message .= '<code>' . join( ', ', $status['failed_rows'] ) . '</code>';
			}
		}

		if ( isset( $status['actions']['merged'] ) ) {
			$status['actions']['created'] += $status['actions']['merged'];
		}
		foreach ( $status['actions'] as $action => $times_taken ) {
			$message .= '<br><br>';
			$message .= '<code>' . self::get_localized_action( $action ) . ': ' . $times_taken . '</code>';
		}

		return $message;
	}

	/**
	 * Get localization for import action word.
	 *
	 * @param string $action Action word.
	 * @return string
	 */
	public static function get_localized_action( $action ) {
		$actions = [
			'created' => __( 'Created', 'rank-math-pro' ),
			'updated' => __( 'Updated', 'rank-math-pro' ),
			'deleted' => __( 'Deleted', 'rank-math-pro' ),
			'merged'  => __( 'Merged', 'rank-math-pro' ),
		];

		if ( isset( $actions[ $action ] ) ) {
			return $actions[ $action ];
		}

		return $action;
	}

	/**
	 * Enqueue styles.
	 *
	 * @return void
	 */
	public function enqueue() {
		if ( ! $this->is_redirections_screen() ) {
			return;
		}

		Helper::add_json( 'redirectionImportAction', add_query_arg( 'importexport', '1' ) . '#import-export-box' );
		Helper::add_json( 'confirmRedirectionsCsvImport', __( 'Are you sure you want to import redirections from this CSV file?', 'rank-math-pro' ) );
		Helper::add_json( 'confirmRedirectionsCsvCancel', __( 'Are you sure you want to stop the import process?', 'rank-math-pro' ) );
		Helper::add_json( 'csvProgressNonce', wp_create_nonce( 'rank_math_csv_progress' ) );

		wp_enqueue_script( 'rank-math-pro-redirections', RANK_MATH_PRO_URL . 'includes/modules/redirections/assets/js/redirections.js', [], RANK_MATH_PRO_VERSION, true );
		wp_enqueue_style( 'rank-math-pro-redirections', RANK_MATH_PRO_URL . 'includes/modules/redirections/assets/css/redirections.css', [], RANK_MATH_PRO_VERSION );
	}
}
