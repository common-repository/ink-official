<?php

if (!function_exists('ink_add_meta_box')) {
	function ink_add_meta_box() {
		$screens = array('post', 'page');
		foreach ($screens as $screen) {
			add_meta_box(
				'ink_customfields',
				__('INK Meta Data', 'ink_text'),
				'ink_meta_box_callback',
				$screen
			);
		}
	}
}
add_action('add_meta_boxes', 'ink_add_meta_box');

if (!function_exists('ink_meta_box_callback')) {
function ink_meta_box_callback($post) {
    wp_nonce_field('myplugin_meta_box', 'myplugin_meta_box_nonce');
    global $wpdb;
    $post_id = $post->ID;

    $ink_metatitle = get_post_meta($post_id, 'ink_metatitle', true);
    $ink_metadesc = get_post_meta($post_id, 'ink_metadesc', true);
    $ink_keywords = get_post_meta($post_id, 'ink_keywords', true);
    $ink_use_meta_data = get_post_meta($post_id, 'ink_use_meta_data', true);
    ?>



    <div class="ink-meta-data">
        <div class="google-article">
            <a href="<?php _e(get_permalink($post_id),'ink'); ?>" class="_title"><h3 class="_snippet_head"><?php echo $post->post_title; ?></h3>
                <br><div class="_cite"><cite><?php _e(get_permalink($post_id),'ink'); ?></cite></div>
            </a>
            <div class="_des"><span class="_date">
			<?php $InkGetDate=get_the_date(); 
					_e($InkGetDate,'ink');
			 ?> - </span>
			<span class="_snippet_desc"><?php _e($ink_metadesc, 'ink'); ?></span>
			</div>
        </div>
        <div class="FieldBox">
            <label>Meta Title: </label>
            <input type="text" class="form-control" id="ink_metatitle" name="ink_metatitle" value="<?php _e($ink_metatitle,'ink'); ?>">
        </div>
        <div class="FieldBox">
            <label>Meta Description:</label>
            <textarea class="form-control" id="ink_metadesc" name="ink_metadesc"><?php _e( $ink_metadesc,'ink'); ?></textarea>
        </div>
        <div class="FieldBox">
            <label>Meta Keywords:</label>
            <textarea class="form-control" id="ink_keywords" name="ink_keywords"><?php _e($ink_keywords,'ink'); ?></textarea>
        </div>
        <div class="FieldBox">
            <input type="checkbox" name="ink_use_meta_data" value="1" <?php if((int)$ink_use_meta_data == 1 ){ echo 'checked="checked"';} ?> /> Use INK Meta Data
        </div>
    </div>


<?php }

}

if (!function_exists('ink_save_meta_box_data')) {
function ink_save_meta_box_data($post_id){

	
if ( ! isset( $_POST['myplugin_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['myplugin_meta_box_nonce'], 'myplugin_meta_box' ) ) {
	return;
	
}

 
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (sanitize_text_field($_POST['post_type']) !==null && 'page' == $_POST['post_type']) {

        if (!current_user_can('edit_page', $post_id)) {
            return;
        }

    } else {

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

	if(!empty(@$_POST['ink_metatitle'])){
		$ink_metatitle = sanitize_text_field(@$_POST['ink_metatitle']);
	}
	if(!empty(@$_POST['ink_metadesc'])){
		$ink_metadesc = sanitize_text_field(@$_POST['ink_metadesc']);
	}
	if(!empty(@$_POST['ink_keywords'])){
		$ink_keywords = sanitize_text_field(@$_POST['ink_keywords']);
	}
	if(!empty(@$_POST['ink_use_meta_data'])){
		$ink_use_meta_data = sanitize_text_field(@$_POST['ink_use_meta_data']);
	}

    if(empty($ink_use_meta_data) || is_null($ink_use_meta_data)){
        update_post_meta($post_id, 'ink_use_meta_data', '0');
    }else{
        update_post_meta($post_id, 'ink_use_meta_data', '1');
    }

    update_post_meta($post_id, 'ink_metatitle', $ink_metatitle);
    update_post_meta($post_id, 'ink_metadesc', $ink_metadesc);
    update_post_meta($post_id, 'ink_keywords', $ink_keywords);

    /*
     * Yoast Seo
     */
    update_post_meta($post_id, 'yoast_wpseo_metadesc', $ink_metadesc);

}

}
add_action('save_post', 'ink_save_meta_box_data');




add_action('wp_head','ink_meta_header');

if (!function_exists('ink_meta_header')) {
function ink_meta_header() {
    $ink_metatitle = get_post_meta(get_the_ID(), 'ink_metatitle', true);
    $ink_metadesc = get_post_meta(get_the_ID(), 'ink_metadesc', true);
    $ink_keywords = get_post_meta(get_the_ID(), 'ink_keywords', true);
    $ink_use_meta_data = get_post_meta(get_the_ID(), 'ink_use_meta_data', true);
    if ( $ink_use_meta_data == 1 && !empty($ink_metatitle) ) {
        ?>
<!-- This site is optimized with the INK SEO plugin - https://inkforall.com -->
<meta name="title" content="<?php _e($ink_metatitle,'ink'); ?>" />
<meta name="description" content="<?php _e($ink_metadesc,'ink');?>" /> 
<meta name="keywords" content="<?php _e($ink_keywords,'ink');?>" />
<meta property="og:site_name" content="<?php _e(get_bloginfo(),'ink'); ?>" />
<meta property="og:title" content="<?php _e($ink_metatitle,'ink'); ?>" />
<meta property="og:description" content="<?php _e($ink_metadesc,'ink'); ?>" />
<meta name="twitter:description" content="<?php _e($ink_metadesc,'ink'); ?>" />
<meta name="twitter:title" content="<?php _e($ink_metatitle,'ink'); ?>" />
<!-- / INK SEO plugin -->
        <?php 

	
			
			
    } // echo get_post_meta(get_the_ID(), '_yoast_wpseo_metadesc', true);
  }
}
add_filter( 'wpseo_metadesc', 'ink_wpseo_metadesc');
if (!function_exists('ink_wpseo_metadesc')) {
function ink_wpseo_metadesc( $desc ) {
	    if ( get_post_meta(get_the_ID(), 'ink_use_meta_data', true) == 1 && !empty(get_post_meta(get_the_ID(), 'ink_metadesc', true))) {
        return get_post_meta(get_the_ID(), 'ink_metadesc', true);
    }
  return $desc;
 }
}
add_filter( 'wpseo_metakeywords', 'ink_wpseo_metakeywords');
if (!function_exists('ink_wpseo_metakeywords')) {
	function ink_wpseo_metakeywords( $keyw ) {
			if ( get_post_meta(get_the_ID(), 'ink_use_meta_data', true) == 1 && !empty(get_post_meta(get_the_ID(), 'ink_keywords', true))) {
			return get_post_meta(get_the_ID(), 'ink_keywords', true);
		}
	  return $keyw;
	 }
}
add_filter( 'wpseo_opengraph_title', 'ink_wpseo_opengraph_title');
if (!function_exists('ink_wpseo_opengraph_title')) {
	function ink_wpseo_opengraph_title( $title ) {
			if ( get_post_meta(get_the_ID(), 'ink_use_meta_data', true) == 1 && !empty(get_post_meta(get_the_ID(), 'ink_metatitle', true))) {
			return get_post_meta(get_the_ID(), 'ink_metatitle', true);
		}
	  return $title;
	 }
}
//add_action('wp_head', 'remove_all_wpseo_og', 1);

if (!function_exists('remove_all_wpseo_og')) {
	function remove_all_wpseo_og() {
	  remove_action( 'wpseo_head', array( $GLOBALS['wpseo_og'], 'opengraph' ), 30 );
	}
}
//add_filter( 'wpseo_locale', 'remove_yoast_meta_tags_if_ink_meta_used' );
//add_filter( 'wpseo_type', 'remove_yoast_meta_tags_if_ink_meta_used' );
add_filter( 'wpseo_metadesc', 'remove_yoast_meta_tags_if_ink_meta_used' );
add_filter( 'wpseo_social', 'remove_yoast_meta_tags_if_ink_meta_used' );
//add_filter( 'wpseo_robots', 'remove_yoast_meta_tags_if_ink_meta_used' );
//add_filter( 'wpseo_canonical', 'remove_yoast_meta_tags_if_ink_meta_used' );
add_filter( 'wpseo_metakeywords', 'remove_yoast_meta_tags_if_ink_meta_used' );
add_filter( 'wpseo_opengraph_title', 'remove_yoast_meta_tags_if_ink_meta_used' );
add_filter( 'wpseo_opengraph_site_name', 'remove_yoast_meta_tags_if_ink_meta_used' );
add_filter( 'wpseo_opengraph_desc', 'remove_yoast_meta_tags_if_ink_meta_used' );
add_filter( 'wpseo_twitter_title', 'remove_yoast_meta_tags_if_ink_meta_used' );
//add_filter( 'wpseo_opengraph_url', 'remove_yoast_meta_tags_if_ink_meta_used' );
//add_filter( 'wpseo_opengraph_admin', 'remove_yoast_meta_tags_if_ink_meta_used' );
//add_filter( 'wpseo_opengraph_author_facebook', 'remove_yoast_meta_tags_if_ink_meta_used' );
//add_filter( 'wpseo_opengraph_show_publish_date', 'remove_yoast_meta_tags_if_ink_meta_used' );
add_filter( 'wpseo_json_ld_output', 'remove_yoast_meta_tags_if_ink_meta_used' );


// Remove multiple Yoast meta tags

if (!function_exists('remove_yoast_meta_tags_if_ink_meta_used')) {
	function remove_yoast_meta_tags_if_ink_meta_used( $myfilter ) {
		$ink_use_meta_data = get_post_meta(get_the_ID(), 'ink_use_meta_data', true);
		$ink_use_title = get_post_meta(get_the_ID(), 'ink_metatitle', true);
		if ( $ink_use_meta_data == 1 && !empty($ink_use_title)) {
			return false;
		}
		return $myfilter;
	}
}