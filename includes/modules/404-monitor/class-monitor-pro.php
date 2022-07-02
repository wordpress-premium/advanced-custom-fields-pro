<?php
/**
 * 404 Monitor module.
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\Param;
use MyThemeShop\Database\Database;
use RankMathPro\Admin\CSV;

defined( 'ABSPATH' ) || exit;

/**
 * Monitor class.
 *
 * @codeCoverageIgnore
 */
class Monitor_Pro extends CSV {

	use Hooker;

	/**
	 * Total hits cache.
	 *
	 * @var array
	 */
	private $total_hits_cache = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->filter( 'rank_math/404_monitor/page_title_actions', 'page_title_actions', 20, 1 );
		$this->action( 'rank_math/404_monitor/before_list_table', 'export_panel', 20 );
		$this->action( 'admin_enqueue_scripts', 'enqueue', 20 );
		$this->action( 'init', 'maybe_export', 20 );
		$this->filter( 'rank_math/404_monitor/list_table_columns', 'manage_columns', 20 );
		$this->filter( 'rank_math/404_monitor/list_table_column', 'total_hits_column', 20, 3 );
		$this->filter( 'rank_math/404_monitor/get_logs_args', 'get_logs_args', 20 );
	}

	/**
	 * Add page title action for export.
	 *
	 * @param array $actions Original actions.
	 * @return array
	 */
	public function page_title_actions( $actions ) {
		$actions['export'] = [
			'class' => 'page-title-action',
			'href'  => add_query_arg( 'export-404', '1' ),
			'label' => __( 'Export', 'rank-math-pro' ),
		];

		return $actions;
	}

	/**
	 * Output export panel.
	 *
	 * @return void
	 */
	public function export_panel() {
		$today = date( 'Y-m-d' );
		?>
		<div class="rank-math-box rank-math-export-404-panel <?php echo Param::get( 'export-404' ) ? '' : 'hidden'; ?>">
			<h3><?php esc_html_e( 'Export 404 Logs', 'rank-math-pro' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'Export and download 404 logs from a selected period of time in the form of a CSV file. Leave the from/to fields empty to export all logs.', 'rank-math-pro' ); ?>
			</p>
			<div class="form-wrap">
				<form action="" method="get" autocomplete="off">
					<input type="hidden" name="action" value="rank_math_export_404">
					<?php wp_nonce_field( 'export_404' ); ?>

					<div class="form-field">
						<label for="rank_math_export_404_date_from">
								<?php esc_html_e( 'From date', 'rank-math-pro' ); ?>
						</label>
						<input type="text" name="date_from" value="" id="rank_math_export_404_date_from" class="rank-math-datepicker" placeholder="<?php echo esc_attr( $today ); ?>">
					</div>

					<div class="form-field">
						<label for="rank_math_export_404_date_to">
							<?php esc_html_e( 'To date', 'rank-math-pro' ); ?>
						</label>
						<input type="text" name="date_to" value="" id="rank_math_export_404_date_to" class="rank-math-datepicker" placeholder="<?php echo esc_attr( $today ); ?>">
					</div>

					<div class="rank_math_export_404_submit_wrap form-field">
						<input type="submit" value="<?php esc_attr_e( 'Export', 'rank-math-pro' ); ?>" class="button button-primary">
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function maybe_export() {
		if ( Param::get( 'action' ) !== 'rank_math_export_404' ) {
			return;
		}

		if ( ! current_user_can( 'export' ) || ! Helper::has_cap( '404_monitor' ) ) {
			// Todo: add error notice instead of wp_die()?
			wp_die( esc_html__( 'Sorry, your user does not seem to have the necessary capabilities to export.', 'rank-math-pro' ) );
		}

		if ( wp_verify_nonce( Param::get( '_nonce' ), 'export_404' ) ) {
			// Todo: add error notice instead of wp_die()?
			wp_die( esc_html__( 'Nonce error. Please try again.', 'rank-math-pro' ) );
		}

		$date_from = $this->sanitize_datetime( Param::get( 'date_from' ) );
		$date_to   = $this->sanitize_datetime( Param::get( 'date_to' ) );

		$data = $this->export_items( $date_from, $date_to );

		$this->export(
			[
				'filename' => '404-log',
				'columns'  => $data['columns'],
				'items'    => $data['items'],
			]
		);
		die();
	}

	/**
	 * Do export.
	 *
	 * @param  string $time_from Start date (SQL DateTime format).
	 * @param  string $time_to   End date (SQL DateTime format).
	 *
	 * @return array
	 */
	private function export_items( $time_from = null, $time_to = null ) {
		global $wpdb;
		$logs_table = $wpdb->prefix . 'rank_math_404_logs';
		$query      = "SELECT * FROM {$logs_table} WHERE 1=1";
		$where      = '';
		if ( $time_from ) {
			$where .= " AND accessed > '{$time_from} 00:00:01'";
		}
		if ( $time_to ) {
			$where .= " AND accessed < '{$time_to} 23:59:59'";
		}
		$query .= $where;
		$items  = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( empty( $items ) ) {
			return [
				'columns' => [],
				'items'   => [],
			];
		}

		$columns = array_keys( $items[0] );

		return [
			'columns' => $columns,
			'items'   => $items,
		];

	}

	/**
	 * Sanitize date field inputs.
	 *
	 * @param string $date Date input.
	 * @return string
	 */
	public function sanitize_datetime( $date ) {
		return preg_replace( '/[^0-9 :-]/', '', $date );
	}

	/**
	 * Enqueue styles and scripts.
	 *
	 * @param string $hook The current admin page.
	 *
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( 'rank-math_page_rank-math-404-monitor' !== $hook ) {
			return;
		}

		$url = RANK_MATH_PRO_URL . 'includes/modules/404-monitor/assets/';
		wp_enqueue_script( 'rank-math-pro-404-monitor', $url . 'js/404-monitor.js', [ 'jquery-ui-core', 'jquery-ui-datepicker' ], RANK_MATH_PRO_VERSION, true );
		wp_enqueue_style( 'rank-math-pro-404-monitor', $url . 'css/404-monitor.css', [], RANK_MATH_PRO_VERSION );
	}

	/**
	 * Add extra columns for the list table.
	 *
	 * @param array $columns Original columns.
	 * @return array
	 */
	public function manage_columns( $columns ) {
		if ( 'simple' === Helper::get_settings( 'general.404_monitor_mode' ) ) {
			return $columns;
		}

		$columns['total_hits'] = esc_html__( 'Hits', 'rank-math-pro' );
		return $columns;
	}

	/**
	 * Add content in the extra columns.
	 *
	 * @param string $content Original content.
	 * @param array  $item    Table item.
	 * @param string $column  Column name.
	 * @return string
	 */
	public function total_hits_column( $content, $item, $column ) {
		if ( 'total_hits' !== $column ) {
			return $content;
		}

		if ( ! isset( $this->total_hits_cache[ $item['uri'] ] ) ) {
			$this->total_hits_cache[ $item['uri'] ] = Database::table( 'rank_math_404_logs' )->selectCount( '*', 'count' )->where( 'uri', $item['uri'] )->getVar();
		}

		return '<a href="' . add_query_arg( [ 'uri' => $item['uri'] ] ) . '">' . $this->total_hits_cache[ $item['uri'] ] . '</a>';
	}

	/**
	 * Change get_logs() args when filtering for a URI.
	 *
	 * @param array $args Original args.
	 * @return array
	 */
	public function get_logs_args( $args ) {
		$uri = Param::get( 'uri' );
		if ( ! $uri ) {
			return $args;
		}

		$args['uri'] = $uri;
		return $args;
	}

}
