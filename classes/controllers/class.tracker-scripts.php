<?php
/**
 * The E20R Tracker Plugin – a coaching client management plugin for WordPress. Tracks client training, habits, educational reminders, etc.
 * Copyright (c) 2018, Wicked Strong Chicks, LLC
 *
 * The E20R Tracker Plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 * You can contact us at info@eighty20results.com
 *
 *
 */

namespace E20R\Tracker\Controllers;

use E20R\Tracker\Models\Assignment_Model;
use E20R\Tracker\Models\Article_Model;
use E20R\Tracker\Models\Action_Model;
use E20R\Tracker\Models\Program_Model;
use E20R\Tracker\Models\Workout_Model;

/**
 * Class Tracker_Scripts
 * @package E20R\Tracker\Controllers
 */
class Tracker_Scripts {
	
	/**
	 * @var null|Tracker_Scripts
	 */
	static private $instance = null;
	
	/**
	 * Tracker_Scripts constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * @return Tracker_Scripts|null;
	 */
	static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	public function loadHooks() {
		
		$this->has_activity_shortcode();
		$this->has_clientlist_shortcode();
		$this->has_dailyProgress_shortcode();
		$this->has_exercise_shortcode();
		$this->has_gravityforms_shortcode();
		$this->has_weeklyProgress_shortcode();
		$this->has_summary_shortcode();
		$this->has_profile_shortcode();
		$this->has_measurementprogress_shortcode();
	}
	
	/**
	 * Load all JS for Admin page
	 */
	public function load_adminJS()
	{
		
		if ( is_admin() && ( ! wp_script_is( 'e20r_tracker_admin', 'enqueued' ) ) ) {
			
			global $e20r_plot_jscript;
			
			wp_enqueue_style( "jquery-ui-tabs", "//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css", false, '1.11.2' );
			
			wp_enqueue_style( "e20r-tracker-admin", E20R_PLUGINS_URL . "/css/e20r-tracker-admin.min.css", false, E20R_VERSION );
			wp_enqueue_style( "e20r-activity", E20R_PLUGINS_URL . "/css/e20r-activity.min.css", false, E20R_VERSION );
			wp_enqueue_style( "e20r-assignments", E20R_PLUGINS_URL . "/css/e20r-assignments.min.css", false, E20R_VERSION );
			wp_enqueue_style( "codetabs", E20R_PLUGINS_URL . "/css/codetabs/codetabs.css", false, E20R_VERSION );
			wp_enqueue_style( "code.animate", E20R_PLUGINS_URL . "/css/codetabs/code.animate.css", false, E20R_VERSION );
			wp_enqueue_style('jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
			wp_enqueue_style('jquery-ui-datetimepicker', E20R_PLUGINS_URL . "/css/jquery.datetimepicker.min.css", false, E20R_VERSION);
			
			E20R_Tracker::dbg("Tracker_Scripts::load_adminJS() - Loading admin javascript");
			wp_register_script( 'select2', "//cdnjs.cloudflare.com/ajax/libs/select2/" . E20R_SELECT2_VER ."/js/select2.min.js", array('jquery'), E20R_SELECT2_VER, true );
			wp_register_script( 'jquery.timeago', E20R_PLUGINS_URL . '/js/libraries/jquery.timeago.min.js', array( 'jquery' ), '0.1', true );
			wp_register_script( 'jquery.autoresize', E20R_PLUGINS_URL . '/js/libraries/jquery.autogrowtextarea.min.js' , array('jquery'), E20R_VERSION, true );
			wp_register_script( 'codetabs', E20R_PLUGINS_URL . '/js/libraries/codetabs/codetabs.min.js', array( 'jquery' ), E20R_VERSION, true );
			wp_register_script( 'jquery-ui-tabs', "//code.jquery.com/ui/1.11.2/jquery-ui.js", array('jquery'), '1.11.2', true);
			wp_register_script( 'jquery-ui-datetimepicker', E20R_PLUGINS_URL . '/js/libraries/jquery.datetimepicker.min.js', array('jquery-ui-core' ,'jquery-ui-datepicker', 'jquery-ui-slider' ), E20R_VERSION, true);
			
			wp_register_script( 'e20r-tracker-js', E20R_PLUGINS_URL . '/js/e20r-tracker.min.js', array( 'jquery.timeago' ), '0.1', true );
			wp_register_script( 'e20r-progress-page', E20R_PLUGINS_URL . '/js/e20r-progress-measurements.min.js', array('jquery'), E20R_VERSION, false); // true == in footer of body.
			wp_register_script( 'e20r_tracker_admin', E20R_PLUGINS_URL . '/js/e20r-tracker-admin.min.js', array('jquery', 'e20r-progress-page'), E20R_VERSION, false); // true == in footer of body.
			wp_register_script( 'e20r-assignment-admin', E20R_PLUGINS_URL . '/js/e20r-assignment-admin.min.js', array( 'jquery' ), E20R_VERSION, true);
			wp_register_script( 'e20r-assignments', E20R_PLUGINS_URL . '/js/e20r-assignments.min.js', array( 'jquery', 'jquery.autoresize' ), E20R_VERSION, true);
			
			// $this->load_frontend_scripts('progress_overview');
			wp_localize_script( 'e20r-progress-page', 'e20r_admin',
				array(
					'timeout' => 30000,
					'longpoll_timeout' => apply_filters('e20r-tracker-longpoll-timeout', 300000),
				)
			);
			
			
			$e20r_plot_jscript = true;
			self::register_plotSW();
			self::enqueue_plotSW();
			$e20r_plot_jscript = false;
			wp_print_scripts( 'select2' );
			wp_print_scripts( 'jquery.timeago' );
			wp_print_scripts( 'jquery.autoresize' );
			wp_print_scripts( 'jquery-ui-tabs' );
			wp_print_scripts( 'codetabs' );
			wp_print_scripts( 'jquery-ui-datetimepicker' );
			wp_print_scripts( 'e20r-tracker-js' );
			wp_print_scripts( 'e20r-progress-page' );
			wp_print_scripts( 'e20r_tracker_admin' );
			wp_print_scripts( 'e20r-assignments' );
			wp_print_scripts( 'e20r-assignment-admin' );
		}
	}
	
	/**
	 * Load the plotting/graphing software scripts/styles
	 *
	 * @param null $hook
	 */
	public function register_plotSW( $hook = null ) {
		
		global $e20r_plot_jscript;
		global $post;
		global $e20rAdminPage;
		global $ClientInfoPage;
		
		if ( $e20r_plot_jscript || ( !is_null($hook) && $hook == $ClientInfoPage ) || ( !is_null($hook) && $hook == $e20rAdminPage ) || has_shortcode( $post->post_content, 'user_progress_info' ) ) {
			
			E20R_Tracker::dbg( "Tracker::register_plotSW() - Plotting javascript being registered." );
			
			wp_deregister_style( 'jqplot' );
			wp_enqueue_style( 'jqplot', E20R_PLUGINS_URL . '/js/jQPlot/core/jquery.jqplot.min.css', false, E20R_VERSION );
			
			wp_deregister_script( 'jqplot' );
			wp_register_script( 'jqplot', E20R_PLUGINS_URL . '/js/jQPlot/core/jquery.jqplot.min.js', array( 'jquery' ), E20R_VERSION );
			
			wp_deregister_script( 'jqplot_export' );
			wp_register_script( 'jqplot_export', E20R_PLUGINS_URL . '/js/jQPlot/plugins/export/exportImg.min.js', array( 'jqplot' ), E20R_VERSION );
			
			wp_deregister_script( 'jqplot_pie' );
			wp_register_script( 'jqplot_pie', E20R_PLUGINS_URL . '/js/jQPlot/plugins/pie/jqplot.pieRenderer.min.js', array( 'jqplot' ), E20R_VERSION );
			
			wp_deregister_script( 'jqplot_text' );
			wp_register_script( 'jqplot_text', E20R_PLUGINS_URL . '/js/jQPlot/plugins/text/jqplot.canvasTextRenderer.min.js', array( 'jqplot' ), E20R_VERSION );
			
			wp_deregister_script( 'jqplot_mobile' );
			wp_register_script( 'jqplot_mobile', E20R_PLUGINS_URL . '/js/jQPlot/plugins/mobile/jqplot.mobile.min.js', array( 'jqplot' ), E20R_VERSION );
			
			wp_deregister_script( 'jqplot_date' );
			wp_register_script( 'jqplot_date', E20R_PLUGINS_URL . '/js/jQPlot/plugins/axis/jqplot.dateAxisRenderer.min.js', array( 'jqplot' ), E20R_VERSION );
			
			wp_deregister_script( 'jqplot_label' );
			wp_register_script( 'jqplot_label', E20R_PLUGINS_URL . '/js/jQPlot/plugins/axis/jqplot.canvasAxisLabelRenderer.min.js', array( 'jqplot' ), E20R_VERSION );
			
			wp_deregister_script( 'jqplot_pntlabel' );
			wp_register_script( 'jqplot_pntlabel', E20R_PLUGINS_URL . '/js/jQPlot/plugins/points/jqplot.pointLabels.min.js', array( 'jqplot' ), E20R_VERSION );
			
			wp_deregister_script( 'jqplot_ticks' );
			wp_register_script( 'jqplot_ticks', E20R_PLUGINS_URL . '/js/jQPlot/plugins/axis/jqplot.canvasAxisTickRenderer.min.js', array( 'jqplot' ), E20R_VERSION );
		}
	}
	
	/**
	 * Load graphing scripts (if needed)
	 */
	private function enqueue_plotSW( $hook = null ) {
		
		global $e20r_plot_jscript, $post;
		global $e20rAdminPage;
		global $ClientInfoPage;
		
		if ( $e20r_plot_jscript || $hook == $ClientInfoPage || $hook == $e20rAdminPage || has_shortcode( $post->post_content, 'progress_overview' ) ) {
			
			E20R_Tracker::dbg("Tracker::enqueue_plotSW() -- Loading javascript for graph generation");
			
			wp_print_scripts( array(
					'jqplot', 'jqplot_export', 'jqplot_pie', 'jqplot_text', 'jqplot_mobile', 'jqplot_date', 'jqplot_label', 'jqplot_pntlabel', 'jqplot_ticks',
				)
			);
			
		}
	}
	
	/**
	 * Identify and return the post type being processed
	 *
	 * @return null|string
	 */
	public static function getCurrentPostType() {
		
		global $post, $typenow, $current_screen;
		
		//we have a post so we can just get the post type from that
		if ( $post && $post->post_type ) {
			
			return $post->post_type;
		} //check the global $typenow - set in admin.php
		elseif( $typenow ) {
			
			return $typenow;
		} //check the global $current_screen object - set in sceen.php
		elseif( $current_screen && $current_screen->post_type ) {
			
			return $current_screen->post_type;
		} //lastly check the post_type querystring
		elseif( isset( $_REQUEST['post_type'] ) ) {
			
			return sanitize_key( $_REQUEST['post_type'] );
		}
		
		//we do not know the post type!
		return null;
	}
	
	/**
	 * Load JS on admin pages
	 *
	 * @param $hook
	 */
	public function enqueue_admin_scripts( $hook ) {
		
		E20R_Tracker::dbg("Tracker::enqueue_admin_scripts() - Loading javascript");
		
		global $e20rAdminPage;
		global $ClientInfoPage;
		global $post;
		
		
		E20R_Tracker::dbg("Loading the admin page: {$e20rAdminPage} or {$ClientInfoPage}");
		
		wp_enqueue_style( 'fontawesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css', false, '4.4.0' );
		
		$e20r_post_types = apply_filters( 'e20r_tracker_used_post_types', array(
				Assignment_Model::post_type,
				Article_Model::post_type,
				Action_Model::post_type,
				Workout_Model::post_type,
				Exercise::post_type,
				Program_Model::post_type,
				'e20r_girth_types',
			)
		);
		
		if( ( is_admin() && ( isset( $post->post_type ) && in_array( $post->post_type, $e20r_post_types ))) || $hook == $e20rAdminPage || $hook == $ClientInfoPage ) {
			
			E20R_Tracker::dbg("Loading for Tracker admin page!");
			$this->load_adminJS();
			
			global $e20r_plot_jscript;
			
			$e20r_plot_jscript = true;
			$this->register_plotSW( $hook );
			$this->enqueue_plotSW( $hook );
			$e20r_plot_jscript = false;
			
			wp_enqueue_style( 'e20r_tracker', E20R_PLUGINS_URL . '/css/e20r-tracker.min.css', false, E20R_VERSION );
			// wp_enqueue_style( 'e20r_tracker-admin', E20R_PLUGINS_URL . '/css/e20r-tracker-admin.min.css', false, E20R_VERSION );
			wp_enqueue_style( 'select2', "//cdnjs.cloudflare.com/ajax/libs/select2/" . E20R_SELECT2_VER ."/css/select2.min.css", null, E20R_SELECT2_VER );
			wp_enqueue_script( 'jquery.timeago' );
			wp_enqueue_script( 'select2' );
			
		}
		
		if( $hook == 'edit.php' || $hook == 'post.php' || $hook == 'post-new.php' ) {
			
			switch( self::getCurrentPostType() ) {
				
				case Action_Model::post_type:
					
					wp_enqueue_script( 'jquery-ui-datepicker' );
					wp_enqueue_style( 'jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
					
					$type = 'action';
					$deps = array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'select2' );
					break;
				
				case Program_Model::post_type:
					
					wp_enqueue_style( 'e20r-tracker-program-admin', E20R_PLUGINS_URL . '/css/e20r-tracker-admin.min.css', false, E20R_VERSION );
					$type = 'program';
					$deps = array('jquery', 'jquery-ui-core', 'select2' );
					break;
				
				case  Article_Model::post_type:
					
					$type = 'article';
					$deps = array( 'jquery', 'jquery-ui-core', 'select2' );
					
					break;
				
				case Workout_Model::post_type:
					
					wp_enqueue_style( 'e20r-tracker-workout-admin', E20R_PLUGINS_URL . '/css/e20r-tracker-admin.min.css', false, E20R_VERSION );
					$type = 'workout';
					$deps = array( 'jquery', 'jquery-ui-core', 'select2' );
					break;
				
				case Assignment_Model::post_type:
					
					wp_enqueue_style( 'e20r-tracker-assignment-admin', E20R_PLUGINS_URL . '/css/e20r-tracker-admin.min.css', false, E20R_VERSION );
					$type = 'assignment';
					$deps = array( 'jquery', 'jquery-ui-core', 'select2' );
					break;
				
				default:
					$type = null;
			}
			
			E20R_Tracker::dbg("Tracker::enqueue_admin_scripts() - Loading Custom Post Type specific admin script");
			
			if ( $type !== null ) {
				
				wp_register_script( 'e20r-cpt-admin', E20R_PLUGINS_URL . "/js/e20r-{$type}-admin.min.js", $deps, E20R_VERSION, true );
				
				/* Localize ajax script */
				wp_localize_script( 'e20r-cpt-admin', 'e20r_tracker',
					array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'timeout' => 30000,
						'longpoll_timeout' => apply_filters('e20r-tracker-longpoll-timeout', 300000),
						'lang'    => array(
							'no_entry' => __( 'Please select', 'e20r-tracker' ),
							'no_ex_entry' => __( 'Please select an exercise', 'e20r-tracker' ),
							'adding' => __( 'Adding...', 'e20r-tracker' ),
							'add'   => __( 'Add', 'e20r-tracker' ),
							'saving' => __( 'Saving...', 'e20r-tracker' ),
							'save'   => __( 'Save', 'e20r-tracker' ),
							'edit'   => __( 'Update', 'e20r-tracker' ),
							'remove' => __( 'Remove', 'e20r-tracker' ),
							'empty'  => __( 'No exercises found.', 'e20r-tracker' ),
							'none'   => __( 'None', 'e20r-tracker' ),
							'no_exercises'  => __( 'No exercises found', 'e20r-tracker' ),
						),
					)
				);
				
				wp_enqueue_script( 'e20r-cpt-admin' );
			}
		}
	}
	
	/**
	 * Load the frontend script(s) when/where applicable
	 *
	 * @param $events
	 */
	public function load_frontend_scripts( $events ) {
		
		if (defined('DOING_AJAX') && DOING_AJAX) {
			
			E20R_Tracker::dbg("Tracker::load_frontend_scripts() - Doing AJAX call. No need to load any scripts/styling");
			return;
		}
		
		global $e20r_plot_jscript;
		$Tracker = Tracker::getInstance();
		
		global $current_user;
		global $post;
		
		global $currentClient;
		global $currentProgram;
		
		if ( !is_user_logged_in() ) {
			
			auth_redirect();
		}
		
		if ( !is_array( $events ) ) {
			$events = array( $events );
		}
		
		$load_jq_plot = false;
		
		E20R_Tracker::dbg("Tracker::load_frontend_scripts() - Loading " . count( $events ) . " script events");
		foreach( $events as $event ) {
			
			$css_list = array( 'print', 'e20r-tracker', 'e20r-tracker-activity' );
			$css = array(
				"e20r-print" => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/css/print.css' : '/css/print.min.css' ),
				"e20r-tracker" => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/css/e20r-tracker.css' : '/css/e20r-tracker.min.css'),
				"e20r-tracker-activity" => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/css/e20r-activity.css' : '/css/e20r-activity.min.css' ),
			);
			
			$scripts = array();
			$prereqs = array(
				'jquery' => null,
				'jquery-ui-core' => null,
				/* 'jquery-touchpunch' => E20R_PLUGINS_URL . '/js/libraries/jquery.ui.touch-punch.min.js',*/
				'dependencies' => array(),
			);
			
			switch ( $event ) {
				
				case 'article_summary':
					
					$load_jq_plot = false;
					E20R_Tracker::dbg("Tracker::load_frontend_scripts() - Loading CSS for the article summary page.");
					
					$css = array_replace( $css, array(
							'e20r-article-summary' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/css/e20r-article-summary.css' : '/css/e20r-article-summary.min.css' ),
						)
					);
					
					$prereqs = array_replace( $prereqs, array(
						'jquery' => null,
						'jquery-ui-core' => null,
						/* 'jquery-touchpunch' => E20R_PLUGINS_URL . '/js/libraries/jquery.ui.touch-punch.min.js', */
						'dependencies' => array(
							'jquery' => false,
							'jquery-ui-core' => array( 'jquery' ),
							/* 'jquery-touchpunch' => array( 'jquery', 'jquery-ui-core' ), */
						),
					) );
					
					break;
				
				case 'client_overview':
					
					E20R_Tracker::dbg("Tracker::load_frontend_scripts() - Loading for the 'e20r_client_overview' shortcode");
					$load_jq_plot = false;
					
					$prereqs = array_replace( $prereqs, array(
						'jquery' => null,
						'jquery-ui-core' => null,
						/* 'jquery-touchpunch' => E20R_PLUGINS_URL . '/js/libraries/jquery.ui.touch-punch.min.js',*/
						'dependencies' => array(
							'jquery' => false,
							'jquery-ui-core' => array( 'jquery' ),
							/* 'jquery-touchpunch' => array( 'jquery', 'jquery-ui-core' ), */
						),
					) );
					
					break;
				
				case 'profile':
					
					E20R_Tracker::dbg("Tracker::load_frontend_scripts() - Loading for the 'e20r_profile' shortcode");
					$load_jq_plot = true;
					
					$css = array_replace( $css, array(
						"jquery-ui-tabs" => "//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css",
						"codetabs" => E20R_PLUGINS_URL . "/css/codetabs/codetabs.css",
						"codetabs-animate" => E20R_PLUGINS_URL . "/css/codetabs/code.animate.css",
					) );
					
					$prereqs = array_replace( $prereqs, array(
						'jquery' => null,
						'jquery-ui-core' => null,
						"jquery-ui-tabs" => "//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css",
						/* 'jquery-touchpunch' => E20R_PLUGINS_URL . '/js/libraries/jquery.ui.touch-punch.min.js',*/
						'dependencies' => array(
							'jquery' => false,
							'jquery-ui-core' => array( 'jquery' ),
							'jquery-ui-tabs' => array( 'jquery', 'jquery-ui-core' ),
							/* 'jquery-touchpunch' => array( 'jquery', 'jquery-ui-core' ), */
						),
					) );
					
					$scripts = array_replace( $scripts, array(
						'jquery.codetabs' => E20R_PLUGINS_URL . '/js/libraries/codetabs/codetabs.min.js',
						'dependencies' => array(
							'jquery.codetabs' => array( 'jquery' ),
						),
					) );
					
					break;
				
				case 'assignments':
					
					E20R_Tracker::dbg("Tracker::load_frontend_scripts() - Loading the assignments javascripts");
					E20R_Tracker::dbg("Tracker::load_frontend_scripts() - Path to thickbox: " . home_url( '/' . WPINC . "/js/thickbox/thickbox.css" ));
					
					$css = array_replace( $css, array(
						"thickbox" => null,
						"e20r-assignments" => E20R_PLUGINS_URL . ( true === WP_DEBUG ? "/css/e20r-assignments.css" : "/css/e20r-assignments.min.css" ),
					) );
					
					$prereqs = array_replace( $prereqs, array(
						'heartbeat' => null,
						'jquery' => null,
						'jquery-ui-core' => null,
						'thickbox' => null,
						'jquery.autoresize' => E20R_PLUGINS_URL . '/js/libraries/jquery.autogrowtextarea.min.js',
						/* 'jquery-touchpunch' => E20R_PLUGINS_URL . '/js/libraries/jquery.ui.touch-punch.min.js', */
						'dependencies' => array(
							'heartbeat' => false,
							'jquery' => false,
							'jquery-ui-core' => array('jquery'),
							'jquery.autoresize' => array('jquery'),
							/* 'jquery-touchpunch' => array('jquery', 'jquery-ui-core'), */
							'thickbox' => array('jquery'),
						),
					) );
					
					$scripts = array_replace( $scripts, array(
						Assignment_Model::post_type => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/e20r-assignments.js' : '/js/e20r-assignments.min.js' ),
						'dependencies' => array(
							Assignment_Model::post_type => array('jquery', 'thickbox', 'jquery.autoresize' ),
						),
					) );
					
					$script = Assignment_Model::post_type;
					$id = Assignment_Model::post_type;
					
					break;
				
				case 'progress_overview':
					
					E20R_Tracker::dbg("Tracker::load_frontend_scripts() - Loading for the 'progress_overview' shortcode");
					
					$load_jq_plot = true;
					
					$css = array_replace( $css, array(
						'thickbox' => null,
						"jquery-ui-tabs" => "//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css",
						"codetabs" => E20R_PLUGINS_URL . "/css/codetabs/codetabs.css",
						"codetabs-animate" => E20R_PLUGINS_URL . "/css/codetabs/code.animate.css",
					) );
					
					$prereqs = array_replace( $prereqs, array(
						'jquery' => null,
						'jquery-ui-core' => null,
						'thickbox' => null,
						'jquery-ui-tabs' => "//code.jquery.com/ui/1.11.2/jquery-ui.min.js",
						/* 'jquery-touchpunch' => E20R_PLUGINS_URL . '/js/libraries/jquery.ui.touch-punch.min.js', */
						'jquery.timeago' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/libraries/jquery.timeago.js' : '/js/libraries/jquery.timeago.min.js' ),
						'jquery.codetabs' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/libraries/codetabs/codetabs.js' : '/js/libraries/codetabs/codetabs.min.js' ),
						'e20r_tracker' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/e20r-tracker.js' : '/js/e20r-tracker.min.js' ),
						'dependencies' => array(
							'jquery' => false,
							'jquery-ui-core' => array( 'jquery' ),
							'thickbox' => array('jquery'),
							/* 'jquery-touchpunch' => array( 'jquery', 'jquery-ui-core' ), */
							'jquery-ui-tabs' => array( 'jquery', 'jquery-ui-core' ),
							'jquery.easing' => array( 'jquery' ),
							'jquery.timeago' => array( 'jquery' ),
							'jquery.codetabs' => array( 'jquery' ),
							'e20r_tracker' => array( 'jquery', 'jquery-ui-core', 'jquery.timeago', 'jquery.codetabs', 'jquery-ui-tabs' /*, 'jquery-touchpunch' */ ),
						),
					) );
					
					$scripts = array_replace( $scripts, array(
						'e20r-progress-measurements' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/e20r-progress-measurements.js' : '/js/e20r-progress-measurements.min.js' ),
						'dependencies' => array(
							'e20r-progress-measurements' => array( 'jquery', 'jquery-ui-core', /* 'jquery-touchpunch', */ 'jquery.timeago', 'jquery.codetabs', 'jquery-ui-tabs',  'e20r_tracker' ),
						),
					) );
					
					$script = 'e20r-progress-measurements';
					$id = 'e20r_progress';
					
					break;
				
				case 'exercise':
					
					E20R_Tracker::dbg("Tracker::load_frontend_scripts() - Loading for the 'exercise' shortcode");
					$load_jq_plot = false;
					
					$css = array_replace( $css, array(
							'e20r-exercise' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? "/css/e20r-exercise.css" : "/css/e20r-exercise.min.css" ),
						)
					);
					
					$prereqs = array_replace( $prereqs, array(
						'jquery' => null,
						'jquery-ui-core' => null,
						/* 'jquery-touchpunch' => E20R_PLUGINS_URL . '/js/libraries/jquery.ui.touch-punch.min.js', */
						'fitvids' => '//cdnjs.cloudflare.com/ajax/libs/fitvids/1.1.0/jquery.fitvids.min.js',
						'e20r_tracker' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/e20r-tracker.js' : '/js/e20r-tracker.min.js' ),
						'dependencies' => array(
							'jquery' => false,
							'jquery-ui-core' => array( 'jquery' ),
							/* 'jquery-touchpunch' => array( 'jquery', 'jquery-ui-core' ), */
							'fitvids' => array( 'jquery' ),
							'e20r_tracker' => array( 'jquery', 'jquery-ui-core' /*, 'jquery-touchpunch' */, 'fitvids' ),
						),
					) );
					
					$scripts = array_replace( $scripts, array(
						'e20r_exercise' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/e20r-exercise.js' : '/js/e20r-exercise.min.js'),
						'dependencies' => array(
							'e20r_exercise' => array( 'jquery', 'jquery-ui-core' /*, 'jquery-touchpunch' */, 'fitvids', 'e20r_tracker' ),
						),
					) );
					
					$script = 'e20r_exercise';
					$id = 'e20r_exercise';
					
					break;
				
				case 'activity':
					
					E20R_Tracker::dbg("Tracker::load_frontend_scripts() - Loading for the 'activity' shortcode");
					$load_jq_plot = true;
					
					$css = array_replace( $css, array(
						"e20r-assignments" => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/css/e20r-assignments.css' : '/css/e20r-assignments.min.css' ),
					) );
					
					$prereqs = array_replace( $prereqs, array(
						'jquery' => null,
						'jquery-ui-core' => null,
						/* 'jquery-touchpunch' => E20R_PLUGINS_URL . '/js/libraries/jquery.ui.touch-punch.min.js', */
						'fitvids' => '//cdnjs.cloudflare.com/ajax/libs/fitvids/1.1.0/jquery.fitvids.min.js',
						'e20r_tracker' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/e20r-tracker.js' : '/js/e20r-tracker.min.js' ),
						'e20r_exercise' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/e20r-exercise.js' : '/js/e20r-exercise.min.js' ),
						'dependencies' => array(
							'jquery' => false,
							'jquery-ui-core' => array( 'jquery' ),
							/* 'jquery-touchpunch' => array( 'jquery', 'jquery-ui-core' ), */
							'fitvids' => array( 'jquery' ),
							'e20r_tracker' => array( 'jquery', 'fitvids' ),
							'e20r_exercise' => array( 'jquery', 'jquery-ui-core', /* 'jquery-touchpunch' , */ 'fitvids', 'e20r_tracker' ),
						),
					) );
					
					$scripts = array_replace( $scripts, array(
						Workout_Model::post_type => E20R_PLUGINS_URL . '/js/e20r-workout.min.js',
						'dependencies' => array(
							Workout_Model::post_type => array( 'jquery', 'fitvids', 'e20r_tracker', 'e20r_exercise' ),
						),
					) );
					
					$script = Workout_Model::post_type;
					$id = Workout_Model::post_type;
					
					break;
				
				case 'daily_progress':
					
					E20R_Tracker::dbg("Tracker::load_frontend_scripts() - Loading for the 'daily_progress' shortcode");
					$load_jq_plot = false;
					
					$css = array_replace( $css, array(
						'select2' => "https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css",
						"codetabs" => E20R_PLUGINS_URL . "/css/codetabs/codetabs.css",
						"codetabs-animate" => E20R_PLUGINS_URL . "/css/codetabs/code.animate.css",
						'e20r_action'  => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/css/e20r-action.css' : '/css/e20r-action.min.css' ),
					) );
					
					$css_dependencies = array(
						'e20r-tracker' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/css/e20r-tracker.css' : '/css/e20r-tracker.min.css'),
					);
					
					// 'jquery.ui.tabs' => "//code.jquery.com/ui/1.11.2/jquery-ui.min.js",
					//  'jquery-effects-core' => null,
					$prereqs = array_replace( $prereqs, array(
						'jquery' => null,
						'jquery-ui-core' => null,
						'select2' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.min.js',
						'base64' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/libraries/Base64.js' : '/js/libraries/Base64.min.js' ),
						/* 'jquery-touchpunch' => E20R_PLUGINS_URL . '/js/libraries/jquery.ui.touch-punch.min.js', */
						'jquery.autoresize' => E20R_PLUGINS_URL . '/js/libraries/jquery.autogrowtextarea.min.js',
						'jquery.timeago' => E20R_PLUGINS_URL . '/js/libraries/jquery.timeago.min.js',
						'jquery.redirect' => E20R_PLUGINS_URL . '/js/libraries/jquery.redirect.min.js',
						'e20r_tracker' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/e20r-tracker.js' : '/js/e20r-tracker.min.js' ),
						'dependencies' => array(
							'jquery' => false,
							'jquery-ui-core' => array( 'jquery' ),
							'base64' => false,
							'select2' => array( 'jquery' ),
							/* 'jquery-touchpunch' => array( 'jquery', 'jquery-ui-core' ), */
							'jquery.autoresize' => array( 'jquery' ),
							'jquery.timeago' => array( 'jquery' ),
							'jquery.redirect' => array( 'jquery' ),
							'e20r_tracker' => array( 'jquery' ),
						),
					) );
					
					$scripts = array_replace( $scripts, array(
						'e20r_action' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/e20r-action.js' : '/js/e20r-action.min.js'),
						'dependencies' => array(
							'e20r_action' => array( 'jquery', 'base64', 'select2', 'jquery-ui-core', /* 'jquery-touchpunch' , */ 'jquery.timeago', 'jquery.autoresize', 'jquery.redirect', 'e20r_tracker'),
						),
					) );
					
					$script = 'e20r_action';
					$id = 'e20r_action';
					
					break;
				
				case 'default':
					$load_jq_plot = false;
					E20R_Tracker::dbg("Tracker::load_frontend_scripts() - Loading CSS for the standard formatting & gravity forms pages.");
					break;
				
			}

//            E20R_Tracker::dbg("Tracker::load_frontend_scripts() - Scripts to print, prerequisites, scripts and CSS:");
//            E20R_Tracker::dbg($prereqs);
			
			$prereq = array( 'jquery', 'jquery-ui-core'/* 'jquery-touchpunch' , */ );
			
			foreach( $prereqs as $tag => $url ) {
				
				if ( 'dependencies' != $tag ) {
					
					if ( !empty( $url ) ) {
						
						E20R_Tracker::dbg("Tracker::load_frontend_scripts() - Adding {$tag} as prerequisite for {$event}");
						$this->register_script( $tag, $url, $prereqs['dependencies'][$tag] );
					}
					
					if ( !in_array( $tag, $prereq ) ) {
						
						E20R_Tracker::dbg("Tracker::load_frontend_scripts() - Adding {$tag} to list of prerequisites to print/enqueue");
						$prereq[] = $tag;
					}
				}
			}
			
			// $prereq = array_keys( $prereq );
			E20R_Tracker::dbg("Tracker::load_frontend_scripts() - For the prerequisites -- wp_print_scripts( " . print_r( $prereq, true) . " )");
			wp_enqueue_script( $prereq );
			
			$list = array();
			
			foreach( $scripts as $tag => $url ) {
				
				if ( 'dependencies' != $tag ) {
					
					if ( !empty( $url ) ) {
						
						E20R_Tracker::dbg("Tracker::load_frontend_scripts() - Adding {$tag} as script for {$event}");
						$this->register_script( $tag, $url, $scripts['dependencies'][$tag] );
					}
					
					if ( !in_array( $tag, $list ) ) {
						
						E20R_Tracker::dbg("Tracker::load_frontend_scripts() - Adding {$tag} to list of scripts to print/enqueue");
						$list[] = $tag;
					}
				}
			}
			
			if ( ( !empty( $script) ) && ( !empty($id) ) ) {
				
				E20R_Tracker::dbg("Tracker::load_frontendscripts() - localizing tag ({$script}) with name {$id}");
				$Client = Client::getInstance();
				
				wp_localize_script( $script, $id,
					array(
						'timeout' => 30000,
						'longpoll_timeout' => apply_filters('e20r-tracker-longpoll-timeout', 300000),
						'coach_message_nonce' => wp_create_nonce('e20r-coach-message'),
						'ticks_to_skip' => apply_filters('e20r-tracker-heartbeat-skip-count', 5),
						'ajaxurl' => admin_url('admin-ajax.php'),
						'interview_complete' => $Client->completeInterview( $current_user->ID ),
						'clientId' => $current_user->ID,
						'is_profile_page' => has_shortcode( $post->post_content, 'e20r_profile' ),
						'activity_url' => get_permalink( $currentProgram->activity_page_id ),
						'login_url' => wp_login_url( get_permalink( $currentProgram->dashboard_page_id ) ),
					)
				);
			}
			
			E20R_Tracker::dbg("Tracker::load_frontend_scripts() - For the script(s) -- wp_print_scripts( " . print_r( $list, true) . " )");
			wp_enqueue_script( $list );
			
			if ( !empty( $css_dependencies ) ) {
				$css_deps = array_keys( $css_dependencies );
			} else {
				$css_deps = false;
			}
			
			foreach( $css as $tag => $url ) {
				
				$css_list[] = $tag;
				
				if ( !empty( $url ) ) {
					
					E20R_Tracker::dbg("Tracker::load_frontend_scripts() - Adding {$tag} CSS");
					wp_enqueue_style( $tag, $url, false, E20R_VERSION );
				}
				else {
					
					wp_enqueue_style( $tag );
				}
			}
			
			/*
						$depKey = array_search( 'dependencies', $list );
			
						if ( $depKey ) {
			
							E20R_Tracker::dbg("Tracker::load_frontend_scripts() - Removing the dummy entry 'dependencies' from the list of scripts to enqueue/print");
							unset( $list[$depKey] );
						}
			*/
		}
		
		$e20r_plot_jscript = $load_jq_plot;
		
		$this->register_plotSW();
		E20R_Tracker::dbg("Tracker::load_frontend_scripts() - Loading CSS for front-end");
		
		$this->enqueue_plotSW();
		$e20r_plot_jscript = false;
		
		// Extract the javascripts scripts to load/print for the short code.
		// $list = array_keys( $list );
		// E20R_Tracker::dbg("Tracker::load_frontend_scripts() - wp_print_scripts( " . print_r( $list, true) . " )");
		// wp_print_scripts( $list );
	}
	
	/**
	 * Register (load) the specified script and its dependencies
	 *
	 * @param $script
	 * @param $location
	 * @param $deps
	 */
	private function register_script( $script, $location, $deps ) {
		
		E20R_Tracker::dbg("Tracker_Scripts::register_script() - script: {$script}, location: {$location}, dependencies ");
		// E20R_Tracker::dbg($deps);
		
		wp_register_script( $script, $location, $deps, E20R_VERSION, true );
	}
	
	public function has_gravityforms_shortcode() {
		
		global $post;
		
		if ( ! isset( $post->ID ) ) {
			
			E20R_Tracker::dbg("Tracker::has_gravityforms_shortcode() - No post ID present?");
			return;
		}
		
		if ( has_shortcode( $post->post_content, 'gravityform' ) ) {
			
			$this->load_frontend_scripts('defaults');
		}
	}
	
	public function has_measurementprogress_shortcode() {
		
		global $post;
		$Client = Client::getInstance();
		$Program = Program::getInstance();
		
		global $currentClient;
		global $e20r_plot_jscript;
		
		global $current_user;
		
		
		if ( ! isset( $post->ID ) ) {
			
			E20R_Tracker::dbg("Tracker::has_measurementprogress_shortcode() - No post ID present?");
			return;
		}
		
		if ( has_shortcode( $post->post_content, 'progress_overview' ) ) {
			
			if ( ! is_user_logged_in() ) {
				
				auth_redirect();
			}
			
			$Program->getProgramIdForUser( $current_user->ID );
			// $Article->init( $post->ID );
			
			if ( !isset( $currentClient->loadedDefaults ) || ( $currentClient->loadedDefaults == true ) ) {
				
				E20R_Tracker::dbg( "Tracker::has_measurementprogress_shortcode() - Have to init Client class & grab data..." );
				$Client->init();
			}
			
			E20R_Tracker::dbg("Tracker::has_measurementprogress_shortcode() - Loading scripts and styles for assignments and progress_overview");
			$this->load_frontend_scripts( array(
					'assignments',
					'activity',
					'progress_overview',
				)
			);
		}
	}
	
	public function has_exercise_shortcode() {
		
		global $post;
		
		if ( ! isset( $post->ID ) ) {
			return;
		}
		
		if ( has_shortcode( $post->post_content, 'e20r_exercise' ) ) {
			
			if ( ! is_user_logged_in() ) {
				
				auth_redirect();
			}
			
			E20R_Tracker::dbg("Tracker::has_exercise_shortcode() -- Loading & adapting user javascripts for exercise form(s). ");
			$this->load_frontend_scripts( 'exercise' );
		}
		
	}
	
	public function has_summary_shortcode() {
		
		global $post;
		
		if ( !isset( $post->ID ) ) {
			return;
		}
		
		if ( has_shortcode( $post->post_content, 'e20r_article_summary' ) ) {
			
			if ( !is_user_logged_in() ) {
				
				auth_redirect();
			}
			
			E20R_Tracker::dbg("Tracker::has_summary_shortcode() - Load CSS for weekly summary post");
			$this->load_frontend_scripts( 'article_summary' );
		}
	}
	
	public function has_activity_shortcode() {
		
		global $post;
		global $pagenow;
		
		$Program = Program::getInstance();
		
		global $current_user;
		
		if ( ! isset( $post->ID ) ) {
			return;
		}
		
		if ( ( has_shortcode( $post->post_content, 'e20r_activity' ) || ( has_shortcode( $post->post_content, 'e20r_activity_archive' ) ) ) ) {
			
			if ( ! is_user_logged_in() ) {
				
				auth_redirect();
			}
			
			$Program->getProgramIdForUser( $current_user->ID );
			
			E20R_Tracker::dbg("Tracker::has_activity_shortcode() -- Loading & adapting user javascripts for activity/exercise form(s). ");
			$this->load_frontend_scripts( 'activity' );
		}
		
	}
	
	public function has_dailyProgress_shortcode() {
		
		global $post;
		global $pagenow;
		
		global $currentProgram;
		global $current_user;
		
		$Program = Program::getInstance();
		
		if ( ! isset( $post->ID ) ) {
			return;
		}
		
		
		if (  Article_Model::post_type === $post->post_type || has_shortcode( $post->post_content, 'daily_progress' ) ) {
			
			if ( !is_user_logged_in() ) {
				
				auth_redirect();
			}
			
			$Program->getProgramIdForUser( $current_user->ID );
			
			E20R_Tracker::dbg("Tracker::has_dailyProgress_shortcode() -- Loading & adapting activity/assignment CSS & Javascripts. ");
			
			$this->load_frontend_scripts( array( 'daily_progress', 'assignments' ) );
		}
	}
	
	public function has_clientlist_shortcode() {
		
		global $post;
		
		if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'e20r_client_overview' ) ) {
			
			if ( ! is_user_logged_in() ) {
				
				auth_redirect();
			}
			
			E20R_Tracker::dbg("Tracker::has_clientlist_shortcode() -- Loading & adapting activity/assignment CSS & Javascripts. ");
			
			$this->load_frontend_scripts('client_overview');
		}
	}
	
	public function has_profile_shortcode() {
		
		global $post;
		
		if ( ! isset( $post->ID )  ) {
			return;
		}
		
		if ( has_shortcode( $post->post_content, 'e20r_profile' ) ) {
			
			if ( !is_user_logged_in()) {
				auth_redirect();
			}
			
			E20R_Tracker::dbg("Tracker::has_profile_shortcode() -- Loading & adapting user javascripts for exercise form(s). ");
			$this->load_frontend_scripts( array( 'assignments', 'progress_overview', 'daily_progress', 'profile' ) );
		}
		
	}
	
	/**
	 * Load Javascript for the Weekly Progress page/shortcode
	 */
	public function has_weeklyProgress_shortcode() {
		
		global $post;
		
		if ( !isset( $post->ID ) ) {
			return;
		}
		
		if ( has_shortcode( $post->post_content, 'weekly_progress' ) ) {
			
			$e20rMeasurements = Measurements::getInstance();
			$Client = Client::getInstance();
			$Article = Article::getInstance();
			$Program = Program::getInstance();
			$Tracker = Tracker::getInstance();
			
			global $current_user;
			global $currentArticle;
			global $currentProgram;
			global $e20rMeasurementDate;
			
			if ( ! is_user_logged_in() ) {
				
				auth_redirect();
			}
			
			E20R_Tracker::dbg("Tracker::has_weeklyProgress_shortcode() - Found the weekly progress shortcode on page: {$post->ID}: ");
			
			$this->register_plotSW();
			
			// Get the requested Measurement date & article ID (passed from the "Need your measuresments today" form.)
			$measurementDate = isset( $_POST['e20r-progress-form-date'] ) ? sanitize_text_field( $_POST['e20r-progress-form-date'] ) : null;
			$articleId = isset( $_POST['e20r-progress-form-article']) ? intval( $_POST['e20r-progress-form-article']) : null;
			
			$e20rMeasurementDate = $measurementDate;
			
			$userId = $current_user->ID;
			$Program->getProgramIdForUser( $userId );
			$programId = $currentProgram->id;
			
			// Get current article ID if it's not set as part of the $_POST variable.
			if ( empty( $articleId ) ) {
				$delay = $Tracker->getDelay();
				$program  = $Program->getProgramIdForUser( $current_user->ID );
				$currentArticle = $Article->findArticles( 'release_day', $delay, $program )[0];
				$articleId = $currentArticle->id;
				$programId = $program;
				
				E20R_Tracker::dbg("Measurements::has_weeklyProgress_shortcode() - Article ID is now: {$articleId} for program {$programId}");
			}
			
			// $articleId = $Article->init( $articleId );
			
			$articleURL = $Article->getPostUrl( $articleId );
			
			if ( ! $Tracker->isActiveUser( $userId ) ) {
				E20R_Tracker::dbg("Tracker::has_weeklyProgress_shortcode() - User isn't a valid user. Not loading any data.");
				return;
			}
			
			if ( ! $Client->client_loaded ) {
				
				E20R_Tracker::dbg( "Tracker::has_weeklyProgress_shortcode() - Have to init Client class & grab data..." );
				$Client->setClient( $userId );
				$Client->init();
			}
			
			E20R_Tracker::dbg("Tracker::has_weeklyProgress_shortcode() - Loading measurements for: " . ( !isset( $measurementDate ) ? 'No date given' : $measurementDate ) );
			$e20rMeasurements->init( $measurementDate, $userId );
			
			E20R_Tracker::dbg("Tracker::has_weeklyProgress_shortcode() - Register scripts");
			
			$this->enqueue_plotSW();
			wp_register_script( 'e20r-jquery-json', E20R_PLUGINS_URL . '/js/libraries/jquery.json.min.js', array( 'jquery' ), '0.1', false );
			wp_register_script( 'jquery-colorbox', "//cdnjs.cloudflare.com/ajax/libs/jquery.colorbox/1.4.33/jquery.colorbox-min.js", array('jquery'), '1.4.33', false);
			wp_register_script( 'jquery.timeago', E20R_PLUGINS_URL . '/js/libraries/jquery.timeago.min.js', array( 'jquery' ), E20R_VERSION, false );
			
			if (! WP_DEBUG) {
				wp_register_script( 'e20r-tracker-js', E20R_PLUGINS_URL . '/js/e20r-tracker.min.js', array( 'jquery.timeago' ), E20R_VERSION, false );
				wp_register_script( 'e20r-progress-js', E20R_PLUGINS_URL . '/js/e20r-progress.min.js', array( 'e20r-tracker-js' ) , E20R_VERSION, false );
			}
			else {
				wp_register_script( 'e20r-tracker-js', E20R_PLUGINS_URL . '/js/e20r-tracker.js', array( 'jquery.timeago' ), E20R_VERSION, false );
				wp_register_script( 'e20r-progress-js', E20R_PLUGINS_URL . '/js/e20r-progress.js', array( 'e20r-tracker-js' ) , E20R_VERSION, false );
			}
			
			
			E20R_Tracker::dbg("Tracker::has_weeklyProgress_shortcode() - Find last weeks measurements");
			
			$lw_measurements = $e20rMeasurements->getMeasurement( 'last_week', true );
			E20R_Tracker::dbg("Tracker::has_weeklyProgress_shortcode() - Measurements from last week loaded:");
			
			$bDay = $Client->getBirthdate( $userId );
			E20R_Tracker::dbg("Tracker::has_weeklyProgress_shortcode() - Birthdate for {$userId} is: {$bDay}");
			
			E20R_Tracker::dbg("Tracker::has_weeklyProgress_shortcode() - Check if user has completed Interview?");
			if ( ! $Client->completeInterview( $userId ) ) {
				
				E20R_Tracker::dbg("Tracker::has_weeklyProgress_shortcode() - No USER DATA found in the database. Redirect to User interview info!");
				
				
				if ( empty( $currentProgram->incomplete_intake_form_page ) ) {
					$url = $Program->get_welcomeSurveyLink( $userId );
				}
				else {
					$url = get_permalink( $currentProgram->incomplete_intake_form_page );
				}
				
				E20R_Tracker::dbg("Tracker::has_weeklyProgress_shortcode() - URL to redirect to: {$url}");
				if ( !empty( $url ) ) {
					
					wp_redirect( $url, 302 );
					exit;
					// wp_die("Tried to redirect to");
				}
				else {
					E20R_Tracker::dbg("Tracker::has_weeklyProgress_shortcode() - No URL defined! Can't redirect.");
				}
			}
			
			E20R_Tracker::dbg("Tracker::has_weeklyProgress_shortcode() - Localizing progress script for use on measurement page");
			E20R_Tracker::dbg("Tracker::has_weeklyProgress_shortcode() - Loading survey data for user - {$articleURL} - ...");
			
			$dashboardLnk = get_permalink( $Program->getValue( $programId, 'dashboard_page_id') );
			
			/* Load user specific settings */
			wp_localize_script( 'e20r-progress-js', 'e20r_progress',
				array(
					'clientId' => $current_user->ID,
					'ajaxurl'   => admin_url('admin-ajax.php'),
					'settings'     => array(
						'article_id'        => $articleId,
						'lengthunit'        => $Client->getLengthUnit(),
						'weightunit'        => $Client->getWeightUnit(),
						'interview_url'     => $Program->get_welcomeSurveyLink($userId),
						'imagepath'         => E20R_PLUGINS_URL . '/img/',
						'overrideDiff'      => ( isset( $lw_measurements->id ) ? false : true ),
						'measurementSaved'  => ( !empty( $articleURL ) ? $dashboardLnk : E20R_COACHING_URL . 'home/' ),
						'weekly_progress'   => get_permalink( $currentProgram->measurements_page_id ),
					),
					'measurements' => array(
						'last_week' => json_encode( $lw_measurements, JSON_NUMERIC_CHECK ),
					),
					'user_info'    => array(
						'userdata'          => json_encode( $Client->get_data( $userId, true, true ), JSON_NUMERIC_CHECK ),
						'interview_complete' => $Client->completeInterview( $userId ),
//                        'progress_pictures' => '',
//                        'display_birthdate' => ( empty( $bDay ) ? false : true ),
					
					),
				)
			);
			
			E20R_Tracker::dbg("Tracker::has_weeklyProgress_shortcode() - Loading scripts in footer of page");
			wp_enqueue_media();
			
			E20R_Tracker::dbg("Tracker::has_weeklyProgress_shortcode() - Add manually created javascript");
			
			wp_print_scripts( 'jquery-colorbox' );
			wp_print_scripts( 'e20r-jquery-json' );
			wp_print_scripts( 'e20r-tracker-js' );
			wp_print_scripts( 'e20r-progress-js' );
			
			if ( ! wp_style_is( 'e20r-tracker', 'enqueued' )) {
				
				E20R_Tracker::dbg("Tracker::has_weeklyProgress_shortcode() - Need to load CSS for Tracker.");
				wp_deregister_style("e20r-tracker");
				wp_enqueue_style( "e20r-tracker", E20R_PLUGINS_URL . '/css/e20r-tracker.min.css', false, E20R_VERSION );
			}
			
		} // End of shortcode check for weekly progress form
	}
}