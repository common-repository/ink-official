( function( blocks, editor, element ) {
    var el = element.createElement;
    var RichText = editor.RichText;
    const iconEl = el('svg', { width: 20, height: 20 },
        el('path', { d: "M1.19351,4.13526H2.43137V13H1.19351Z" } ),
        el('path', { d: "M5.65246,4.13526H6.97971l2.729,6.26627h.02984V4.13526h1.07379V13H9.679L6.78594,6.19917H6.7411V13H5.65246Z" } ),
        el('path', { d: "M14.0486,4.02083h1.23786v4.536l2.6395-4.536h1.23772L17.09091,7.56161,19.19351,13H17.88126L16.196,8.5316l-.90958,1.43605V13H14.0486Z" } ),
        el('path', { d: "M1.19351,15h18v1h-18Z" } )
    );

    blocks.registerBlockType( 'ink-adddata/ink-adddata-ink', {
        title: 'Upload .ink File',
        icon: iconEl,
        category: 'inkblock',

        attributes: {
            content: {
                type: 'array',
                source: 'children',
                selector: 'div',
            },
        },

        edit: function( props ) {
            var content = props.attributes.content;
            function onChangeContent( newContent ) {
                props.setAttributes( { content: newContent } );
            }
            if(props.attributes.content == '') {
                var ink = wp.media({
                    title: 'Upload .ink File',
                    multiple: false,
                    library: {type: 'ink'},
                }).open()
                    .on('select', function (e) {
                        jQuery('body').prepend('<div class="loader_overlay"><div id="loader"></div></div>');
                        jQuery('#ink_import_btn').hide();
                        jQuery('#inkloader').show();
                        var uploaded_ink = ink.state().get('selection').first();
                        var ink_url = uploaded_ink.toJSON().url;						
						/*Changes*/									
						var filename = ink_url;									
						fileExt = filename.substring(filename.lastIndexOf('.')+1, filename.length) || filename;
						console.log(fileExt);
						if(fileExt !== 'ink') {							
							alert('Select a file with .ink extension');							
							jQuery('.loader_overlay').remove();											
							return false;									
						}									
						/*End Changes*/	
                        var data = {'ink_file_url': ink_url, action: 'ink_action'};
                        jQuery.post(inkObj.inkUrl, data, function (response) {
                            jQuery('#loader').show();
                            onChangeContent(response.content);
                            if (response.title) {
                                jQuery('#post-title-0').val(response.title);
                                jQuery('input[name="ink_post_title"]').val(response.title);
                            }
                            if (response.thumbnail_id) {
                                jQuery('input[name="_thumbnail_id"]').val(response.thumbnail_id);
                                jQuery('input[name="featured_media"]').val(response.thumbnail_id);
                                var imgsrc = '<img width="266" height="135" src="' + response.thumbnail_url + '" class="attachment-266x266 size-266x266">'
                                jQuery('#set-post-thumbnail').html(imgsrc);
                                jQuery('input[name="ink_post_featured_image"]').val(response.thumbnail_id);
                            }
                            if (response.ink_metatitle) {
                                jQuery('input[name="ink_metatitle"]').val(response.ink_metatitle);
                                jQuery('input[name="yoast_wpseo_title"]').val(response.ink_metatitle);
                            }
                            if (response.ink_metadesc) {
                                jQuery('#ink_metadesc').val(response.ink_metadesc);
                                jQuery('input[name="yoast_wpseo_metadesc"]').val(response.ink_metadesc);
                            }
                            if (response.ink_keywords) {
                                jQuery('#ink_keywords').val(response.ink_keywords);
                                jQuery('input[name="yoast_wpseo_focuskw"]').val(response.ink_keywords);
                            }
                            if (jQuery("#wpseo_meta").length == 0) {
                                jQuery("#ink_customfields").show();
                            }

                            jQuery('.loader_overlay').remove();
                        }, "json").error(function(){
                            jQuery('.loader_overlay').remove();
                            alert('There was some problem in processing the file, please try again. If the problem persists, please check file size and make sure server time is large enough to process the file.');
                        });

                    });
            }
        return el(
                RichText,
                {
                    tagName: 'div',
                    className: 'ink_content_area',
                    onChange: onChangeContent,
                    value: content,
                }
            );
        },

        save: function( props ) {
            return el( RichText.Content, {
                tagName: 'div', value: props.attributes.content,
            } );
        },
    } );
}(
    window.wp.blocks,
    window.wp.editor,
    window.wp.element
) );

