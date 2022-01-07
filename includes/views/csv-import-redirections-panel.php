<?php
/**
 * CSV Import/Export Redirections panel template.
 *
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 */

namespace RankMathPro\Admin\CSV_Import_Export_Redirections;

defined( 'ABSPATH' ) || exit;

$import_in_progress = (bool) get_option( 'rank_math_csv_import_redirections' );
?>
<h2><?php esc_html_e( 'Redirections CSV', 'rank-math-pro' ); ?></h2>

<p class="description">
	<?php esc_html_e( 'Add or edit redirections by importing and exporting a CSV file.', 'rank-math-pro' ); ?>
</p>

<div id="csv-redirections-box" class="rank-math-box no-padding">
	<div class="rank-math-box-tabs wp-clearfix">	
		<a href="#csv-panel-import-redirections" class="active-tab">
			<i class="rm-icon rm-icon-import"></i>
			<span class="rank-math-tab-text"><?php esc_html_e( 'Import Redirections', 'rank-math-pro' ); ?></span>
		</a>
		<a href="#csv-panel-export-redirections" class="">
			<i class="rm-icon rm-icon-export"></i>
			<span class="rank-math-tab-text"><?php esc_html_e( 'Export Redirections', 'rank-math-pro' ); ?></span>
		</a>
	</div>

	<div class="rank-math-box-content">

		<div class="rank-math-box-inner">
			<form id="csv-panel-import-redirections" class="rank-math-import-form cmb2-form active-tab" action="<?php echo esc_url( add_query_arg( 'importexport', '1' ) ); ?>#csv-redirections-box" method="post" enctype="multipart/form-data" accept-charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
				<?php if ( ! $import_in_progress ) : ?>
					<p><label for="csv-import-me"><strong><?php esc_html_e( 'CSV File', 'rank-math-pro' ); ?></label></strong><p>
					<input type="file" name="csv-redirections-import-me" id="csv-redirections-import-me" value="" accept=".csv">
					<br>
					<span class="validation-message"><?php esc_html_e( 'Please select a CSV file to import.', 'rank-math-pro' ); ?></span>
					<!-- <div><input type="checkbox" class="cmb2-option" name="no_overwrite" id="csv_redirections_no_update" value="on" checked="checked"> <label for="csv_no_overwrite"><?php esc_html_e( 'Do not update current redirections', 'rank-math-pro' ); ?></label></div>
					<p class="description no-overwrite-description"><?php esc_html_e( 'Check this to only create non-existing redirections.', 'rank-math-pro' ); ?></p> -->
				<?php else: ?>
					<div id="csv-import-redirections-progress-details">
						<?php CSV_Import_Export_Redirections::import_progress_details(); ?>
					</div>
				<?php endif; ?>

				<footer>
					<?php wp_nonce_field( 'rank_math_pro_csv_import' ); ?>
					<input type="hidden" name="object_id" value="csv-import-redirections-plz">
					<input type="hidden" name="action" value="wp_handle_upload">
					<?php if ( $import_in_progress ) : ?>
						<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'rank_math_cancel_csv_import_redirections' => 1 ] ), 'rank_math_cancel_csv_import_redirections' ) ); ?>" id="csv-import-cancel" class="button button-link-delete csv-import-redirections-cancel"><?php esc_html_e( 'Cancel Import', 'rank-math-pro' ); ?></a>
						<span class="input-loading" style="visibility: visible;"></span>
					<?php else: ?>
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Import', 'rank-math-pro' ); ?></button>
					<?php endif; ?>
				</footer>
			</form>
			<form id="csv-panel-export-redirections" class="rank-math-export-form cmb2-form" action="" method="post">
				<p class="description"><?php esc_html_e( 'Export current redirections as a CSV file.', 'rank-math-pro' ); ?></p>
				<input type="checkbox" class="cmb2-option" name="include-deactivated" id="include-deactivated" value="1" checked="checked"> <label for="include-deactivated"><?php esc_html_e( 'Include deactivated redirections', 'rank-math-pro' ); ?></label>

				<footer>
					<?php wp_nonce_field( 'rank_math_pro_csv_export' ); ?>
					<input type="hidden" name="rank_math_pro_csv_export_redirections" value="1">
					<?php wp_nonce_field( 'rank_math_pro_csv_export_redirections', '_wpnonce', true, true ); ?>
					<button type="submit" class="button button-primary" id="export-redirections-csv"><?php esc_html_e( 'Export', 'rank-math-pro' ); ?></button>
					<span class="input-loading"></span>
				</footer>
			</form>
		</div>
	</div>
</div>
