<?php
/**
 * The Redirections Module.
 *
 * @since      2.10
 * @package    RankMathPro
 * @subpackage RankMathPro\Redirections
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Redirections;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMath\Redirections\DB;
use MyThemeShop\Helpers\Param;
use RankMath\Admin\Admin_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Redirections class.
 */
class Redirections {

	use Hooker;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		$this->action( 'admin_enqueue_scripts', 'admin_scripts', 20 );

		// Sync to .htaccess.
		$this->action( 'rank_math/redirections/export_tab_content', 'add_export_tab_content', 25 );
		$this->action( 'admin_init', 'maybe_sync_htaccess', 120 );

		// Auto-delete auto-redirections.
		if ( Helper::get_settings( 'general.redirections_post_redirect' ) ) {
			$this->action( 'rank_math/redirection/post_updated', 'mark_redirected_post', 20, 2 );
			$this->action( 'rank_math/redirection/term_updated', 'mark_redirected_term', 20, 2 );

			$this->action( 'pre_delete_term', 'delete_auto_term_redirects', 20 );
			$this->action( 'before_delete_post', 'delete_auto_post_redirects', 20, 1 );
		}

		new Categories();
		new Schedule();
	}

	/**
	 * Output extra content for the Export tab.
	 *
	 * @return void
	 */
	public function add_export_tab_content() {
		?>
		<div class="rank-math-redirections-csv-export">
			<h4><?php esc_html_e( 'Sync to .htaccess', 'rank-math-pro' ); ?></h4>
			<p class="description">
				<?php esc_html_e( 'Copy all active redirections to the .htaccess file.', 'rank-math-pro' ); ?>
			</p>
			<div class="csv-export-footer">
				<?php wp_nonce_field( 'rank_math_pro_htaccess_sync_redirections', '_wpnonce_htaccess', true, true ); ?>
				<button type="submit" class="button button-secondary" id="export-redirections-csv" name="rank-math-redirections-export" value="htaccess-sync"><?php esc_html_e( 'Sync to .htaccess', 'rank-math-pro' ); ?></button>
				<span class="input-loading"></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Start export if requested and allowed.
	 *
	 * @return void
	 */
	public function maybe_sync_htaccess() {
		if ( ! is_admin() || Param::post( 'rank-math-redirections-export' ) !== 'htaccess-sync' ) {
			return;
		}
		if ( ! wp_verify_nonce( Param::post( '_wpnonce_htaccess' ), 'rank_math_pro_htaccess_sync_redirections' ) ) {
			wp_die( esc_html__( 'Invalid nonce.', 'rank-math-pro' ) );
		}
		if ( ! current_user_can( 'export' ) || ! current_user_can( 'rank_math_redirections' ) || ! Helper::has_cap( 'edit_htaccess' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to export redirections on this site.', 'rank-math-pro' ) );
		}

		$htaccess = Admin_Helper::get_htaccess_data();
		if ( false === $htaccess || ! $htaccess['writable'] || ! Helper::is_filesystem_direct() ) {
			wp_die( esc_html__( 'The redirections could not be synced because the .htaccess file does not exist or it is not writable.', 'rank-math-pro' ) );
		}

		$rules = $this->get_htaccess_rules();
		if ( ! $rules ) {
			Helper::add_notification( __( 'No valid redirection found.', 'rank-math-pro' ) );
			return;
		}

		// Remove existing block.
		$filtered_htaccess_content = trim( preg_replace( '/\# Created by Rank Math[\s\S]+?# Rank Math Redirections END/si', '', $htaccess['content'] ) );

		$this->write_htaccess( $filtered_htaccess_content . PHP_EOL . $rules );
		Helper::add_notification( __( 'Redirections successfully synced to the .htaccess file.', 'rank-math-pro' ) );
	}

	/**
	 * Function to get htaccess rules.
	 *
	 * @return string
	 */
	public function get_htaccess_rules() {
		$items = DB::get_redirections(
			[
				'limit'  => 1000,
				'status' => 'active',
			]
		);

		$text[] = '# Created by Rank Math';
		$text[] = '# ' . date_i18n( 'r' );
		$text[] = '# Rank Math ' . trim( rank_math()->version ) . ' - https://rankmath.com/';
		$text[] = '';

		$text = array_merge( $text, $this->apache( $items['redirections'] ) );

		$text[] = '';
		$text[] = '# Rank Math Redirections END';

		return implode( PHP_EOL, $text ) . PHP_EOL;
	}

	/**
	 * Apache rewrite rules.
	 *
	 * @param array $items Array of DB items.
	 *
	 * @return string
	 */
	private function apache( $items ) {
		$output[] = '<IfModule mod_rewrite.c>';

		foreach ( $items as $item ) {
			$this->apache_item( $item, $output );
		}

		$output[] = '</IfModule>';

		return $output;
	}

	/**
	 * Format Apache single item.
	 *
	 * @param array $item   Single item.
	 * @param array $output Output array.
	 */
	private function apache_item( $item, &$output ) {
		$target  = '410' === $item['header_code'] ? '- [G]' : sprintf( '%s [R=%d,L]', $this->encode2nd( $item['url_to'] ), $item['header_code'] );
		$sources = maybe_unserialize( $item['sources'] );

		foreach ( $sources as $from ) {
			$url = $from['pattern'];
			if ( 'regex' !== $from['comparison'] && strpos( $url, '?' ) !== false || strpos( $url, '&' ) !== false ) {
				$url_parts = parse_url( $url );
				$url       = $url_parts['path'];
				$output[]  = sprintf( 'RewriteCond %%{QUERY_STRING} ^%s$', preg_quote( $url_parts['query'] ) );
			}

			// Get rewrite string.
			$output[] = sprintf( '%sRewriteRule %s %s', ( $this->is_valid_regex( $from ) ? '' : '# ' ), $this->get_comparison( $url, $from ), $target );
		}
	}

	/**
	 * Encode URL.
	 *
	 * @param string $url URL to encode.
	 *
	 * @return string
	 */
	private function encode2nd( $url ) {
		$url = urlencode( $url );
		$url = str_replace( '%2F', '/', $url );
		$url = str_replace( '%3F', '?', $url );
		$url = str_replace( '%3A', ':', $url );
		$url = str_replace( '%3D', '=', $url );
		$url = str_replace( '%26', '&', $url );
		$url = str_replace( '%25', '%', $url );
		$url = str_replace( '+', '%20', $url );
		$url = str_replace( '%24', '$', $url );
		return $url;
	}

	/**
	 * Check if it's a valid pattern.
	 *
	 * So we don't break the site when it's inserted in the .htaccess.
	 *
	 * @param array $source Source array.
	 *
	 * @return string
	 */
	private function is_valid_regex( $source ) {
		if ( 'regex' == $source['comparison'] && @preg_match( $source['pattern'], null ) === false ) { // phpcs:ignore
			return false;
		}

		return true;
	}

	/**
	 * Encode regex.
	 *
	 * @param string $url URL to encode.
	 *
	 * @return string
	 */
	private function encode_regex( $url ) {
		$url = preg_replace( '/[^a-zA-Z0-9\s](.*)[^a-zA-Z0-9\s][imsxeADSUXJu]*/', '$1', $url ); // Strip delimiters.
		$url = preg_replace( "/[\r\n\t].*?$/s", '', $url ); // Remove newlines.
		$url = preg_replace( '/[^\PC\s]/u', '', $url ); // Remove any invalid characters.
		$url = str_replace( ' ', '%20', $url ); // Make sure spaces are quoted.
		$url = str_replace( '%24', '$', $url );
		$url = ltrim( $url, '/' ); // No leading slash.
		$url = preg_replace( '@^\^/@', '^', $url ); // If pattern has a ^ at the start then ensure we don't have a slash immediately.

		return $url;
	}

	/**
	 * Get comparison pattern.
	 *
	 * @param string $url  URL for comparison.
	 * @param array  $from Comparison type and URL.
	 *
	 * @return string
	 */
	private function get_comparison( $url, $from ) {
		$comparison = $from['comparison'];
		if ( 'regex' === $comparison ) {
			return $this->encode_regex( $from['pattern'] );
		}

		$hash = [
			'exact'    => '^{url}/?$',
			'contains' => '^(.*){url}(.*)$',
			'start'    => '^{url}',
			'end'      => '{url}/?$',
		];

		$url = preg_quote( $url );
		return isset( $hash[ $comparison ] ) ? str_replace( '{url}', $url, $hash[ $comparison ] ) : $url;
	}

	/**
	 * Update htaccess file.
	 *
	 * @param string $content Htaccess content.
	 * @return string|bool
	 */
	private function write_htaccess( $content ) {
		if ( empty( $content ) ) {
			return false;
		}

		global $wp_filesystem;
		$htaccess_file = get_home_path() . '.htaccess';

		return $wp_filesystem->put_contents( $htaccess_file, $content );
	}

	/**
	 * Store auto redirection for post.
	 *
	 * @param  int $redirection_id Redirection ID.
	 * @param  int $post_id        Post ID.
	 * @return void
	 */
	public function mark_redirected_post( $redirection_id, $post_id ) {
		$redirects = get_post_meta( $post_id, 'rank_math_auto_redirect', true );
		if ( empty( $redirects ) ) {
			$redirects = [];
		}

		if ( ! in_array( $redirection_id, $redirects, true ) ) {
			$redirects[] = $redirection_id;
		}

		update_post_meta( $post_id, 'rank_math_auto_redirect', $redirects );
	}

	/**
	 * Store auto redirection for term.
	 *
	 * @param  int $redirection_id Redirection ID.
	 * @param  int $term_id        Term ID.
	 * @return void
	 */
	public function mark_redirected_term( $redirection_id, $term_id ) {
		$redirects = get_term_meta( $term_id, 'rank_math_auto_redirect', true );
		if ( empty( $redirects ) ) {
			$redirects = [];
		}

		if ( ! in_array( $redirection_id, $redirects, true ) ) {
			$redirects[] = $redirection_id;
		}

		update_term_meta( $term_id, 'rank_math_auto_redirect', $redirects );
	}

	/**
	 * Delete auto-created post redirects.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function delete_auto_post_redirects( $post_id ) {
		$redirects = get_post_meta( $post_id, 'rank_math_auto_redirect', true );
		if ( empty( $redirects ) ) {
			return;
		}

		DB::delete( $redirects );
	}

	/**
	 * Delete auto-created term redirects.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public function delete_auto_term_redirects( $term_id ) {
		$redirects = get_term_meta( $term_id, 'rank_math_auto_redirect', true );
		if ( empty( $redirects ) ) {
			return;
		}

		DB::delete( $redirects );
	}

	/**
	 * Enqueue styles and scripts for Redirections & Redirection Categories screens.
	 *
	 * @param  string $hook Page hook prefix.
	 *
	 * @return void
	 */
	public function admin_scripts( $hook ) {
		$screen = get_current_screen();

		if ( ! in_array( $screen->id, [ 'rank-math_page_rank-math-redirections', 'edit-rank_math_redirection_category' ], true ) ) {
			return;
		}

		$url = RANK_MATH_PRO_URL . 'includes/modules/redirections/assets/';
		Helper::add_json( 'add_redirection_category_nonce', wp_create_nonce( 'add-rank_math_redirection_category' ) );
		wp_enqueue_style( 'rank-math-pro-redirections', $url . 'css/redirections.css', [], RANK_MATH_PRO_VERSION );
		wp_enqueue_script( 'rank-math-pro-redirections', $url . 'js/redirections.js', [], RANK_MATH_PRO_VERSION, true );
	}
}
