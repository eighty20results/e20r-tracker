<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rCheckinModel extends e20rSettingsModel {

    private $settings;

    public function e20rCheckinModel()  {

        parent::__construct( 'checkin', 'e20r_checkins' );

/*        global $e20rTables;

        $this->table = $e20rTables->getTable('checkin');
        $this->fields = $e20rTables->getFields('checkin');
*/
    }

    public function defaultSettings() {

        global $post;

        $settings = parent::defaultSettings();

        $settings->checkin_type = 0; // 1 = Action, 2 = Assignment, 3 = Survey, 4 = Activity.
        $settings->item_text = ( isset( $post->post_excerpt ) ? $post->post_excerpt : null );
        $settings->short_name =  ( isset( $post->post_title ) ? $post->post_title : null );
        $settings->startdate = null;
        $settings->enddate = null;
        $settings->maxcount = 0;
        $settings->program_ids = null;

        return $settings;
    }

    public function getCheckins( $id, $type = 1, $numBack = -1 ) {

        $start_date = $this->getSetting( $id, 'startdate' );

        $checkins = array();

        dbg("e20rCheckinModel::getCheckins() - Loaded startdate: {$start_date}");

        $args = array(
            'posts_per_page' => $numBack,
            'post_type' => 'e20r_checkins',
            // 'post_status' => 'published',
            'order_by' => 'meta_value',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_e20r-checkin-startdate',
                    'value' => $start_date,
                    'compare' => '<=',
                    'type' => 'DATE',
                ),
                array(
                    'key' => '_e20r-checkin-checkin_type',
                    'value' => $type,
                    'compare' => '=',
                    'type' => 'numeric',
                ),
            )
        );

        $query = new WP_Query( $args );
        dbg("e20rCheckinModel::getCheckins() - Returned checkins: {$query->post_count}" );

        while ( $query->have_posts() ) {

            $query->the_post();

            $new = new stdClass();

            $new = $this->loadSettings( get_the_ID() );

            $new->id = get_the_ID();
            $new->item_text = $query->post->post_excerpt;
            $new->short_name = $query->post->post_title;

            $checkins[] = $new;
        }

        dbg("e20rCheckinModel::getCheckins() - Data to return:");

        return $checkins;
    }

    public function loadUserCheckin( $articleId, $userId, $type, $short_name = null ) {

        global $wpdb;
        global $current_user;
        global $e20rProgram;
        global $e20rArticle;

        $programId = $e20rProgram->getProgramIdForUser( $userId );

        if ( is_null( $short_name ) ) {
            $sql = $wpdb->prepare(
                "SELECT *
                FROM {$this->table} AS c
                 WHERE ( ( c.user_id = %d ) AND
                  ( c.checkin_short_name = %s ) AND
                  ( c.program_id = %d ) AND
                  ( c.checkin_type = %d ) AND
                  ( c.article_id = %d ) )",
                $userId,
                $short_name,
                $programId,
                $type,
                $articleId
            );
        }
        else {
            $sql = $wpdb->prepare(
                "SELECT *
                FROM {$this->table} AS c
                 WHERE ( ( c.user_id = %d ) AND
                  ( c.checkin_short_name = null ) AND
                  ( c.program_id = %d ) AND
                  ( c.checkin_type = %d ) AND
                  ( c.article_id = %d ) )",
                $userId,
                $programId,
                $type,
                $articleId
            );
        }
        $result = $wpdb->get_row( $sql );

        if ( is_wp_error( $result ) ) {

            dbg("e20rCheckinModel::loadCheckinData() - Error loading checkin... " . $wpdb->last_error );
            return false;
        }

        dbg("e20rCheckinModel::loadCheckinData() - Loaded " . count($result) . " check-in records");

        if ( empty( $result ) ) {

            if ( empty( $this->settings ) ) {
                $this->loadSettings( $articleId );
            }

            $result = new stdClass();
            $result->descr_id = $short_name;
            $result->user_id = $current_user->ID;
            $result->program_id = $programId;
            $result->article_id = $articleId;
            $result->checkin_date = $e20rArticle->releaseDate( $articleId );
            $result->checkin_note = '';

            dbg("e20rCheckinModel::loadCheckinData() - Using default values: ");
            dbg($result);
        }

        return $result;
    }

    public function exists( $checkin ) {

        global $wpdb;

        if ( ! is_array( $checkin ) ) {

            return false;
        }

        $sql = $wpdb->prepare(
           "SELECT id, checkedin
                FROM {$this->table}
                WHERE (
                ( {$this->fields['user_id']} = %d ) AND
                ( {$this->fields['checkin_date']} LIKE %s ) AND
                ( {$this->fields['program_id']} = %d )
           ",
           $checkin['user_id'],
           $checkin['checkin_date'] . '%',
           $checkin['program_id']
        );

        $result = $wpdb->get_row( $sql );

        if ( ! empty( $result ) ) {
            return $result;
        }

        return false;
    }

    public function loadSettings( $id ) {

        $this->settings = parent::loadSettings($id);

        $pst = get_post( $id );

        $this->settings->item_text = $pst->post_excerpt;
        $this->settings->short_name = $pst->post_title;

        return $this->settings;
    }

    public function setCheckin( $checkin ) {

        global $wpdb;

        if ( $result = $this->exists( $checkin ) ) {

            $checkin['id'] = $result->id;
        }

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

        dbg("e20rCheckinModel::saveSettings() - Saving checkin Metadata: " . print_r( $settings, true ) );

        $error = false;

        foreach ( $defaults as $key => $value ) {

            if ( in_array( $key, array( 'id', 'short_name', 'item_text' ) ) ) {
                continue;
            }

            if ( false === $this->settings( $checkinId, 'update', $key, $settings->{$key} ) ) {

                dbg( "e20rCheckin::saveSettings() - ERROR saving {$key} setting ({$settings->{$key}}) for check-in definition with ID: {$checkinId}" );

                $error = true;
            }
        }

        return ( !$error ) ;
    }

}