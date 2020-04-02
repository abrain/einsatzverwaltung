<?php

/**
 * Import functions from more recent WordPress versions, so we can use them without raising the minimum WordPress
 * version requirement. For now. I know it's bad.
 */

if (!function_exists('wp_timezone_string')) {
    /**
     * @since WordPress 5.3.0
     */
    function wp_timezone_string() {
        $timezone_string = get_option( 'timezone_string' );

        if ( $timezone_string ) {
            return $timezone_string;
        }

        $offset  = (float) get_option( 'gmt_offset' );
        $hours   = (int) $offset;
        $minutes = ( $offset - $hours );

        $sign      = ( $offset < 0 ) ? '-' : '+';
        $abs_hour  = abs( $hours );
        $abs_mins  = abs( $minutes * 60 );
        $tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

        return $tz_offset;
    }
}

if (!function_exists('wp_timezone')) {
    /**
     * @since WordPress 5.3.0
     */
    function wp_timezone() {
        return new DateTimeZone( wp_timezone_string() );
    }
}
