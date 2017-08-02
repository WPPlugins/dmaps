<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class DMaps_Admin_Utils {

	/**
	* Returns an array with: post, page and custom post types
	* @return array 
	*/
	public function dmaps_get_post_types() {
				
		$args = array(
		   'public'   => true,
		   '_builtin' => false
		);

		$post_types = get_post_types( $args ); 
		$post_arr = array( 'post' => 'Post', 'page' => 'Page' );

		foreach ( $post_types  as $post_type ) {

			$arr = array($post_type => $post_type);
			$post_arr += $arr;

		}
		
		return $post_arr;	

	}

	/**
	* function from https://wordpress.org/plugins/simple-google-maps-short-code/
	* converts address in coordinates
	* @param string $address 
	* @return array $data['lat'] $data['lng'] $data['address']
	*/
	public function dmap_get_coordinates( $address, $force_refresh = false ) {

	    $address_hash = md5( $address );

	    $coordinates = get_transient( $address_hash );

	    if ($force_refresh || $coordinates === false) {

	    	$args       = array( 'address' => urlencode( $address ), 'sensor' => 'false' );
	    	$url        = add_query_arg( $args, 'http://maps.googleapis.com/maps/api/geocode/json' );
	     	$response 	= wp_remote_get( $url );

	     	if( is_wp_error( $response ) )
	     		return;

	     	$data = wp_remote_retrieve_body( $response );

	     	if( is_wp_error( $data ) )
	     		return;

			if ( $response['response']['code'] == 200 ) {

				$data = json_decode( $data );

				if ( $data->status === 'OK' ) {

				  	$coordinates = $data->results[0]->geometry->location;

				  	$cache_value['lat'] 	= $coordinates->lat;
				  	$cache_value['lng'] 	= $coordinates->lng;
				  	$cache_value['address'] = (string) $data->results[0]->formatted_address;

				  	set_transient($address_hash, $cache_value, 3600*24*30*3);
				  	$data = $cache_value;

				} elseif ( $data->status === 'ZERO_RESULTS' ) {

				  	return;

				} elseif( $data->status === 'INVALID_REQUEST' ) {

				   	return;

				} else {

					return;
				
				}

			} else {

			 	return;

			}

	    } else {

	       $data = $coordinates;
	    }

	    return $data;

	}

	/**
	* Check if a post type can show a map based on dmaps_show_dmaps_metabox option
	* @param string $id post id
	* @return boolean true|false
	*/
	public function dmaps_post_type_show_map( $id ) {

		$post_type = get_post_type( $id );
		$screens = get_option( 'dmaps_show_dmaps_metabox', array() );

		if( count($screens) == 0 || $screens == '') { return false; }

		foreach ( $screens as $screen ) {

			if ( $post_type == $screen ) {

				return true;

			}				

		}

	}	

}