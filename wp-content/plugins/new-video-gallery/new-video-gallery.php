<?php
/**
@package New Video Gallery
Plugin Name: New Video Gallery
Plugin URI:  https://awplife.com/
Description: Create YouTube Vimeo Video Galleries Into WordPress Blog
Version:     1.3.1
Author:      A WP Life
Author URI:  https://awplife.com/
Text Domain: new-video-gallery
Domain Path: /languages
License:     GPL2

New Video Gallery is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
New Video Gallery is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with New Video Gallery. If not, see https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html.
*/



if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'New_Video_Gallery' ) ) {

	class New_Video_Gallery {
		
		protected $protected_plugin_api;
		protected $ajax_plugin_nonce;
		
		public function __construct() {
			$this->_constants();
			$this->_hooks();
		}		
		
		protected function _constants() {
			//Plugin Version
			define( 'VG_PLUGIN_VER', '1.3.1' );
			
			//Plugin Text Domain
			define("VGP_TXTDM","new-video-gallery" );

			//Plugin Name
			define( 'VG_PLUGIN_NAME', __( 'New Video Gallery', VGP_TXTDM ) );

			//Plugin Slug
			define( 'VG_PLUGIN_SLUG', 'video_gallery' );

			//Plugin Directory Path
			define( 'VG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

			//Plugin Directory URL
			define( 'VG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

			/**
			 * Create a key for the .htaccess secure download link.
			 * @uses    NONCE_KEY     Defined in the WP root config.php
			 */
			define( 'VG_SECURE_KEY', md5( NONCE_KEY ) );
			
		} // end of constructor function
		
		
		/**
		 * Setup the default filters and actions
		 * @uses      add_action()  To add various actions
		 * @access    private
		 * @since     0.0.5
		 * @return    void
		 */
		protected function _hooks() {
			
			//Load text domain
			add_action( 'plugins_loaded', array( $this, '_load_textdomain' ) );
			
			//add Video gallery menu item, change menu filter for multisite
			add_action( 'admin_menu', array( $this, '_srgallery_menu' ), 101 );
			
			//Create Video Gallery Custom Post
			add_action( 'init', array( $this, '_New_Video_Gallery' ));
			
			//Add meta box to custom post
			add_action( 'add_meta_boxes', array( $this, '_admin_add_meta_box' ) );
			 
			//loaded during admin init 
			add_action( 'admin_init', array( $this, '_admin_add_meta_box' ) );
			
			add_action('wp_ajax_video_gallery_js', array(&$this, '_ajax_video_gallery'));
		
			add_action('save_post', array(&$this, '_vg_save_settings'));

			//Shortcode Compatibility in Text Widgets
			add_filter('widget_text', 'do_shortcode');
			
			// add pfg cpt shortcode column - manage_{$post_type}_posts_columns
			add_filter( 'manage_video_gallery_posts_columns', array(&$this, 'set_video_gallery_shortcode_column_name') );
			
			// add pfg cpt shortcode column data - manage_{$post_type}_posts_custom_column
			add_action( 'manage_video_gallery_posts_custom_column' , array(&$this, 'custom_video_gallery_shodrcode_data'), 10, 2 );

			add_action( 'wp_enqueue_scripts', array(&$this, 'testimonial_enqueue_scripts_in_header') );
			
		} // end of hook function
		
		public function testimonial_enqueue_scripts_in_header() {
			wp_enqueue_script('jquery');
		}
		
		// Video Gallery table cpt shortcode column before date columns
		public function set_video_gallery_shortcode_column_name($defaults) {
			$new = array();
			$shortcode = $columns['video_gallery_shortcode'];  // save the tags column
			unset($defaults['tags']);   // remove it from the columns list

			foreach($defaults as $key=>$value) {
				if($key=='date') {  // when we find the date column
				   $new['video_gallery_shortcode'] = __( 'Shortcode', VGP_TXTDM );  // put the tags column before it
				}    
				$new[$key] = $value;
			}
			return $new;  
		}
		
		// Video Gallery cpt shortcode column data
		public function custom_video_gallery_shodrcode_data( $column, $post_id ) {
			switch ( $column ) {
				case 'video_gallery_shortcode' :
					echo "<input type='text' class='button button-primary' id='video-gallery-shortcode-$post_id' value='[VDGAL id=$post_id]' style='font-weight:bold; background-color:#32373C; color:#FFFFFF; text-align:center;' />";
					echo "<input type='button' class='button button-primary' onclick='return VIDEOCopyShortcode$post_id();' readonly value='Copy' style='margin-left:4px;' />";
					echo "<span id='copy-msg-$post_id' class='button button-primary' style='display:none; background-color:#32CD32; color:#FFFFFF; margin-left:4px; border-radius: 4px;'>copied</span>";
					echo "<script>
						function VIDEOCopyShortcode$post_id() {
							var copyText = document.getElementById('video-gallery-shortcode-$post_id');
							copyText.select();
							document.execCommand('copy');
							
							//fade in and out copied message
							jQuery('#copy-msg-$post_id').fadeIn('1000', 'linear');
							jQuery('#copy-msg-$post_id').fadeOut(2500,'swing');
						}
						</script>
					";
				break;
			}
		}
		
		public function _load_textdomain() {
			load_plugin_textdomain( VGP_TXTDM, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );		
		}		
		
		public function _srgallery_menu() {
			$vg_featured_plugin_menu = add_submenu_page( 'edit.php?post_type='.VG_PLUGIN_SLUG, __( 'Featured-Plugin', VGP_TXTDM ), __( 'Featured Plugin', VGP_TXTDM ), 'administrator', 'sr-feature-plugin-page', array( $this, '_vg_feature_plugin_page') );
			$theme_menu    = add_submenu_page( 'edit.php?post_type='.VG_PLUGIN_SLUG, __( 'Our Theme', VGP_TXTDM ), __( 'Our Theme', VGP_TXTDM ), 'administrator', 'sr-theme-page', array( $this, '_vg_theme_page') );
		}
		
		/**
		 * video Gallery Custom Post
		 * Create gallery post type in admin dashboard.
		 * @access    private
		*/
		public function _New_Video_Gallery() {
			$labels = array(
				'name'               => __( 'Video Gallery', 'Post Type General Name', VGP_TXTDM ),
				'singular_name'      => __( 'Video Gallery', 'Post Type Singular Name', VGP_TXTDM ),
				'menu_name'          => __( 'Video Gallery', VGP_TXTDM ),
				'name_admin_bar'     => __( 'Video Gallery', VGP_TXTDM ),
				'add_new'            => __( 'Add Video Gallery',VGP_TXTDM ),
				'add_new_item'       => __( 'Add New Video Gallery', VGP_TXTDM ),
				'new_item'           => __( 'New Video Gallery ', VGP_TXTDM ),
				'edit_item'          => __( 'Edit Video Gallery', VGP_TXTDM ),
				'view_item'          => __( 'View Video Gallery', VGP_TXTDM ),
				'all_items'          => __( 'All Video Gallery', VGP_TXTDM ),
				'search_items'       => __( 'Search Video Gallery', VGP_TXTDM ),
				'parent_item_colon'  => __( 'Parent Video Gallery:', VGP_TXTDM ),
				'not_found'          => __( 'Video Gallery Not found.', VGP_TXTDM ),
				'not_found_in_trash' => __( 'Video Gallery Not found in Trash.', VGP_TXTDM )
			);

			$args = array(
				'label'               => __( 'Video Gallery', VGP_TXTDM ),
				'description'         => __( 'Custom Post Type For Video Gallery', VGP_TXTDM ),
				'labels'              => $labels,
				'supports'            => array( 'title'),
				'taxonomies'          => array(),
				'hierarchical'        => false,
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_position'       => 65,
				'menu_icon'           => 'dashicons-images-alt2',
				'show_in_admin_bar'   => true,
				'show_in_nav_menus'   => true,
				'can_export'          => true,
				'has_archive'         => true,		
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'capability_type'     => 'page',
			);

			register_post_type( 'video_gallery', $args );
		}// end of post type function
		
		/**
		 * Adds Meta Boxes
		 */
		public function _admin_add_meta_box() {
			// Syntax: add_meta_box( $id, $title, $callback, $screen, $context, $priority, $callback_args );
			add_meta_box( '1', __('Copy Video Gallery Shortcode', VGP_TXTDM), array(&$this, '_vg_shortcode_left_metabox'), 'video_gallery', 'side', 'default' );
			add_meta_box( '', __('Add Video', VGP_TXTDM), array(&$this, 'vg_upload_multiple_images'), 'video_gallery', 'normal', 'default' );
		}
		
		// Video gallery copy shortcode meta box under publish button
		public function _vg_shortcode_left_metabox($post) { ?>
			<p class="input-text-wrap">
				<input type="text" name="VIDEOCopyShortcode" id="VIDEOCopyShortcode" value="<?php echo "[VDGAL id=".$post->ID."]"; ?>" readonly style="height: 60px; text-align: center; width:100%;  font-size: 24px; border: 2px dashed;">
				<p id="vg-copy-code"><?php _e('Shortcode copied to clipboard!', VGP_TXTDM); ?></p>
				<p style="margin-top: 10px"><?php _e('Copy & Embed shotcode into any Page/ Post / Text Widget to display gallery.', VGP_TXTDM); ?></p>
			</p>
			<span onclick="copyToClipboard('#VIDEOCopyShortcode')" class="vg-copy dashicons dashicons-clipboard"></span>
			<style>
				.vg-copy {
					position: absolute;
					top: 9px;
					right: 24px;
					font-size: 26px;
					cursor: pointer;
				}
			</style>
			<script>
				jQuery( "#vg-copy-code" ).hide();
				function copyToClipboard(element) {
				  var $temp = jQuery("<input>");
				  jQuery("body").append($temp);
				  $temp.val(jQuery(element).val()).select();
				  document.execCommand("copy");
				  $temp.remove();
				  jQuery( "#VIDEOCopyShortcode" ).select();
				  jQuery( "#vg-copy-code" ).fadeIn();
				}
			</script>
			<?php
		}
		
		public function vg_upload_multiple_images($post) {
			wp_enqueue_script('media-upload');
			wp_enqueue_script('awl-vg-uploader.js', VG_PLUGIN_URL . 'assets/js/awl-vg-uploader.js', array('jquery'));
			wp_enqueue_style('awl-vg-uploader-css', VG_PLUGIN_URL . 'assets/css/awl-vg-uploader.css');
			wp_enqueue_style('awl-metabox-css', VG_PLUGIN_URL . 'assets/css/metabox.css');
			wp_enqueue_media();
			?>
			
		
			<div class="row">
			<!--Add New Image Button-->
				<div class="file-upload">
					<div class="image-upload-wrap">
						<input class="new-slider file-upload-input" id="add-new-slider" name="add-new-slider" value="Upload Image" />
						<div class="drag-text">
							<h3><?php _e('ADD Video Banner',VGP_TXTDM); ?></h3>
						</div>
					</div>
				</div>
			</div>
			<?php
			require_once('include/video-gallery-settings.php');
		}
		public function _vg_ajax_callback_function($id) {
			$thumbnail = wp_get_attachment_image_src($id, 'medium', true);
			$attachment = get_post( $id ); // $id = attachment id
			?>
			<li class="slide">
				<img class="new-slide" src="<?php echo $thumbnail[0]; ?>" alt="<?php echo get_the_title($id); ?>" style="height: 150px; width: 100%; border-radius: 8px;">
				<input type="hidden" id="slide-ids[]" name="slide-ids[]" value="<?php echo $id; ?>" />
				<select id="slide-type[]" name="slide-type[]" style="width: 100%;" placeholder="Image Title" value="<?php echo $image_type; ?>" >
					<option value="y" <?php if($image_type == "y") echo "selected=selected"; ?>><?php _e('YouTube', VGP_TXTDM); ?></option>
					<option value="v" <?php if($image_type == "v") echo "selected=selected"; ?>><?php _e('Vimeo', VGP_TXTDM); ?></option>
				</select>
				<input type="text" name="slide-link[]" id="slide-link[]" style="width: 100%;" placeholder="Enter YouTube / Vimeo Video ID">
				<input type="text" name="slide-title[]" id="slide-title[]" style="width: 100%;" placeholder="Video Title" value="<?php echo get_the_title($id); ?>">
				<textarea name="slide-desc[]" id="slide-desc[]" placeholder="Video Description" style="height: 100px; width: 100%;"><?php echo $attachment->post_content; ?></textarea>
				<select id="poster-type[]" name="poster-type[]" style="width: 100%;" value="<?php echo $poster_type; ?>">
					<optgroup label="Select Poster Option">
					<option value="internal"><?php _e('Use Above Poster', VGP_TXTDM); ?></option>
					<option value="youtube"><?php _e('Fetch YouTube Poster', VGP_TXTDM); ?></option>
					</optgroup>
				</select>
				<input type="button" name="remove-slide" id="remove-slide" style="width: 100%;" class="button" value="Delete">
			</li>
			<?php
		}
		
		public function _ajax_video_gallery() {
			echo $this->_vg_ajax_callback_function($_POST['slideId']);
			die;
		}
		
		public function _vg_save_settings($post_id) {
			if (isset($_POST['vg-settings'] ) == "vg-save-settings") {
				$image_ids = $_POST['slide-ids'];
				$image_titles = $_POST['slide-title'];
				$image_descs = $_POST['slide-desc'];
				$image_types = $_POST['slide-type'];
				$i = 0;
				foreach($image_ids as $image_id) {
					$single_image_update = array(
						'ID'           => $image_id,
						'post_title'   => $image_titles[$i],
						'post_content' => $image_descs[$i],
					);
					wp_update_post( $single_image_update );
					$i++;
				}				
				$awl_video_gallery_shortcode_setting = "awl_vg_settings_".$post_id;
				update_post_meta($post_id, $awl_video_gallery_shortcode_setting, base64_encode(serialize($_POST)));
			}
		}// end save setting
		
		public function _vg_feature_plugin_page() {
			require_once('featured-plugins/featured-plugins.php');
		}
		
		// theme page
		public function _vg_theme_page() {
			require_once('our-theme/awp-theme.php');
		}
	}
	$vg_gallery_object = new New_Video_Gallery();
	require_once('include/shortcode.php');
}
?>