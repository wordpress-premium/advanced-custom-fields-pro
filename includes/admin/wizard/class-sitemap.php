<?php
/**
 * The Sitemap wizard step
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Sitemap
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Wizard;

use RankMath\KB;
use RankMath\Helper;
use RankMath\Wizard\Wizard_Step;

defined( 'ABSPATH' ) || exit;

/**
 * Step class.
 */
class Sitemap implements Wizard_Step {

	/**
	 * Render step body.
	 *
	 * @param object $wizard Wizard class instance.
	 *
	 * @return void
	 */
	public function render( $wizard ) {
		?>
		<header>
			<h1><?php esc_html_e( 'Sitemap', 'rank-math-pro' ); ?> </h1>
			<p>
				<?php
				/* translators: Link to How to Setup Sitemap KB article */
				printf( esc_html__( 'Choose your Sitemap configuration and select which type of posts or pages you want to include in your Sitemaps. %s', 'rank-math-pro' ), '<a href="' . KB::get( 'configure-sitemaps', 'SW Sitemap Step' ) . '" target="_blank">' . esc_html__( 'Learn more.', 'rank-math-pro' ) . '</a>' );
				?>
			</p>
		</header>

		<?php $wizard->cmb->show_form(); ?>

		<footer class="form-footer wp-core-ui rank-math-ui">
			<?php $wizard->get_skip_link(); ?>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save and Continue', 'rank-math-pro' ); ?></button>
		</footer>
		<?php
	}

	/**
	 * Render form for step.
	 *
	 * @param object $wizard Wizard class instance.
	 *
	 * @return void
	 */
	public function form( $wizard ) {
		$wizard->cmb->add_field(
			[
				'id'      => 'sitemap',
				'type'    => 'toggle',
				'name'    => esc_html__( 'Sitemaps', 'rank-math-pro' ),
				'desc'    => esc_html__( 'XML Sitemaps help search engines index your website&#039;s content more effectively.', 'rank-math-pro' ),
				'default' => Helper::is_module_active( 'sitemap' ) ? 'on' : 'off',
			]
		);

		$wizard->cmb->add_field(
			[
				'id'      => 'include_images',
				'type'    => 'toggle',
				'name'    => esc_html__( 'Include Images', 'rank-math-pro' ),
				'desc'    => esc_html__( 'Include reference to images from the post content in sitemaps. This helps search engines index your images better.', 'rank-math-pro' ),
				'default' => Helper::get_settings( 'sitemap.include_images' ) ? 'on' : 'off',
				'classes' => 'features-child',
				'dep'     => [ [ 'sitemap', 'on' ] ],
			]
		);

		// Post Types.
		$post_types = $this->get_post_types();
		$wizard->cmb->add_field(
			[
				'id'      => 'sitemap_post_types',
				'type'    => 'multicheck',
				'name'    => esc_html__( 'Public Post Types', 'rank-math-pro' ),
				'desc'    => esc_html__( 'Select post types to enable SEO options for them and include them in the sitemap.', 'rank-math-pro' ),
				'options' => $post_types['post_types'],
				'default' => $post_types['defaults'],
				'classes' => 'features-child cmb-multicheck-inline' . ( count( $post_types['post_types'] ) === count( $post_types['defaults'] ) ? ' multicheck-checked' : '' ),
				'dep'     => [ [ 'sitemap', 'on' ] ],
			]
		);

		// Taxonomies.
		$taxonomies = $this->get_taxonomies();
		$wizard->cmb->add_field(
			[
				'id'      => 'sitemap_taxonomies',
				'type'    => 'multicheck',
				'name'    => esc_html__( 'Public Taxonomies', 'rank-math-pro' ),
				'desc'    => esc_html__( 'Select taxonomies to enable SEO options for them and include them in the sitemap.', 'rank-math-pro' ),
				'options' => $taxonomies['taxonomies'],
				'default' => $taxonomies['defaults'],
				'classes' => 'features-child cmb-multicheck-inline' . ( count( $taxonomies['taxonomies'] ) === count( $taxonomies['defaults'] ) ? ' multicheck-checked' : '' ),
				'dep'     => [ [ 'sitemap', 'on' ] ],
			]
		);

		$news_sitemap_dep   = [ 'relation' => 'and' ] + [ [ 'sitemap', 'on' ] ];
		$news_sitemap_dep[] = [ 'news-sitemap', 'on' ];
		$wizard->cmb->add_field(
			[
				'id'      => 'news_sitemap_title',
				'type'    => 'raw',
				'content' => sprintf( '<br><br><div class="cmb-form cmb-row nopb"><header class="sitemap-title"><h1>%1$s</h1><p>%2$s</p></header><div class="rank-math-cmb-dependency hidden" data-relation="or"><span class="hidden" data-field="sitemap" data-comparison="=" data-value="on"></span></div></div>', esc_html__( 'News Sitemap', 'rank-math-pro' ), esc_html__( 'News Sitemaps allow you to control which content you submit to Google News.', 'rank-math-pro' ) ),
			]
		);

		$wizard->cmb->add_field(
			[
				'id'      => 'news-sitemap',
				'type'    => 'toggle',
				'name'    => esc_html__( 'News Sitemaps', 'rank-math-pro' ),
				'desc'    => esc_html__( 'You will generally only need a News Sitemap when your website is included in Google News.', 'rank-math-pro' ),
				'default' => Helper::is_module_active( 'news-sitemap' ) ? 'on' : 'off',
				'dep'     => [ [ 'sitemap', 'on' ] ],
			]
		);

		$wizard->cmb->add_field(
			[
				'id'      => 'news_sitemap_publication_name',
				'type'    => 'text',
				'name'    => esc_html__( 'Google News Publication Name', 'rank-math-pro' ),
				'classes' => 'features-child cmb-multicheck-inline',
				'desc'    => wp_kses_post( __( 'The name of the news publication. It must match the name exactly as it appears on your articles in news.google.com, omitting any trailing parentheticals. <a href="https://support.google.com/news/publisher-center/answer/9606710" target="_blank">More information at support.google.com</a>', 'rank-math-pro' ) ),
				'default' => Helper::get_settings( 'sitemap.news_sitemap_publication_name' ),
				'dep'     => $news_sitemap_dep,
			]
		);

		$wizard->cmb->add_field(
			[
				'id'      => 'news_sitemap_post_type',
				'type'    => 'multicheck_inline',
				'name'    => esc_html__( 'News Post Type', 'rank-math-pro' ),
				'desc'    => esc_html__( 'Select the post type you use for News articles.', 'rank-math-pro' ),
				'classes' => 'features-child cmb-multicheck-inline',
				'options' => $post_types['post_types'],
				'default' => Helper::get_settings( 'sitemap.news_sitemap_post_type' ),
				'dep'     => $news_sitemap_dep,
			]
		);

		$wizard->cmb->add_field(
			[
				'id'      => 'video_sitemap_title',
				'type'    => 'raw',
				'content' => sprintf( '<br><br><div class="cmb-form cmb-row nopb"><header class="sitemap-title"><h1>%1$s</h1><p>%2$s</p></header><div class="rank-math-cmb-dependency hidden" data-relation="or"><span class="hidden" data-field="sitemap" data-comparison="=" data-value="on"></span></div></div>', esc_html__( 'Video Sitemap', 'rank-math-pro' ), esc_html__( 'Video Sitemaps give search engines information about video content on your site.', 'rank-math-pro' ) ),
			]
		);

		$wizard->cmb->add_field(
			[
				'id'      => 'video-sitemap',
				'type'    => 'toggle',
				'name'    => esc_html__( 'Video Sitemaps', 'rank-math-pro' ),
				'desc'    => esc_html__( 'You will generally only need a Video Sitemap when your website has video content.', 'rank-math-pro' ),
				'default' => Helper::is_module_active( 'video-sitemap' ) ? 'on' : 'off',
				'dep'     => [ [ 'sitemap', 'on' ] ],
			]
		);

		$wizard->cmb->add_field(
			[
				'id'      => 'video_sitemap_post_type',
				'type'    => 'multicheck_inline',
				'name'    => esc_html__( 'Video Post Type', 'rank-math-pro' ),
				'desc'    => esc_html__( 'Select the post type where you use videos and want them to be shown in the Video search.', 'rank-math-pro' ),
				'classes' => 'features-child cmb-multicheck-inline',
				'options' => $post_types['post_types'],
				'default' => Helper::get_settings( 'sitemap.video_sitemap_post_type', array_keys( $post_types['post_types'] ) ),
				'dep'     => [ 'relation' => 'and' ] + [ [ 'sitemap', 'on' ], [ 'video-sitemap', 'on' ] ],
			]
		);
	}

	/**
	 * Save handler for step.
	 *
	 * @param array  $values Values to save.
	 * @param object $wizard Wizard class instance.
	 *
	 * @return bool
	 */
	public function save( $values, $wizard ) {
		$settings = rank_math()->settings->all_raw();
		Helper::update_modules( [ 'sitemap' => $values['sitemap'] ] );
		Helper::update_modules( [ 'news-sitemap' => $values['news-sitemap'] ] );
		Helper::update_modules( [ 'video-sitemap' => $values['video-sitemap'] ] );

		if ( 'on' === $values['sitemap'] ) {
			$settings['sitemap']['include_images'] = $values['include_images'];

			$settings = $this->save_post_types( $settings, $values );
			$settings = $this->save_taxonomies( $settings, $values );
			Helper::update_all_settings( null, null, $settings['sitemap'] );
		}

		if ( 'on' === $values['news-sitemap'] ) {
			$settings['sitemap']['news_sitemap_publication_name'] = ! empty( $values['news_sitemap_publication_name'] ) ? $values['news_sitemap_publication_name'] : '';
			$settings['sitemap']['news_sitemap_post_type']        = ! empty( $values['news_sitemap_post_type'] ) ? $values['news_sitemap_post_type'] : [];

			Helper::update_all_settings( null, null, $settings['sitemap'] );
		}

		if ( 'on' === $values['video-sitemap'] ) {
			$settings['sitemap']['video_sitemap_post_type'] = ! empty( $values['video_sitemap_post_type'] ) ? $values['video_sitemap_post_type'] : [];

			Helper::update_all_settings( null, null, $settings['sitemap'] );
		}

		Helper::schedule_flush_rewrite();
		return true;
	}

	/**
	 * Get post type data.
	 *
	 * @return array
	 */
	private function get_post_types() {
		$p_defaults = [];
		$post_types = Helper::choices_post_types();
		if ( Helper::get_settings( 'general.attachment_redirect_urls', true ) ) {
			unset( $post_types['attachment'] );
		}

		foreach ( $post_types as $post_type => $object ) {
			if ( true === Helper::get_settings( "sitemap.pt_{$post_type}_sitemap" ) ) {
				$p_defaults[] = $post_type;
			}
		}

		return [
			'defaults'   => $p_defaults,
			'post_types' => $post_types,
		];
	}

	/**
	 * Get taxonomies data.
	 *
	 * @return array
	 */
	private function get_taxonomies() {
		$t_defaults = [];
		$taxonomies = Helper::get_accessible_taxonomies();
		unset( $taxonomies['post_tag'], $taxonomies['post_format'], $taxonomies['product_tag'] );
		$taxonomies = wp_list_pluck( $taxonomies, 'label', 'name' );
		foreach ( $taxonomies as $taxonomy => $label ) {
			if ( true === Helper::get_settings( "sitemap.tax_{$taxonomy}_sitemap" ) ) {
				$t_defaults[] = $taxonomy;
			}
		}

		return [
			'defaults'   => $t_defaults,
			'taxonomies' => $taxonomies,
		];
	}

	/**
	 * Save Post Types
	 *
	 * @param array $settings Array of all settings.
	 * @param array $values   Array of posted values.
	 *
	 * @return array
	 */
	private function save_post_types( $settings, $values ) {
		$post_types = Helper::choices_post_types();
		if ( ! isset( $values['sitemap_post_types'] ) ) {
			$values['sitemap_post_types'] = [];
		}

		foreach ( $post_types as $post_type => $object ) {
			$settings['sitemap'][ "pt_{$post_type}_sitemap" ] = in_array( $post_type, $values['sitemap_post_types'], true ) ? 'on' : 'off';
		}

		return $settings;
	}

	/**
	 * Save Taxonomies
	 *
	 * @param array $settings Array of all settings.
	 * @param array $values   Array of posted values.
	 *
	 * @return array
	 */
	private function save_taxonomies( $settings, $values ) {
		$taxonomies = Helper::get_accessible_taxonomies();
		$taxonomies = wp_list_pluck( $taxonomies, 'label', 'name' );
		if ( ! isset( $values['sitemap_taxonomies'] ) ) {
			$values['sitemap_taxonomies'] = [];
		}

		foreach ( $taxonomies as $taxonomy => $label ) {
			$settings['sitemap'][ "tax_{$taxonomy}_sitemap" ] = in_array( $taxonomy, $values['sitemap_taxonomies'], true ) ? 'on' : 'off';
		}

		return $settings;
	}
}
