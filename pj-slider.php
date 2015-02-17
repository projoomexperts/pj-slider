<?php
/*
Plugin Name: PJ Slider
Plugin URI: http://www.projoomexperts.com/pj-slider
Description: Wordpress Slideshow and Carousel Plugin developed by projoomexperts
Version: 0.1
Author: Mash R
Author Email: projoomexperts@gmail.com
License:

  Copyright 2011 Mash R (projoomexperts@gmail.com)

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

add_action( 'init', 'PJSlider_cpt' );
		function PJSlider_cpt() {
			$labels = array(
				'name' => 'Slides',
				'singular_name' => 'Slide',
				'menu_name' => 'PJ Slider',
				'all_items' => 'All Slides',
				'add_new' => 'Add New',
				'add_new_item' => 'Add New Slide',
				'edit_item' => 'Edit Slide',
				'new_item' => 'New Slide',
				'view_item' => 'View Slide',
				);

			$args = array(
				'labels' => $labels,
				'description' => 'PJ Slider',
				'public' => true,
				'show_ui' => true,
				'has_archive' => false,
				'show_in_menu' => true,
				'exclude_from_search' => false,
				'capability_type' => 'post',
				'map_meta_cap' => true,
				'hierarchical' => false,
				'rewrite' => array( 'slug' => 'slide', 'with_front' => true ),
				'query_var' => true,
				'menu_position' => 30,				'supports' => array( 'title', 'thumbnail' ),			);
			register_post_type( 'slide', $args );

		// End of PJSlider_cpt()
		}

		add_action( 'init', 'PJSlider_tax' );
		function PJSlider_tax() {

			$labels = array(
				'name' => 'slider',
				'label' => 'Sliders',
				'menu_name' => 'Slider',
				'all_items' => 'All Sliders',
				'edit_item' => 'Edit Slider',
				'view_item' => 'View Slider',
				'update_item' => 'Update Slider Name',
				'add_new_item' => 'Add New Slider',
				'new_item_name' => 'New Slider Name',
				'search_items' => 'Search Slider',
				'popular_items' => 'Popular Sliders',
				'add_or_remove_items' => 'Add / Remove Sliders',
				'not_found' => 'Slider Not Fount',
					);

			$args = array(
				'labels' => $labels,
				'hierarchical' => true,
				'label' => 'Sliders',
				'show_ui' => true,
				'query_var' => true,
				'rewrite' => true,
				'show_admin_column' => false,
			);
			register_taxonomy( 'slider', array( 'slide' ), $args );

		// End cptui_register_my_taxes
		}
		
function pjslider_add_meta_box() {

	$screens = array( 'slide');

	foreach ( $screens as $screen ) {

		add_meta_box(
			'pjslider_sectionid',
			__( 'Slide Link', 'pjslider_textdomain' ),
			'pjslider_meta_box_callback',
			$screen
		);
	}
}
add_action( 'add_meta_boxes', 'pjslider_add_meta_box', 10, 2  );

/**
 * Prints the box content.
 * 
 * @param WP_Post $post The object for the current post/page.
 */
function pjslider_meta_box_callback( $post ) {

	// Add an nonce field so we can check for it later.
	wp_nonce_field( 'pjslider_meta_box', 'pjslider_meta_box_nonce' );

	/*
	 * Use get_post_meta() to retrieve an existing value
	 * from the database and use the value for the form.
	 */
	$value = get_post_meta( $post->ID, '_my_meta_value_key', true );

	echo '<label for="pjslider_new_field">';
	_e( 'Type the URL here', 'pjslider_textdomain' );
	echo '</label> ';
	echo '<input type="text" id="pjslider_new_field" name="pjslider_new_field" value="' . esc_attr( $value ) . '" size="25" placeholder="http://" />';
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function pjslider_save_meta_box_data( $post_id ) {

	/*
	 * We need to verify this came from our screen and with proper authorization,
	 * because the save_post action can be triggered at other times.
	 */

	// Check if our nonce is set.
	if ( ! isset( $_POST['pjslider_meta_box_nonce'] ) ) {
		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['pjslider_meta_box_nonce'], 'pjslider_meta_box' ) ) {
		return;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check the user's permissions.
	if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

	} else {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	/* OK, it's safe for us to save the data now. */
	
	// Make sure that it is set.
	if ( ! isset( $_POST['pjslider_new_field'] ) ) {
		return;
	}

	// Sanitize user input.
	$my_data = sanitize_text_field( $_POST['pjslider_new_field'] );

	// Update the meta field in the database.
	update_post_meta( $post_id, '_my_meta_value_key', $my_data );
}
add_action( 'save_post', 'pjslider_save_meta_box_data' );


class PJSlider {

	/*--------------------------------------------*
	 * Constants
	 *--------------------------------------------*/
	const name = 'PJ Slider';
	const slug = 'pj_slider';
	
	/**
	 * Constructor
	 */
	function __construct() {
		//register an activation hook for the plugin
		register_activation_hook( __FILE__, array( &$this, 'install_pj_slider' ) );

		//Hook up to the init action
		add_action( 'init', array( &$this, 'init_pj_slider' ) );
	}
  
	/**
	 * Runs when the plugin is activated
	 */  
	function install_pj_slider() {
		// do not generate any output here
	}
  
	/**
	 * Runs when the plugin is initialized
	 */
	function init_pj_slider() {
		// Setup localization
		load_plugin_textdomain( self::slug, false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
		// Load JavaScript and stylesheets
		$this->register_scripts_and_styles();

		// Register the shortcode [pj_slider]
		add_shortcode( 'pj_slider', array( &$this, 'render_shortcode' ) );
		
		

		
	
		if ( is_admin() ) {
			//this will run when in the WordPress admin
		} else {
			//this will run when on the frontend
		}

		/*
		 * TODO: Define custom functionality for your plugin here
		 *
		 * For more information: 
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		add_action( 'your_action_here', array( &$this, 'action_callback_method_name' ) );
		add_filter( 'your_filter_here', array( &$this, 'filter_callback_method_name' ) );    
	}

	function action_callback_method_name() {
		// TODO define your action method here
	}

	function filter_callback_method_name() {
		// TODO define your filter method here
	}

	function render_shortcode($atts) {
		// Extract the attributes
		extract(shortcode_atts(array(
			'slider' => 'No Slider Found', 
			'attr2' => 'bar'
			), $atts));
		// you can now access the attribute values using $attr1 and $attr2
		
		$args = array( 
			'post_type' => 'slide', 
			'posts_per_page' => 10,
			'tax_query' => array(
				array(
					'taxonomy' => 'slider',
					'field'    => 'slug',
					'terms'    =>  $slider ,
				),
			)
			);
		$loop = new WP_Query( $args );
		global $post;
		echo '<div class="owl-carousel">';
		while ( $loop->have_posts() ) : $loop->the_post();
		?>
		<div class="item">
			<div class="shadow"></div>
			<?php $url = get_post_meta( $post->ID, '_my_meta_value_key', true );
			?>
		   <a href="<?php echo $url; ?>"><?php if ( has_post_thumbnail() ) {
			the_post_thumbnail('full');  
			} ?></a>
			<h4 class="caption"><a href="<?php echo $url; ?>"><?php the_title(); ?></a></h4>
			
		</div>
		<?php
		endwhile;
		?>
		<?php wp_reset_postdata(); ?>
		</div>
		<script>
			jQuery(document).ready(function(){
				jQuery('.owl-carousel').owlCarousel({
					center: true,
					items:1,
					loop:true,
					nav:true,
					autoplay:true,
					autoplayTimeout:2000,
					autoplayHoverPause:true,
					stagePadding: 500,
					margin:0,
					responsive:{
						    0 : {
								items:1,
								stagePadding: 50,
								nav:false
							},
							// breakpoint from 480 up
							480 : {
								items:1,
								stagePadding: 200,
								nav:false
							},
							// breakpoint from 768 up
							768 : {
								items:1,
								stagePadding: 500
								
							}
					}
				});
			})
		</script>
		<style>
		.item {
position: relative;
overflow:hidden;
}
.shadow{
position: absolute;
top:0;
bottom:0;
left:0;
right:0;
z-index: 10;
}


.owl-item .shadow{
background: -moz-linear-gradient(left,  rgba(0,0,0,0.22) 0%, rgba(0,0,0,0.65) 100%); /* FF3.6+ */
background: -webkit-gradient(linear, left top, right top, color-stop(0%,rgba(0,0,0,0.22)), color-stop(100%,rgba(0,0,0,0.65))); /* Chrome,Safari4+ */
background: -webkit-linear-gradient(left,  rgba(0,0,0,0.22) 0%,rgba(0,0,0,0.65) 100%); /* Chrome10+,Safari5.1+ */
background: -o-linear-gradient(left,  rgba(0,0,0,0.22) 0%,rgba(0,0,0,0.65) 100%); /* Opera 11.10+ */
background: -ms-linear-gradient(left,  rgba(0,0,0,0.22) 0%,rgba(0,0,0,0.65) 100%); /* IE10+ */
background: linear-gradient(to right,  rgba(0,0,0,0.22) 0%,rgba(0,0,0,0.65) 100%); /* W3C */
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#38000000', endColorstr='#a6000000',GradientType=1 ); /* IE6-9 */

}

.owl-item.active .shadow{
background: -moz-linear-gradient(left,  rgba(0,0,0,0.65) 0%, rgba(0,0,0,0.22) 100%); /* FF3.6+ */
background: -webkit-gradient(linear, left top, right top, color-stop(0%,rgba(0,0,0,0.65)), color-stop(100%,rgba(0,0,0,0.22))); /* Chrome,Safari4+ */
background: -webkit-linear-gradient(left,  rgba(0,0,0,0.65) 0%,rgba(0,0,0,0.22) 100%); /* Chrome10+,Safari5.1+ */
background: -o-linear-gradient(left,  rgba(0,0,0,0.65) 0%,rgba(0,0,0,0.22) 100%); /* Opera 11.10+ */
background: -ms-linear-gradient(left,  rgba(0,0,0,0.65) 0%,rgba(0,0,0,0.22) 100%); /* IE10+ */
background: linear-gradient(to right,  rgba(0,0,0,0.65) 0%,rgba(0,0,0,0.22) 100%); /* W3C */
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#a6000000', endColorstr='#38000000',GradientType=1 ); /* IE6-9 */


}

.owl-item.center .shadow{
background: transparent;
background-image: none;
display:none;
}
.owl-item.center {
z-index:1000;
}

.item h4.caption {
    position: absolute;
    z-index: 100;
    bottom: 0;
    padding: 30px;
    background: rgba(0, 0, 0, 0.17);
    left: 0;
    right: 0;
    color: #fff;
    font-size: 30px;
    margin: 0px;
    line-height: 30px;
    text-shadow: 1px 1px 1px #000;
    font-family: "Abel", sans-serif;
    text-align: center;
}
.item h4.caption a{
color:#fff;
}

.owl-nav {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    margin-top: -35px;
}

.owl-prev, .owl-next , .owl-theme .owl-controls .owl-nav [class*=owl-]{
font-size:0px;
color: transparent;
}

.owl-prev{
float:left;
}
.owl-next{
float:right;
}

.owl-prev:before {
    content: "\f053";
    font-family: fontawesome;
	color: #fff;
font-size: 40px;
line-height: 60px;
vertical-align: middle;
}


.owl-next:after {
    content: "\f054";
    font-family: fontawesome;
		color: #fff;
font-size: 40px;
line-height: 60px;
vertical-align: middle;
}

		</style>
		<?php
		
	}
  
	/**
	 * Registers and enqueues stylesheets for the administration panel and the
	 * public facing site.
	 */
	private function register_scripts_and_styles() {
		if ( is_admin() ) {
			$this->load_file( self::slug . '-admin-script', '/js/admin.js', true );
			$this->load_file( self::slug . '-admin-style', '/css/admin.css' );
		} else {
			$this->load_file( self::slug . '-script', '/assets/owl.carousel.min.js', true );
			$this->load_file( self::slug . '-style', '/assets/owl.carousel.css' );
			$this->load_file( self::slug . '-style2', '/assets/owl.theme.default.min.css' );
		} // end if/else
	} // end register_scripts_and_styles
	
	/**
	 * Helper function for registering and enqueueing scripts and styles.
	 *
	 * @name	The 	ID to register with WordPress
	 * @file_path		The path to the actual file
	 * @is_script		Optional argument for if the incoming file_path is a JavaScript source file.
	 */
	private function load_file( $name, $file_path, $is_script = false ) {

		$url = plugins_url($file_path, __FILE__);
		$file = plugin_dir_path(__FILE__) . $file_path;

		if( file_exists( $file ) ) {
			if( $is_script ) {
				wp_register_script( $name, $url, array('jquery') ); //depends on jquery
				wp_enqueue_script( $name );
			} else {
				wp_register_style( $name, $url );
				wp_enqueue_style( $name );
			} // end if
		} // end if

	} // end load_file
  
} // end class
new PJSlider();

?>