<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 10/9/14
 * Time: 3:10 PM
 */

class e20rArticle {

    /* Articles contain list of programs it belongs to along with the PostID and all metadata.*/
    private $post_id;
    private $program_ids = false;
    private $user_programId = 0;
    private $meta;
    private $sequence_list = array();

    public function setId( $id = null ) {

        if ( $id === null ) {

            global $post;

            $id = $post->ID;
        }

        $this->post_id = $post;
        $this->init();
    }

    private function defaults( ) {

        $this->meta = array (
            'category' => 'Lesson',
            'assignment_question_ids' => array(),
            'checkin_item_id' => 0,
            'is_measurement_day' => 1,
            'is_photo_day' => 1,
            'release_date' => '',
            'release_day' => 1, // Get from post_id's postmeta ('get sequence ID and delay value for sequence')
        );
    }

    /**
     * An article can have multiple program IDs associated with it.
     *
     * A program can have one article per postID but many postIDs - also known as A program can have multiple article IDs.
     */

    /**
     * @param $programId
     */
    public function init( $userId = null ) {

        global $current_user, $post;

        if ( $userId === null ) {
            $userId = $current_user->ID;
        }

        if ( $userId == 0 ) {
            dbg("e20rArticle::init() - No logged in user defined. Returning...");
            return;
        }

        if ( ! $post->ID ) {
            dbg("e20rArticle::init() - No post ID available...?");
            return;
        }

        $this->post_id = $post->ID;

        if ( empty( $program_ids ) && ( $this->program_ids === false ) ) {

            dbg("e20rArticle::init() -- Loading array of program IDs from DB");
            $this->program_ids = get_post_meta( $this->post_id, 'e20r_tracker_program_ids', true);
        }

        if ( is_array( $program_ids ) && ( $this->program_ids === false ) ) {
            $this->program_ids = $program_ids;
        }
        elseif ( is_array( $program_ids ) && ( ! empty( $this->program_ids ) ) ) {

            dbg("e20rArticle::init() -- Received array of program IDs and existing array is present");
            $this->program_ids = array_merge( $this->program_ids, $program_ids );
            $this->program_ids = array_unique( $this->program_ids );
        }

        if ( (! is_array( $program_ids ) ) && ( $program_ids !== null ) ) {
            dbg("e20rArticle::init() -- Program ID specified, not an array of IDs");
            $this->program_ids = array( $program_ids );
        }

        dbg("e20rArticle::init() -- loading article settings");
        $this->meta = get_post_meta( $this->post_id, 'e20r_article_meta', true);

        $this->meta = wp_parse_args( $this->meta, $this->defaults() );

        if ( empty( $this->meta ) ) {
            dbg("e20rArticle::init() -- Loading default article meta");
            $this->defaults();
        }

        dbg("e20rArticle::init() -- loading program ID for user");
        $this->user_programId = get_user_meta( $userId, 'e20r_program', true);

        dbg("e20rArticle::init() -- loading drip-feed settings for article");
        $this->sequence_list = get_post_meta( $this->post_id, '_post_sequences', true);

        dbg("e20rArticle::init() - Belongs to sequence(s): " . print_r( $this->sequence_list, true ) );

    }

    public function save() {

        if ( update_post_meta( $this->post_id, 'e20r_programs', $this->program_ids ) === false ) {
            throw new Exception( "Error saving list of programs for article (id: {$this->post_id})" );
        }

        if ( update_post_meta( $this->post_id, 'e20r_article_meta', $this->meta ) === false ) {
            throw new Exception( "Error saving article settings (id: {$this->post_id})" );
        }
    }

    public function releaseDate() {

        if ( empty($this->meta['release_date'] ) &&
             ( $this->meta['is_measurement_day'] === 1 ) ) {

            global $e20rTracker;

            $sat = $e20rTracker->datesForMeasurements();
            $this->meta['release_date'] = $sat['current'];
        }

        dbg( "e20rArticle::releaseDate: {$this->meta['release_date']}" );
        return $this->meta['release_date'];
    }

    public function isMeasurementDay( $post_id ) {

        return ( $this->meta['is_measurement_day'] == 0 ? false : true );

    }

    public function isPhotoDay( $post_id ) {

        return ( $this->meta['is_photo_day'] == 0 ? false : true );
    }

    public function getMeta() {

        return $this->meta;
    }

    public function view_articlePostMetabox() {

        $metabox = '';

        global $post;

        $this->program_ids =  ( $metabox['args']['program_ids'] != false ? $metabox['args']['program_ids'] : null );
        $this->init(); // Self-init.

        dbg("e20rArticle() - Article data for Post {$post->ID} loaded. " . print_r( $this->program_ids, true) );

        ob_start();
        ?>
        <div class="submitbox" id="e20r-tracker-article-postmeta">
            <div id="minor-publishing">
                <div id="e20r-article-postmetabox">
                    <?php echo $this->view_articleMetabox( $post->ID, $this->program_ids ) ?>
                </div>
            </div>
        </div>
        <?php

        $metabox = ob_get_clean();

        echo $metabox;
    }

    public function view_articleMetabox( $post_id, $program_ids ) {

        /**
         * Tie $article_id to $post_id, checkin_id, assignment Question ID (and answers)
         */


    }

    private function hasProgramId() {

        global $post;

        if ( $post->ID ) {
            dbg("hasProgramId() -  Post ID defined.. Looking up the program Ids.");
            get_post_meta( $post->ID, 'e20r_tracker_program_ids', true);
            return ( empty( $this->program_ids ) ? false : true );
        }

        return false;
    }

    public function editor_metabox_setup( $object, $box ) {

        if ( false === ( $program_ids = $this->hasProgramId() ) ) {

            dbg("e20rArticle::editor_metabox_setup() -  Warning: Not loading the metabox since there are no program(s) defined for the post");
            return;
        }

        $program_ids = 0;
        dbg("e20rArticle() - Metabox for Post/Page editor being loaded");
        global $e20rTracker;

        foreach( $e20rTracker->managed_types as $type ) {

            add_meta_box( 'e20r-article-meta', __( 'Article Configuration', 'e20r_tracker' ),
                array( &$this, 'view_articlePostMetabox' ), 'e20r_articles', 'advanced', 'high' );
        }
    }

    public function view_manageArticles() {

        $html = '';

        return $html;
    }

    private function load_articles() {

        if ( ! empty( $this->program_ids ) ) {

            foreach ( $this->program_ids as $pid ) {

                $this->postArticles[ $pid ] = get_user_meta( $this->post_id, "e20r_articles_{$pid}", true );
            }
        }
    }

    public function render_submenu_page() {

        ?>
        <div id="e20r-articles">
            <?php

            echo $this->view_manageArticles();

            ?>
        </div>
    <?php

    }

    public function addArticle( $obj ) {

        $key = $this->findArticle( $obj->id );

        if ($key !== false ) {

            $this->article_list[ $key ] = $obj;
        }
        else {

            $this->article_list[] = $obj;
        }
    }

    public function getArticles() {

        return $this->article_list;
    }

    public function getArticle( $id ) {

        if ( $key = $this->findArticle( $id ) !== false ) {
            return $this->article_list[$key];
        }

        return false;
    }


    public function getID() {

        return $this->post_id;
    }

    private function findArticle( $id ) {

        foreach ( $this->article_list as $key => $article ) {

            if ( $article->id == $id ) {

                return $key;
            }
        }

        return false;
    }
} 