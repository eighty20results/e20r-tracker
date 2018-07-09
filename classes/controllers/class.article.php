<?php

namespace E20R\Tracker\Controllers;

/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

use E20R\Tracker\Models\Article_Model;
use E20R\Tracker\Views\Article_View;
use E20R\Utilities\Utilities;

/**
 * Class Article
 *
 * Articles contain list of programs it belongs to along with the PostID and all metadata.
 *
 * @package E20R\Tracker\Controllers
 *
 * @since   1.0
 */
class Article extends Settings {
	
	private static $instance = null;
	protected $articleId;
	protected $model;
	protected $view;
	
	public function __construct() {
		
		Utilities::get_instance()->log( "Initializing Article class" );
		
		$this->model = new Article_Model();
		$this->view  = new Article_View();
		
		parent::__construct( 'article', Article_Model::post_type, $this->model, $this->view );
	}
	
	/**
	 * Return the active instance of this class
	 *
	 * @return Article
	 */
	static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Generate and return a default (empty) article definition
	 *
	 * @return mixed
	 */
	public function emptyArticle() {
		
		return $this->model->defaultSettings();
	}
	
	/**
	 * Pass-through for the findClosestArticle() model method
	 *
	 * @param mixed  $key
	 * @param mixed  $value
	 * @param int    $programId
	 * @param string $comp
	 * @param string $sort_order
	 * @param int    $limit
	 * @param string $type
	 *
	 * @return array
	 */
	public function findArticlesNear( $key, $value, $programId = - 1, $comp = '<=', $sort_order = 'DESC', $limit = 1, $type = 'numeric' ) {
		
		// findClosestArticle( $key, $value, $programId = -1, $comp = '<=', $limit = 1, $type = 'numeric', $sort_order = 'DESC' )
		return $this->model->findClosestArticle( $key, $value, $programId, $comp, $limit, $sort_order, $type );
	}
	
	/**
	 * Load the checkin records for the specified Article configuration
	 *
	 * @param \stdClass $aConfig
	 *
	 * @return array|bool
	 */
	public function getCheckins( $aConfig ) {
		
		Utilities::get_instance()->log( "Get array of checkin IDs for {$aConfig->articleId}" );
		
		$checkin_ids = $this->model->getSetting( $aConfig->articleId, 'action_ids' );
		// $activity = $this->model->getSetting( $aConfig->articleId, 'activity_id' );
		
		if ( ! is_array( $checkin_ids ) ) {
			$checkin_ids = array( $checkin_ids );
		}
		
		if ( empty( $checkin_ids ) /* && is_null( $activity ) */ ) {
			Utilities::get_instance()->log( "No check-in IDs found for this article ({$aConfig->articleId})" );
			
			return false;
		}
		
		return $checkin_ids;
	}
	
	/**
	 * Get the day of release for an Article ID
	 *
	 * @param int $articleId
	 *
	 * @return int|bool
	 */
	public function releaseDay( $articleId ) {
		
		return $this->model->getSetting( $articleId, 'release_day' );
	}
	
	/**
	 * Check if an article is a survey article
	 *
	 * @param $id
	 *
	 * @return bool
	 */
	public function isSurvey( $id ) {
		
		Utilities::get_instance()->log( "checking survey setting for {$id}" );
		
		$setting = $this->model->getSetting( $id, 'is_survey' );
		
		if ( empty( $setting ) ) {
			
			Utilities::get_instance()->log( "is_survey setting is empty/null" );
			
			return false;
		} else {
			return ( 0 != $setting ? true : false );
		}
	}
	
	/**
	 * Get the 'prefix' setting for the specified articleId
	 *
	 * @param $articleId - The Article ID to look up settings for
	 *
	 * @return string - The actual prefix (or NULL) for the articleId
	 */
	public function getArticlePrefix( $articleId ) {
		
		global $currentArticle;
		$this->model->loadSettings( $articleId );
		
		if ( ! empty( $currentArticle ) ) {
			
			return $currentArticle->prefix;
		}
		
		return null;
	}
	
	/**
	 * Get the URL to the linked post for the specified Article ID
	 *
	 * @param int $articleId
	 *
	 * @return bool|false|string
	 */
	public function getPostUrl( $articleId ) {
		
		if ( ! $articleId ) {
			
			Utilities::get_instance()->log( "No article ID provided?!?" );
			
			return false;
		}
		
		$postId = $this->model->getSetting( $articleId, 'post_id' );
		$url    = get_permalink( $postId );
		
		Utilities::get_instance()->log( "Got URL for post ({$postId}): {$url}" );
		
		return ( empty( $url ) ? false : $url );
	}
	
	/**
	 * Get the stub/short-name for the article/checkin type
	 *
	 * @param int    $articleId
	 * @param string $checkin_type
	 *
	 * @return bool|string
	 */
	public function get_checkin_shortname( $articleId, $checkin_type ) {
		
		$Action = Action::getInstance();
		global $currentArticle;
		$this->model->loadSettings( $articleId );
		
		foreach ( $currentArticle->action_ids as $cId ) {
			
			$activity = $Action->init( $cId );
			
			if ( $checkin_type == $activity->checkin_type ) {
				return $activity->short_code;
			}
		}
		
		return false;
	}
	
	/**
	 * Generate an archive of Articles
	 *
	 * @param null|array $attr
	 */
	public function article_archive_shortcode( $attr = null ) {
		
		$Client = Client::getInstance();
		global $currentClient;
		global $current_user;
		
		$Client->setClient( $current_user->ID );
		$articles = $this->get_article_archive( $currentClient->user_id );
		$cards    = array();
		
		foreach ( $articles as $article ) {
			
			$cards[] = $this->get_card_info( $article, $currentClient->user_id );
			Utilities::get_instance()->log( "Received " . count( $cards ) . " cards for day # {$article->release_day}" );
		}
		
		Utilities::get_instance()->log( print_r( $cards, true ) );
	}
	
	/**
	 * Load and return the article archive available for the specified user ID (or the current user)
	 *
	 * @param int|null $user_id
	 *
	 * @return array|bool
	 */
	public function get_article_archive( $user_id = null ) {
		
		$Program = Program::getInstance();
		global $currentProgram;
		
		if ( is_null( $user_id ) ) {
			
			global $currentClient;
			global $current_user;
			
			if ( ! isset( $currentClient->user_id ) ) {
				
				$Client = Client::getInstance();
				$Client->setClient( $current_user->ID );
				$Client->init();
			}
			
			$user_id = $currentClient->user_id;
		}
		
		Utilities::get_instance()->log( "Loading article archive for user {$user_id}" );
		
		$Program->getProgramIdForUser( $user_id );
		
		// The archive goes up to (but doesn't include) the current day.
		$up_to = ( $currentProgram->active_delay - 1 );
		
		$archive = $this->model->load_for_archive( $up_to );
		
		Utilities::get_instance()->log( "Returned " . count( $archive ) . " articles for archive for user {$user_id} with a last-day value of {$currentProgram->active_delay}" );
		
		return $archive;
		
		// $Tracker->get_closest_release_day( $array, $day );
	}
	
	/**
	 * Get the data for the dashboard card for the specified user/article/type combination
	 *
	 * @param \stdClass $article
	 * @param null|int  $userId
	 * @param string    $type
	 *
	 * @return array
	 */
	public function get_card_info( $article, $userId = null, $type = 'action' ) {
		
		global $post;
		
		$Workout = Workout::getInstance();
		
		global $current_user;
		global $currentProgram;
		
		$postId        = null;
		$activityField = null;
		
		$cards      = array();
		$actions    = array();
		$activities = array();
		$lessons    = array();
		
		$oldPost = $post;
		
		if ( is_null( $userId ) ) {
			// Assuming current_user->ID..
			$userId = $current_user->ID;
		}
		
		$lesson = new \stdClass();
		
		// Generate the post (lesson/reminder/text) card
		if ( isset( $article->post_id ) && ( ! empty( $article->post_id ) ) ) {
			
			if ( ! is_array( $article->post_id ) ) {
				$article->post_id = array( $article->post_id );
			}
			
			foreach ( $article->post_id as $post_id ) {
				
				$p = get_post( $post_id );
				$a = get_post( $article->id );
				wp_reset_postdata();
				
				$lesson->summary  = $this->get_summary( $p, $a );
				$lesson->feedback = $this->get_feedback( $article, CONST_ARTICLE_FEEDBACK_LESSONS, $userId );
				
				$lessons[] = $lesson;
			}
			
			$cards['lesson'] = $lessons;
		}
		
		
		if ( isset( $article->activity_id ) && ( ! empty( $article->activity_id ) ) ) {
			
			if ( ! is_array( $article->activity_id ) ) {
				
				$article->activity_id = array( $article->activity_id );
			}
			
			foreach ( $article->activity_id as $activity_id ) {
				
				
				$activity        = $Workout->load_user_activity( $activity_id, $userId );
				$activity->saved =
				$activities[] = $activity;
			}
			
			$cards['activity'] = $activities;
		}
		
		
		if ( isset( $article->action_ids ) && ( ! empty( $article->action_ids ) ) ) {
			
			$Action = Action::getInstance();
			
			if ( ! is_array( $article->action_ids ) ) {
				$article->action_ids = array( $article->action_ids );
			}
			
			foreach ( $article->action_ids as $action_id ) {
				
				$action = $Action->find( 'id', $action_id, $currentProgram->id );
				Utilities::get_instance()->log( "Found " . count( $action ) . " action(s) for article {$article->id}" );
				
				// $action = new \stdClass();
				// $action->title = "Some title";
				$actions[] = $action;
			}
			
			$cards['actions'] = $actions;
		}
		
		// Reset $post content
		$post = $oldPost;
		
		return $cards;
	}
	
	/**
	 * Return the summary information (Excerpt) for the Post or Article
	 *
	 * @param \WP_Post $post
	 * @param \WP_Post $article
	 *
	 * @return string|null
	 */
	private function get_summary( \WP_Post $post, \WP_Post $article ) {
		
		$excerpt = __( "No information found", "e20r-tracker" );
		
		$article_has_excerpt = ( empty( $article->post_excerpt ) ? false : true );
		// $article_has_content = (empty($article->post_content) ? false : true);
		$article_has_content = false; // Fix: Only use post_content for the reminder summary description in the []
		$post_has_excerpt    = ( empty( $post->post_excerpt ) ? false : true );
		$post_has_content    = ( empty( $post->post_content ) ? false : true );
		
		if ( ( false === $article_has_content ) && ( false === $article_has_excerpt ) ) {
			
			if ( true === $post_has_excerpt ) {
				$excerpt = $post->post_excerpt;
			}
			
			if ( ( false === $post_has_excerpt ) && ( true == $post_has_content ) ) {
				$excerpt = wp_trim_words( $post->post_excerpt, 20, " [...]" );
			}
		}
		
		if ( true === $article_has_excerpt ) {
			$excerpt = $article->post_excerpt;
		}
		
		if ( ( false === $article_has_excerpt ) && ( true === $article_has_content ) ) {
			$excerpt = wp_trim_words( $article->post_content, 20, " [...]" );
		}
		
		return $excerpt;
	}
	
	/**
	 * @param \stdClass $article
	 * @param int       $type
	 * @param int       $userId
	 *
	 * @return null
	 */
	public function get_feedback( $article, $type, $userId ) {
		
		// TODO: Implement get_feedback() for cards. (Implement as a clickable envelope with pop-up for the feedback?)
		
		return null;
	}
	
	/**
	 * Redirect all bots to the actual linked post content for the article
	 */
	public function send_to_post() {
		
		if ( is_user_logged_in() ) {
			return;
		}
		
		$post_type = get_post_type();
		
		if ( Article_Model::post_type === $post_type && true === $this->_bot_detected() ) {
			
			$article_id   = get_the_ID();
			$real_post_id = get_post_meta( $article_id, '_e20r-article-post_id', true );
			
			wp_redirect( get_permalink( $real_post_id ), 301 );
			exit();
		}
	}
	
	/**
	 * Detects whether the current User Agent (most likely) is a BOT/Spider
	 *
	 * @return bool
	 */
	private function _bot_detected() {
		
		return (
			isset( $_SERVER['HTTP_USER_AGENT'] ) && 1 === preg_match( '/bot/crawl/slurp/spider/mediapartners/i', $_SERVER['HTTP_USER_AGENT'] )
		);
	}
	
	/**
	 * Fetch/Configure the cards to use for the article/day of the user's membership
	 *
	 * @param      $article
	 * @param null $user_id
	 */
	public function get_cards_for_day( $article, $user_id = null ) {
		
		global $currentArticle;
		
		if ( ! isset( $article->activity_id ) ) {
			$this->init( $article->article_id );
		}
		
		if ( isset( $article->article_id ) && ( $currentArticle->id != $article->article_id ) ) {
			
			$this->init( $article->article_id );
		}
		
		
	}
	
	/**
	 * Load/Configure the specified Article ID from the database
	 *
	 * @param null|int $article_id
	 *
	 * @return int|bool
	 */
	public function init( $article_id = null ) {
		
		global $currentArticle;
		
		if ( ( isset( $currentArticle->id ) && ( $currentArticle->id != $article_id ) ) || ! isset( $currentArticle->id ) ) {
			
			$currentArticle = parent::init( $article_id );
			Utilities::get_instance()->log( "Loaded settings for article ({$article_id})" );
			
			$this->articleId = ( ! isset( $currentArticle->id ) ? false : $currentArticle->id );
			Utilities::get_instance()->log( " Loaded global currentArticle and set it for article ID {$this->articleId}" );
		} else {
			Utilities::get_instance()->log( "No need to load settings (previously loaded): " );
//		    Utilities::get_instance()->log($currentArticle);
		}
		
		// Utilities::get_instance()->log( print_r( $currentArticle, true) );
		
		if ( ( $this->articleId !== false ) && ( $currentArticle->id != $article_id ) ) {
			
			Utilities::get_instance()->log( "No article defined for this postId: {$currentArticle->id} & {$article_id}" );
			$this->articleId = false;
		}
		
		return $this->articleId;
	}
	
	/**
	 * Return the article excerpt for the specified user and article combination
	 *
	 * @param        $articleId
	 * @param null   $userId
	 * @param string $type
	 * @param bool   $in_card
	 *
	 * @return string
	 */
	public function getExcerpt( $articleId, $userId = null, $type = 'action', $in_card = false ) {
		
		global $post;
		$Tracker = Tracker::getInstance();
		
		global $current_user;
		global $currentProgram;
		
		$postId        = null;
		$activityField = null;
		$prefix        = null;
		$url           = null;
		$oldPost       = $post;
		
		if ( is_null( $userId ) ) {
			// Assuming current_user->ID..
			$userId = $current_user->ID;
		}
		
		$group = $Tracker->getGroupIdForUser( $userId );
		
		switch ( $type ) {
			
			case 'action':
				$postId = $this->model->getSetting( $articleId, 'post_id' );
				$prefix = $this->model->getSetting( $articleId, 'prefix' );
				Utilities::get_instance()->log( "Loaded post ID ($postId) for the action in article {$articleId}" );
				
				if ( - 1 == $postId ) {
					Utilities::get_instance()->log( "No activity excerpt to be found (no activity specified)." );
					
					return null;
				}
				
				break;
			
			case 'activity':
				
				Utilities::get_instance()->log( "Searching for activities for {$articleId}" );
				$actId = $this->getActivity( $articleId, $userId );
				
				Utilities::get_instance()->log( "Found activity: {$actId}: " . print_r( $actId, true ) );
				
				if ( false == $actId ) {
					$postId = - 1;
				} else {
					
					$postId        = $actId;
					$activityField = sprintf( '<input type="hidden" id="e20r-activity-activity_id" value="%d" name="e20r-activity-activity_id">', $postId );
					$prefix        = null; // Using NULL prefix for activities
					
					Utilities::get_instance()->log( "Loaded post ID ($postId) for the activity in article {$articleId}" );
				}
				
				if ( - 1 == $postId ) {
					Utilities::get_instance()->log( "No activity excerpt to be found (no activity specified)." );
					
					return null;
				}
				
				break;
		}
		
		Utilities::get_instance()->log( "Post Id for article {$articleId}: {$postId}" );
		
		if ( empty( $postId ) ) {
			return null;
		}
		
		$art  = get_post( $articleId );
		$post = get_post( $postId );
		
		Utilities::get_instance()->log( "Prefix for {$type}: {$prefix}" );
		
		if ( ! empty( $art->post_excerpt ) && ( 'action' == $type ) ) {
			
			Utilities::get_instance()->log( "Using the article summary." );
			$pExcerpt = $art->post_excerpt;
		} else if ( ! empty( $post->post_excerpt ) ) {
			
			Utilities::get_instance()->log( "Using the post excerpt." );
			$pExcerpt = $post->post_excerpt;
		} else {
			
			Utilities::get_instance()->log( "Using the post summary." );
			$pExcerpt = $post->post_content;
			$pExcerpt = wp_trim_words( $pExcerpt, 20, " [...]" );
		}
		
		$image = ( has_post_thumbnail( $post->ID ) ? get_the_post_thumbnail( $post->ID ) : '<div class="noThumb"></div>' );
		
		$pExcerpt = preg_replace( "/\<br(\s+)\/\>/i", null, $pExcerpt );
		
		ob_start();
		?>
        <h4>
			<?php if ( false === $in_card ): ?><span
                    class="e20r-excerpt-prefix"><?php esc_html_e( $prefix ); ?></span><?php endif; ?><?php echo get_the_title( $post->ID ); ?>
        </h4>
		<?php echo ! is_null( $activityField ) ? $activityField : null; ?>
        <p class="e20r-descr e20r-descr-text"><?php esc_html_e( $pExcerpt ); ?></p> <?php
		
		if ( $type == 'action' ) {
			
			$url = get_permalink( $post->ID );
		} else if ( $type == 'activity' ) {
			
			$url = null;
			
			Utilities::get_instance()->log( "Loading URL for activity..." );
			
			$url = get_permalink( $currentProgram->activity_page_id );
			Utilities::get_instance()->log( "URL is: {$url}" );
			
		} ?>
        <p class="e20r-descr e20r-descr-link">
        <a href="<?php echo esc_url_raw( $url ); ?>" id="e20r-<?php esc_html_e( $type ); ?>-read-lnk"
           title="<?php esc_attr_e( get_the_title( $post->ID ) ); ?>">
			<?php _e( 'Click to read', 'e20tracker' ); ?>
        </a>
        </p><?php
		
		wp_reset_postdata();
		
		$html = ob_get_clean();
		
		$post = $oldPost;
		
		return $html;
	}
	
	/**
	 * Return the activity for the specified user and article combination
	 *
	 * @param $articleId - The ID of the article containing the workout/activity for this lesson.
	 *
	 * @returns int - The post ID for the activity/workout.
	 */
	public function getActivity( $articleId, $userId = null ) {
		
		$Access  = Tracker_Access::getInstance();
		$Workout = Workout::getInstance();
		$Tracker = Tracker::getInstance();
		
		$postId = null;
		
		// $excerpt = __( "We haven't found an activity for this day", "e20r-tracker" );
		// TODO: Verify that supplying the ArticleID is the correct thing to do for getSetting() in Article::getActivity() method
		$aIds = $this->model->getSetting( $articleId, 'activity_id' );
		
		// No activity defined
		if ( empty( $aIds ) || ( ! is_array( $aIds ) && ( false == $aIds ) ) ) {
			
			Utilities::get_instance()->log( "No defined activity for this article ({$articleId})" );
			
			return false;
		}
		
		$delay = $this->model->getSetting( $articleId, 'release_day' );
		
		$mGroupId   = $Tracker->getGroupIdForUser( $userId );
		$activities = $Workout->find( 'id', $aIds, - 1, 'IN' );
		
		$post_date = Time_Calculations::getDateForPost( $delay, $userId );
		
		Utilities::get_instance()->log( "Date for post with delay {$delay} for user {$userId}: {$post_date}" );
		
		$art_day_no = date( 'N', strtotime( $post_date ) );
		Utilities::get_instance()->log( "For article #{$articleId}, delay: {$delay}, on day: {$art_day_no}." );
		
		// Loop through all the defined activities for the $articleId
		foreach ( $activities as $a ) {
			
			Utilities::get_instance()->log( "On day # {$delay} for article {$articleId} processing activity {$a->id}: {$art_day_no}" );
			// Utilities::get_instance()->log($a->days);
			
			if ( in_array( $art_day_no, $a->days ) ) {
				
				// The delay value for the $articleId releases the $articleId on one of the $activity days.
				Utilities::get_instance()->log( "ID for an activity allowed on {$art_day_no}: {$a->id}" );
				// $activity = $Workout->getActivity( $a->id );
				
				$has_access = array();
				
				$access_map        = new \stdClass();
				$access_map->user  = array();
				$access_map->group = array();
				
				Utilities::get_instance()->log( "Testing if {$userId} is in group or user list for activity #{$a->id}" );
				
				$has_access                  = $Access->allowedActivityAccess( $a, $userId, $mGroupId );
				$access_map->user[ $a->id ]  = $has_access['user'];
				$access_map->group[ $a->id ] = $has_access['group'];
				
				$postId = array_search( true, $access_map->user );
				
				if ( $postId ) {
					Utilities::get_instance()->log( "Found an activity ({$postId}) that is assigned to the specific user ({$userId})" );
					
					return $postId;
				}
				
				$postId = array_search( true, $access_map->group );
				
				if ( ! empty( $postId ) ) {
					Utilities::get_instance()->log( "Found an activity ({$postId}) that is assigned to the group ({$mGroupId}) that the user ({$userId}) is in" );
					
					return $postId;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Return the assignments for the specified user and article combination
	 *
	 * @param      $articleId
	 * @param null $userId
	 *
	 * @return array
	 */
	public function getAssignments( $articleId, $userId = null ) {
		
		$Assignment = Assignment::getInstance();
		global $currentArticle;
		
		Utilities::get_instance()->log( "Need to load settings for {$articleId}" );
		// $this->init( $articleId );
		$articleSettings = $this->model->loadSettings( $articleId );
		
		// $articleSettings = $this->model->loadSettings( $this->articleId );
		
		// Utilities::get_instance()->log($articleSettings);
		
		$assignment_ids = array();
		
		Utilities::get_instance()->log( "Loading assignments for article # {$articleId}" );
		
		if ( ! empty( $articleSettings->assignment_ids ) ) {
			
			Utilities::get_instance()->log( "Have predefined assignments for article" );
			
			foreach ( $articleSettings->assignment_ids as $assignmentId ) {
				
				Utilities::get_instance()->log( "Loading assignment {$assignmentId} for article" );
				
				// Load the user specific assignment data (if available. If not, load default data)
				$tmp                                  = $Assignment->load_userAssignment( $articleId, $assignmentId, $userId );
				$assignment_ids[ $tmp[0]->order_num ] = $tmp[0];
			}
		} else {
			Utilities::get_instance()->log( "No defined explicit assignments for this article." );
			$assignment_ids[0] = $Assignment->loadAssignment();
		}
		
		Utilities::get_instance()->log( "Sorting assignments for article # {$articleId} by order number" );
		ksort( $assignment_ids );
		
		// Set the active article
		$currentArticle = $articleSettings;
		
		return $assignment_ids;
	}
	
	/**
	 * Clear the assignment info for the article
	 */
	public function remove_assignment_callback() {
		
		$Assignment = Assignment::getInstance();
		global $currentArticle;
		
		check_ajax_referer( 'e20r-tracker-data', 'e20r-tracker-article-settings-nonce' );
		Utilities::get_instance()->log( "Deleting assignment for article." );
		
		$articleId    = isset( $_POST['e20r-article-id'] ) ? intval( $_POST['e20r-article-id'] ) : null;
		$assignmentId = isset( $_POST['e20r-assignment-id'] ) ? intval( $_POST['e20r-assignment-id'] ) : null;
		
		// $this->articleId = $articleId;
		$this->init( $articleId );
		
		$artSettings = $currentArticle;
		Utilities::get_instance()->log( "Article settings for ({$articleId}): " );
		Utilities::get_instance()->log( print_r( $artSettings, true) );
		
		$assignment = $Assignment->loadAssignment( $assignmentId );
		
		Utilities::get_instance()->log( "Updating Assignment ({$assignmentId}) settings & saving." );
		$assignment->article_id = null;
		
		Utilities::get_instance()->log( "Assignment settings for ({$assignmentId}): " );
		$Assignment->saveSettings( $articleId, $assignment );
		
		Utilities::get_instance()->log( "Updating Article settings for ({$articleId}): " );
		
		if ( in_array( $assignmentId, $artSettings->assignment_ids ) ) {
			
			$artSettings->assignment_ids = array_diff( $artSettings->assignment_ids, array( $assignmentId ) );
		}
		
		$this->model->set( 'assignment_ids', $artSettings->assignment_ids, $articleId );
		
		Utilities::get_instance()->log( "Generating the assignments metabox for the article {$articleId} definition" );
		
		$toBrowser = array(
			'success' => true,
			'data'    => $Assignment->configureArticleMetabox( $articleId ),
		);
		
		if ( ! empty( $toBrowser['data'] ) ) {
			
			Utilities::get_instance()->log( "Transmitting new HTML for metabox" );
			// wp_send_json_success( $html );
			
			echo json_encode( $toBrowser );
			exit();
		}
		
		Utilities::get_instance()->log( "Error generating the metabox html!" );
		wp_send_json_error( __( "No assignments found for this article!", "e20r-tracker" ) );
		exit();
	}
	
	/**
	 * Add/load the Assignment info for the article
	 */
	public function add_assignment_callback() {
		
		global $currentArticle;
		
		check_ajax_referer( 'e20r-tracker-data', 'e20r-tracker-article-settings-nonce' );
		
		$Assignment = Assignment::getInstance();
		$Tracker    = Tracker::getInstance();
		$reloadPage = false;
		
		Utilities::get_instance()->log( "Saving new assignment for article." . print_r( $_POST, true ) );
		
		$articleId     = isset( $_POST['e20r-article-id'] ) ? $Tracker->sanitize( $_POST['e20r-article-id'] ) : null;
		$postId        = isset( $_POST['e20r-assignment-post_id'] ) ? $Tracker->sanitize( $_POST['e20r-assignment-post_id'] ) : null;
		$assignmentId  = isset( $_POST['e20r-assignment-id'] ) ? $Tracker->sanitize( $_POST['e20r-assignment-id'] ) : null;
		$new_order_num = isset( $_POST['e20r-assignment-order_num'] ) ? $Tracker->sanitize( $_POST['e20r-assignment-order_num'] ) : null;
		
		if ( $new_order_num <= 0 ) {
			
			Utilities::get_instance()->log( "Resetting the requested order number to a valid value ( >0 )." );
			$new_order_num = 1;
		}
		
		Utilities::get_instance()->log( "Article: {$articleId}, Assignment: {$assignmentId}, Assignment Order#: {$new_order_num}" );
		
		// $this->articleId = $articleId;
		
		$post = get_post( $articleId );
		setup_postdata( $post );
		
		
		// Just in case we're working on a brand new article
		if ( 'auto-draft' == $post->post_status ) {
			
			if ( empty( $post->post_title ) ) {
				
				$post->post_title = "Please update this title before updating";
			}
			
			$articleId  = wp_insert_post( $post );
			$reloadPage = true;
		}
		
		wp_reset_postdata();
		
		$this->model->loadSettings( $articleId );
		
		Utilities::get_instance()->log( "Article settings for ({$articleId}): " );
		Utilities::get_instance()->log( print_r( $currentArticle, true) );
		
		$assignment             = $Assignment->loadAssignment( $assignmentId );
		$assignment->order_num  = $new_order_num;
		$assignment->article_id = null;
		
		Utilities::get_instance()->log( "Updating assignment settings for ({$assignmentId}), with new order {$new_order_num}" );
		$Assignment->saveSettings( $assignmentId, $assignment );
		
		$ordered = array();
		$orig    = $currentArticle->assignment_ids;
		$new     = array();
		
		// Load assignments so we can sort (if needed)
		foreach ( $currentArticle->assignment_ids as $aId ) {
			
			$a = $Assignment->loadAssignment( $aId );
			
			if ( $a->order_num == 0 ) {
				$a->order_num = 1;
			}
			$ordered[ ( $a->order_num - 1 ) ] = $a;
		}
		
		// Sort by order number.
		ksort( $ordered );
		$orig = $ordered;
		
		Utilities::get_instance()->log( "Sorted previously saved assignments: " . print_r( $ordered, true ) );
		
		// Are we asking to reorder the assignment?
		if ( ( isset( $ordered[ $new_order_num ] ) ) && ( $assignmentId != $ordered[ $new_order_num ]->id ) ) {
			
			Utilities::get_instance()->log( "Re-sorting list of assignments:" );
			reset( $ordered );
			$first = key( $ordered );
			
			for ( $i = $first; $i < $new_order_num; $i ++ ) {
				
				if ( isset( $ordered[ $i ] ) ) {
					Utilities::get_instance()->log( "Sorting assignment {$ordered[$i]->id} to position {$ordered[$i]->order_num}" );
					$ordered[ $i ]->order_num = $new_order_num;
					$new[ $i ]                = $ordered[ $i ];
				}
			}
			
			$new[ $new_order_num ] = $assignment;
			
			end( $orig );
			$last = key( $orig );
			
			for ( $i = $new_order_num; $i <= $last; $i ++ ) {
				
				if ( isset( $orig[ $i ] ) ) {
					
					Utilities::get_instance()->log( "Sorting assignment {$orig[($i)]->id} to position " . ( $orig[ $i ]->order_num + 1 ) );
					$orig[ $i ]->order_num = $orig[ $i ]->order_num + 1;
					$new[ ( $i + 1 ) ]     = $orig[ $i ];
					$Assignment->saveSettings( $new[ ( $i + 1 ) ]->id, $new[ ( $i + 1 ) ] );
				}
			}
			
			$ordered = $new;
		} else {
			
			
			Utilities::get_instance()->log( "Adding {$assignment->id} to the list of assignments" );
			if ( ! isset( $ordered[ $assignment->order_num ] ) ) {
				Utilities::get_instance()->log( "Using position {$assignment->order_num}" );
				$ordered[ $assignment->order_num ] = $assignment;
			} else {
				
				$ordered[] = $assignment;
				end( $ordered );
				$new_order                        = key( $ordered );
				$ordered[ $new_order ]->order_num = ( $new_order + 1 );
				
				Utilities::get_instance()->log( "Using position {$ordered[$new_order]->order_num }" );
				$Assignment->saveSettings( $ordered[ $new_order ]->id, $ordered[ $new_order ] );
			}
			
		}
		
		Utilities::get_instance()->log( "Sorted all of the assignments: " . print_r( $ordered, true ) );
		
		$new = array();
		
		foreach ( $ordered as $a ) {
			
			$new[] = $a->id;
		}
		
		if ( empty( $new ) && ( ! is_null( $assignmentId ) ) ) {
			
			Utilities::get_instance()->log( "No previously defined assignments. Adding the new one" );
			$new = array( $assignmentId );
		}
		
		Utilities::get_instance()->log( "Assignment list to be used by {$articleId}: " . print_r( $new, true ) );
		
		Utilities::get_instance()->log( "Updating Article settings for ({$articleId}): " );
		$currentArticle->assignment_ids = $new;
		
		Utilities::get_instance()->log( "Saving Article settings for ({$articleId}): " . print_r( $currentArticle, true ) );
		
		$this->saveSettings( $articleId, $currentArticle );
		
		Utilities::get_instance()->log( "Generating the assignments metabox for the article {$articleId} definition" );
		
		$html = $Assignment->configureArticleMetabox( $articleId, true );
		
		Utilities::get_instance()->log( "Transmitting new HTML for metabox" );
		wp_send_json_success( array( 'html' => $html, 'reload' => $reloadPage ) );
		exit();
	}
	
	/**
	 * Save the Article Settings to the metadata table.
	 *
	 * @param $settings - Array of settings for the specific article.
	 *
	 * @return bool - True if successful at updating article settings
	 */
	public function saveSettings( $articleId, $settings = null ) {
		
		$Assignment = Assignment::getInstance();
		global $currentArticle;
		$Tracker = Tracker::getInstance();
		global $post;
		
		if ( empty( $articleId ) ) {
			
			Utilities::get_instance()->log( "No article ID supplied" );
			
			return false;
		}
		
		if ( is_null( $settings ) && ( ( ! isset( $post->post_type ) ) || ( $post->post_type !== $this->cpt_slug ) ) ) {
			
			Utilities::get_instance()->log( "Not an article. " );
			
			return $articleId;
		}
		
		if ( empty( $articleId ) ) {
			
			Utilities::get_instance()->log( "No post ID supplied" );
			
			return false;
		}
		
		if ( wp_is_post_revision( $articleId ) ) {
			
			return $articleId;
		}
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			
			return $articleId;
		}
		
		$savePost = $post;
		
		if ( empty( $settings ) ) {
			
			Utilities::get_instance()->log( "  - Saving metadata from edit.php page, related to the {$this->type} post_type" );
			
			// $this->model->init( $articleId );
			
			$settings = $this->model->loadSettings( $articleId );
			$defaults = $this->model->defaultSettings();
			
			Utilities::get_instance()->log( print_r( $settings, true) );
			
			if ( ! $settings ) {
				
				$settings = $defaults;
			}
			
			foreach ( $settings as $field => $setting ) {
				
				$tmp = isset( $_POST["e20r-article-{$field}"] ) ? $Tracker->sanitize( $_POST["e20r-article-{$field}"] ) : null;
				
				Utilities::get_instance()->log( "Page data : {$field}->" );
				Utilities::get_instance()->log( print_r( $tmp, true) );
				
				if ( 'assignment_ids' == $field ) {
					
					Utilities::get_instance()->log( "Process the assignments array" );
					
					if ( empty( $tmp[0] ) ) {
						
						Utilities::get_instance()->log( "Assignments Array: The assignments key contains no data" );
						Utilities::get_instance()->log( "Create a new default assignment for the article ID: {$currentArticle->id}" );
						
						//Generate a default assignment for this article.
						$status = $Assignment->createDefaultAssignment( $currentArticle );
						
						if ( false !== $status ) {
							
							$tmp[0] = $status;
						}
						
					} else {
						
						foreach ( $tmp as $k => $assignmentId ) {
							
							if ( is_null( $assignmentId ) ) {
								
								Utilities::get_instance()->log( "Assignments Array: Setting key {$k} has no ID}" );
								Utilities::get_instance()->log( "Create a new default assignment for this article ID: {$settings->id}" );
								
								//Generate a default assignment for this article.
								$assignmentId = $Assignment->createDefaultAssignment( $settings );
								
								if ( false !== $assignmentId ) {
									
									Utilities::get_instance()->log( "Replacing empty assignment key #{$k} with value {$assignmentId}" );
									$tmp[ $k ] = $assignmentId;
								}
							}
						}
						
						Utilities::get_instance()->log( "Assignments array after processing: " );
						Utilities::get_instance()->log( print_r( $tmp, true) );
					}
				}
				
				if ( empty( $tmp ) ) {
					
					$tmp = $defaults->{$field};
				}
				
				$settings->{$field} = $tmp;
			}
			
			// Add post ID (article ID)
			$settings->id = isset( $_REQUEST["post_ID"] ) ? intval( $_REQUEST["post_ID"] ) : null;
			
			if ( ! $this->model->saveSettings( $settings ) ) {
				
				Utilities::get_instance()->log( "Error saving settings!" );
			}
			
		} else if ( get_class( $settings ) != 'WP_Post' ) {
			
			Utilities::get_instance()->log( "Received settings from calling function." );
			
			if ( ! $this->model->saveSettings( $settings ) ) {
				
				Utilities::get_instance()->log( "Error saving settings!" );
			}
			
			$post = get_post( $articleId );
			
			setup_postdata( $post );
			
			$this->model->set( 'question', get_the_title() );
			$this->model->set( 'descr', get_the_excerpt() );
			
			wp_reset_postdata();
			
		}
		
		$post = $savePost;
		
	}
	
	/**
	 * Fetch the delay value for the Article editor
	 */
	public function getDelayValue_callback() {
		
		Utilities::get_instance()->log( "Callback initiated" );
		
		check_ajax_referer( 'e20r-tracker-data', 'e20r-tracker-article-settings-nonce' );
		
		Utilities::get_instance()->log( "Nonce is OK" );
		
		$postId = ! empty( $_REQUEST['post_ID'] ) ? intval( $_REQUEST['post_ID'] ) : 0;
		
		if ( empty( $postId ) ) {
			wp_send_json_error( __( 'Error: Not a valid Post/Page', 'e20r-tracker' ) );
			exit();
		}
		
		$Tracker       = Tracker::getInstance();
		$dripFeedDelay = $Tracker->getDripFeedDelay( $postId );
		$seo_summary   = get_post_meta( $postId, '_yoast_wpseo_metadesc', true );
		
		if ( ! empty( $dripFeedDelay ) || ! empty( $seo_summary ) ) {
			wp_send_json_success( array(
				'delay'   => $dripFeedDelay,
				'nodelay' => false,
				'summary' => $seo_summary,
			) );
			exit();
		}
		
		wp_send_json_success( array( 'nodelay' => true ) );
		exit();
	}
	
	/**
	 * Add Meta Box for the WP Post/Page editor
	 *
	 * @param \WP_Post $post
	 */
	public function editor_metabox_setup( $post ) {
		
		$Assignment = Assignment::getInstance();
		
		remove_meta_box( 'postexcerpt', Article_Model::post_type, 'side' );
		remove_meta_box( 'wpseo_meta', Article_Model::post_type, 'side' );
		add_meta_box( 'postexcerpt', __( 'Article Summary' ), 'post_excerpt_meta_box', Article_Model::post_type, 'normal', 'high' );
		
		add_meta_box( 'e20r-tracker-article-settings', __( 'Article Settings', 'e20r-tracker' ), array(
			&$this,
			"addMeta_Settings",
		), Article_Model::post_type, 'normal', 'high' );
		add_meta_box( 'e20r-tracker-answer-settings', __( 'Assignments', 'e20r-tracker' ), array(
			&$Assignment,
			"addMeta_answers",
		), Article_Model::post_type, 'normal', 'high' );
		// add_meta_box('e20r-tracker-article-checkinlist', __('Check-in List', 'e20r-tracker'), array( &$this, "addMeta_SettingsCheckin" ),  Article_Model::post_type, 'normal', 'high');
		
	}
	
	/**
	 * Load the meta box content
	 */
	public function addMeta_Settings() {
		
		global $post;
		
		$savePost = $post;
		
		$def_status = array(
			'publish',
			'pending',
			'draft',
			'future',
			'private',
		);
		
		// Query to load all available settings (used with check-in definition)
		$query = array(
			'post_type'      => apply_filters( 'e20r_tracker_article_types', array( 'post', 'page' ) ),
			'post_status'    => apply_filters( 'e20r_tracker_article_post_status', $def_status ),
			'posts_per_page' => - 1,
		);
		
		wp_reset_query();
		
		//  Fetch Programs
		$lessons = new \WP_Query( $query );
		
		if ( empty( $lessons ) ) {
			
			Utilities::get_instance()->log( "No posts/pages/lessons found!" );
		}
		
		Utilities::get_instance()->log( "Loaded {$lessons->found_posts} posts/pages/lessons" );
		Utilities::get_instance()->log( "Loading settings metabox for article page {$post->ID} or {$savePost->ID}?" );
		
		$settings = $this->model->loadSettings( $post->ID );
		
		$post = $savePost;
		
		ob_start();
		?>
        <div id="e20r-article-settings">
			<?php echo $this->view->viewArticleSettings( $settings, $lessons ); ?>
        </div>
		<?php
		
		echo ob_get_clean();
	}
	
	/**
	 * Add/check whether the interview is complete (add label & nag if not)
	 *
	 * @param string $title
	 * @param bool   $is_complete
	 *
	 * @return string
	 */
	public function interviewCompleteLabel( $title, $is_complete ) {
		
		return $this->view->viewInterviewComplete( $title, $is_complete );
	}
	
	/**
	 * Return the closest article in a list of articles
	 *
	 * @param int   $day
	 * @param array $articles
	 *
	 * @return null|\stdClass
	 *
	 * @since v3.0 - BUG FIX: Didn't verify that the $closest article had a valid release_day
	 */
	public function getClosest( $day, $articles ) {
		
		$closest = null;
		
		foreach ( $articles as $article ) {
			
			if ( $closest === null ||
			     ( ( isset( $closest->release_day ) ? abs( $day - $closest->release_day ) : abs( $day ) ) > abs( $article->release_day - $day ) ) ) {
				$closest = $article;
			}
		}
		
		return $closest;
	}
	
	/**
	 * Filters the content for a post to check whether ot inject Tracker Article info in the post/page.
	 *
	 * @param $content - Content to prepend data to if the article meta warrants is.
	 *
	 * @return string -- The content to display on the page/post.
	 */
	public function contentFilter( $content ) {
		
		Utilities::get_instance()->log( "Processing the_content() filter" );
		
		// Quit if this isn't a single-post display and we're not in the main query of wordpress.
		if ( ! is_singular() && ! is_main_query() || ! is_user_logged_in() || is_front_page() ) {
			return $content;
		}
		
		$new_messages    = null;
		$md_alert        = null;
		$update_reminder = null;
		
		Utilities::get_instance()->log( "Processing a single page and we're in the main query" );
		
		if ( has_shortcode( $content, 'e20r_profile' ) ||
		     has_shortcode( $content, 'progress_overview' ) ||
		     has_shortcode( $content, 'e20r_activity_archive' )
		) {
			// Process in shortcode actions
			return $this->view->new_message_warning() . $content;
		}
		
		global $post;
		global $current_user;
		
		$Tracker = Tracker::getInstance();
		
		global $currentArticle;
		global $currentProgram;
		
		$article_id = isset( $_REQUEST['article-id'] ) ? $Tracker->sanitize( $_REQUEST['article-id'] ) : null;
		$for_date   = isset( $_REQUEST['for-date'] ) ? $Tracker->sanitize( $_REQUEST['for-date'] ) : null;
		$program_id = isset( $_REQUEST['program-id'] ) ? $Tracker->sanitize( $_REQUEST['program-id'] ) : null;
		
		if ( is_null( $for_date ) ) {
			$for_date = $Tracker->sanitize( get_query_var( 'article_date', null ) );
			Utilities::get_instance()->log( "Loaded date for article: {$for_date}" );
		}
		
		$Program = Program::getInstance();
		
		if ( ! is_null( $program_id ) ) {
			
			$Program->setProgram( $program_id );
		} else {
			
			$Program->getProgramIdForUser( $current_user->ID );
		}
		
		if ( is_user_logged_in() && ! empty( $currentProgram->sales_page_ids ) && is_page( $currentProgram->sales_page_ids ) ) {
			
			Utilities::get_instance()->log( "WARNING: Logged in user requested sales page, suspect they want the dashboard..." );
			if ( isset( $currentProgram->dashboard_page_id ) ) {
				
				$to_dashboard = get_permalink( $currentProgram->dashboard_page_id );
				wp_redirect( $to_dashboard, 302 );
				exit();
			}
		}
		
		$program_pages = array();
		
		$pgm_pages = array(
			'dashboard_page_id',
			'activity_page_id',
			'measurements_page_id',
			'progress_page_id',
		);
		
		foreach ( $pgm_pages as $key ) {
			
			if ( ! empty( $currentProgram->{$key} ) ) {
				
				if ( ! is_array( $currentProgram->{$key} ) ) {
					
					$program_pages[] = $currentProgram->{$key};
				} else {
					
					foreach ( $currentProgram->{$key} as $id ) {
						
						$program_pages[] = $id;
					}
				}
			}
		}
		
		if ( ( ! empty( $currentProgram->dashboard_page_id ) && is_page( $program_pages ) ) ) {
			
			Utilities::get_instance()->log( "Check whether this user should have access to the dashboard page for their program (yet!)" );
			
			if ( function_exists( 'pmpro_getMemberStartdate' ) ) {
				
				$user_startdate = date_i18n( 'Y-m-d', pmpro_getMemberStartdate( $current_user->ID ) );
				$today          = date_i18n( 'Y-m-d', current_time( 'timestamp' ) );
				
				if ( $today < $user_startdate ) {
					
					Utilities::get_instance()->log( "Warning: User shouldn't have access to this dashboard yet. Redirect them to the start page(s)?" );
					if ( ! empty( $currentProgram->welcome_page_id ) ) {
						
						wp_redirect( get_permalink( $currentProgram->welcome_page_id ) );
						exit();
					}
				}
			}
		}
		
		Utilities::get_instance()->log( "loading article settings for post ID {$post->ID} and article id: " . ( is_null( $article_id ) ? 'null' : $article_id ) );
		
		$articles = array();
		
		if ( ! is_null( $article_id ) ) {
			
			$articles = $this->find( 'id', $article_id, $currentProgram->id );
		}
		
		if ( empty( $articles ) && is_null( $article_id ) && ! empty( $for_date ) ) {
			
			Utilities::get_instance()->log( "Searching for article based on date argument passed by calling entity/page: {$for_date}" );
			$delayVal = $Tracker->getDelay( $for_date, $current_user->ID );
			$articles = $this->findArticles( 'release_day', $delayVal, $currentProgram->id );
			Utilities::get_instance()->log( "Found: " . print_r( $articles, true ) );
		}
		
		if ( ! empty( $currentProgram->id ) && empty( $articles ) && is_null( $article_id ) && is_null( $for_date ) ) {
			
			Utilities::get_instance()->log( "Searching for article based on the ID of the current post: {$post->ID}" );
			$articles = $this->findArticles( 'post_id', $post->ID, $currentProgram->id );
		}
		
		if ( empty( $articles ) && is_null( $article_id ) && is_null( $for_date ) ) {
			
			$dayNo = $Tracker->getDelay( 'now', $current_user->ID );
			Utilities::get_instance()->log( "Searching for article based on the current delay value: {$dayNo}" );
			
			foreach ( $articles as $article ) {
				
				if ( in_array( $currentProgram->id, $article->program_ids ) ) {
					
					if ( $dayNo == $article->release_day ) {
						
						Utilities::get_instance()->log( "Found the correct article for post {$post->ID}" );
						$this->init( $article->id );
					}
				}
			}
		}
		
		
		if ( empty( $articles ) ) {
			
			Utilities::get_instance()->log( "No article defined for this content. Exiting the filter." );
			
			return $content;
			
		} else {
			
			Utilities::get_instance()->log( "Found article(s). Using first in list returned by search." );
			$article = array_pop( $articles );
			$this->init( $article->id );
		}
		
		$Access = Tracker_Access::getInstance();
		
		if ( ! $Access->hasAccess( $current_user->ID, $currentArticle->post_id ) ) {
			
			Utilities::get_instance()->log( "User doesn't have access to this post/page. Exiting the filter." );
			
			return $content;
		}
		
		Utilities::get_instance()->log( "Restoring article: {$article->id} as the current article after access check" );
		Utilities::get_instance()->log( "User HAS access to post/page: {$post->ID}." );
		$measured = false;
		
		$rDay  = $currentArticle->release_day;
		$rDate = $this->releaseDate( $currentArticle->id );
		
		$programId = $currentProgram->id;
		
		Utilities::get_instance()->log( "Release Date for article: {$rDate} calculated from {$rDay}" );
		
		$md = $this->isMeasurementDay( $currentArticle->id );
		
		$info = '';
		
		$Action = Action::getInstance();
		
		if ( $Action->hasCompletedLesson( $currentArticle->id, $post->ID, $current_user->ID ) ) {
			
			Utilities::get_instance()->log( "Processing a defined article to see if lesson is completed. This is not for a measurement day." );
			
			$currentArticle->complete = true;
		}
		
		$lesson_complete = $this->view->viewLessonComplete( $rDay, $currentArticle->id );
		// $content = $data . $content;
		
		if ( $currentArticle->post_id == $post->ID && has_shortcode( $content, 'daily_progress' ) ) {
			
			$new_messages = $this->view->new_message_warning();
		}
		
		if ( $md ) {
			
			$Measurements = Measurements::getInstance();
			$measured     = $Measurements->areCaptured( $currentArticle->id, $programId, $current_user->ID, $rDate );
			
			Utilities::get_instance()->log( "Result from Measurements::areCaptured: " );
			Utilities::get_instance()->log( print_r( $measured, true) );
			Utilities::get_instance()->log( "Check whether it's a measurement day or not: {$md} " );
			
			if ( $md && ! $measured['status'] ) {
				
				Utilities::get_instance()->log( "It's a measurement day!" );
				$md_alert = $this->view->viewMeasurementAlert( $this->isPhotoDay( $currentArticle->id ), $rDay, $currentArticle->id );
			}
			
			if ( $md && $measured['status'] ) {
				
				Utilities::get_instance()->log( "Measurement day, and we've measured." );
				$md_alert = $this->view->viewMeasurementComplete( $rDay, $md, $currentArticle->id );
			}
		}
		
		$Client = Client::getInstance();
		
		if ( true === ( $is_complete = $Client->completeInterview( $current_user->ID ) && ( $post->ID == $currentProgram->intake_form ) ) ) {
			
			Utilities::get_instance()->log( "User is viewing the Welcome interview page & their interview is saved already" );
			$interview_title = get_the_title( $currentProgram->intake_form );
			$update_reminder = $this->view->viewInterviewComplete( $interview_title, $is_complete );
		}
		
		// Construct content based on available data.
		
		if ( ! empty( $lesson_complete ) ) {
			
			Utilities::get_instance()->log( "Adding lesson complete flag" );
			$info .= $lesson_complete;
		}
		
		if ( ! empty( $update_reminder ) ) {
			Utilities::get_instance()->log( "Adding update reminder for welcome interview" );
			$info .= $update_reminder;
		}
		
		if ( ! empty( $new_messages ) ) {
			Utilities::get_instance()->log( "Adding new messages warning" );
			$info .= $new_messages;
		}
		
		if ( ! empty( $md_alert ) ) {
			Utilities::get_instance()->log( "Adding Weekly Progress reminder" );
			$info .= $md_alert;
		}
		
		Utilities::get_instance()->log( "Content being returned." );
		
		return $info . $content;
	}
	
	/**
	 * Pass-through for the Model::find() method for Articles
	 *
	 * @param string $key
	 * @param null   $val
	 * @param int    $programId
	 * @param string $comp
	 * @param bool   $dont_drop
	 * @param string $type
	 *
	 * @return array|bool|mixed
	 */
	public function findArticles( $key = 'id', $val = null, $programId = - 1, $comp = '=', $dont_drop = false, $type = 'numeric' ) {
		
		return $this->model->find( $key, $val, $programId, $comp, 'DESC', $dont_drop, $type );
	}
	
	/**
	 * Get the release date for the check-in in the article (day)
	 *
	 * @param int $articleId
	 *
	 * @return bool|string
	 */
	public function releaseDate( $articleId ) {
		
		$Tracker = Tracker::getInstance();
		global $currentArticle;
		
		if ( ( empty( $articleId ) || ( $articleId == - 1 ) || ( $articleId == 0 ) ) ) {
			
			$delay = isset( $_POST['e20r-checkin-day'] ) ? $Tracker->sanitize( $_POST['e20r-checkin-day'] ) : null;
			
			if ( isset( $delay ) ) {
				Utilities::get_instance()->log( " No articleId specified... Using delay value from _POST" );
				$release_date = Time_Calculations::getDateForPost( $delay );
				
				return $release_date;
			}
			Utilities::get_instance()->log( " No articleId specified and no delay value found... returning FALSE" );
			
			return false;
		}
		
		if ( ! isset( $currentArticle->id ) || ( $currentArticle->id != $articleId ) ) {
			Utilities::get_instance()->log( "currentArticle is NOT defined." );
			$release_date = Time_Calculations::getDateForPost( $this->model->getSetting( $articleId, 'release_day' ) );
		} else {
			Utilities::get_instance()->log( "currentArticle is defined." );
			$release_date = Time_Calculations::getDateForPost( $currentArticle->release_day );
		}
		
		Utilities::get_instance()->log( "Article::releaseDate: {$release_date}" );
		
		return ( empty( $release_date ) ? false : $release_date );
	}
	
	/**
	 * Is the specified Article is for a measurement day?
	 *
	 * @param null|int $articleId
	 *
	 * @return bool
	 */
	public function isMeasurementDay( $articleId = null ) {
		
		global $currentArticle;
		
		if ( is_null( $articleId ) ) {
			
			$articleId = $currentArticle->id;
		}
		
		return ( $this->model->getSetting( $articleId, 'measurement_day' ) == 0 ? false : true );
		
	}
	
	/**
	 * Is the specified Article for a photo day?
	 *
	 * @param int $articleId
	 *
	 * @return bool
	 */
	public function isPhotoDay( $articleId ) {
		
		Utilities::get_instance()->log( "getting photo_day setting for {$articleId}" );
		
		$setting = $this->model->getSetting( $articleId, 'photo_day' );
		Utilities::get_instance()->log( "Is ({$articleId}) on a photo day ({$setting})? " . ( $setting == 0 ? 'No' : 'Yes' ) );
		
		if ( empty( $setting ) ) {
			Utilities::get_instance()->log( "photo_day setting ID empty/null." );
			
			return false;
		} else {
			return ( $setting != 0 ? true : false );
		}
	}
	
	/**
	 * Load the lesson (post) for the article (include "reading time" data)
	 *
	 * @param null|int $article_id
	 * @param bool     $reading_time
	 *
	 * @return null|string
	 */
	public function load_lesson( $article_id = null, $reading_time = true ) {
		
		global $currentArticle;
		
		global $current_user;
		$html = null;
		
		if ( is_null( $article_id ) ) {
			
			if ( ! isset( $currentArticle->id ) ) {
				
				return null;
			}
		}
		
		if ( $article_id != $currentArticle->id ) {
			
			Utilities::get_instance()->log( "Requested article ID != currentArticle id" );
			
			$this->init( $article_id );
		}
		$Tracker = Tracker::getInstance();
		
		$post = get_post( $currentArticle->post_id );
		setup_postdata( $post );
		
		ob_start(); ?>
        <article class="e20r-article-lesson">
            <div class="e20r-article-lesson-header clear-after">
                <span class="e20r-article-lesson-title">
                    <h2><?php echo apply_filters( 'the_title', $post->post_title ); ?></h2>
                </span>
                <span class="e20r-article-lesson-date">
                    <?php
                    $when = $Tracker->getDateFromDelay( ( $currentArticle->release_day - 1 ), $current_user->ID );
                    echo date_i18n( "M jS", strtotime( $when ) );
                    ?>
                </span>
            </div>
            <div class="e20r-article-lesson">
				<?php
				if ( $reading_time ) { ?>
                    <div
                            class="e20r-article-lesson-readingtime"><?php echo $this->reading_time( $post->post_content ); ?></div>
                    <br/><?php
				} ?>
				<?php echo apply_filters( 'the_content', $post->post_content ); ?>
            </div>
        </article>
		<?php
		
		wp_reset_postdata();
		
		$html = ob_get_clean();
		
		return $html;
	}
	
	/**
     * Calculate the amount of time (roughly) it would take to read the supplied content
     *
	 * @param string $content
	 *
	 * @return string
	 */
	public function reading_time( $content ) {
		
		$words   = str_word_count( strip_tags( $content ) );
		$minutes = floor( $words / 180 );
		$seconds = floor( $words % 180 / ( 180 / 60 ) );
		
		$estimated_time = sprintf( __( '%sTime to read (approximately): %s', 'e20r-tracker' ), '<em>', '</em> <strong>' );
		
		if ( 1 <= $minutes ) {
			
			$estimated_time .= $minutes;
			
			if ( $seconds >= 30 ) {
				$estimated_time .= " &dash; " . ( $minutes + 1 );
			}
			
			$estimated_time .= sprintf( ' %s%s', __( 'minute', 'e20r-tracker' ), ( ( $minutes <= 1 && $seconds < 30 ) ? '' : 's' ) );
		} else {
			
			$estimated_time .= __( 'Less than a minute', 'e20r-tracker' );
		}
		
		$estimated_time .= '</strong>';
		
		return $estimated_time;
	}
	
	/**
     * Display article summary (short code)
     *
	 * @param null $attributes
	 *
	 * @return null|string
	 */
	public function shortcode_article_summary( $attributes = null ) {
		
		$Program = Program::getInstance();
		$Tracker = Tracker::getInstance();
		
		global $currentProgram;
		global $currentArticle;
		
		global $current_user;
		global $post;
		
		$html       = null;
		$article    = null;
		$article_id = null;
		
		$for_date = $Tracker->sanitize( get_query_var( 'article_date' ) );
		Utilities::get_instance()->log( "Loading article summary based on shortcode: {$for_date}" );
		
		if ( ! is_user_logged_in() ) {
			
			auth_redirect();
			wp_die();
		}
		
		if ( ! empty( $_REQUEST ) && ( isset( $_REQUEST['e20r-checkin-nonce'] ) ) ) {
			
			Utilities::get_instance()->log( "Checking for valid check-in Nonce" );
			check_ajax_referer( 'e20r-checkin-data', 'e20r-checkin-nonce' );
			
			$article_id = isset( $_REQUEST['article-id'] ) ? $Tracker->sanitize( $_REQUEST['article-id'] ) : null;
			$for_date   = isset( $_REQUEST['for-date'] ) ? $Tracker->sanitize( $_REQUEST['for-date'] ) : null;
			$program_id = isset( $_REQUEST['program-id'] ) ? $Tracker->sanitize( $_REQUEST['program-id'] ) : null;
		}
		
		if ( ! empty( $_REQUEST ) && ( isset( $_REQUEST['e20r-action-nonce'] ) ) ) {
			
			Utilities::get_instance()->log( "Checking for valid action Nonce (from dashboard)" );
			check_ajax_referer( 'e20r-action-data', 'e20r-action-nonce' );
			
			$article_id = isset( $_REQUEST['article-id'] ) ? $Tracker->sanitize( $_REQUEST['article-id'] ) : null;
			$for_date   = isset( $_REQUEST['for-date'] ) ? $Tracker->sanitize( $_REQUEST['for-date'] ) : null;
			$program_id = isset( $_REQUEST['program-id'] ) ? $Tracker->sanitize( $_REQUEST['program-id'] ) : null;
			Utilities::get_instance()->log( "Checking for valid action Nonce (from dashboard)" );
		}
		
		if ( empty( $program_id ) ) {
			
			Utilities::get_instance()->log( "Loading program info for user {$current_user->ID}" );
			$Program->getProgramIdForUser( $current_user->ID );
		} else {
			Utilities::get_instance()->log( "Loading program config for {$program_id}" );
			$Program->init( $program_id );
		}
		
		if ( ! empty( $for_date ) ) {
			
			Utilities::get_instance()->log( "Received date: {$for_date} and will calculate # of days from that" );
			$days_since_start = Time_Calculations::daysBetween( strtotime( $currentProgram->startdate ), strtotime( $for_date ) );
		} else {
			$days_since_start = $Tracker->getDelay( 'now', $current_user->ID );
		}
		
		Utilities::get_instance()->log( "using delay value of: {$days_since_start}" );
		
		if ( is_null( $article_id ) ) {
			
			global $post;
			
			$articles = $this->model->find( 'post_id', $post->ID, $currentProgram->id );
			
			Utilities::get_instance()->log( "Found " . count( $articles ) . " for this post ID ({$post->ID})" );
			
			foreach ( $articles as $a ) {
				
				if ( $a->release_day == $days_since_start ) {
					
					Utilities::get_instance()->log( "Found article {$a->id} and release day {$a->release_day}" );
					$currentArticle = $a;
					break;
				}
			}
			
			if ( ! isset( $currentArticle->id ) || ( $currentArticle->id == 0 ) ) {
				Utilities::get_instance()->log( "No article ID specified by calling post/page. Not displaying anything" );
				
				return false;
			}
		}
		
		Utilities::get_instance()->log( "Loading article summary shortcode for: {$currentArticle->id}" );
		
		// $program_id = $Program->getProgramIdForUser($current_user->ID);
		// $days_since_start = $Tracker->getDelay('now', $current_user->ID);
		
		if ( ! isset( $currentArticle->id ) ) { // || !empty( $article_id ) && ( $article_id != $currentArticle->id )
			
			$articles = $this->model->find( 'id', $article_id, $currentProgram->id );
			Utilities::get_instance()->log( "Found " . count( $articles ) . " article(s) with post ID {$post->ID}" );
			$article        = array_pop( $articles );
			$article_id     = $article->id;
			$currentArticle = $article;
		}
		
		if ( ! isset( $currentArticle->id ) && ( ! is_null( $article_id ) ) ) {
			
			Utilities::get_instance()->log( "Configure article settings (not needed?) " );
			$this->init( $article_id );
		}
		
		$defaults          = $this->model->defaultSettings();
		$days_of_summaries = ( ! isset( $currentArticle->max_summaries ) || is_null( $currentArticle->max_summaries ) ? $defaults->max_summaries : $currentArticle->max_summaries );
		$title             = null;
		
		$tmp = shortcode_atts( array(
			'days'  => $days_of_summaries,
			'title' => null,
		), $attributes );
		
		Utilities::get_instance()->log( "Article # {$currentArticle->id} needs to locate {$tmp['days']} or {$days_of_summaries} days worth of articles to pull summaries from, ending on day # {$currentArticle->release_day}" );
		
		if ( isset( $tmp['title'] ) && ! empty( $tmp['title'] ) ) {
			$title = $tmp['title'];
		}
		
		if ( $days_of_summaries != $tmp['days'] ) {
			
			$days_of_summaries = $tmp['days'];
		}
		
		$start_day = ( $currentArticle->release_day - $days_of_summaries );
		$gt_days   = ( $currentArticle->release_day - $days_of_summaries );
		
		$start_TS = strtotime( "{$currentProgram->startdate} +{$start_day} days" );
		
		Utilities::get_instance()->log( "Searching for articles with release_day between start: {$start_day} and end: {$currentArticle->release_day}" );
		
		$history = $this->find( 'release_day', array(
			( $start_day - 1 ),
			$currentArticle->release_day,
		), $currentProgram->id, 'BETWEEN' );
		
		Utilities::get_instance()->log( "Fetched " . count( $history ) . " articles to pull summaries from" );
		
		$summary = array();
		
		foreach ( $history as $k => $a ) {
			
			$new = array(
				'title'   => null,
				'summary' => null,
				'day'     => null,
			);
			
			if ( ! empty( $a->post_id ) ) {
				
				$p   = get_post( $a->post_id );
				$art = get_post( $a->id );
				
				Utilities::get_instance()->log( "Loading data for post {$a->post_id} vs {$p->ID}" );
				
				$new['day']   = $a->release_day;
				$new['title'] = $p->post_title;
				
				if ( ! empty( $art->post_content ) ) {
					
					Utilities::get_instance()->log( "Using the article description." );
					$new['summary'] = wp_kses_allowed_html( $art->post_content );
					
				} else if ( ! empty( $art->post_excerpt ) ) {
					
					Utilities::get_instance()->log( "Using the article summary." );
					$new['summary'] = $art->post_excerpt;
					
				} else if ( ! empty( $p->post_excerpt ) ) {
					
					Utilities::get_instance()->log( "Using the post excerpt." );
					$new['summary'] = $p->post_excerpt;
				} else {
					
					Utilities::get_instance()->log( "Using the post summary." );
					$new['summary'] = $p->post_content;
					$new['summary'] = wp_trim_words( $new['summary'], 30, " [...]" );
				}
				
				Utilities::get_instance()->log( "Current day: {$currentArticle->release_day} + Last release day to include: {$gt_days}." );
				
				if ( ( $new['day'] > $gt_days ) ) {
					
					if ( ( $a->measurement_day != true ) && ( $a->summary_day != true ) ) {
						
						Utilities::get_instance()->log( "Adding {$new['title']} (for day# {$new['day']}) to list of posts to summarize" );
						$summary[ $a->release_day ] = $new;
					}
				}
				
				$new = array();
				$a   = null;
				wp_reset_postdata();
			}
		}
		
		ksort( $summary );
		
		Utilities::get_instance()->log( "Original prefix of {$currentArticle->prefix}" );
		$prefix = lcfirst( preg_replace( '/\[|\]/', '', $currentArticle->prefix ) );
		Utilities::get_instance()->log( "Scrubbed prefix: {$prefix}" );
		// Utilities::get_instance()->log($summary);
		
		$summary_post = get_post( $currentArticle->id );
		$info         = null;
		
		wp_reset_postdata();
		
		if ( ! empty( $summary_post->post_content ) ) {
			
			$info = wpautop( $summary_post->post_content_filtered );
		}
		
		// Since we're saving the array using the delay day as the key we'll have to do some jumping through hoops
		// to get the right key for the last day in the list.
		$k_array          = array_keys( $summary );
		$last_day_key     = count( $summary ) > 0 ? $k_array[ ( count( $summary ) - 1 ) ] : 0;
		$last_day_summary = isset( $summary[ $last_day_key ] ) ? $summary[ $last_day_key ] : null;
		$end_day          = ! empty( $last_day_summary['day'] ) ? $last_day_summary['day'] : 7;
		
		Utilities::get_instance()->log( "Using end day for summary period: {$end_day}" );
		
		$end_TS = strtotime( "{$currentProgram->startdate} +{$end_day} days", current_time( 'timestamp' ) );
		
		$html = $this->view->view_article_history( $prefix, $title, $summary, $start_TS, $end_TS, $info );
		
		return $html;
	}
}