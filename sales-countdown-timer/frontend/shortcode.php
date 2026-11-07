<?php

/**
 * Class SALES_COUNTDOWN_TIMER_Frontend_Shortcode
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Intentional SALES_COUNTDOWN_TIMER_ / woo_sctr_ / sctv_ prefixes.

class SALES_COUNTDOWN_TIMER_Frontend_Shortcode {
	protected $settings;

	public function __construct() {
		$this->settings = new SALES_COUNTDOWN_TIMER_Data();
		add_action( 'init', array( $this, 'shortcode_init' ) );
	}

	public function shortcode_init() {
		add_shortcode( 'sales_countdown_timer', array( $this, 'register_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'shortcode_enqueue_script' ) );
	}

	/**
	 * Attach profile CSS once when countdown assets are enqueued.
	 */
	public function maybe_add_countdown_inline_css() {
		static $added = false;
		if ( $added ) {
			return;
		}
		$css = SALES_COUNTDOWN_TIMER_Countdown_Style::get_frontend_countdown_css();
		if ( $css && wp_style_is( 'woo-sctr-shortcode-style', 'enqueued' ) ) {
			wp_add_inline_style( 'woo-sctr-shortcode-style', $css );
			$added = true;
		}
	}

	public function shortcode_enqueue_script() {
		$suffix = WP_DEBUG ? '' : '.min';
		if ( ! wp_script_is( 'woo-sctr-shortcode-style', 'registered' ) ) {
			wp_register_style( 'woo-sctr-shortcode-style', SALES_COUNTDOWN_TIMER_CSS . 'shortcode-style' . $suffix . '.css', array(), SALES_COUNTDOWN_TIMER_VERSION );
		}
		if ( ! wp_script_is( 'woo-sctr-shortcode-script', 'registered' ) ) {
			wp_register_script( 'woo-sctr-shortcode-script', SALES_COUNTDOWN_TIMER_JS . 'shortcode-script' . $suffix . '.js', array( 'jquery' ), SALES_COUNTDOWN_TIMER_VERSION, false );
		}
	}

	public function register_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'                        => '',
				'names'                     => '',
				'message'                   => '',
				'active'                    => 0,
				'enable_single_product'     => 0,
				'is_variation'              => 0,
				'sale_price_type'           => 0,
				'time_type'                 => 'fixed',
				'datetime_format'           => '',
				'hide_zero_items'           => 0,
				'sale_from_date'            => 0,
				'sale_to_date'              => 0,
				'sale_from_time'            => 0,
				'sale_to_time'              => 0,
				'upcoming'                  => 0,
				'upcoming_message'          => 'Starts in {countdown_timer}',
				'style'                     => 1,
				'time_separator'            => '',
				'display_type'              => '',
				'sale_countdown_timer_id_t' => '',
			),
			$atts,
			'sales_countdown_timer'
		);

		$id                        = $atts['id'];
		$names                     = $atts['names'];
		$message                   = $atts['message'];
		$active                    = $atts['active'];
		$enable_single_product     = $atts['enable_single_product'];
		$is_variation              = $atts['is_variation'];
		$sale_price_type           = $atts['sale_price_type'];
		$time_type                 = $atts['time_type'];
		$datetime_format           = $atts['datetime_format'];
		$hide_zero_items           = $atts['hide_zero_items'];
		$sale_from_date            = $atts['sale_from_date'];
		$sale_to_date              = $atts['sale_to_date'];
		$sale_from_time            = $atts['sale_from_time'];
		$sale_to_time              = $atts['sale_to_time'];
		$upcoming                  = $atts['upcoming'];
		$upcoming_message          = $atts['upcoming_message'];
		$style                     = $atts['style'];
		$time_separator            = $atts['time_separator'];
		$display_type              = $atts['display_type'];
		$sale_countdown_timer_id_t = $atts['sale_countdown_timer_id_t'];

		global $sale_countdown_timer_count;
		$sale_countdown_timer_count ++;
		$sale_countdown_timer_id_t = sanitize_html_class( (string) $sale_countdown_timer_id_t );
		$sale_countdown_timer_id = $sale_countdown_timer_id_t ? $sale_countdown_timer_id_t . '_' . $sale_countdown_timer_count : $sale_countdown_timer_count;
		$now                     = current_time( 'timestamp' );
		$sale_from_date          = strtotime( $sale_from_date );
		$sale_to_date            = strtotime( $sale_to_date );
		$sale_from_time          = woo_ctr_time( $sale_from_time );
		$sale_to_time            = woo_ctr_time( $sale_to_time );
		$sale_text_before        = $sale_text_after = '';
		$day                     = $hour = $minute = $second = '';
		if ( $id ) {
			$index = array_search( $id, $this->settings->get_id(), true );
			if ( false !== $index ) {
				if ( ! $sale_from_date && ! $enable_single_product ) {
					$sale_from_date = strtotime( $this->settings->get_sale_from_date()[ $index ] );
				}
				if ( ! $sale_to_date && ! $enable_single_product ) {
					$sale_to_date = strtotime( $this->settings->get_sale_to_date()[ $index ] );
				}
				if ( ! $sale_from_time && ! $enable_single_product ) {
					$sale_from_time = woo_ctr_time( $this->settings->get_sale_from_time()[ $index ] );
				}
				if ( ! $sale_to_time && ! $enable_single_product ) {
					$sale_to_time = woo_ctr_time( $this->settings->get_sale_to_time()[ $index ] );
				}
				$active                             = $active ?: $this->settings->get_active()[ $index ];
				$message                            = $message ?: $this->settings->get_message()[ $index ];
				$upcoming_message                   = $this->settings->get_upcoming_message()[ $index ];
				$count_style                        = $datetime_format ?: $this->settings->get_count_style()[ $index ];
				$upcoming                           = $this->settings->get_upcoming()[ $index ];
				$time_separator                     = $time_separator ?: $this->settings->get_time_separator()[ $index ];
				$display_type                       = $this->settings->get_display_type()[ $index ];
				$countdown_timer_padding            = $this->settings->get_countdown_timer_padding()[ $index ];
				$countdown_timer_item_border_color  = $this->settings->get_countdown_timer_item_border_color()[ $index ];
				$countdown_timer_item_border_radius = $this->settings->get_countdown_timer_item_border_radius()[ $index ];
				$countdown_timer_item_height        = $this->settings->get_countdown_timer_item_height()[ $index ];
				$countdown_timer_item_width         = $this->settings->get_countdown_timer_item_width()[ $index ];
				$datetime_unit_font_size            = $this->settings->get_datetime_unit_font_size()[ $index ];
				$datetime_value_font_size           = $this->settings->get_datetime_value_font_size()[ $index ];
				$size_on_archive_page               = ( isset( $this->settings->get_size_on_archive_page()[ $index ] ) && $this->settings->get_size_on_archive_page()[ $index ] ) ? $this->settings->get_size_on_archive_page()[ $index ] : 75;
				$datetime_unit_position             = isset( $this->settings->get_datetime_unit_position()[ $index ] ) ? $this->settings->get_datetime_unit_position()[ $index ] : 'bottom';
				$animation_style                    = isset( $this->settings->get_animation_style()[ $index ] ) ? $this->settings->get_animation_style()[ $index ] : 'default';

				if ( ! isset( $hide_zero_items ) || ( isset( $hide_zero_items ) && ! $hide_zero_items ) ) {
					$hide_zero_items = $this->settings->get_countdown_timer_hide_zero()[ $index ];
				}
			}
		}
		if ( ! $active ) {
			return false;
		}

		/*handle time type*/
		if ( $time_type == 'fixed' ) {
			$sale_from = $sale_from_date + $sale_from_time;
			$sale_to   = $sale_to_date + $sale_to_time;
		} else {
			$sale_from = strtotime( 'today' ) + woo_ctr_time( $sale_from_time );
			$sale_to   = strtotime( 'today' ) + woo_ctr_time( $sale_to_time );
			if ( $sale_from >= $sale_to ) {
				if ( $now > $sale_to ) {
					$sale_to += 86400;
				} else {
					$sale_from -= 86400;
				}
			}

		}

		if ( $sale_to < $sale_from ) {
			return false;
		}
		if ( $sale_from > $now ) {
			if ( $upcoming && $enable_single_product ) {
				$end_time = $sale_from - $now;
				$text     = $upcoming_message;
			} else {
				return false;
			}
		} else {
			if ( $sale_to < $now ) {
				return false;
			} else {
				$end_time = $sale_to - $now;
				$text     = $message;
			}
		}
		/*datetime format*/
		switch ( $count_style ) {
			case 1:
				$day    = esc_html__( 'days', 'sales-countdown-timer' );
				$hour   = esc_html__( 'hrs', 'sales-countdown-timer' );
				$minute = esc_html__( 'mins', 'sales-countdown-timer' );
				$second = esc_html__( 'secs', 'sales-countdown-timer' );
				break;
			case 2:
				$day    = esc_html__( 'days', 'sales-countdown-timer' );
				$hour   = esc_html__( 'hours', 'sales-countdown-timer' );
				$minute = esc_html__( 'minutes', 'sales-countdown-timer' );
				$second = esc_html__( 'seconds', 'sales-countdown-timer' );
				break;
			case 3:
				$day    = '';
				$hour   = '';
				$minute = '';
				$second = '';
				break;
			case 4:
				$day    = esc_html__( 'd', 'sales-countdown-timer' );
				$hour   = esc_html__( 'h', 'sales-countdown-timer' );
				$minute = esc_html__( 'm', 'sales-countdown-timer' );
				$second = esc_html__( 's', 'sales-countdown-timer' );
				break;
			default:
		}

		if ( ! wp_script_is( 'woo-sctr-shortcode-script', 'enqueued' ) ) {
			wp_enqueue_script( 'woo-sctr-shortcode-script' );
		}
		if ( ! wp_script_is( 'woo-sctr-shortcode-style', 'enqueued' ) ) {
			wp_enqueue_style( 'woo-sctr-shortcode-style' );
		}
		$this->maybe_add_countdown_inline_css();
		$shop_css = '';
		if ( is_tax( 'product_cat' ) || is_post_type_archive( 'product' ) ) {
			if ( $countdown_timer_padding ) {
				$countdown_timer_padding = $countdown_timer_padding * $size_on_archive_page / 100;
			}
			if ( $datetime_value_font_size ) {
				$datetime_value_font_size = $datetime_value_font_size * $size_on_archive_page / 100;
			}
			if ( $datetime_unit_font_size ) {
				$datetime_unit_font_size = $datetime_unit_font_size * $size_on_archive_page / 100;
			}
			if ( $countdown_timer_item_height ) {
				$countdown_timer_item_height = $countdown_timer_item_height * $size_on_archive_page / 100;
			}
			if ( $countdown_timer_item_width ) {
				$countdown_timer_item_width = $countdown_timer_item_width * $size_on_archive_page / 100;
			}
			$shop_css = '.woo-sctr-shortcode-wrap-wrap{width: 100%;justify-content: center;}';
		}
		$countdown_timer_padding_mobile     = $countdown_timer_padding ? $countdown_timer_padding * $size_on_archive_page / 100 : '';
		$datetime_value_font_size_mobile    = $datetime_value_font_size ? $datetime_value_font_size * $size_on_archive_page / 100 : '';
		$datetime_unit_font_size_mobile     = $datetime_unit_font_size ? $datetime_unit_font_size * $size_on_archive_page / 100 : '';
		$countdown_timer_item_height_mobile = $countdown_timer_item_height ? $countdown_timer_item_height * $size_on_archive_page / 100 : '';
		$countdown_timer_item_width_mobile  = $countdown_timer_item_width ? $countdown_timer_item_width * $size_on_archive_page / 100 : '';

		$end_time  = (int) $end_time - 1;
		$day_left  = floor( $end_time / 86400 );
		$hour_left = floor( ( $end_time - 86400 * (int) $day_left ) / 3600 );
		$min_left  = floor( ( $end_time - 86400 * (int) $day_left - 3600 * (int) $hour_left ) / 60 );
		$sec_left  = $end_time - 86400 * (int) $day_left - 3600 * (int) $hour_left - 60 * (int) $min_left;
		$day_deg   = $day_left;
		$hour_deg  = $hour_left * 15;
		$min_deg   = $min_left * 6;
		$sec_deg   = ( $sec_left + 1 ) * 6;

		if ( $is_variation ) {
			$day_left_t = $hour_left_t = $min_left_t = $sec_left_t = '00';
		} else {
			$day_left_t  = zeroise( $day_left, 2 );
			$hour_left_t = zeroise( $hour_left, 2 );
			$min_left_t  = zeroise( $min_left, 2 );
			$sec_left_t  = zeroise( $sec_left, 2 );
			if ( $animation_style == 'default' ) {
				$sec_left_t = zeroise( $sec_left == 59 ? 0 : $sec_left + 1, 2 );
			}
		}
		$css = '';
		if ( $display_type == 4 ) {
			/*set circle fill*/
			$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-shortcode-countdown-date.woo-sctr-shortcode-countdown-unit .woo-sctr-progress-circle .woo-sctr-value-bar{' . 'transform: rotate(' . esc_attr( $day_deg ) . 'deg);}';
			$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-shortcode-countdown-hour.woo-sctr-shortcode-countdown-unit .woo-sctr-progress-circle .woo-sctr-value-bar{' . 'transform: rotate(' . esc_attr( $hour_deg ) . 'deg);}';
			$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-shortcode-countdown-minute.woo-sctr-shortcode-countdown-unit .woo-sctr-progress-circle .woo-sctr-value-bar{' . 'transform: rotate(' . esc_attr( $min_deg ) . 'deg);}';
			$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-shortcode-countdown-second.woo-sctr-shortcode-countdown-unit .woo-sctr-progress-circle .woo-sctr-value-bar{' . 'transform: rotate(' . esc_attr( $sec_deg ) . 'deg);}';
			/*mobile*/
			$css .= '@media screen and (max-width:600px){';
			if ( $countdown_timer_padding_mobile !== '' ) {
				$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-1{padding:' . esc_attr( $countdown_timer_padding_mobile ) . 'px;}';
			}
			if ( $datetime_value_font_size_mobile !== '' ) {
				$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-progress-circle{';
				$css .= 'font-size: ' . esc_attr( $datetime_value_font_size_mobile ) . 'px;';
				$css .= '}';
			}
			if ( $datetime_unit_font_size_mobile !== '' ) {
				$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-shortcode-countdown-text{font-size:' . esc_attr( $datetime_unit_font_size_mobile ) . 'px;}';
			}
			$css .= '}';
		} else {
			$css1 = '';
			if ( $countdown_timer_item_height ) {
				$css1 .= 'height:' . esc_attr( $countdown_timer_item_height ) . 'px;';
			}
			if ( $countdown_timer_item_width ) {
				$css1 .= 'width:' . esc_attr( $countdown_timer_item_width ) . 'px;';
			}
			if ( $countdown_timer_item_border_radius ) {
				$css1 .= 'border-radius:' . esc_attr( $countdown_timer_item_border_radius ) . 'px;';
			}
			if ( $countdown_timer_item_border_color ) {
				$css1 .= 'border:1px solid ' . esc_attr( $countdown_timer_item_border_color ) . ';';
			}
			//shop resize
            if ( $datetime_value_font_size ) {
	            $css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-shortcode-countdown-value,.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-shortcode-countdown-value-container{';
	            $css .= 'font-size: ' . esc_attr( $datetime_value_font_size ) . 'px;';
	            $css .= '}';
            }
            if ( $datetime_unit_font_size ) {
	            $css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-shortcode-countdown-text{font-size:' . esc_attr( $datetime_unit_font_size ) . 'px;}';
            }

			/*mobile*/
			$mobile_css = '@media screen and (max-width:600px){';
			if ( $countdown_timer_padding_mobile !== '' ) {
				$mobile_css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-1{padding:' . esc_attr( $countdown_timer_padding_mobile ) . 'px;}';
			}
			if ( $datetime_value_font_size_mobile !== '' ) {
				$mobile_css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-shortcode-countdown-value,.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-shortcode-countdown-value-container{';
				$mobile_css .= 'font-size: ' . esc_attr( $datetime_value_font_size_mobile ) . 'px;';
				$mobile_css .= '}';
			}
			if ( $datetime_unit_font_size_mobile !== '' ) {
				$mobile_css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-1 .woo-sctr-shortcode-countdown-text{font-size:' . esc_attr( $datetime_unit_font_size_mobile ) . 'px;}';
			}

			if ( $css1 ) {
				if ( $display_type == 1 ) {
					$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-wrap.woo-sctr-shortcode-countdown-style-1 .woo-sctr-shortcode-countdown-unit{' . $css1 . '}';
					if ( $countdown_timer_item_height_mobile !== '' ) {
						$mobile_css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-wrap.woo-sctr-shortcode-countdown-style-1 .woo-sctr-shortcode-countdown-unit{height:' . esc_attr( $countdown_timer_item_height_mobile ) . 'px;}';
					}
					if ( $countdown_timer_item_width_mobile !== '' ) {
						$mobile_css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-wrap.woo-sctr-shortcode-countdown-style-1 .woo-sctr-shortcode-countdown-unit{width:' . esc_attr( $countdown_timer_item_width_mobile ) . 'px;}';
					}
				} elseif ( $display_type == 2 ) {
					if ( $animation_style == 'default' ) {
						$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-wrap.woo-sctr-shortcode-countdown-style-2 .woo-sctr-shortcode-countdown-value{' . $css1 . '}';
						if ( $countdown_timer_item_height_mobile !== '' ) {
							$mobile_css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-wrap.woo-sctr-shortcode-countdown-style-2 .woo-sctr-shortcode-countdown-value{height:' . esc_attr( $countdown_timer_item_height_mobile ) . 'px;}';
						}
						if ( $countdown_timer_item_width_mobile !== '' ) {
							$mobile_css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-wrap.woo-sctr-shortcode-countdown-style-2 .woo-sctr-shortcode-countdown-value{width:' . esc_attr( $countdown_timer_item_width_mobile ) . 'px;}';
						}
					} else {
						$css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-wrap.woo-sctr-shortcode-countdown-style-2 .woo-sctr-shortcode-countdown-value-container{' . $css1 . '}';
						if ( $countdown_timer_item_height_mobile !== '' ) {
							$mobile_css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-wrap.woo-sctr-shortcode-countdown-style-2 .woo-sctr-shortcode-countdown-value-container{height:' . esc_attr( $countdown_timer_item_height_mobile ) . 'px;}';
						}
						if ( $countdown_timer_item_width_mobile !== '' ) {
							$mobile_css .= '.woo-sctr-shortcode-wrap-wrap.woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id . ' .woo-sctr-shortcode-countdown-wrap.woo-sctr-shortcode-countdown-style-2 .woo-sctr-shortcode-countdown-value-container{width:' . esc_attr( $countdown_timer_item_width_mobile ) . 'px;}';
						}
					}
				}
			}
			$mobile_css .= '}';

			$css .= $mobile_css;
		}
		$css .= $shop_css;
		wp_add_inline_style( 'woo-sctr-shortcode-style', $css );
		/*message*/
		$text = explode( '{countdown_timer}', $text );
		if ( count( $text ) >= 2 ) {
			$sale_text_before = $text[0];
			$sale_text_after  = $text[1];
		} else {
			return '';
		}
		$design_class = ' woo-sctr-shortcode-countdown-style-' . $display_type;
		switch ( $time_separator ) {
			case 'colon':
				$time_separator = ':';
				break;
			case 'comma':
				$time_separator = ',';
				break;
			case 'dot':
				$time_separator = '.';
				break;
			default:
				$time_separator = '';
		}
		$wrap_class = array(
			'woo-sctr-shortcode-wrap-wrap',
			'woo-sctr-shortcode-wrap-wrap-' . $sale_countdown_timer_id,
			'woo-sctr-shortcode-wrap-wrap-' . $id,
		);
		if ( $display_type == 3 ) {
			$wrap_class[] = 'woo-sctr-shortcode-wrap-wrap-inline';
		} elseif ( $display_type == 4 ) {
			$wrap_class[] = 'woo-sctr-shortcode-wrap-wrap-circle';
		}
		$wrap_class         = trim( implode( ' ', $wrap_class ) );
		$countdown_time_end = $now + $end_time + 1 - ( get_option( 'gmt_offset' ) * 3600 );
		ob_start();
		if ( $datetime_unit_position == 'bottom' ) {
			if ( $animation_style == 'default' ) {
				switch ( $display_type ) {
					case '1':
					case '2':
						?>
                        <div class="<?php echo esc_attr( $wrap_class ); ?>">
                            <div class="woo-sctr-shortcode-wrap">
                                <input type="hidden" class="woo-sctr-shortcode-data-end_time"
                                       data-countdown_time_start="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $now ) ); ?>"
                                       data-countdown_time_end="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $countdown_time_end ) ); ?>"
                                       value="<?php echo esc_attr( $end_time ); ?>">
                                <div class="woo-sctr-shortcode-countdown-wrap <?php echo esc_attr( $design_class ); ?>">
                                    <div class="woo-sctr-shortcode-countdown">
                                        <div class="woo-sctr-shortcode-countdown-1">
                                            <span class="woo-sctr-shortcode-countdown-text-before"><?php echo wp_kses_post( $sale_text_before ); ?></span>
                                            <div class="woo-sctr-shortcode-countdown-2">
                                        <span class="woo-sctr-shortcode-countdown-unit-wrap"
                                              style="<?php if ( ! $day_left && $hide_zero_items ) {
	                                              echo 'display:none';
                                              } ?>">
                                            <span class="woo-sctr-shortcode-countdown-date woo-sctr-shortcode-countdown-unit">
                                                <span class="woo-sctr-shortcode-countdown-date-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $day_left_t ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-date-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $day ); ?></span>
                                            </span>
                                        </span>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"
                                                      style="<?php if ( ! $day_left && $hide_zero_items ) {
													      echo 'display:none';
												      } ?>"><?php echo esc_html( $time_separator ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                            <span class="woo-sctr-shortcode-countdown-hour woo-sctr-shortcode-countdown-unit">
                                                <span class="woo-sctr-shortcode-countdown-hour-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $hour_left_t ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-hour-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $hour ); ?></span>
                                            </span>
                                        </span>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                            <span class="woo-sctr-shortcode-countdown-minute woo-sctr-shortcode-countdown-unit">
                                                <span class="woo-sctr-shortcode-countdown-minute-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $min_left_t ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-minute-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $minute ); ?></span>
                                            </span>
                                        </span>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                            <span class="woo-sctr-shortcode-countdown-second woo-sctr-shortcode-countdown-unit">
                                                <span class="woo-sctr-shortcode-countdown-second-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $sec_left_t ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-second-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $second ); ?></span>
                                            </span>
                                        </span>
                                            </div>
                                            <span class="woo-sctr-shortcode-countdown-text-bottom"><?php echo wp_kses_post( $sale_text_after ); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
						<?php
						break;
					case '3':
						?>
                        <div class="<?php echo esc_attr( $wrap_class ); ?>">
                            <input type="hidden" class="woo-sctr-shortcode-data-end_time"
                                   data-countdown_time_start="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $now ) ); ?>"
                                   data-countdown_time_end="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $countdown_time_end ) ); ?>"
                                   value="<?php echo esc_attr( $end_time ); ?>">
                            <span class="woo-sctr-shortcode-countdown-text-before"><?php echo wp_kses_post( $sale_text_before ); ?></span>
                            <span class="woo-sctr-shortcode-countdown-1">
                <span class="woo-sctr-shortcode-countdown-unit-wrap"
                      style="<?php if ( ! $day_left && $hide_zero_items ) {
	                      echo 'display:none';
                      } ?>">
                                <span class="woo-sctr-shortcode-countdown-date woo-sctr-shortcode-countdown-unit">
                                    <span class="woo-sctr-shortcode-countdown-date-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $day_left_t ); ?></span>
                                    <span class="woo-sctr-shortcode-countdown-date-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $day ); ?></span>
                                </span>
                </span>
                <span class="woo-sctr-shortcode-countdown-time-separator"
                      style="<?php if ( ! $day_left && $hide_zero_items ) {
	                      echo 'display:none';
                      } ?>"><?php echo esc_html( $time_separator ); ?></span>
                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                <span class="woo-sctr-shortcode-countdown-hour woo-sctr-shortcode-countdown-unit">
                                    <span class="woo-sctr-shortcode-countdown-hour-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $hour_left_t ); ?></span>
                                    <span class="woo-sctr-shortcode-countdown-hour-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $hour ); ?></span>
                                </span>
                </span>
                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                <span class="woo-sctr-shortcode-countdown-minute woo-sctr-shortcode-countdown-unit">
                                    <span class="woo-sctr-shortcode-countdown-minute-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $min_left_t ); ?></span>
                                    <span class="woo-sctr-shortcode-countdown-minute-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $minute ); ?></span>
                                </span>
                                </span>
                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                <span class="woo-sctr-shortcode-countdown-second woo-sctr-shortcode-countdown-unit">
                                    <span class="woo-sctr-shortcode-countdown-second-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $sec_left_t ); ?></span>
                                    <span class="woo-sctr-shortcode-countdown-second-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $second ); ?></span>
                                </span>

                                    </span>
                                    </span>
                            <span class="woo-sctr-shortcode-countdown-text-after"><?php echo wp_kses_post( $sale_text_after ); ?></span>
                        </div>

						<?php
						break;
					case '4':
						?>
                        <div class="<?php echo esc_attr( $wrap_class ); ?>">
                            <div class="woo-sctr-shortcode-wrap">
                                <input type="hidden" class="woo-sctr-shortcode-data-end_time"
                                       data-countdown_time_start="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $now ) ); ?>"
                                       data-countdown_time_end="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $countdown_time_end ) ); ?>"
                                       value="<?php echo esc_attr( $end_time ); ?>">
                                <div class="woo-sctr-shortcode-countdown-wrap <?php echo esc_attr( $design_class ); ?>">
                                    <div class="woo-sctr-shortcode-countdown">
                                        <div class="woo-sctr-shortcode-countdown-1">
                                            <span class="woo-sctr-shortcode-countdown-text-before"><?php echo wp_kses_post( $sale_text_before ); ?></span>
                                            <div class="woo-sctr-shortcode-countdown-2">
                                    <span class="woo-sctr-shortcode-countdown-unit-wrap"
                                          style="<?php if ( ! $day_left && $hide_zero_items ) {
	                                          echo 'display:none';
                                          } ?>">
                                        <span class="woo-sctr-shortcode-countdown-date woo-sctr-shortcode-countdown-unit">
                                            <div class="woo-sctr-progress-circle<?php echo $day_deg <= 180 ? '' : ' woo-sctr-over50' ?>">
                                                <span class="woo-sctr-shortcode-countdown-date-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $day_left_t ); ?></span>
                                                <div class="woo-sctr-left-half-clipper">
                                                    <div class="woo-sctr-first50-bar"<?php echo $day_deg <= 180 ? 'style="display:none"' : '' ?>></div>
                                                    <div class="woo-sctr-value-bar"
                                                         data-deg="<?php echo esc_attr( $day_deg ); ?>"></div>
                                                </div>
                                            </div>
                                            <span class="woo-sctr-shortcode-countdown-date-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $day ); ?></span>
                                        </span>
                                    </span>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"
                                                      style="<?php if ( ! $day_left && $hide_zero_items ) {
													      echo 'display:none';
												      } ?>"><?php echo esc_html( $time_separator ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                            <span class="woo-sctr-shortcode-countdown-hour woo-sctr-shortcode-countdown-unit">
                                                <div class="woo-sctr-progress-circle<?php echo $hour_deg <= 180 ? '' : ' woo-sctr-over50' ?>">
                                                    <span class="woo-sctr-shortcode-countdown-hour-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $hour_left_t ); ?></span>

                                                    <div class="woo-sctr-left-half-clipper">
                                                        <div class="woo-sctr-first50-bar"<?php echo $hour_deg <= 180 ? 'style="display:none"' : '' ?>></div>
                                                        <div class="woo-sctr-value-bar"
                                                             data-deg="<?php echo esc_attr( $hour_deg ); ?>"></div>
                                                    </div>
                                                </div>
                                                <span class="woo-sctr-shortcode-countdown-hour-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $hour ); ?></span>
                                            </span>
                                    </span>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                        <span class="woo-sctr-shortcode-countdown-minute woo-sctr-shortcode-countdown-unit">
                                            <div class="woo-sctr-progress-circle<?php echo $min_deg <= 180 ? '' : ' woo-sctr-over50' ?>">
                                                <span class="woo-sctr-shortcode-countdown-minute-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $min_left_t ); ?></span>

                                                <div class="woo-sctr-left-half-clipper">
                                                    <div class="woo-sctr-first50-bar"<?php echo $min_deg <= 180 ? 'style="display:none"' : '' ?>></div>
                                                    <div class="woo-sctr-value-bar"
                                                         data-deg="<?php echo esc_attr( $min_deg ); ?>"></div>
                                                </div>
                                            </div>
                                            <span class="woo-sctr-shortcode-countdown-minute-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $minute ); ?></span>
                                        </span>
                                    </span>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                        <span class="woo-sctr-shortcode-countdown-second woo-sctr-shortcode-countdown-unit">
                                            <div class="woo-sctr-progress-circle<?php echo $sec_deg <= 180 ? '' : ' woo-sctr-over50' ?>">
                                                <span class="woo-sctr-shortcode-countdown-second-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $sec_left_t ); ?></span>
                                                <div class="woo-sctr-left-half-clipper">
                                                    <div class="woo-sctr-first50-bar"<?php echo $sec_deg <= 180 ? 'style="display:none"' : '' ?>></div>
                                                    <div class="woo-sctr-value-bar"
                                                         data-deg="<?php echo esc_attr( $sec_deg ); ?>"></div>
                                                </div>
                                            </div>
                                            <span class="woo-sctr-shortcode-countdown-second-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $second ); ?></span>
                                        </span>
                                    </span>
                                            </div>
                                            <span class="woo-sctr-shortcode-countdown-text-bottom"><?php echo wp_kses_post( $sale_text_after ); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
						<?php
						break;
					default:
				}
			} else {
				switch ( $display_type ) {
					case '1':
					case '2':
						?>
                        <div class="<?php echo esc_attr( $wrap_class ); ?>">
                            <div class="woo-sctr-shortcode-wrap">
                                <input type="hidden" class="woo-sctr-shortcode-data-end_time"
                                       data-countdown_time_start="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $now ) ); ?>"
                                       data-countdown_time_end="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $countdown_time_end ) ); ?>"
                                       value="<?php echo esc_attr( $end_time ); ?>">
                                <div class="woo-sctr-shortcode-countdown-wrap <?php echo esc_attr( $design_class ); ?>">
                                    <div class="woo-sctr-shortcode-countdown">
                                        <div class="woo-sctr-shortcode-countdown-1">
                                            <span class="woo-sctr-shortcode-countdown-text-before"><?php echo wp_kses_post( $sale_text_before ); ?></span>
                                            <div class="woo-sctr-shortcode-countdown-2">
                                                <span class="woo-sctr-shortcode-countdown-unit-wrap"
                                                      style="<?php if ( ! $day_left && $hide_zero_items ) {
	                                                      echo 'display:none';
                                                      } ?>">
                                                    <span class="woo-sctr-shortcode-countdown-date woo-sctr-shortcode-countdown-unit">
                                                        <span class="woo-sctr-shortcode-countdown-date-value-container woo-sctr-shortcode-countdown-value-container">
                                                            <span class="woo-sctr-shortcode-countdown-date-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                                <span class="woo-sctr-shortcode-countdown-date-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                                    <span class="woo-sctr-shortcode-countdown-date-value-1 woo-sctr-shortcode-countdown-value1"><?php echo $day_left > 0 ? esc_html( zeroise( $day_left - 1, 2 ) ) : '00'; ?></span>
                                                                    <span class="woo-sctr-shortcode-countdown-date-value-2 woo-sctr-shortcode-countdown-value2"><?php echo esc_html( $day_left_t ); ?></span>
                                                                </span>
                                                            </span>
                                                        </span>
                                                        <span class="woo-sctr-shortcode-countdown-date-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $day ); ?></span>
                                                    </span>
                                                </span>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"
                                                      style="<?php if ( ! $day_left && $hide_zero_items ) {
													      echo 'display:none';
												      } ?>"><?php echo esc_html( $time_separator ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                                    <span class="woo-sctr-shortcode-countdown-hour woo-sctr-shortcode-countdown-unit">
                                                        <span class="woo-sctr-shortcode-countdown-hour-value-container woo-sctr-shortcode-countdown-value-container">
                                                            <span class="woo-sctr-shortcode-countdown-hour-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                                <span class="woo-sctr-shortcode-countdown-hour-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                                    <span class="woo-sctr-shortcode-countdown-hour-value-1 woo-sctr-shortcode-countdown-value1"><?php echo $hour_left > 0 ? esc_html( zeroise( $hour_left - 1, 2 ) ) : '23'; ?></span>
                                                                    <span class="woo-sctr-shortcode-countdown-hour-value-2 woo-sctr-shortcode-countdown-value2"><?php echo esc_html( $hour_left_t ); ?></span>
                                                                </span>
                                                            </span>
                                                        </span>
                                                        <span class="woo-sctr-shortcode-countdown-hour-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $hour ); ?></span>
                                                    </span>
                                                </span>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                                    <span class="woo-sctr-shortcode-countdown-minute woo-sctr-shortcode-countdown-unit">
                                                        <span class="woo-sctr-shortcode-countdown-minute-value-container woo-sctr-shortcode-countdown-value-container">
                                                            <span class="woo-sctr-shortcode-countdown-minute-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                                <span class="woo-sctr-shortcode-countdown-minute-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                                    <span class="woo-sctr-shortcode-countdown-minute-value-1 woo-sctr-shortcode-countdown-value1"><?php echo $min_left > 0 ? esc_html( zeroise( $min_left - 1, 2 ) ) : '59'; ?></span>
                                                                    <span class="woo-sctr-shortcode-countdown-minute-value-2 woo-sctr-shortcode-countdown-value2"><?php echo esc_html( $min_left_t ); ?></span>
                                                                </span>
                                                            </span>
                                                        </span>
                                                        <span class="woo-sctr-shortcode-countdown-minute-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $minute ); ?></span>
                                                    </span>
                                                </span>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                                    <span class="woo-sctr-shortcode-countdown-second woo-sctr-shortcode-countdown-unit">
                                                        <span class="woo-sctr-shortcode-countdown-second-value-container woo-sctr-shortcode-countdown-value-container">
                                                            <span class="woo-sctr-shortcode-countdown-second-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                                <span class="woo-sctr-shortcode-countdown-second-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                                    <span class="woo-sctr-shortcode-countdown-second-value-1 woo-sctr-shortcode-countdown-value1"><?php echo esc_html( $sec_left_t ); ?></span>
                                                                    <span class="woo-sctr-shortcode-countdown-second-value-2 woo-sctr-shortcode-countdown-value2"><?php echo $sec_left < 60 ? esc_html( zeroise( $sec_left + 1, 2 ) ) : '00'; ?></span>
                                                                </span>
                                                            </span>
                                                        </span>
                                                        <span class="woo-sctr-shortcode-countdown-second-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $second ); ?></span>
                                                    </span>
                                                </span>
                                            </div>
                                            <span class="woo-sctr-shortcode-countdown-text-bottom"><?php echo wp_kses_post( $sale_text_after ); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
						<?php
						break;
					case '3':
						?>
                        <div class="<?php echo esc_attr( $wrap_class ); ?>">
                            <input type="hidden" class="woo-sctr-shortcode-data-end_time"
                                   data-countdown_time_start="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $now ) ); ?>"
                                   data-countdown_time_end="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $countdown_time_end ) ); ?>"
                                   value="<?php echo esc_attr( $end_time ); ?>">
                            <span class="woo-sctr-shortcode-countdown-text-before"><?php echo esc_html( $sale_text_before ); ?></span>
                            <span class="woo-sctr-shortcode-countdown-1">
                                <span class="woo-sctr-shortcode-countdown-unit-wrap"
                                      style="<?php if ( ! $day_left && $hide_zero_items ) {
	                                      echo 'display:none';
                                      } ?>">
                                    <span class="woo-sctr-shortcode-countdown-date woo-sctr-shortcode-countdown-unit">
                                        <span class="woo-sctr-shortcode-countdown-date-value-container woo-sctr-shortcode-countdown-value-container">
                                            <span class="woo-sctr-shortcode-countdown-date-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                <span class="woo-sctr-shortcode-countdown-date-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                    <span class="woo-sctr-shortcode-countdown-date-value-1 woo-sctr-shortcode-countdown-value1"><?php echo $day_left > 0 ? esc_html( zeroise( $day_left - 1, 2 ) ) : '00'; ?></span>
                                                    <span class="woo-sctr-shortcode-countdown-date-value-2 woo-sctr-shortcode-countdown-value2"><?php echo esc_html( $day_left_t ); ?></span>
                                                </span>
                                            </span>
                                        </span>
                                        <span class="woo-sctr-shortcode-countdown-date-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $day ); ?></span>
                                    </span>
                                </span>
                                <span class="woo-sctr-shortcode-countdown-time-separator"
                                      style="<?php if ( ! $day_left && $hide_zero_items ) {
	                                      echo 'display:none';
                                      } ?>"><?php echo esc_html( $time_separator ); ?></span>
                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                    <span class="woo-sctr-shortcode-countdown-hour woo-sctr-shortcode-countdown-unit">
                                        <span class="woo-sctr-shortcode-countdown-hour-value-container woo-sctr-shortcode-countdown-value-container">
                                            <span class="woo-sctr-shortcode-countdown-hour-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                <span class="woo-sctr-shortcode-countdown-hour-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                    <span class="woo-sctr-shortcode-countdown-hour-value-1 woo-sctr-shortcode-countdown-value1"><?php echo $hour_left > 0 ? esc_html( zeroise( $hour_left - 1, 2 ) ) : '23'; ?></span>
                                                    <span class="woo-sctr-shortcode-countdown-hour-value-2 woo-sctr-shortcode-countdown-value2"><?php echo esc_html( $hour_left_t ); ?></span>
                                                </span>
                                            </span>
                                        </span>
                                        <span class="woo-sctr-shortcode-countdown-hour-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $hour ); ?></span>
                                    </span>
                                </span>
                                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                    <span class="woo-sctr-shortcode-countdown-minute woo-sctr-shortcode-countdown-unit">
                                        <span class="woo-sctr-shortcode-countdown-minute-value-container woo-sctr-shortcode-countdown-value-container">
                                            <span class="woo-sctr-shortcode-countdown-minute-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                <span class="woo-sctr-shortcode-countdown-minute-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                    <span class="woo-sctr-shortcode-countdown-minute-value-1 woo-sctr-shortcode-countdown-value1"><?php echo $min_left > 0 ? esc_html( zeroise( $min_left - 1, 2 ) ) : '59'; ?></span>
                                                    <span class="woo-sctr-shortcode-countdown-minute-value-2 woo-sctr-shortcode-countdown-value2"><?php echo esc_html( $min_left_t ); ?></span>
                                                </span>
                                            </span>
                                        </span>
                                        <span class="woo-sctr-shortcode-countdown-minute-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $minute ); ?></span>
                                    </span>
                                </span>
                                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                    <span class="woo-sctr-shortcode-countdown-second woo-sctr-shortcode-countdown-unit">
                                        <span class="woo-sctr-shortcode-countdown-second-value-container woo-sctr-shortcode-countdown-value-container">
                                            <span class="woo-sctr-shortcode-countdown-second-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                <span class="woo-sctr-shortcode-countdown-second-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                    <span class="woo-sctr-shortcode-countdown-second-value-1 woo-sctr-shortcode-countdown-value1"><?php echo esc_html( $sec_left_t ); ?></span>
                                                    <span class="woo-sctr-shortcode-countdown-second-value-2 woo-sctr-shortcode-countdown-value2"><?php echo $sec_left < 60 ? esc_html( zeroise( $sec_left + 1, 2 ) ) : '00'; ?></span>
                                                </span>
                                            </span>
                                        </span>
                                        <span class="woo-sctr-shortcode-countdown-second-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $second ); ?></span>
                                    </span>
                                </span>
                            </span>
                            <span class="woo-sctr-shortcode-countdown-text-after"><?php echo wp_kses_post( $sale_text_after ); ?></span>
                        </div>

						<?php
						break;
					case '4':
						?>
                        <div class="<?php echo esc_attr( $wrap_class ); ?>">
                            <div class="woo-sctr-shortcode-wrap">
                                <input type="hidden" class="woo-sctr-shortcode-data-end_time"
                                       data-countdown_time_start="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $now ) ); ?>"
                                       data-countdown_time_end="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $countdown_time_end ) ); ?>"
                                       value="<?php echo esc_attr( $end_time ); ?>">
                                <div class="woo-sctr-shortcode-countdown-wrap <?php echo esc_attr( $design_class ); ?>">
                                    <div class="woo-sctr-shortcode-countdown">
                                        <div class="woo-sctr-shortcode-countdown-1">
                                            <span class="woo-sctr-shortcode-countdown-text-before"><?php echo wp_kses_post( $sale_text_before ); ?></span>
                                            <div class="woo-sctr-shortcode-countdown-2">
                                                <div class="woo-sctr-shortcode-countdown-unit-wrap"
                                                     style="<?php if ( ! $day_left && $hide_zero_items ) {
													     echo 'display:none';
												     } ?>">
                                                    <div class="woo-sctr-shortcode-countdown-date woo-sctr-shortcode-countdown-unit">
                                                        <div class="woo-sctr-progress-circle<?php echo $day_deg <= 180 ? '' : ' woo-sctr-over50' ?>">
                                                            <div class="woo-sctr-shortcode-countdown-date-value-container woo-sctr-shortcode-countdown-value-container">
                                                                <div class="woo-sctr-shortcode-countdown-date-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                                    <div class="woo-sctr-shortcode-countdown-date-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                                        <span class="woo-sctr-shortcode-countdown-date-value-1 woo-sctr-shortcode-countdown-value1"><?php echo $day_left > 0 ? esc_html( zeroise( $day_left - 1, 2 ) ) : '00'; ?></span>
                                                                        <span class="woo-sctr-shortcode-countdown-date-value-2 woo-sctr-shortcode-countdown-value2"><?php echo esc_html( $day_left_t ); ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="woo-sctr-left-half-clipper">
                                                                <div class="woo-sctr-first50-bar"<?php echo $day_deg <= 180 ? 'style="display:none"' : '' ?>></div>
                                                                <div class="woo-sctr-value-bar"
                                                                     data-deg="<?php echo esc_attr( $day_deg ); ?>"></div>
                                                            </div>
                                                        </div>
                                                        <span class="woo-sctr-shortcode-countdown-date-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $day ); ?></span>
                                                    </div>
                                                </div>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"
                                                      style="<?php if ( ! $day_left && $hide_zero_items ) {
													      echo 'display:none';
												      } ?>"><?php echo esc_html( $time_separator ); ?></span>
                                                <div class="woo-sctr-shortcode-countdown-unit-wrap">
                                                    <div class="woo-sctr-shortcode-countdown-hour woo-sctr-shortcode-countdown-unit">
                                                        <div class="woo-sctr-progress-circle<?php echo $hour_deg <= 180 ? '' : ' woo-sctr-over50' ?>">
                                                            <div class="woo-sctr-shortcode-countdown-hour-value-container woo-sctr-shortcode-countdown-value-container">
                                                                <div class="woo-sctr-shortcode-countdown-hour-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                                    <div class="woo-sctr-shortcode-countdown-hour-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                                        <span class="woo-sctr-shortcode-countdown-hour-value-1 woo-sctr-shortcode-countdown-value1"><?php echo $hour_left > 0 ? esc_html( zeroise( $hour_left - 1, 2 ) ) : '23'; ?></span>
                                                                        <span class="woo-sctr-shortcode-countdown-hour-value-2 woo-sctr-shortcode-countdown-value2"><?php echo esc_html( $hour_left_t ); ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="woo-sctr-left-half-clipper">
                                                                <div class="woo-sctr-first50-bar"<?php echo $hour_deg <= 180 ? 'style="display:none"' : '' ?>></div>
                                                                <div class="woo-sctr-value-bar"
                                                                     data-deg="<?php echo esc_attr( $hour_deg ); ?>"></div>
                                                            </div>
                                                        </div>
                                                        <span class="woo-sctr-shortcode-countdown-hour-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $hour ); ?></span>
                                                    </div>
                                                </div>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                                                <div class="woo-sctr-shortcode-countdown-unit-wrap">
                                                    <div class="woo-sctr-shortcode-countdown-minute woo-sctr-shortcode-countdown-unit">
                                                        <div class="woo-sctr-progress-circle<?php echo $min_deg <= 180 ? '' : ' woo-sctr-over50' ?>">
                                                            <div class="woo-sctr-shortcode-countdown-minute-value-container woo-sctr-shortcode-countdown-value-container">
                                                                <div class="woo-sctr-shortcode-countdown-minute-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                                    <div class="woo-sctr-shortcode-countdown-minute-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                                        <span class="woo-sctr-shortcode-countdown-minute-value-1 woo-sctr-shortcode-countdown-value1"><?php echo $min_left > 0 ? esc_html( zeroise( $min_left - 1, 2 ) ) : '59'; ?></span>
                                                                        <span class="woo-sctr-shortcode-countdown-minute-value-2 woo-sctr-shortcode-countdown-value2"><?php echo esc_html( $min_left_t ); ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="woo-sctr-left-half-clipper">
                                                                <div class="woo-sctr-first50-bar"<?php echo $min_deg <= 180 ? 'style="display:none"' : '' ?>></div>
                                                                <div class="woo-sctr-value-bar"
                                                                     data-deg="<?php echo esc_attr( $min_deg ); ?>"></div>
                                                            </div>
                                                        </div>
                                                        <span class="woo-sctr-shortcode-countdown-minute-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $minute ); ?></span>
                                                    </div>
                                                </div>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                                                <div class="woo-sctr-shortcode-countdown-unit-wrap">
                                                    <div class="woo-sctr-shortcode-countdown-second woo-sctr-shortcode-countdown-unit">
                                                        <div class="woo-sctr-progress-circle<?php echo $sec_deg <= 180 ? '' : ' woo-sctr-over50' ?>">
                                                            <div class="woo-sctr-shortcode-countdown-second-value-container woo-sctr-shortcode-countdown-value-container">
                                                                <div class="woo-sctr-shortcode-countdown-second-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                                    <div class="woo-sctr-shortcode-countdown-second-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                                        <span class="woo-sctr-shortcode-countdown-second-value-1 woo-sctr-shortcode-countdown-value1"><?php echo esc_html( $sec_left_t ); ?></span>
                                                                        <span class="woo-sctr-shortcode-countdown-second-value-2 woo-sctr-shortcode-countdown-value2"><?php echo $sec_left < 60 ? esc_html( zeroise( $sec_left + 1, 2 ) ) : '00'; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="woo-sctr-left-half-clipper">
                                                                <div class="woo-sctr-first50-bar"<?php echo $sec_deg <= 180 ? 'style="display:none"' : '' ?>></div>
                                                                <div class="woo-sctr-value-bar"
                                                                     data-deg="<?php echo esc_attr( $sec_deg ); ?>"></div>
                                                            </div>
                                                        </div>
                                                        <span class="woo-sctr-shortcode-countdown-second-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $second ); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="woo-sctr-shortcode-countdown-text-bottom"><?php echo wp_kses_post( $sale_text_after ); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
						<?php
						break;
					default:
				}
			}
		} else {
			if ( $animation_style == 'default' ) {
				switch ( $display_type ) {
					case '1':
					case '2':
						?>
                        <div class="<?php echo esc_attr( $wrap_class ); ?>">
                            <div class="woo-sctr-shortcode-wrap">
                                <input type="hidden" class="woo-sctr-shortcode-data-end_time"
                                       data-countdown_time_start="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $now ) ); ?>"
                                       data-countdown_time_end="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $countdown_time_end ) ); ?>"
                                       value="<?php echo esc_attr( $end_time ); ?>">
                                <div class="woo-sctr-shortcode-countdown-wrap <?php echo esc_attr( $design_class ); ?>">
                                    <div class="woo-sctr-shortcode-countdown">
                                        <div class="woo-sctr-shortcode-countdown-1">
                                            <span class="woo-sctr-shortcode-countdown-text-before"><?php echo wp_kses_post( $sale_text_before ); ?></span>
                                            <div class="woo-sctr-shortcode-countdown-2">
                                        <span class="woo-sctr-shortcode-countdown-unit-wrap"
                                              style="<?php if ( ! $day_left && $hide_zero_items ) {
	                                              echo 'display:none';
                                              } ?>">
                                            <span class="woo-sctr-shortcode-countdown-date woo-sctr-shortcode-countdown-unit">
                                                <span class="woo-sctr-shortcode-countdown-date-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $day ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-date-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $day_left_t ); ?></span>
                                            </span>
                                        </span>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"
                                                      style="<?php if ( ! $day_left && $hide_zero_items ) {
													      echo 'display:none';
												      } ?>"><?php echo esc_html( $time_separator ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                            <span class="woo-sctr-shortcode-countdown-hour woo-sctr-shortcode-countdown-unit">
                                                <span class="woo-sctr-shortcode-countdown-hour-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $hour ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-hour-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $hour_left_t ); ?></span>
                                            </span>
                                        </span>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                            <span class="woo-sctr-shortcode-countdown-minute woo-sctr-shortcode-countdown-unit">
                                                <span class="woo-sctr-shortcode-countdown-minute-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $minute ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-minute-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $min_left_t ); ?></span>
                                            </span>
                                        </span>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                            <span class="woo-sctr-shortcode-countdown-second woo-sctr-shortcode-countdown-unit">
                                                <span class="woo-sctr-shortcode-countdown-second-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $second ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-second-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $sec_left_t ); ?></span>
                                            </span>
                                        </span>
                                            </div>
                                            <span class="woo-sctr-shortcode-countdown-text-bottom"><?php echo wp_kses_post( $sale_text_after ); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
						<?php
						break;
					case '3':
						?>
                        <div class="<?php echo esc_attr( $wrap_class ); ?>">
                            <input type="hidden" class="woo-sctr-shortcode-data-end_time"
                                   data-countdown_time_start="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $now ) ); ?>"
                                   data-countdown_time_end="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $countdown_time_end ) ); ?>"
                                   value="<?php echo esc_attr( $end_time ); ?>">
                            <span class="woo-sctr-shortcode-countdown-text-before"><?php echo wp_kses_post( $sale_text_before ); ?></span>
                            <span class="woo-sctr-shortcode-countdown-1">
                <span class="woo-sctr-shortcode-countdown-unit-wrap"
                      style="<?php if ( ! $day_left && $hide_zero_items ) {
	                      echo 'display:none';
                      } ?>">
                                <span class="woo-sctr-shortcode-countdown-date woo-sctr-shortcode-countdown-unit">
                                    <span class="woo-sctr-shortcode-countdown-date-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $day_left_t ); ?></span>
                                    <span class="woo-sctr-shortcode-countdown-date-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $day ); ?></span>
                                </span>
                </span>
                <span class="woo-sctr-shortcode-countdown-time-separator"
                      style="<?php if ( ! $day_left && $hide_zero_items ) {
	                      echo 'display:none';
                      } ?>"><?php echo esc_html( $time_separator ); ?></span>
                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                <span class="woo-sctr-shortcode-countdown-hour woo-sctr-shortcode-countdown-unit">
                                    <span class="woo-sctr-shortcode-countdown-hour-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $hour_left_t ); ?></span>
                                    <span class="woo-sctr-shortcode-countdown-hour-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $hour ); ?></span>
                                </span>
                </span>
                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                <span class="woo-sctr-shortcode-countdown-minute woo-sctr-shortcode-countdown-unit">
                                    <span class="woo-sctr-shortcode-countdown-minute-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $min_left_t ); ?></span>
                                    <span class="woo-sctr-shortcode-countdown-minute-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $minute ); ?></span>
                                </span>
                                </span>
                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                <span class="woo-sctr-shortcode-countdown-second woo-sctr-shortcode-countdown-unit">
                                    <span class="woo-sctr-shortcode-countdown-second-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $sec_left_t ); ?></span>
                                    <span class="woo-sctr-shortcode-countdown-second-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $second ); ?></span>
                                </span>

                                    </span>
                                    </span>
                            <span class="woo-sctr-shortcode-countdown-text-after"><?php echo wp_kses_post( $sale_text_after ); ?></span>
                        </div>

						<?php
						break;
					case '4':
						?>
                        <div class="<?php echo esc_attr( $wrap_class ); ?>">
                            <div class="woo-sctr-shortcode-wrap">
                                <input type="hidden" class="woo-sctr-shortcode-data-end_time"
                                       data-countdown_time_start="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $now ) ); ?>"
                                       data-countdown_time_end="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $countdown_time_end ) ); ?>"
                                       value="<?php echo esc_attr( $end_time ); ?>">
                                <div class="woo-sctr-shortcode-countdown-wrap <?php echo esc_attr( $design_class ); ?>">
                                    <div class="woo-sctr-shortcode-countdown">
                                        <div class="woo-sctr-shortcode-countdown-1">
                                            <span class="woo-sctr-shortcode-countdown-text-before"><?php echo wp_kses_post( $sale_text_before ); ?></span>
                                            <div class="woo-sctr-shortcode-countdown-2">
                                    <span class="woo-sctr-shortcode-countdown-unit-wrap"
                                          style="<?php if ( ! $day_left && $hide_zero_items ) {
	                                          echo 'display:none';
                                          } ?>">
                                        <span class="woo-sctr-shortcode-countdown-date woo-sctr-shortcode-countdown-unit">
                                            <span class="woo-sctr-shortcode-countdown-date-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $day ); ?></span>
                                            <div class="woo-sctr-progress-circle<?php echo $day_deg <= 180 ? '' : ' woo-sctr-over50' ?>">
                                                <span class="woo-sctr-shortcode-countdown-date-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $day_left_t ); ?></span>
                                                <div class="woo-sctr-left-half-clipper">
                                                    <div class="woo-sctr-first50-bar"<?php echo $day_deg <= 180 ? 'style="display:none"' : '' ?>></div>
                                                    <div class="woo-sctr-value-bar"
                                                         data-deg="<?php echo esc_attr( $day_deg ); ?>"></div>
                                                </div>
                                            </div>
                                        </span>
                                    </span>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"
                                                      style="<?php if ( ! $day_left && $hide_zero_items ) {
													      echo 'display:none';
												      } ?>"><?php echo esc_html( $time_separator ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                            <span class="woo-sctr-shortcode-countdown-hour woo-sctr-shortcode-countdown-unit">
                                                <span class="woo-sctr-shortcode-countdown-hour-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $hour ); ?></span>
                                                <div class="woo-sctr-progress-circle<?php echo $hour_deg <= 180 ? '' : ' woo-sctr-over50' ?>">
                                                    <span class="woo-sctr-shortcode-countdown-hour-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $hour_left_t ); ?></span>

                                                    <div class="woo-sctr-left-half-clipper">
                                                        <div class="woo-sctr-first50-bar"<?php echo $hour_deg <= 180 ? 'style="display:none"' : '' ?>></div>
                                                        <div class="woo-sctr-value-bar"
                                                             data-deg="<?php echo esc_attr( $hour_deg ); ?>"></div>
                                                    </div>
                                                </div>
                                            </span>
                                    </span>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                        <span class="woo-sctr-shortcode-countdown-minute woo-sctr-shortcode-countdown-unit">
                                            <span class="woo-sctr-shortcode-countdown-minute-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $minute ); ?></span>
                                            <div class="woo-sctr-progress-circle<?php echo $min_deg <= 180 ? '' : ' woo-sctr-over50' ?>">
                                                <span class="woo-sctr-shortcode-countdown-minute-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $min_left_t ); ?></span>

                                                <div class="woo-sctr-left-half-clipper">
                                                    <div class="woo-sctr-first50-bar"<?php echo $min_deg <= 180 ? 'style="display:none"' : '' ?>></div>
                                                    <div class="woo-sctr-value-bar"
                                                         data-deg="<?php echo esc_attr( $min_deg ); ?>"></div>
                                                </div>
                                            </div>
                                        </span>
                                    </span>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                        <span class="woo-sctr-shortcode-countdown-second woo-sctr-shortcode-countdown-unit">
                                            <span class="woo-sctr-shortcode-countdown-second-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $second ); ?></span>
                                            <div class="woo-sctr-progress-circle<?php echo $sec_deg <= 180 ? '' : ' woo-sctr-over50' ?>">
                                                <span class="woo-sctr-shortcode-countdown-second-value woo-sctr-shortcode-countdown-value"><?php echo esc_html( $sec_left_t ); ?></span>
                                                <div class="woo-sctr-left-half-clipper">
                                                    <div class="woo-sctr-first50-bar"<?php echo $sec_deg <= 180 ? 'style="display:none"' : '' ?>></div>
                                                    <div class="woo-sctr-value-bar"
                                                         data-deg="<?php echo esc_attr( $sec_deg ); ?>"></div>
                                                </div>
                                            </div>
                                        </span>
                                    </span>
                                            </div>
                                            <span class="woo-sctr-shortcode-countdown-text-bottom"><?php echo wp_kses_post( $sale_text_after ); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
						<?php
						break;
					default:
				}
			} else {
				switch ( $display_type ) {
					case '1':
					case '2':
						?>
                        <div class="<?php echo esc_attr( $wrap_class ); ?>">
                            <div class="woo-sctr-shortcode-wrap">
                                <input type="hidden" class="woo-sctr-shortcode-data-end_time"
                                       data-countdown_time_start="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $now ) ); ?>"
                                       data-countdown_time_end="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $countdown_time_end ) ); ?>"
                                       value="<?php echo esc_attr( $end_time ); ?>">
                                <div class="woo-sctr-shortcode-countdown-wrap <?php echo esc_attr( $design_class ); ?>">
                                    <div class="woo-sctr-shortcode-countdown">
                                        <div class="woo-sctr-shortcode-countdown-1">
                                            <span class="woo-sctr-shortcode-countdown-text-before"><?php echo wp_kses_post( $sale_text_before ); ?></span>
                                            <div class="woo-sctr-shortcode-countdown-2">
                                                <span class="woo-sctr-shortcode-countdown-unit-wrap"
                                                      style="<?php if ( ! $day_left && $hide_zero_items ) {
	                                                      echo 'display:none';
                                                      } ?>">
                                                    <span class="woo-sctr-shortcode-countdown-date woo-sctr-shortcode-countdown-unit">
                                                        <span class="woo-sctr-shortcode-countdown-date-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $day ); ?></span>
                                                        <span class="woo-sctr-shortcode-countdown-date-value-container woo-sctr-shortcode-countdown-value-container">
                                                            <span class="woo-sctr-shortcode-countdown-date-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                                <span class="woo-sctr-shortcode-countdown-date-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                                    <span class="woo-sctr-shortcode-countdown-date-value-1 woo-sctr-shortcode-countdown-value1"><?php echo $day_left > 0 ? esc_html( zeroise( $day_left - 1, 2 ) ) : '00'; ?></span>
                                                                    <span class="woo-sctr-shortcode-countdown-date-value-2 woo-sctr-shortcode-countdown-value2"><?php echo esc_html( $day_left_t ); ?></span>
                                                                </span>
                                                            </span>
                                                        </span>
                                                    </span>
                                                </span>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"
                                                      style="<?php if ( ! $day_left && $hide_zero_items ) {
													      echo 'display:none';
												      } ?>"><?php echo esc_html( $time_separator ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                                    <span class="woo-sctr-shortcode-countdown-hour woo-sctr-shortcode-countdown-unit">
                                                        <span class="woo-sctr-shortcode-countdown-hour-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $hour ); ?></span>
                                                        <span class="woo-sctr-shortcode-countdown-hour-value-container woo-sctr-shortcode-countdown-value-container">
                                                            <span class="woo-sctr-shortcode-countdown-hour-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                                <span class="woo-sctr-shortcode-countdown-hour-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                                    <span class="woo-sctr-shortcode-countdown-hour-value-1 woo-sctr-shortcode-countdown-value1"><?php echo $hour_left > 0 ? esc_html( zeroise( $hour_left - 1, 2 ) ) : '23'; ?></span>
                                                                    <span class="woo-sctr-shortcode-countdown-hour-value-2 woo-sctr-shortcode-countdown-value2"><?php echo esc_html( $hour_left_t ); ?></span>
                                                                </span>
                                                            </span>
                                                        </span>
                                                    </span>
                                                </span>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                                    <span class="woo-sctr-shortcode-countdown-minute woo-sctr-shortcode-countdown-unit">
                                                        <span class="woo-sctr-shortcode-countdown-minute-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $minute ); ?></span>
                                                        <span class="woo-sctr-shortcode-countdown-minute-value-container woo-sctr-shortcode-countdown-value-container">
                                                            <span class="woo-sctr-shortcode-countdown-minute-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                                <span class="woo-sctr-shortcode-countdown-minute-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                                    <span class="woo-sctr-shortcode-countdown-minute-value-1 woo-sctr-shortcode-countdown-value1"><?php echo $min_left > 0 ? esc_html( zeroise( $min_left - 1, 2 ) ) : '59'; ?></span>
                                                                    <span class="woo-sctr-shortcode-countdown-minute-value-2 woo-sctr-shortcode-countdown-value2"><?php echo esc_html( $min_left_t ); ?></span>
                                                                </span>
                                                            </span>
                                                        </span>
                                                    </span>
                                                </span>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                                    <span class="woo-sctr-shortcode-countdown-second woo-sctr-shortcode-countdown-unit">
                                                        <span class="woo-sctr-shortcode-countdown-second-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $second ); ?></span>
                                                        <span class="woo-sctr-shortcode-countdown-second-value-container woo-sctr-shortcode-countdown-value-container">
                                                            <span class="woo-sctr-shortcode-countdown-second-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                                <span class="woo-sctr-shortcode-countdown-second-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                                    <span class="woo-sctr-shortcode-countdown-second-value-1 woo-sctr-shortcode-countdown-value1"><?php echo esc_html( $sec_left_t ); ?></span>
                                                                    <span class="woo-sctr-shortcode-countdown-second-value-2 woo-sctr-shortcode-countdown-value2"><?php echo $sec_left < 60 ? esc_html( zeroise( $sec_left + 1, 2 ) ) : '00'; ?></span>
                                                                </span>
                                                            </span>
                                                        </span>
                                                    </span>
                                                </span>
                                            </div>
                                            <span class="woo-sctr-shortcode-countdown-text-bottom"><?php echo wp_kses_post( $sale_text_after ); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
						<?php
						break;
					case '3':
						?>
                        <div class="<?php echo esc_attr( $wrap_class ); ?>">
                            <input type="hidden" class="woo-sctr-shortcode-data-end_time"
                                   data-countdown_time_start="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $now ) ); ?>"
                                   data-countdown_time_end="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $countdown_time_end ) ); ?>"
                                   value="<?php echo esc_attr( $end_time ); ?>">
                            <span class="woo-sctr-shortcode-countdown-text-before"><?php echo esc_attr( $sale_text_before ); ?></span>
                            <span class="woo-sctr-shortcode-countdown-1">
                                <span class="woo-sctr-shortcode-countdown-unit-wrap"
                                      style="<?php if ( ! $day_left && $hide_zero_items ) {
	                                      echo 'display:none';
                                      } ?>">
                                    <span class="woo-sctr-shortcode-countdown-date woo-sctr-shortcode-countdown-unit">
                                        <span class="woo-sctr-shortcode-countdown-date-value-container woo-sctr-shortcode-countdown-value-container">
                                            <span class="woo-sctr-shortcode-countdown-date-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                <span class="woo-sctr-shortcode-countdown-date-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                    <span class="woo-sctr-shortcode-countdown-date-value-1 woo-sctr-shortcode-countdown-value1"><?php echo $day_left > 0 ? esc_html( zeroise( $day_left - 1, 2 ) ) : '00'; ?></span>
                                                    <span class="woo-sctr-shortcode-countdown-date-value-2 woo-sctr-shortcode-countdown-value2"><?php echo esc_html( $day_left_t ); ?></span>
                                                </span>
                                            </span>
                                        </span>
                                        <span class="woo-sctr-shortcode-countdown-date-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $day ); ?></span>
                                    </span>
                                </span>
                                <span class="woo-sctr-shortcode-countdown-time-separator"
                                      style="<?php if ( ! $day_left && $hide_zero_items ) {
	                                      echo 'display:none';
                                      } ?>"><?php echo esc_html( $time_separator ); ?></span>
                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                    <span class="woo-sctr-shortcode-countdown-hour woo-sctr-shortcode-countdown-unit">
                                        <span class="woo-sctr-shortcode-countdown-hour-value-container woo-sctr-shortcode-countdown-value-container">
                                            <span class="woo-sctr-shortcode-countdown-hour-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                <span class="woo-sctr-shortcode-countdown-hour-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                    <span class="woo-sctr-shortcode-countdown-hour-value-1 woo-sctr-shortcode-countdown-value1"><?php echo $hour_left > 0 ? esc_html( zeroise( $hour_left - 1, 2 ) ) : '23'; ?></span>
                                                    <span class="woo-sctr-shortcode-countdown-hour-value-2 woo-sctr-shortcode-countdown-value2"><?php echo esc_html( $hour_left_t ); ?></span>
                                                </span>
                                            </span>
                                        </span>
                                        <span class="woo-sctr-shortcode-countdown-hour-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $hour ); ?></span>
                                    </span>
                                </span>
                                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                    <span class="woo-sctr-shortcode-countdown-minute woo-sctr-shortcode-countdown-unit">
                                        <span class="woo-sctr-shortcode-countdown-minute-value-container woo-sctr-shortcode-countdown-value-container">
                                            <span class="woo-sctr-shortcode-countdown-minute-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                <span class="woo-sctr-shortcode-countdown-minute-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                    <span class="woo-sctr-shortcode-countdown-minute-value-1 woo-sctr-shortcode-countdown-value1"><?php echo $min_left > 0 ? esc_html( zeroise( $min_left - 1, 2 ) ) : '59'; ?></span>
                                                    <span class="woo-sctr-shortcode-countdown-minute-value-2 woo-sctr-shortcode-countdown-value2"><?php echo esc_html( $min_left_t ); ?></span>
                                                </span>
                                            </span>
                                        </span>
                                        <span class="woo-sctr-shortcode-countdown-minute-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $minute ); ?></span>
                                    </span>
                                </span>
                                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                                <span class="woo-sctr-shortcode-countdown-unit-wrap">
                                    <span class="woo-sctr-shortcode-countdown-second woo-sctr-shortcode-countdown-unit">
                                        <span class="woo-sctr-shortcode-countdown-second-value-container woo-sctr-shortcode-countdown-value-container">
                                            <span class="woo-sctr-shortcode-countdown-second-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                <span class="woo-sctr-shortcode-countdown-second-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                    <span class="woo-sctr-shortcode-countdown-second-value-1 woo-sctr-shortcode-countdown-value1"><?php echo esc_html( $sec_left_t ); ?></span>
                                                    <span class="woo-sctr-shortcode-countdown-second-value-2 woo-sctr-shortcode-countdown-value2"><?php echo $sec_left < 60 ? esc_html( zeroise( $sec_left + 1, 2 ) ) : '00'; ?></span>
                                                </span>
                                            </span>
                                        </span>
                                        <span class="woo-sctr-shortcode-countdown-second-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $second ); ?></span>
                                    </span>
                                </span>
                            </span>
                            <span class="woo-sctr-shortcode-countdown-text-after"><?php echo wp_kses_post( $sale_text_after ); ?></span>
                        </div>

						<?php
						break;
					case '4':
						?>
                        <div class="<?php echo esc_attr( $wrap_class ); ?>">
                            <div class="woo-sctr-shortcode-wrap">
                                <input type="hidden" class="woo-sctr-shortcode-data-end_time"
                                       data-countdown_time_start="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $now ) ); ?>"
                                       data-countdown_time_end="<?php echo esc_attr( gmdate( 'Y-m-d H:i:s', $countdown_time_end ) ); ?>"
                                       value="<?php echo esc_attr( $end_time ); ?>">
                                <div class="woo-sctr-shortcode-countdown-wrap <?php echo esc_attr( $design_class ); ?>">
                                    <div class="woo-sctr-shortcode-countdown">
                                        <div class="woo-sctr-shortcode-countdown-1">
                                            <span class="woo-sctr-shortcode-countdown-text-before"><?php echo wp_kses_post( $sale_text_before ); ?></span>
                                            <div class="woo-sctr-shortcode-countdown-2">
                                                <div class="woo-sctr-shortcode-countdown-unit-wrap"
                                                     style="<?php if ( ! $day_left && $hide_zero_items ) {
													     echo 'display:none';
												     } ?>">
                                                    <div class="woo-sctr-shortcode-countdown-date woo-sctr-shortcode-countdown-unit">
                                                        <span class="woo-sctr-shortcode-countdown-date-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $day ); ?></span>
                                                        <div class="woo-sctr-progress-circle<?php echo $day_deg <= 180 ? '' : ' woo-sctr-over50' ?>">
                                                            <div class="woo-sctr-shortcode-countdown-date-value-container woo-sctr-shortcode-countdown-value-container">
                                                                <div class="woo-sctr-shortcode-countdown-date-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                                    <div class="woo-sctr-shortcode-countdown-date-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                                        <span class="woo-sctr-shortcode-countdown-date-value-1 woo-sctr-shortcode-countdown-value1"><?php echo $day_left > 0 ? esc_html( zeroise( $day_left - 1, 2 ) ) : '00'; ?></span>
                                                                        <span class="woo-sctr-shortcode-countdown-date-value-2 woo-sctr-shortcode-countdown-value2"><?php echo esc_html( $day_left_t ); ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="woo-sctr-left-half-clipper">
                                                                <div class="woo-sctr-first50-bar"<?php echo $day_deg <= 180 ? 'style="display:none"' : '' ?>></div>
                                                                <div class="woo-sctr-value-bar"
                                                                     data-deg="<?php echo esc_attr( $day_deg ); ?>"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"
                                                      style="<?php if ( ! $day_left && $hide_zero_items ) {
													      echo 'display:none';
												      } ?>"><?php echo esc_html( $time_separator ); ?></span>
                                                <div class="woo-sctr-shortcode-countdown-unit-wrap">
                                                    <div class="woo-sctr-shortcode-countdown-hour woo-sctr-shortcode-countdown-unit">
                                                        <span class="woo-sctr-shortcode-countdown-hour-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $hour ); ?></span>
                                                        <div class="woo-sctr-progress-circle<?php echo $hour_deg <= 180 ? '' : ' woo-sctr-over50' ?>">
                                                            <div class="woo-sctr-shortcode-countdown-hour-value-container woo-sctr-shortcode-countdown-value-container">
                                                                <div class="woo-sctr-shortcode-countdown-hour-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                                    <div class="woo-sctr-shortcode-countdown-hour-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                                        <span class="woo-sctr-shortcode-countdown-hour-value-1 woo-sctr-shortcode-countdown-value1"><?php echo $hour_left > 0 ? esc_html( zeroise( $hour_left - 1, 2 ) ) : '23'; ?></span>
                                                                        <span class="woo-sctr-shortcode-countdown-hour-value-2 woo-sctr-shortcode-countdown-value2"><?php echo esc_html( $hour_left_t ); ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="woo-sctr-left-half-clipper">
                                                                <div class="woo-sctr-first50-bar"<?php echo $hour_deg <= 180 ? 'style="display:none"' : '' ?>></div>
                                                                <div class="woo-sctr-value-bar"
                                                                     data-deg="<?php echo esc_attr( $hour_deg ); ?>"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                                                <div class="woo-sctr-shortcode-countdown-unit-wrap">
                                                    <div class="woo-sctr-shortcode-countdown-minute woo-sctr-shortcode-countdown-unit">
                                                        <span class="woo-sctr-shortcode-countdown-minute-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $minute ); ?></span>
                                                        <div class="woo-sctr-progress-circle<?php echo $min_deg <= 180 ? '' : ' woo-sctr-over50' ?>">
                                                            <div class="woo-sctr-shortcode-countdown-minute-value-container woo-sctr-shortcode-countdown-value-container">
                                                                <div class="woo-sctr-shortcode-countdown-minute-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                                    <div class="woo-sctr-shortcode-countdown-minute-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                                        <span class="woo-sctr-shortcode-countdown-minute-value-1 woo-sctr-shortcode-countdown-value1"><?php echo $min_left > 0 ? esc_html( zeroise( $min_left - 1, 2 ) ) : '59'; ?></span>
                                                                        <span class="woo-sctr-shortcode-countdown-minute-value-2 woo-sctr-shortcode-countdown-value2"><?php echo esc_html( $min_left_t ); ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="woo-sctr-left-half-clipper">
                                                                <div class="woo-sctr-first50-bar"<?php echo $min_deg <= 180 ? 'style="display:none"' : '' ?>></div>
                                                                <div class="woo-sctr-value-bar"
                                                                     data-deg="<?php echo esc_attr( $min_deg ); ?>"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span class="woo-sctr-shortcode-countdown-time-separator"><?php echo esc_html( $time_separator ); ?></span>
                                                <div class="woo-sctr-shortcode-countdown-unit-wrap">
                                                    <div class="woo-sctr-shortcode-countdown-second woo-sctr-shortcode-countdown-unit">
                                                        <span class="woo-sctr-shortcode-countdown-second-text woo-sctr-shortcode-countdown-text"><?php echo esc_html( $second ); ?></span>
                                                        <div class="woo-sctr-progress-circle<?php echo $sec_deg <= 180 ? '' : ' woo-sctr-over50' ?>">
                                                            <div class="woo-sctr-shortcode-countdown-second-value-container woo-sctr-shortcode-countdown-value-container">
                                                                <div class="woo-sctr-shortcode-countdown-second-value-container-1 woo-sctr-shortcode-countdown-value-container-1">
                                                                    <div class="woo-sctr-shortcode-countdown-second-value-container-2 woo-sctr-shortcode-countdown-value-container-2">
                                                                        <span class="woo-sctr-shortcode-countdown-second-value-1 woo-sctr-shortcode-countdown-value1"><?php echo esc_html( $sec_left_t ); ?></span>
                                                                        <span class="woo-sctr-shortcode-countdown-second-value-2 woo-sctr-shortcode-countdown-value2"><?php echo $sec_left < 60 ? esc_html( zeroise( $sec_left + 1, 2 ) ) : '00'; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="woo-sctr-left-half-clipper">
                                                                <div class="woo-sctr-first50-bar"<?php echo $sec_deg <= 180 ? 'style="display:none"' : '' ?>></div>
                                                                <div class="woo-sctr-value-bar"
                                                                     data-deg="<?php echo esc_attr( $sec_deg ); ?>"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="woo-sctr-shortcode-countdown-text-bottom"><?php echo wp_kses_post( $sale_text_after ); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
						<?php
						break;
					default:
				}
			}
		}


		$return = ob_get_clean();
		$return = str_replace( "\n", '', $return );
		$return = str_replace( "\r", '', $return );
		$return = str_replace( "\t", '', $return );
		$return = str_replace( "\l", '', $return );
		$return = str_replace( "\0", '', $return );

		return $return;
	}
}