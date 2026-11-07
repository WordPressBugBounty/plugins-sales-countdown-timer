<?php

/**
 * Class SALES_COUNTDOWN_TIMER_Frontend_Shortcode
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Intentional SALES_COUNTDOWN_TIMER_ prefixes and WooCommerce hooks.

class SALES_COUNTDOWN_TIMER_Frontend_Archive_Page {
	protected $settings;
	protected $return;
	protected $progress_bar_html;
	protected $id;
	protected $index;
	protected $position;
	protected $sale_from_date;
	protected $sale_from_time;
	protected $sale_to_date;
	protected $sale_to_time;

	public function __construct() {
		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$this->settings = new SALES_COUNTDOWN_TIMER_Data();
			add_action( 'wp', array( $this, 'update_price' ) );

			add_action( 'woocommerce_new_order_item', array( $this, 'woocommerce_new_order_item' ), 10, 3 );
			/*countdown timer position*/
			add_action( 'woocommerce_before_template_part', array( $this, 'countdown_before_template_loop' ) );
			add_action( 'woocommerce_after_template_part', array( $this, 'countdown_after_template_loop' ) );
			add_filter( 'woocommerce_product_get_image', array( $this, 'woocommerce_product_get_image' ), 99, 2 );
			add_filter( 'woocommerce_loop_add_to_cart_link', array(
				$this,
				'woocommerce_loop_add_to_cart_link'
			), 99, 2 );
		}
	}

	/**
	 * Correct scheduled-sale price meta only when mismatched, at most once per transient TTL.
	 *
	 * @param WC_Product $product Product or variation.
	 */
	protected function maybe_correct_sale_schedule( $product ) {
		if ( ! $product || ! $product->get_sale_price( 'edit' ) ) {
			return;
		}
		$product_id    = $product->get_id();
		$transient_key = 'woo_sctr_sale_schedule_sync_' . $product_id;
		if ( get_transient( $transient_key ) ) {
			return;
		}

		$now     = current_time( 'timestamp', true );
		$regular = $product->get_regular_price( 'edit' );
		$price   = $product->get_price( 'edit' );
		$sale    = $product->get_sale_price( 'edit' );
		$from    = $product->get_date_on_sale_from( 'edit' );
		$to      = $product->get_date_on_sale_to( 'edit' );
		$from_ts = ( $from && $from->getTimestamp() ) ? $from->getTimestamp() : 0;
		$to_ts   = ( $to && $to->getTimestamp() ) ? $to->getTimestamp() : 0;

		$needs_upcoming = ( (string) $regular !== (string) $price && $from_ts && $now < $from_ts );
		$needs_expire   = ( $to_ts && $now > $to_ts );
		$needs_start    = ( (string) $regular === (string) $price && $from_ts && $now >= $from_ts && ( ! $to_ts || $now <= $to_ts ) );

		if ( ! $needs_upcoming && ! $needs_expire && ! $needs_start ) {
			return;
		}

		if ( $needs_upcoming ) {
			update_post_meta( $product_id, '_price', $regular );
			$product->set_price( $regular );
		} elseif ( $needs_expire ) {
			update_post_meta( $product_id, '_sale_price_old_woo_ctr', $sale );
			$product->set_price( $regular );
			$product->set_sale_price( '' );
			$product->set_date_on_sale_to( '' );
			$product->set_date_on_sale_from( '' );
			$product->save();
			delete_post_meta( $product_id, '_woo_ctr_product_sold_quantity' );
		} elseif ( $needs_start ) {
			update_post_meta( $product_id, '_price', $sale );
			$product->set_price( $sale );
		}

		set_transient( $transient_key, 1, HOUR_IN_SECONDS );
	}

	public function update_price() {
		if ( is_admin() ) {
			return;
		}
		// Shop/category: no archive query re-loop writes; countdown filters use in-loop product objects.
		if ( is_tax( 'product_cat' ) || is_post_type_archive( 'product' ) ) {
			return;
		}
		if ( ! is_checkout() && ! is_cart() ) {
			return;
		}
		if ( ! WC()->cart ) {
			return;
		}
		$cart = WC()->cart->get_cart();
		if ( ! is_array( $cart ) || ! count( $cart ) ) {
			return;
		}
		foreach ( $cart as $cart_item ) {
			$product_id = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];
			$product    = wc_get_product( $product_id );
			if ( $product ) {
				$this->maybe_correct_sale_schedule( $product );
			}
		}
	}


	public function get_progress_bar_html( $product_id, $index, $progress_bar_real_quantity, $progress_bar_message, $progress_bar_type ) {
		$wc_product = wc_get_product( $product_id );
		if ( ! $wc_product || ! $wc_product->is_on_sale() ) {
			return '';
		}
		$progress_bar               = $wc_product->get_meta( '_woo_ctr_enable_progress_bar', true );
		$progress_bar_goal          = $wc_product->get_meta( '_woo_ctr_progress_bar_goal', true );
		$progress_bar_initial       = $wc_product->get_meta( '_woo_ctr_progress_bar_initial', true );
		$progress_bar_width         = absint( $this->settings->get_progress_bar_width()[ $index ] );
		$progress_bar_height        = absint( $this->settings->get_progress_bar_height()[ $index ] );
		$progress_bar_color         = $this->settings->get_progress_bar_color()[ $index ];
		$progress_bar_bg_color      = $this->settings->get_progress_bar_bg_color()[ $index ];
		$progress_bar_border_radius = absint( $this->settings->get_progress_bar_border_radius()[ $index ] );
		$progress_bar_html          = '';
		if ( $progress_bar_real_quantity >= 0 && $progress_bar && $progress_bar_goal ) {
			$progress_bar_real_quantity += $progress_bar_initial;
			$quantity_sold              = $progress_bar_real_quantity;
			$quantity_left              = (int) ( $progress_bar_goal - $progress_bar_real_quantity );
			$percentage_sold            = (int) ( 100 * ( $progress_bar_real_quantity / $progress_bar_goal ) );
			$percentage_left            = 100 - $percentage_sold;
			if ( $progress_bar_real_quantity >= $progress_bar_goal ) {
				$progress_bar_real_quantity = $progress_bar_goal;
			}
			$progress_bar_fill = 100 * ( $progress_bar_real_quantity / $progress_bar_goal );
			if ( $progress_bar_type == 'decrease' ) {
				$progress_bar_fill = 100 - $progress_bar_fill;
			}
			if ( $progress_bar_fill < 0 ) {
				$progress_bar_fill = 0;
			} elseif ( $progress_bar_fill > 100 ) {
				$progress_bar_fill = 100;
			}

			$progress_bar_message = str_replace( '{quantity_left}', $quantity_left, $progress_bar_message );
			$progress_bar_message = str_replace( '{quantity_sold}', $quantity_sold, $progress_bar_message );
			$progress_bar_message = str_replace( '{percentage_sold}', $percentage_sold, $progress_bar_message );
			$progress_bar_message = str_replace( '{percentage_left}', $percentage_left, $progress_bar_message );
			$progress_bar_message = str_replace( '{goal}', $progress_bar_goal, $progress_bar_message );

			ob_start();
			?>
            <div class="woo-sctr-progress-bar-wrap-container">
                <div class="woo-sctr-progress-bar-wrap" style="<?php if ( $progress_bar_width ) {
					echo 'width:' . esc_attr( $progress_bar_width ) . 'px;';
				}
				if ( $progress_bar_height ) {
					echo 'height:' . esc_attr( $progress_bar_height ) . 'px;';
				}
				if ( $progress_bar_bg_color ) {
					echo 'background:' . esc_attr( $progress_bar_bg_color ) . ';';
				}
				if ( '' !== $progress_bar_border_radius ) {
					echo 'border-radius:' . esc_attr( $progress_bar_border_radius ) . 'px;';
				} ?>">
                    <div class="woo-sctr-progress-bar-fill"
                         style="width: <?php echo esc_attr( $progress_bar_fill ) . '%;';
					     if ( $progress_bar_color )
						     echo 'background:' . esc_attr( $progress_bar_color ) . ';' ?>"></div>
                </div>
				<?php
				?>
                <div class="woo-sctr-progress-bar-message"><?php echo wp_kses_post( $progress_bar_message ); ?></div>
				<?php
				?>
            </div>
			<?php
			$progress_bar_html = ob_get_clean();
		}

		return $progress_bar_html;
	}

	public function get_progress_bar_real_quantity( $data, $order_status ) {
		static $order_status_cache = array();
		$progress_bar_real_quantity = 0;
		if ( is_array( $order_status ) && empty( $order_status ) ) {
			$order_status = array_keys( wc_get_order_statuses() );
		}
		if ( is_array( $data ) && count( $data ) && is_array( $order_status ) && count( $order_status ) ) {
			foreach ( $data as $value ) {
				$order_id = isset( $value['id'] ) ? intval( $value['id'] ) : 0;
				if ( ! $order_id ) {
					continue;
				}
				if ( ! array_key_exists( $order_id, $order_status_cache ) ) {
					$order = wc_get_order( $order_id );
					$order_status_cache[ $order_id ] = $order ? 'wc-' . $order->get_status() : '';
				}
				if ( $order_status_cache[ $order_id ] && in_array( $order_status_cache[ $order_id ], $order_status, true ) ) {
					$progress_bar_real_quantity += $value['quantity'];
				}
			}
		}

		return $progress_bar_real_quantity;
	}

	public function woocommerce_product_get_image( $html, $product ) {
		if ( is_admin() ) {
			return $html;
		}
		if ( ! $product ) {
			return $html;
		}
		if ( ! $product->is_in_stock() ) {
			return $html;
		}
		if ( ! $product->get_sale_price( 'edit' ) ) {
			return $html;
		}
		if ( ! $product->get_date_on_sale_from( 'edit' ) && ! $product->get_date_on_sale_to( 'edit' ) ) {
			return $html;
		}
		$product_id = $product->get_id();
		$id         = $product->get_meta( '_woo_ctr_select_countdown_timer', true );

		if ( $id !== '' ) {
			$index = array_search( $id, $this->settings->get_id() );
			if ( $index === false ) {
				return $html;
			}
			if ( ! $this->settings->get_active()[ $index ] ) {
				return $html;
			}
			if ( $this->settings->get_archive_page_position()[ $index ] !== 'product_image' ) {
				return $html;
			}
			if ( is_tax( 'product_cat' ) && ! $this->settings->get_category_page()[ $index ] ) {
				return $html;
			} elseif ( is_post_type_archive( 'product' ) && ! $this->settings->get_shop_page()[ $index ] ) {
				return $html;
			} elseif ( ! is_tax( 'product_cat' ) && ! is_post_type_archive( 'product' ) ) {
				return $html;
			} elseif ( is_cart() ) {
				return $html;
			}
			$offset         = get_option( 'gmt_offset' );
			$sale_from      = ( $product->get_date_on_sale_from( 'edit' ) ) ? ( $product->get_date_on_sale_from( 'edit' )->getTimestamp() + $offset * 3600 ) : 0;
			$sale_to        = ( $product->get_date_on_sale_to( 'edit' ) ) ? ( $product->get_date_on_sale_to( 'edit' )->getTimestamp() + $offset * 3600 ) : 0;
			$sale_from_date = gmdate( 'Y-m-d', $sale_from );
			$sale_to_date   = gmdate( 'Y-m-d', $sale_to );
			$sale_from_time = $sale_from - strtotime( $sale_from_date );
			$sale_to_time   = $sale_to - strtotime( $sale_to_date );
			$sale_from_time = woo_ctr_time_revert( $sale_from_time );
			$sale_to_time   = woo_ctr_time_revert( $sale_to_time );
//		    calculate sold quantity during campaign
			$data                       = $product->get_meta( '_woo_ctr_product_sold_quantity', true ) ? ( $product->get_meta( '_woo_ctr_product_sold_quantity', true ) ) : array();
			$order_status               = $this->settings->get_progress_bar_order_status()[ $index ] ? explode( ',', $this->settings->get_progress_bar_order_status()[ $index ] ) : array();
			$progress_bar_message       = $this->settings->get_progress_bar_message()[ $index ];
			$progress_bar_type          = $this->settings->get_progress_bar_type()[ $index ];
			$progress_bar_real_quantity = $this->get_progress_bar_real_quantity( $data, $order_status );

			$progress_bar_html = $this->get_progress_bar_html( $product_id, $index, $progress_bar_real_quantity, $progress_bar_message, $progress_bar_type );
			if ( $this->settings->get_progress_bar_position()[ $index ] == 'above_countdown' ) {
				return $progress_bar_html . '<div class="woo-sctr-countdown-timer-product-image-cate-shop-wrap">' . $html . do_shortcode( '[sales_countdown_timer enable_single_product="1" id="' . $id . '" sale_from_date="' . $sale_from_date . '" sale_from_time="' . $sale_from_time . '" sale_to_date="' . $sale_to_date . '" sale_to_time="' . $sale_to_time . '"]' ) . '</div>';

			} else {
				return '<div class="woo-sctr-countdown-timer-product-image-cate-shop-wrap">' . $html . do_shortcode( '[sales_countdown_timer enable_single_product="1" id="' . $id . '" sale_from_date="' . $sale_from_date . '" sale_from_time="' . $sale_from_time . '" sale_to_date="' . $sale_to_date . '" sale_to_time="' . $sale_to_time . '"]' ) . '</div>' . $progress_bar_html;

			}
		}

		return $html;
	}

	public function woocommerce_loop_add_to_cart_link( $html, $product ) {
		if ( is_admin() ) {
			return $html;
		}
		if ( ! $product ) {
			return $html;
		}
		if ( ! $product->is_in_stock() ) {
			return $html;
		}
		if ( ! $product->get_sale_price( 'edit' ) ) {
			return $html;
		}
		if ( ! $product->get_date_on_sale_from( 'edit' ) && ! $product->get_date_on_sale_to( 'edit' ) ) {
			return $html;
		}
		$product_id = $product->get_id();
		$id         = $product->get_meta( '_woo_ctr_select_countdown_timer', true );

		if ( $id !== '' ) {
			$index = array_search( $id, $this->settings->get_id() );
			if ( $index === false ) {
				return $html;
			}
			if ( ! $this->settings->get_active()[ $index ] ) {
				return $html;
			}
			if ( ! in_array( $this->settings->get_archive_page_position()[ $index ], array(
				'before_cart',
				'after_cart'
			) ) ) {
				return $html;
			}
			if ( is_tax( 'product_cat' ) && ! $this->settings->get_category_page()[ $index ] ) {
				return $html;
			} elseif ( is_post_type_archive( 'product' ) && ! $this->settings->get_shop_page()[ $index ] ) {
				return $html;
			} elseif ( ! is_tax( 'product_cat' ) && ! is_post_type_archive( 'product' ) ) {
				return $html;
			}
			$offset         = get_option( 'gmt_offset' );
			$sale_from      = ( $product->get_date_on_sale_from( 'edit' ) ) ? ( $product->get_date_on_sale_from( 'edit' )->getTimestamp() + $offset * 3600 ) : 0;
			$sale_to        = ( $product->get_date_on_sale_to( 'edit' ) ) ? ( $product->get_date_on_sale_to( 'edit' )->getTimestamp() + $offset * 3600 ) : 0;
			$sale_from_date = gmdate( 'Y-m-d', $sale_from );
			$sale_to_date   = gmdate( 'Y-m-d', $sale_to );
			$sale_from_time = $sale_from - strtotime( $sale_from_date );
			$sale_to_time   = $sale_to - strtotime( $sale_to_date );
			$sale_from_time = woo_ctr_time_revert( $sale_from_time );
			$sale_to_time   = woo_ctr_time_revert( $sale_to_time );
//		    calculate sold quantity during campaign
			$data                       = $product->get_meta( '_woo_ctr_product_sold_quantity', true ) ? ( $product->get_meta( '_woo_ctr_product_sold_quantity', true ) ) : array();
			$order_status               = $this->settings->get_progress_bar_order_status()[ $index ] ? explode( ',', $this->settings->get_progress_bar_order_status()[ $index ] ) : array();
			$progress_bar_message       = $this->settings->get_progress_bar_message()[ $index ];
			$progress_bar_type          = $this->settings->get_progress_bar_type()[ $index ];
			$progress_bar_real_quantity = $this->get_progress_bar_real_quantity( $data, $order_status );

			$progress_bar_html = $this->get_progress_bar_html( $product_id, $index, $progress_bar_real_quantity, $progress_bar_message, $progress_bar_type );
			if ( $this->settings->get_archive_page_position()[ $index ] == 'before_cart' ) {
				if ( $this->settings->get_progress_bar_position()[ $index ] == 'above_countdown' ) {
					return $progress_bar_html . do_shortcode( '[sales_countdown_timer enable_single_product="1" id="' . $id . '" sale_from_date="' . $sale_from_date . '" sale_from_time="' . $sale_from_time . '" sale_to_date="' . $sale_to_date . '" sale_to_time="' . $sale_to_time . '"]' ) . $html;

				} else {
					return do_shortcode( '[sales_countdown_timer enable_single_product="1" id="' . $id . '" sale_from_date="' . $sale_from_date . '" sale_from_time="' . $sale_from_time . '" sale_to_date="' . $sale_to_date . '" sale_to_time="' . $sale_to_time . '"]' ) . $progress_bar_html . $html;

				}
			} else {
				if ( $this->settings->get_progress_bar_position()[ $index ] == 'above_countdown' ) {
					return $html . $progress_bar_html . do_shortcode( '[sales_countdown_timer enable_single_product="1" id="' . $id . '" sale_from_date="' . $sale_from_date . '" sale_from_time="' . $sale_from_time . '" sale_to_date="' . $sale_to_date . '" sale_to_time="' . $sale_to_time . '"]' );

				} else {
					return $html . do_shortcode( '[sales_countdown_timer enable_single_product="1" id="' . $id . '" sale_from_date="' . $sale_from_date . '" sale_from_time="' . $sale_from_time . '" sale_to_date="' . $sale_to_date . '" sale_to_time="' . $sale_to_time . '"]' ) . $progress_bar_html;

				}
			}

		}

		return $html;
	}


	public function woocommerce_new_order_item( $item_id, $item, $order_id ) {
		$product_id = wc_get_order_item_meta( $item_id, '_product_id', true );
		$product    = wc_get_product( $product_id );
		if ( $product && $product->is_on_sale() ) {
			$data   = $product->get_meta( '_woo_ctr_product_sold_quantity', true ) ? ( $product->get_meta( '_woo_ctr_product_sold_quantity', true ) ) : array();
			$data[] = array( 'id' => $order_id, 'quantity' => wc_get_order_item_meta( $item_id, '_qty', true ) );
			update_post_meta( $product_id, '_woo_ctr_product_sold_quantity', $data );
		}

	}

	public function countdown_before_template_loop( $template_name ) {
		if ( ! in_array( $template_name, array(
			'loop/price.php',
			'loop/sale-flash.php',
		) ) ) {
			return;
		}

		global $product;
		if ( ! $product ) {
			return;
		}
		if ( ! $product->is_in_stock() ) {
			return;
		}
		if ( ! $product->get_sale_price( 'edit' ) ) {
			return;
		}
		if ( ! $product->get_date_on_sale_from( 'edit' ) && ! $product->get_date_on_sale_to( 'edit' ) ) {
			return;
		}
		$product_id = $product->get_id();
		$id         = $product->get_meta( '_woo_ctr_select_countdown_timer', true );

		if ( $id !== '' ) {
			$index = array_search( $id, $this->settings->get_id() );
			if ( $index === false ) {
				return;
			}
			if ( ! $this->settings->get_active()[ $index ] ) {
				return;
			}
			switch ( $this->settings->get_archive_page_position()[ $index ] ) {
				case 'before_saleflash':
					if ( $template_name !== 'loop/sale-flash.php' ) {
						return;
					}
					break;
				case 'before_price':
					if ( $template_name !== 'loop/price.php' ) {
						return;
					}
					break;
				default:
					return;
			}
			if ( is_tax( 'product_cat' ) && ! $this->settings->get_category_page()[ $index ] ) {
				return;
			} elseif ( is_post_type_archive( 'product' ) && ! $this->settings->get_shop_page()[ $index ] ) {

				return;

			} elseif ( ! is_tax( 'product_cat' ) && ! is_post_type_archive( 'product' ) ) {
				return;
			}
			$offset         = get_option( 'gmt_offset' );
			$sale_from      = ( $product->get_date_on_sale_from( 'edit' ) ) ? ( $product->get_date_on_sale_from( 'edit' )->getTimestamp() + $offset * 3600 ) : 0;
			$sale_to        = ( $product->get_date_on_sale_to( 'edit' ) ) ? ( $product->get_date_on_sale_to( 'edit' )->getTimestamp() + $offset * 3600 ) : 0;
			$sale_from_date = gmdate( 'Y-m-d', $sale_from );
			$sale_to_date   = gmdate( 'Y-m-d', $sale_to );
			$sale_from_time = $sale_from - strtotime( $sale_from_date );
			$sale_to_time   = $sale_to - strtotime( $sale_to_date );
			$sale_from_time = woo_ctr_time_revert( $sale_from_time );
			$sale_to_time   = woo_ctr_time_revert( $sale_to_time );
//		    calculate sold quantity during campaign
			$data                       = $product->get_meta( '_woo_ctr_product_sold_quantity', true ) ? ( $product->get_meta( '_woo_ctr_product_sold_quantity', true ) ) : array();
			$order_status               = $this->settings->get_progress_bar_order_status()[ $index ] ? explode( ',', $this->settings->get_progress_bar_order_status()[ $index ] ) : array();
			$progress_bar_message       = $this->settings->get_progress_bar_message()[ $index ];
			$progress_bar_type          = $this->settings->get_progress_bar_type()[ $index ];
			$progress_bar_real_quantity = $this->get_progress_bar_real_quantity( $data, $order_status );
			$progress_bar_html          = $this->get_progress_bar_html( $product_id, $index, $progress_bar_real_quantity, $progress_bar_message, $progress_bar_type );
			if ( $this->settings->get_progress_bar_position()[ $index ] == 'above_countdown' ) {
				echo $progress_bar_html . do_shortcode( '[sales_countdown_timer enable_single_product="1" id="' . esc_attr( $id ) . '" sale_from_date="' . esc_attr( $sale_from_date ) . '" sale_from_time="' . esc_attr( $sale_from_time ) . '" sale_to_date="' . esc_attr( $sale_to_date ) . '" sale_to_time="' . esc_attr( $sale_to_time ) . '"]' );// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo do_shortcode( '[sales_countdown_timer enable_single_product="1" id="' . esc_attr( $id ) . '" sale_from_date="' . esc_attr( $sale_from_date ) . '" sale_from_time="' . esc_attr( $sale_from_time ) . '" sale_to_date="' . esc_attr( $sale_to_date ) . '" sale_to_time="' . esc_attr( $sale_to_time ) . '"]' ) . $progress_bar_html;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

	}

	public function countdown_after_template_loop( $template_name ) {
		if ( ! in_array( $template_name, array(
			'loop/price.php',
			'loop/sale-flash.php',
		) ) ) {
			return;
		}

		global $product;
		if ( ! $product ) {
			return;
		}
		if ( ! $product->is_in_stock() ) {
			return;
		}
		if ( ! $product->get_sale_price( 'edit' ) ) {
			return;
		}
		if ( ! $product->get_date_on_sale_from( 'edit' ) && ! $product->get_date_on_sale_to( 'edit' ) ) {
			return;
		}
		$product_id = $product->get_id();
		$id         = $product->get_meta( '_woo_ctr_select_countdown_timer', true );

		if ( $id !== '' ) {
			$index = array_search( $id, $this->settings->get_id() );
			if ( $index === false ) {
				return;
			}
			if ( ! $this->settings->get_active()[ $index ] ) {
				return;
			}
			switch ( $this->settings->get_archive_page_position()[ $index ] ) {
				case 'after_saleflash':
					if ( $template_name !== 'loop/sale-flash.php' ) {
						return;
					}
					break;
				case 'after_price':
					if ( $template_name !== 'loop/price.php' ) {
						return;
					}
					break;
				default:
					return;
			}

			if ( is_tax( 'product_cat' ) && ! $this->settings->get_category_page()[ $index ] ) {

				return;
			} elseif ( is_post_type_archive( 'product' ) && ! $this->settings->get_shop_page()[ $index ] ) {

				return;

			} elseif ( ! is_tax( 'product_cat' ) && ! is_post_type_archive( 'product' ) ) {
				return;
			}
			$offset         = get_option( 'gmt_offset' );
			$sale_from      = ( $product->get_date_on_sale_from( 'edit' ) ) ? ( $product->get_date_on_sale_from( 'edit' )->getTimestamp() + $offset * 3600 ) : 0;
			$sale_to        = ( $product->get_date_on_sale_to( 'edit' ) ) ? ( $product->get_date_on_sale_to( 'edit' )->getTimestamp() + $offset * 3600 ) : 0;
			$sale_from_date = gmdate( 'Y-m-d', $sale_from );
			$sale_to_date   = gmdate( 'Y-m-d', $sale_to );
			$sale_from_time = $sale_from - strtotime( $sale_from_date );
			$sale_to_time   = $sale_to - strtotime( $sale_to_date );
			$sale_from_time = woo_ctr_time_revert( $sale_from_time );
			$sale_to_time   = woo_ctr_time_revert( $sale_to_time );
//		    calculate sold quantity during campaign
			$data                       = $product->get_meta( '_woo_ctr_product_sold_quantity', true ) ? ( $product->get_meta( '_woo_ctr_product_sold_quantity', true ) ) : array();
			$order_status               = $this->settings->get_progress_bar_order_status()[ $index ] ? explode( ',', $this->settings->get_progress_bar_order_status()[ $index ] ) : array();
			$progress_bar_message       = $this->settings->get_progress_bar_message()[ $index ];
			$progress_bar_type          = $this->settings->get_progress_bar_type()[ $index ];
			$progress_bar_real_quantity = $this->get_progress_bar_real_quantity( $data, $order_status );
			$progress_bar_html          = $this->get_progress_bar_html( $product_id, $index, $progress_bar_real_quantity, $progress_bar_message, $progress_bar_type );
			if ( $this->settings->get_progress_bar_position()[ $index ] == 'above_countdown' ) {
				echo $progress_bar_html . do_shortcode( '[sales_countdown_timer enable_single_product="1" id="' . esc_attr( $id ) . '" sale_from_date="' . esc_attr( $sale_from_date ) . '" sale_from_time="' . esc_attr( $sale_from_time ) . '" sale_to_date="' . esc_attr( $sale_to_date ) . '" sale_to_time="' . esc_attr( $sale_to_time ) . '"]' );// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo do_shortcode( '[sales_countdown_timer enable_single_product="1" id="' . esc_attr( $id ) . '" sale_from_date="' . esc_attr( $sale_from_date ) . '" sale_from_time="' . esc_attr( $sale_from_time ) . '" sale_to_date="' . esc_attr( $sale_to_date ) . '" sale_to_time="' . esc_attr( $sale_to_time ) . '"]' ) . $progress_bar_html;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

	}
}