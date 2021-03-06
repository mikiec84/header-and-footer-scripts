<?php
/*
Plugin Name: Header and Footer Scripts
Plugin URI: http://www.blogsynthesis.com/plugins/header-and-footer-scripts/
Description: Allows you to insert code or text in the header or footer of your WordPress blog
Version: 1.3.5.1
Author: Anand Kumar
Author URI: http://www.blogsynthesis.com
License: GPLv2 or later

Header and Footer Scripts
Copyright (C) 2013-16, Anand Kumar <anand@anandkumar.net>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('SHFS_PLUGIN_DIR',str_replace('\\','/',dirname(__FILE__)));

if ( !class_exists( 'HeaderAndFooterScripts' ) ) {
	
	class HeaderAndFooterScripts {

		function __construct() {
		
			add_action( 'init', array( &$this, 'init' ) );
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
			add_action( 'wp_head', array( &$this, 'wp_head' ) );
			add_action( 'wp_footer', array( &$this, 'wp_footer' ) );
		
		}
		
	
		function init() {
			load_plugin_textdomain( 'insert-headers-and-footers', false, dirname( plugin_basename ( __FILE__ ) ).'/lang' );
		}
	
		function admin_init() {
			register_setting( 'insert-headers-and-footers', 'shfs_insert_header', 'trim' );
			register_setting( 'insert-headers-and-footers', 'shfs_insert_footer', 'trim' );

			foreach (array('post','page') as $type) 
			{
				add_meta_box('shfs_all_post_meta', 'Insert Script to &lt;head&gt;', 'shfs_meta_setup', $type, 'normal', 'high');
			}
			
			add_action('save_post','shfs_post_meta_save');
		}
	
		function admin_menu() {
			$page = add_submenu_page( 'options-general.php', 'Header and Footer Scripts', 'Header and Footer Scripts', 'manage_options', __FILE__, array( &$this, 'shfs_options_panel' ) );
			}
	
		function wp_head() {
			$meta = get_option( 'shfs_insert_header', '' );
				if ( $meta != '' ) {
					echo $meta, "\n";
				}

			$shfs_post_meta = get_post_meta( get_the_ID(), '_inpost_head_script' , TRUE );
				if ( $shfs_post_meta != '' ) {
					echo $shfs_post_meta['synth_header_script'], "\n";
				}
			
		}
	
		function wp_footer() {
			if ( !is_admin() && !is_feed() && !is_robots() && !is_trackback() ) {
				$text = get_option( 'shfs_insert_footer', '' );
				$text = convert_smilies( $text );
				$text = do_shortcode( $text );
			
			if ( $text != '' ) {
				echo $text, "\n";
			}
			}
		}

			
		function fetch_rss_items( $num, $feed ) {
			include_once( ABSPATH . WPINC . '/feed.php' );
			$rss = fetch_feed( $feed );

			// Bail if feed doesn't work
			if ( !$rss || is_wp_error( $rss ) )
			return false;

			$rss_items = $rss->get_items( 0, $rss->get_item_quantity( $num ) );

			// If the feed was erroneous 
			if ( !$rss_items ) {
				$md5 = md5( $feed );
				delete_transient( 'feed_' . $md5 );
				delete_transient( 'feed_mod_' . $md5 );
				$rss = fetch_feed( $feed );
				$rss_items = $rss->get_items( 0, $rss->get_item_quantity( $num ) );
			}

			return $rss_items;
		}
		   
				
		function shfs_options_panel() { ?>
			<div id="shfs-wrap">
				<div class="wrap">
				<?php screen_icon(); ?>
					<h2>Header and Footer Scripts - Options <a class="add-new-h2" target="_blank" href="#">Read Tutorial</a></h2>
					
					<hr />
					<div id="post-body" class="metabox-holder columns-2">
						<form name="dofollow" action="options.php" method="post">
						
							<?php settings_fields( 'insert-headers-and-footers' ); ?>
							
							<h3 class="shfs-labels" for="shfs_insert_header">Scripts in header:</h3>
							<textarea style="width:98%; max-width: 700px;" rows="5" cols="57" id="insert_header" name="shfs_insert_header"><?php echo esc_html( get_option( 'shfs_insert_header' ) ); ?></textarea><br />
						These scripts will be printed to the <code>&lt;head&gt;</code> section. 
						
							<h3 class="shfs-labels footerlabel" for="shfs_insert_footer">Scripts in footer:</h3>
							<textarea style="width:98%; max-width: 700px;" rows="5" cols="57" id="shfs_insert_footer" name="shfs_insert_footer"><?php echo esc_html( get_option( 'shfs_insert_footer' ) ); ?></textarea><br />
						These scripts will be printed to the <code>&lt;footer&gt;</code> section.

						<p class="submit">
							<input class="button button-primary" type="submit" name="Submit" value="Save settings" /> 
						</p>

						</form>
					</div>
				</div>
			</div>

		<?php
		}
	}

	function shfs_meta_setup()
	{
		global $post;
	 
		// using an underscore, prevents the meta variable
		// from showing up in the custom fields section
		$meta = get_post_meta($post->ID,'_inpost_head_script',TRUE);
	 
		// instead of writing HTML here, lets do an include
		include(SHFS_PLUGIN_DIR . '/meta.php');
	 
		// create a custom nonce for submit verification later
		echo '<input type="hidden" name="shfs_post_meta_noncename" value="' . wp_create_nonce(__FILE__) . '" />';
	}
	 
	function shfs_post_meta_save($post_id) 
	{
		// authentication checks

		// make sure data came from our meta box
		if ( ! isset( $_POST['shfs_post_meta_noncename'] )
			|| !wp_verify_nonce($_POST['shfs_post_meta_noncename'],__FILE__)) return $post_id;

		// check user permissions
		if ($_POST['post_type'] == 'page') 
		{
			if (!current_user_can('edit_page', $post_id)) return $post_id;
		}
		else 
		{
			if (!current_user_can('edit_post', $post_id)) return $post_id;
		}

		$current_data = get_post_meta($post_id, '_inpost_head_script', TRUE);	
	 
		$new_data = $_POST['_inpost_head_script'];

		shfs_post_meta_clean($new_data);
		
		if ($current_data) 
		{
			if (is_null($new_data)) delete_post_meta($post_id,'_inpost_head_script');
			else update_post_meta($post_id,'_inpost_head_script',$new_data);
		}
		elseif (!is_null($new_data))
		{
			add_post_meta($post_id,'_inpost_head_script',$new_data,TRUE);
		}

		return $post_id;
	}

	function shfs_post_meta_clean(&$arr)
	{
		if (is_array($arr))
		{
			foreach ($arr as $i => $v)
			{
				if (is_array($arr[$i])) 
				{
					shfs_post_meta_clean($arr[$i]);

					if (!count($arr[$i])) 
					{
						unset($arr[$i]);
					}
				}
				else 
				{
					if (trim($arr[$i]) == '') 
					{
						unset($arr[$i]);
					}
				}
			}

			if (!count($arr)) 
			{
				$arr = NULL;
			}
		}
	}

$shfs_header_and_footer_scripts = new HeaderAndFooterScripts();

}


