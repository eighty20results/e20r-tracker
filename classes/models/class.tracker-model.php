<?php
namespace E20R\Tracker\Models;

use E20R\Tracker\Controllers\Tracker;
use E20R\Utilities\Utilities;

class Tracker_Model {
	
	/**
	 * @var null|Tracker_Model
	 */
	private static $instance = null;

	const post_type = 'e20r_girth_types';
	
	/**
	 * @return Tracker_Model
	 */
	static function getInstance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
	
	/**
	 * Load/return user info for Tracker member(s)
	 *
	 * @param $levels
	 *
	 * @return array|null|object
	 */
    public function loadUsers( $levels ) {

        global $wpdb;
	    
        if ( empty( $levels ) ) {
        	return array( $this->defaultUserData() );
        }
        
        $Tracker = Tracker::getInstance();

        $sql = "
                SELECT m.user_id AS id, u.display_name AS name, um.meta_value AS last_name
                FROM $wpdb->users AS u
                  INNER JOIN {$wpdb->pmpro_memberships_users} AS m
                    ON ( u.ID = m.user_id )
                    INNER JOIN {$wpdb->usermeta} AS um
                    ON ( u.ID = um.user_id )
                WHERE ( um.meta_key = 'last_name' ) AND ( m.status = 'active' AND m.membership_id IN ( [IN] ) )
                ORDER BY last_name ASC
        ";

        $sql = $Tracker->prepare_in( $sql, $levels );

        Utilities::get_instance()->log("SQL: " . print_r( $sql, true));

        $user_list = $wpdb->get_results( $sql, OBJECT );

        if (! empty( $user_list ) ) {
            return $user_list;
        }
        else {
            
            return array( $this->defaultUserData() );
        }

    }
	
	/**
	 * Generate and return a dummy user record
	 * @return \stdClass
	 */
    private function defaultUserData( ) {
	   
	    $data = new \stdClass();
	    $data->id = 0;
	    $data->name = 'No users found';
	    
	    return $data;
    }
	
	/**
	 * Save the settings for the Girth Types Custom Post Type
	 *
	 * @param int $post_id
	 *
	 * @return int
	 */
	public function saveSettings( $post_id ) {
		
		global $post;
		
		if ( ( ! isset( $post->post_type ) ) || ( 'e20r_girth_types' != $post->post_type ) ) {
			return $post_id;
		}
		
		if ( empty( $post_id ) ) {
			
			Utilities::get_instance()->log("No post ID supplied");
			return false;
		}
		
		if ( wp_is_post_revision( $post_id ) ) {
			return $post_id;
		}
		
		if ( defined( 'DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
			return $post_id;
		}
		
		Utilities::get_instance()->log("Saving metadata for the post_type(s)");
		
		if ( isset( $_POST[ 'e20r-girth-type-sortorder-nonce' ] ) ) {
			
			$newOrder = Utilities::get_instance()->get_variable( 'e20r_girth_order', null );
			
			if ( !empty( $newOrder ) ) {
				update_post_meta( $post_id, 'e20r_girth_type_sortorder', $newOrder );
			}
		}
		
	}
	
	/**
	 * Register the E20R Girth Custom Post Type (CPT)
	 */
	public static function registerCPT() {
		
		$labels =  array(
			'name' => __( 'Girth Types', 'e20r-tracker'  ),
			'singular_name' => __( 'Girth Type', 'e20r-tracker' ),
			'slug' => self::post_type,
			'add_new' => __( 'New Girth Type', 'e20r-tracker' ),
			'add_new_item' => __( 'New Girth Type', 'e20r-tracker' ),
			'edit' => __( 'Edit Girth Type', 'pmprosequence' ),
			'edit_item' => __( 'Edit Girth Type', 'e20r-tracker'),
			'new_item' => __( 'Add New', 'e20r-tracker' ),
			'view' => __( 'View Girth Types', 'e20r-tracker' ),
			'view_item' => __( 'View This Girth Type', 'e20r-tracker' ),
			'search_items' => __( 'Search Girths', 'e20r-tracker' ),
			'not_found' => __( 'No Girth Types Found', 'e20r-tracker' ),
			'not_found_in_trash' => __( 'No Girth Types Found In Trash', 'e20r-tracker' ),
		);
		
		$error = register_post_type(self::post_type,
			array( 'labels' => apply_filters( 'e20r-tracker-girth-cpt-labels', $labels ),
			       'public' => true,
			       'show_ui' => true,
			       'menu_icon' => '',
				// 'show_in_menu' => true,
				   'publicly_queryable' => true,
				   'hierarchical' => true,
				   'supports' => array('title','editor','excerpt','thumbnail','custom-fields','author'),
				   'can_export' => true,
				   'show_in_nav_menus' => false,
				   'show_in_menu' => 'e20r-tracker-info',
				   'rewrite' => array(
					   'slug' => apply_filters('e20r-tracker-girth-cpt-slug', 'girth'),
					   'with_front' => false,
				   ),
				   'has_archive' => apply_filters('e20r-tracker-girth-cpt-archive-slug', 'girths-archive'),
			)
		);
		
		if ( is_wp_error($error) ) {
			Utilities::get_instance()->log('ERROR: when registering e20r_girth_types: ' . $error->get_error_message);
		}
	}
}