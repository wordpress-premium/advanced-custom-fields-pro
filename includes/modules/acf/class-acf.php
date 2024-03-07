<?php
/**
 * ACF module.
 *
 * @since      2.0.9
 * @package    RankMathPro
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\ACF;

use RankMath\Helper;
use RankMath\Traits\Hooker;

defined( 'ABSPATH' ) || exit;

/**
 * ACF class.
 */
class ACF {
	use Hooker;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->action( 'rank_math/sitemap/urlimages', 'add_acf_images', 10, 2 );
		$this->filter( 'rank_math/sitemap/content_before_parse_html_images', 'parse_html_images', 10, 2 );
		$this->action( 'rank_math/admin/settings/general', 'acf_sitemap_settings' );
	}

	/**
	 * Add new settings.
	 *
	 * @param object $cmb CMB2 instance.
	 */
	public function acf_sitemap_settings( $cmb ) {
		$field_ids      = wp_list_pluck( $cmb->prop( 'fields' ), 'id' );
		$field_position = array_search( 'include_featured_image', array_keys( $field_ids ), true ) + 1;

		$cmb->add_field(
			[
				'id'      => 'include_acf_images',
				'type'    => 'toggle',
				'name'    => esc_html__( 'Include Images from the ACF Fields.', 'rank-math-pro' ),
				'desc'    => esc_html__( 'Include images added in the ACF fields.', 'rank-math-pro' ),
				'options' => [
					'off' => esc_html__( 'Default', 'rank-math-pro' ),
					'on'  => esc_html__( 'Custom', 'rank-math-pro' ),
				],
				'default' => 'off',
				'dep'     => [ [ 'include_images', 'on' ] ],
			],
			++$field_position
		);
	}

	/**
	 * Add images from the ACF fields content in the Sitemap.
	 *
	 * @param string $content Post content.
	 * @param int    $post_id Post ID.
	 */
	public function parse_html_images( $content, $post_id ) {
		if ( ! Helper::get_settings( 'sitemap.include_acf_images' ) ) {
			return $content;
		}

		$fields = get_field_objects( $post_id );
		if ( empty( $fields ) ) {
			return $content;
		}

		foreach ( $fields as $field ) {
			if ( empty( $field['value'] ) ) {
				continue;
			}

			if ( in_array( $field['type'], [ 'wysiwyg', 'textarea' ], true ) ) {
				$content .= $field['value'];
				continue;
			}

			if ( 'flexible_content' === $field['type'] ) {
				$this->get_flexible_content( $content, $field, $post_id );
				continue;
			}

			if ( 'repeater' === $field['type'] || 'group' === $field['type'] ) {
				$this->get_sub_fields_content( $content, $field['sub_fields'], $field );
				continue;
			}
		}

		return $content;
	}

	/**
	 * Filter images to be included for the post in XML sitemap.
	 *
	 * @param array $images  Array of image items.
	 * @param int   $post_id ID of the post.
	 */
	public function add_acf_images( $images, $post_id ) {
		if ( ! Helper::get_settings( 'sitemap.include_acf_images' ) ) {
			return $images;
		}

		$fields = get_field_objects( $post_id );
		if ( empty( $fields ) ) {
			return $images;
		}

		$values = wp_list_pluck( $fields, 'value' );
		$this->get_all_images( $images, $values );

		return $images;
	}

	/**
	 * Get content from flexible_content field.
	 *
	 * @param string $content Post content.
	 * @param array  $field   Current field data.
	 * @param int    $post_id Post ID.
	 */
	private function get_flexible_content( &$content, $field, $post_id ) {
		if ( empty( $field['layouts'] ) || empty( current( $field['layouts'] ) ) ) {
			return;
		}

		$this->get_sub_fields_content( $content, current( $field['layouts'] )['sub_fields'], $field );
	}

	/**
	 * Get content from ACF sub-fields.
	 *
	 * @param string $content    Post content.
	 * @param array  $sub_fields Array of subfields.
	 * @param array  $field      Current field data.
	 */
	private function get_sub_fields_content( &$content, $sub_fields, $field ) {
		foreach ( $sub_fields as $layout ) {
			if ( ! in_array( $layout['type'], [ 'wysiwyg', 'textarea' ], true ) ) {
				continue;
			}

			foreach ( $field['value'] as $key => $value ) {
				if ( $key === $layout['name'] ) {
					$content .= $value;
					continue;
				}

				$content .= is_array( $value ) && ! empty( $value[ $layout['name'] ] ) ? $value[ $layout['name'] ] : '';
			}
		}
	}

	/**
	 * Add Images to XML Sitemap.
	 *
	 * @param array  $images     Array of image items.
	 * @param array  $field_data Current Image array.
	 * @param string $field_type Is field type gallery.
	 */
	private function add_images_to_sitemap( &$images, $field_data, $field_type ) {
		if ( empty( $field_data ) ) {
			return;
		}

		if ( in_array( $field_type, [ 'group', 'repeater', 'flexible_content' ], true ) ) {
			$this->add_images_from_repeater_field( $images, $field_data );
			return;
		}

		if ( in_array( $field_type, [ 'gallery' ], true ) ) {
			foreach ( $field_data as $image ) {
				$this->add_images_to_sitemap( $images, $image, 'image' );
			}
			return;
		}

		if ( 'image' !== $field_type || empty( $field_data ) ) {
			return;
		}

		if ( is_array( $field_data ) && ! empty( $field_data['url'] ) ) {
			$images[] = [
				'src'   => $field_data['url'],
				'title' => $field_data['title'],
				'alt'   => $field_data['alt'],
			];
		} elseif ( is_int( $field_data ) ) {
			$image_url = wp_get_attachment_image_url( $field_data, 'full' );
			if ( $image_url ) {
				$images[] = [
					'src'   => $image_url,
					'title' => '',
					'alt'   => '',
				];
			}
		} elseif ( Helper::is_image_url( $field_data ) ) {
			$images[] = [
				'src'   => $field_data,
				'title' => '',
				'alt'   => '',
			];
		}
	}

	/**
	 * Add Images to XML Sitemap from Repeater field.
	 *
	 * @param array $images     Array of image items.
	 * @param array $field_data Current Image array.
	 */
	private function add_images_from_repeater_field( &$images, $field_data ) {
		if ( empty( $field_data ) ) {
			return;
		}

		foreach ( $field_data as $data ) {
			if ( is_array( $data ) ) {
				foreach ( $data as $image ) {
					if ( is_array( $image ) ) {
						$this->add_images_to_sitemap( $images, $image[0], 'image' );
					} else {
						$this->add_images_to_sitemap( $images, $image, 'image' );
					}
				}
			} else {
				$this->add_images_to_sitemap( $images, $data, 'image' );
			}
		}
	}

	/**
	 * Get all images
	 *
	 * @param array $images     All images.
	 * @param array $data       All acf field values.
	 *
	 * @return array
	 */
	public function get_all_images( &$images, $data ) {

		if ( is_array( $data ) ) {
			foreach ( array_values( $data ) as $single_data ) {
				if ( is_array( $single_data ) && isset( $single_data['type'] ) && 'image' === $single_data['type'] ) {
					$images[]    = [
						'src'   => $single_data['url'],
						'title' => $single_data['title'],
						'alt'   => $single_data['alt'],
					];
					$single_data = '';
				}
				$this->get_all_images( $images, $single_data );
			}
		}

		if ( empty( $data ) || is_array( $data ) ) {
			return $images;
		}

		if ( is_int( $data ) ) {
			$image_url = wp_get_attachment_image_url( $data, 'full' );
			if ( $image_url ) {
				$images[] = [
					'src'   => $image_url,
					'title' => '',
					'alt'   => '',
				];
			}
		}

		if ( Helper::is_image_url( $data ) ) {
			$images[] = [
				'src'   => $data,
				'title' => '',
				'alt'   => '',
			];
		}

		return $images;
	}

}
