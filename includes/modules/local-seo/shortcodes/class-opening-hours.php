<?php
/**
 * The Opening Hours shortcode Class.
 *
 * @since      1.0.1
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Local_Seo;

use RankMath\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Opening_Hours class.
 */
class Opening_Hours {

	/**
	 * Get Opening_Hours Data.
	 *
	 * @param Location_Shortcode $shortcode Location_Shortcode Instance.
	 * @param array              $schema    Schema data.
	 * @return string
	 */
	public function get_data( $shortcode, $schema ) {
		if ( ! isset( $schema['openingHoursSpecification'] ) ) {
			return '<p>' . esc_html__( 'Open 24/7', 'rank-math-pro' ) . '</p>';
		}

		if ( empty( $schema['openingHoursSpecification'] ) ) {
			return false;
		}

		$days = $this->normalize_days( $schema, $shortcode );
		ob_start();
		?>
		<h5><?php esc_html_e( 'Opening Hours:', 'rank-math-pro' ); ?></h5>
		<div class="rank-math-business-opening-hours">
			<?php
			foreach ( $days as $day => $hours ) {
				$time = ! empty( $hours['time'] ) ? implode( ' and ', $hours['time'] ) : esc_html__( 'Closed', 'rank-math-pro' );
				$time = str_replace( '-', ' &ndash; ', $time );

				printf(
					'<div class="rank-math-opening-hours"><span class="rank-math-opening-days">%1$s</span> : <span class="rank-math-opening-time">%2$s</span> <span class="rank-math-business-open">%3$s</span></div>',
					esc_html( $this->get_localized_day( $day ) ),
					esc_html( $time ),
					esc_html( $hours['isOpen'] )
				);
			}
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get Local Time.
	 *
	 * @param Location_Shortcode $shortcode Location_Shortcode Instance.
	 * @param array              $schema    Schema data.
	 * @return string
	 */
	private function get_local_time( $shortcode, $schema ) {
		if ( empty( $shortcode->atts['show_opening_now_label'] ) ) {
			return false;
		}

		$timezone       = ! empty( $schema['metadata']['timeZone'] ) ? $schema['metadata']['timeZone'] : wp_timezone_string();
		$local_datetime = new \DateTime( 'now', new \DateTimeZone( $timezone ) );

		return [
			'day'  => $local_datetime->format( 'l' ),
			'time' => strtotime( $local_datetime->format( 'H:i' ) ),
		];
	}

	/**
	 * Normalize Weekdays.
	 *
	 * @param array              $schema    Schema data.
	 * @param Location_Shortcode $shortcode Location_Shortcode Instance.
	 * @return array
	 */
	private function normalize_days( $schema, $shortcode ) {
		$hours      = $schema['openingHoursSpecification'];
		$days       = explode( ',', $shortcode->atts['show_days'] );
		$format     = ! isset( $schema['metadata']['use_24h_format'] ) ? Helper::get_settings( 'titles.opening_hours_format' ) : empty( $schema['metadata']['use_24h_format'] );
		$data       = [];
		$local_time = $this->get_local_time( $shortcode, $schema );
		foreach ( $days as $day ) {
			$day = ucfirst( trim( $day ) );

			$data[ $day ] = [
				'isOpen' => '',
			];

			foreach ( $hours as $hour ) {
				if ( ! in_array( $day, (array) $hour['dayOfWeek'], true ) ) {
					continue;
				}

				$open  = strtotime( $hour['opens'] );
				$close = strtotime( $hour['closes'] );

				$is_open = ! empty( $local_time ) &&
					$day === $local_time['day'] &&
					$local_time['time'] >= $open &&
					$local_time['time'] <= $close;

				$data[ $day ]['time'][] = $format ? date_i18n( 'g:i a', $open ) . ' - ' . date_i18n( 'g:i a', $close ) : $hour['opens'] . ' - ' . $hour['closes'];
				$data[ $day ]['isOpen'] = $is_open ? $this->get_opening_hours_note( $shortcode ) : '';
			}

			if ( $shortcode->atts['hide_closed_days'] && empty( $data[ $day ]['time'] ) ) {
				unset( $data[ $day ] );
			}
		}

		return $data;
	}

	/**
	 * Get Opening Hours note.
	 *
	 * @param Location_Shortcode $shortcode Location_Shortcode Instance.
	 * @return string
	 */
	private function get_opening_hours_note( $shortcode ) {
		return empty( $shortcode->atts['opening_hours_note'] ) ? esc_html__( 'Open now', 'rank-math-pro' ) : esc_html( $shortcode->atts['opening_hours_note'] );
	}

	/**
	 * Retrieve the full translated weekday word.
	 *
	 * @param string $day Day to translate.
	 *
	 * @return string
	 */
	private function get_localized_day( $day ) {
		global $wp_locale;
		$hash = [
			'Sunday'    => 0,
			'Monday'    => 1,
			'Tuesday'   => 2,
			'Wednesday' => 3,
			'Thursday'  => 4,
			'Friday'    => 5,
			'Saturday'  => 6,
		];

		return ! isset( $hash[ $day ] ) ? $day : $wp_locale->get_weekday( $hash[ $day ] );
	}
}
