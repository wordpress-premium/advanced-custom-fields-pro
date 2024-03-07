<?php
/**
 * SEO Analyzer module - Pro features.
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\SEO_Analysis;

use RankMath\Traits\Hooker;
use RankMath\Helpers\Param;

defined( 'ABSPATH' ) || exit;

/**
 * SEO_Analysis_Pro class.
 *
 * @codeCoverageIgnore
 */
class SEO_Analysis_Pro {

	use Hooker;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->filter( 'rank_math/seo_analysis/admin_tab_view', 'add_tab_views', 20, 2 );
		$this->action( 'admin_enqueue_scripts', 'enqueue' );

		remove_all_actions( 'rank_math/analyzer/results_header', 15 );
		$this->action( 'rank_math/analyzer/results_header', 'add_print_button', 20 );
		$this->action( 'rank_math/analyzer/results_header', 'add_logo', 25 );

		new Competitor_Analysis();
	}

	/**
	 * Add include files for the additional tabs.
	 *
	 * @param string $file        Include file.
	 * @param string $current_tab Current tab.
	 */
	public function add_tab_views( $file, $current_tab ) {
		if ( 'competitor_analyzer' === $current_tab ) {
			$file = dirname( __FILE__ ) . '/views/competitor-analysis.php';
		}

		return $file;
	}

	/**
	 * Enqueue styles and scripts.
	 *
	 * @param string $hook Page hook name.
	 */
	public function enqueue( $hook ) {
		if ( 'rank-math_page_rank-math-seo-analysis' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'rank-math-pro-seo-analysis', RANK_MATH_PRO_URL . 'includes/modules/seo-analysis/assets/css/seo-analysis.css', [], RANK_MATH_PRO_VERSION );
		wp_enqueue_script( 'rank-math-pro-seo-analysis', RANK_MATH_PRO_URL . 'includes/modules/seo-analysis/assets/js/seo-analysis-pro.js', [ 'jquery' ], RANK_MATH_PRO_VERSION, true );

		if ( Param::get( 'print' ) ) {
			wp_enqueue_style( 'rank-math-pro-seo-analysis-print', RANK_MATH_PRO_URL . 'includes/modules/seo-analysis/assets/css/seo-analysis-print.css', [], RANK_MATH_PRO_VERSION );
		}
	}

	/**
	 * Add print button to the results header.
	 */
	public function add_print_button() {
		?>
		<a href="#print" class="button button-secondary rank-math-print-results" id="rank-math-print-results">
			<span class="dashicons dashicons-printer"></span>
			<span class="input-loading"></span>
			<?php esc_html_e( 'Print', 'rank-math-pro' ); ?>
		</a>
		<?php
	}

	/**
	 * Add Rank Math logo to the results header.
	 */
	public function add_logo() {
		?>
		<div class="print-logo">
			<img src="<?php echo esc_url( rank_math()->plugin_url() . 'assets/admin/img/logo.svg' ); ?>">
		</div>
		<?php
	}
}
