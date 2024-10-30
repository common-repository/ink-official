<?php
/*
Plugin Name: INK Official
Plugin URL: https://inkforall.com
Description: INK Block plugin allows you to import .ink files to wordpress posts / pages.
Version: 4.1.2
Text Domain: ink
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block Initializer.
 */
require_once plugin_dir_path( __FILE__ ) . 'src/init.php';
require_once plugin_dir_path( __FILE__ ) . 'modules/post-sidebar/init.php';

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'ink_action_links' );
/*
Add Settings link 
*/
if (!function_exists('ink_action_links')){
	function ink_action_links ( $links ) {
		$links_ink = array(
			'<a href="' . admin_url( 'options-general.php?page=inkimport' ) . '">Settings</a>',
		);
		return array_merge( $links, $links_ink );
	}
}
if (!function_exists('upload_ink_mimes')){
	function upload_ink_mimes($mimes = array()) {
		$mimes['ink'] = "text/plain";
		$mimes['ink'] = "application/zip";

		return $mimes;
	}
}
add_action('upload_mimes', 'upload_ink_mimes');


add_filter( 'wp_check_filetype_and_ext', 'ink_mimes', 99, 4 );

if (!function_exists('ink_mimes')){
	function ink_mimes( $check, $file, $filename, $mimes ) {
		if ( empty( $check['ext'] ) && empty( $check['type'] ) ) {
			$multi_mimes = [ [ 'ink' => 'text/plain' ], [ 'ink' => 'application/zip' ] ];

			foreach( $multi_mimes as $mime ) {
				remove_filter( 'wp_check_filetype_and_ext', 'wpse323750_multi_mimes', 99, 4 );
				$check = wp_check_filetype_and_ext( $file, $filename, $mime );
				add_filter( 'wp_check_filetype_and_ext', 'wpse323750_multi_mimes', 99, 4 );
				if ( ! empty( $check['ext'] ) ||  ! empty( $check['type'] ) ) {
					return $check;
				}
			}
		}
		return $check;
	}
}