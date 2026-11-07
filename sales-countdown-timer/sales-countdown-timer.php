<?php
/**
 * Plugin Name: Countix Sales Countdown Timer
 * Plugin URI: https://villatheme.com/extensions/sales-countdown-timer/
 * Description: Create a sense of urgency with a countdown to the beginning or end of sales, store launch or other events for higher conversions.
 * Version: 1.1.13
 * Author: VillaTheme
 * Author URI: https://villatheme.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sales-countdown-timer
 * Domain Path: /languages
 * Copyright 2018 - 2026 VillaTheme.com. All rights reserved.
 * Requires at least: 5.2
 * Tested up to: 7.1
 * WC requires at least: 7.0
 * WC tested up to: 11.2
 * Requires PHP: 7.4
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Intentional SALES_COUNTDOWN_TIMER_ / woo_ctr_ / woo_sctr_ / sctv_ / VillaTheme_ prefixes.

define( 'SALES_COUNTDOWN_TIMER_VERSION', '1.1.13' );

/**
 * Class SALES_COUNTDOWN_TIMER
 */
class SALES_COUNTDOWN_TIMER {
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );

		// Compatible with High-Performance order storage (COT).
		add_action( 'before_woocommerce_init', array( $this, 'before_woocommerce_init' ) );
	}

	public function init() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( is_plugin_active( 'sctv-sales-countdown-timer/sctv-sales-countdown-timer.php' ) ) {
			return;
		}
		if ( is_plugin_active( 'countix-sales-countdown-timer/countix-sales-countdown-timer.php' ) ) {
			return;
		}

		$plugin_dir = plugin_dir_path( __FILE__ );

		if ( ! class_exists( 'VillaTheme_Require_Environment' ) ) {
			require_once $plugin_dir . 'includes/support.php';
		}

		$environment = new VillaTheme_Require_Environment(
			array(
				'plugin_name' => 'Countix Sales Countdown Timer',
				'php_version' => '7.4',
				'wp_version'  => '5.2',
			)
		);

		if ( $environment->has_error() ) {
			return;
		}

		require_once $plugin_dir . 'includes/define.php';
	}

	public function before_woocommerce_init() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
}

new SALES_COUNTDOWN_TIMER();
