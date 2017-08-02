<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class DMaps_Settings {

	private static $_instance = null;

	public $parent = null;
	public $base = '';
	public $settings = array();

	public function __construct ( $parent ) {

		$this->parent = $parent;
		$this->utils = new DMaps_Admin_Utils();

		$this->base = 'dmaps_';

		add_action( 'init', array( $this, 'init_settings' ), 11 );
		add_action( 'admin_init' , array( $this, 'register_settings' ) );
		add_action( 'admin_menu' , array( $this, 'add_menu_item' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file ) , array( $this, 'add_settings_link' ) );

	}

	public function init_settings () {

		$this->settings = $this->settings_fields();

	}

	public function add_menu_item () {

		$page = add_options_page( __( 'DMaps Settings', 'dmaps' ) , __( 'DMaps Settings', 'dmaps' ) , 'manage_options' , $this->parent->_token . '_settings' ,  array( $this, 'settings_page' ) );
		add_action( 'admin_print_styles-' . $page, array( $this, 'settings_assets' ) );

	}


	public function settings_assets () {

		wp_enqueue_style( 'farbtastic' );
    	wp_enqueue_script( 'farbtastic' );

    	//wp_enqueue_media();

    	wp_register_script( $this->parent->_token . '-settings-js', $this->parent->assets_url . 'js/settings.js', array( 'farbtastic', 'jquery' ), '1.0.0' );
    	wp_enqueue_script( $this->parent->_token . '-settings-js' );

	}

	public function add_settings_link ( $links ) {

		$settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __( 'Settings', 'dmaps' ) . '</a>';
  		array_push( $links, $settings_link );
  		return $links;

	}

	private function settings_fields () {

    	$post_arr = $this->utils->dmaps_get_post_types();

		$settings['standard'] = array(
			'title'					=> '',
			'description'			=> '',
			'fields'				=> array(
				array(
					'id' 			=> 'show_dmaps_metabox',
					'label'			=> __( 'Show DMaps Metabox', 'dmaps' ),
					'description'	=> '',
					'type'			=> 'checkbox_multi',
					'options'		=> $post_arr,
					'default'		=> array()
				),
				array(
					'id' 			=> 'map_height',
					'label'			=> __( 'Map height' , 'dmaps' ),
					'description'	=> '',
					'type'			=> 'number',
					'default'		=> '300',
					'placeholder'	=> __( 'Map height', 'dmaps' )
				),
				array(
					'id' 			=> 'map_zoom',
					'label'			=> __( 'Map zoom' , 'dmaps' ),
					'description'	=> '',
					'type'			=> 'number',
					'default'		=> '13',
					'placeholder'	=> __( 'Map zoom', 'dmaps' )
				),				
				array(
					'id' 			=> 'radius_color',
					'label'			=> __( 'Radius color', 'dmaps' ),
					'description'	=> '',
					'type'			=> 'color',
					'default'		=> '#21759B'
				),
			)
		);

		$settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;

	}

	public function register_settings () {

		if( is_array( $this->settings ) ) {

			$current_section = '';

			if( isset( $_POST['tab'] ) && $_POST['tab'] ) {

				$current_section = $_POST['tab'];

			} else {

				if( isset( $_GET['tab'] ) && $_GET['tab'] ) {

					$current_section = $_GET['tab'];

				}

			}

			foreach( $this->settings as $section => $data ) {

				if( $current_section && $current_section != $section ) continue;

				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), $this->parent->_token . '_settings' );

				foreach( $data['fields'] as $field ) {

					$validation = '';

					if( isset( $field['callback'] ) ) {

						$validation = $field['callback'];

					}

					$option_name = $this->base . $field['id'];
					register_setting( $this->parent->_token . '_settings', $option_name, $validation );

					add_settings_field( $field['id'], $field['label'], array( $this->parent->admin, 'display_field' ), $this->parent->_token . '_settings', $section, array( 'field' => $field, 'prefix' => $this->base ) );
				
				}

				if( ! $current_section ) break;

			}

		}

	}

	public function settings_section ( $section ) {

		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;

	}

	public function settings_page () {

		$html = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
			$html .= '<h2>' . __( 'DMaps Settings' , 'dmaps' ) . '</h2>' . "\n";

			$tab = '';
			if( isset( $_GET['tab'] ) && $_GET['tab'] ) {
				$tab .= $_GET['tab'];
			}

			if( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

				$html .= '<h2 class="nav-tab-wrapper">' . "\n";

				$c = 0;
				foreach( $this->settings as $section => $data ) {

					// Set tab class
					$class = 'nav-tab';
					if( ! isset( $_GET['tab'] ) ) {
						if( 0 == $c ) {
							$class .= ' nav-tab-active';
						}
					} else {
						if( isset( $_GET['tab'] ) && $section == $_GET['tab'] ) {
							$class .= ' nav-tab-active';
						}
					}

					// Set tab link
					$tab_link = add_query_arg( array( 'tab' => $section ) );
					if( isset( $_GET['settings-updated'] ) ) {
						$tab_link = remove_query_arg( 'settings-updated', $tab_link );
					}

					// Output tab
					$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

					++$c;
				}

				$html .= '</h2>' . "\n";
			}

			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

				ob_start();
				settings_fields( $this->parent->_token . '_settings' );
				do_settings_sections( $this->parent->_token . '_settings' );
				$html .= ob_get_clean();

				$html .= '<p class="submit">' . "\n";
					$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
					$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings' , 'dmaps' ) ) . '" />' . "\n";
				$html .= '</p>' . "\n";
			$html .= '</form>' . "\n";
		$html .= '</div>' . "\n";

		echo $html;
	}

	public static function instance ( $parent ) {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}

		return self::$_instance;

	}

	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} 

	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} 

}