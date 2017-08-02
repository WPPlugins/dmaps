<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class DMaps {

	private static $_instance = null;

	public $settings = null;
	public $_version;
	public $_token;
	public $file;
	public $dir;
	public $assets_dir;
	public $assets_url;

	public function __construct ( $file = '', $version = '1.0' ) {

		$this->_version = $version;
		$this->_token = 'dmaps';

		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		register_activation_hook( $this->file, array( $this, 'install' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );
		add_action( 'wp_head', array( $this, 'dmaps_css' ) );
		
		if( is_admin() ) {
			$this->admin = new DMaps_Admin_API();
		}		

		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		add_action( 'add_meta_boxes', array( $this, 'dmaps_add_meta_box' ) );

		$post_types = get_option( 'dmaps_show_dmaps_metabox', array() );

		if( $post_types != '') {

			foreach ( $post_types  as $post_type ) {

				add_filter( $post_type . '_custom_fields', array( $this, 'dmaps_custom_fields' ), 10, 2 );

			}

		}

		add_shortcode( 'dmaps', array( $this, 'dmaps_shortcode' ) );
		add_filter( 'the_content', array( $this, 'dmaps_filter' ) );	

	}

	public function dmaps_add_meta_box() {

		$screens = get_option( 'dmaps_show_dmaps_metabox', array() );

		if( count($screens) == 0 || $screens == '') { return; }

		$this->admin->add_meta_box(

			$id = 'dmaps_metabox', 
			$title = 'DMaps', 
			$post_types = $screens,
			$context = 'advanced', 
			$priority = 'default', 
			$callback_args = null

		);		

	}

	public function dmaps_custom_fields ( $fields, $post_type ) {

	  $fields = array(

	    array(
	      'id' => '_dmaps_address',
	      'metabox' => 'dmaps_metabox',
	      'label' => __( 'Address', 'dmaps' ),
	      'description' => '',
	      'type' => 'text',
	      'default' => '',
	    ),

	    array(
	      'id' => '_dmaps_radius',
	      'metabox' => 'dmaps_metabox',
	      'label' => __( 'Radius', 'dmaps' ),
	      'description' => __( 'If a numeric value (meters) is inserted here, shows a radius circle in meters without marker. If leave it blank, a marker is going to show instead.', 'dmaps' ),
	      'type' => 'text',
	      'default' => '',
	    )

	  );

	  return $fields;

	}

	/**
	* [dmaps address="new york" radius="1000"]
	*/
	public function dmaps_shortcode( $atts ) { 
		
		$a = shortcode_atts( array(

	        'address' => '',
	        'radius' => ''

	    ), $atts );

	    $address = $a['address'];
	    $radius = $a['radius'];

	    if( $address ) {

			$id = uniqid();

			$this->utils = new DMaps_Admin_Utils();
			$coordinates = $this->utils->dmap_get_coordinates( $address );

			$zoom = get_option( 'dmaps_map_zoom', '13' );
			$radius_color = get_option( 'dmaps_radius_color', '#21759B' );
			$height = get_option( 'dmaps_map_height', '300' );			

			return $this->dmaps_create_map( $id, $coordinates['lat'], $coordinates['lng'], $radius, $zoom, $radius_color, $height);

		} else {

			return; // @todo return error?

		}	   

	}

	public function dmaps_filter( $content ) {
	    
	    global $wp_query;

	    $id = $wp_query->post->ID;
	    $address = get_post_meta( $id, '_dmaps_address', true );
	    $radius = get_post_meta( $id, '_dmaps_radius', true );

	    $this->utils = new DMaps_Admin_Utils();

	    $show_map = $this->utils->dmaps_post_type_show_map( $id );

	    if ( $address && $show_map == true ) {

			$coordinates = $this->utils->dmap_get_coordinates( $address );

			$zoom = get_option( 'dmaps_map_zoom', '13' );
			$radius_color = get_option( 'dmaps_radius_color', '#21759B' );
			$height = get_option( 'dmaps_map_height', '300' );	    	

	    	$content .= $this->dmaps_create_map( $id, $coordinates['lat'], $coordinates['lng'], $radius, $zoom, $radius_color, $height);

	    	return $content;

	    } else {

	    	return $content;

	    }

	}

	/**
	* Creates the DOM for rendering the map via data attributes
	* @param string $id
	* @param string $lat
	* @param string $lng
	* @param string $radius
	* @param string $zoom
	* @param string $radius_color
	* @param string $height
	* @return string $map
	*/
	public function dmaps_create_map( $id, $lat, $lng, $radius, $zoom='13', $radius_color='#21759B', $height='300') {

		// @todo sanitize all parameters?

		wp_register_script( 'dmaps_handle', esc_url( $this->assets_url ) . 'js/dmaps.js' );
		wp_enqueue_script( 'dmaps_handle' );
		
		$map = '<div data-id="'. $id .'" data-lat="'. $lat .'" data-lng="'. $lng .'" data-radius="'. $radius .'" data-zoom="'. $zoom .'" data-radiusc="'. $radius_color .'"class="dmaps_canvas_style" id="dmaps_canvas_'. $id .'" style="height:'. $height .'px;"></div>';

		return $map;

	}

	public function enqueue_styles () {

		wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-frontend' );

	}

	public function enqueue_scripts () {

		wp_register_script( $this->_token . '-google-maps-api', 'http://maps.google.com/maps/api/js?sensor=true', false, '3', true );
		wp_enqueue_script( $this->_token . '-google-maps-api' );

		wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-frontend' );

	} 

	public function dmaps_css() {

		echo '<style type="text/css">.dmaps_canvas_style {
		    vertical-align: middle;
		    max-width: 100%;
		    width:100%;
		    width: auto\9;
		}.dmaps_canvas_style img {
			max-width:none;
		}</style>';

	}

	public function load_localisation () {

		load_plugin_textdomain( 'dmaps', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );

	}

	public function load_plugin_textdomain () {

	    $domain = 'dmaps';
	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );

	} 

	public static function instance ( $file = '', $version = '1.0.0' ) {

		if ( is_null( self::$_instance ) ) {

			self::$_instance = new self( $file, $version );

		}

		return self::$_instance;

	}

	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	}

	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	}

	public function install () {
		$this->_log_version_number();
	}

	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	} 

}