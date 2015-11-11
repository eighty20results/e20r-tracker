<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rAssignmentModel extends e20rSettingsModel {

    private $settings;
	private $answerTypes;
	private $answerInputs;

    public function __construct()  {

	    global $e20rTables;

	    parent::__construct( 'assignments', 'e20r_assignments' );

	    $this->answerInputs = array(
	        0 => 'button',
		    1 => 'input',
		    2 => 'textbox',
//		    3 => 'checkbox',
		    4 => 'multichoice',
            5 => 'rank',
            6 => 'yesno',
	    );

	    $this->answerTypes = array(
		    0 => __("'Lesson complete' button", "e20rtracker"),
		    1 => __("Line of text (input)", "e20rtracker"),
		    2 => __("Paragraph of text (textbox)", "e20rtracker"),
//		    3 => __("Checkbox", "e20rtracker"),
		    4 => __("Multiple choice", "e20rtracker"),
            5 => __("1-10 ranking", "e20rtracker"),
            6 => __("Yes/No question", "e20rtracker"),
	    );

        $this->table = $e20rTables->getTable('assignments');
        $this->fields = $e20rTables->getFields('assignments');
    }

    public function getAnswerDescriptions() {

        return $this->answerTypes;
    }

	public function getInputType( $key ) {

		return $this->answerInputs[ $key ];
	}

	public function saveUserAssignment ( $aArray ) {

		global $wpdb;

		if ( ( $result = $this->exists( $aArray ) ) !== false ) {
			dbg("e20rAssignmentModel::saveUserAssignment() - found existing record: ");
			dbg($result->id);

			$aArray['id'] = $result->id;
		}

		$state = $wpdb->replace( $this->table, $aArray );

		dbg("e20rAssignmentModel::saveUserAssignment() - State: ({$state})");

		if ( ! $state ) {
			dbg("e20rAssignmentModel::saveUserAssignment() - Error: " .  $wpdb->last_error );
		}
		return ($state ? true : false );
	}

	public function exists( $assignment ) {

		global $wpdb;

//		dbg("e20rAssignmentModel::exists() -  Data: ");
//		dbg( $assignment );

		if ( ! is_array( $assignment ) ) {

			dbg("e20rAssignmentModel::exists() -  Incorrect data received!");
			return false;
		}

		$sql = $wpdb->prepare(
			"SELECT id, answer
                FROM {$this->table}
                WHERE (
                ( {$this->fields['user_id']} = %d ) AND
                ( {$this->fields['delay']} = %d ) AND
                ( {$this->fields['program_id']} = %d ) AND
                ( {$this->fields['article_id']} = %d ) AND
                ( {$this->fields['question_id']} = %d )
                )
           ",
			$assignment['user_id'],
			$assignment['delay'],
			$assignment['program_id'],
			$assignment['article_id'],
			$assignment['question_id']
		);

		$result = $wpdb->get_row( $sql );

		if ( ! empty( $result ) ) {

			dbg("e20rAssignmentModel::exists() - Got a result returned. ");
// 			dbg($result);

			return $result;
		}
		elseif ( $result === false ) {

			dbg("e20rAssignmentModel::exists() - Error: " . $wpdb->last_error );
		}

		return false;
	}

    public function createDefaultAssignment( $article, $title ) {

        $postDef = array(
            'post_title' => "Lesson complete for {$title}",
            'post_excerpt' => '',
            'post_type' => 'e20r_assignments',
            'post_status' => 'publish',
            'post_date' => date( 'Y-m-d H:i:s', current_time('timestamp') ),
            'comment_status' => 'closed',
            'ping_status' => 'closed',
        );

        $exists = get_page_by_title( "Lesson complete for {$title}" );

        if ( !empty( $exists ) ) {

            return false;
        }

        $assignment = $this->defaultSettings();
        $assignment->id = wp_insert_post( $postDef );

        if ( 0 != $assignment->id ) {

            $assignment->question_id = $assignment->id;
            $assignment->order_num = 1;
            $assignment->field_type = 0; // Lesson complete button
            $assignment->article_ids = array( $article->id );
            $assignment->delay = $article->release_day;

            if ( ! $this->saveSettings( $assignment ) ) {

                dbg("e20rAssignmentModel::createDefaultAssignment() - Error saving assignment settings ({$assignment->id}) for Article # {$article->id}");
                return false;
            }

            dbg("e20rAssignmentModel::createDefaultAssignment() - Saved new assignment Article # {$article->id}: {$assignment->id}");
            return $assignment->id; // Return the new assignment ID.
        }

        dbg("e20rAssignmentModel::createDefaultAssignment() - Error adding post (assignment) for article # {$article->id}");
        return false;
    }

    public function defaultSettings() {

        global $e20rTracker;

        $settings = parent::defaultSettings();

        $settings->id = null;
        $settings->descr = null;
        $settings->order_num = 1;
        $settings->question = null;
        $settings->question_id = null;
        $settings->delay = null;
        $settings->field_type = 0;
        // $settings->user_id = $current_user->ID;
        $settings->article_ids = array();
	    $settings->program_ids = array();
        $settings->answer_date = null;
        $settings->answer = null;
        $settings->select_options = array();

        return $settings;
    }

	public function loadAllUserAssignments( $userId ) {

		global $e20rTracker;
		global $e20rProgram;

        global $currentProgram;

		$delay = $e20rTracker->getDelay( 'now', $userId );
		$programId = $e20rProgram->getProgramIdForUser( $userId );

        dbg("e20rAssignmentModel::loadAllUserAssignments() - Loading assignments for user {$userId} until day {$delay} for program {$programId}");


		$assignments = $this->loadAssignmentByMeta( $programId, 'delay', $delay, '<=', 'numeric', 'delay' );
		dbg("e20rAssignmentModel::loadAllUserAssignments() - Returned " . count( $assignments ) . " to process ");
        // dbg($assignments);

		if ( empty( $assignments ) ) {

			dbg("e20rAssignmentModel::loadAllUserAssignments() - No records found.");
			return false;
		}

		$answers = array();

		foreach ( $assignments as $assignment ) {

            if ( !empty( $assignment->article_ids) && ( $key = array_search( 0, $assignment->article_ids ) ) !== false ) {

                dbg("e20rAssignmentModel::loadAllUserAssignments() - Assignment has a 0 for an article_ids value. Removing that value.");
                unset($assignment->article_ids[$key]);
            }

            // Process this assignment if it's NOT a "I've read it" button.
            if ( 0 != $assignment->field_type ) {

                dbg("e20rAssigmentModel::loadAllUserAssignments() - Assignment information being processed:");
                // dbg($assignment);

                if ( count( $assignment->article_ids) < 1 ) {

                    dbg("e20rAssignmentModel::loadAssUserAssignments() - ERROR: No user assignments defined for {$assignment->question_id}!");
                    $assignment->article_ids = array();

                }

                foreach( $assignment->article_ids as $userAId ) {

                    $userInfo = $this->loadUserAssignment( $userAId, $userId, $assignment->delay, $assignment->id);

                    foreach( $userInfo as $k => $data ) {

                        $assignment->answer = isset( $data->answer ) ? $data->answer : null;
                        $assignment->answer_date = isset($data->answer_date) ? $data->answer_date : null;
                        $assignment->article_id = $userAId;
                        $answers[] = $assignment;
                    }
                }
            }
		}

        // Sort the answers by the delay value, then the order_num value
        $answers = $e20rTracker->sortByFields( $answers, array( 'delay', 'order_num' ) );

        dbg("e20rAssignmentModel::loadAllUserAssignments() - Returning sorted array of answers: ");
        // dbg($answers);

        return $answers;
	}

    public function getArticleAssignments( $articleId ) {

        global $current_user;
        global $e20rProgram;

        $assignments = array();

        dbg("e20rAssignmentModel::getArticleAssignments() - for article #: {$articleId}");

        $args = array(
            'posts_per_page' => -1,
            'post_type' => 'e20r_assignments',
            'post_status' => 'publish',
            'meta_key' => '_e20r-assignments-order_num',
            'order_by' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => '_e20r-assignments-article_ids',
                    'value' => $articleId,
                    'compare' => '=',
                    'type' => 'numeric',
                ),
            )
        );

        $query = new WP_Query( $args );

        dbg("e20rAssignmentModel::getArticleAssignments() - Returned assignments: {$query->post_count}" );

        while ( $query->have_posts() ) {

            $query->the_post();

            $new = new stdClass();

			$id = get_the_ID();

            $new = $this->loadSettings( $id );
            // $new->id = $id;

            $new->descr = $query->post->post_excerpt;
            $new->question = $query->post->post_title;
            // $new->article_id = $articleId;
            $assignments[] = $new;
        }

        wp_reset_postdata();

        return $assignments;
    }

	private function loadAssignmentByMeta( $programId, $key, $value, $comp = '=', $type = 'numeric', $orderbyKey = 'order_num' ) {

		global $current_user;
		global $e20rProgram;
        global $e20rArticle;
		global $e20rTracker;

		$assignments = array();

		dbg("e20rAssignmentModel::loadAssignmentByMeta() - for program #: {$programId}");

		$args = array(
			'posts_per_page' => -1,
			'post_type' => $this->cpt_slug,
			'post_status' => 'publish',
			'meta_key' => "_e20r-{$this->type}-{$orderbyKey}",
			'order_by' => 'meta_key',
			'order' => 'ASC',
			'meta_query' => array(
				array(
					'key' => "_e20r-{$this->type}-{$key}",
					'value' => $value,
					'compare' => $comp,
					'type' => $type,
				),
				array(
					'key' => "_e20r-{$this->type}-program_ids",
					'value' => $programId,
					'compare' => '=',
					'type' => 'numeric',
				),
			)
		);

        if ( 'field_type' != $key ) {

            $args['meta_query'][] = array(
                'key' => "_e20r-{$this->type}-field_type",
                'value' => 0,
                'compare' => '!=',
                'type' => 'numeric'
            );
        }
        dbg("e20rAssignmentModel::loadAssignmentByMeta() - Using:" );
        dbg( $args );

		$query = new WP_Query( $args );

		dbg("e20rAssignmentModel::loadAssignmentByMeta() - Returned {$query->post_count} {$this->cpt_slug} records" );

		while ( $query->have_posts() ) {

			$query->the_post();

			// $new = new stdClass();
            $assignment_id = get_the_ID();

			$new = $this->loadSettings( $assignment_id );

            if ( empty( $new->article_ids) || !isset( $new->article_ids ) || in_array( 0, $new->article_ids ) ) {

                dbg("e20rAssignmentModel::loadAssignmentByMeta() - Loading articles which have had {$assignment_id} assigned to it");
                $tmp = $e20rArticle->findArticles('assignment_ids', $assignment_id, 'numeric', $programId);
                $article_list = array();

                foreach ($tmp as $art) {

                    if ( !empty( $art->id ) && ( 0 != $art->id ) ) {
                        dbg("e20rAssignmentModel::loadAssignmentByMeta() - Article definitions received: ");
                        //dbg($tmp);
                        $article_list[] = $art->id;
                    }
/*
                    if ( !empty( $art->id ) && ( 0 != $art->id ) ) {
                        $article_list[] = $art->id;
                    }
*/
                }

                $new->article_ids = $article_list;
            }

			$new->id = $assignment_id;
			$new->descr = $query->post->post_excerpt;
			$new->question = $query->post->post_title;
			// $new->{$key} = $value;

            $assignments[] = $new;
            /*
            if ( empty( $new->program_ids ) || in_array( $programId, $new->program_ids ) ) {
                $assignments[] = $new;
            }
            */
		}

		dbg("e20rAssignmentModel::loadAssignmentByMeta() - Returning " .
		    count( $assignments ) . " records to: " . $e20rTracker->whoCalledMe() );

        wp_reset_postdata();

		return $assignments;
	}

    public function loadAllAssignments() {

        global $post;

	    $assignments = parent::loadAllSettings( 'publish' );

	    if ( empty( $assignments ) ) {

		    $assignments[0] = $this->defaultSettings();
	    }

        $savePost = $post;

        foreach( $assignments as $id => $obj ) {

            $post = get_post( $id );
            setup_postdata( $post );

            $obj->descr = $post->post_excerpt;
            $obj->question = $post->post_title;
            $obj->id = $id;

            wp_reset_postdata();

            $assignments[$id] = $obj;
        }

        $post = $savePost;

        return $assignments;
    }

    public function loadUserAssignment( $articleId, $userId, $delay = null, $assignmentId = null ) {

        // TODO: Load the recorded user assignment answers by assignment ID.
        global $wpdb;
        global $post;
        global $e20rProgram;
        global $e20rTracker;
	    global $currentAssignment;

        $records = array();

        // Don't clobber the user responses
        $resp = array(
            $this->fields['id'],
            $this->fields['answer_date'],
            $this->fields['answer'],
            $this->fields['user_id'],
            $this->fields['article_id'],
        );

        // Preserve
        $save_post = $post;

        if ( is_null( $userId ) ) {

            $result = array();
        }
        else {

            $programId = $e20rProgram->getProgramIdForUser($userId);

            dbg("e20rAssignmentModel::loadUserAssignment() - date for article # {$articleId} in program {$programId} for user {$userId}: {$delay}");

            $sql =  "SELECT {$this->fields['id']},
                            {$this->fields['answer_date']},
                            {$this->fields['answer']},
                            {$this->fields['user_id']},
                            {$this->fields['question_id']},
                            {$this->fields['article_id']}
                     FROM {$this->table} AS a
                     WHERE ( ( a.{$this->fields['user_id']} = %d ) AND
                      ( a.{$this->fields['question_id']} = %d ) AND
                      ( a.{$this->fields['program_id']} = %d ) AND
                    " . (!is_null($delay) ? "( a.{$this->fields['delay']} = " . intval($delay) . " ) ) " : null) .
                    " ORDER BY a.{$this->fields['id']}";

            // $sql = $e20rTracker->prepare_in( $sql, $articleIds, '%d' );

            $sql = $wpdb->prepare( $sql,
                $userId,
                $assignmentId,
                $programId
//                $articleId
            );

            // dbg("e20rAssignmentModel::loadUserAssignment() - SQL: {$sql}");

            $result = $wpdb->get_results($sql);
        }

        dbg("e20rAssignmentModel::loadUserAssignment() - Loaded " . count($result) . " check-in records");

        if ( ! empty($result) ) {

            // Index the result array by the ID of the assignment (key)
            foreach( $result as $key => $data ) {

                $recordId = $data->id;

                dbg("e20rAssignmentModel::loadUserAssignment() - Loading config first for assignment #{$data->question_id} on behalf of record ID {$data->id}");
	            $assignment = $this->loadSettings( $data->question_id );

	            foreach ( $assignment as $k => $val ) {

		            if ( ! in_array( $k, $resp ) ) {

			            if ( 'article_ids' == $k ) {

                            $data->{$k}[] = $val;
                        }
                        else {
                            $data->{$k} = $val;
                        }

                        // Special handling of field_type == 4
                        if ( ( 'field_type' == $k ) && ( 4 == $val ) ) {

                            dbg("e20rAssignmentModel::loadUserAssignment() - Found a multi-choice answer. Restoring it as an array.");
                            $data->answer = json_decode( stripslashes( $data->answer ) );

                        }
		            }
	            }
                dbg("e20rAssignmentModel::loadUserAssignment()- Loading record ID {$data->id} from database result: {$key}");
	            $records[(count($result) - 1)] = $data;

	            $post = get_post( $data->question_id );

                setup_postdata( $post );

                $records[(count($result) - 1)]->id = $data->id;
                $records[(count($result) - 1)]->descr = $post->post_excerpt;
                $records[(count($result) - 1)]->question = $post->post_title;
                $records[(count($result) - 1)]->question_id = $assignment->question_id;
                $records[(count($result) - 1)]->article_ids = $assignment->article_ids;

                unset($result[$key]);

                // Array is now indexed by record/post/assignment ID
                wp_reset_postdata();
            }
        }
        else {
	        dbg("e20rAssignmentModel::loadUserAssignment() - No user data returned. {$wpdb->last_error}");
            // dbg("e20rAssignmentModel::loadUserAssignment() - No user data returned.");

	        if (  CONST_DEFAULT_ASSIGNMENT != $assignmentId ) {

                dbg("e20rAssignmentModel::loadUserAssignment() - Loading settings ({$assignmentId})");
                $assignment = $this->loadSettings( $assignmentId );
	        }

            if ( empty( $assignment->question_id ) ) {
                dbg("e20rAssignmentModel::loadUserAssignment() - Loading default settings");
                $assignment = $this->defaultSettings();
            }

            unset($assignment->id);
            $records = array( 0 => $assignment );

        }

        // Restore
        $post = $save_post;

        return $records;
    }

    public function loadSettings( $id = null ) {

        global $post;
	    global $currentAssignment;

	    if ( isset( $currentAssignment->id ) && ( $currentAssignment->id == $id ) ) {

		    return $currentAssignment;
	    }

	    if ( 0 == $id ) {

		    $this->settings              = $this->defaultSettings( $id );
            $this->settings->question    = "Lesson complete (default)";
		    // $this->settings->id          = $id;
	    } else {

		    $savePost = $post;

            dbg("e20rAssignmentModel::loadSettings() - Loading settings for {$id}");

		    $this->settings = parent::loadSettings( $id );

		    $post = get_post( $id );
		    setup_postdata( $post );

		    if ( ! empty( $post->post_title ) ) {

			    $this->settings->descr       = $post->post_excerpt;
			    $this->settings->question    = $post->post_title;
			    $this->settings->id          = $post->ID;
		    }

            if ( ! is_array( $this->settings->program_ids ) ) {
                $this->settings->program_ids = array( $this->settings->program_ids );
            }

            if ( ! is_array( $this->settings->article_ids ) ) {
                $this->settings->article_ids = array( $this->settings->article_ids );
            }

            if ( !is_array( $this->settings->select_options ) ) {
                $this->settings->select_options = array( $this->settings->select_options );
            }

		    wp_reset_postdata();
		    $post = $savePost;
	    }

        if ( empty( $this->settings->field_type ) ) {
            $this->settings->field_type = 0;
        }

        $currentAssignment = $this->settings;
        return $currentAssignment;
    }
/*
    public function setAssignment( $assignment ) {

        global $wpdb;

        if ( ( $result = $this->exists( $assignment ) ) !== false ) {
            dbg("e20rAssignmentModel::setAssignment() - found existing record: ");
            dbg($result->id);

            $assignment['id'] = $result->id;
        }

        dbg("e20rAssignmentModel::setAssignment() - Assignment record:");
        dbg($assignment);

        return ( $wpdb->replace( $this->table, $assignment ) ? true : false );
    }
*/
    /**
     * Save the Assignment Settings to the metadata table.
     *
     * @param $settings - Array of settings for the specific assignment.
     *
     * @return bool - True if successful at updating assignment settings
     */
    public function saveSettings( $settings ) {

        $assignmentId = $settings->id;

        $defaults = $this->defaultSettings();

        dbg("e20rAssignmentModel::saveSettings() - Saving assignment Metadata: " . print_r( $settings, true ) );

        $error = false;

        foreach ( $defaults as $key => $value ) {

            if ( in_array( $key, array( 'id', 'descr', 'question', 'program_id' ) ) ) {
                continue;
            }

            if ( false === $this->settings( $assignmentId, 'update', $key, $settings->{$key} ) ) {

                dbg( "e20rAssignment::saveSettings() - ERROR saving {$key} setting ({$settings->{$key}}) for check-in definition with ID: {$assignmentId}" );

                $error = true;
            }
        }

        return ( !$error ) ;
    }

}