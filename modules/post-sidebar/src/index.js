const { registerPlugin } = wp.plugins;
const { __ } = wp.i18n;
const { Fragment } = wp.element;
const { PluginSidebarMoreMenuItem, PluginSidebar } = wp.editPost;
const { PanelBody, PanelRow, ToggleControl } = wp.components;
const { compose } = wp.compose;
const search = window.location.search;
const params = new URLSearchParams(search);
const postId = params.get('post');
var el = wp.element.createElement;
const inkIcon = el('img', {src:"../wp-content/plugins/ink-official/modules/post-sidebar/img/logo.svg", className:"ink_icon"},)
import React from 'react'
import Dropzone from 'react-dropzone-uploader'
import 'react-dropzone-uploader/dist/styles.css'
const AjaxCall = () => {
      jQuery('body').prepend('<div class="loader_overlay"><div id="loader"><img src="'+inkObj.pluginsUrl+'/ink-official/images/ink.gif" class="loader_img"/> </div></div>');
      jQuery('#ink_import_btn').hide();
      jQuery('#inkloader').show();
    var data = {'post_id': postId, action: 'get_response'};
    jQuery.post(inkObj.inkUrl, data, function (response) {
      jQuery('.loader_overlay').show();
      console.log(response);
      jQuery('.loader_overlay').remove();
  }, "json").error(function(){
      jQuery('.loader_overlay').remove();
      alert('There was some problem in processing the file, please try again. If the problem persists, please check file size and make sure server time is large enough to process the file.');
  });

}
console.log('Version 4.0.3');
const exportCall = () => {
    if(postId !==null){
        console.log('Post Id Exist');
        window.location.href=inkObj.adminUrl+"/admin-ajax.php?id="+postId+"&action=generate_ink";
    }else{
        jQuery(".editor-post-save-draft" ).trigger( "click" );
        jQuery('body').prepend('<div class="loader_overlay"><div id="loader"><img src="'+inkObj.pluginsUrl+'/ink-official/images/ink.gif" class="loader_img"/> </div></div>');
        jQuery('.loader_overlay').show();
        setTimeout(
            function(){
                var pid=jQuery("#post_ID").val();    
                console.log(pid);
                jQuery('.loader_overlay').remove();
                window.location.href=inkObj.adminUrl+"/admin-ajax.php?id="+pid+"&action=generate_ink";
            }, 1000);

            
    }
    
    
}



const addGutenburgContent =(ink_url_as)=>{
    console.log('before Ajax');
    var data = {'ink_file_url': ink_url_as, action: 'ink_action',fileName:'yes'};
     setTimeout(
            function(){
                console.log('Ajax started');
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
                                var caps=response.guten_images["INK_IMG_"+img_index]['caption'];
                                    //caps= text.replace("\'s", "'s");

                                block = wp.blocks.createBlock( 'core/image', { 
                                    align: response.guten_images["INK_IMG_"+img_index]['align'],
                                     alt: response.guten_images["INK_IMG_"+img_index]['alt'], 
                                     caption:caps, 
                                     id: response.guten_images["INK_IMG_"+img_index]['id'], 
                                     link: "", 
                                     linkDestination: "none",
                                     url: response.guten_images["INK_IMG_"+img_index]['url'], 
                                     height: response.guten_images["INK_IMG_"+img_index]['height'], 
                                     width: response.guten_images["INK_IMG_"+img_index]['width'] 
                                    } );

                                wp.data.dispatch( 'core/editor' ).insertBlocks( block );
                                img_index = img_index+1;
                            }else if (value.indexOf("<h") >= 0){
                                var res = value.split("</p>");
                                    res[1]=value;
                                if(res[1] != ""){
                                if(res[1] && res[1].indexOf("<h") >= 0){
                                    var headings=res[1];
                                    var hCount=headings.split('</h');	
                                    var i;
                            for (i = 0; i <hCount.length-1; i++) {
                                        var htext=hCount[i];

                                        // replace line break in heading
                                        htext = htext.replace(/(\r\n|\r|\n)/gi, '');
                                        
                                        var htextclean = htext.replace(/^\d+>/, '');
                                        
                                        

                                        htextclean = htextclean.trim();
                                        var afterH = htextclean.match(/<h\d+>(.+)/gm);

                                        
                                        if(afterH?.length){
                                            afterH = afterH[0];
                                        }else{
                                            afterH = "";
                                        }
                                        var beforeH = htextclean.replace(/<h\d+>(.+)/gm, '');

                                        var hwithNo = afterH && afterH.split('<')[1].split('>')[0];
                                        var onlyNo = hwithNo.replace("h", "");
                                    afterH = afterH.replace(/<?h?\d+>/, '');
                                    if(beforeH && beforeH.trim()){
                                        block = wp.blocks.createBlock( 'core/paragraph', { content: beforeH } );
                                        wp.data.dispatch( 'core/editor' ).insertBlocks( block );
                                    }
                                    if(afterH && afterH.trim()){
                                        var onlyNo=parseInt(onlyNo);
                                        block = wp.blocks.createBlock( 'core/heading', {level:onlyNo, content: afterH } );
                                        wp.data.dispatch( 'core/editor' ).insertBlocks( block );
                                    }
                                }

                            
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
                            }else if (value.indexOf("<ul>") >= 0){
                                value = value.replace("</p>", "");
                                value = value.replace("<ul>", "");
                                value = value.replace("</ul>", "");
                                block = wp.blocks.createBlock( 'core/list', { 
                                  nodeName:'UL',
                                  values: value } );
                                wp.data.dispatch( 'core/editor' ).insertBlocks( block );
                          }else if (value.indexOf("<ol>") >= 0){
                              value = value.replace("</p>", "");
                              value = value.replace("<ol>", "");
                              value = value.replace("</ol>", "");
                              block = wp.blocks.createBlock( 'core/list', { 
                                nodeName:'OL',
                                values: value } );
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
                    if (response.primary_keyword) {

                        wp.data.dispatch( 'core/editor' ).editPost( { slug: response.primary_keyword } );
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
                    jQuery(".editor-post-save-draft" ).trigger( "click" );
                    jQuery('.loader_overlay').remove();
                }, "json").error(function(){
                    jQuery('.loader_overlay').remove();
                    alert('There was some problem in processing the file, please try again. If the problem persists, please check file size and make sure server time is large enough to process the file.');
                });
            }, 1000);
  
  

}



const SingleFileAutoSubmit = () => {
  console.log(inkObj.inkUrl);
 /* const toast = (innerHTML) => {
    const el = document.getElementById('toast')
    el.innerHTML = innerHTML
    el.className = 'show'
    setTimeout(() => { el.className = el.className.replace('show', '') }, 3000)
  }
*/
  const getUploadParams = () => {
    jQuery('body').prepend('<div class="loader_overlay"><div id="loader"><img src="'+inkObj.pluginsUrl+'/ink-official/images/ink.gif" class="loader_img"/> </div></div>');
    return { url: inkObj.inkUrl+'?action=upload_ink&inkSecurity='+inkObj.inkSecurity }
  }

  const handleChangeStatus = ({ meta, remove }, status) => {
    if (status === 'headers_received') {
      //toast(`${meta.name} uploaded!`)
      remove()
      {addGutenburgContent(meta.name)}
    } else if (status === 'aborted') {
      //toast(`${meta.name}, upload failed...`)
    }
    console.log(meta);
  }
      return (
        <React.Fragment>
          <Dropzone
            getUploadParams={getUploadParams}
            onChangeStatus={handleChangeStatus}
            maxFiles={1}
            multiple={false}
            canCancel={false}
            inputContent="Drop .ink file here to import"
            accept=".ink"
            styles={{
              dropzone: { width: 400, height: 200 },
              dropzoneActive: { backgroundColor: '#e45c965c' },
            }}
          />
        </React.Fragment>
      )
  }
    const importInk = () => {
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
                            block = wp.blocks.createBlock( 'core/image', { 
                                align: response.guten_images["INK_IMG_"+img_index]['align'], 
                                alt: response.guten_images["INK_IMG_"+img_index]['alt'], 
                                caption: response.guten_images["INK_IMG_"+img_index]['caption'],
                                id: response.guten_images["INK_IMG_"+img_index]['id'], link: "", 
                                linkDestination: "none", 
                                url: response.guten_images["INK_IMG_"+img_index]['url'],
                                height: response.guten_images["INK_IMG_"+img_index]['height'], 
                                width: response.guten_images["INK_IMG_"+img_index]['width'] 
                            } );

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
                                    if(afterH.length){
                                      afterH = afterH[0];
                                    }else{
                                      afterH = "";
                                    }

                                    var beforeH = htextclean.replace(/<h\d+>(.+)/gm, '');

                                    var hwithNo = afterH.split('<')[1].split('>')[0];
                                    var onlyNo = hwithNo.replace("h", "");                          
                                  afterH = afterH.replace(/<?h?\d+>/, '');
                                  if(beforeH && beforeH.trim()){
                                      block = wp.blocks.createBlock( 'core/paragraph', { content: beforeH } );
                                      wp.data.dispatch( 'core/editor' ).insertBlocks( block );
                                  }
                                  if(afterH && afterH.trim()){
                                    var lvl=parseInt(onlyNo);
                                      block = wp.blocks.createBlock( 'core/heading', {level:lvl, content: afterH } );
                                      wp.data.dispatch( 'core/editor' ).insertBlocks( block );
                                  }
                              }
                         
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
    }
const CustomSidebarComponent = () => {
	return(
		<Fragment>
			<PluginSidebarMoreMenuItem 
				target='ink-post-sidebar'
			>{__('INK', 'ink')}
			</PluginSidebarMoreMenuItem>
			<PluginSidebar 
				name="ink-post-sidebar" 
				title={__('INK', 'ink')
			}
			>                
				<PanelBody
					title={__('Import/Export ink file', 'ink')}
					initialOpen={true}  
				>
                <PanelRow>
                  {SingleFileAutoSubmit()}
                
                </PanelRow>
                <PanelRow>
                    <a href="#" className="ink_export button button-primary" onClick={()=>exportCall()} >
                    Click to export .ink file
                    </a>
				</PanelRow>
                </PanelBody>
                
				
			</PluginSidebar>
		</Fragment>
	);
}
 
registerPlugin('ink-postsidebar', {
	render: CustomSidebarComponent,
	icon: inkIcon,
	class: "ink_btn"
});