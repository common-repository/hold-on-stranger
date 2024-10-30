<?php
/**
 * Unistall Holdonstranger.
 *
 * @package Holdonstranger
 *
 * @file
 * If uninstall is not called from WordPress, exit.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

$hos_options = array( 'k2c_holdonstranger', 'k2c_holdonstranger_coupons' );
foreach ( $hos_options as $option_name ) {
	delete_option( $option_name );

	// For site options in Multisite.
	delete_site_option( $option_name );
}
