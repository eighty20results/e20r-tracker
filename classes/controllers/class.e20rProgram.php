<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rProgram extends e20rSettings {

    private $programTree = array();

    public function e20rProgram() {

        dbg("e20rProgram::init() - Initializing Program data");
        parent::__construct( 'program', 'e20r_programs', new e20rProgramModel(), new e20rProgramView() );

    }

    public function init( $programId = null ) {

	    global $currentProgram;
	    global $current_user;

	    if ( is_user_logged_in() ) {

            if ( ( is_null( $programId ) ) ) {

                dbg("e20rProgram::init() - Fetching program ID for user {$current_user->ID}.");
                $programId = get_user_meta($current_user->ID, 'e20r-tracker-program-id', true);
            }

            if ( ( empty( $currentProgram->id) ||
                ( !empty( $programId ) && ( $currentProgram->id != $programId ) ) ) ) {

			    dbg("e20rProgram::init() - Loading program settings for {$programId}.");

                $currentProgram = $this->model->loadSettings( $programId );

                dbg("e20rProgram::init() - Program info has been loaded for: {$currentProgram->id}");
                return true;
		    }

/*		    if ( isset( $currentProgram->id ) && ( $currentProgram->id == $programId ) ) {

			    $this->programTree = $this->getPeerPrograms( $currentProgram->id );
		    }
*/
	    }

	    $currentProgram = new stdClass();
	    $currentProgram->id = null;

	    dbg("e20rProgram::init() - No Program ID found or user not logged in!");
	    return false;
    }

    public function isActive( $program_shortname ) {

        $program = $this->findByName( $program_shortname );

        if ( ( $program !== false ) && ( ! in_array( $program->post_status, array( 'publish', 'private' ) ) ) ) {

            dbg("e20rProgram::isActive() - Program not found or not published");
            return false;
        }

        $now = current_time( 'timestamp' );
        $start = strtotime( $program->startdate );
        $end = strtotime( $program->enddate );

        // It's available since no start has been configured.
        if ( ! $start ) {
            dbg("e20rProgram::isActive() - Start value not set, program is available");
            return true;
        }

        // It's available since no end-time has been configured and it's after the starttime
        if ( ( ! $end )  && ( $now >= $start ) ) {
            dbg("e20rProgram::isActive() - It's after the start date, and end value not set, program is available");
            return true;
        }

        if ( ( $now >= $start ) && ( $now <= $end ) ) {
            dbg("e20rProgram::isActive() - Currently somewhere between start and end for the program. it's available ");
            return true;
        }

        return false;
    }

    public function getPeerPrograms( $programId = null ) {

        if ( is_null( $programId ) ) {

            global $post;
            // Use the parent value for the current post to get all of its peers.
            $programId = $post->post_parent;
        }

        $programs = new WP_Query( array(
            'post_type' => 'page',
            'post_parent' => $programId,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'fields' => 'ids',
        ) );

        $this->programTree = array(
            'pages' => $programs->posts,
        );

        foreach ( $programs->posts as $k => $v ) {

            if ( $v == get_the_ID() ) {

                if( isset( $programs->posts[$k-1] ) ) {

                    $this->programTree['prev'] = $programs->posts[ $k - 1 ];
                }

                if( isset( $programs->posts[$k+1] ) ) {

                    $this->programTree['next'] = $programs->posts[ $k + 1 ];
                }
            }
        }

        wp_reset_postdata();

        return $this->programTree;
    }

    public function getProgramList() {

	    global $post;

	    /*
	    $bPost = $post;
        $list = array();

        $the_query = new WP_Query( array(
            'post_type' => 'e20r_programs',
            'posts_per_page' => -1,
            'post_status' => array( 'publish', 'private' ),
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'fields' => 'ids',
        ) );

	    if ( $the_query->have_posts() ) {

            dbg("e20rProgram::getProgramList() - Loaded " . count($the_query->post_count) . " program definitions from the DB");

            while( $the_query->have_posts() ) {

		        $the_query->the_post();

		        dbg( "e20rProgram::getProgramList() - Adding " . get_the_title() );
		        $list[ get_the_ID() ] = get_the_title();
	        }
	    }

	    wp_reset_postdata();
		*/

	    $list = $this->model->loadAllSettings();

	    dbg("e20rProgram::getProgramList() - Content of list being returned ");
	    // dbg($list);

//	    $post = $bPost;

        return $list;
    }

	public function get_welcomeSurveyLink( $userId ) {

		global $currentProgram;

		$this->loadProgram( $userId );

		$link = get_permalink( $currentProgram->intake_form );

		dbg("e20rProgram::get_welcomeSurveyLink(): Link: {$link}");

		return $link;
	}

    public function getProgramIdForUser( $userId = 0, $articleId = null ) {

	    global $currentProgram;

        global $e20rArticle;

        $user_program = -9999;

        if ( 0 != $userId ) {

            $user_program = get_user_meta( $userId, 'e20r-tracker-program-id', true);
        }

        if ( !empty( $currentProgram->id ) && ( $currentProgram->id == $user_program ) ) {

            return $currentProgram->id;
        }

	    if ( empty( $currentProgram->id ) || ( !empty( $user_program) && ( $currentProgram->id != $user_program ) ) ) {

		    dbg("e20rProgram::getProgramIdForUser() - currentProgram->id isn't configured or its different from what this user ({$userId}) needs it to be ({$user_program}).");

            dbg("e20rProgram::getProgramIdForUser() - currentProgram being configured for {$userId} -> {$user_program}.");
            $this->model->loadSettings( $user_program );

            $this->configure_startdate( $user_program, $userId );

		    if ( empty( $currentProgram->id ) ) {

			    dbg("e20rProgram::getProgramIdForUser() - currentProgram getting set to default values");
			    $this->init();
		    }

            if ( !empty( $articleId ) ) {

                dbg("e20rProgram::getProgramIdForUser() - load Article ({$articleId}) too");
                $e20rArticle->init( $articleId );
            }

        }

        dbg("e20rProgram::getProgramIdForUser() - Loaded program ID ($currentProgram->id) for user {$userId}");
	    return ( isset( $currentProgram->id ) ? $currentProgram->id : false );
    }

    private function configure_startdate( $program_id, $userId ) {

        global $currentProgram;

        dbg("e20rProgram::configure_startdate() - Defined program startdate value: {$currentProgram->startdate}");

        if ( ( $currentProgram->id == $program_id ) && ( function_exists( 'pmpro_getMemberStartdate' ) ) ) {

            dbg( "e20rProgram::configure_startdate() - Using PMPro's member startdate for user ID {$userId}: {$currentProgram->startdate}");

            $currentProgram->startdate = date_i18n( 'Y-m-d', pmpro_getMemberStartdate( $userId ) );
            dbg("e20rProgram::configure_startdate() - Startdate set to the member's start date for the program: {$currentProgram->startdate} for {$userId}");
        }

    }

    public function setProgramForUser( $user_id, $membership_id ) {

        global $e20rTracker;
        global $currentProgram;

        dbg("e20rTracker::setProgramForUser() - Called from: " . $e20rTracker->whoCalledMe() );
        dbg("e20rProgram::setProgramForUser() - Locating programs from membership id # {$membership_id} on behalf of user {$user_id}");

        if ( false === ( $pId = $this->model->findByMembershipId( $membership_id ) ) ) {

            dbg("e20rProgram::setProgramForUser() - ERROR: No program IDs returned!");

            $addr = get_option( 'admin_email' );

            $subj = "Error: Cannot locate program definition for {$membership_id}";

            $msg = "Membership Level {$membership_id} isn't associated with a published program ID.\n";
            $msg .= "Please correct the program definitions for the {$membership_id} membership level\n";

            wp_mail( $addr, $subj, $msg);

        }

        dbg("e20rProgram::setProgramForUser() - Returned groups/membership IDs: ");
        dbg($pId);

        if ( is_array( $pId ) ) {

            dbg("e20rProgram::setProgramForUser() - ERROR: More than one program ID associated with membership!");
            $addr = get_option( 'admin_email' );

            $subj = "Error: Unexpected program definition(s)";

            $msg = "Membership Level {$membership_id} is associated with more than a single program ID.\n";
            $msg .= "Please correct the program definitions for the following programs:\n\n";

            foreach ( $pId as $id ) {

                $msg .= get_the_title($id) . "({$id})\n";
            }

            wp_mail( $addr, $subj, $msg);
            return;
        }

        update_user_meta( $user_id, 'e20r-tracker-program-id', $pId );

        if ( $pId ===  get_user_meta( $user_id, 'e20r-tracker-program-id', true ) ) {

            $addr = get_option( 'admin_email' );

            $subj = "Error: Unable to set program for user ID";

            $msg = "Membership Level {$membership_id} could not be configured for user ID {$user_id}.\n";
            $msg .= "Please update the profile for user with ID {$user_id} in the admin panel.\n";

            wp_mail( $addr, $subj, $msg);
        }

        dbg("e20rProgram::setProgramForUser() - Testing whether to add user to program list");

        if ( !isset( $currentProgram->id ) || ( $pId != $currentProgram->id ) ) {

            $this->init($pId);
        }

        $currentProgram->users[] = $user_id;

        if ( !in_array( $user_id, $currentProgram->users ) ) {

            dbg("e20rProgram::setProgramForUser() - Adding user to the program 'users' list");
            $this->model->set('users', $currentProgram->users, $currentProgram->id);
        }

        return;
    }

    private function loadProgram( $userId = 0 ) {

		global $currentProgram;

		if ( !isset( $currentProgram->id ) || ( ! in_array( $userId, $currentProgram->users ) ) ) {

			if ( is_user_logged_in() && ( $userId != 0 ) ) {

                dbg( "e20rProgram::loadProgram() - Loading usermeta for ID {$userId}");
				$programId = get_user_meta( $userId, 'e20r-tracker-program-id', true );

				if ( ( false !== $programId ) &&
                    ( !isset( $currentProgram->id) || ( $currentProgram->id !== $programId ) ) ) {

                    dbg( "e20rProgram::loadProgram() - Need to init the program object");
                    $this->model->loadSettings($programId);
				}
			}

            $this->configure_startdate( $programId, $userId );
		}


/*		else {

			$this->init();
		}
*/
        // Set the program startdate based on the user's membership start.
/*        if ( function_exists( 'pmpro_getMemberStartdate' ) ) {

            dbg( "e20rProgram::startdate() - Using PMPro's member startdate for user ID {$userId}: {$currentProgram->startdate}");

            // $from_mbr = date_i18n( 'Y-m-d', pmpro_getMemberStartdate( $userId ) );

            // dbg("e20rProgram::startdate() - From membership plugin's startdate value: {$from_mbr}");

            // $actual_startTS = strtotime( "{$from_mbr} + 1 day");
            // $currentProgram->startdate = date_i18n( 'Y-m-d', $actual_startTS );
            $currentProgram->startdate = date_i18n( 'Y-m-d', pmpro_getMemberStartdate( $userId ) );
            dbg("e20rProgram::startdate() - From membership plugin's startdate value: {$from_mbr}");
            dbg("e20rProgram::startdate() - Forcing startTS to the day after (workaround): {$from_mbr} vs {$currentProgram->startdate} for {$userId}");
        }
*/
        dbg( "e20rProgram::loadProgram() - User's programID: " . isset( $currentProgram->id ) ? $currentProgram->id : 'null' );
	}
    /**
     * Action Hook to add the E20R Tracker user specific settings (like adding a program for the user)
     *
     * @param $user -- WP_User object
     */
    public function selectProgramForUser( $user ) {

        dbg("e20rProgram::selectProgramForUser() - user: {$user->ID}");

        $programlist = $this->getProgramList();
        $activeProgram = $this->getProgramIdForUser( $user->ID, null );

        dbg("e20rProgram::selectProgramForUser() - Active Program: {$activeProgram}");

        echo $this->view->view_userProfile( $programlist, $activeProgram );
    }

    /**
     * Calculates the startdate (as a 'seconds since epoch') value and returns it to the calling function.
     *
     * Currently supports:
     *      Internal usermeta value. (e20r-program-startdate => 'When this user started the program'
     *      Paid Memberships Pro.
     *
     * @param $userId - ID of user to find the startdate for.
     *
     * @return int|mixed - Timestamp (seconds since UNIX epoch
     */
    public function startdate( $userId, $program_id = null, $membership = true) {

	    global $currentProgram;

        if ( ( !empty( $program_id ) && !empty( $currentProgram->id ) && ( $currentProgram->id === false ) ) ||
                ( (!empty( $program_id ) ) && ( $program_id != $currentProgram->id ) ) ) {

            dbg( "e20rProgram::startdate() - Loading new program {$program_id} in place of {$currentProgram->id}" );
            $this->model->loadSettings( $program_id );
        }

        if ( ( empty($program_id) ) ) {

            // dbg( "e20rProgram::startdate() - Loading program for user with ID: {$userId}" );
            $this->getProgramIdForUser( $userId );

/*            if ( ( $currentProgram->id != $program_id ) && ( $program_id !== false ) ) {

                dbg( "e20rProgram::startdate() - User program not set! Loading settings by passed Program ID: {$program_id}" );
                $this->model->loadSettings( $program_id );
            }
*/
        }

        dbg("e20rProgram::startdate() - Using startdate as configured for program with id {$currentProgram->id}: {$currentProgram->startdate}");
        // dbg($currentProgram);

        // This is a date of the 'Y-m-d' PHP format. (eg 2015-01-01).
        return strtotime( $currentProgram->startdate );
    }

    /**
     * @param $userId - The USER id
     *
     * @return bool -- DIE()s if we're unable to save the settings.
     */
    public function updateProgramForUser( $userId ) {

        global $currentProgram;

        if ( ! current_user_can( 'edit_user', $userId ) ) {
            return false;
        }

        $programId = isset( $_POST['e20r-tracker-user-program'] ) ? intval( $_POST['e20r-tracker-user-program'] ) : 0;

        dbg("e20rProgram::updateProgramForUser() - Setting program ID = {$programId} for user with ID of {$userId}");

        if ( $programId != 0 ) {

            update_user_meta( $userId, 'e20r-tracker-program-id', $programId );

            if ( get_user_meta( $userId, 'e20r-tracker-program-id', true ) != $programId ) {

                wp_die( 'Unable to save the program for this user' );
            }

            dbg("e20rProgram::updateProgramForUser() - Testing whether to add user to program list");

            if ( !isset( $currentProgram->id ) || ( $programId != $currentProgram->id ) ) {

                $this->init($programId);
            }

            $currentProgram->users[] = $userId;

            if ( !in_array( $userId, $currentProgram->users ) ) {
                dbg("e20rProgram::updateProgramForUser() - Adding user to the program 'users' list");
                $this->model->set('users', $currentProgram->users, $currentProgram->id);
            }
        }
    }

    protected function loadDripFeed( $feedId ) {

        if ( $feedId == 'all' ) {
            $id = null;
        }
        else {
            $id = $feedId;
        }

        if ( class_exists( 'PMProSequence' ) ) {

            $dripFeed = new PMProSequence();
            return $dripFeed->getAllSequences('publish');
        }

        return false;
    }
    /********************** OBSOLETE ***************************/

    /**
     * Function renders the page to add/edit/remove programs from the E20R tracker plugin
     */
    public function render_submenu_page() {

        dbg("e20rProgram::render_submenu_page() - Loading program list...");
        $this->init();

        ?><div id="e20r-program-list"><?php

        echo $this->view->view_listPrograms();

        ?></div><?php
    }

    public function getValue( $fieldName = 'id' ) {

        return $this->model->getFieldValue( $fieldName );
    }

} 