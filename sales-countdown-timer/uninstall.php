<?php
/**
 * Uninstall Sales Countdown Timer.
 *
 * @package SalesCountdownTimer
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Intentional SALES_COUNTDOWN_TIMER_ / woo_ctr_ / villatheme_ prefixes.

delete_option( 'sales_countdown_timer_params' );
delete_option( 'villatheme_sales-countdown-timer_dismiss_notices' );
delete_option( 'villatheme_sales-countdown-timer_wp_reviewed' );
delete_option( 'villatheme_sales-countdown-timer_start_use' );
delete_transient( '_sales_countdown_timer_demo_product_init' );

global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_woo_sctr_update_variable_price_start_sale_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_woo_sctr_update_variable_price_start_sale_' ) . '%'
	)
);
