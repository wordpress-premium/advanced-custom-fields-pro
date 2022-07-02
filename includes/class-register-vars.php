<?php
/**
 * Register replacement vars.
 *
 * @since      1.0
 * @package    RankMathPro
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Register replacement vars class.
 *
 * @codeCoverageIgnore
 */
class Register_Vars {
	use Hooker;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		$this->action( 'rank_math/vars/register_extra_replacements', 'register_replacements' );
	}

	/**
	 * Registers variable replacements for Rank Math Pro.
	 */
	public function register_replacements() {
		rank_math_register_var_replacement(
			'randomword',
			[
				'name'        => esc_html__( 'Random Word', 'rank-math-pro' ),
				'description' => esc_html__( 'Persistent random word chosen from a list', 'rank-math-pro' ),
				'variable'    => 'randomword(word1|word2|word3)',
				'example'     => ' ',
			],
			[ $this, 'get_randomword' ]
		);

		rank_math_register_var_replacement(
			'randomword_np',
			[
				'name'        => esc_html__( 'Random Word', 'rank-math-pro' ),
				'description' => esc_html__( 'Non-persistent random word chosen from a list. A new random word will be chosen on each page load.', 'rank-math-pro' ),
				'variable'    => 'randomword_np(word1|word2|word3)',
				'example'     => ' ',
			],
			[ $this, 'get_randomword_np' ]
		);
	}

	/**
	 * Get random word from list of words. Use the object ID for the seed if persistent.
	 *
	 * @param  string $list       Words list in spintax-like format.
	 * @param  string $persistent Get persistent return value.
	 * @return string             Random word.
	 */
	public function get_randomword( $list = null, $persistent = true ) {
		$words = Arr::from_string( $list, '|' );
		$max   = count( $words );
		if ( ! $max ) {
			return '';
		} elseif ( 1 === $max ) {
			return $words[0];
		}

		if ( $persistent ) {
			$queried_id = (int) get_queried_object_id();
			$hash       = (int) crc32( serialize( $words ) . $queried_id );
			mt_srand( $hash );
		}

		$rand = mt_rand( 0, $max - 1 );

		return $words[ $rand ];
	}

	/**
	 * Get random word from list of words.
	 *
	 * @param  string $list Words list in spintax-like format.
	 * @return string       Random word.
	 */
	public function get_randomword_np( $list = null ) {
		return $this->get_randomword( $list, false );
	}
}
