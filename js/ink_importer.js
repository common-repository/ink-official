jQuery(document).ready(function ($) {
	var post_title="";
	var post_thumbnail_id="";
	var post_thumbnail_url="";
    jQuery('#ink_import_btn').click(function (e) {
        e.preventDefault();
        var ink = wp.media({
            title: 'Upload .ink File',
            multiple: false,
			library: {type: 'application/octet-stream'},
        }).open().on('select', function (e) {
			jQuery('body').prepend('<div class="loader_overlay"><div id="loader"><img src="'+inkObj.pluginsUrl+'/ink-official/images/ink.gif" class="loader_img"/> </div></div>');
			var uploaded_ink = ink.state().get('selection').first();
			var ink_url = uploaded_ink.toJSON().url;						
			/*Changes*/			
			var filename = ink_url;			
			fileExt = filename.substring(filename.lastIndexOf('.')+1, filename.length) || filename;			
			if(fileExt !== 'ink') {
				alert('The editor support only .ink files');
				jQuery('.loader_overlay').remove();				
				return false;			
			}			
			/*End Changes*/			
			var data = {'ink_file_url': ink_url,action: 'ink_action'};
			jQuery.post(inkObj.inkUrl, data, function(response) {
				jQuery('#loader').show();
				tinymce.editors['content'].setContent('');
				wp.media.editor.insert(response.content);
				if(response.title){
                    jQuery('#title-prompt-text').text('');
                    jQuery('input[name="post_title"]').val(response.title);
                    jQuery('h3._snippet_head').html(response.title);
                    jQuery('[name="ink_metatitle"]').val(response.title);
				}
				if(response.thumbnail_id){
					jQuery('input[name="_thumbnail_id"]').val(response.thumbnail_id);
					jQuery('input[name="featured_media"]').val(response.thumbnail_id);
					var imgsrc='<img width="266" height="135" src="'+response.thumbnail_url+'" class="attachment-266x266 size-266x266">'
					jQuery('#set-post-thumbnail').html(imgsrc);
					jQuery('.editor-post-featured-image').html(imgsrc);
					jQuery('input[name="ink_post_featured_image"]').val(response.thumbnail_id);
				}else{
					jQuery('input[name="ink_post_featured_image"]').val('');
				}
				if(response.ink_metatitle){
					jQuery('input[name="yoast_wpseo_title"]').val(response.ink_metatitle);
				}else{
					jQuery('input[name="yoast_wpseo_title"]').val('');
				}
				if(response.ink_metadesc){
					jQuery('#ink_metadesc').val(response.ink_metadesc);
					jQuery('input[name="yoast_wpseo_metadesc"]').val(response.ink_metadesc);
                    jQuery('span._snippet_desc').html(response.ink_metadesc);
				}else{
					jQuery('#ink_metadesc').val('');
					jQuery('input[name="yoast_wpseo_metadesc"]').val('');
					jQuery('span._snippet_desc').html('');
				}
				if(response.ink_keywords){
					jQuery('#ink_keywords').val(response.ink_keywords);
					jQuery('input[name="yoast_wpseo_focuskw"]').val(response.ink_keywords);
				}else{
					jQuery('#ink_keywords').val('');
					jQuery('input[name="yoast_wpseo_focuskw"]').val('');
				}
				if(jQuery("#wpseo_meta").length == 0) {
					jQuery("#ink_customfields").show();
				}
				jQuery('input[name="ink_import_data"]').val('1');
				jQuery('.loader_overlay').remove();
		}, "json").error(function(){
			jQuery('.loader_overlay').remove();
			alert('There was some problem in processing the file, please try again. If the problem persists, please check file size and make sure server time is large enough to process the file.');
		});
		});
	});

	jQuery(document).on('keyup', '[name="ink_metatitle"]', function(){
		jQuery('.ink-meta-data').find('._snippet_head').text(jQuery(this).val());
	});
	jQuery(document).on('keyup', '[name="ink_metadesc"]', function(){
		jQuery('.ink-meta-data').find('._snippet_desc').text(jQuery(this).val());
	});
});
if (document.getElementById("inktooltiptext") !== null){
var x = document.getElementById("inktooltiptext").getAttribute("data");
document.getElementById("inktooltiptext").innerHTML = x; }