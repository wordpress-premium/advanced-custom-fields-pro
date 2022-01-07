<?php
/**
 * Setup wizard.
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Admin;

use RankMath\KB;
use RankMath\Helper;
use RankMath\Traits\Ajax;
use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\Param;
use MyThemeShop\Helpers\WordPress;

defined( 'ABSPATH' ) || exit;

/**
 * Trends tool class.
 *
 * @codeCoverageIgnore
 */
class Setup_Wizard {

	use Hooker, Ajax;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		$this->action( 'admin_init', 'enqueue', 20 );
		$this->action( 'rank_math/wizard/settings/compatibility', 'add_sw_custom_mode', 20 );
		$this->filter( 'rank_math/wizard/steps', 'steps' );

		$this->ajax( 'import_settings', 'ajax_import_settings' );
	}

	/**
	 * Enqueue assets.
	 *
	 * @return void
	 */
	public function enqueue() {
		if ( Param::get( 'page' ) !== 'rank-math-wizard' ) {
			return;
		}

		wp_enqueue_style(
			'rank-math-pro-setup-wizard',
			RANK_MATH_PRO_URL . 'assets/admin/css/setup-wizard.css',
			null,
			rank_math_pro()->version
		);
		wp_enqueue_script(
			'rank-math-pro-setup-wizard',
			RANK_MATH_PRO_URL . 'assets/admin/js/setup-wizard.js',
			[ 'jquery' ],
			rank_math_pro()->version,
			true
		);
	}

	/**
	 * Add custom option in Setup Wizard.
	 *
	 * @param CMB2 $cmb CMB instance.
	 */
	public function add_sw_custom_mode( $cmb ) {
		$field = $cmb->get_field( 'setup_mode' );
		if ( false !== $field ) {
			$import_html = '<div id="rank-math-custom-mode-form" class="hidden">';

			$import_html .= '<div id="rank-math-custom-mode-form-initial">';
			$import_html .= '<input type="file" id="rank-math-custom-mode-file-field" value="" accept=".txt,.json">';
			$import_html .= '<button type="button" class="button button-primary button-small" id="rank-math-custom-mode-submit">' . __( 'Upload', 'rank-math-pro' ) . '</button>';
			$import_html .= '<p id="rank-math-custom-mode-import-help">';
			$import_html .= __( 'Select a Rank Math settings file on your computer and upload it to use the custom settings.', 'rank-math-pro' ) . ' ';
			// Translators: placeholder is a list of filetypes.
			$import_html .= sprintf( __( 'Allowed filetypes: %s', 'rank-math-pro' ), '<code>.txt, .json</code>' ) . ' ';
			$import_html .= '<a href="' . KB::get( 'import-export-settings' ) . '">' . __( 'More information', 'rank-math-pro' ) . '</a> ';
			$import_html .= '</p>';
			$import_html .= '</div>';

			$import_html .= '<p id="rank-math-custom-mode-import-progress" class="hidden"><img src="' . esc_url( get_admin_url() . 'images/loading.gif' ) . '" /> ' . __( 'Uploading...', 'rank-math-pro' ) . '</p>';
			$import_html .= '<p id="rank-math-custom-mode-import-success" class="hidden">' . __( 'Import finished. In the next steps you can review the settings.', 'rank-math-pro' ) . '</p>';
			$import_html .= '<p id="rank-math-custom-mode-import-fail" class="hidden">' . __( 'Import failed.', 'rank-math-pro' ) . ' <span id="rank-math-custom-mode-import-message"></span></p>';

			$import_html .= '</div>';

			$field->args['options'] = [
				/* translators: Option Description */
				'easy'     => '<div class="rank-math-mode-title">' . sprintf( __( 'Easy %s', 'rank-math-pro' ), '</div><p>' . __( 'For websites where you only want to change the basics and let Rank Math do most of the heavy lifting. Most settings are set to default as per industry best practices. One just has to set it and forget it.', 'rank-math-pro' ) . '</p>' ),
				/* translators: Option Description */
				'advanced' => '<div class="rank-math-mode-title">' . sprintf( __( 'Advanced %s', 'rank-math-pro' ), '</div><p>' . __( 'For the advanced users who want to control every SEO aspect of the website. You are offered options to change everything and have full control over the website’s SEO.', 'rank-math-pro' ) . '</p>' ),
				/* translators: Option Description */
				'custom'   => '<div class="rank-math-mode-title">' . sprintf( __( 'Custom Mode %s', 'rank-math-pro' ), '</div><p class="rank-math-mode-description">' . __( 'Select this if you have a custom Rank Math settings file you want to use.', 'rank-math-pro' ) . '</p>' ) . $import_html,
			];
		}
	}

	/**
	 * Ajax import settings.
	 */
	public function ajax_import_settings() {
		$this->verify_nonce( 'rank-math-ajax-nonce' );
		$this->has_cap_ajax( 'general' );

		$file = $this->has_valid_import_file();
		if ( false === $file ) {
			return false;
		}

		// Parse Options.
		$wp_filesystem = WordPress::get_filesystem();
		if ( is_null( $wp_filesystem ) ) {
			return false;
		}

		$settings = $wp_filesystem->get_contents( $file['file'] );
		$settings = json_decode( $settings, true );

		\unlink( $file['file'] );

		if ( is_array( $settings ) && $this->do_import_data( $settings ) ) {
			$this->success( __( 'Import successful.', 'rank-math-pro' ) );
			exit();
		}

		$this->error( __( 'No settings found to be imported.', 'rank-math-pro' ) );
		exit();
	}

	/**
	 * Add News/Video Sitemap in Setup Wizard.
	 *
	 * @param array $steps Wizard Steps.
	 *
	 * @return array
	 */
	public function steps( $steps ) {
		if ( isset( $steps['sitemaps'] ) ) {
			$steps['sitemaps']['class'] = '\\RankMathPro\\Wizard\\Sitemap';
		}

		return $steps;
	}

	/**
	 * Import has valid file.
	 *
	 * @return mixed
	 */
	private function has_valid_import_file() {
		if ( empty( $_FILES['import-me'] ) ) {
			$this->error( __( 'No file selected.', 'rank-math-pro' ) );
			return false;
		}

		$this->filter( 'upload_mimes', 'allow_txt_upload' );
		$file = wp_handle_upload( $_FILES['import-me'], [ 'test_form' => false ] );
		$this->remove_filter( 'upload_mimes', 'allow_txt_upload', 10 );

		if ( is_wp_error( $file ) ) {
			$this->error( __( 'Settings file could not be imported:', 'rank-math-pro' ) . ' ' . $file->get_error_message() );
			return false;
		}

		if ( isset( $file['error'] ) ) {
			$this->error( __( 'Settings could not be imported:', 'rank-math-pro' ) . ' ' . $file['error'] );
			return false;
		}

		if ( ! isset( $file['file'] ) ) {
			$this->error( __( 'Settings could not be imported: Upload failed.', 'rank-math-pro' ) );
			return false;
		}

		return $file;
	}

	/**
	 * Allow txt & json file upload.
	 *
	 * @param array $types Mime types keyed by the file extension regex corresponding to those types.
	 *
	 * @return array
	 */
	public function allow_txt_upload( $types ) {
		$types['txt']  = 'text/plain';
		$types['json'] = 'application/json';

		return $types;
	}

	/**
	 * Does import data.
	 *
	 * @param  array $data           Import data.
	 * @param  bool  $suppress_hooks Suppress hooks or not.
	 * @return bool
	 */
	private function do_import_data( array $data, $suppress_hooks = false ) {
		$this->run_import_hooks( 'pre_import', $data, $suppress_hooks );

		// Import options.
		$down = $this->import_set_options( $data );

		// Import capabilities.
		if ( ! empty( $data['role-manager'] ) ) {
			$down = true;
			Helper::set_capabilities( $data['role-manager'] );
		}

		// Import redirections.
		if ( ! empty( $data['redirections'] ) ) {
			$down = true;
			$this->import_set_redirections( $data['redirections'] );
		}

		$this->run_import_hooks( 'after_import', $data, $suppress_hooks );

		return $down;
	}

	/**
	 * Set options from data.
	 *
	 * @param array $data An array of data.
	 */
	private function import_set_options( $data ) {
		$set  = false;
		$hash = [
			'modules' => 'rank_math_modules',
			'general' => 'rank-math-options-general',
			'titles'  => 'rank-math-options-titles',
			'sitemap' => 'rank-math-options-sitemap',
		];

		foreach ( $hash as $key => $option_key ) {
			if ( ! empty( $data[ $key ] ) ) {
				$set = true;
				update_option( $option_key, $data[ $key ] );
			}
		}

		return $set;
	}

	/**
	 * Set redirections.
	 *
	 * @param array $redirections An array of redirections to import.
	 */
	private function import_set_redirections( $redirections ) {
		foreach ( $redirections as $key => $redirection ) {
			$matched = \RankMath\Redirections\DB::match_redirections_source( $redirection['sources'] );
			if ( ! empty( $matched ) ) {
				continue;
			}

			$sources = maybe_unserialize( $redirection['sources'] );
			if ( ! is_array( $sources ) ) {
				continue;
			}

			\RankMath\Redirections\DB::add(
				[
					'url_to'      => $redirection['url_to'],
					'sources'     => $sources,
					'header_code' => $redirection['header_code'],
					'hits'        => $redirection['hits'],
					'created'     => $redirection['created'],
					'updated'     => $redirection['updated'],
				]
			);
		}
	}

	/**
	 * Run import hooks
	 *
	 * @param string $hook     Hook to fire.
	 * @param array  $data     Import data.
	 * @param bool   $suppress Suppress hooks or not.
	 */
	private function run_import_hooks( $hook, $data, $suppress ) {
		if ( ! $suppress ) {
			/**
			 * Fires while importing settings.
			 *
			 * @since 0.9.0
			 *
			 * @param array $data Import data.
			 */
			$this->do_action( 'import/settings/' . $hook, $data );
		}
	}
}
