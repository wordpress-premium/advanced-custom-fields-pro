<?php
/**
 * CSV
 *
 * This class defines all code necessary to run during the CSV export.
 *
 * @since      x.x.x
 * @package    RankMathPro
 * @subpackage RankMathPro
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Installer class.
 */
class CSV {

	/**
	 * Do export.
	 *
	 * @param array $data Data to export.
	 * @return void
	 */
	public function export( $data = [] ) {
		if ( empty( $data['items'] ) ) {
			return;
		}

		$this->increase_limits();
		$this->headers( $data['filename'] );
		$this->output( $data );

		exit;
	}


	/**
	 * Try to increase time limit on server.
	 *
	 * @return void
	 */
	public function increase_limits() {
		set_time_limit( 300 );
	}

	/**
	 * Send headers.
	 *
	 * @param string $name File name.
	 * @return void
	 */
	public function headers( $name = '' ) {
		$sitename = sanitize_key( get_bloginfo( 'name' ) );
		$filename = $sitename . '_' .$name. '-' . date( 'Y-m-d_H-i-s' ) . '.csv'; // phpcs:ignore

		header( 'Content-Type: application/csv' );
		header( 'Content-Description: File Transfer' );
		header( "Content-Disposition: attachment; filename={$filename}" );
		header( 'Pragma: no-cache' );
	}

	/**
	 * Output
	 *
	 * @param array $data Data to export.
	 * @return void
	 */
	public function output( $data = [] ) {
		$this->output_csv( $data['columns'] );
		foreach ( $data['items'] as $line ) {
			$this->output_csv( array_values( $line ) );
		}
	}

	/**
	 * Output fputcsv instead of saving to a file.
	 *
	 * @param array $data Data array.
	 * @return void
	 */
	public function output_csv( $data ) {
		echo implode( ',', $data ) . "\n"; // phpcs:ignore
	}

	/**
	 * Escape CSV: quotes and slashes
	 *
	 * @param string $string String to escape.
	 * @return string
	 */
	public function escape_csv( $string ) {
		return '"' . str_replace( [ "'", '"', '\\' ], [ "''", '""', '\\\\' ], $string ) . '"';
	}
}
