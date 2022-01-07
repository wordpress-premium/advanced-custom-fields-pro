<?php
/**
 * Pro AMP support.
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Admin;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMath\Redirections\DB;
use RankMath\Redirections\Redirection;

defined( 'ABSPATH' ) || exit;

/**
 * Amp tool class.
 *
 * @codeCoverageIgnore
 */
class Amp {

	use Hooker;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		if ( Helper::get_settings( 'general.redirections_post_redirect' ) ) {
			$this->action( 'rank_math/redirection/post_updated', 'add_amp_redirect', 20 );
			$this->action( 'rank_math/redirection/term_updated', 'add_amp_redirect', 20 );
		}

	}

	/**
	 * Add /amp redirection based on the original redirection.
	 *
	 * @param  int $redirection_id Redirection ID.
	 * @return void
	 */
	public function add_amp_redirect( $redirection_id ) {
		$db_redirection = DB::get_redirection_by_id( $redirection_id );

		$url_to      = trailingslashit( $db_redirection['url_to'] ) . 'amp/';
		$redirection = Redirection::from(
			[
				'url_to'      => $url_to,
				'header_code' => $db_redirection['header_code'],
			]
		);

		$redirection->set_nocache( true );
		$redirection->add_source( trailingslashit( $db_redirection['sources'][0]['pattern'] ) . 'amp/', 'exact' );
		$redirection->add_destination( $url_to );
		$redirection->save();
	}

}
