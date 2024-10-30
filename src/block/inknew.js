/**
 * BLOCK: my-block
 *
 * Registering a basic block with Gutenberg.
 * Simple block, renders and saves the same content without any interactivity.
 */

//  Import CSS.
import "./style.scss";
import "./editor.scss";


const __ = wp.i18n.__; // The __() for internationalization.
const registerBlockType = wp.blocks.registerBlockType; // The registerBlockType() to register blocks.
const { MediaUpload, PlainText, InnerBlocks } = wp.editor;
const { Button } = wp.components;
var RichText = wp.editor.RichText;
var el = wp.element.createElement;
const iconEl = el('svg', { width: 20, height: 20 },
    el('path', { d: "M1.19351,4.13526H2.43137V13H1.19351Z" } ),
    el('path', { d: "M5.65246,4.13526H6.97971l2.729,6.26627h.02984V4.13526h1.07379V13H9.679L6.78594,6.19917H6.7411V13H5.65246Z" } ),
    el('path', { d: "M14.0486,4.02083h1.23786v4.536l2.6395-4.536h1.23772L17.09091,7.56161,19.19351,13H17.88126L16.196,8.5316l-.90958,1.43605V13H14.0486Z" } ),
    el('path', { d: "M1.19351,15h18v1h-18Z" } )
);

jQuery(document).click(function(){
    var data_type = jQuery('.block-editor-block-list__block.is-selected').attr('data-type');
    if(data_type == "ink-adddata/ink-adddata-ink"){
        if(jQuery('.edit-post-header__settings button.components-icon-button').hasClass('is-toggled')){

        }else{
            jQuery('.edit-post-header__settings button.components-icon-button').click();
        }
    }
});

/**
 * Register: a Gutenberg Block.
 *
 * Registers a new block provided a unique name and an object defining its
 * behavior. Once registered, the block is made editor as an option to any
 * editor interface where blocks are implemented.
 *
 * @link https://wordpress.org/gutenberg/handbook/block-api/
 * @param  {string}   name     Block name.
 * @param  {Object}   settings Block settings.
 * @return {?WPBlock}          The block, if it has been successfully
 *                             registered; otherwise `undefined`.
 */

registerBlockType("ink-adddata/ink-adddata-ink", {
    title: 'Upload .ink File',
    description: 'INK Block allows you to import .INK file and convert content into blocks.',
    icon: iconEl,
    category: 'inkblock',

    attributes: {
        content: {
            type: 'array',
            source: 'children',
            selector: 'div',
        },
    },

    /**
     * The edit function describes the structure of your block in the context of the editor.
     * This represents what the editor will render when the block is used.
     *
     * The "edit" property must be a valid function.
     *
     * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
     */

    // The "edit" property must be a valid function.
    edit: props => {
        var content = props.attributes.content;
        function onChangeContent( newContentt ) {
            props.setAttributes( { content: newContentt } );
        };

        return (
          <div className={props.className}>
            <button
              className="add-more-file"
              onClick={() => {
                  var ink = wp.media({
                      title: 'Upload .ink File',
                      multiple: false,
                      library: {type: 'ink'},
                  }).open()
                      .on('select', function (e) {
                          jQuery('body').prepend('<div class="loader_overlay"><div id="loader"><img src="'+inkObj.pluginsUrl+'/ink-official/images/ink.gif" class="loader_img"/> </div></div>');
                          jQuery('#ink_import_btn').hide();
                          jQuery('#inkloader').show();
                          var uploaded_ink = ink.state().get('selection').first();
                          var ink_url = uploaded_ink.toJSON().url;						  						  /*Changes*/							
						  var filename = ink_url;							
						  var fileExt = filename.substring(filename.lastIndexOf('.')+1, filename.length) || filename;	
						  console.log(fileExt);
						  if(fileExt !== 'ink') {								
							  alert('The editor support only .ink files');								
							  jQuery('.loader_overlay').remove();								
							  return false;							
						  }							
						  /*End Changes*/						  
                          var data = {'ink_file_url': ink_url, action: 'ink_action'};

                          jQuery.post(inkObj.inkUrl, data, function (response) {
                              jQuery('#loader').show();
                              wp.data.dispatch( 'core/editor' ).resetBlocks([]);
                              let block = wp.blocks.createBlock( 'core/paragraph', { content: '' } );
                              var img_index = 0;
                              jQuery.each(response.guten_content, function( index, value ) {
                                  if(value != ""){
                                      //guten_images
                                      if (value.indexOf("INK_IMG_") >= 0){
                                          value = value.replace("</p>", "");
                                          var index_guten_images = "INK_IMG_"+img_index;
                                          block = wp.blocks.createBlock( 'core/image', { align: response.guten_images["INK_IMG_"+img_index]['align'], alt: response.guten_images["INK_IMG_"+img_index]['alt'], caption: response.guten_images["INK_IMG_"+img_index]['caption'], id: response.guten_images["INK_IMG_"+img_index]['id'], link: "", linkDestination: "none", url: response.guten_images["INK_IMG_"+img_index]['url'], height: response.guten_images["INK_IMG_"+img_index]['height'], width: response.guten_images["INK_IMG_"+img_index]['width'] } );

                                          wp.data.dispatch( 'core/editor' ).insertBlocks( block );
                                          img_index = img_index+1;
                                      }else if (value.indexOf("<h") >= 0){
                                          var res = value.split("</p>");
                                          if(res[0] != ''){
                                              block = wp.blocks.createBlock( 'core/paragraph', { content: res[0] } );
                                              wp.data.dispatch( 'core/editor' ).insertBlocks( block );

                                          }
										  if(res[1] != ""){
                                          if(res[1] && res[1].indexOf("<h") >= 0){
											var headings=res[1];
											  var hCount=headings.split('</h');	
											  var i;
										for (i = 0; i <hCount.length-1; i++) {

										
												  var htext=hCount[i];
												  var htextclean = htext.replace(/^\d+>/, '');
												  htextclean = htextclean.trim();
												  var afterH = htextclean.match(/<h\d+>(.+)/gm);
												  //console.log('log 1');
												  //console.log(afterH);
												  if(afterH.length){
													afterH = afterH[0];
												  }else{
													afterH = "";
												  }
												  //console.log('log 2');
												  //console.log(afterH);
												  var beforeH = htextclean.replace(/<h\d+>(.+)/gm, '');
												//console.log('log 3');
												//console.log(beforeH);
												//console.log('log 4');
												//console.log(htext);

												  var hwithNo = afterH.split('<')[1].split('>')[0];
												  //console.log('log 5');
												//console.log(hwithNo);
												  var onlyNo = hwithNo.replace("h", "");
												  //console.log('log 6');
												//console.log(onlyNo);
										 
												afterH = afterH.replace(/<?h?\d+>/, '');
												//console.log('log 7');
												//console.log(afterH);
												if(beforeH && beforeH.trim()){
													block = wp.blocks.createBlock( 'core/paragraph', { content: beforeH } );
													wp.data.dispatch( 'core/editor' ).insertBlocks( block );
												}
												if(afterH && afterH.trim()){
													block = wp.blocks.createBlock( 'core/heading', {level:onlyNo, content: afterH } );
													wp.data.dispatch( 'core/editor' ).insertBlocks( block );
												}
											}

										/*
                                              var heading_text = res[1];
                                              var h_level = res[1].split('<')[1].split('>')[0];
                                              heading_text = heading_text.split('<'+h_level+'>')[1].split('</'+h_level+'>')[0];
                                              h_level = h_level.replace("h", "");
                                              block = wp.blocks.createBlock( 'core/heading', { content: heading_text } );
                                              //block = wp.blocks.createBlock( 'core/heading', { content: heading_text, level:h_level } );
                                              wp.data.dispatch( 'core/editor' ).insertBlocks( block );
										*/
                                          }
										  }
                                      }else if (value.indexOf("ink_container") >= 0){
                                          value = value.replace("</p></p>", "");
                                          value = value.replace("</p>", "");
                                          value = value.replace("<p><p>", "");
                                          value = value.replace("<p>", "");
                                          value = value.replace('<div class="ink_container">', '');
                                          value = value.replace("</div>", "");
                                          block = wp.blocks.createBlock( "core/embed", { url: value, allowResponsive: true, className: "wp-embed-aspect-16-9 wp-has-aspect-ratio", providerNameSlug: "youtube", type: "video" } );
                                          //block = wp.blocks.createBlock( 'core/html', { content: value } );
                                          wp.data.dispatch( 'core/editor' ).insertBlocks( block );
                                      }else{
                                          value = value.replace("</p>", "");
                                          block = wp.blocks.createBlock( 'core/paragraph', { content: value } );
                                          wp.data.dispatch( 'core/editor' ).insertBlocks( block );
                                      }
                                  }
                              });

                              let block_ink = wp.blocks.createBlock( 'ink-adddata/ink-adddata-ink', {  } );
                              wp.data.dispatch( 'core/editor' ).insertBlocks( block_ink );
                              wp.data.dispatch( 'core/editor' ).editPost( { featured_media: response.thumbnail_id  } );
                              if (response.title) {
                                  jQuery('#post-title-0').val(response.title);
                                  jQuery('input[name="ink_post_title"]').val(response.title);
                                  jQuery('h3._snippet_head').html(response.title);
                                  wp.data.dispatch( 'core/editor' ).editPost( { title: response.title } );
                              }
                              if (response.thumbnail_id) {
                                  jQuery('input[name="_thumbnail_id"]').val(response.thumbnail_id);
                                  jQuery('input[name="featured_media"]').val(response.thumbnail_id);
                                  var imgsrc = '<img width="266" height="135" src="' + response.thumbnail_url + '" class="attachment-266x266 size-266x266">';
                                  jQuery('#set-post-thumbnail').html(imgsrc);
                              }
                              if (response.ink_metatitle) {
                                  jQuery('input[name="ink_metatitle"]').val(response.ink_metatitle);
                                  jQuery('input[name="yoast_wpseo_title"]').val(response.ink_metatitle);
                              }else{
                                  jQuery('input[name="ink_metatitle"]').val('');
                                  jQuery('input[name="yoast_wpseo_title"]').val('');
                              }
                              if (response.ink_metadesc) {
                                  jQuery('#ink_metadesc').val(response.ink_metadesc);
                                  jQuery('input[name="yoast_wpseo_metadesc"]').val(response.ink_metadesc);
                                  jQuery('span._snippet_desc').html(response.ink_metadesc);
                              }else{
                                  jQuery('#ink_metadesc').val('');
                                  jQuery('input[name="yoast_wpseo_metadesc"]').val('');
                                  jQuery('span._snippet_desc').html('');
                              }
                              if (response.ink_keywords) {
                                  jQuery('#ink_keywords').val(response.ink_keywords);
								  jQuery('#focus-keyword-input-metabox').val(response.ink_keywords);

                                  jQuery('input[name="yoast_wpseo_focuskw"]').val(response.ink_keywords);

                              }else{
                                  jQuery('#ink_keywords').val('');
                                  jQuery('input[name="yoast_wpseo_focuskw"]').val('');
                              }
                              if (jQuery("#wpseo_meta").length == 0) {
                                  jQuery("#ink_customfields").show();
                              }

                              jQuery('input[name="ink_import_data"]').val('1');

                              jQuery('.loader_overlay').remove();
                          }, "json").error(function(){
                              jQuery('.loader_overlay').remove();
                              alert('There was some problem in processing the file, please try again. If the problem persists, please check file size and make sure server time is large enough to process the file.');
                          });
                      });
              }}
            >
              +
            </button>
          </div>
        );
    },

    /**
     * The save function defines the way in which the different attributes should be combined
     * into the final markup, which is then serialized by Gutenberg into post_content.
     *
     * The "save" property must be specified and must be a valid function.
     *
     * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
     */

    save: props => {
        return (
            <div>
                <InnerBlocks.Content />
            </div>
        );
    }
});