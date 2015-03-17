<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rCheckin extends e20rSettings {

    private $checkin = array();

    protected $model;
    protected $view;

    // checkin_type: 0 - action (habit), 1 - lesson, 2 - activity (workout), 3 - survey
    // "Enum" for the types of check-ins
    private $types = array(
        'none' => 0,
        'action' => CHECKIN_ACTION,
        'assignment' => CHECKIN_ASSIGNMENT,
        'survey' => CHECKIN_SURVEY,
        'activity' => CHECKIN_ACTIVITY,
	    'note' => CHECKIN_NOTE
    );

    // checkedin values: 0 - false, 1 - true, 2 - partial, 3 - not applicable
    // "Enum" for the valid statuses.
    private $status = array(
        'no' => 0,
        'yes' => 1,
        'partial' => 2,
        'na' => 3
    );

    public function __construct() {

        dbg("e20rCheckin::__construct() - Initializing Checkin class");

        $this->model = new e20rCheckinModel();
        $this->view = new e20rCheckinView();

        parent::__construct( 'checkin', 'e20r_checkins', $this->model, $this->view );
    }

	public function hasCompletedLesson( $articleId, $postId = null, $userId = null ) {

		dbg("e20rCheckin::hasCompletedLesson() - Verify whether the current UserId has checked in for this lesson.");

		global $post;
		global $currentArticle;
		global $e20rArticle;

        $config = new stdClass();

		if ( is_null( $postId ) ) {

			$postId = $post->ID;
		}

		if ( empty( $currentArticle ) || ( $currentArticle->post_id != $postId ) ) {

			dbg("e20rCheckin::hasCompletedLesson() - loading settings for article post #{$postId} (ID)");
			$config->articleId = $e20rArticle->init( $postId );
		}

		if ( ! isset( $config->articleId ) ) {
			dbg("e20rCheckin::hasCompletedLesson() - No article ID defined. Exiting.");
			return false;
		}

		dbg("e20rCheckin::hasCompletedLesson() - Check if the e20r-checkin table indicates a completed lesson...");
		$checkin = $this->model->loadUserCheckin( $config, $userId, CHECKIN_ASSIGNMENT );

		if ( isset( $checkin->checkedin ) && ( $checkin->checkedin == 1 ) ) {

			dbg("e20rCheckin::hasCompletedLesson() - User has completed this check-in.");
			return true;
		}
		else {

			dbg("e20rCheckin::hasCompletedLesson() - No user check-in found.");
			return false;
		}

		// return $this->model->lessonComplete( $this->articleId );

	}

	public function hasCheckedIn( $userId, $articleId, $type = CHECKIN_ASSIGNMENT) {

		global $wpdb;
		global $currentArticle;
		global $current_user;
		global $e20rTracker;
		global $e20rTables;
		global $e20rCheckin;

		if ( is_null( $userId ) ) {

			$userId = $current_user->ID;
		}

		if ( ( empty($currentArticle) ) || ( $currentArticle->id != $articleId ) ) {

			dbg("e20rArticleModel::lessonComplete() - loading settings for article: {$articleId} (ID)");
			$currentArticle = $this->loadSettings( $articleId );
		}

		$sql = $wpdb->prepare("
	    		    SELECT checkedin
	    		    FROM $e20rTables->getTable('checkin')
	    		    WHERE article_id = %d AND user_id = %d AND
	    		    	program_id = %d AND checkin_type =



	    ");
	}

    public function findCheckinItemId( $articleId ) {

        global $e20rArticle;
    }

    public function saveCheckin_callback() {

        dbg("e20rCheckin::saveCheckin_callback() - Attempting to save checkin for user.");

        // Save the $_POST data for the Action callback
        global $current_user;
	    global $e20rTracker;

        dbg("e20rCheckin::saveCheckin_callback() - Content of POST variable:");
        dbg($_POST);

        $data = array(
            'user_id' => $current_user->ID,
            'id' => (isset( $_POST['id']) ? ( $e20rTracker->sanitize( $_POST['id'] ) != 0 ?  $e20rTracker->sanitize( $_POST['id'] ) : null ) : null),
            'checkin_type' => (isset( $_POST['checkin-type']) ? $e20rTracker->sanitize( $_POST['checkin-type'] ) : null),
            'article_id' => (isset( $_POST['article-id']) ? $e20rTracker->sanitize( $_POST['article-id'] ) : null ),
            'program_id' => (isset( $_POST['program-id']) ? $e20rTracker->sanitize( $_POST['program-id'] ) : -1 ),
            'checkin_date' => (isset( $_POST['checkin-date']) ? $e20rTracker->sanitize( $_POST['checkin-date'] ) : null ),
	        'checkedin_date' => (isset( $_POST['checkedin-date']) ? $e20rTracker->sanitize( $_POST['checkedin-date'] ) : null ),
	        'descr_id' => (isset( $_POST['assignment-id']) ? $e20rTracker->sanitize( $_POST['assignment-id'] ) : null ),
	        'checkin_note' => (isset( $_POST['checkin-note']) ? $e20rTracker->sanitize( $_POST['checkin-note'] ) : null ),
            'checkin_short_name' => (isset( $_POST['checkin-short-name']) ? $e20rTracker->sanitize( $_POST['checkin-short-name'] ) : null),
            'checkedin' => (isset( $_POST['checkedin']) ? $e20rTracker->sanitize( $_POST['checkedin'] ) : null),
        );

        if ( ! $this->model->setCheckin( $data ) ) {

            dbg("e20rCheckin::saveCheckin_callback() - Error saving checkin information...");
            wp_send_json_error();
            wp_die();
        }

        wp_send_json_success();
        wp_die();
    }

    public function setArticleAsComplete( $userId, $articleId ) {

        global $e20rArticle;
        global $e20rProgram;
        global $e20rTracker;

        // $articleId = $e20rArticle->init( $articleId );
        $programId = $e20rProgram->getProgramIdForUser( $userId );

        $defaults = array(
            'user_id' => $userId,
            'checkedin' => $this->status['yes'],
            'article_id' => $articleId,
            'program_id' => $programId,
            'checkin_date' => $e20rArticle->getReleaseDate( $articleId ),
            'checkin_short_name' => null,
            'checkin_type' => $this->types['action'],
            'checkin_note' => null,
        );

        if ( $this->model->setCheckin( $defaults ) ) {
            dbg("e20rCheckin::setArticleAsComplete() - Check-in for user {$userId}, article {$articleId} in program ${$programId} has been saved");
            return true;
        }

        dbg("e20rCheckin::setArticleAsComplete() - Unable to save check0in value!");
        return false;
    }

    /*
    public function getCheckin( $shortName ) {

        $chkinList = $this->model->loadAllSettings( 'any' );

        foreach ($chkinList as $chkin ) {

            if ( $chkin->short_name == $shortName ) {

                unset($chkinList);
                return $chkin;
            }
        }

        unset($chkinList);
        return false; // Returns false if the program isn't found.
    }
*/
    /*
    public function getAllCheckins() {

        return $this->model->loadAllSettings();

    }
    public function getCheckinSettings( $id ) {

        return $this->model->loadSettings( $id );
    }
*/
	private function count_actions( $action_list, $type, $start_date, $end_date ) {

		$action_count = 0;

		foreach( $action_list as $action ) {

			$comp_date = date( 'Y-m-d', strtotime( $action->checkin_date ) );

			if ( ( $action->checkin_type == $type) && ( $action->checkedin == 1 ) &&
			     ( $comp_date >= $start_date ) && ( $comp_date <= $end_date ) ) {

				$action_count += 1;
			}
		}

		dbg("e20rCheckin::count_actions() - Counted {$action_count} completed actions of type {$type} between {$start_date} and {$end_date}");
		return $action_count;
	}

	private function days_of_action( $checkin ) {

		global $e20rTracker;

		if ( $checkin->enddate <= date( 'Y-m-d', current_time( 'timestamp' ) ) ) {

			$days_to_add = $checkin->maxcount;
		}
		else {
			// Calculate the # of days passed since start of current action
			$days_to_add = $e20rTracker->daysBetween( strtotime( $checkin->startdate . " 00:00:00" ), current_time( 'timestamp' ), get_option( 'timezone_string' ) );
		}

		return $days_to_add;
	}

	public function listUserAccomplishments( $userId ) {

		global $currentProgram;
		global $current_user;

		global $e20rArticle;
		global $e20rAssignment;

		global $e20rProgram;
		global $e20rTracker;

		// $config = new stdClass();

		$user_delay = $e20rTracker->getDelay( 'now', $current_user->ID );

		if ( ! isset( $currentProgram->id ) ) {

			$e20rProgram->getUserProgramIdForUser( $userId );
		}

		$programId = $currentProgram->id;

		$art_list = $e20rArticle->loadArticlesByMeta( 'release_day', $user_delay, 'numeric', $programId, '<=' );
		dbg("e20rCheckin::listUserAccomplishments() - Loaded " . count( $art_list ) . " articles between start of program #{$programId} and day #{$user_delay}:");

		$results = array();
		$aIds = array();
		$dates = array();
		$delays = array();
		$actions = array();

		// Get all articleIds to look for:
		foreach( $art_list as $article ) {

			$aIds[] = $article->id;
			$delays[] = $article->release_day;
		}

		// Sort the delays (to find min/max delays)
		sort($delays, SORT_NUMERIC );

		// dbg("e20rCheckin::getAllUserAccomplishments() - Loading user check-in for {$article->id} with delay value: {$article->release_day}");
		$dates['min'] = $e20rTracker->getDateForPost( $delays[0] );
		$dates['max'] = $e20rTracker->getDateForPost( $delays[ ( count( $delays ) - 1 ) ] );

		dbg("e20rCheckin::listUserAccomplishments() - Dates between: {$dates['min']} and {$dates['max']}");

		// Get an array of actions & Activities to match the max date for the $programId
		$cTypes = array( $this->types['action'], $this->types['activity'] );
		$curr_action_ids = $this->model->findActionByDate( $e20rTracker->getDateForPost( $user_delay, $userId ), $programId );

		foreach( $curr_action_ids as $id ) {

			$type = $this->model->getSetting( $id, 'checkin_type' );

			switch ($type) {

				case CHECKIN_ACTION:
					$type_string = 'action';
					break;

				case CHECKIN_ACTIVITY:
					$type_string = 'activity';
					break;

				case CHECKIN_ASSIGNMENT:
					$type_string = 'assignment';
					break;

				default:
					dbg("e20rCheckin::getAllUserAccomplishments() - No activity type specified in record! ($id)");
			}

			// Get all actions of this type.
			$actions[$type_string] = $this->model->getActions( $id, $type, -1 );

		}

		$results['program_days'] = 0; //$user_delay;
		$results['program_score'] = 0;

		dbg("e20rCheckin::listUserAccomplishments() - Loaded " . count($actions). " defined actions. I.e. all possible 'check-ins' for this program so far.");
		$checkins = $this->model->loadCheckinsForUser( $current_user->ID, $aIds, $cTypes, $dates );
		$lessons = $this->model->loadCheckinsForUser( $current_user->ID, $aIds, array( $this->types['assignment'] ), $dates );

		foreach( $actions as $type => $a_list ) {

			foreach ( $a_list as $action ) {

				// Skip
				if ( $action->checkin_type != $this->types['action'] ) {

					dbg( "e20rCheckin::listUserAccomplishments() - Skipping {$action->id} since it's not an action/habit: {$this->types['action']}" );
					dbg( $action );
					continue;
				}

				$count = 0;

				$action_count     = 0;
				$activity_count   = 0;
				$assignment_count = 0;

				$results[ $action->startdate ]             = new stdClass();
				$results[ $action->startdate ]->actionText = $action->item_text;
				$results[ $action->startdate ]->days       = $this->days_of_action( $action );

				dbg( "e20rCheckin::listUserAccomplishments() - Processing actions" );
				$action_count     = $this->count_actions( $checkins, $this->types['action'], $action->startdate, $action->enddate );

				dbg( "e20rCheckin::listUserAccomplishments() - Processing activities" );
				$activity_count   = $this->count_actions( $checkins, $this->types['activity'], $action->startdate, $action->enddate );

				dbg( "e20rCheckin::listUserAccomplishments() - Processing assignments" );
				$assignment_count = $this->count_actions( $lessons, $this->types['assignment'], $action->startdate, $action->enddate );

				$avg_score = 0;

				foreach ( array( 'action', 'activity', 'assignment' ) as $key ) {

					$var_name = "{$key}_count";

					$results[ $action->startdate ]->{$key} = new stdClass();
					$score = round( ( ${$var_name} / $results[ $action->startdate ]->days ), 2 );
					$badge = null;

					if ( ( $score >= 0.7 ) && ( $score < 0.8 ) ) {

						$badge = 'bronze';
					}
					elseif ( ( $score >= 0.8 ) && ( $score < 0.9 ) ) {

						$badge = 'silver';
					}
					elseif ( ( $score >= 0.9 ) && ( $score <= 1.0 ) ) {

						$badge = 'gold';
					}

					$results[ $action->startdate ]->{$key}->badge = $badge;
					$results[ $action->startdate ]->{$key}->score = $score;
					$avg_score += $score;
				}


				$results['program_days'] += $results[ $action->startdate ]->days;
				$avg_score = ( $avg_score / 3 );

				// All $action->shortname entries minus the two program_* entries in the array.
				$result_count = count( $results ) - 2;

				// Set the overall program score for this user.
				$results['program_score'] = ( $results['program_score'] + $avg_score ) / ( $result_count + 1 );

			}
		}

		// Get list of articles (assignment check-ins) we could have completed until now (as array w/articleId as key).
		// Get list of activities we could have completed until now (as array w/articleId as key).
		// Get list of actions we could have completed until now (as array w/articleId as key).

		return $this->view->view_user_achievements( $results );
	}

    public function editor_metabox_setup( $post ) {

        add_meta_box('e20r-tracker-checkin-settings', __('Action Settings', 'e20rtracker'), array( &$this, "addMeta_Settings" ), 'e20r_checkins', 'normal', 'high');

    }

    public function dailyProgress( $config ) {

        global $e20rTracker;
        global $e20rArticle;
	    global $e20rProgram;
	    global $e20rActivity;
	    global $e20rAssignment;
	    global $currentArticle;

        global $current_user;
	    global $post;

	    if ( empty( $currentArticle) || ( $config->articleId != $currentArticle->id ) ) {

		    dbg("e20rCheckin::dailyProgress() - No or wrong article is active. Updating...");
		    $e20rArticle->init( $post->ID );
	    }

        if ( $config->delay > ( $config->delay_byDate + 2 ) ) {
            // The user is attempting to view a day >2 days after today.
            $config->maxDelayFlag = CONST_MAXDAYS_FUTURE;
        }

        if ( $config->delay < ( $config->delay_byDate - 2 ) ) {

            // The user is attempting to view a day >2 days before today.
            $config->maxDelayFlag = CONST_MAXDAYS_PAST;
        }

        $config->prev = $config->delay - 1;
        $config->next = $config->delay + 1;

//        $config->articleId = $e20rArticle->findArticleByDelay( $config->delay );
//        dbg("e20rCheckinView::view_actionAndActivityCheckin() - ArticleID: {$articleId}");

	    dbg("e20rCheckin::dailyProgress() - Delay info: Now = {$config->delay}, 'tomorrow' = {$config->next}, 'yesterday' = {$config->prev}");

        $t = $e20rTracker->getDateFromDelay( $config->next );
        $config->tomorrow = date_i18n( 'l M. jS', strtotime( $t ));

        $y = $e20rTracker->getDateFromDelay( $config->prev );
        $config->yesterday = date_i18n( 'l M. jS', strtotime( $y ) );

	    $config->userId = $current_user->ID;
	    $config->complete = $this->hasCompletedLesson( $config->articleId, $currentArticle->post_id, $config->userId );

        if ( ( strtolower($config->type) == 'action' ) || ( strtolower($config->type) == 'activity' ) ) {

            dbg("e20rCheckin::dailyProgress() - Configured daily action check-ins for article ID(s):");
            dbg($config->articleId);

            if ( empty( $config->articleId ) ) {

                dbg("e20rCheckin::dailyProgress() -  No articleId specified. Searching...");
                $config->articleId = $e20rArticle->findArticleByDelay( $config->delay );

                if ( empty( $config->articleId ) ) {
                    dbg("e20rCheckin::dailyProgress() - No article found. Using default of " . CONST_NULL_ARTICLE );
                    $config->articleId = CONST_NULL_ARTICLE;
                }
            }

            if ( $config->articleId !== CONST_NULL_ARTICLE ) {

                dbg("e20rCheckin::dailyProgress() - Loading lesson & activity excerpts");

                $config->lessonExcerpt = $e20rArticle->getLessonExcerpt( $config->articleId );
	            // TODO: Load $config->activityExcerpt (first need to create Activity stuff)
	            // $config->activityExcerpt = $e20rActivity->getExcerpt( $config->articleId );
            }

            // Get the check-in id list for the specified article ID
            $checkinIds = $e20rArticle->getCheckins( $config->articleId );

            if ( empty( $checkinIds ) ) {

                dbg("e20rCheckin::dailyProgress() - No check-in ids stored for this user/article Id...");
                $config->post_date = $e20rTracker->getDateForPost( $config->delay );
                $checkinIds = $this->model->findActionByDate( $config->post_date , $config->programId );
            }

            dbg( "e20rCheckin::dailyProgress() - Checkin/article info loaded." );
            // dbg( $checkinIds );

            foreach ( $checkinIds as $id ) {

                $settings = $this->model->loadSettings( $id );

                switch ( $settings->checkin_type ) {

                    case $this->types['assignment']:

                        dbg( "e20rCheckin::dailyProgress() - Loading data for assignment check-in" );

                        // TODO: Load view for assignment check-in (including check for questions & answers to load).
                        $checkin = null;
                        break;

                    case $this->types['survey']:

                        dbg( "e20rCheckin::dailyProgress() - Loading data for survey check-in" );
                        // TODO: Load view for survey data (pick up survey(s) from Gravity Forms entry.
                        $checkin = null;
                        break;

                    case $this->types['action']:

                        dbg( "e20rCheckin::dailyProgress() - Loading data for daily action check-in" );

                        $checkin            = $this->model->loadUserCheckin( $config, $current_user->ID, $settings->checkin_type, $settings->short_name );
                        $checkin->actionList = $this->model->getActions( $id, $settings->checkin_type, - 3 );

                        break;

                    case $this->types['activity']:

                        dbg( "e20rCheckin::dailyProgress() - Loading data for daily activity check-in" );
                        $checkin = $this->model->loadUserCheckin( $config, $current_user->ID, $settings->checkin_type, $settings->short_name );
                        break;

                    case $this->types['note']:
                        // TODO: Decide.. Do we handler this in the action check-in?
                        dbg( "e20rCheckin::dailyProgress() - Loading data for daily activity note(s)" );
                        break;

                    default:

                        // Load action and acitvity view.
                        dbg( "e20rCheckin::dailyProgress() - No default action to load!" );
                        $checkin = null;

                }

                // Reset the value to true Y-m-d format
                $checkin->checkin_date                    = date( 'Y-m-d', strtotime( $checkin->checkin_date ) );
                $this->checkin[ $settings->checkin_type ] = $checkin;
            }

            dbg( "e20rCheckin::dailyProgress() - Loading checkin for user and delay {$config->delay}.." );
            dbg( $this->checkin );

            return $this->load_UserCheckin( $config, $this->checkin );
        }

        if ( strtolower($config->type) == 'assignment' ) {

            if ( $config->articleId === false ) {

                dbg("e20rCheckin::dailyProgress() - No article defined. Quitting.");
                return null;
            }

            dbg("e20rCheckin::dailyProgress() - Loading pre-existing data for the lesson/assignment ");

            $assignments = $e20rArticle->getAssignments( $config->articleId, $current_user->ID );

	        return $e20rAssignment->showAssignment( $assignments, $config );
        }

	    if ( strtolower( $config->type == 'show_assignment' ) ) {

		    dbg("e20rCheckin::dailyProgress[show_assignment]() - Loading Assignment list");
		    return $e20rAssignment->listUserAssignments( $config );
	    }
    }

	public function dailyProgress_callback() {

		check_ajax_referer('e20r-tracker-data', 'e20r-tracker-assignment-answer' );
		dbg("e20rCheckin::dailyProgress_callback() - Ajax calleee has the right privileges");

		global $currentArticle;
		global $e20rTracker;
		global $e20rProgram;
		global $e20rArticle;
		global $e20rAssignment;

		$descrId = null;
		$success = false;

		$articleId = ( isset( $_POST['e20r-article-id'] ) ? $e20rTracker->sanitize( $_POST['e20r-article-id'] ) : null );
		$userId = ( isset( $_POST['e20r-article-user_id'] ) ? $e20rTracker->sanitize( $_POST['e20r-article-user_id'] ) : null );
		$delay = ( isset( $_POST['e20r-article-release_day'] ) ? $e20rTracker->sanitize( $_POST['e20r-article-release_day'] ) : null );
		$answerDate = ( isset( $_POST['e20r-assignment-answer_date'] ) ? $e20rTracker->sanitize( $_POST['e20r-assignment-answer_date'] ) : null );
		$answerIds = ( isset( $_POST['e20r-assignment-id'] ) && is_array( $_POST['e20r-assignment-id'] ) ? $e20rTracker->sanitize( $_POST['e20r-assignment-id'] ) : array() );
		$questionIds = ( isset( $_POST['e20r-assignment-question_id'] ) && is_array( $_POST['e20r-assignment-question_id'] ) ? $e20rTracker->sanitize( $_POST['e20r-assignment-question_id'] ) : array() );
		$fieldTypes = ( isset( $_POST['e20r-assignment-field_type'] ) && is_array( $_POST['e20r-assignment-field_type'] ) ? $e20rTracker->sanitize( $_POST['e20r-assignment-field_type'] ) : array() );
		$answers = ( isset( $_POST['e20r-assignment-answer'] ) && is_array( $_POST['e20r-assignment-answer'] ) ? $e20rTracker->sanitize( $_POST['e20r-assignment-answer'] ) : array() );

		$programId = $e20rProgram->getProgramIdForUser( $userId, $articleId );

		if ( ! $articleId  && $userId && $answerDate && $delay ) {
			wp_send_json_error( "Unable to save your answer. Please contact technical support!" );
		}

		if ( count( $questionIds ) != count( $answers ) ) {
			dbg("e20rCheckin::dailyProgress_callback() - Mismatch for # of questions and # of answers provided/supplied. ");
			wp_send_json_error( "You didn't answer all of the questions we had for you. We're saving what we got.");
		}

		if ( ( count( $answerIds ) == 1 ) && ( $fieldTypes[0]) == 0 ) {

			dbg("e20rCheckin::dailyProgress_callback() - user clicked 'lesson complete' button.");
			$checkin = array(
				'article_id' => $articleId,
				'descr_id' => $descrId,
				'user_id' => $userId,
				'checkin_type' => CHECKIN_ASSIGNMENT,
				'program_id' => $programId,
				'checkin_date' => $e20rArticle->releaseDate( $articleId ),
				'checkedin_date' => $answerDate,
				'checkin_short_name' => 'daily_lesson',
				'checkedin' => true,
			);
		}
		else {

			// Build answer objects to save to database
			foreach ( $answerIds as $key => $id ) {

				if ( ! $descrId ) {
					$descrId = $id;
				}

				$checkin = array(
					'descr_id' => $descrId,
					'article_id' => $articleId,
					'user_id' => $userId,
					'checkin_type' => CHECKIN_ASSIGNMENT,
					'program_id' => $programId,
					'checkin_date' => $e20rArticle->releaseDate( $articleId ),
					'checkedin_date' => $answerDate,
					'checkin_short_name' => 'daily_lesson',
					'checkedin' => !empty( $answers[$key]),
				);

				dbg("e20rCheckin::dailyProgress_callback() - Saving answer(s) for assignment # {$id} ");

				$answer = array(
					'id' => $questionIds[$key],
					'article_id' => $articleId,
					'program_id' => $programId,
					'delay' => $delay,
					'question_id' => $id,
					'user_id' => $userId,
					'answer_date' => $answerDate,
					'answer' => $answers[$key],
					'field_type' => $e20rAssignment->getInputType( $fieldTypes[ $key ] ),
				);

				dbg('e20rCheckin::dailyProgress_callback() - Answer Provided: ');
				dbg($answer);

				dbg("e20rCheckin::dailyProgress_callback() - Saving answer to question # {$answer['question_id']}" );
				$success = ( $success || $e20rAssignment->saveAssignment( $answer ) );
			}
		}

		if ( ( $success !== false ) && ( !empty( $checkin ) ) ) {

			dbg( "e20rCheckin::dailyProgress_callback() - Saving checkin for date {$checkin['checkin_date']}" );
			$ok = $this->model->setCheckin( $checkin );

			if ( ! $ok ) {
				global $wpdb;
				dbg("e20rCheckin::dailyProgress_callback() - DB: " . $wpdb->last_error );
			}
		}

		if ( $success == true ) {
			wp_send_json_success();
		}
		else {
			wp_send_json_error( "Unable to save lesson data" );
		}
	}

	public function dailyCheckin_callback() {

		dbg($_POST);
		check_ajax_referer('e20r-tracker-data', 'e20r-tracker-assignment-answer' );
		dbg("e20rCheckin::dailyCheckin_callback() - Ajax callee has the right privileges");

	}

    public function nextCheckin_callback() {

        dbg("e20rCheckin::nextCheckin_callback() - Checking ajax referrer privileges");
        check_ajax_referer('e20r-checkin-data', 'e20r-checkin-nonce');

        dbg("e20rCheckin::nextCheckin_callback() - Checking ajax referrer has the right privileges");

        global $e20rArticle;
        global $e20rProgram;
        global $e20rTracker;

        global $current_user;
        global $post;

        $config = new stdClass();

        $config->type = 'action';
        $config->survey_id = null;
        $config->post_date = null;

        $config->userId = $current_user->ID;
        $config->startTS = $e20rProgram->startdate( $config->userId );
        $config->url = URL_TO_CHECKIN_FORM;

        $config->articleId = ( ! isset( $_POST['article-id'] ) ? $e20rArticle->init($post->ID) : intval($_POST['article-id']) );
        $config->programId = ( ! isset( $_POST['program-id'] ) ? $e20rProgram->getProgramIdForUser( $config->userId, $config->articleId ) : intval( $_POST['program-id'] ) );

        $config->delay = ( ! isset( $_POST['e20r-checkin-day'] ) ? $e20rTracker->getDelay( 'now' ) : intval( $_POST['e20r-checkin-day'] ) );
        $config->delay_byDate = $e20rTracker->daysBetween( $config->startTS, ( $config->startTS + ( $config->delay * ( 3600*24 ) ) ) );

        dbg("e20rCheckin::nextCheckin_callback() - Article: {$config->articleId}, Program: {$config->programId}, delay: {$config->delay}, start: {$config->startTS}, delay_byDate: {$config->delay_byDate}");

        if ( ( $html = $this->dailyProgress( $config ) ) !== false ) {

            dbg("e20rCheckin::nextCheckin() - Sending new dailyProgress data (html)");
            wp_send_json_success( $html );
        }

        wp_send_json_error();
    }

    public function shortcode_dailyProgress( $attributes = null ) {

        global $e20rArticle;
        global $e20rProgram;
        global $e20rTracker;

        global $current_user;
	    global $currentArticle;
        global $post;

	    if ( $current_user->ID == 0 ) {

		    dbg("e20rCheckin::shortcode_dailyProgress() - User Isn't logged in! Redirect immediately");
		    auth_redirect();
	    }

        $config = new stdClass();

        $config->type = 'action';
        $config->survey_id = null;
        $config->post_date = null;
        $config->maxDelayFlag = null;
        $config->url = URL_TO_CHECKIN_FORM;

        $config->userId = $current_user->ID;
	    $config->programId = $e20rProgram->getProgramIdForUser( $config->userId );
        $config->startTS = $e20rProgram->startdate( $config->userId );
        $config->delay = $e20rTracker->getDelay( 'now' );
        $config->delay_byDate = $config->delay;

        $tmp = shortcode_atts( array(
            'type' => 'action',
            'form_id' => null,
        ), $attributes );

	    foreach ( $tmp as $key => $val ) {

		    $config->{$key} = $val;
	    }

	    dbg( $config );

	    if ( $config->type == 'assignment' ) {

		    dbg("e20rCheckin::shortcode_dailyProgress() - Finding article info by post_id: {$post->ID}");
		    $article = $e20rArticle->findArticle( 'post_id', $post->ID );
	    }
	    elseif ( $config->type == 'action' ) {

		    dbg("e20rCheckin::shortcode_dailyProgress() - Finding article info by delay value of {$config->delay} days");
		    $article = $e20rArticle->findArticle( 'release_day', $config->delay );
	    }
	    elseif ( $config->type == 'show_assignments' ) {


	    }

	    /*
	    if ( empty( $article ) ) {

		    // TODO: Find article by closest delay.
		    // $article = $e20rArticle->findArticleNear( 'release_day', $config->delay, $config->programId, '<=' );
		    $article = $e20rArticle->emptyArticle();
	    }
		*/
	    dbg("e20rCheckin::shortcode_dailyProgress() - Article object:");
	    dbg( $article );

        $config->articleId = isset( $article->id ) ? $article->id : null;
        $config->programId = $e20rProgram->getProgramIdForUser( $config->userId, $config->articleId );

        ob_start();
        ?>
        <div id="e20r-daily-progress">
            <?php echo $this->dailyProgress( $config ); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function load_UserCheckin( $config, $checkinArr ) {

        $action = null;
        $activity = null;
        $assignment = null;
        $survey = null;
        $view = null;

        if ( ! empty($checkinArr) ) {

            dbg( "e20rCheckin::load_UserCheckin() - Array of checkin values isn't empty..." );
            dbg($checkinArr);

            foreach ( $checkinArr as $type => $c ) {

                dbg( "e20rCheckin::load_UserCheckin() - Loading view type {$type} for checkin" );

                if ( $type == CHECKIN_ACTION ) {

                    dbg( "e20rCheckin::load_UserCheckin() - Setting Action checkin data" );
                    $action = $c;

                    dbg( $action );
                }

                if ( $type == CHECKIN_ACTIVITY ) {

                    dbg( "e20rCheckin::load_UserCheckin() - Setting Activity checkin data" );
                    $activity = $c;

                    dbg( $activity );
                }

                if ( $type == CHECKIN_ASSIGNMENT ) {
                    $assignment = $c;
                }

                if ( $type == CHECKIN_SURVEY ) {
                    $survey = $c;
                }
                /*
							if ( $type == CHECKIN_NOTE ) {
								$note = $c;
							}
				*/
            }


            if ( ( ! empty( $action ) ) && ( ! empty( $activity ) ) ) {

                dbg( "e20rCheckin::load_UserCheckin() - Loading the view for the Actions & Activity check-in." );
                $view = $this->view->view_actionAndActivityCheckin( $config, $action, $activity, $action->actionList );
            }
        }
        else if ( ( $config->type == CHECKIN_ACTION ) || ( $config->type == CHECKIN_ACTIVITY ) ) {
            dbg("e20rCheckin::load_UserCheckin() - An activity or Action check-in requested...");
            $view = $this->view->view_actionAndActivityCheckin( $config, $action, $activity, $action->actionList );
        }

        return $view;
    }

    public function getPeers( $checkinId = null ) {

        if ( is_null( $checkinId ) ) {

            global $post;
            // Use the parent value for the current post to get all of its peers.
            $checkinId = $post->post_parent;
        }

        $checkins = new WP_Query( array(
            'post_type' => 'page',
            'post_parent' => $checkinId,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'fields' => 'ids',
        ) );

        $checkinList = array(
            'pages' => $checkins->posts,
        );

        foreach ( $checkinList->posts as $k => $v ) {

            if ( $v == get_the_ID() ) {

                if( isset( $checkins->posts[$k-1] ) ) {

                    $checkinList['prev'] = $checkins->posts[ $k - 1 ];
                }

                if( isset( $checkins->posts[$k+1] ) ) {

                    $checkinList['next'] = $checkins->posts[ $k + 1 ];
                }
            }
        }

        return $checkinList;
    }

    public function addMeta_Settings() {

        global $post;

        $def_status = array(
            'publish',
            'pending',
            'draft',
            'future',
            'private'
        );

        // Query to load all available programs (used with check-in definition)
        $query = array(
            'post_type'   => 'e20r_programs',
            'post_status' => apply_filters( 'e20r_tracker_checkin_status', $def_status ),
            'posts_per_page' => -1,
        );

        wp_reset_query();

        //  Fetch Programs
        $checkins = get_posts( $query );

        if ( empty( $checkins ) ) {

            dbg( "e20rCheckin::addMeta_Settings() - No programs found!" );
        }

        dbg("e20rCheckin::addMeta_Settings() - Loading settings metabox for checkin page {$post->ID}");
        $settings = $this->model->loadSettings( $post->ID );

        echo $this->view->viewSettingsBox( $settings , $checkins );

    }

    public function saveSettings( $post_id ) {

        $post = get_post( $post_id );

	    if ( isset( $post->post_type ) && ( $post->post_type == $this->model->get_slug() ) ) {

		    setup_postdata( $post );

		    $this->model->set( 'short_name', get_the_title( $post_id) );
		    $this->model->set( 'item_text', get_the_excerpt() );
	    }

	        parent::saveSettings( $post_id );
    }
}