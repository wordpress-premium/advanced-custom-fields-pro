<?php
/**
 * CSV Import/Export panel template.
 *
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 */

namespace RankMathPro\Admin\CSV_Import_Export;

use RankMath\Helper;

defined( 'ABSPATH' ) || exit;

$import_in_progress = (bool) get_option( 'rank_math_csv_import' );

?>
<h2><?php esc_html_e( 'CSV File', 'rank-math-pro' ); ?></h2>

<p class="description">
	<?php esc_html_e( 'Import SEO meta data for posts, terms, and users from a CSV file.', 'rank-math-pro' ); ?>
</p>

<div id="csv-box" class="rank-math-box no-padding">
	<div class="rank-math-box-tabs wp-clearfix">
		<a href="#csv-panel-import" class="active-tab">
			<i class="rm-icon rm-icon-import"></i>
			<span class="rank-math-tab-text"><?php esc_html_e( 'Import CSV', 'rank-math-pro' ); ?></span>
		</a>
		<a href="#csv-panel-export" class="">
			<i class="rm-icon rm-icon-export"></i>
			<span class="rank-math-tab-text"><?php esc_html_e( 'Export CSV', 'rank-math-pro' ); ?></span>
		</a>
	</div>

	<div class="rank-math-box-content">

		<div class="rank-math-box-inner">
			<form id="csv-panel-import" class="rank-math-import-form cmb2-form active-tab" action="#csv-box" method="post" enctype="multipart/form-data" accept-charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
				<?php if ( ! $import_in_progress ) : ?>
					<p><label for="csv-import-me"><strong><?php esc_html_e( 'CSV File', 'rank-math-pro' ); ?></label></strong><p>
					<input type="file" name="csv-import-me" id="csv-import-me" value="" accept=".csv">
					<br>
					<span class="validation-message"><?php esc_html_e( 'Please select a CSV file to import.', 'rank-math-pro' ); ?></span>
					<div><input type="checkbox" class="cmb2-option" name="no_overwrite" id="csv_no_overwrite" value="on" checked="checked"> <label for="csv_no_overwrite"><?php esc_html_e( 'Do not overwrite existing data', 'rank-math-pro' ); ?></label></div>
					<p class="description no-overwrite-description"><?php esc_html_e( 'Check this to import meta fields only if their current meta value is empty.', 'rank-math-pro' ); ?></p>
					<div id="csv-import-warning" class="rank-math-notice notice-warning hidden">
						<p class="description">
							<?php // Translators: placeholder is the word Warning: in bold. ?>
							<?php printf( esc_html__( '%s It is recommended to save a database backup before using this option because importing malformed CSV can result in loss of data.', 'rank-math-pro' ), '<strong>' . esc_html__( 'Warning:', 'rank-math-pro' ) . '</strong> ' ); ?>
						</p>
					</div>
				<?php else: ?>
					<div id="csv-import-progress-details">
						<?php CSV_Import_Export::import_progress_details(); ?>
					</div>
				<?php endif; ?>

				<footer>
					<?php wp_nonce_field( 'rank_math_pro_csv_import' ); ?>
					<input type="hidden" name="object_id" value="csv-import-plz">
					<input type="hidden" name="action" value="wp_handle_upload">
					<?php if ( $import_in_progress ) : ?>
						<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'rank_math_cancel_csv_import' => 1 ] ), 'rank_math_pro_cancel_csv_import' ) ); ?>" id="csv-import-cancel" class="button button-link-delete csv-import-cancel"><?php esc_html_e( 'Cancel Import', 'rank-math-pro' ); ?></a>
						<span class="input-loading" style="visibility: visible;"></span>
					<?php else: ?>
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Import', 'rank-math-pro' ); ?></button>
					<?php endif; ?>
				</footer>
			</form>
			<form id="csv-panel-export" class="rank-math-export-form cmb2-form" action="" method="post">
				<ul class="cmb2-checkbox-list no-select-all cmb2-list">
					<?php foreach ( CSV_Import_Export::get_possible_object_types() as $object_type => $label ) : ?>
						<li>
							<input type="checkbox" class="cmb2-option" name="object_types[]" id="object_types_<?php echo sanitize_html_class( $object_type ); ?>" value="<?php echo sanitize_html_class( $object_type ); ?>" checked="checked"> <label for="object_types_<?php echo sanitize_html_class( $object_type ); ?>"><?php echo esc_html( $label ); ?></label>
							<?php if ( 'post' === $object_type ): ?>
								<div class="csv-advanced-options">
									<p class="description csv-advanced-options-description"><?php esc_html_e( 'Post types:', 'rank-math-pro' ); ?></p>
									<ul class="cmb2-checkbox-list no-select-all cmb2-list csv-advanced-options-list">
										<?php foreach ( Helper::get_allowed_post_types() as $post_type ) : ?>
											<li>
												<input type="checkbox" class="cmb2-option" name="post_types[]" id="post_types_<?php echo sanitize_html_class( $post_type ); ?>" value="<?php echo sanitize_html_class( $post_type ); ?>" checked="checked"> <label for="post_types_<?php echo sanitize_html_class( $post_type ); ?>"><?php echo esc_html( get_post_type_object( $post_type )->labels->name ); ?></label>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php elseif ( 'term' === $object_type ): ?>
								<div class="csv-advanced-options">
									<p class="description csv-advanced-options-description"><?php esc_html_e( 'Taxonomies:', 'rank-math-pro' ); ?></p>
									<ul class="cmb2-checkbox-list no-select-all cmb2-list csv-advanced-options-list">
										<?php foreach ( Helper::get_allowed_taxonomies() as $tax ) : ?>
											<li>
												<input type="checkbox" class="cmb2-option" name="taxonomies[]" id="taxonomies_<?php echo sanitize_html_class( $tax ); ?>" value="<?php echo sanitize_html_class( $tax ); ?>" checked="checked"> <label for="taxonomies_<?php echo sanitize_html_class( $tax ); ?>"><?php echo esc_html( get_taxonomy( $tax )->labels->name ); ?></label>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php elseif ( 'user' === $object_type ): ?>
								<div class="csv-advanced-options">
									<p class="description csv-advanced-options-description"><?php esc_html_e( 'User Roles:', 'rank-math-pro' ); ?></p>
									<ul class="cmb2-checkbox-list no-select-all cmb2-list csv-advanced-options-list">
										<?php foreach ( get_editable_roles() as $role_id => $role_obj ) : ?>
											<li>
												<input type="checkbox" class="cmb2-option" name="roles[]" id="roles_<?php echo sanitize_html_class( $role_id ); ?>" value="<?php echo sanitize_html_class( $role_id ); ?>" checked="checked"> <label for="roles_<?php echo sanitize_html_class( $role_id ); ?>"><?php echo esc_html( $role_obj['name'] ); ?></label>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
				<div class="csv-advanced-options readonly-columns">
					<p>
						<input type="checkbox" class="cmb2-option" name="readonly_columns" id="readonly_columns" value="1"> <label for="readonly_columns"><?php esc_html_e( 'Include read-only columns (SEO score and link counts)', 'rank-math-pro' ); ?></label>
					</p>
				</div>
				<p class="description"><?php esc_html_e( 'Choose the object types to export.', 'rank-math-pro' ); ?></p>

				<input type="checkbox" class="cmb2-option" name="use_advanced_options" id="csv-advanced-options-toggle" value="1"> <label for="csv-advanced-options-toggle"><?php esc_html_e( 'Use advanced options', 'rank-math-pro' ); ?></label>

				<footer>
					<?php wp_nonce_field( 'rank_math_pro_csv_export' ); ?>
					<input type="hidden" name="rank_math_pro_csv_export" value="1">
					<?php wp_nonce_field( 'rank_math_pro_csv_export', '_wpnonce', true, true ); ?>
					<button type="submit" class="button button-primary" id="export-csv"><?php esc_html_e( 'Export', 'rank-math-pro' ); ?></button>
					<span class="input-loading"></span>
				</footer>
			</form>
		</div>
	</div>
</div>
