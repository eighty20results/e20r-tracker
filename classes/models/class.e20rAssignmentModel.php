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

	private static $instance = null;

    public function __construct()  {

	    $e20rTables = e20rTables::getInstance();

	    parent::__construct( 'assignments', 'e20r_assignments' );

	    $this->answerInputs = array(
	        0 => 'button',
		    1 => 'input',
		    2 => 'textbox',
//		    3 => 'checkbox',
		    4 => 'multichoice',
            5 => 'rank',
            6 => 'yesno',
            7 => 'html',
	    );

	    $this->answerTypes = array(
		    0 => __("'Lesson complete' button", "e20r-tracker"),
		    1 => __("Line of text (input)", "e20r-tracker"),
		    2 => __("Paragraph of text (textbox)", "e20r-tracker"),
//		    3 => __("Checkbox", "e20r-tracker"),
		    4 => __("Multiple choice", "e20r-tracker"),
            5 => __("1-10 ranking", "e20r-tracker"),
            6 => __("Yes/No question", "e20r-tracker"),
            7 => __("HTML/Text Field", "e20r-tracker"),
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

        $e20rTracker = e20rTracker::getInstance();

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
        $settings->messages = array();
        $settings->new_messages = false;
        $settings->thread_is_archived = false;

        return $settings;
    }

    public function get_assignment_question( $assignment_id ) {

        $text = get_the_title( $assignment_id );
        wp_reset_postdata();

        return $text;
    }

	public function loadAllUserAssignments( $userId, $page_num = -1 ) {

		$e20rTracker = e20rTracker::getInstance();
		$e20rProgram = e20rProgram::getInstance();

        global $currentProgram;

		$delay = $e20rTracker->getDelay( 'now', $userId );
		$programId = $e20rProgram->getProgramIdForUser( $userId );

        dbg("e20rAssignmentModel::loadAllUserAssignments() - Loading assignments for user {$userId} until day {$delay} for program {$programId}");


		$assignments = $this->loadAssignmentByMeta( $programId, 'delay', $delay, '<=', 'numeric', 'delay', 'DESC', $page_num );

		dbg("e20rAssignmentModel::loadAllUserAssignments() - Returned " . count( $assignments ) . " to process ");
        // dbg($assignments);

		if ( empty( $assignments ) ) {

			dbg("e20rAssignmentModel::loadAllUserAssignments() - No records found.");
			return false;
		}

		$answers = array();

		// Transfer config settings...
		if ( isset( $assignments['max_num_pages'] ) ) {
			$answers['max_num_pages'] = $assignments['max_num_pages'];
			unset( $assignments['max_num_pages']);
		}

		if ( isset( $assignments['current_page'] ) ) {
			$answers['current_page'] = $assignments['current_page'];
			unset( $assignments['current_page']);
		}

		foreach ( $assignments as $assignment ) {

            if ( !empty( $assignment->article_ids) && ( $key = array_search( 0, $assignment->article_ids ) ) !== false ) {

                dbg("e20rAssignmentModel::loadAllUserAssignments() - Assignment has a 0 for an article_ids value. Removing that value.");
                unset($assignment->article_ids[$key]);
            }

            // Process this assignment if it's NOT a "I've read it" button.
            if ( isset($assignment->field_type) && 0 != $assignment->field_type ) {

                dbg("e20rAssigmentModel::loadAllUserAssignments() - Assignment information being processed:");
                // dbg($assignment);

                if ( count( $assignment->article_ids) < 1 ) {

                    dbg("e20rAssignmentModel::loadAllUserAssignments() - ERROR: No user assignments defined for {$assignment->question_id}!");
                    $assignment->article_ids = array();

                }
                // TODO: Unroll loop to simplify fetching assignment repsponses for the user
                foreach( $assignment->article_ids as $userAId ) {

                    // $userInfo = $this->loadUserAssignment( $userAId, $userId, $assignment->delay, $assignment->id);
                    $userInfo = $this->load_user_assignment_info( $userId, $assignment->id, $userAId );
                    dbg("e20rAssignmentModel::loadAllUserAssignments() - Returned assignment info for user: {$userId} and assignment {$assignment->id}");
                    // dbg($userInfo);

                    foreach( $userInfo as $k => $data ) {

                        $assignment->id = isset($data->id) ? $data->id : null;
                        $assignment->answer = isset( $data->answer ) ? $data->answer : null;
                        $assignment->answer_date = isset($data->answer_date) ? $data->answer_date : null;
                        $assignment->article_id = $userAId;
                        $assignment->messages = isset($data->messages) ? $data->messages : null;
                        $assignment->new_messages = isset($data->new_messages) ? $data->new_messages : false;
                        $assignment->thread_is_archived = isset($data->thread_is_archived) ? $data->thread_is_archived : false ;
                        $answers[] = $assignment;
                    }
                }
            }
		}

        // Sort the answers by the delay value, then the order_num value
        // $answers = $e20rTracker->sortByFields( $answers, array( 'delay', 'order_num' ) );

        dbg("e20rAssignmentModel::loadAllUserAssignments() - Returning sorted array of answers: ");
        // dbg($answers);

        return $answers;
	}

    public function getArticleAssignments( $articleId ) {

        global $current_user;
        $e20rProgram = e20rProgram::getInstance();

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

	/**
	 * Query and paginate the Assignment data for a program / user
	 *
	 * @param $programId
	 * @param $key
	 * @param $value
	 * @param string $comp
	 * @param string $type
	 * @param string $orderbyKey
	 * @param string $order
	 *
	 * @return array    - Array of assignments + the max number of pages (for pagination) in the `max_num_pages` array key
	 */
	private function loadAssignmentByMeta( $programId, $key, $value, $comp = '=', $type = 'numeric', $orderbyKey = 'order_num', $order = 'ASC', $page_num = -1 ) {

		global $current_user;
		$e20rProgram = e20rProgram::getInstance();
        $e20rArticle = e20rArticle::getInstance();
		$e20rTracker = e20rTracker::getInstance();

		$assignments = array();

		dbg("e20rAssignmentModel::loadAssignmentByMeta() - for program #: {$programId}");

		$items = apply_filters( 'e20r-tracker-items-per-page', 20 );

		$page = 1;

		if ( -1 === $page_num ) {
			$pg = get_query_var( 'paged' );

			if ( !empty($pg) || 1 == $pg ) {
				$page = get_query_var( 'paged' );
			}
		} else {
			dbg("Received Page number request {$page_num}");
			$page = $page_num;
		}

		$args = array(
			'posts_per_page' => ( empty( $items ) ? -1 : $items ),
			'paged' => $page,
			'post_type' => $this->cpt_slug,
			'post_status' => 'publish',
			'meta_key' => "_e20r-{$this->type}-{$orderbyKey}",
			'order_by' => 'meta_key',
			'order' => $order,
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

		$assignments['max_num_pages'] = $query->max_num_pages;
		$assignments['current_page'] = $page;

		dbg("e20rAssignmentModel::loadAssignmentByMeta() - Returned {$query->post_count} {$this->cpt_slug} records. Page # {$page}" );

		while ( $query->have_posts() ) {

			$query->the_post();

			// $new = new stdClass();
            $assignment_id = get_the_ID();

			$new = $this->loadSettings( $assignment_id );

            if ( empty( $new->article_ids) || !isset( $new->article_ids ) || in_array( 0, $new->article_ids ) ) {

                dbg("e20rAssignmentModel::loadAssignmentByMeta() - Loading articles which have had {$assignment_id} assigned to it");
                $tmp = $e20rArticle->findArticles('assignment_ids', $assignment_id, $programId);
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

            $new->id = !empty( $assignment_id ) ? $assignment_id : null;
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

	    $assignments = parent::loadAllSettings( 'publish', 'asc' );

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

    public function load_user_assignment_info( $user_id, $assignment_id, $article_id = null ) {

        global $wpdb;
        global $post;

        global $currentProgram;

        $e20rProgram = e20rProgram::getInstance();
        $e20rTables = e20rTables::getInstance();

        dbg("e20rAssignmentModel::load_user_assignment_info() - Attempting to locate info for assignment {$assignment_id}, user {$user_id} and article {$article_id}");

        $save_data = array(
            $this->fields['id'],
            $this->fields['answer_date'],
            $this->fields['answer'],
            $this->fields['user_id'],
            $this->fields['article_id'],
        );

        $records = array();
        $record_count = 0;

/*        $r_table = $e20rTables->getTable('response');
        $r_fields = $e20rTables->getFields('response');
*/
        $save_post = $post;

        if ( empty( $currentProgram->id ) || ( -1 == $currentProgram->id ) ) {
            $program_id = $e20rProgram->getProgramIdForUser($user_id);
        } else {
        	$program_id = $currentProgram->id;
        }

        dbg("e20rAssignmentModel::load_user_assignment_info() - Loading data for article # {$article_id} in program {$program_id} for user {$user_id}");

        $assignment_sql =  "SELECT a.{$this->fields['id']} AS id,
                            a.{$this->fields['answer_date']} AS answer_date,
                            a.{$this->fields['answer']} AS answer,
                            a.{$this->fields['user_id']} AS recipient_id,
                            a.{$this->fields['question_id']} AS question_id,
                            a.{$this->fields['article_id']} AS article_id
                     FROM {$this->table} AS a
                     WHERE ( ( a.{$this->fields['user_id']} = %d ) AND
                      ( a.{$this->fields['question_id']} = %d ) AND
                      ( a.{$this->fields['program_id']} = %d ) )
                  ORDER BY a.{$this->fields['delay']}";

        $assignment_sql = $wpdb->prepare( $assignment_sql, $user_id, $assignment_id, $program_id );

        $assignments = $wpdb->get_results( $assignment_sql );

        if (empty( $assignments) ) {

            dbg("e20rAssignmentModel::load_user_assignment_info() - No records found for {$assignment_id} and user {$user_id}");
            dbg("e20rAssignmentModel::load_user_assignment_info() - Error: " . ( empty( $wpdb->last_error ) ? 'N/A' : $wpdb->last_error ));

            if (  CONST_DEFAULT_ASSIGNMENT != $assignment_id ) {

                dbg("e20rAssignmentModel::load_user_assignment_info() - Loading regular settings ({$assignment_id})");
                $assignment = $this->loadSettings( $assignment_id );
            }

            if ( !isset( $assignment->question_id ) ) {
                dbg("e20rAssignmentModel::load_user_assignment_info() - Loading default settings");
                $assignment = $this->defaultSettings();
            }

            unset($assignment->id);
            return array( 0 => $assignment );
        }

        dbg("e20rAssignmentModel::load_user_assignment_info() - Found " . count( $assignments ) . " records for {$assignment_id} and user {$user_id}");
        foreach( $assignments as $key => $r ) {

            // dbg("e20rAssignmentModel::load_user_assignment_info() - Used SQL: {$assignment_sql}");

            $assignment = $this->loadSettings( $r->question_id );

            if ( !empty( $assignment->article_ids ) && !in_array( $article_id, $assignment->article_ids ) ) {
                dbg("e20rAssignmentModel::load_user_assignment_info() - WARNING: Assignment {$r->question_id} is NOT included in article {$article_id}. Skipping it...");
                dbg(  $assignment->article_ids );
                continue;
            }

            foreach ( $assignment as $k => $val ) {

                if ( !in_array( $k, $save_data ) ) {

                    $r->{$k} = $val;

                    // Special handling of field_type == 4
                    if ( ( 'field_type' == $k ) && ( 4 == $val ) ) {

                        dbg("e20rAssignmentModel::load_user_assignment_info() - Found a multi-choice answer. Restoring it as an array.");
                        $r->answer = json_decode( stripslashes( $r->answer ) );
                    }
                }
            }

            dbg("e20rAssignmentModel::load_user_assignment_info()- Loading record ID {$r->id} from database result: {$key}");
            $records[$record_count] = $r;

            $post = get_post( $r->question_id );

            setup_postdata( $post );

            $records[$record_count]->id = $r->id;
            $records[$record_count]->descr = $post->post_excerpt;
            $records[$record_count]->question = $post->post_title;
            $records[$record_count]->question_id = $assignment->question_id;
            $records[$record_count]->article_ids = $assignment->article_ids;
            $records[$record_count]->messages = $this->get_history( $assignment->question_id, $currentProgram->id, $article_id, $user_id );
            $records[$record_count]->new_messages = $this->has_unread_messages( $records[$record_count]->messages );
            $records[$record_count]->thread_is_archived = $this->thread_is_archived( $records[$record_count]->messages );

            if ( !empty($records[$record_count]->article_ids) && !in_array( $article_id, $records[$record_count]->article_ids ) ) {
                dbg("e20rAssignmentModel::load_user_assignment_info() - Assignment is NOT included in article {$article_id}}. Skipping it");
                unset( $records[$record_count]);
                continue;
            }

            $record_count++;
            // unset($assignments[$key]);

            wp_reset_postdata();
        }

        $post = $save_post;

        return $records;
    }

    public function loadUserAssignment( $articleId, $userId, $delay = null, $assignmentId = null ) {

        // TODO: Load the recorded user assignment answers by assignment ID.
        global $wpdb;
        global $post;
        $e20rProgram = e20rProgram::getInstance();
        $e20rTracker = e20rTracker::getInstance();
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
                            {$this->fields['article_id']},
                            {$this->fields['response_id']}
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
        $record_count = 0;

        if ( ! empty($result) ) {

            // Index the result array by the ID of the assignment (key)
            foreach( $result as $key => $data ) {

                $recordId = $data->id;

                dbg("e20rAssignmentModel::loadUserAssignment() - Loading config first for assignment #{$data->question_id} on behalf of record ID {$data->id}");
	            $assignment = $this->loadSettings( $data->question_id );

	            foreach ( $assignment as $k => $val ) {

		            if ( ! in_array( $k, $resp ) ) {

			            if ( 'article_ids' == $k )  {

                            if ( !empty( $val )) {

                                $data->{$k}[] = $val;

                                dbg( "{$k} => " . print_r( $val, true) );

                                $data->new_messages[$val] = $this->has_unread_messages( $data->question_id, $data->program_id, $val, $userId );
                                dbg("e20rAssignmentModel::loadUserAssignment()- Loading article {$val} based message history for user {$userId} regarding {$data->question_id}");
                                $data->message_history[$val] = $this->get_history( $assignment->question_id, $programId, $val, $userId );

                            } else {
                                $data->{$k} = array();

                            }

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
	            $records[$record_count] = $data;

	            $post = get_post( $data->question_id );

                setup_postdata( $post );

                $records[$record_count]->id = $data->id;
                $records[$record_count]->descr = $post->post_excerpt;
                $records[$record_count]->question = $post->post_title;
                $records[$record_count]->question_id = $assignment->question_id;
                $records[$record_count]->article_ids = $assignment->article_ids;
                $records[$record_count]->message_history = $data->message_history;
                $records[$record_count]->new_messages = $data->new_messages;
                // $records[$record_count]->message_history = array();

/*                foreach( $assignment->article_ids as $a ) {

                    if ( !empty( $a ) ) {

                        $records[$record_count]->new_messages += $this->has_unread_message( $data->question_id, $data->program_id, $a, $userId );
                        dbg("e20rAssignmentModel::loadUserAssignment()- Loading article {$a} based message history for user {$userId} regarding {$data->question_id}");
                        $records[$record_count]->message_history[$a] = $this->get_history( $assignment->question_id, $programId, $a, $userId );
                    }
                }
*/
                unset($result[$key]);

                $record_count++;

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

            if ( !isset( $assignment->question_id ) ) {
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

    public function update_reply_status( $message_id, $status, $status_field ) {

        global $wpdb;
        $e20rTables = e20rTables::getInstance();

        $table = $e20rTables->getTable('response');
        $fields = $e20rTables->getFields('response');

        $status = sprintf( '%d', $status );

        $data = array( $status_field => $status );

        if ( $status_field == $fields['archived']) {

            $data = array(
                $status_field => $status,
                "{$fields['message_read']}" => 1
            );
        }


        dbg("e20rAssignmentModel::update_reply_status() - Setting {$status_field} to: {$status} for message ID {$message_id}");

        $result = $wpdb->update(
                        $table,
                        $data,
                        array( "{$fields['id']}" => $message_id ),
                        array( '%d' ),
                        array( '%d' )
                    );

        if ( false === $result ) {
            dbg("e20rAssignmnentModel::update_reply_status() - ERROR: Unable to update status to {$status} for record # {$message_id} in {$table}: {$wpdb->last_error}");
            return false;
        }

        return true;
    }

    public function user_has_new_messages( $client_id ) {

        $e20rTables = e20rTables::getInstance();

        global $currentProgram;

        global $current_user;
        global $wpdb;

        $fields = $e20rTables->getFields('response');
        $table = $e20rTables->getTable('response');

        $sql = "SELECT COUNT({$fields['id']}) AS unread_messages FROM {$table} WHERE (
                  ({$fields['program_id']} = %d) AND
                  ({$fields['recipient_id']} = %d) AND
                  ({$fields['client_id']} = %d) AND
                  ({$fields['sent_by_id']} <> %d) AND
                  ({$fields['message_read']} = 0 AND {$fields['archived']} = 0)
                )";

        $sql = $wpdb->prepare( $sql, $currentProgram->id, $client_id, $client_id, $current_user->ID );

        $unread_messages = $wpdb->get_var( $sql );

        dbg("e20rAssignmentModel::user_has_new_messages() - Has {$unread_messages} new/unread messages");
        if (is_null( $unread_messages) ) {
            return false;
        }

        return $unread_messages;
    }

    private function has_unread_messages( $messages ) {

        global $current_user;

        dbg("e20rAssignmentModel::has_unread_message() - Counting unread messages...");
        foreach( $messages as $message ) {

            dbg("e20rAssignmentModel::has_unread_message() - Checking if message # {$message->response_id} is read: {$message->read_status}");

            if ( ( 0 == $message->read_status  ) && ( $current_user->ID != $message->message_sender_id ) && ( 0 == $message->archived ) && ($current_user->ID == $message->recipient_id)) {

                dbg("e20rAssignmentModel::has_unread_message() - Found unread messages");
                return true;
            }
        }

        dbg("e20rAssignmentModel::has_unread_message() - Found no unread messages");
        return false;
    }

    private function thread_is_archived( $messages ) {

        global $current_user;

        foreach( $messages as $message ) {

            if (( 1 == $message->archived ) && ( $current_user->ID == $message->recipient_id ) ) {
                return true;
            }
        }

        return false;
    }

    public function get_history( $assignment_id, $program_id, $article_id, $user_id ) {

        global $wpdb;
        global $current_user;

        $e20rTables = e20rTables::getInstance();
        $e20rTracker = e20rTracker::getInstance();

        $r_table = $e20rTables->getTable('response');
        $r_fields = $e20rTables->getFields('response');

        $user_field = $r_fields['recipient_id'];

        if ( $e20rTracker->is_a_coach($current_user->ID) ) {

            dbg("e20rAssignmentModel::get_history() - Setting the user_field to the coach's value");
            $user_field = $r_fields['client_id'];
        }

        dbg("e20rAssignmentModel::get_history() - Loading message history for {$user_id}, assignment: {$assignment_id} in program: {$program_id} and article: {$article_id} with field {$user_field}");

        $sql = $wpdb->prepare( "
            SELECT
                {$r_fields['id']} AS response_id,
                {$r_fields['message_time']} AS message_time,
                {$r_fields['sent_by_id']} AS message_sender_id,
                {$r_fields['recipient_id']} AS recipient_id,
                {$r_fields['message_read']} AS read_status,
                {$r_fields['archived']} AS archived,
                {$r_fields['message']} AS message
            FROM {$r_table}
            WHERE ( {$r_fields['assignment_id']} = %d ) AND
             ( {$r_fields['article_id']} = %d ) AND
             ( {$r_fields['program_id']} = %d ) AND
             ( {$user_field} = %d )
            ORDER BY {$r_fields['message_time']}
        ",
            $assignment_id,
            $article_id,
            $program_id,
            $user_id
        );

        $history = $wpdb->get_results( $sql );
        // dbg("e20rAssignmentModel::get_history() - Using SQL: {$sql}");
        dbg("e20rAssignmentModel::get_history() - Found " . count($history) . " message records for user {$user_id} about assignment {$assignment_id}");

        if ( empty( $history ) ) {
            dbg("e20rAssignmentModel::get_history() - No records found for {$assignment_id} and user {$user_id} as part of program {$program_id}");
            dbg("e20rAssignmentModel::get_history() - Error: " . ( empty( $wpdb->last_error) ? 'N/A' : $wpdb->last_error ));
        }

        dbg("e20rAssignmentModel::get_history() - Returning message history for {$user_id} and {$assignment_id} as part of program {$program_id}: " . count( $history ) . " messages");
        return $history;
    }

    public function save_response( $data ) {

        $e20rTracker = e20rTracker::getInstance();
        $e20rTables = e20rTables::getInstance();

        global $wpdb;

        $reply_table = $e20rTables->getTable('response');
        $assignment_table = $e20rTables->getTable('assignments');
//        $reply_fields = $e20rTables->getFields('response');
        $assignment_fields = $e20rTables->getFields('assignments');

        $format = $e20rTracker->setFormatForRecord( $data );

        if ( array_key_exists( 'record_id', $data ) ) {

            $assignment_record_id = $data['record_id'];
            unset( $data['record_id']);
        }
        else {
            dbg("e20rAssignmentModel::save_response() - ERROR: No record ID found for existing (saved) assignment record in {$assignment_table}");
            return false;
        }

        dbg("e20rAssignmentModel::save_response() - Attempting to add response to {$reply_table}");
        dbg($data);
        dbg($format);

        if ( false === $wpdb->insert( $reply_table, $data, $format  ) ) {

            dbg("e20rAssignmentModel::save_response() - ERROR: Unable to save response data: " . $wpdb->last_error . ' for query: ' . $wpdb->last_query );
            return false;
        }

        $id = $wpdb->insert_id;
        dbg("e20rAssignmentModel::save_response() - Successfully inserted new reply in {$reply_table} with ID {$id}");

        dbg("e20rAssignmentModel::save_response() - Attempting to update saved assignment {$assignment_record_id} in {$assignment_table} to reflect new response {$id}");
        $updated = $wpdb->update( $assignment_table,
            array( "{$assignment_fields['response_id']}" => $id ),
            array( 'id' => $assignment_record_id ),
            array( '%d' ),
            array( '%d' )
            );

        if ( false === $updated ) {
            dbg("e20rAssignmentModel::save_response() - ERROR: Unable to update assignment record # {$data['assignment_id']} in {$assignment_table}: " . $wpdb->last_error . ' for query: ' . $wpdb->last_query );
            return false;
        }
        dbg("e20rAssignmentModel::save_response() - Returning success to calling function: " . $e20rTracker->whoCalledMe());
        return true;
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

            if ( !is_array( $this->settings->program_ids )&& ( !empty( $this->settings->program_ids )) ) {
                $this->settings->program_ids = array( $this->settings->program_ids );
            }

            /*
            if ( isset($this->settings->article_ids) && !is_array( $this->settings->article_ids ) && ( !empty( $this->settings->article_ids )) ) {
                $this->settings->article_ids = array( $this->settings->article_ids );
            }
            */

            if ( isset($this->settings->article_id) ) {
                // Delete this (old setting and no longer used).
                unset($this->settings->article_id);
            }

            if ( !is_array( $this->settings->select_options ) && (!empty( $this->settings->select_options )) ) {
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