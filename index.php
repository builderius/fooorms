<?php
/*
Plugin Name: Fooorms!
Plugin URI: http://builderius.io/
Description: This plugin allows creating REST endpoints for forms, creates entries from forms submissions and includes a custom email templates editor
Author: Vitalii Kiiko
Version: 1.0.0
Author URI: http://builderius.io/
Text Domain: fooorms
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'FooormsInit' ) ) {

	// Include the main class.
	if ( ! class_exists( 'Fooorms' ) ) {
		include_once dirname( __FILE__ ) . '/class-fooorms.php';
	}
	/**
	 * Main instance of Fooorms.
	 *
	 * Returns the main instance of Fooorms to prevent the need to use globals.
	 *
	 * @return Fooorms\Fooorms
	 * @since  1.0.0
	 */
	function FooormsInit() {
		$inst = Fooorms\Fooorms::instance();

		return $inst;
	}

    add_action( 'plugins_loaded', 'FooormsInit', 10 );
}