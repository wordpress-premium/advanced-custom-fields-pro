<?php
/**
 * Image SEO module.
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro;

use stdClass;
use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMath\Helpers\HTML;

defined( 'ABSPATH' ) || exit;

/**
 * Image_Seo class.
 *
 * @codeCoverageIgnore
 */
class Image_Seo_Pro {

	use Hooker;

	/**
	 * Change the case of the alt attribute.
	 *
	 * @var string
	 */
	public $alt_change_case;

	/**
	 * Change the case of the title attribute.
	 *
	 * @var string
	 */
	public $title_change_case;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->action( 'rank_math/admin/settings/images', 'add_options' );

		if ( Helper::get_settings( 'general.add_avatar_alt' ) ) {
			$this->filter( 'get_avatar', 'avatar_add_missing_alt', 99, 6 );
		}

		if ( Helper::get_settings( 'general.add_img_caption' ) ) {
			$this->filter( 'shortcode_atts_caption', 'add_caption', 99, 1 );
			$this->filter( 'the_content', 'content_add_caption', 99 );
		}

		if ( Helper::get_settings( 'general.add_img_description' ) ) {
			$this->filter( 'the_content', 'add_description', 99 );
		}

		$replacements = Helper::get_settings( 'general.image_replacements' );
		if ( ! empty( $replacements ) ) {
			$this->filter( 'the_content', 'attribute_replacements', 20 );
			$this->filter( 'post_thumbnail_html', 'attribute_replacements', 20 );
			$this->filter( 'woocommerce_single_product_image_thumbnail_html', 'attribute_replacements', 20 );
			$this->filter( 'shortcode_atts_caption', 'caption_replacements', 20, 3 );
		}

		$this->action( 'wp_head', 'maybe_change_attributes_case', 110 );

		if ( Helper::get_settings( 'general.img_caption_change_case' ) ) {
			$this->filter( 'shortcode_atts_caption', 'change_caption_case', 110, 1 );
			$this->filter( 'the_content', 'change_content_caption_case', 110 );
		}

		if ( Helper::get_settings( 'general.img_description_change_case' ) ) {
			$this->filter( 'the_content', 'change_description_case', 110 );
		}

		$this->action( 'rank_math/vars/register_extra_replacements', 'register_replacements' );
		$this->filter( 'cmb2_field_arguments', 'maybe_exclude_image_vars', 10 );
	}

	/**
	 * Registers variable replacements for the Image SEO Pro module.
	 */
	public function register_replacements() {
		rank_math_register_var_replacement(
			'imagealt',
			[
				'name'        => esc_html__( 'Image Alt', 'rank-math-pro' ),
				'description' => esc_html__( 'Alt text set for the current image.', 'rank-math-pro' ),
				'variable'    => 'imagealt',
				'example'     => '',
				'nocache'     => true,
			],
			[ $this, 'get_imagealt' ]
		);

		rank_math_register_var_replacement(
			'imagetitle',
			[
				'name'        => esc_html__( 'Image Title', 'rank-math-pro' ),
				'description' => esc_html__( 'Title text set for the current image.', 'rank-math-pro' ),
				'variable'    => 'imagetitle',
				'example'     => '',
				'nocache'     => true,
			],
			[ $this, 'get_imagetitle' ]
		);
	}


	/**
	 * Filter CMB field arguments to exclude `imagealt` & `imagetitle` when they are not needed.
	 *
	 * @param array $args Arguments array.
	 * @return array
	 */
	public function maybe_exclude_image_vars( $args ) {
		if ( empty( $args['classes'] ) ) {
			return $args;
		}

		$classes = is_array( $args['classes'] ) ? $args['classes'] : explode( ' ', $args['classes'] );
		if ( ! in_array( 'rank-math-supports-variables', $classes, true ) ) {
			return $args;
		}

		if ( ! is_string( $args['id'] ) || strpos( $args['id'], 'img_' ) !== false ) {
			return $args;
		}

		if ( ! isset( $args['attributes']['data-exclude-variables'] ) ) {
			$args['attributes']['data-exclude-variables'] = '';
		}

		$args['attributes']['data-exclude-variables'] .= ',imagealt,imagetitle';

		$args['attributes']['data-exclude-variables'] = trim( $args['attributes']['data-exclude-variables'], ',' );

		return $args;
	}

	/**
	 * Get the alt attribute of the attachment to use as a replacement.
	 * See rank_math_register_var_replacement().
	 *
	 * @codeCoverageIgnore
	 *
	 * @param  string $var_args         Variable name, for example %custom%. The '%' signs are optional.
	 * @param  object $replacement_args Additional title, description and example values for the variable.
	 *
	 * @return bool Replacement was registered successfully or not.
	 */
	public function get_imagealt( $var_args, $replacement_args = null ) {
		if ( empty( $replacement_args->alttext ) ) {
			return null;
		}

		return $replacement_args->alttext;
	}

	/**
	 * Get the title attribute of the attachment to use as a replacement.
	 * See rank_math_register_var_replacement().
	 *
	 * @codeCoverageIgnore
	 *
	 * @param  string $var_args         Variable name, for example %custom%. The '%' signs are optional.
	 * @param  object $replacement_args Additional title, description and example values for the variable.
	 *
	 * @return bool Replacement was registered successfully or not.
	 */
	public function get_imagetitle( $var_args, $replacement_args = null ) {
		if ( empty( $replacement_args->titletext ) ) {
			return null;
		}

		return $replacement_args->titletext;
	}

	/**
	 * Change case of alt & title attributes if needed.
	 *
	 * @return void
	 */
	public function maybe_change_attributes_case() {
		// Change image title and alt casing.
		$this->alt_change_case   = Helper::get_settings( 'general.img_alt_change_case' );
		$this->title_change_case = Helper::get_settings( 'general.img_title_change_case' );

		if ( $this->alt_change_case || $this->title_change_case ) {
			$this->filter( 'the_content', 'change_attribute_case', 30 );
			$this->filter( 'post_thumbnail_html', 'change_attribute_case', 30 );
			$this->filter( 'woocommerce_single_product_image_thumbnail_html', 'change_attribute_case', 30 );
		}
	}

	/**
	 * Change case of alt & title attributes in a post content string.
	 *
	 * @param  string $content Post content.
	 * @return string          New post content.
	 */
	public function change_attribute_case( $content ) {
		if ( empty( $content ) ) {
			return $content;
		}

		$stripped_content = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $content );
		preg_match_all( '/<img ([^>]+)\/?>/iU', $stripped_content, $matches, PREG_SET_ORDER );
		if ( empty( $matches ) ) {
			return $content;
		}

		foreach ( $matches as $image ) {
			$is_dirty = false;
			$attrs    = HTML::extract_attributes( $image[0] );

			if ( ! isset( $attrs['src'] ) ) {
				continue;
			}

			$this->set_image_attribute( $attrs, 'alt', $this->alt_change_case, $is_dirty );
			$this->set_image_attribute( $attrs, 'title', $this->title_change_case, $is_dirty );

			if ( $is_dirty ) {
				$new     = '<img' . HTML::attributes_to_string( $attrs ) . '>';
				$content = str_replace( $image[0], $new, $content );
			}
		}

		return $content;
	}

	/**
	 * Change image attribute case after checking condition.
	 *
	 * @param array   $attrs     Array which hold rel attribute.
	 * @param string  $attribute Attribute to set.
	 * @param boolean $condition Condition to check.
	 * @param boolean $is_dirty  Is dirty variable.
	 */
	private function set_image_attribute( &$attrs, $attribute, $condition, &$is_dirty ) {

		if ( $condition && ! empty( $attrs[ $attribute ] ) ) {
			$is_dirty = true;

			$attrs[ $attribute ] = $this->change_case( $attrs[ $attribute ], $condition );
		}
	}

	/**
	 * Turn first character of every sentence to uppercase.
	 *
	 * @param  string $string Original sring.
	 *
	 * @return string         New string.
	 */
	private function sentence_case( $string ) {
		$sentences  = preg_split( '/([.?!]+)/', $string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
		$new_string = '';
		foreach ( $sentences as $key => $sentence ) {
			$new_string .= ( $key & 1 ) === 0 ?
				$this->mb_ucfirst( trim( $sentence ) ) :
				$sentence . ' ';
		}

		return trim( $new_string );
	}

	/**
	 * Multibyte ucfirst().
	 *
	 * @param  string $string String.
	 *
	 * @return string         New string.
	 */
	private function mb_ucfirst( $string ) {
		return mb_strtoupper( mb_substr( $string, 0, 1 ) ) . mb_strtolower( mb_substr( $string, 1 ) );
	}

	/**
	 * Change case of string.
	 *
	 * @param  string $string String to change.
	 * @param  string $case   Case type to change to.
	 *
	 * @return string         New string.
	 */
	private function change_case( $string, $case ) {
		$cases_hash = [
			'titlecase'    => MB_CASE_TITLE,
			'sentencecase' => MB_CASE_LOWER,
			'lowercase'    => MB_CASE_LOWER,
			'uppercase'    => MB_CASE_UPPER,
		];

		if ( ! isset( $cases_hash[ $case ] ) ) {
			return $string;
		}

		if ( 'sentencecase' === $case ) {
			return $this->sentence_case( $string );
		}

		return mb_convert_case( $string, $cases_hash[ $case ] );
	}

	/**
	 * Add alt attribute for avatars if they don't have one.
	 *
	 * @param string $avatar      Avatar HTML.
	 * @param mixed  $id_or_email User ID or email.
	 * @param int    $size        Width in px.
	 * @param string $default     Fallback.
	 * @param string $alt         Alt attribute value.
	 * @param array  $args        Avatar args.
	 *
	 * @return string             New avatar HTML.
	 */
	public function avatar_add_missing_alt( $avatar, $id_or_email, $size, $default, $alt, $args ) { // phpcs:ignore
		if ( is_admin() ) {
			return $avatar;
		}

		if ( empty( $avatar ) ) {
			return $avatar;
		}

		if ( ! empty( $alt ) ) {
			return $avatar;
		}

		if ( ! preg_match( '/<img ([^>]+)\/?>/iU', $avatar ) ) {
			return $avatar;
		}

		$attrs = HTML::extract_attributes( $avatar );
		if ( ! empty( $attrs['alt'] ) ) {
			return $avatar;
		}

		$new_alt = '';
		if ( is_a( $id_or_email, 'WP_Comment' ) ) {
			$user = $id_or_email->comment_author;
		} elseif ( is_int( $id_or_email ) ) {
			// This is a user ID.
			$user = get_user_by( 'id', $id_or_email );
		} elseif ( is_string( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );
		}

		if ( is_a( $user, 'WP_User' ) ) {
			$new_alt = $user->get( 'display_name' );
		} elseif ( is_string( $user ) ) {
			$new_alt = $user;
		} else {
			return $avatar;
		}

		// Translators: placeholder is the username or email.
		$attrs['alt'] = sprintf( __( 'Avatar of %s', 'rank-math-pro' ), $new_alt );
		$attrs['alt'] = apply_filters( 'rank_math_pro/images/avatar_alt', $attrs['alt'], $id_or_email );

		$new = '<img' . HTML::attributes_to_string( $attrs ) . '>';

		// Change image title and alt casing.
		$this->alt_change_case   = Helper::get_settings( 'general.img_alt_change_case' );
		$this->title_change_case = Helper::get_settings( 'general.img_title_change_case' );

		if ( $this->alt_change_case || $this->title_change_case ) {
			$new = $this->change_attribute_case( $new );
		}

		return $new;
	}

	/**
	 * Add missing caption text if needed.
	 *
	 * @param string $out Shortcode output.
	 *
	 * @return string New shortcode output.
	 */
	public function add_caption( $out ) {
		if ( ! empty( $out['caption'] ) ) {
			return $out;
		}

		$out['caption'] = trim( Helper::replace_vars( Helper::get_settings( 'general.img_caption_format' ), $this->get_post() ) );

		return $out;
	}

	/**
	 * Change case for captions.
	 *
	 * @param string $out Shortcode output.
	 *
	 * @return string New shortcode output.
	 */
	public function change_caption_case( $out ) {
		if ( empty( $out['caption'] ) ) {
			return $out;
		}

		$out['caption'] = $this->change_case( $out['caption'], Helper::get_settings( 'general.img_caption_change_case' ) );

		return $out;
	}

	/**
	 * Change case for captions in Image Blocks.
	 *
	 * @param string $content Content output.
	 *
	 * @return string New output.
	 */
	public function change_content_caption_case( $content ) {
		$content = preg_replace_callback( '/(<figure[^<]+class="([^"]+ )?(wp-block-image|wp-caption).+<figcaption[^>]*>)([^<]+)(<\/figcaption>)/sU', [ $this, 'caption_case_cb' ], $content );
		return $content;
	}

	/**
	 * Change case for captions in Image Blocks.
	 *
	 * @param string $content Content output.
	 *
	 * @return string New output.
	 */
	public function content_add_caption( $content ) {
		$content = preg_replace_callback( '/<figure class="([^"]+ )?wp-block-image .+<\/figure>/sU', [ $this, 'add_caption_cb' ], $content );
		return $content;
	}

	/**
	 * Change case for captions in Image Blocks.
	 *
	 * @param string $matches Content output.
	 *
	 * @return string New output.
	 */
	public function caption_case_cb( $matches ) {
		return $matches[1] . $this->change_case( $matches[4], Helper::get_settings( 'general.img_caption_change_case' ) ) . $matches[5];
	}

	/**
	 * Add caption in Image Blocks.
	 *
	 * @param string $matches Content output.
	 *
	 * @return string New output.
	 */
	public function add_caption_cb( $matches ) {
		if ( stripos( $matches[0], '<figcaption' ) !== false ) {
			return $matches[0];
		}

		$caption = trim( Helper::replace_vars( Helper::get_settings( 'general.img_caption_format' ), $this->get_post( $matches[0] ) ) );
		return str_replace( '</figure>', '<figcaption>' . $caption . '</figcaption></figure>', $matches[0] );
	}

	/**
	 * Add missing attachment description if needed.
	 *
	 * @param string $content Original content.
	 *
	 * @return string         New content.
	 */
	public function add_description( $content ) {
		if ( get_post_type() !== 'attachment' ) {
			return $content;
		}

		$content_stripped = wp_strip_all_tags( $content );
		if ( ! empty( $content_stripped ) ) {
			return $content;
		}

		return $content . trim( Helper::replace_vars( Helper::get_settings( 'general.img_description_format' ), $this->get_post() ) );
	}

	/**
	 * Change case for image description.
	 *
	 * @param string $content Original content.
	 *
	 * @return string         New content.
	 */
	public function change_description_case( $content ) {
		if ( get_post_type() !== 'attachment' ) {
			return $content;
		}

		$parts = preg_split( '/(<[^>]+>)/', $content, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
		$new   = '';
		foreach ( $parts as $i => $part ) {
			if ( '<' === substr( trim( $part ), 0, 1 ) ) {
				$new .= $part;
				continue;
			}

			$new .= $this->change_case( $part, Helper::get_settings( 'general.img_description_change_case' ) );
		}

		return $new;
	}

	/**
	 * Search & replace in alt & title attributes.
	 *
	 * @param string $content Original post content.
	 *
	 * @return string         New post content.
	 */
	public function attribute_replacements( $content ) {
		$replacements = Helper::get_settings( 'general.image_replacements' );
		foreach ( $replacements as $replacement_id => $replacement ) {
			if ( ! count( array_intersect( $replacement['replace_in'], [ 'alt', 'title' ] ) ) ) {
				continue;
			}

			foreach ( $replacement['replace_in'] as $attr ) {
				if ( 'caption' === $attr ) {
					continue;
				}

				$content = $this->attribute_replacement( $content, $replacement['find'], $replacement['replace'], $attr );
			}
		}
		return $content;
	}

	/**
	 * Do the replacement in an attribute.
	 *
	 * @param  string $content   Original content.
	 * @param  string $find      Search string.
	 * @param  string $replace   Replacement string.
	 * @param  string $attribute Attribute to look for.
	 *
	 * @return string            New content.
	 */
	public function attribute_replacement( $content, $find, $replace, $attribute ) {
		$stripped_content = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $content );
		preg_match_all( '/<img ([^>]+)\/?>/iU', $stripped_content, $matches, PREG_SET_ORDER );
		if ( empty( $matches ) ) {
			return $content;
		}

		foreach ( $matches as $image ) {
			$attrs = HTML::extract_attributes( $image[0] );

			if ( ! isset( $attrs['src'] ) ) {
				continue;
			}

			if ( empty( $attrs[ $attribute ] ) ) {
				continue;
			}

			$attrs[ $attribute ] = str_replace( $find, $replace, $attrs[ $attribute ] );

			$new     = '<img' . HTML::attributes_to_string( $attrs ) . '>';
			$content = str_replace( $image[0], $new, $content );
		}

		return $content;
	}

	/**
	 * Search & replace in image captions.
	 *
	 * @param string $out   Shortcode output.
	 * @param array  $pairs Possible attributes.
	 * @param array  $atts  Shortcode attributes.
	 *
	 * @return string New shortcode output.
	 */
	public function caption_replacements( $out, $pairs, $atts ) {
		$replacements = Helper::get_settings( 'general.image_replacements' );
		foreach ( $replacements as $replacement_id => $replacement ) {
			if ( ! in_array( 'caption', $replacement['replace_in'], true ) ) {
				continue;
			}

			$caption = $atts['caption'];
			if ( empty( $caption ) ) {
				continue;
			}

			$new_caption = str_replace( $replacement['find'], $replacement['replace'], $caption );
			$out         = str_replace( $caption, $new_caption, $out );
		}

		return $out;
	}

	/**
	 * Get post object.
	 *
	 * @return object
	 */
	private function get_post( $image = [] ) {
		$post = \get_post();
		if ( empty( $post ) ) {
			$post = new stdClass();
		}

		if ( empty( $image ) ) {
			return $post;
		}

		$attrs = HTML::extract_attributes( $image );
		if ( empty( $attrs['src'] ) ) {
			return $post;
		}

		$post->filename = $attrs['src'];

		// Lazy load support.
		if ( ! empty( $attrs['data-src'] ) ) {
			$post->filename = $attrs['data-src'];
		} elseif ( ! empty( $attrs['data-layzr'] ) ) {
			$post->filename = $attrs['data-layzr'];
		} elseif ( ! empty( $attrs['nitro-lazy-srcset'] ) ) {
			$post->filename = $attrs['nitro-lazy-srcset'];
		}

		return $post;
	}

	/**
	 * Add options to Image SEO module.
	 *
	 * @param object $cmb CMB object.
	 */
	public function add_options( $cmb ) {
		$field_ids       = wp_list_pluck( $cmb->prop( 'fields' ), 'id' );
		$fields_position = array_search( 'img_title_format', array_keys( $field_ids ), true ) + 1;

		include_once dirname( __FILE__ ) . '/options.php';
	}

}
