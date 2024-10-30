<?php
/**
 * Blocks Initializer
 *
 * Enqueue CSS/JS of all the blocks.
 *
 * @since   1.0.0
 * @package GTS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
set_time_limit(0);

add_action('admin_enqueue_scripts', 'ink_include_media_button_js_file');
add_action('media_buttons', 'add_ink_import_btn');
add_filter('upload_mimes', 'allow_ink_file_to_upload_in_wp');
add_action( 'wp_ajax_ink_action', 'ink_action' );

//include('parsedown.php');
require('vendor/autoload.php');
include('metadata.php');
include('ink_settings.php');

/**
 * cleanWpInkImageFileName()
 *
 * @param mixed $string
 * @return
 */
if (!function_exists('cleanWpInkImageFileName')) {
	function cleanWpInkImageFileName($string) {
		$string = preg_replace('/\s+/', '-', $string);
		$string = str_replace(array('"', '#', '$', '%', '&', "'", '(', ')', '*', '+', ',', '\\', '/', ':', ';', '<', '=', '>', '?', '@', '[', ']', '^', '_', '`', '{', '|', '}', '~'), '', $string);
		return $string;
	}
}
/**
 * Enqueue Gutenberg block assets for both frontend + backend.
 *
 * @since 1.0.0
 */
if (!function_exists('ink_block_assets')) {
	function ink_block_assets() {
		// Styles.
		wp_enqueue_style( 'ink-style-css', plugins_url( 'dist/blocks.style.build.css', dirname( __FILE__ ) ) );

		wp_enqueue_style('font-awesome', plugins_url( 'lib/css/fontawesome.min.css', dirname( __FILE__ ) ) );
		wp_enqueue_style('bootstrap-style', plugins_url( 'lib/css/bootstrap.min.css', dirname( __FILE__ ) ) );

		wp_enqueue_script('bootstrap-script',  plugins_url( 'lib/js/bootstrap.min.js', dirname( __FILE__ ) ), array('jquery'));
	}
}
/**
 * Enqueue Gutenberg block assets for backend editor.
 *
 * `wp-blocks`: includes block type registration and related functions.
 * `wp-element`: includes the WordPress Element abstraction for describing the structure of your blocks.
 * `wp-i18n`: To internationalize the block's text.
 *
 * @since 1.0.0
 */
if (!function_exists('ink_editor_assets')) {
	function ink_editor_assets() {
		// Scripts.
		wp_enqueue_script(
			'ink-block-js', // Handle.
			plugins_url( '/dist/blocks.buildnew.js', dirname( __FILE__ ) ), // Block.build.js: We register the block here. Built with Webpack.
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ), // Dependencies, defined above.
			true // Enqueue the script in the footer.
		);

		// Styles.
		wp_enqueue_style(
			'ink-block-editor-css', // Handle.
			plugins_url( 'dist/blocks.editor.build.css', dirname( __FILE__ ) ), // Block editor CSS.
			array( 'wp-edit-blocks' ) // Dependency to include the CSS after it.
		);
	}
 }
// Hook: Editor assets.
add_action( 'enqueue_block_editor_assets', 'ink_editor_assets' );

if (!function_exists('ink_add_block_category')) {
	function ink_add_block_category( $categories, $post ) {
		$categories[]=array(
			'slug'  => 'inkblock',
			'title' => 'INK Block',
		);
		return $categories;
	}
}
add_filter( 'block_categories_all', 'ink_add_block_category', 10, 2);


add_filter( 'plugin_row_meta', 'ink_plugin_row_meta', 10, 2 );

if (!function_exists('ink_plugin_row_meta')) {
	function ink_plugin_row_meta( $actions, $plugin_file ) {

		if ( strpos( $plugin_file, 'ink-content-block.php' ) !== false ) {

			$action_links = array(
				'visitsite' => '<a href="https://seo.app/l0SIDLV3g" target="_blank">Visit Plugin Site</a>',
				'visitseoblog' => 'Found a Bug? Email: <a href="mailto:support@inkco.co" target="_blank">support@inkco.co</a>'
			);

			$actions = array_merge( $actions, $action_links );
		}
		return $actions;
	}
}

$pluginDirName = plugin_dir_url( __FILE__ );
$pluginDirName = preg_replace('/(src\/)?$/', '', $pluginDirName);
if (!function_exists('add_ink_import_btn')) {
	function add_ink_import_btn() {
		global $pluginDirName;
		echo '<a href="javascript:void(0)" id="ink_import_btn" class="button"><img src="'.$pluginDirName.'images/ink.png" />Import .ink File</a>';

		echo '<div id="inkloader" style="display:none"><img src="'.$pluginDirName.'images/progress.gif" /></div>';
	}
}
if (!function_exists('allow_ink_file_to_upload_in_wp')) {
	function allow_ink_file_to_upload_in_wp( $existing_mimes=array() ) {
		$existing_mimes['ink'] = 'ink';
		return $existing_mimes;
	}
}

if (!function_exists('ink_include_media_button_js_file')) {
	function ink_include_media_button_js_file() {
		global $pluginDirName;
		wp_enqueue_media();
		wp_enqueue_style('ink_css', $pluginDirName.'css/style.css');
		wp_register_script('ink_media_button', $pluginDirName.'js/ink_importer.js', array('jquery'), '1.6', true);
		wp_enqueue_script('ink_media_button');
		$pluginUrl = array( 'inkUrl' => admin_url( 'admin-ajax.php'), 'pluginsUrl' => plugins_url(),'adminUrl' => admin_url(),'inkSecurity'  => wp_create_nonce( 'ink-security-nonce' ), );
		wp_localize_script( 'ink_media_button', 'inkObj', $pluginUrl );

	}
}
if (!function_exists('getInkFileId')) {
	function getInkFileId($fileUrl) {
		global $wpdb;
		$attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $fileUrl )); 
		return @$attachment[0]; 
	}
}
# Save images in wordpress media library 

if (!function_exists('save_ink_block_image')) {
function save_ink_block_image($block) {
    $alt = addslashes($block['alt']);
    $cap = addslashes($block['caption']);
    $imgData=array();
    $rand=rand();
    $upload_dir = wp_upload_dir();
    $filename        = $block['file'];

	// Sometimes we have URL in $filename, so extract the filename
	if($filename) {
		$filenameArr = explode("/", $filename);
	
		if(count($filenameArr) > 1) {
			$filename = $filenameArr[count($filenameArr) - 1];
		}
	}

    $from_url = false;
        $file_directory = "";
    if( wp_mkdir_p( $upload_dir['path'] ) ) {
        $file = $upload_dir['path'] . '/' . $filename;
                $file_directory = $upload_dir['path'] . '/';
                $fileurl = $upload_dir['url'] . '/' . $filename;
        }else{
        $file = $upload_dir['basedir'] . '/' . $filename;
                $file_directory = $upload_dir['basedir'] . '/';
                $fileurl = $upload_dir['url'] . '/' . $filename;
	    }
	
        $iLoop = 1;
        while(file_exists($file)){
					
				 $wp_filetype = wp_check_filetype( $filename, null );
					$attachment = array(
						'post_mime_type' => $wp_filetype['type'],
						'post_title'     => sanitize_file_name($filename),
						'post_content'   => "",
						'post_status'    => 'inherit',
						'post_excerpt'  => $cap,
					);
				   
				$attach_id = wp_insert_attachment( $attachment,$file  );
				$attach_data = wp_generate_attachment_metadata($attach_id, $file);
				wp_update_attachment_metadata($attach_id, $attach_data);
				update_post_meta($attach_id, '_wp_attachment_image_alt', $alt);
				$imagePath=wp_get_attachment_metadata( $attach_id );
				$imgData['url'] = wp_get_attachment_url($attach_id);
				$imgData['path']=$imagePath;
				$imgData['id']=$attach_id;
				@$imgData['align']=@$block['align'];
				$imgData['alt']=$block['alt'];
				$imgData['caption']=$block['caption'];

				if(empty($block['size'])){
					$width="100%";
				}else{
				$width=$block['size']."%";
				}
				$cal_width = $width;
                $cal_height = 'auto';

				if (strpos($width, '%') !== false) {
                    $width_img = $imgData['path']['width'];
                    $height_img = $imgData['path']['height'];
                    $cal_width = ((int)$width / 100) * $width_img;
                    $cal_height = 'auto';
                    $cal_width = (int)$cal_width;
                }
				$editor_width = 650;
                $img_width = 650;
				if (strpos($width, '%') !== false) {
                    $img_width = ((int)$width / 100) * $editor_width;
                }
				if($cal_width < $img_width){
                    $img_width = $cal_width;
                }
				if($img_width == '0'){
					$img_width = '650';	
				}
				
				$imgData['width']=$img_width;
				$imgData['original_width']=$width;
				$imgData['img_width']=$img_width;

				return $imgData;   
					
	}
                       
}
}
# Save images in wordpress media library 
if (!function_exists('save_ink_image')) {
function save_ink_image( $base64_img,$alt,$cap,$title) {
    $alt = addslashes($alt);
    $cap = addslashes($cap);
//      $title = addslashes($title);
    $imgData=array();
    $rand=rand();
        $name = $title;
    $upload_dir = wp_upload_dir();
    $filename        = $name . '.jpg';
    $from_url = false;
        $extension = ".jpg";
    if(stristr($base64_img,'data:image/jpg;base64') !== FALSE || stristr($base64_img,'data:image/jpeg;base64')!== FALSE ){
        $filename        = $name . '.jpg';
                $extension = ".jpg";
    }else if(stristr($base64_img,'data:image/png;base64') !== FALSE){
        $filename        = $name . '.png';
                $extension = ".png";
    }else{
        $from_url = true;
        $i = $base64_img;
        $filename = basename($i);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $extension = "." . $ext;               
        if(!in_array($ext, 'png', 'jpg', 'jpeg'))
            $ext = 'png';
        $data = file_get_contents($i);
        $base64_img = 'data:image/'.$ext.';base64,' . base64_encode($data);
    }
    $img             = str_replace( 'data:image/jpg;base64', '', $base64_img );
    $img             = str_replace( 'data:image/jpeg;base64', '', $img );
    $img             = str_replace( 'data:image/png;base64', '', $img );
    $img             = str_replace( ' ', '+', $img );
    $decoded         = base64_decode( $img );
    if(!$from_url) {
        $filename = stripslashes($filename);
        $filename = cleanWpInkImageFileName($filename);
    }
        $file_directory = "";
    if( wp_mkdir_p( $upload_dir['path'] ) ) {
        $file = $upload_dir['path'] . '/' . $filename;
                $file_directory = $upload_dir['path'] . '/';
                $fileurl = $upload_dir['url'] . '/' . $filename;
        }else{
        $file = $upload_dir['basedir'] . '/' . $filename;
                $file_directory = $upload_dir['basedir'] . '/';
                $fileurl = $upload_dir['url'] . '/' . $filename;
    }
        $iLoop = 1;
        if(file_exists($file)) {$upload_file = file_put_contents($file . '.ink' , '' );$upload_file = file_put_contents($file . '.ink' , $decoded );
        if (filesize($file . '.ink') >= filesize($file)) {
                $attachmentid = attachment_url_to_postid($fileurl);
                $alt_text = get_post_meta($attachmentid , '_wp_attachment_image_alt', true);
                        if($attachmentid != '0' && $alt_text = $alt || $attachmentid != '0' && empty($alt_text) && empty($alt)){
        $imagePath=wp_get_attachment_metadata( $attachmentid ); 
    $imgData['url'] = wp_get_attachment_url($attachmentid);
    $imgData['path']=$imagePath;
    $imgData['id']=$attachmentid;
    return $imgData;
                } else { 
                        $iLoop = 1;
                while(file_exists($file))
                {
                        $file = $file_directory . $name."-".$iLoop++ . $extension;     
                }
                $upload_file = file_put_contents($file , $decoded );
                      
                        }
       
        } else {
                 $iLoop = 1;
                while(file_exists($file))
                {
                        $file = $file_directory . $name."-".$iLoop++ . $extension;     
                }
                $upload_file = file_put_contents($file , $decoded );
                } } else {
                $upload_file = file_put_contents($file , $decoded );
                }
       
    $wp_filetype = wp_check_filetype( $filename, null );
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name($filename),
        'post_content'   => "",
        'post_status'    => 'inherit',
        'post_excerpt'  => $cap,
    );
       
    $attach_id = wp_insert_attachment( $attachment,$file  );
    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    wp_update_attachment_metadata($attach_id, $attach_data);
    update_post_meta($attach_id, '_wp_attachment_image_alt', $alt);
    $imagePath=wp_get_attachment_metadata( $attach_id );
    $imgData['url'] = wp_get_attachment_url($attach_id);
    $imgData['path']=$imagePath;
    $imgData['id']=$attach_id;
    return $imgData;
}
}
# Get String Position  
if (!function_exists('inkStrposX')) {
	function inkStrposX($haystack, $needle, $n = 0){
		$offset = 0;
		for ($i = 0; $i < $n; $i++) {
			$pos = strpos($haystack, $needle, $offset);

			if ($pos !== false) {
				$offset = $pos + strlen($needle);
			} else {
				return false;
			}
		}

		return $offset;
	}
}
# Add Text Properties on text
if (!function_exists('gtnText')) {
	function gtnText($child) {
	  $txt=$child['text'];
		  if(@$child['bold']){
				$txt="<strong>".$txt."</strong>";
			}
		  if(@$child['italic']){
				$txt="<em>".$txt."</em>";
			}
		  if(@$child['underlined']){
				$txt="<u>".$txt."</u>";
			}
		return $txt;
	}
}
# Covert INK Content to Php Array
if (!function_exists('ink_guten')) {
function ink_guten($block){
		$gtnck=array();
				switch ($block['type']){
					  case "heading-two":
							  foreach($block['children'] as $child){
									if(@$child['type']=='link'){
										foreach(@$child['children'] as $child2){
												 $childTxt.=gtnText($child2);
											}
										$blockTxt.="<a href='".$child['href']."'>".$childTxt."</a>";

									}else{
										 if(empty($child['text'])) continue;								
											  $blockTxt.=gtnText($child);				
										}
								}
							if(!empty($blockTxt)){
									$gtnck['guten']="<h2>".$blockTxt."</h2>";
									$gtnck['ck']="<h2>".$blockTxt."</h2>";
									return $gtnck; 
							}
							  break;
					  case "heading-three":
							  foreach($block['children'] as $child){
									if(@$child['type']=='link'){
										foreach($child['children'] as $child2){
												 $childTxt.=gtnText($child2);
											}
										$blockTxt.="<a href='".$child['href']."'>".$childTxt."</a>";

									}else{
										 if(empty($child['text'])) continue;								
											  @$blockTxt.=gtnText($child);				
										}
								}
							if(!empty($blockTxt)){
									$gtnck['guten']="<h3>".$blockTxt."</h3>";
									$gtnck['ck']="<h3>".$blockTxt."</h3>";
									return $gtnck;
							}
							  break;
					  case "heading-four":
							  foreach($block['children'] as $child){
									if($child['type']=='link'){
										foreach($child['children'] as $child2){
												 $childTxt.=gtnText($child2);
											}
										$blockTxt.="<a href='".$child['href']."'>".$childTxt."</a>";

									}else{
										 if(empty($child['text'])) continue;								
											  $blockTxt.=gtnText($child);				
										}
								}
							if(!empty($blockTxt)){
									$gtnck['guten']="<h4>".$blockTxt."</h4>";
									$gtnck['ck']="<h4>".$blockTxt."</h4>";
									return $gtnck;
							}
							  break;
					  case "bulleted-list":
								$blockTxt="<ul>";
								  foreach($block['children'] as $child){
									  $liTxt="";
											foreach($child['children'] as $child2){
												if($child2['type']=='link'){
													$childTxt=gtnText($child2);
													$liTxt.="<a href='".$child2['href']."'>".$childTxt."</a>";
												}else{
													$liTxt.=gtnText($child2);
												}
																								 
											}
												  $blockTxt.="<li>".$liTxt."</li>";			
												  			
								  }												
								$blockTxt.="</ul>";
							   	$gtnck['guten']=$blockTxt;
								$gtnck['ck']=$blockTxt;
								return $gtnck;
							  break;
					 case "numbered-list":
								$blockTxt="<ol>";
								  foreach($block['children'] as $child){
									  $liTxt="";
											foreach($child['children'] as $child2){
												if($child2['type']=='link'){
													$childTxt=gtnText($child2);
													$liTxt.="<a href='".$child2['href']."'>".$childTxt."</a>";
												}else{
													$liTxt.=gtnText($child2);
												}
																								 
											}
												  $blockTxt.="<li>".$liTxt."</li>";				
								  }												
							   $blockTxt.="</ol>";
							   	$gtnck['guten']=$blockTxt;
								$gtnck['ck']=$blockTxt;
								return $gtnck;
							  break;
					case "numbered-list":
								$blockTxt="<ol>";
								  foreach($block['children'] as $child){
										    foreach($child['children'] as $child2){
												if($child2['type']=='link'){
													$childTxt=gtnText($child2);
													$liTxt.="<a href='".$child2['href']."'>".$childTxt."</a>";
												}else{
													$liTxt.=gtnText($child2);
												}
																								 
											}
										$blockTxt.="<li>".$liTxt."</li>";				
								  }												
							   $blockTxt.="</ol>";
							   	$gtnck['guten']=$blockTxt;
								$gtnck['ck']=$blockTxt;
								return $gtnck;
							  break;
					case "quote":
								$blockTxt="";
								$blockTxtCk="";
								  foreach($block['children'] as $child){
										if($child['type']=='q'){
											$blockTxt.=gtnText($child['children'][0]);
											$blockTxtCk.=gtnText($child['children'][0]);
										}elseif($child['type']=='credit'){
											$blockTxt.="<cite>".gtnText($child['children'][0])."</cite>";
											$blockTxtCk.="<cite>".gtnText($child['children'][0])."</cite>";
										}

								  }	
							   	$gtnck['guten']=$blockTxt."</p></blockquote>";
								$gtnck['ck']="<blockquote>".$blockTxtCk."</blockquote>";
								return $gtnck;
							  break;
				     case "paragraph":
						 $blockTxt="";
						 $blockTxtCk="";
						 $childTxt="";
							foreach($block['children'] as $child){
								  if(empty($child['text'])) continue;

								  if(@$child['type']=='link'){
									  if(strpos($child['href'], 'youtube') > 0 && get_option('ink_yotube_settings')){

										  preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $child['href'], $match);
											$youtube_id = $match[1];

										  $blockTxt.='<div class="ink_container">'.$child['href'].'</div>';

										  $blockTxtCk.='<div class="ink_container"><iframe  class="ink_video" src="//www.youtube.com/embed/'.$youtube_id.'" frameborder="0" allowfullscreen></iframe></div>';

									  }else{
												$childTxt="";
												foreach($child['children'] as $child2){
													 $childTxt.=gtnText($child2);
												}

										$blockTxt.="<a href='".$child['href']."'>".$childTxt."</a>";
										$blockTxtCk.="<a href='".$child['href']."'>".$childTxt."</a>";
									  }
								  }elseif($child['text']){
										$blockTxt.=gtnText($child);
										$blockTxtCk.=gtnText($child);
								  }
								  
							}
							if(!empty($blockTxt)){
								$gtnck['guten']=$blockTxt."</p>";
								$gtnck['ck']="<p>".$blockTxtCk."</p>";
								return $gtnck;
							}
						break;
				}
	if(!empty($gtnck)){
		return $gtnck;
	}else{
	   return false;
	}
}
}
# INK to php array
if (!function_exists('ink_action')) {
function ink_action() {
		
		if (empty(esc_url_raw($_POST['ink_file_url']))) {
        return;
    } else {
			if(sanitize_text_field($_POST['fileName'])=='yes'){
						$wpdir = wp_upload_dir();
						$fileUrlAS =$wpdir['url'] . '/' .sanitize_file_name($_POST['ink_file_url']);
						$fileUrl = $wpdir['url'] . '/' .sanitize_file_name($_POST['ink_file_url']);
						$filepath =$wpdir['path'] . '/' .sanitize_file_name($_POST['ink_file_url']);
			}else{

						$fileUrlAS = esc_url_raw($_POST['ink_file_url']);
						$fileUrl = esc_url_raw($_POST['ink_file_url']);
			}
	}
			
    try {
        $rights="";
        $data=array();
        $images_data=array();
        $images = array();
		$filename = basename($fileUrl);
		$upload_dir = wp_upload_dir();
		$comFile=explode("uploads/",$fileUrlAS);
		$afterUpload=$comFile[1];
		$comAs=explode("uploads/",$fileUrl);
		$beforeUpload=$comAs[0];
		$ab=explode("/",$comFile[1]);
		$wpM=$upload_dir['basedir'].'uploads/';
		$fileUrl=$upload_dir['basedir'].'/'.$filename;

		if(!empty($ab[0]) &&!empty($ab[1])){
			$rights=$ab[0].'/'.$ab[1];
			$fileUrl=$upload_dir['basedir'].'/'.$rights.'/'.$filename;
			$wpM=$upload_dir['basedir'].'uploads/'.$rights;
		}

		$upldir = str_replace("/$filename","","$fileUrl");
		$tempdir = get_temp_dir();
		$time=time();
		$zip = new ZipArchive;
if ($zip->open("$fileUrl") === TRUE) {

    $zip->extractTo($tempdir.'/ink-'.$time.'/');
    $zip->close();
	$newstring = substr($filename, -7);
	if (substr($newstring, 0, 1) === '-' && is_numeric(substr($newstring, 1, 2))) {
	$foldername = substr($filename, 0, -7);
		$foldername = $foldername . '.ink';
	}
	$newstring1 = substr($filename, -6);
	if (substr($newstring1, 0, 1) === '-' && is_numeric(substr($newstring1, 1, 2))) {
	$foldername = substr($filename, 0, -6);
		$foldername = $foldername . '.ink';
	}
		
	@$imgdirc = $tempdir.'/ink-'.$time .'/'. $foldername .'/resources/';
	if(!is_dir($imgdirc)) { $imgdirc = $tempdir.'/ink-'.$time. '/resources/'; }
	@$files = scandir ( @$imgdirc );
	if(!empty($files)){
		foreach ( $files as $file ) {
			if ($file != "." && $file != ".."){
				rename($imgdirc . $file, $upldir . '/' . pathinfo($file, PATHINFO_BASENAME));
			}
		}
	}

	@$inkMetafile=$tempdir.'/ink-'.$time. '/'. $foldername .'/meta.json';
	if (!file_exists($inkMetafile)) {
		$inkMetafile = $tempdir.'/ink-'.$time. '/meta.json';
	}
	   $meta_data= file_get_contents($inkMetafile);
	   $metaContent = json_decode($meta_data, true);
	   $metaTitle=urldecode($metaContent['app']['metatitle']);
	   $metaDesc=urldecode($metaContent['app']['metadesc']);
	   $keywords=$metaContent['keywords'];
	   $kwData=$metaContent['kwData'];
	   $metakeywords="";
	   if(is_array($keywords)){
			$metakeywords=implode(",",$keywords);
		}
		$primary_keyword="";
		$heighNo=(int)0;
		if(is_array($kwData)){	
				foreach($kwData as $kw){
					$kwd=explode(':',$kw);
					$kwscore=(int)$kwd[1];
					$kwname=$kwd[0];

					if(empty($primary_keyword)){
							$primary_keyword=$kwname;
							$heighNo=(int)$kwscore;

					}elseif($kwscore>$heighNo){
						$primary_keyword=$kwname;
						$heighNo=$kwscore;
					}
				}
		}


	@$inkContentfile=$tempdir.'/ink-'.$time. '/'. $foldername .'/content.json';
	if (!file_exists($inkContentfile)) {
		$inkContentfile = $tempdir.'/ink-'.$time. '/content.json';
	}
	

	$inkContent= file_get_contents($inkContentfile);
    $ink_content = json_decode($inkContent, true);
	$htmlContent="<p";
	$gtnContent=array();
	$gtnContent[]="";
	$gtnImages=array();
	$images_Rep=array();
	@$images[] = @$wpImgUrl;
	$i=0;
	$prevBlock="";
	@$images_guten_Rep[$image_data_with][] = @$image_data;
	foreach($ink_content as $block){
		if($block['type']=='heading-one'){
			$postTitle=$block['children'][0]['text'];
		}elseif($block['type']=='quote'){
			$count=count($gtnContent);
			$l_value=$gtnContent[$count-1];
			$gtnContent[$count-1]=$l_value."<blockquote>";
			$gtnContent[]=ink_guten($block)['guten'];
			$htmlContent.="\n".ink_guten($block)['ck'];
			//exit;
		}elseif($block['type']=='image'){
			$imgData=save_ink_block_image($block);
			  $imgDataWith = 'INK_IMG_'.$i.'_'.time();
              @$gtnImages[$imgDataWith][] = @$image_data;

			  $gtnImages['INK_IMG_'.$i] = array(
                    'align' => $imgData['align'],
                    'alt' => $imgData['alt'],
                    'caption' =>$imgData['caption'],
                    'id' => $imgData['id'],
                    'url' => $imgData['url'],
                    'height' => '',
                    'width' => $imgData['width'],
                    'original_width' => $imgData['width'],
                );

			  @$images_Rep[@$image_data_with][] = '[caption id="attachment_'.$imgData['id'].'" align="align'.$imgData['align'].'" width="'.(int)$imgData['img_width'].'"]<img class="wp-image-'.$imgData['id'].' align'.$imgData['align'].'" src="'.$imgData['url'].'" alt="'.$imgData['alt'].'" width="'.(int)$imgData['img_width'].'"/> '.$imgData['caption'].' [/caption]';


			  $htmlContent.="\n".'<p>[caption id="attachment_'.$imgData['id'].'" align="align'.$imgData['align'].'" width="'.(int)$imgData['img_width'].'"]<img class="wp-image-'.$imgData['id'].' align'.$imgData['align'].'" src="'.$imgData['url'].'" alt="'.$imgData['alt'].'" width="'.(int)$imgData['img_width'].'"/> '.$imgData['caption'].' [/caption]</p>';

			  $gtnContent[]=$imgDataWith."</p>";

			  $images[] = $imgData;

			$i++;
		}elseif(ink_guten($block)){				
				$gtnBlockContent=ink_guten($block)['guten'];
				if(strpos($gtnBlockContent,'ink_container')!== false){
					$gtnContent[]=$gtnBlockContent;
				}elseif($prevBlock=='paragraph' && $block['type']=='paragraph'){
					$count=count($gtnContent);
					$lastBlockValue=$gtnContent[$count-1];
					if(strpos($lastBlockValue,'ink_container')!== false){
						$gtnContent[]=$gtnBlockContent;
					}elseif(strpos($lastBlockValue,'INK_IMG_')!== false){
						$gtnContent[]=$gtnBlockContent;
					}else{
						$lastBlockValue=str_replace('</p>','',$lastBlockValue);
						$gtnContent[$count-1]=$lastBlockValue."<br><br>".$gtnBlockContent;
					}
				}else{
					$gtnContent[]=$gtnBlockContent;
				}
				$htmlContent.="\n".ink_guten($block)['ck'];

		}
	$prevBlock=$block['type'];
	}
	
  
	  /*
	 * Image Above 500 Code
	 */
	$data['thumbnail_id'] = 0;
	if(count($images) > 0){
		foreach ($images as $rowImg){
			//list($width, $height) = getimagesize($rowImg['path']);

			@$width = @$rowImg['path']['width'];
			@$height = @$rowImg['path']['height'];
			if($width > 500){
				$data['thumbnail_id'] = $rowImg['id'];
				$data['thumbnail_url'] = $rowImg['url'];
				break;
			}
		}
	}
		$inkId=getInkFileId($fileUrlAS);
		wp_delete_attachment($inkId,true);
		@unlink($tempdir.'/ink-'.$time);

		if(sanitize_text_field($_POST['fileName'])=='yes'){
			@unlink($filepath);
		}


	$data['title'] = $postTitle;
	$data['content'] = $htmlContent;
	$data['guten_content'] =$gtnContent;
	$data['guten_images'] = $gtnImages;
	$data['images_Rep'] = $images_Rep;
	$data['ink_metadesc'] = trim($metaDesc);
	$data['ink_metatitle'] = trim($metaTitle);
	$data['ink_keywords'] = trim($metakeywords);
	if(!empty($primary_keyword)){
		$data['primary_keyword'] = sanitize_title($primary_keyword);
	}else{
		$data['primary_keyword']=sanitize_title($postTitle);
	}
        /*
         * Yoast plugin
         */

        $data['yoast_wpseo_metadesc'] = trim($metaDesc);

        //echo json_encode($data);
		echo wp_send_json($data);

        wp_die();
	
	
} else {
		
		
   //     $fileUrl = substr($fileUrl, strpos($fileUrl, 'wp-content'));

        $fp = fopen($fileUrl, "r");
        
		if ( !$fp ) {

		}
        
		$content = fread($fp, filesize($fileUrl));
	//	$content = file_get_contents($fp);
//$content = "This is a test text" . $fileUrl;
        preg_match_all('/<img[^>]+>/i',$content, $imgTags);

        $images_Rep = array();
        $images_guten_Rep = array();
        if($imgTags){
            foreach ($imgTags[0] as $iKey => $image){
                $image_data = $image;
                $image_data_with = 'INK_IMG_'.$iKey.'_'.time();
                $content = str_replace($image, $image_data_with, $content);
                $images_Rep[$image_data_with][] = $image_data;
                $images_guten_Rep[$image_data_with][] = $image_data;

                preg_match('/src="([^"]+)/i',$image, $imgsrc);
                preg_match('/alt="([^"]+)/i',$image, $imgAlt);
                preg_match('/caption="([^"]+)/i',$image, $imgCap);
				preg_match('/title="([^"]+)/i',$image, $imgtitle);

                preg_match('/align="([^"]+)/i',$image, $imgAlign);
                preg_match('/width="([^"]+)/i',$image, $imgWidth);

                $src = str_ireplace( 'src="', '',  $imgsrc[0]);

                $alt = str_ireplace( 'alt="', '',  $imgAlt[0]);
                $alt = htmlspecialchars_decode($alt);


                $cap = str_ireplace( 'caption="', '',  $imgCap[0]);
                $cap = htmlspecialchars_decode($cap);
				
				$title = str_ireplace( 'title="', '',  $imgtitle[0]);
                $title = htmlspecialchars_decode($title);
				if($title=='')
                {
						$content_as=substr($alt,0,50);
	$lastoccur = strrpos($content_as," ");
	$content_as = substr($content_as,0,$lastoccur);
    $content_as=str_replace(" ", "-", $content_as);
	$content_as = preg_replace("/[^a-z0-9\_\-\.]/i", '', str_replace("--", "-", $content_as));
    $name = $content_as;
    $name = urldecode($name);
    $name=str_replace('.', '', $name);
    $name=str_replace('%', '', $name);
                    $title = $name;
                }


                $width = str_ireplace( 'width="', '',  $imgWidth[0]);
                $align = str_ireplace( 'align="', '',  $imgAlign[0]);

                $wpImgUrl = save_ink_image($src,$alt,$cap,$title);
                $images[] = $wpImgUrl;
                $images_data[] = $wpImgUrl['id'];

                $cal_width = $width;
                $cal_height = 'auto';

				if (strpos($width, '%') !== false) {
                    $width_img = $wpImgUrl['path']['width'];
                    $height_img = $wpImgUrl['path']['height'];
                    $cal_width = ((int)$width / 100) * $width_img;
                    $cal_height = 'auto';
                    $cal_width = (int)$cal_width;
                }
				
                $editor_width = 610;
                $img_width = 610;
                
				if (strpos($width, '%') !== false) {
                    $img_width = ((int)$width / 100) * $editor_width;
                }

                if($cal_width < $img_width){
                    $img_width = $cal_width;
                }

                if($alt=='')
                {
                    $alt = ' ';
                }

				if($cap == ''){
                    $images_Rep[$image_data_with][] = '[caption id="attachment_'.$wpImgUrl['id'].'" align="align'.$align.'" width="'.(int)$img_width.'"]<img class="wp-image-'.$wpImgUrl['id'].' align'.$align.'" src="'.$wpImgUrl['url'].'" alt="'.$alt.'" width="'.(int)$img_width.'"/> '.$cap.' [/caption]';
                }else{
                    $images_Rep[$image_data_with][] = '[caption id="attachment_'.$wpImgUrl['id'].'" align="align'.$align.'" width="'.(int)$img_width.'"]<img class="wp-image-'.$wpImgUrl['id'].' '.$align.'" src="'.$wpImgUrl['url'].'" alt="'.$alt.'" width="'.(int)$img_width.'"/> '.$cap.'[/caption]';
                }


                $images_guten_Rep['INK_IMG_'.$iKey] = array(
                    'align' => $align,
                    'alt' => $alt,
                    'caption' => $cap,
                    'id' => $wpImgUrl['id'],
                    'url' => $wpImgUrl['url'],
                    'url' => $wpImgUrl['url'],
                    'height' => '',
                    'width' => (int)$img_width,
                    'original_width' => $width,
                );
            }
        }
        $Parsedown = new Parsedown();

        $meta_info=$Parsedown->text($content);

        ///////=============Meta Desc==============/////////
        $metaDesc = "";
        if(strpos($meta_info, "metadesc:")){
            $meta_desc = substr($meta_info, strpos($meta_info, "metadesc:") + 1);
            $meta_desc = explode('&quot;', $meta_desc);
            $metaDesc = urldecode($meta_desc[1]);
        }
        ///////=============Meta Title==============/////////
        $metaTitle = "";
        if(strpos($meta_info, "metatitle:")) {
            $meta_title = substr($meta_info, strpos($meta_info, "metatitle:") + 1);
            $meta_title = explode('&quot;', $meta_title);
            $metaTitle = urldecode($meta_title[1]);
        }

        ///////=============Keywords==============/////////
        $meta_keywords = "";
        if(strpos($meta_info, "keywords :")){
            $meta_keywords = substr($meta_info, strpos($meta_info, "keywords :") + 10);
        }else {
            $meta_keywords = substr($meta_info, strpos($meta_info, "keywords:") + 9);
        }
        $keyWordsEnd = inkStrposX($meta_keywords, 'title', 1);
        $metakeywords = substr($meta_keywords,0,($keyWordsEnd-5));
        $metakeywords = strip_tags($metakeywords);
        $metakeywords = urldecode($metakeywords);
        $metakeywords = str_replace('&quot;','',$metakeywords);
        $metakeywords = str_replace("'",'',$metakeywords);
        $metakeywords = urldecode($metakeywords);
        $metakeywords = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $metakeywords);
        $metakeywords = str_replace(array("\r\n","\r","\n"),', ',trim($metakeywords));

        $content = preg_replace('/outlinenotes: (\'|")[^\'"]+(\'|")/', 'outlinenotes: ""', $content);
        $content = preg_replace('/ignored: [^\s\n]+/', 'ignored: ', $content);

        $content=substr_replace($content, '', 0, inkStrposX($content, '---', 2)+3);


        $htmlContent=$Parsedown->text($content);
        $strtTitle=strpos($htmlContent,"<h1>");
        $endTitle=strpos($htmlContent,"</h1>");
        $postTitle=substr($htmlContent,$strtTitle+4,$endTitle-4);
        $htmlContent=substr_replace($htmlContent, '', $strtTitle, $endTitle+5);
		$htmlContent=str_replace(' id="h-a-beige-beret-for-the-mad-hatter"',"",$htmlContent);

        if(strpos($htmlContent,"<img")!==false && strpos($htmlContent,"<img")<3){
            $imgStrt=strpos($htmlContent,"<img");
            $imgEnd=strpos($htmlContent,">");
            $imageTag=substr($htmlContent,$imgStrt,$imgEnd);
            preg_match('/src="([^"]+)/i',$htmlContent, $imgUrl);
            $thumbnail_url = str_ireplace( 'src="', '',  $imgUrl[0]);
            $thumbnail_id=$images_data[0];
            $htmlContent=substr_replace($htmlContent, '', $imgStrt, $imgEnd);
        }
        $inkYoutube=get_option('ink_yotube_settings');

        /*
         * Image Above 500 Code
         */
        $data['thumbnail_id'] = 0;
        if(count($images) > 0){
            foreach ($images as $rowImg){
                //list($width, $height) = getimagesize($rowImg['path']);

                $width = $rowImg['path']['width'];
                $height = $rowImg['path']['height'];
                if($width > 500){
                    $data['thumbnail_id'] = $rowImg['id'];
                    $data['thumbnail_url'] = $rowImg['url'];
                    break;
                }
            }
        }

        $htmlContent = '<p'.$htmlContent;
        $htmlContent = str_replace("<p></p>", "", $htmlContent);
        $htmlContent_guten = $htmlContent;
		if($inkYoutube){
        	$htmlContent = preg_replace("/\s*<a href=\"(https:\/\/)*[wW]*\.youtube\.com\/watch\?v=([a-zA-Z0-9\-_]+)(&amp;)*[A-Za-z=\.]*\">(https:\/\/)*[wW]*\.youtube\.com\/watch\?v=[a-zA-Z0-9\-_]+(&amp;)*[A-Za-z=\.]*<\/a>/i","<div class=\"ink_container\"><iframe  class=\"ink_video\" src=\"//www.youtube.com/embed/$2\" frameborder=\"0\" allowfullscreen></iframe></div>", $htmlContent);
	        $htmlContent = preg_replace("/\s*<a href=\"https:\/\/youtu\.be\/([a-zA-Z0-9\-_]+)\">[a-zA-Z]*:\/\/youtu\.be\/([a-zA-Z0-9\-_]+)<\/a>/i","<div class=\"ink_container\"><iframe class=\"ink_video\" src=\"//www.youtube.com/embed/$2\" frameborder=\"0\" allowfullscreen></iframe></div>", $htmlContent);
		
			$htmlContent_guten = preg_replace("/\s*<a href=\"(https:\/\/)*[wW]*\.youtube\.com\/watch\?v=([a-zA-Z0-9\-_]+)(&amp;)*[A-Za-z=\.]*\">(https:\/\/)*[wW]*\.youtube\.com\/watch\?v=[a-zA-Z0-9\-_]+(&amp;)*[A-Za-z=\.]*<\/a>/i","<div class=\"ink_container\">https://www.youtube.com/watch?v=$2</div>", $htmlContent_guten);
			$htmlContent_guten = preg_replace("/\s*<a href=\"https:\/\/youtu\.be\/([a-zA-Z0-9\-_]+)\">[a-zA-Z]*:\/\/youtu\.be\/([a-zA-Z0-9\-_]+)<\/a>/i","<div class=\"ink_container\">https://www.youtube.com/watch?v=$2</div>", $htmlContent_guten);
		
		}
        if($images_Rep){
            foreach($images_Rep as $iKey => $value){
                $htmlContent = str_replace($iKey, $value[1], $htmlContent);
            }
        }
$htmlContent_guten = str_replace("<h", "<replaceme><h", $htmlContent_guten);
$htmlContent_guten = str_replace("<p>", "<replaceme>", $htmlContent_guten);
$htmlContent_guten = str_replace(" <ul", "<ul", $htmlContent_guten);
$htmlContent_guten = str_replace("<ul", "<replaceme><ul", $htmlContent_guten);
$htmlContent_guten = str_replace(" <ol", "<ol", $htmlContent_guten);
$htmlContent_guten = str_replace("<ol", "<replaceme><ol", $htmlContent_guten);
$htmlContent_guten = str_replace("\n", '', $htmlContent_guten);
        $data['title'] = $postTitle;
        $data['content'] = $htmlContent;
        $data['guten_content'] = explode("<replaceme>", $htmlContent_guten);
        $data['guten_images'] = $images_guten_Rep;
        $data['images_Rep'] = $images_Rep;
        $data['ink_metadesc'] = trim($metaDesc);
        $data['ink_metatitle'] = trim($metaTitle);
        $data['ink_keywords'] = trim($metakeywords);

        /*
         * Yoast plugin
         */

        $data['yoast_wpseo_metadesc'] = trim($metaDesc);

        //echo json_encode($data);
		echo wp_send_json($data);

        wp_die();
    } 
	}
    catch(Exception $exception)
    {
        _e($exception->getTraceAsString(),'ink');
    }
}

}

/* INK File Generation */
if (!function_exists('ink_link')) {
	function ink_link($post_ID) {
		$link = '<div class="downloadinkbtn"><a class="downloadink" href="/wp-admin/admin-ajax.php?id=' . $post_ID . '&action=generate_ink"><img border="0" alt="Generate .ink" src="' . plugins_url("ink-official/images/ink_download.svg") . '" width="20" height="20"></a></div>';
		return $link;
	}
}
if (!function_exists('inkfiledownload_head')){
	function inkfiledownload_head($defaults) {
		$defaults['download_ink'] = '<div class="inktooltip">Download .ink file<div class="inkpointer"><span id="inktooltiptext" class="inktooltiptext" data="Generate and download an .ink file from this WordPress post so you can easily optimize it in inkforall.com"></span></div></div>';
		return $defaults;
	}
}
if (!function_exists('inkfiledownload_content')){
	function inkfiledownload_content($column_name, $post_ID) {
			   if( 'download_ink' == $column_name ) {  
			   echo ink_link($post_ID);
			   }
	}
}
add_filter('manage_posts_columns', 'inkfiledownload_head');
add_action('manage_posts_custom_column', 'inkfiledownload_content', 11, 2);

if (!function_exists('generate_ink')){
function generate_ink(){
$upload_dir = wp_upload_dir();
 $offset = sanitize_text_field($_GET['offset']);
 $id= sanitize_text_field($_GET['id']);
if (get_post_meta($id, 'ink_use_meta_data', true ) ){$keyword = get_post_meta( $id, 'ink_keywords', true );} else {$keyword = get_post_meta( $id, 'yoast_wpseo_focuskw', true );}
$authorid = get_post_field( 'post_author', $id);
$inkcontent = "";
$inkcontent .= "---";
$inkcontent .= "\nauthor : '" . get_the_author_meta('display_name', $authorid) . "'\n";
$inkcontent .= "app :\n - name: INK\n - articleId: '7644aae6-9606-d8d5-1463-43e82432e34c'\n - version: 1.193.0\n - metadesc: ''\n - metatitle: ''\n - outlinenotes: ''\n - kwData: \n - ignored: \n - kwLocation: \n";
$inkcontent .= "date :  " . date("Y/m/d H:i:s") . "\n";
$inkcontent .= "keywords :\n";
$inkcontent .= " - '" . $keyword . "'\n";
$inkcontent .= "title : |\n";
$inkcontent .= " '" . get_the_title($id) . "'\n";
$inkcontent .= "---\n";
$inkcontent .= "\n\n\n";
$inkcontent .= get_the_title($id) . "\n";
$inkcontent .= "=========================================================\n";
if ( has_post_thumbnail($id) ) {
	$turl = get_the_post_thumbnail_url($id, 'full');
	$options = array(
  'http'=>array(
    'method'=>"GET",
    'header'=>"Accept-language: en\r\n" .
              "Cookie: foo=bar\r\n" .  // check function.stream-context-create on php.net
              "User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n" 
  )
);

$context = stream_context_create($options);
	$b64fimg1 = base64_encode(file_get_contents($turl, false, $context));
	$alt = get_post_meta ( $id, '_wp_attachment_image_alt', true );
	$tcaption = get_the_post_thumbnail_caption($id);
	if(empty($alt)){$alt = $tcaption;}
	$path      = parse_url($turl, PHP_URL_PATH);
	$extension = pathinfo($path, PATHINFO_EXTENSION);
	$filename  = pathinfo($path, PATHINFO_FILENAME);
	if($extension='png'){$baseprez = 'data:image/png;base64,';} elseif ($extension='jpeg'){$baseprez = 'data:image/jpeg;base64,';} elseif ($extension='webp'){$baseprez = 'data:image/webp;base64,';} else {$baseprez = 'data:image/jpg;base64,';};
	if($b64fimg1){
	$fimg = '<img src="'.$baseprez.''.$b64fimg1.'" align="none" width="100%" title="'.$filename.'" caption="'.$tcaption.'" alt="'.$alt.'" />('. $turl .')';
$inkcontent .= $fimg;
	}
}
	$content_post = get_post($id);
$content = $content_post->post_content;
$content_as = $content_post->post_content;
$content = preg_replace('/<!--(.|s)*?-->/', '', $content);
$content = preg_replace_callback(
			'#<figure ([^>]+?)><img src=[\'"]?([^\'">]+)[\'"]?([^>]*)><figcaption>([^>]+?)<\/figcaption><\/figure>#',
            function($txt)
            {
                $str = $txt[0];
                $str1 = $txt[1];
                $str2 = $txt[2];
                $str3 = $txt[3];
				$str4 = $txt[4];
				$str5 = $txt[5];
                if(false !== strpos($str, 'disable-lazy'))
                {
                    return $str;
                }
                if(0 === strpos($str3, 'about:blank'))
                {
                    return $str;
                }
				preg_match('/class="([^"]+)/i',$str1, $imgclass);
				$imagclass11 = str_ireplace( 'class="', '',  $imgclass[0]);
				preg_match('/width="([^"]+)/i',$str3, $imgWidth);
				$imgWidth11 = str_ireplace( 'width="', '',  $imgWidth[0]);
				preg_match('/alt="([^"]+)/i',$str3, $imgalt);
				$imgalt11 = str_ireplace( 'alt="', '',  $imgalt[0]);
				if($str2){
					$b64fimg1 = base64_encode(file_get_contents($str2));
					$path      = parse_url($str2, PHP_URL_PATH);
					$extension = pathinfo($path, PATHINFO_EXTENSION);
					$filename  = pathinfo($path, PATHINFO_FILENAME);
					if($extension='png'){$basepre = 'data:image/png;base64,';} elseif ($extension='jpeg'){$basepre = 'data:image/jpeg;base64,';} elseif ($extension='webp'){$basepre = 'data:image/webp;base64,';} else {$basepre = 'data:image/jpg;base64,';};
					
					if ($b64fimg1){return '<img src="'.$basepre.''.$b64fimg1.'" align="none" width="100%" class="'.$imagclass11.' '.$imgWidth11.'" alt="'.$imgalt11.'" caption="'.$str4.'" title="'.$filename.'">';} else {return '<img src="'.$str2.'" align="none" width="100%" class="'.$imagclass11.''.$imgWidth11.'" alt="'.$imgalt11.'" caption="'.$str4.'" title="'.$filename.'">';}
                } else {return $str;}
            }, $content);
	
$content = preg_replace_callback(
			'#<p><img src=[\'"]?([^\'">]+)[\'"]?([^>]*)><br><em>([^>]+?)<\/em><\/p>#',
            function($txt)
            {
                $str = $txt[0];
                $str1 = $txt[1];
                $str2 = $txt[2];
                $str3 = $txt[3];
				$str4 = $txt[4];
				$str5 = $txt[5];
                if(false !== strpos($str, 'disable-lazy'))
                {
                    return $str;
                }
                if(0 === strpos($str3, 'about:blank'))
                {
                    return $str;
                }
//				preg_match('/class="([^"]+)/i',$str1, $imgclass);
//				$imagclass11 = str_ireplace( 'class="', '',  $imgclass[0]);
//				preg_match('/width="([^"]+)/i',$str3, $imgWidth);
//				$imgWidth11 = str_ireplace( 'width="', '',  $imgWidth[0]);
				preg_match('/alt="([^"]+)/i',$str2, $imgalt);
				$imgalt11 = str_ireplace( 'alt="', '',  $imgalt[0]);
				if($str1){
					$b64fimg1 = base64_encode(file_get_contents($str1));
					$path      = parse_url($str1, PHP_URL_PATH);
					$extension = pathinfo($path, PATHINFO_EXTENSION);
					$filename  = pathinfo($path, PATHINFO_FILENAME);
					if($extension='png'){$basepre = 'data:image/png;base64,';} elseif ($extension='jpeg'){$basepre = 'data:image/jpeg;base64,';} elseif ($extension='webp'){$basepre = 'data:image/webp;base64,';} else {$basepre = 'data:image/jpg;base64,';};
					
					if ($b64fimg1){return '<img src="'.$basepre.''.$b64fimg1.'" align="none" width="100%" alt="'.$imgalt11.'" caption="'.$str3.'" title="'.$filename.'">';} else {return '<img src="'.$str1.'" align="none" width="100%" alt="'.$imgalt11.'" caption="'.$str3.'" title="'.$filename.'">';}
                } else {return $str;}
            }, $content);
	
$content = preg_replace_callback(
			'#<figure ([^>]+?)><div ([^>]+?)>([^>]+?)<\/div><\/figure>#',
            function($txt)
            {
                $str = $txt[0];
                $str1 = $txt[1];
                $str2 = $txt[2];
                $str3 = $txt[3];
                if(false !== strpos($str, 'disable-lazy'))
                {
                    return $str;
                }
                if(0 === strpos($str3, 'about:blank'))
                {
                    return $str;
                }
				if(strpos($str3, 'youtu') !== false){
					$ytube = "[$str3] ($str3)";
					$ytube = preg_replace('/\s+/', '', $ytube);
					return "$ytube";
                } else {return $str;}
            }, $content);	
	
//$content = preg_replace("/\n\n/", '', $content);
//$content = apply_filters('the_content', $content);
//$content = get_the_content($id);
  if (preg_match_all('#<iframe(?:.*)?\ssrc=["|\']https:\/\/www\.youtube\.com\/embed\/(.*)["|\']\s(?:.*)><\/iframe>#Usm', $content, $matches, PREG_SET_ORDER)) {
	foreach ($matches as $match) {
	  $content = str_replace($match[0], 'https://youtu.be/'.$match[1], $content);
	}
  }
$inkcontent .= $content;

$postTitle = get_the_title($id);

$ast=substr($postTitle,0,20);
$cont=$ast;
$lastoccur = strrpos($cont," ");
$filename = substr($cont,0,$lastoccur);

$filename = sanitize_title($filename);

if(empty($filename) || $filename=='Untitled'){
	    $strtTitle=strpos($content_as,"<h1>");
        $endTitle=strpos($content_as,"</h1>");
        $postTitle=substr($content_as,$strtTitle+4,$endTitle-4);
		$postTitle=substr($postTitle,0,20);

		$cont=$postTitle;
		$lastoccur = strrpos($cont," ");
		$filename = substr($cont,0,$lastoccur);

		$filename = sanitize_title($filename);
}
if(empty($filename) || $filename=='Untitled'){
		$strtTitle=strpos($content_as,"<h2>");
        $endTitle=strpos($content_as,"</h2>");
        $postTitle=substr($content_as,$strtTitle+4,$endTitle-4);
		$postTitle=substr($postTitle,0,20);
		$cont=$postTitle;
		$lastoccur = strrpos($cont," ");
		$filename = substr($cont,0,$lastoccur);
		$filename = sanitize_title($filename);
}

    if( wp_mkdir_p( $upload_dir['path'] ) ) {
        $file = $upload_dir['path'] . '/wp-' . $filename . '.ink';
	}else{
        $file = $upload_dir['basedir'] . '/wp-' . $filename . '.ink';
    }
$filew = fopen("$file", "w") or die("Unable to open file!");
	fwrite($filew,  $inkcontent. "\n");
fclose($filew);
	header('Content-Description: File Transfer');
header('Content-Disposition: attachment; filename='.basename($file));
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file));
header("Content-Type: text/plain");
readfile($file);
	die();
 }
}
add_action('wp_ajax_generate_ink', 'generate_ink');
/* Ink File Generation End */