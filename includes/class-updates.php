<?php
/**
 * Functions and actions related to updates.
 *
 * @since      2.0.6
 * @package    RankMathPro
 * @subpackage RankMathPro\Core
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro;

use RankMath\Traits\Hooker;


defined( 'ABSPATH' ) || exit;

/**
 * Updates class
 */
class Updates {

	use Hooker;

	/**
	 * Updates that need to be run
	 *
	 * @var array
	 */
	private static $updates = [
		'2.0.6'  => 'updates/update-2.0.6.php',
		'2.1.0'  => 'updates/update-2.1.0.php',
		'2.8.1'  => 'updates/update-2.8.1.php',
		'2.12.0' => 'updates/update-2.12.0.php',
	];

	/**
	 * Register hooks.
	 */
	public function __construct() {
		$this->action( 'admin_init', 'do_updates' );
	}

	/**
	 * Check if any update is required.
	 */
	public function do_updates() {
		$installed_version = get_option( 'rank_math_pro_version', '1.0.0' );

		// Maybe it's the first install.
		if ( ! $installed_version ) {
			return;
		}

		if ( version_compare( $installed_version, rank_math_pro()->version, '<' ) ) {
			$this->perform_updates();
		}
	}

	/**
	 * Perform all updates.
	 */
	public function perform_updates() {
		$installed_version = get_option( 'rank_math_pro_version', '1.0.0' );

		foreach ( self::$updates as $version => $path ) {
			if ( version_compare( $installed_version, $version, '<' ) ) {
				include $path;
				update_option( 'rank_math_pro_version', $version );
			}
		}

		update_option( 'rank_math_pro_version', rank_math_pro()->version );
	}
}
