<?php
/*
 * Plugin Name: DMaps
 * Version: 1.2
 * Plugin URI: http://wp.dinamiko.com/demos/dmaps
 * Description: DMaps creates a custom metabox in post types including custom post types. Also you can create maps via [dmaps] shortcode.
 * Author: Emili Castells
 * Author URI: http://www.dinamiko.com
 * Requires at least: 3.8
 * Tested up to: 4.0
 *
 * Text Domain: dmaps
 * Domain Path: /lang/
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once( 'includes/class-dmaps.php' );
require_once( 'includes/class-dmaps-settings.php' );
require_once( 'includes/lib/class-dmaps-admin-api.php' );
require_once( 'includes/lib/class-dmaps-admin-utils.php' );

function DMaps () {

	$instance = DMaps::instance( __FILE__, '1.2' );
	
	if( is_null( $instance->settings ) ) {
		$instance->settings = DMaps_Settings::instance( $instance );
	}	

	return $instance;

}

DMaps();

