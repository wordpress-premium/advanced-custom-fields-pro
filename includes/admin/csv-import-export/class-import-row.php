<?php
/**
 * The CSV Import class.
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Admin\CSV_Import_Export;

use RankMath\Helper;
use RankMath\Redirections\DB;
use RankMath\Redirections\Cache;
use RankMath\Redirections\Redirection;
use MyThemeShop\Helpers\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * CSV Importer class.
 *
 * @codeCoverageIgnore
 */
class Import_Row {

	/**
	 * Constructor.
	 *
	 * @param array $data     Row data.
	 * @param array $settings Import settings.
	 * @return void
	 */
	public function __construct( $data, $settings ) {
		$this->data     = $data;
		$this->settings = $settings;

		foreach ( $this->data as $key => $value ) {
			// Skip empty or n/a.
			if ( empty( $value ) || $this->is_not_applicable( $value ) ) {
				continue;
			}

			$clear_method = "clear_{$key}";
			if ( $this->is_clear_command( $value ) && method_exists( $this, $clear_method ) ) {
				$this->$clear_method();
				continue;
			}

			if ( $this->settings['no_overwrite'] ) {
				$is_empty_method = "is_empty_{$key}";
				if ( ! method_exists( $this, $is_empty_method ) || ! $this->$is_empty_method() ) {
					continue;
				}
			}

			$import_method = "import_{$key}";
			if ( method_exists( $this, $import_method ) ) {
				$this->$import_method( $value );
			}
		}

		/**
		 * Do custom action after importing a row.
		 */
		do_action( 'rank_math/admin/csv_import_row', $data, $settings, $this );
	}

	/**
	 * Check if given column value is empty or not applicable.
	 *
	 * @param mixed $value Column value.
	 * @return bool
	 */
	public function is_not_applicable( $value ) {
		return $value === $this->settings['not_applicable_value'];
	}

	/**
	 * Check if given column value is the delete command.
	 *
	 * @param mixed $value Column value.
	 * @return bool
	 */
	public function is_clear_command( $value ) {
		return $value === $this->settings['clear_command'];
	}

	/**
	 * Magic getter.
	 *
	 * Return column value if is set and column name is in allowed columns list.
	 *
	 * @param string $property Property we want to get.
	 * @return string
	 */
	public function __get( $property ) {
		if ( in_array( $property, $this->get_columns(), true ) && isset( $this->data[ $property ] ) ) {
			return $this->data[ $property ];
		}

		return '';
	}

	/**
	 * Get CSV columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		if ( ! empty( $this->columns ) ) {
			return $this->columns;
		}
		$this->columns = CSV_Import_Export::get_columns();

		return $this->columns;
	}

	/**
	 * Clear SEO Title column.
	 *
	 * @return void
	 */
	public function clear_seo_title() {
		$this->delete_meta( 'title' );
	}

	/**
	 * Clear SEO Description column.
	 *
	 * @return void
	 */
	public function clear_seo_description() {
		$this->delete_meta( 'description' );
	}

	/**
	 * Clear Focus Keyword column.
	 *
	 * @return void
	 */
	public function clear_focus_keyword() {
		$this->delete_meta( 'focus_keyword' );
	}

	/**
	 * Clear Robots column.
	 *
	 * @return void
	 */
	public function clear_robots() {
		$this->delete_meta( 'robots' );
	}

	/**
	 * Clear Advanced Robots column.
	 *
	 * @return void
	 */
	public function clear_advanced_robots() {
		$this->delete_meta( 'advanced_robots' );
	}

	/**
	 * Clear Canonical URL column.
	 *
	 * @return void
	 */
	public function clear_canonical_url() {
		$this->delete_meta( 'canonical_url' );
	}

	/**
	 * Clear Primary Term column.
	 *
	 * @return void
	 */
	public function clear_primary_term() {
		$this->delete_meta( 'primary_category' );
	}

	/**
	 * Clear Schema Data column. Schema data must be valid JSON.
	 *
	 * @return void
	 */
	public function clear_schema_data() {
		$current_meta = $this->get_meta();
		foreach ( $current_meta as $key => $value ) {
			if ( substr( $key, 0, 17 ) === 'rank_math_schema_' ) {
				// Cut off "rank_math_" prefix.
				$this->delete_meta( substr( $key, 10 ) );
			}
		}
	}

	/**
	 * Clear FB Thumbnail column.
	 *
	 * @return void
	 */
	public function clear_social_facebook_thumbnail() {
		$this->delete_meta( 'facebook_image' );
	}

	/**
	 * Clear FB Title column.
	 *
	 * @return void
	 */
	public function clear_social_facebook_title() {
		$this->delete_meta( 'facebook_title' );
	}

	/**
	 * Clear FB Description column.
	 *
	 * @return void
	 */
	public function clear_social_facebook_description() {
		$this->delete_meta( 'facebook_description' );
	}

	/**
	 * Clear Twitter Thumbnail column.
	 *
	 * @return void
	 */
	public function clear_social_twitter_thumbnail() {
		$this->delete_meta( 'twitter_image' );
	}

	/**
	 * Clear Twitter Title column.
	 *
	 * @return void
	 */
	public function clear_social_twitter_title() {
		$this->delete_meta( 'twitter_title' );
	}

	/**
	 * Clear Twitter Description column.
	 *
	 * @return void
	 */
	public function clear_social_twitter_description() {
		$this->delete_meta( 'twitter_description' );
	}

	/**
	 * Clear Redirection URL column. Only if 'redirect_type' column is set, too.
	 *
	 * @return void
	 */
	public function clear_redirect_to() {
		if ( ! $this->is_empty_redirect_to() ) {
			DB::delete( $this->get_redirection()['id'] );
		}
	}

	/**
	 * Import slug column.
	 *
	 * @param string $value Column value.
	 * @return void
	 */
	public function import_slug( $value ) {
		switch ( $this->object_type ) {
			case 'post':
				wp_update_post(
					[
						'ID'        => $this->id,
						'post_name' => $value,
					]
				);
				break;

			case 'term':
				global $wpdb;
				$wpdb->update(
					$wpdb->terms,
					[ 'slug' => sanitize_title( $value ) ], // Update.
					[ 'term_id' => sanitize_title( $value ) ], // Where.
					[ '%s' ], // Format.
					[ '%d' ] // Where format.
				);
				break;

			case 'user':
				update_user_meta( $this->id, 'rank_math_permalink', $value );
				break;
		}

		// Refresh URI.
		$this->get_object_uri( true );
	}

	/**
	 * Import SEO Title column.
	 *
	 * @param string $value Column value.
	 * @return void
	 */
	public function import_seo_title( $value ) {
		$this->update_meta( 'title', $value );
	}

	/**
	 * Import SEO Description column.
	 *
	 * @param string $value Column value.
	 * @return void
	 */
	public function import_seo_description( $value ) {
		$this->update_meta( 'description', $value );
	}

	/**
	 * Import Is Pillar Content column.
	 *
	 * @param string $value Column value.
	 * @return void
	 */
	public function import_is_pillar_content( $value ) {
		$lcvalue = strtolower( $value );
		if ( 'yes' === $lcvalue ) {
			$this->update_meta( 'pillar_content', 'on' );
		} elseif ( 'no' === $lcvalue ) {
			$this->delete_meta( 'pillar_content' );
		}
	}

	/**
	 * Import Focus Keyword column.
	 *
	 * @param string $value Column value.
	 * @return void
	 */
	public function import_focus_keyword( $value ) {
		$this->update_meta( 'focus_keyword', $value );
	}

	/**
	 * Import Robots column.
	 *
	 * @param string $value Column value.
	 * @return void
	 */
	public function import_robots( $value ) {
		$this->update_meta( 'robots', Arr::from_string( $value ) );
	}

	/**
	 * Import Advanced Robots column.
	 *
	 * @param string $value Column value.
	 * @return void
	 */
	public function import_advanced_robots( $value ) {
		$robots       = [];
		$robots_rules = Arr::from_string( $value );
		foreach ( $robots_rules as $robots_rule ) {
			$parts = Arr::from_string( $robots_rule, '=' );
			if ( count( $parts ) === 2 ) {
				$robots[ $parts[0] ] = $parts[1];
			}
		}

		$this->update_meta( 'advanced_robots', $robots );
	}

	/**
	 * Import Canonical URL column.
	 *
	 * @param string $value Column value.
	 * @return void
	 */
	public function import_canonical_url( $value ) {
		$this->update_meta( 'canonical_url', $value );
	}

	/**
	 * Import Primary Term column.
	 *
	 * @param string $value Column value.
	 * @return void
	 */
	public function import_primary_term( $value ) {
		$term_id = Importer::get_term_id( $value );
		if ( ! $term_id ) {
			return;
		}

		$this->update_meta( 'primary_category', $value );
	}

	/**
	 * Import Schema Data column. Schema data must be valid JSON.
	 *
	 * @param string $value Column value.
	 * @return void
	 */
	public function import_schema_data( $value ) {
		$value = json_decode( $value, true );
		if ( ! $value ) {
			return;
		}

		foreach ( $value as $key => $value ) {
			$meta_key = 'schema_' . $key;
			$this->update_meta( $meta_key, $value );
		}
	}

	/**
	 * Import FB Thumbnail column.
	 *
	 * @param string $value Column value.
	 * @return void
	 */
	public function import_social_facebook_thumbnail( $value ) {
		$this->update_meta( 'facebook_image', $value );
	}

	/**
	 * Import FB Title column.
	 *
	 * @param string $value Column value.
	 * @return void
	 */
	public function import_social_facebook_title( $value ) {
		$this->update_meta( 'facebook_title', $value );
	}

	/**
	 * Import FB Description column.
	 *
	 * @param string $value Column value.
	 * @return void
	 */
	public function import_social_facebook_description( $value ) {
		$this->update_meta( 'facebook_description', $value );
	}

	/**
	 * Import Twitter Thumbnail column.
	 *
	 * @param string $value Column value.
	 * @return void
	 */
	public function import_social_twitter_thumbnail( $value ) {
		$this->update_meta( 'twitter_image', $value );
		$this->update_meta( 'twitter_use_facebook', 'off' );
	}

	/**
	 * Import Twitter Title column.
	 *
	 * @param string $value Column value.
	 * @return void
	 */
	public function import_social_twitter_title( $value ) {
		$this->update_meta( 'twitter_title', $value );
		$this->update_meta( 'twitter_use_facebook', 'off' );
	}

	/**
	 * Import Twitter Description column.
	 *
	 * @param string $value Column value.
	 * @return void
	 */
	public function import_social_twitter_description( $value ) {
		$this->update_meta( 'twitter_description', $value );
		$this->update_meta( 'twitter_use_facebook', 'off' );
	}

	/**
	 * Import Redirection URL column. Only if 'redirect_type' column is set, too.
	 *
	 * @param string $value Column value.
	 * @return void
	 */
	public function import_redirect_to( $value ) {
		if ( empty( $this->data['redirect_type'] ) ) {
			return;
		}

		if ( ! $this->is_empty_redirect_to() ) {
			DB::delete( $this->get_redirection()['id'] );
		}

		$redirection = Redirection::from(
			[
				'id'          => '',
				'url_to'      => $this->redirect_to,
				'sources'     => [
					[
						'pattern'    => $this->get_object_uri(),
						'comparison' => 'exact',
					],
				],
				'header_code' => $this->redirect_type,
			]
		);
		$redirection->set_nocache( true );
		$redirection->save();
	}

	/**
	 * Check if empty: SEO Title
	 *
	 * @return bool
	 */
	public function is_empty_seo_title() {
		return ! $this->get_meta( 'title' );
	}

	/**
	 * Check if empty: SEO Description
	 *
	 * @return bool
	 */
	public function is_empty_seo_description() {
		return ! $this->get_meta( 'description' );
	}

	/**
	 * Check if empty: Is Pillar Content column.
	 * We return true so this will always be overwritten.
	 *
	 * @return bool
	 */
	public function is_empty_is_pillar_content() {
		return true;
	}

	/**
	 * Check if empty: Focus Keyword column.
	 *
	 * @return bool
	 */
	public function is_empty_focus_keyword() {
		return ! $this->get_meta( 'focus_keyword' );
	}

	/**
	 * Check if empty: Robots column.
	 *
	 * @return bool
	 */
	public function is_empty_robots() {
		return empty( $this->get_meta( 'robots' ) );
	}

	/**
	 * Check if empty: Advanced Robots column.
	 *
	 * @return bool
	 */
	public function is_empty_advanced_robots() {
		return empty( $this->get_meta( 'advanced_robots' ) );
	}

	/**
	 * Check if empty: Canonical URL column.
	 *
	 * @return bool
	 */
	public function is_empty_canonical_url() {
		return ! $this->get_meta( 'canonical_url' );
	}

	/**
	 * Check if empty: Primary Term column.
	 *
	 * @return bool
	 */
	public function is_empty_primary_term() {
		return empty( $this->get_meta( 'primary_category' ) );
	}

	/**
	 * Check if empty: Schema Data column.
	 * We return true so this will always be overwritten.
	 *
	 * @return bool
	 */
	public function is_empty_schema_data() {
		return true;
	}

	/**
	 * Check if empty: FB Thumbnail column.
	 *
	 * @return bool
	 */
	public function is_empty_social_facebook_thumbnail() {
		return ! $this->get_meta( 'facebook_image' );
	}

	/**
	 * Check if empty: FB Title column.
	 *
	 * @return bool
	 */
	public function is_empty_social_facebook_title() {
		return ! $this->get_meta( 'facebook_title' );
	}

	/**
	 * Check if empty: FB Description column.
	 *
	 * @return bool
	 */
	public function is_empty_social_facebook_description() {
		return ! $this->get_meta( 'facebook_description' );
	}

	/**
	 * Check if empty: Twitter Thumbnail column.
	 *
	 * @return bool
	 */
	public function is_empty_social_twitter_thumbnail() {
		return ! $this->get_meta( 'twitter_image' );
	}

	/**
	 * Check if empty: Twitter Title column.
	 *
	 * @return bool
	 */
	public function is_empty_social_twitter_title() {
		return ! $this->get_meta( 'twitter_title' );
	}

	/**
	 * Check if empty: Twitter Description column.
	 *
	 * @return bool
	 */
	public function is_empty_social_twitter_description() {
		return ! $this->get_meta( 'twitter_description' );
	}

	/**
	 * Check if empty: Redirect URL.
	 *
	 * @return bool
	 */
	public function is_empty_redirect_to() {
		return ! (bool) $this->get_redirection()['id'];
	}

	/**
	 * Get redirection for object.
	 *
	 * @return mixed
	 */
	public function get_redirection() {
		if ( isset( $this->redirection ) ) {
			return $this->redirection;
		}
		$object_type = $this->object_type;
		$object_id   = $this->id;

		$this->get_object_uri();

		$redirection = Cache::get_by_object_id( $object_id, $object_type );
		$redirection = $redirection ? DB::get_redirection_by_id( $redirection->redirection_id, 'active' ) : [
			'id'          => '',
			'url_to'      => '',
			'header_code' => Helper::get_settings( 'general.redirections_header_code' ),
		];

		$this->redirection = $redirection;

		return $redirection;
	}

	/**
	 * Get object URI.
	 *
	 * @param bool $refresh Force refresh.
	 *
	 * @return string
	 */
	public function get_object_uri( $refresh = false ) {
		if ( isset( $this->object_uri ) && ! $refresh ) {
			return $this->object_uri;
		}

		$url = 'term' === $this->object_type ? get_term_link( (int) $this->id ) : get_permalink( $this->id );
		if ( empty( $url ) || is_wp_error( $url ) ) {
			return false;
		}

		$url = wp_parse_url( $url, PHP_URL_PATH );

		$this->object_uri = trim( $url, '/' );

		return $this->object_uri;
	}

	/**
	 * Update object meta.
	 *
	 * @param string $key   Meta key.
	 * @param mixed  $value Meta value.
	 * @return void
	 */
	public function update_meta( $key, $value ) {
		$update_meta = "update_{$this->object_type}_meta";
		$update_meta( $this->id, 'rank_math_' . $key, $value );
	}

	/**
	 * Get object meta.
	 *
	 * @param string $key   Meta key.
	 * @return bool
	 */
	public function get_meta( $key = '' ) {
		$get_meta = "get_{$this->object_type}_meta";
		return $get_meta( $this->id, $key ? 'rank_math_' . $key : '', (bool) $key );
	}

	/**
	 * Delete object meta.
	 *
	 * @param string $key Meta key.
	 * @return void
	 */
	public function delete_meta( $key ) {
		$delete_meta = "delete_{$this->object_type}_meta";
		$delete_meta( $this->id, 'rank_math_' . $key );
	}

}
