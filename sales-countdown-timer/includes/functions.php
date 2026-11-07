<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Intentional SALES_COUNTDOWN_TIMER_ / woo_ctr_ / vi_ prefixes.

/**
 * Function include all files in folder
 *
 * @param string       $path   Directory address.
 * @param string       $prefix Class prefix.
 * @param array|string $ext    File extension(s) to include.
 */
if ( ! function_exists( 'vi_include_folder' ) ) {
	function vi_include_folder( $path, $prefix = '', $ext = array( 'php' ) ) {

		if ( ! is_array( $ext ) ) {
			$ext = explode( ',', $ext );
			$ext = array_map( 'trim', $ext );
		}
		$sfiles = scandir( $path );
		foreach ( $sfiles as $sfile ) {
			if ( '.' === $sfile || '..' === $sfile || 'index.php' === $sfile ) {
				continue;
			}
			if ( is_file( $path . '/' . $sfile ) ) {
				$ext_file  = pathinfo( $path . '/' . $sfile );
				$file_name = $ext_file['filename'];
				if ( ! empty( $ext_file['extension'] ) && in_array( $ext_file['extension'], $ext, true ) ) {
					$class = preg_replace( '/\W/i', '_', $prefix . ucfirst( $file_name ) );

					if ( ! class_exists( $class ) ) {
						require_once $path . $sfile;
						if ( class_exists( $class ) ) {
							new $class();
						}
					}
				}
			}
		}
	}
}
if ( ! function_exists( 'woo_ctr_time' ) ) {
	function woo_ctr_time( $time ) {
		if ( ! $time ) {
			return 0;
		}
		$temp = explode( ':', $time );
		if ( count( $temp ) === 2 ) {
			return ( absint( $temp[0] ) * 3600 + absint( $temp[1] ) * 60 );
		}

		return 0;
	}
}
if ( ! function_exists( 'woo_ctr_time_revert' ) ) {
	function woo_ctr_time_revert( $time ) {
		$hour = floor( $time / 3600 );
		$min  = floor( ( $time - 3600 * $hour ) / 60 );

		return implode( ':', array( zeroise( $hour, 2 ), zeroise( $min, 2 ) ) );
	}
}

if ( ! function_exists( 'woo_ctr_sanitize_html_class' ) ) {
	/**
	 * Sanitize a countdown profile id for use as an HTML/CSS class token.
	 *
	 * @param mixed $id Raw profile id.
	 * @return string
	 */
	function woo_ctr_sanitize_html_class( $id ) {
		return sanitize_html_class( is_string( $id ) || is_numeric( $id ) ? (string) $id : '' );
	}
}

if ( ! function_exists( 'woo_ctr_sanitize_css_color' ) ) {
	/**
	 * Allowlist safe CSS color values; return empty string when invalid.
	 *
	 * @param mixed $color Raw color.
	 * @return string
	 */
	function woo_ctr_sanitize_css_color( $color ) {
		if ( ! is_string( $color ) ) {
			return '';
		}
		$color = trim( $color );
		if ( '' === $color ) {
			return '';
		}
		if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color ) ) {
			return $color;
		}
		if ( preg_match( '/^rgba?\(\s*([0-9]{1,3}\s*,\s*){2}[0-9]{1,3}(\s*,\s*(0|1|0?\.\d+))?\s*\)$/', $color ) ) {
			return $color;
		}
		if ( preg_match( '/^hsla?\(\s*[0-9]{1,3}\s*,\s*[0-9]{1,3}%\s*,\s*[0-9]{1,3}%(\s*,\s*(0|1|0?\.\d+))?\s*\)$/', $color ) ) {
			return $color;
		}
		if ( preg_match( '/^[a-zA-Z]{1,20}$/', $color ) ) {
			return $color;
		}

		return '';
	}
}
