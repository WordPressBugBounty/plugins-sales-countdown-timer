<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Intentional SALES_COUNTDOWN_TIMER_ prefixes.

class SALES_COUNTDOWN_TIMER_Countdown_Style {
	public static $settings;

	public function __construct() {
		self::$settings = new  SALES_COUNTDOWN_TIMER_Data();
	}

	public static function get_frontend_countdown_css() {
		self::$settings = new  SALES_COUNTDOWN_TIMER_Data();
		$shortcode_ids  = self::$settings->get_id();
		$css            = '';
		if ( $shortcode_ids && is_array( $shortcode_ids ) && $count_ids = count( $shortcode_ids ) ) {
			for ( $i = 0; $i < $count_ids; $i ++ ) {
				if ( empty( self::$settings->get_active()[ $i ] ) ) {
					continue;
				}
				$id           = woo_ctr_sanitize_html_class( $shortcode_ids[ $i ] );
				if ( '' === $id ) {
					continue;
				}
				$display_type = self::$settings->get_display_type()[ $i ];
				$countdown_timer_color              = woo_ctr_sanitize_css_color( self::$settings->get_countdown_timer_color()[ $i ] ?? '' );
				$countdown_timer_bg_color              = woo_ctr_sanitize_css_color( self::$settings->get_countdown_timer_bg_color()[ $i ] ?? '' );
				$countdown_timer_padding              = self::$settings->get_countdown_timer_padding()[ $i ]??'';
				$countdown_timer_border_radius              = self::$settings->get_countdown_timer_border_radius()[ $i ]??'';
				$countdown_timer_border_color              = woo_ctr_sanitize_css_color( self::$settings->get_countdown_timer_border_color()[ $i ] ?? '' );
				$datetime_value_color              = woo_ctr_sanitize_css_color( self::$settings->get_datetime_value_color()[ $i ] ?? '' );
				$datetime_value_bg_color              = woo_ctr_sanitize_css_color( self::$settings->get_datetime_value_bg_color()[ $i ] ?? '' );
				$datetime_value_font_size              = self::$settings->get_datetime_value_font_size()[ $i ]??'';
				$datetime_unit_color              = woo_ctr_sanitize_css_color( self::$settings->get_datetime_unit_color()[ $i ] ?? '' );
				$datetime_unit_bg_color              = woo_ctr_sanitize_css_color( self::$settings->get_datetime_unit_bg_color()[ $i ] ?? '' );
				$datetime_unit_font_size              = self::$settings->get_datetime_unit_font_size()[ $i ]??'';
				$circle_smooth_animation              = self::$settings->get_circle_smooth_animation()[ $i ]??'1';
				$countdown_timer_item_border_color              = woo_ctr_sanitize_css_color( self::$settings->get_countdown_timer_item_border_color()[ $i ] ?? '' );
				if ( $display_type == 4 ) {
					if ( $circle_smooth_animation == 1 ) {
						$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-progress-circle .woo-sctr-value-bar{transition: transform 1s linear;}';
					}
					$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $id . ' .woo-sctr-shortcode-countdown-1{';
					if ( $countdown_timer_color ) {
						$css .= 'color:' . esc_attr( $countdown_timer_color ) . ';';
					}
					if ( $countdown_timer_bg_color ) {
						$css .= 'background:' . esc_attr( $countdown_timer_bg_color ) . ';';
					}
					if ( $countdown_timer_padding ) {
						$css .= 'padding:' . esc_attr( $countdown_timer_padding ) . 'px;';
					}
					if ( $countdown_timer_border_radius ) {
						$css .= 'border-radius:' . esc_attr( $countdown_timer_border_radius ) . 'px;';
					}
					if ( $countdown_timer_bg_color ) {
						$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $id . '.woo-sctr-sticky-top{background:' . esc_attr( $countdown_timer_bg_color ) . ';}';
					}

					$css .= '}';
					if ( $countdown_timer_item_border_color ) {
						$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-progress-circle .woo-sctr-value-bar{border-color: ' . esc_attr( $countdown_timer_item_border_color ) . ';}';
						$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-progress-circle .woo-sctr-first50-bar{background-color: ' . esc_attr( $countdown_timer_item_border_color ) . ';}';
					}
					$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-shortcode-countdown-value-container,.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-shortcode-countdown-value{';
					if ( $datetime_value_color ) {
						$css .= 'color:' . esc_attr( $datetime_value_color ) . ';';
					}
					$css .= '}';
					if ( $datetime_value_bg_color ) {
						$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-progress-circle:after{background:' . esc_attr( $datetime_value_bg_color ) . ';}';
					}
					if ( $datetime_value_font_size ) {
						$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-progress-circle{';
						$css .= 'font-size: ' . esc_attr( $datetime_value_font_size ) . 'px;';
						$css .= '}';
					}

					$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-shortcode-countdown-text{';
					if ( $datetime_unit_color ) {
						$css .= 'color:' . esc_attr( $datetime_unit_color ) . ';';
					}
					if ( $datetime_unit_bg_color ) {
						$css .= 'background:' . esc_attr( $datetime_unit_bg_color ) . ';';
					}
					if ( $datetime_unit_font_size ) {
						$css .= 'font-size:' . esc_attr( $datetime_unit_font_size ) . 'px;';
					}

					$css .= '}';
				} else {
					if ( $display_type == 1 || $display_type == 2 ) {
						$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $id . '{display:block;text-align:center;}';
					}
					if ( $display_type == 3 ) {
						$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $id . '.woo-sctr-shortcode-wrap-wrap-inline{';
						if ( $countdown_timer_color ) {
							$css .= 'color:' . esc_attr( $countdown_timer_color ) . ';';
						}
						if ( $countdown_timer_bg_color ) {
							$css .= 'background:' . esc_attr( $countdown_timer_bg_color ) . ';';
						}
						if ( $countdown_timer_padding ) {
							$css .= 'padding:' . esc_attr( $countdown_timer_padding ) . 'px;';
						}
						if ( $countdown_timer_border_radius ) {
							$css .= 'border-radius:' . esc_attr( $countdown_timer_border_radius ) . 'px;';
						}
						if ( $countdown_timer_border_color ) {
							$css .= 'border: 1px solid ' . esc_attr( $countdown_timer_border_color ) . ';';
						}
						$css .= '}';
					} else {
						$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $id . ' .woo-sctr-shortcode-countdown-1{';
						if ( $countdown_timer_color ) {
							$css .= 'color:' . esc_attr( $countdown_timer_color ) . ';';
						}
						if ( $countdown_timer_bg_color ) {
							$css .= 'background:' . esc_attr( $countdown_timer_bg_color ) . ';';
						}
						if ( $countdown_timer_padding ) {
							$css .= 'padding:' . esc_attr( $countdown_timer_padding ) . 'px;';
						}
						if ( $countdown_timer_border_radius ) {
							$css .= 'border-radius:' . esc_attr( $countdown_timer_border_radius ) . 'px;';
						}
						if ( $countdown_timer_border_color ) {
							$css .= 'border: 1px solid ' . esc_attr( $countdown_timer_border_color ) . ';';
						}
						$css .= '}';
					}
					if ( $countdown_timer_bg_color ) {
						$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $id . '.woo-sctr-sticky-top{background:' . esc_attr( $countdown_timer_bg_color ) . ';}';
					}
					$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-shortcode-countdown-value,.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-shortcode-countdown-value-container{';
					if ( $datetime_value_color ) {
						$css .= 'color:' . esc_attr( $datetime_value_color ) . ';';
					}
					if ( $datetime_value_bg_color ) {
						$css .= 'background:' . esc_attr( $datetime_value_bg_color ) . ';';
					}
					if ( $datetime_value_font_size ) {
						$css .= 'font-size:' . esc_attr( $datetime_value_font_size ) . 'px;';
					}
					$css .= '}';
					$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-shortcode-countdown-text{';
					if ( $datetime_unit_color ) {
						$css .= 'color:' . esc_attr( $datetime_unit_color ) . ';';
					}
					if ( $datetime_unit_bg_color ) {
						$css .= 'background:' . esc_attr( $datetime_unit_bg_color ) . ';';
					}
					if ( $datetime_unit_font_size ) {
						$css .= 'font-size:' . esc_attr( $datetime_unit_font_size ) . 'px;';
					}
					$css  .= '}';
				}
			}
		}

		return $css;
	}
}