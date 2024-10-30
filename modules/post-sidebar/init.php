<?php
/**
 * Gutenburg Sidebar Js enque
 */
 if (!function_exists('ink_sidebar_assests')){
	function ink_sidebar_assests(){
	wp_enqueue_script(
			'ink-sidebar-js',
			plugins_url( '/post-sidebar/build/index.js?v=4.07', dirname( __FILE__ ) ), 
			array( 'wp-plugins','wp-edit-post','wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ), 
			true 
		);
	}
 }
add_action( 'enqueue_block_editor_assets', 'ink_sidebar_assests' );

/**
 * Gutenburg Sidebar style sheet enque
 */
add_action('admin_enqueue_scripts', function() {

	global $pagenow, $post_type;
	 wp_enqueue_style(
        'ink-sidebar-css',
        plugins_url( '/post-sidebar/css/sidebar.css?v=4.06', dirname( __FILE__ ) )
    );
});

/**
 * Upload INK File in wp-upload directory
 */
add_action( 'wp_ajax_upload_ink', 'upload_ink' );

 if (!function_exists('upload_ink')){
	function upload_ink() {

			if ( ! check_ajax_referer( 'ink-security-nonce', 'inkSecurity', false ) ) {
					wp_send_json_error( 'Error' );
					wp_die();
			 }


		$inkFile = $_FILES['file'];
		$wordpress_upload_dir = wp_upload_dir();
		$new_file_path = $wordpress_upload_dir['path'] . '/' . sanitize_file_name($inkFile['name']);

		if( move_uploaded_file($inkFile['tmp_name'],$new_file_path ) ) {
			wp_die();
		}else{
				_e("File Not Uploaded",'ink');
				wp_die();
		}   
	}
}


?>