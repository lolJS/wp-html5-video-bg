<?php
/**
 * Plugin Name: HTML5 Video Background
 * Plugin URI: http://github.com/loljs/wp-html5-video-bg
 * Description: Creates a fullscreen video background using html5 video. Falls back to an image with a link.
 * Version: 1.0
 * Author: Jonathan Brito
 * Author URI: http://github.com/loljs
 * License: MIT
 */

global $VIDBG;
$VIDBG = "";

if( !class_exists('VIDBG') ){
	
	class VIDBG {
		static $plugin_menu_slug = 'vidbg_menu';

		function __construct() {
			add_action( 'admin_menu' , array( __CLASS__ , 'register_admin_menu' ) );
			add_action( 'add_meta_boxes', array( __CLASS__ , 'vidbg_add_custom_box' ) );
			add_action( 'save_post', array(__CLASS__, 'vidbg_save_postdata' ) );
			add_action( 'wp_enqueue_scripts', array(__CLASS__, 'vidbg_add_scripts') );

			add_filter( 'wp_head', array(__CLASS__, 'vidbg_add_bg_placeholder') );
			add_filter( 'wp_footer', array(__CLASS__, 'vidbg_append_vid_html') );
		}

		// add metabox to posts and pages
		function vidbg_add_custom_box() {
			add_meta_box('vidbg_post_options', 'Video Background Options', array( __CLASS__ , 'vidbg_post_html' ), 'post', 'normal', 'default', null);
			add_meta_box('vidbg_post_options', 'Video Background Options', array( __CLASS__ , 'vidbg_post_html' ), 'page', 'normal', 'default', null);
		}

		// add metabox form html
		function vidbg_post_html($post, $metabox) {
			wp_nonce_field( 'vidbg_verify_nonce', 'vidbg_nonce' );

			$values = get_post_custom( $post->ID );
			$_vidbg_enabled = isset( $values['vidbg_enabled'] ) ? esc_attr( $values['vidbg_enabled'][0] ) : '';
			$_vidbg_vid_url_mp4 = isset( $values['vidbg_vid_url_mp4'] ) ? esc_attr( $values['vidbg_vid_url_mp4'][0] ) : '';
			$_vidbg_vid_url_webm = isset( $values['vidbg_vid_url_webm'] ) ? esc_attr( $values['vidbg_vid_url_webm'][0] ) : '';
			$_vidbg_img_url = isset( $values['vidbg_img_url'] ) ? esc_attr( $values['vidbg_img_url'][0] ) : '';
			$_vidbg_custom_overlay_html = isset( $values['vidbg_custom_overlay_html'] ) ? esc_attr( $values['vidbg_custom_overlay_html'][0] ) : '';
			$_vidbg_autoplay = isset( $values['vidbg_autoplay'] ) ? esc_attr( $values['vidbg_autoplay'][0] ) : '';
			$_vidbg_loop = isset( $values['vidbg_loop'] ) ? esc_attr( $values['vidbg_loop'][0] ) : '';
			$_vidbg_muted = isset( $values['vidbg_muted'] ) ? esc_attr( $values['vidbg_muted'][0] ) : '';

			// Render meta box form
			echo '<label for="vidbg_enabled">';
		    	_e( "Enable Video Background", 'vidbg_text' );
			echo '</label> ';
			echo '<input type="checkbox" id="vidbg_enabled" name="vidbg_enabled"' . (( esc_attr( $_vidbg_enabled ) == 'true' ) ? 'checked' : '' ) . ' size="25" />';

			echo '<br />';

			echo '<label for="vidbg_autoplay">';
		    	_e( "Autoplay Video?", 'vidbg_text' );
			echo '</label> ';
			echo '<input type="checkbox" id="vidbg_autoplay" name="vidbg_autoplay"' . (( esc_attr( $_vidbg_autoplay ) == 'true' ) ? 'checked' : '' ) . ' size="25" />';

			echo '<br />';

			echo '<label for="vidbg_loop">';
		    	_e( "Loop Video?", 'vidbg_text' );
			echo '</label> ';
			echo '<input type="checkbox" id="vidbg_loop" name="vidbg_loop"' . (( esc_attr( $_vidbg_loop ) == 'true' ) ? 'checked' : '' ) . ' size="25" />';

			echo '<br />';

			echo '<label for="vidbg_muted">';
		    	_e( "Start Video Muted?", 'vidbg_text' );
			echo '</label> ';
			echo '<input type="checkbox" id="vidbg_muted" name="vidbg_muted"' . (( esc_attr( $_vidbg_muted ) == 'true' ) ? 'checked' : '' ) . ' size="25" />';

			echo '<br />';

			echo '<label for="vidbg_vid_url_mp4">';
		    	_e( "Video Background URL (mp4)", 'vidbg_text' );
			echo '<input type="text" id="vidbg_vid_url_mp4" name="vidbg_vid_url_mp4" value="' . esc_attr( $_vidbg_vid_url_mp4 ) . '"/>';
			echo '</label> ';

			echo '<br />';

			echo '<label for="vidbg_vid_url_webm">';
		    	_e( "Video Background URL (webm)", 'vidbg_text' );
			echo '<input type="text" id="vidbg_vid_url_webm" name="vidbg_vid_url_webm" value="' . esc_attr( $_vidbg_vid_url_webm ) . '"/>';
			echo '</label> ';

			echo '<br />';

			echo '<label for="vidbg_img_url">';
		    	_e( "Video Background Placeholder URL", 'vidbg_text' );
			echo '<input type="text" id="vidbg_img_url" name="vidbg_img_url" value="' . esc_attr( $_vidbg_img_url ) . '"/>';
			echo '</label> ';

			echo '<br />';

			echo '<label for="vidbg_custom_overlay_html">';
		    	_e( "Custom Overlay HTML", 'vidbg_text' );
			echo '<textarea type="text" id="vidbg_custom_overlay_html" name="vidbg_custom_overlay_html" />';
				echo $_vidbg_custom_overlay_html;
			echo '</textarea>';
			echo '</label> ';
		}

		// validate input and save
		function vidbg_save_postdata( $post_id ) {
			$allowed = array(	// allowed tags
		        'a' => array(
		            'href' => array()
		        ),
		        'div' => array(
		        	'id' => array(),
		        	'class' => array()
		        ),
		        'input' => array(
		        	'id' => array(),
		        	'class' => array(),
		        	'type' => array(),
		        	'value' => array(),
		        	'onclick' => array()
		        ),
		        'p' => array(
		        	'id' => array(),
		        	'class' => array()
		        ),
		        'br' => array()
		    );

            // Pz if we're doing an auto save
		    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		     
		    // if our nonce isn't there, or we can't verify it, pz
		    if( !isset( $_POST['vidbg_nonce'] ) || !wp_verify_nonce( $_POST['vidbg_nonce'], 'vidbg_verify_nonce' ) ) return;
		     
		    // if our current user can't edit this post, pz
            if( !current_user_can( 'edit_post' ) ) return;

            // All's good, update fields
            $chk = isset( $_POST['vidbg_enabled'] ) && $_POST['vidbg_enabled'] ? 'true' : 'false';
            update_post_meta( $post_id, 'vidbg_enabled', $chk );

            $chk_autoplay = isset( $_POST['vidbg_autoplay'] ) && $_POST['vidbg_autoplay'] ? 'true' : 'false';
            update_post_meta( $post_id, 'vidbg_autoplay', $chk_autoplay );

            $chk_loop = isset( $_POST['vidbg_loop'] ) && $_POST['vidbg_loop'] ? 'true' : 'false';
            update_post_meta( $post_id, 'vidbg_loop', $chk_loop );

            $chk_muted = isset( $_POST['vidbg_muted'] ) && $_POST['vidbg_muted'] ? 'true' : 'false';
            update_post_meta( $post_id, 'vidbg_muted', $chk_muted );

		    if ( isset( $_POST['vidbg_vid_url_mp4']) ) {
		    	update_post_meta( $post_id, 'vidbg_vid_url_mp4', esc_attr( $_POST['vidbg_vid_url_mp4'] ) );
		    }

		    if ( isset( $_POST['vidbg_vid_url_webm']) ) {
		    	update_post_meta( $post_id, 'vidbg_vid_url_webm', esc_attr( $_POST['vidbg_vid_url_webm'] ) );
		    }

		    if ( isset( $_POST['vidbg_img_url']) ) {
		    	update_post_meta( $post_id, 'vidbg_img_url', esc_attr( $_POST['vidbg_img_url'] ) );
		    }

		    if ( isset( $_POST['vidbg_custom_overlay_html']) ) {
		    	update_post_meta( $post_id, 'vidbg_custom_overlay_html', wp_kses ( $_POST['vidbg_custom_overlay_html'], $allowed) );
		    }
		}

		// Generate fullscreen video bg html
		function vidbg_append_vid_html() {
			$curr_post = $GLOBALS['post'];
			$values = get_post_custom( $curr_post->ID );
			$_vidbg_enabled = isset( $values['vidbg_enabled'] ) ? esc_attr( $values['vidbg_enabled'][0] ) : '';

			$_vidbg_enabled = ($_vidbg_enabled == 'true') ? true : false;

			if (!$_vidbg_enabled) return;

			// check variables
			$_vidbg_vid_url_mp4 = isset( $values['vidbg_vid_url_mp4'] ) ? esc_attr( $values['vidbg_vid_url_mp4'][0] ) : '';
			$_vidbg_vid_url_webm = isset( $values['vidbg_vid_url_webm'] ) ? esc_attr( $values['vidbg_vid_url_webm'][0] ) : '';
			$_vidbg_custom_overlay_html = isset( $values['vidbg_custom_overlay_html'] ) ? $values['vidbg_custom_overlay_html'][0] : '';
			$_vidbg_autoplay = isset( $values['vidbg_autoplay'] ) ? esc_attr( $values['vidbg_autoplay'][0] ) : '';
			$_vidbg_autoplay = ($_vidbg_autoplay == 'true') ? 'autoplay' : '';
			$_vidbg_loop = isset( $values['vidbg_loop'] ) ? esc_attr( $values['vidbg_loop'][0] ) : '';
			$_vidbg_loop = ($_vidbg_loop == 'true') ? 'loop' : '';
			$_vidbg_muted = isset( $values['vidbg_muted'] ) ? esc_attr( $values['vidbg_muted'][0] ) : '';
			$_vidbg_muted = ($_vidbg_muted == 'true') ? 'muted' : '';

			// if neither of these two are set theres no point in doing anything so get out
			if (!$_vidbg_vid_url_mp4 || !$_vidbg_vid_url_webm) return;

			$_vidbg_img_url = isset( $values['vidbg_img_url'] ) ? esc_attr( $values['vidbg_img_url'][0] ) : '';
			$video_html = '<video ' . $_vidbg_autoplay . ' ' . $_vidbg_loop . ' ' . $_vidbg_muted . ' poster="' . $_vidbg_img_url . '" id="vidbg_fullscreen">';

			if ( !empty($_vidbg_vid_url_webm) ) {
				$video_html .= '<source src="' . $_vidbg_vid_url_webm . '" type="video/webm">';
			}

			if ( !empty($_vidbg_vid_url_mp4) ) {
				$video_html .= '<source src="' . $_vidbg_vid_url_mp4 . '" type="video/mp4">';
			}

			$video_html .= '</video>';

			echo $video_html;
			echo '<div id="vidbg_controls" style="display: none;">';
				echo '<div id="vidbg_play_pause" class="icon-pause"></div><div id="vidbg_mute" class="icon-volume-off"></div>';
			echo '</div>';
			echo '<div id="vidbg_close" style="display: none;">x</div>';

			if ( !empty($_vidbg_custom_overlay_html) ) {
				echo $_vidbg_custom_overlay_html;
			}
		}

		// add css for video placeholder
		function vidbg_add_bg_placeholder () {
			$curr_post = $GLOBALS['post'];
			$values = get_post_custom( $curr_post->ID );
			$_vidbg_enabled = isset( $values['vidbg_enabled'] ) ? esc_attr( $values['vidbg_enabled'][0] ) : '';

			$_vidbg_enabled = ($_vidbg_enabled == 'true') ? true : false;

			if (!$_vidbg_enabled) return;

			$_vidbg_img_url = isset( $values['vidbg_img_url'] ) ? esc_attr( $values['vidbg_img_url'][0] ) : '';

			echo '<style type="text/css">';
			echo '	video#vidbg_fullscreen { background: url(' . $_vidbg_img_url . '); }';
			echo '</style>';
		}

		// add stylesheet to make video fullscreen js
		function vidbg_add_scripts () {
			wp_register_style( 'vidbg_fullscreen_style', plugins_url('css/style.css', __FILE__) );
			wp_register_style( 'vidbg_controls_font', plugins_url('css/fontello.css'),  __FILE__ );
			wp_enqueue_script( 'vidbg_controls_js', plugins_url('js/html5-video-background.js', __FILE__) );

			wp_enqueue_style( 'vidbg_controls_font' );
			wp_enqueue_style( 'vidbg_fullscreen_style' );
			wp_enqueue_script( 'vidbg_controls_js' );
		}

		// add admin menu
		function register_admin_menu (){
			$page = add_menu_page(
				"Fullscreen Video Background for Posts and Pages",
				"Fullscreen Video Background",
				"manage_options",
				self::$plugin_menu_slug,
				array( __CLASS__, "do_admin_menu" ),
				null,
				null
			);
		}

		function do_admin_menu() {
		?>
          <div class='wrap'>
            <h2>Fullscreen Video Background for Posts and Pages</h2>
            <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
              <input type="hidden" name="cmd" value="_s-xclick">
              <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
              <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
              <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
            </form>
          </div>
		<?php
		}
	}

	function register_vidbg(){
		global $VIDBG;
		$VIDBG = new VIDBG();
	}

	add_action( "init" , "register_vidbg" );
}

?>
