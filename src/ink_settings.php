<?php
if (!function_exists('ink_importer_register_settings')) {
	function ink_importer_register_settings(){
		add_option('ink_yotube_settings', '1');

		register_setting('inkimport_options_group', 'ink_yotube_settings', 'inkimport_callback');
	}
}
add_action('admin_init', 'ink_importer_register_settings');

if (!function_exists('ink_importer_register_options_page')) {
	function ink_importer_register_options_page(){
		add_options_page('INK Settings', 'INK Plugin', 'manage_options', 'inkimport', 'ink_importer_options_page');
	}
}
add_action('admin_menu', 'ink_importer_register_options_page');

if (!function_exists('ink_importer_options_page')) {
function ink_importer_options_page(){
    $ink_youtube = get_option('ink_yotube_settings');
    ?>
    <div>
        <?php screen_icon(); ?>


        <h2>INK Plugin Settings</h2>
        <div class="ink_wp-logo">
			<?php 
			echo '<img src="' . esc_url( plugins_url( 'images/top.png', __FILE__ ) ) . '" alt=""> ';
			?>
        </div>
        <p>This plugin helps you import .ink files produced by the  INK web copy editor into wordpress.<br><br>
            To edit or create .ink files for free, visit <a href="https://seo.app/l0SIDLV3g" target="_blank">https://seo.app/l0SIDLV3g</a><br>
            INK is a web content editor unlike any other, It helps you achive better results with your content.<br>

        <h4>Settings</h4>
        <form method="post" action="options.php">
            <?php settings_fields('inkimport_options_group'); ?>
            <table>
                <tr valign="top">
                    <td>
                        <label for="yotube_settings">
                            <input name="ink_yotube_settings" type="checkbox" id="ink_yotube_settings" value="1" <?php if ($ink_youtube) echo 'checked="checked"'; ?>>
                            YouTube links to YouTube embeds with wordPress.</label>
                    </td>
                </tr>

            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
	} 				
}?>