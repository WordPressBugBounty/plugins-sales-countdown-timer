<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Intentional SALES_COUNTDOWN_TIMER_ / woo_ctr_ / woo_sctr_ / sctv_ / VillaTheme_ prefixes.

define( 'SALES_COUNTDOWN_TIMER_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
define( 'SALES_COUNTDOWN_TIMER_ADMIN', SALES_COUNTDOWN_TIMER_DIR . 'admin' . DIRECTORY_SEPARATOR );
define( 'SALES_COUNTDOWN_TIMER_FRONTEND', SALES_COUNTDOWN_TIMER_DIR . 'frontend' . DIRECTORY_SEPARATOR );
define( 'SALES_COUNTDOWN_TIMER_LANGUAGES', SALES_COUNTDOWN_TIMER_DIR . 'languages' . DIRECTORY_SEPARATOR );
define( 'SALES_COUNTDOWN_TIMER_INCLUDES', SALES_COUNTDOWN_TIMER_DIR . 'includes' . DIRECTORY_SEPARATOR );
$sales_countdown_timer_plugin_url = plugins_url( '', dirname( __FILE__ ) );
define( 'SALES_COUNTDOWN_TIMER_CSS', $sales_countdown_timer_plugin_url . '/css/' );
define( 'SALES_COUNTDOWN_TIMER_CSS_DIR', SALES_COUNTDOWN_TIMER_DIR . 'css' . DIRECTORY_SEPARATOR );
define( 'SALES_COUNTDOWN_TIMER_JS', $sales_countdown_timer_plugin_url . '/js/' );
define( 'SALES_COUNTDOWN_TIMER_JS_DIR', SALES_COUNTDOWN_TIMER_DIR . 'js' . DIRECTORY_SEPARATOR );
define( 'SALES_COUNTDOWN_TIMER_IMAGES', $sales_countdown_timer_plugin_url . '/images/' );

if ( is_file( SALES_COUNTDOWN_TIMER_INCLUDES . 'functions.php' ) ) {
	require_once SALES_COUNTDOWN_TIMER_INCLUDES . 'functions.php';
}

if ( is_file( SALES_COUNTDOWN_TIMER_INCLUDES . 'data.php' ) ) {
	require_once SALES_COUNTDOWN_TIMER_INCLUDES . 'data.php';
}
if ( is_file( SALES_COUNTDOWN_TIMER_INCLUDES . 'support.php' ) ) {
	require_once SALES_COUNTDOWN_TIMER_INCLUDES . 'support.php';
}
if ( is_file( SALES_COUNTDOWN_TIMER_INCLUDES . 'countdown-style.php' ) ) {
	require_once SALES_COUNTDOWN_TIMER_INCLUDES . 'countdown-style.php';
}

if ( is_file( SALES_COUNTDOWN_TIMER_INCLUDES . '3rd/elementor/elementor.php' ) ) {
	require_once SALES_COUNTDOWN_TIMER_INCLUDES . '3rd/elementor/elementor.php';
}
vi_include_folder( SALES_COUNTDOWN_TIMER_ADMIN, 'SALES_COUNTDOWN_TIMER_Admin_' );
vi_include_folder( SALES_COUNTDOWN_TIMER_FRONTEND, 'SALES_COUNTDOWN_TIMER_Frontend_' );
