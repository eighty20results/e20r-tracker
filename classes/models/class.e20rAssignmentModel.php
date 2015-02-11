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

    public function e20rAssignmentModel()  {

        parent::__construct( 'assignments', 'e20r_assignments' );

/*        global $e20rTables;

        $this->table = $e20rTables->getTable('assignment');
        $this->fields = $e20rTables->getFields('assignment');
*/
    }

    public function defaultSettings() {

        global $current_user;
        global $e20rProgram;

        $settings = parent::defaultSettings();

        $settings->id = null;
        $settings->descr = null;
        $settings->order_num = 1;
        $settings->question = null;
        $settings->delay = 0;
        $settings->field_type = 'textbox';
        $settings->article_id;
        $settings->user_id = $current_user->ID;
        $settings->program_id = $e20rProgram->getProgramIdForUser( $settings->user_id );
        $settings->answer_date = null;
        $settings->answer;

        return $settings;
    }

    public function getAssignments( $id, $articleId ) {

        global $current_user;
        global $e20rProgram;

        $assignments = array();

        dbg("e20rAssignmentModel::getAssignments() - for article #: {$articleId}");

        $args = array(
            'posts_per_page' => -1,
            'post_type' => 'e20r_assignments',
            'post_status' => 'publish',
            'order_by' => 'meta_value',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_e20r-assignment-article_id',
                    'value' => $articleId,
                    'compare' => '=',
                    'type' => 'numeric',
                ),
            )
        );

        $query = new WP_Query( $args );
        dbg("e20rAssignmentModel::getAssignments() - Returned assignments: {$query->post_count}" );

        while ( $query->have_posts() ) {

            $query->the_post();

            $new = new stdClass();

            $new = $this->loadSettings( get_the_ID() );

            $new->id = get_the_ID();
            $new->descr = $query->post->post_excerpt;
            $new->question = $query->post->post_title;
            $new->article_id = $articleId;
            $assignments[] = $new;
        }

        dbg("e20rAssignmentModel::getAssignments() - Data to return:");

        return $assignments;
    }

    public function loadUserAssignment( $articleId, $userId, $delay = null ) {

        // TODO: Load the recored user assignment answers by assignment ID.
        global $wpdb;
        global $current_user;
        global $post;
        global $e20rProgram;

        $programId = $e20rProgram->getProgramIdForUser( $userId );

        // Preserve
        $save_post = $post;

        dbg("e20rAssignmentModel::loadUserAssignment() - date for article # {$articleId} in program {$programId} for user {$userId}: {$delay}");

        $sql = $wpdb->prepare(
            "SELECT *
             FROM {$this->table} AS a
             WHERE ( ( a.user_id = %d ) AND
              ( c.program_id = %d ) AND
              ( c.delay = %d ) AND
              ( c.article_id = %d ) )
              ORDER BY c.id ",
            $userId,
            $programId,
            $delay,
            $articleId
        );

        dbg("e20rAssignmentModel::loadUserAssignment() - SQL: {$sql}");

        $result = $wpdb->get_results( $sql );

        if ( $result === false ) {

            dbg("e20rAssignmentModel::loadAssignmentData() - Error loading assignments: " . $wpdb->last_error );
            return false;
        }

        dbg("e20rAssignmentModel::loadAssignmentData() - Loaded " . count($result) . " check-in records");

        if ( ! empty( $result ) ) {

            // Index the result array by the ID of the assignment (key)
            foreach( $result as $key => $data ) {

                $result[$data->id] = $data;
                $post = get_post( $data->id );

                setup_postdata( $post );

                $result[$data->id]->descr = $post->post_excerpt;
                $result[$data->id]->question = $post->post_title;


                unset($result[$key]);

                // Array is now indexed by record/post/assignment ID
                wp_reset_postdata();
            }
        }
        else {
            $result = array( 0 => $this->defaultSettings() );

            dbg("e20rAssignmentModel::loadAssignmentData() - Using default values: ");
            dbg($result);
        }

        // Restore
        $post = $save_post;

        return $result;
    }

    /*
    public function exists( $assignment ) {

        global $wpdb;

        dbg("e20rAssignmentModel::exists() -  Data: ");
        dbg( $assignment );

        if ( ! is_array( $assignment ) ) {

            return false;
        }

        $sql = $wpdb->prepare(
           "SELECT id, checkedin
                FROM {$this->table}
                WHERE (
                ( {$this->fields['user_id']} = %d ) AND
                ( {$this->fields['assignment_date']} LIKE %s ) AND
                ( {$this->fields['program_id']} = %d ) AND
                ( {$this->fields['assignment_type']} = %d ) AND
                ( {$this->fields['assignment_short_name']} = %s )
                )
           ",
            $assignment['user_id'],
            $assignment['assignment_date'] . '%',
            $assignment['program_id'],
            $assignment['assignment_type'],
            $assignment['assignment_short_name']
        );

        $result = $wpdb->get_row( $sql );

        if ( ! empty( $result ) ) {
            dbg("e20rAssignmentModel::exists() - Got a result returned: ");
            dbg($result);
            return $result;
        }

        return false;
    }
*/
    public function loadSettings( $id ) {

        $this->settings = parent::loadSettings($id);

        $pst = get_post( $id );

        $this->settings->descr = $pst->post_excerpt;
        $this->settings->question = $pst->post_title;
        $this->settings->id = $id;

        return $this->settings;
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

            if ( in_array( $key, array( 'id', 'descr', 'question' ) ) ) {
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