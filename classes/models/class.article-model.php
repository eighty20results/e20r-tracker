<?php

namespace E20R\Tracker\Models;
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

/* Prevent direct access to the plugin */
if ( ! defined( 'ABSPATH' ) ) {
	die( "Sorry, you are not allowed to access this page directly." );
}

use E20R\Tracker\Controllers\Assignment;
use E20R\Tracker\Controllers\Action;
use E20R\Tracker\Controllers\Program;
use E20R\Tracker\Controllers\Tracker;
use E20R\Utilities\Utilities;

class  Article_Model extends Settings_Model {
	
	const post_type = 'e20r_articles';
	
	/**
	 * @var null| Article_Model
	 */
	private static $instance = null;
	/**
	 * The ID of the Article
	 *
	 * @var int|null $id
	 */
	protected $id;
	
	// protected $settings;
	
	public function __construct() {
		try {
			parent::__construct( 'article', self::post_type );
		} catch ( \Exception $exception ) {
			
			Utilities::get_instance()->log( "Error instantiating the Article Model class: " . $exception->getMessage() );
			
			return false;
		}
		
		return $this;
	}
	
	/**
	 * @return  Article_Model
	 */
	static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Register the E20R Article post type
	 */
	public static function registerCPT() {
		
		$labels = array(
			'name'               => __( 'Articles', 'e20r-tracker' ),
			'singular_name'      => __( 'Article', 'e20r-tracker' ),
			'slug'               => self::post_type,
			'add_new'            => __( 'New Article', 'e20r-tracker' ),
			'add_new_item'       => __( 'New Tracker Article', 'e20r-tracker' ),
			'edit'               => __( 'Edit Article', 'pmprosequence' ),
			'edit_item'          => __( 'Edit Tracker Article', 'e20r-tracker' ),
			'new_item'           => __( 'Add New', 'e20r-tracker' ),
			'view'               => __( 'View Articles', 'e20r-tracker' ),
			'view_item'          => __( 'View This Article', 'e20r-tracker' ),
			'search_items'       => __( 'Search Articles', 'e20r-tracker' ),
			'not_found'          => __( 'No Articles Found', 'e20r-tracker' ),
			'not_found_in_trash' => __( 'No Articles Found In Trash', 'e20r-tracker' ),
		);
		
		$error = register_post_type( self::post_type,
			array(
				'labels'             => apply_filters( 'e20r-tracker-article-cpt-labels', $labels ),
				'public'             => true,
				'show_ui'            => true,
				'menu_icon'          => '',
				// 'show_in_menu' => true,
				'publicly_queryable' => true,
				'hierarchical'       => true,
				'supports'           => array( 'title', 'excerpt', 'editor' ),
				'can_export'         => true,
				'show_in_nav_menus'  => false,
				'show_in_menu'       => 'e20r-tracker-articles',
				'rewrite'            => array(
					'slug'       => apply_filters( 'e20r-tracker-article-cpt-slug', 'tracker-articles' ),
					'with_front' => false,
				),
				'has_archive'        => apply_filters( 'e20r-tracker-article-cpt-archive-slug', 'tracker-articles-archive' ),
			)
		);
		
		if ( is_wp_error( $error ) ) {
			Utilities::get_instance()->log( 'ERROR: Failed to register e20r_articles CPT: ' . $error->get_error_message );
		}
	}
	
	public function find( $key, $value, $programId = - 1, $comp = 'LIKE', $order = 'DESC', $dont_drop = false, $dataType = 'numeric' ) {
		
		$Tracker = Tracker::getInstance();
		global $currentClient;
		
		$user_id = null;
		
		if ( isset( $currentClient->user_id ) ) {
			$user_id = $currentClient->user_id;
		}
		
		$result = parent::find( $key, $value, $programId, $comp, $order, $dataType );
		
		$member_days = $Tracker->getDelay( 'now', $user_id );
		
		foreach ( $result as $k => $data ) {
			
			Utilities::get_instance()->log( " Article_Model::find() - Drop setting is: " . ( $dont_drop ? "Don't Drop" : 'Drop' ) );
			Utilities::get_instance()->log( " Article_Model::find() - Survey setting is: " . ( $data->is_survey ? "Survey" : 'Not a survey' ) );
			$allow_drop = ! $dont_drop;
			
			if ( ! $dont_drop && true == $data->is_survey ) {
				$allow_drop = false;
			}
			
			if ( ( empty( $data->release_day ) || ( - 9999 == $data->release_day ) ) && ( true === $allow_drop ) ) {
				
				// Dropping articles containing the "Always released" indicator ( -9999 )
				Utilities::get_instance()->log( " Article_Model::find() - Dropping article {$data->id} since it's a 'default' article" );
				unset( $result[ $k ] );
			}
			
			if ( ( false === $dont_drop ) && ( $data->release_day > $member_days ) ) {
				
				Utilities::get_instance()->log( " Article_Model::find() - Dropping article {$data->id} since it's availability {$data->release_day} is after the current delay value for this user" );
				unset( $result[ $k ] );
			}
		}
		
		return $result;
	}
	
	public function findClosestArticle( $key, $value, $programId = - 1, $comp = '<=', $limit = 1, $sort_order = 'DESC', $type = 'numeric' ) {
		$args = array(
			'posts_per_page' => $limit,
			'post_type'      => self::post_type,
			'post_status'    => 'publish',
			'order_by'       => 'meta_value_num',
			'meta_key'       => "_e20r-article-{$key}",
			'order'          => $sort_order,
			'meta_query'     => array(
				array(
					'key'     => "_e20r-article-{$key}",
					'value'   => $value,
					'compare' => $comp,
					/* 'type'    => $type, */
				),
				array(
					'key'     => "_e20r-article-program_ids",
					'value'   => $programId,
					'compare' => '=',
				),
			),
		);
		
		if ( $key !== 'release_day' ) {
			$args['meta_query'][] = array(
				'key'     => "_e20r-article-release_day",
				'value'   => - 9999,
				'compare' => '!=',
			);
			$args['meta_query'][] = array(
				'key'     => "_e20r-article-release_day",
				'value'   => 0,
				'compare' => '!=',
			);
		}
		
		$a_list = $this->loadForQuery( $args );
		
		Utilities::get_instance()->log( " Article_Model()::findClosestArticle() - List of articles:" . print_r( $a_list, true ) );
		
		return $a_list;
	}
	
	private function loadForQuery( $args ) {
		
		$articleList = array();
		
		$query = new \WP_Query( $args );
		
		Utilities::get_instance()->log( " Article_Model::loadForQuery() - Returned articles: {$query->post_count}" );
		
		while ( $query->have_posts() ) {
			
			$query->the_post();
			
			$new     = $this->loadSettings( get_the_ID() );
			$new->id = get_the_ID();
			
			$articleList[] = $new;
		}
		
		wp_reset_postdata();
		
		return $articleList;
	}
	
	public function loadSettings( $id ) {
		
		global $currentArticle;
		
		if ( - 9999 === $id ) {
			Utilities::get_instance()->log( " Article_Model::loadSettings() - Loading default for the NULL article ID (-9999)" );
			$currentArticle = $this->defaultSettings();
			
			return $currentArticle;
		}
		
		if ( isset( $currentArticle->loaded ) && ( true === $currentArticle->loaded && $currentArticle->id === $id ) ) {
			Utilities::get_instance()->log( "Returning cached values for the Article" );
			
			return $currentArticle;
		}
		
		$currentArticle = parent::loadSettings( $id );
		
		if ( empty( $currentArticle->program_ids ) ) {
			
			$currentArticle->program_ids = array();
		}
		
		if ( empty( $currentArticle->activity_id ) ) {
			
			$currentArticle->activity_id = array();
		}
		
		if ( empty( $currentArticle->assignment_ids ) ) {
			
			$currentArticle->assignment_ids = array();
		} else {
			Utilities::get_instance()->log( " Article_Model::loadSettings() - Found configured assignments." );
			foreach ( $currentArticle->assignment_ids as $k => $assignmentId ) {
				
				if ( empty( $assignmentId ) ) {
					
					Utilities::get_instance()->log( " Article_Model::loadSettings() - Removing empty assignment key #{$k} with value " . empty( $assignmentId ) ? 'null' : $assignmentId );
					unset( $currentArticle->assignment_ids[ $k ] );
				}
			}
			
		}
		
		if ( empty( $currentArticle->action_ids ) ) {
			
			$currentArticle->action_ids = array();
		} else {
			Utilities::get_instance()->log( " Article_Model::loadSettings() - Found configured actions." );
			foreach ( $currentArticle->action_ids as $k => $checkinId ) {
				
				if ( empty( $checkinId ) ) {
					
					Utilities::get_instance()->log( " Article_Model::loadSettings() - Removing empty assignment key #{$k} with value " . empty( $checkinId ) ? 'null' : $checkinId );
					unset( $currentArticle->action_ids[ $k ] );
				}
			}
			
		}
		
		
		// Check if the post_id has defined excerpt we can use for this article.
		/**
		 * BUG FIX: Caused loop when saving/updating a linked post for an article
		 */
		if ( isset( $currentArticle->post_id ) && ( ! empty( $currentArticle->post_id ) ) ) {
			
			$post = get_post( $currentArticle->post_id );
			setup_postdata( $post );
			
			$article = get_post( $currentArticle->id );
			setup_postdata( $article );
			
			if ( ! empty( $post->post_excerpt ) && ( empty( $article->post_excerpt ) ) ) {
				
				$article->post_excerpt = $post->post_excerpt;
				
				Utilities::get_instance()->log("Remove save_post and post_updated actions");
				
				Tracker::remove_save_actions();
				wp_update_post( $article );
				Tracker::add_save_actions();
			}
			
		}
		/* */
		$currentArticle->loaded = true;
		
		return $currentArticle;
	}
	
	public function defaultSettings() {
		
		$defaults = parent::defaultSettings();
		
		$defaults->id             = null;
		$defaults->program_ids    = array();
		$defaults->post_id        = null;
		$defaults->activity_id    = array();
		$defaults->release_day    = null;
		$defaults->release_date   = null;
		$defaults->assignment_ids = array();
		$defaults->action_ids     = array();
		$defaults->is_survey      = false;
		$defaults->is_preview_day = false;
		$defaults->summary_day    = false;
		$defaults->max_summaries  = 7;
//        $defaults->assignments = array();
//        $defaults->checkins = array();
		$defaults->measurement_day = false;
		$defaults->photo_day       = false;
		$defaults->prefix          = "Lesson";
		$defaults->complete        = false;
		
		// Utilities::get_instance()->log(" Article_Model::defaultSettings() - Defaults loaded");
		return $defaults;
	}
	
	public function load_for_archive( $last_day = null, $order = 'ASC', $program_id = - 1 ) {
		
		$Program = Program::getInstance();
		$Tracker = Tracker::getInstance();
		
		global $currentClient;
		global $currentProgram;
		
		global $current_user;
		
		if ( ! isset( $currentClient->user_id ) || ( isset( $currentClient->user_id ) && ( $currentClient->user_id == 0 ) ) ) {
			$user_id = $current_user->ID;
		} else {
			$user_id = $currentClient->user_id;
		}
		
		if ( is_null( $last_day ) || ! is_numeric( $last_day ) ) {
			Utilities::get_instance()->log( " Article_Model::load_for_archive() - No 'last-day' variable provided. Using user information" );
			$last_day = ( $Tracker->getDelay( 'now', $user_id ) - 1 );
		}
		
		Utilities::get_instance()->log( " Article_Model::load_for_article() - Last day of archive is: {$last_day}" );
		
		if ( ! isset( $currentProgram->id ) || ( isset( $currentProgram->id ) && ( $program_id != $currentProgram->id ) ) ) {
			
			if ( $program_id === - 1 ) {
				
				$Program->getProgramIdForUser( $user_id );
			} else {
				$Program->setProgram( $program_id );
			}
		}
		
		if ( $program_id == - 1 ) {
			$program_id = $currentProgram->id;
		}
		
		$list = parent::find( 'release_day', array( 0, $last_day ), $program_id, 'BETWEEN' );
		
		return $list;
	}
	
	public function findArticle( $key, $value, $programId = - 1, $comp = '=', $order = 'DESC', $type = 'NUMERIC' ) {
		
		$article = null;
		
		$list = parent::find( $key, $value, $programId, $comp, $order, $type );
		
		Utilities::get_instance()->log( " Article_Model::findArticle() - Loaded " . count( $list ) . " articles" );
		
		foreach ( $list as $a ) {
			
			if ( ( - 9999 != $a->release_day ) && ( $programId !== - 1 ) ) {
				
				Utilities::get_instance()->log( " Article_Model::findArticle() - Returning {$a->id} because it matches program ID {$programId}" );
				$article[] = $a;
			}
			
			/*            if ( ( !is_null( $multi ) ) &&
							( ( $programId !== -1 ) && ( isset( $a->programs ) && in_array( $programId, $a->programs ) ) ) ) {

							Utilities::get_instance()->log( " Article_Model::findArticle() - Returning more than one article for program ID == {$programId}" );
							$article[] = $a;
						}
			*/
		}
		
		if ( count( $article ) == 1 ) {
			$article = array_pop( $article );
		}
		
		return empty( $article ) ? $list : $article;
	}
	
	/**
	 * Save the Article Settings to the metadata table.
	 *
	 * @param array $settings - Array of settings for the specific article.
	 *
	 * @return bool - True if successful at updating article settings
	 */
	public function saveSettings( \stdClass $settings ) {
		
		$Assignment = Assignment::getInstance();
		$Action     = Action::getInstance();
		
		$articleId = $settings->id;
		
		$defaults = self::defaultSettings();
		
		Utilities::get_instance()->log( " Article_Model::saveSettings() - Saving article Metadata" );
		
		$error = false;
		
		foreach ( $defaults as $key => $value ) {
			
			if ( in_array( $key, array( 'id' ) ) ) {
				continue;
			}
			
			if ( 'post_id' == $key ) {
				
				Utilities::get_instance()->log( " Article_Model::saveSettings() - Saving the article ID with the post " );
				update_post_meta( $settings->{$key}, '_e20r-article-id', $articleId );
			}
			
			// if ( 'assignments' == $key ) {
			if ( ( 'assignment_ids' == $key ) || ( 'action_ids' == $key ) ) {
				
				Utilities::get_instance()->log( " Article_Model::saveSettings() - Processing assignments (include program info): " . print_r( $settings->{$key}, true ) );
				
				foreach ( $settings->{$key} as $k => $id ) {
					
					if ( empty( $id ) || ( 0 == $id ) ) {
						
						Utilities::get_instance()->log( " Article_Model::saveSettings() - Removing empty assignment key #{$k} with value {$id}" );
						unset( $settings->{$key}[ $k ] );
					}
					
					Utilities::get_instance()->log( " Article_Model::saveSettings() - Adding program IDs for assignment {$id}" );
					
					if ( ( 'assignment_ids' == $key ) && ( ! $Assignment->addPrograms( $id, $settings->program_ids ) ) ) {
						
						Utilities::get_instance()->log( " Article_Model::saveSettings() - ERROR: Unable to save program list for assignment {$id}: " . print_r( $settings->program_ids, true ) );
					}
					
					if ( ( 'action_ids' == $key ) && ( ! $Action->addPrograms( $id, $settings->program_ids ) ) ) {
						
						Utilities::get_instance()->log( " Article_Model::saveSettings() - ERROR: Unable to save program list for action #{$id}: " . print_r( $settings->program_ids, true ) );
					}
				}
			}
			
			if ( false === $this->settings( $articleId, 'update', $key, $settings->{$key} ) ) {
				
				Utilities::get_instance()->log( " Article_Model::saveSettings() - ERROR saving {$key} setting ({$settings->{$key}}) for article definition with ID: {$articleId}" );
				
				$error = true;
			}
		}
		
		return ( ! $error );
	}
}