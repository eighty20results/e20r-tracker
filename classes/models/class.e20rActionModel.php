<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  GPL v2 license
 */

class e20rActionModel extends e20rSettingsModel {

    private $settings;

    private static $instance = null;

    public function __construct()  {

        parent::__construct( 'action', 'e20r_actions' );

/*        $e20rTables = e20rTables::getInstance();

        $this->table = $e20rTables->getTable('action');
        $this->fields = $e20rTables->getFields('action');
*/
    }

	/**
	 * @return e20rActionModel
	 */
	static function getInstance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

    public function defaultCheckin( $type ) {

        global $currentProgram;
        global $current_user;

        $default = new stdClass();
        $default->id = null;
        $default->user_id = $current_user->ID;
        $default->program_id = isset( $currentProgram->id ) ? $currentProgram->id : null;
        $default->article_id = CONST_NULL_ARTICLE;
        $default->checkin_date = date('Y-m-d', current_time('timestamp'));
        $default->checkin_type = $type;
        $default->checkin_note = null;
        $default->checkedin = null;
        $default->checkin_short_name = 'default_check_in';

        if ( CHECKIN_ACTION === $type ) {

            $default->actionList = array();
            $default->actionList[] = $this->defaultAction();
        }

        return $default;
    }

    public function defaultSettings() {

        global $post;

        $settings = parent::defaultSettings();
	    $settings->id = ( isset( $post->id ) ? $post->id : null );
        $settings->checkin_type = 0; // 1 = Action, 2 = Assignment, 3 = Survey, 4 = Activity.
        $settings->item_text = ( isset( $post->post_excerpt ) ? $post->post_excerpt : 'Not scheduled' );
        $settings->short_name =  ( isset( $post->post_title ) ? $post->post_title : null );
        $settings->startdate = null;
        $settings->enddate = null;
        $settings->maxcount = 0;
        $settings->program_ids = array();

        return $settings;
    }

    public function findActionByDate( $date, $programId ) {

        dbg("e20rActionModel::findActionByDate() - Searching by date: {$date}" );

	    $actions = array();

        $args = array(
            'posts_per_page' => -1,
            'post_type' => 'e20r_actions',
            'post_status' => 'publish',
            'order_by' => 'meta_value',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_e20r-action-startdate',
                    'value' => $date,
                    'compare' => '<=',
                    'type' => 'DATE',
                ),
                array(
                    'key' => '_e20r-action-enddate',
                    'value' => $date,
                    'compare' => '>=',
                    'type' => 'DATE',
                ),
                array(
                    'key' => '_e20r-action-program_ids',
                    'value' => $programId,
                    'compare' => '=',
                    'type' => 'numeric'
                )
            )
        );

        $query = new WP_Query( $args );
        dbg("e20rActionModel::findActionByDate() - Returned actions: {$query->post_count} for query... " );
	    // dbg($args);

        while ( $query->have_posts() ) {

            $query->the_post();

            $actions[] = get_the_ID();

            /*
            $id = get_the_ID();


            dbg("e20rActionModel::findActionByDate() - Getting program info for action ID: {$id}");

            $programs = get_post_meta( $id, '_e20r-action-program_ids');

            dbg("e20rActionModel::findActionByDate() - Getting program info... ");

            if ( in_array( $programId, $programs ) || ( $programs === false ) ) {


                $actions[] = $id;
            }
            */
        }

        wp_reset_postdata();

	    dbg("e20rActionModel::findActionByDate() - Returning " . count($actions) . " action ids");
	    // dbg( $actions );

        return $actions;
    }

	public function defaultAction() {

		$action = $this->defaultSettings();
		$action->id = CONST_NULL_ARTICLE;
		$action->item_text = 'No action scheduled';
		$action->short_name = 'null_action';

		return $action;
	}

    public function getActions( $id, $type = 1, $numBack = -1 ) {

        global $currentProgram;

        dbg("e20rActionModel::getActions() - id: {$id}, type: {$type}, records: {$numBack}");

        $start_date = $this->getSetting( $id, 'startdate' );
        $checkins = array();

        dbg("e20rActionModel::getActions() - Loaded startdate: {$start_date} for id {$id}");

        $args = array(
            'posts_per_page' => $numBack,
            'post_type' => 'e20r_actions',
            'post_status' => 'publish',
            'order_by' => 'meta_value',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_e20r-action-startdate',
                    'value' => $start_date,
                    'compare' => '<=',
                    'type' => 'DATE',
                ),
                array(
                    'key' => '_e20r-action-checkin_type',
                    'value' => $type,
                    'compare' => '=',
                    'type' => 'numeric',
                ),
                array(
                    'key' => '_e20r-action-program_ids',
                    'value' => $currentProgram->id,
                    'compare' => '=',
                    'type' => 'numeric'
                ),
            )
        );

        $query = new WP_Query( $args );
        dbg("e20rActionModel::getActions() - Returned checkins: {$query->post_count}" );
	    // dbg($args);

        while ( $query->have_posts() ) {

            $query->the_post();

            $new = $this->loadSettings( get_the_ID() );

            /*
            if ( ! in_array( $currentProgram->id, $new->program_ids ) ) {
                dbg( "e20rActionModel::getActions() - {$new->id} not part of program {$currentProgram->id}");
                continue;
            }
            */
            $new->id = get_the_ID();
            $new->item_text = $query->post->post_excerpt;
            $new->short_name = $query->post->post_title;

            $checkins[] = $new;
        }

        wp_reset_postdata();
        return $checkins;
    }

	// TODO: This requires the presence of checkin IDs in the Article list, etc.
	// checkin definitions -> $obj->type, $obj->
/*
	public function lessonComplete( $articleId, $userId = null ) {

		dbg("e20rArticleModel::lessonComplete() - Checking lesson status for article: {$articleId} (ID)");

		global $wpdb;
		global $currentArticle;
		global $current_user;
		$e20rTracker = e20rTracker::getInstance();
		$e20rTables = e20rTables::getInstance();
		$e20rAction = e20rAction::getInstance();

		if ( is_null( $userId ) ) {

			$userId = $current_user->ID;
		}



		$sql = $wpdb->prepare("
	    		    SELECT checkedin
	    		    FROM $e20rTables->getTable('checkin')
	    		    WHERE article_id = %d AND user_id = %d AND
	    		    	program_id = %d AND checkin_type =



	    ");
		// Find the e20r_checkin record with the $articleId,
		// for the $this->releaseDate( $articleId )
		// AND the $userId AND the $checkin_item_id
		// AND the $checkin_type == 1 (lesson)
		// AND the $programId that applies to this $articleId and $userId.
		return false;
	}
*/
	public function loadCheckinsForUser( $userId, $articleArr, $typeArr, $dateArr ) {

		global $wpdb;
		global $current_user;
		$e20rTracker = e20rTracker::getInstance();
		$e20rProgram = e20rProgram::getInstance();
		$e20rArticle = e20rArticle::getInstance();

		$programId = $e20rProgram->getProgramIdForUser( $userId );
		
		$checkin_list = $e20rTracker->prepare_in("c.checkin_type IN ([IN])", $typeArr );
		// Add the article ID array
		$article_in = $e20rTracker->prepare_in(" IN ([IN]) ) ", $articleArr );
		
		$sql = "SELECT *
                 FROM {$this->table} AS c
                 WHERE ( ( c.user_id = %s ) AND
                  ( c.program_id = %d ) AND
                  ( {$checkin_list} ) AND
                  ( c.checkin_date BETWEEN %s AND %s ) AND
                  ( c.article_id {$article_in} )";
		
		$sql = $wpdb->prepare( $sql,
			$userId,
			$programId,
			$dateArr['min'] . " 00:00:00",
			$dateArr['max'] . " 23:59:59"
		);
		
		dbg("e20rActionModel::loadCheckinsForUser({$userId}) - Using SQL: {$sql}");
		
		// dbg("e20rActionModel::loadCheckinsForUser() - SQL: {$sql}");

		$results = $wpdb->get_results( $sql );

		if ( is_wp_error( $results ) ) {

			dbg("e20rActionModel::loadCheckinsForUser() - Error: {$wpdb->last_error}");
			return array();
		}

		return $results;
	}

    public function get_user_checkin( $config, $userId, $type, $short_name = null ) {

        global $wpdb;
        global $current_user;
	    global $currentAction;

        $e20rProgram = e20rProgram::getInstance();
        $e20rArticle = e20rArticle::getInstance();
        $e20rTracker = e20rTracker::getInstance();

	    dbg("e20rActionModel::get_user_checkin() - Loading type {$type} check-ins for user {$userId}");

        $programId = $e20rProgram->getProgramIdForUser( $userId );

        if ( empty( $config->articleId ) || ( $config->articleId == -1 ) ) {

            $date = $e20rTracker->getDateForPost( $config->delay );
        }
        else {
            $date = $e20rArticle->releaseDate( $config->articleId );
        }
	    // if ( $currentAction->articleId )

        dbg("e20rActionModel::get_user_checkin() - date for article # {$config->articleId} in program {$programId} for user {$userId}: {$date}");

        if ( is_null( $short_name ) ) {

            dbg("e20rActionModel::get_user_checkin() - No short_name defined...");
            $sql = $wpdb->prepare(
                "SELECT *
                 FROM {$this->table} AS c
                 WHERE ( ( c.user_id = %d ) AND
                  ( c.program_id = %d ) AND
                  ( c.checkin_type = %d ) AND
                  ( c.checkin_date LIKE %s ) AND
                  ( c.article_id = %d ) )",
                $userId,
                $programId,
                $type,
                $date . "%",
                $config->articleId
            );
        }
        else {
            dbg("e20rActionModel::get_user_checkin() - short_name defined: {$short_name}");
            $sql = $wpdb->prepare(
                "SELECT *
                 FROM {$this->table} AS c
                 WHERE ( ( c.user_id = %d ) AND
                  ( c.checkin_short_name = %s ) AND
                  ( c.program_id = %d ) AND
                  ( c.checkin_type = %d ) AND
                  ( c.checkin_date LIKE %s ) AND
                  ( c.article_id = %d ) )",
                $userId,
                $short_name,
                $programId,
                $type,
                $date . "%",
                $config->articleId
            );
        }

        // dbg("e20rActionModel::get_user_checkin() - SQL: {$sql}");

        $result = $wpdb->get_row( $sql );

        if ( $result === false ) {

            dbg("e20rActionModel::get_user_checkin() - Error loading check-in: " . $wpdb->last_error );
            return null;
        }

        dbg("e20rActionModel::get_user_checkin() - Loaded {$wpdb->num_rows} check-in records");

        if ( empty( $result ) ) {

            dbg( "e20rActionModel::get_user_checkin() - No check-in records found for this user (ID: {$current_user->ID})");

            /*
            if ( empty( $this->settings ) ) {
                $this->loadSettings( $articleId );
            }
            */
	        $a = $this->findActionByDate( $date, $programId );

	        $result = new stdClass();
	        $result->id = null;

	        if ( is_array( $a ) && ( count( $a ) >= 1 ) ) {

		        dbg( "e20rActionModel::get_user_checkin() - Default action: Found one or more ids");

		        foreach ( $a as $i ) {

			        $n_type = $this->getSetting( $i, 'checkin_type' );

			        dbg( "e20rActionModel::get_user_checkin() - Default action: Type settings for {$i}: {$n_type}");

			        if ($n_type == $type ) {

				        dbg('e20rActionModel::get_user_checkin() - Default action: the type settings are correct. Using it...');
				        $result->id = $i;
				        break;
			        }

			        dbg("e20rActionModel::get_user_checkin() - Default action: the type mismatch {$n_type} != {$type}. Looping again.");
		        }

	        }

            // $result->descr_id = $short_name;
            $result->user_id = $current_user->ID;
            $result->program_id = $programId;
            $result->article_id = $config->articleId;
            $result->checkin_date = $date;
	        $result->checkin_type = $type;
            $result->checkin_note = null;
            $result->checkedin = null;
            $result->checkin_short_name = $short_name;

            dbg("e20rActionModel::get_user_checkin() - Default action: No user record found");
            // dbg($result);
        }

        return $result;
    }

    public function exists( $checkin ) {

        global $wpdb;

        dbg("e20rActionModel::exists() -  Data: ");
        dbg( $checkin );

        if ( ! is_array( $checkin ) ) {

            return false;
        }

        $sql = $wpdb->prepare(
           "SELECT id, checkedin
                FROM {$this->table}
                WHERE (
                ( {$this->fields['user_id']} = %d ) AND
                ( {$this->fields['checkin_date']} LIKE %s ) AND
                ( {$this->fields['program_id']} = %d ) AND
                ( {$this->fields['checkin_type']} = %d ) AND
                ( {$this->fields['checkin_short_name']} = %s )
                )
           ",
            $checkin['user_id'],
            $checkin['checkin_date'] . '%',
            $checkin['program_id'],
            $checkin['checkin_type'],
            $checkin['checkin_short_name']
        );

        $result = $wpdb->get_row( $sql );

        if ( ! empty( $result ) ) {

            dbg("e20rActionModel::exists() - Got a result returned: ");
            dbg($result);
            return $result;
        }

        return false;
    }

    public function loadSettings( $id ) {

        $this->settings = parent::loadSettings($id);

        $pst = get_post( $id );

        $this->settings->item_text = $pst->post_excerpt;
        $this->settings->short_name = $pst->post_title;

	    if ( empty( $this->settings->program_ids ) ) {

		    $this->settings->program_ids = array();
	    }

        return $this->settings;
    }

    public function isValid( $checkin_array ) {

        if ( array_key_exists( 'descr_id', $checkin_array ) &&
            array_key_exists( 'checkedin', $checkin_array ) &&
            array_key_exists( 'program_id', $checkin_array ) &&
            array_key_exists( 'user_id', $checkin_array ) &&
            array_key_exists( 'action_id', $checkin_array ) &&
            array_key_exists( 'checkin_type', $checkin_array ) &&
            array_key_exists( 'checkin_date', $checkin_array ) ) {

            return true;
        }

        return false;
    }

    public function saveCheckin( $checkin ) {

        global $wpdb;

        dbg("e20rActionModel::setCheckin() - Check if the record exists already");

        if ( ( $result = $this->exists( $checkin ) ) !== false ) {

            dbg("e20rActionModel::setCheckin() - found existing record: ");
            dbg($result->id);

            $checkin['id'] = $result->id;
        }

        dbg("e20rActionModel::setCheckin() - Checkin record:");
        dbg($checkin);

        if ( false !== $wpdb->replace( $this->table, $checkin ) ) {

            $result = $this->exists( $checkin );

            return $result->id;
        }

        return false;
    }

    public function setCheckin( $checkin ) {

        global $wpdb;

	    dbg("e20rActionModel::setCheckin() - Check if the record exists already");

        if ( ( $result = $this->exists( $checkin ) ) !== false ) {

            dbg("e20rActionModel::setCheckin() - found existing record: ");
            dbg($result->id);

            $checkin['id'] = $result->id;
        }

        dbg("e20rActionModel::setCheckin() - Checkin record:");
        dbg($checkin);

        return ( $wpdb->replace( $this->table, $checkin ) ? true : false );
    }

    /**
     * Save the Checkin Settings to the metadata table.
     *
     * @param $settings - Array of settings for the specific checkin.
     *
     * @return bool - True if successful at updating checkin settings
     */
    public function saveSettings( $settings ) {

        $checkinId = $settings->id;

        $defaults = $this->defaultSettings();

        dbg("e20rActionModel::saveSettings() - Saving checkin Metadata: " . print_r( $settings, true ) );

        $error = false;

        foreach ( $defaults as $key => $value ) {

            if ( in_array( $key, array( 'id', 'short_name', 'item_text' ) ) ) {
                continue;
            }

            if ( false === $this->settings( $checkinId, 'update', $key, $settings->{$key} ) ) {

                dbg( "e20rAction::saveSettings() - ERROR saving {$key} setting ({$settings->{$key}}) for check-in definition with ID: {$checkinId}" );

                $error = true;
            }
        }

        return ( !$error ) ;
    }

}