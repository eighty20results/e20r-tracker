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
    private $program_ids = array();
    private $meta = array();

    public function e20rArticle( $program_ids = null, $post_id = null ) {

        dbg("Loading article class");
        global $e20rTracker, $post, $e20rArticle;

        $e20rTracker = $this;

        if ( $post_id == null ) {

            global $post;

            if ( $post->ID ) {
                $post_id = $post->ID;
            }
        }

        $this->post_id = $post_id;

        if ( empty( $program_ids ) && ( $this->program_ids === false ) ) {

            dbg("e20rArticle() - Loading array of program IDs from DB");
            $this->program_ids = get_post_meta( $this->post_id, 'e20r_tracker_program_ids', true);
        }

        if ( is_array( $program_ids ) && ( $this->program_ids === false ) ) {
            $this->program_ids = $program_ids;
        }
        elseif ( is_array( $program_ids ) && ( ! empty( $this->program_ids ) ) ) {

            dbg("e20rArticle() - Received array of program IDs and existing array is present");
            $this->program_ids = array_merge( $this->program_ids, $program_ids );
            $this->program_ids = array_unique( $this->program_ids );
        }

        if ( (! is_array( $program_ids ) ) && ( $program_ids !== null ) ) {
            dbg("e20rArticle() - Program ID specified, not an array of IDs");
            $this->program_ids = array( $program_ids );
        }

        $this->init();

        add_action( 'add_meta_boxes', array( &$this, 'editor_metabox_setup') );
    }

    private function defaults( $program_id ) {

        $this->meta[$program_id] = new stdClass();
        $this->meta[$program_id]->category = 'Lesson';
        $this->meta[$program_id]->assignment_question_ids = array();
        $this->meta[$program_id]->checkin_item_id = 0;
        $this->meta[$program_id]->is_measurement_day = 0;
        $this->meta[$program_id]->release_date = 0; // Calculate based on program_id's startdate and $this->release_day;
        $this->meta[$program_id]->release_day = 0;

    }

    /**
     * An article can have multiple program IDs associated with it.
     *
     * A program can have one article per postID but many postIDs - also known as A program can have multiple article IDs.
     */

    /**
     * @param $programId
     */
    public function init() {

        if ( ! empty( $this->program_ids ) ) {
            $this->load_articles();

        }
        else {
            $this->meta = array();
        }
    }

    private function saveArticleData() {


    }

    public function isMeaurementDay( $post_id ) {

        $articleData = $this->findArticle( $post_id );



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
                    <?php echo $this->view_articleMetabox( $post->ID, $this->program_id ) ?>
                </div>
            </div>
        </div>
        <?php

        $metabox = ob_get_clean();

        echo $metabox;
    }

    public function view_articleMetabox( $post_id, $program_id ) {

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

        $program_ids =
        dbg("e20rArticle() - Metabox for Post/Page editor being loaded");
        global $e20rTracker;

        foreach( $e20rTracker->managed_types as $type ) {

            add_meta_box( 'e20r-article-meta', __( 'Article Configuration', 'e20r_tracker' ), array(
                &$this,
                'view_articlePostMetabox'
            ), $type, 'advanced', 'high', array( 'program_ids' => $program_ids ) );
        }
    }

    public function view_manageArticles() {

        // Fetch the Checkin Item we're looking to manage
        $article_list = $this->load_articles( false );

        $programs = new e20rPrograms();
        $items = new e20rCheckin();


        ob_start();
        ?>
        <H1>Articles, Check-Ins and Assignments</H1>
        <hr />
        <form action="" method="post">
            <?php wp_nonce_field('e20r-tracker-data', 'e20r_tracker_edit_nonce'); ?>
            <div class="e20r-checkin-editform">
                <table id="e20r-manage-checkin-items">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label for="e20r-article-edit">Edit</label></th>
                        <th class="e20r-label header"><label for="e20r-article-id">Id</label></th>
                        <th class="e20r-label header"><label for="e20r-article-order">Order #</label></th>
                        <th class="e20r-label header"><label for="e20r-program">Program</label></th>
                        <th class="e20r-label header"><label for="e20r-article-short-name">Short name</label></th>
                        <th class="e20r-label header"><label for="e20r-checkin-item-name">Summary</label></th>
                        <th class="e20r-label header"><label for="e20r-checkin-startdate">Starts on</label></th>
                        <th class="e20r-label header"><label for="e20r-checkin-enddate">Ends on</label></th>
                        <th class="e20r-label header"><label for="e20r-checkin-maxcount">Max count</label></th>
                        <th class="e20r-save-col hidden">Save</th>
                        <th class="e20r-cancel-col hidden">Cancel</th>
                        <th class="e20r-delete-col hidden">Remove</th>
                        <th class="e20r-label header hidden"></th>
                    </tr>
                    <tr>
                        <td colspan="13"><hr/></td>
                        <!-- select for choosing the membership type to tie this check-in to -->
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ( count($article_list) > 0) {

                        // dbg("Fetched Check-in items: " . print_r( $article_list, true ) );

                        foreach ( $article_list as $article ) {

                            if ( is_null( $article->startdate ) ) {
                                $start = '';
                            } else {
                                $start = new DateTime( $article->startdate );
                                $start = $start->format( 'Y-m-d' );
                            }

                            if ( is_null( $article->enddate ) ) {
                                $end = '';
                            } else {
                                $end = new DateTime( $article->enddate );
                                $end = $end->format( 'Y-m-d' );
                            }

                            if ( is_object( $end ) && is_object( $start ) ) {

                                $diff = $end->diff( $start );

                                $max = $diff->format( '%a' );
                            }
                            else {
                                $max = 14; // Default max if a habit is 14 days.
                            }

                            if ( is_null($article->maxcount ) ) {
                                $article->maxcount = $max;
                            }

                            $iId = $article->id;

                            ?>
                            <tr id="<?php echo $iId; ?>" class="checkin-inputs">
                                <td class="text-input">
                                    <input type="checkbox" name="checkin-item-edit" id="edit_<?php echo $iId; ?>">
                                </td>
                                <td class="text-input">
                                    <input type="text" id="e20r-checkin-item-id_<?php echo $iId; ?>" disabled name="e20r-checkin-item-id" size="5" value="<?php echo ( ! empty( $article->id ) ? $article->id : null ); ?>">
                                </td>

                                <td class="text-input">
                                    <input type="text" name="e20r-checkin-item-order" id="e20r-checkin-item-order_<?php echo $iId; ?>" disabled size="4" value="<?php echo ( ! empty( $article->item_order ) ? $article->item_order : null ); ?>">
                                </td>
                                <td class="select-input" id="e20r-checkin-item-select_program-col_<?php echo $iId; ?>">
                                    <?php echo $programs->programSelector( true, ( ( $article->program_id == 0) ? null : $article->program_id ), $iId, true ); ?>
                                </td>
                                <td class="text-input">
                                    <input type="text" name="e20r-checkin-short-name" id="e20r-checkin-item-short-name_<?php echo $iId; ?>" disabled size="25" value="<?php echo ( ! empty( $article->short_name ) ? $article->short_name : null ); ?>">
                                </td>
                                <td class="text-input">
                                    <input type="text" name="e20r-checkin-item-name" id="e20r-checkin-item-name_<?php echo $iId; ?>" disabled size="50" value="<?php echo ( ! empty( $article->item_name ) ? $article->item_name : null ); ?>">
                                </td>
                                <td class="text-input">
                                    <input type="date" name="e20r-checkin-startdate" id="e20r-checkin-item-startdate_<?php echo $iId; ?>" disabled value="<?php echo $start; ?>">
                                </td>
                                <td class="text-input">
                                    <input type="date" name="e20r-checkin-enddate" id="e20r-checkin-item-enddate_<?php echo $iId; ?>" disabled value="<?php echo $end; ?>">
                                </td>
                                <td class="text-input">
                                    <input type="text" name="e20r-checkin-maxcount" id="e20r-checkin-item-maxcount_<?php echo $iId; ?>" disabled size="5" value="<?php echo ( ! empty( $article->maxcount ) ? $article->maxcount : null ); ?>" >
                                </td>

                                <td class="hidden select-input">
                                    <!-- Insert membership type this program belongs to -->
                                </td>
                                <td class="hidden save-button-row" id="e20r-td-save_<?php echo $iId; ?>">
                                    <a href="#" class="e20r-save-edit-checkin-item button">Save</a>
                                </td>
                                <td class="hidden cancel-button-row" id="e20r-td-cancel_<?php echo $iId; ?>">
                                    <a href="#" class="e20r-cancel-edit-checkin-item button">Cancel</a>
                                </td>
                                <td class="hidden delete-button-row" id="e20r-td-delete_<?php echo $iId; ?>">
                                    <a href="#" class="e20r-delete-checkin-item button">Remove</a>
                                </td>
                                <td class="hidden hidden-input">
                                    <input type="hidden" class="hidden_id" value="<?php echo $iId; ?>">
                                </td>
                            </tr>
                        <?php
                        } // foreach
                    } // Endif
                    else {
                        ?>
                        <tr>
                            <td colspan="13">
                                No Items found in the database. Please add one or more new Items by clicking the "Add New" button.
                            </td>
                        </tr>

                    <?php
                    }
                    ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="13"><hr/></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="add-new" style="text-align: left;"><a class="e20r-button button" id="e20r-add-new-item" href="#">Add New</a></td>
                    </tr>
                    <tr id="add-new-checkin-item" class="hidden">
                        <td class="text-input">
                            <input type="checkbox" disabled name="edit" id="edit">
                        </td>
                        <td class="text-input">
                            <input type="text" id="e20r-checkin-item-id" name="e20r-checkin-item-id" disabled size="5" value="auto">
                        </td>
                        <td class="text-input">
                            <input type="text" name="e20r-checkin-item-order" id="e20r-checkin-item-order" size="4" value="">
                        </td>
                        <td class="select-input">
                            <?php echo $programs->programSelector( true, null, null, false ); ?>
                        </td>
                        <td class="text-input">
                            <input type="text" name="e20r-checkin-short_name" id="e20r-checkin-item-short-name" size="25" value="">
                        </td>
                        <td class="text-input">
                            <input type="text" name="e20r-checkin-item_name" id="e20r-checkin-item-name" size="35" value="">
                        </td>
                        <td class="text-input">
                            <input type="date" name="e20r-checkin-startdate" id="e20r-checkin-item-startdate" value="">
                        </td>
                        <td class="text-input">
                            <input type="date" name="e20r-checkin-enddate" id="e20r-checkin-item-enddate" value="">
                        </td>
                        <td class="text-input">
                            <input type="text" name="e20r-checkin-maxcount" id="e20r-checkin-item-maxcount" size="5" value="" >
                        </td>
                        <td class="select-input hidden">
                            <!-- Insert membership type this program belongs to -->
                        </td>
                        <td class="save">
                            <a class="e20r-button button" id="e20r-save-new-checkin-item" href="#">Save</a>
                        </td>
                        <td class="cancel">
                            <a class="e20r-button button" id="e20r-cancel-new-checkin-item" href="#">Cancel</a>
                        </td>
                        <td class="hidden">
                            <!-- Nothing here, it's for the delete/remove button -->
                        </td>
                        <td class="hidden-input">
                            <input type="hidden" class="hidden_id" value="<?php echo $iId; ?>">
                        </td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </form>
        <?php
        $html = ob_get_clean();

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

    public function setID( $id ) {

        $this->id = $id;
    }

    public function getID() {

        return $this->id;
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