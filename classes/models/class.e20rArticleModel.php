<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rArticleModel extends e20rSettingsModel {

    protected $id;
    protected $settings;

    public function e20rArticleModel() {

        parent::__construct( 'article', 'e20r_articles' );
    }

    public function defaultSettings() {

        $this->settings = parent::defaultSettings();

        $this->settings->id = null;
        $this->settings->programs = null;
        $this->settings->post_id = null;
        $this->settings->release_day = null;
        $this->settings->release_date;
        $this->settings->assignments = null;
        $this->settings->checkins = null;
        $this->settings->measurement_day = false;
        $this->settings->photo_day = false;
        $this->settings->prefix = "Lesson";

        dbg("e20rArticleModel::defaultSettings() - Defaults loaded");
        return $this->settings;
    }

    public function getProgramID() {

        global $current_user;

        $userPrograms = get_user_meta( $current_user->ID, '_e20r-user-programs' );

        if ( $userPrograms == false ) {
            return false;
        }

        // Combination of program from usermeta & the $settings-Programs;
    }

    public function findArticle($key, $value, $type = 'numeric', $programId = -1 ) {

        $articleList = array();

        $args = array(
            'posts_per_page' => -1,
            'post_type' => 'e20r_articles',
            'post_status' => 'publish',
            'order_by' => 'meta_value',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => "_e20r-article-{$key}",
                    'value' => $value,
                    'compare' => '=',
                    'type' => $type,
                ),
            )
        );

        $query = new WP_Query( $args );
        dbg("e20rArticleModel::findArticle() - Returned articles: {$query->post_count}" );

        while ( $query->have_posts() ) {

            $query->the_post();

            $new = $this->loadSettings( get_the_ID() );
            $new->id = get_the_ID();

            if ( $query->post_count > 1 ) {

                $articleList[] = $new;
            }
            else {
                $articleList = $new;
            }
        }

        return $articleList;
    }

    // TODO: This requires the presence of checkin IDs in the Article list, etc.
    // checkin definitions -> $obj->type, $obj->
    public function lessonComplete( $articleId ) {

        dbg("e20rArticleModel::lessonComplete() - Checking lesson status for article: {$articleId} (ID)");

        global $wpdb;


        // Find the e20r_checkin record with the $articleId,
        // for the $this->releaseDate( $articleId )
        // AND the $userId AND the $checkin_item_id
        // AND the $checkin_type == 1 (lesson)
        // AND the $programId that applies to this $articleId and $userId.
        return false;
    }

    /**
     * Save the Article Settings to the metadata table.
     *
     * @param $settings - Array of settings for the specific article.
     *
     * @return bool - True if successful at updating article settings
     */
    public function saveSettings( stdClass $settings ) {

        $articleId = $settings->id;

        $defaults = self::defaultSettings();

        dbg("e20rArticleModel::saveSettings() - Saving article Metadata: " . print_r( $settings, true ) );

        $error = false;

        foreach ( $defaults as $key => $value ) {

            if ( in_array( $key, array( 'id' ) ) ) {
                continue;
            }

            if ( $key == 'post_id' ) {

                dbg("e20rArticleModel::saveSettings() - Saving the article ID with the post ");
                update_post_meta( $settings->{$key}, '_e20r-article-id', $articleId );
            }

            if ( false === $this->settings( $articleId, 'update', $key, $settings->{$key} ) ) {

                dbg( "e20rArticleModel::saveSettings() - ERROR saving {$key} setting ({$settings->{$key}}) for article definition with ID: {$articleId}" );

                $error = true;
            }
        }

        return ( !$error ) ;
    }

}