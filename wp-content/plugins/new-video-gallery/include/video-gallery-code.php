<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Gallery Output Code
 */
//js
wp_enqueue_script('imagesloaded');
wp_enqueue_script('awl-vg-isotope-js', VG_PLUGIN_URL .'assets/js/isotope.pkgd.js', array('jquery'), '', false);

//video js
wp_enqueue_script('awl-vg-scale-fix-js', VG_PLUGIN_URL .'assets/js/video-js/scale.fix.js', array('jquery'), '', true);
wp_enqueue_script('awl-vg-video-lightning-js', VG_PLUGIN_URL .'assets/js/video-js/videoLightning.js', array('jquery'), '', true);
wp_enqueue_script('awl-vg-jqvl-page-js', VG_PLUGIN_URL .'assets/js/video-js/jqvl-page.js', array('jquery'), '', true);

// custom bootstrap css
wp_enqueue_style('awl-bootstrap-css', VG_PLUGIN_URL .'assets/css/video-gallery-bootstrap.css');
 
$video_gallery_id = $post_id['id'];
 
$all_galleries = array(  'p' => $video_gallery_id, 'post_type' => 'video_gallery', 'orderby' => 'ASC');
$loop = new WP_Query( $all_galleries );

while ( $loop->have_posts() ) : $loop->the_post();

	$post_id = get_the_ID();
	$gallery_settings = unserialize(base64_decode(get_post_meta( $post_id, 'awl_vg_settings_'.$post_id, true)));
	//columns settings
	$gal_thumb_size = $gallery_settings['gal_thumb_size'];
	$col_large_desktops = $gallery_settings['col_large_desktops'];
	$col_desktops = $gallery_settings['col_desktops'];
	$col_tablets = $gallery_settings['col_tablets'];
	$col_phones = $gallery_settings['col_phones'];
	$width = $gallery_settings['width'];
	$height = $gallery_settings['height'];
	$auto_play = $gallery_settings['auto_play'];
	$auto_close = $gallery_settings['auto_close'];
	$custom_css = $gallery_settings['custom_css'];
	$z_index = $gallery_settings['z_index'];
	if($z_index == "default") $z_index_value = 2100; else { $z_index_value = $gallery_settings['z_index_custom_value']; }
	// start the video gallery contents
	?>
	<div id="image_gallery_<?php echo $video_gallery_id; ?>" class="row all-images">
		<?php
		if(isset($gallery_settings['slide-ids']) && count($gallery_settings['slide-ids']) > 0) {
			$count = 0;
			foreach($gallery_settings['slide-ids'] as $attachment_id) {
				$thumb = wp_get_attachment_image_src($attachment_id, 'thumb', true);
				$thumbnail = wp_get_attachment_image_src($attachment_id, 'thumbnail', true);
				$medium = wp_get_attachment_image_src($attachment_id, 'medium', true);
				$large = wp_get_attachment_image_src($attachment_id, 'large', true);
				$full = wp_get_attachment_image_src($attachment_id, 'full', true);
				$attachment_details = get_post( $attachment_id );
				$src = $attachment_details->guid;
				$title = $attachment_details->post_title;
				$description = $attachment_details->post_content;				
				$video_type =  $gallery_settings['slide-type'][$count];
				$video_id =  $gallery_settings['slide-link'][$count];
				$poster_type = $gallery_settings['poster-type'][$count];
				
				//set thumbnail size
				if($gal_thumb_size == "thumbnail") { $thumbnail_url = $thumbnail[0]; }
				if($gal_thumb_size == "medium") { $thumbnail_url = $medium[0]; }
				if($gal_thumb_size == "large") { $thumbnail_url = $large[0]; }
				if($gal_thumb_size == "full") { $thumbnail_url = $full[0]; }
				if($poster_type == "youtube" && $video_type == "y") {
					$thumbnail_url = "https://img.youtube.com/vi/$video_id/hqdefault.jpg";
				}
					?>
					<div class="single-image <?php echo $col_large_desktops; ?> <?php echo $col_desktops; ?> <?php echo $col_tablets; ?> <?php echo $col_phones; ?>">
						<div class="vg-title"><?php echo $title; ?></div>
							<img class="img-thumbnail vid-<?php echo $video_gallery_id; ?>" src="<?php echo $thumbnail_url; ?>" alt="<?php echo $title; ?>" data-video-id="<?php echo $video_type; ?>-<?php echo $video_id; ?>" alt="<?php echo $title; ?>">
						<div class="vg-desc"><?php echo $description; ?> </div>
					</div>
					<?php
				$count++;
			}// end of attachment foreach
		} else {
			_e('Sorry! No video gallery found ', VGP_TXTDM);
			echo ": [VID-Gal id=$post_id]";
		} // end of if esle of slides avaialble check into slider
		?>
	</div>
<?php
endwhile;
wp_reset_query();
?>
<style>
<?php if($close_button == "false") { ?>
.video-close {
	display: none !important;
}
<?php } ?>
.single-image .vg-title {
	font-size: 25px;
	font-weight: bold;
	text-align: center;
	padding: 5px
}
.single-image .vg-desc{
	font-size: 15px;
	padding: 5px;
}

.single-image {
 padding-top: 20px;
}
<?php echo $custom_css; ?>
</style>
<script>
jQuery(document).ready(function () {
	// isotope effect function
	// Method 1 - Initialize Isotope, then trigger layout after each image loads.
	var $grid = jQuery('.all-images').isotope({
		// options...
		itemSelector: '.single-image',
	});
	// layout Isotope after each image loads
	$grid.imagesLoaded().progress( function() {
		$grid.isotope('layout');
	});
	
	//video lighting js
	videoLightning({
		elements: [
			{
				".vid-<?php echo $video_gallery_id; ?>": {
					width: '<?php echo $width; ?>',
					height: '<?php echo $height; ?>',
					autoplay: <?php echo $auto_play; ?>,
					autoclose: <?php echo $auto_close; ?>,
					zindex: '<?php echo $z_index_value; ?>',
					autohide: 2,
				}
			}
		]
	});
});
</script>