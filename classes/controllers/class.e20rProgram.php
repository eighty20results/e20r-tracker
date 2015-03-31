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

        global $post;
	    global $currentProgram;
	    global $current_user;

	    if ( is_user_logged_in() ) {

		    if ( ( ! isset( $currentProgram->id ) && !is_null( $programId ) ) ||
		         ( !is_null( $programId ) && ( $currentProgram->id != $programId ) ) ){

			    dbg("e20rProgram::init() - Loading program settings for {$programId}.");
			    $currentProgram = parent::init( $programId );
			    return true;
		    }

		    if ( ( is_null( $programId ) ) ) {

			    $pid = get_user_meta( $current_user->ID, 'e20r-tracker-program-id', true );

			    if ( ( $pid !== false ) && ( $pid != $programId ) ) {
				    dbg("e20rProgram::init() - Loading program settings based on user login {$programId}.");
				    $currentProgram = parent::init( $pid );
				    return true;
			    }
			    else {
				    $currentProgram = parent::init( $programId );
				    return true;
			    }
		    }

		    if ( isset( $currentProgram->id ) ) {

			    $this->programTree = $this->getPeerPrograms( $currentProgram->id );
		    }

		    dbg("e20rProgram::init() - Program ID: {$currentProgram->id}");

		    return true;
	    }

	    $currentProgram = new stdClass();
	    $currentProgram->id = false;

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

        return $this->programTree;
    }

    public function getProgramList() {

        $list = array();

        $programs = new WP_Query( array(
            'post_type' => 'e20r_programs',
            'posts_per_page' => -1,
            'post_status' => array( 'publish', 'private' ),
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'fields' => 'ids',
        ) );

        dbg("e20rProgram::getProgramList() - Loaded " . count($programs) . " program definitions from the DB");

        while( $programs->have_posts() ) {

            $programs->the_post();
            dbg("e20rProgram::getProgramList() - Adding ". get_the_title());
            $list[get_the_ID()] = get_the_title();
        }

        wp_reset_postdata();

        return $list;
    }

	public function get_welcomeSurveyLink( $userId ) {

		global $currentProgram;

		$this->loadProgram( $userId );

		$link = get_permalink( $currentProgram->intake_form );

		dbg("e20rProgram::get_welcomeSurveyLink(): Link: {$link}");

		return $link;
	}

    public function getProgramIdForUser( $userId = null, $articleId = null ) {

	    global $currentProgram;

	    if ( ! isset( $currentProgram->id ) || ( $currentProgram->id === false ) ) {

		    dbg("e20rProgram::getProgramIdForUser() - currentProgram->id is false or not set yet.");

		    if ( $userId != 0 ) {

			    dbg("e20rProgram::getProgramIdForUser() - Loading program info from DB for user w/ID {$userId}");
			    $this->loadProgram( $userId );
			    dbg("e20rProgram::getProgramIdForUser() - Loaded program settings for user w/ID {$userId}");
			    dbg($currentProgram);
		    }
	    }
	    else {

		    dbg("e20rProgram::getProgramIdForUser() - currentProgram has been loaded.");

		    if ( $currentProgram->id === false ) {
			    dbg("e20rProgram::getProgramIdForUser() - currentProgram getting set to default values");
			    $this->init();
		    }
	    }

	    return $currentProgram->id;
    }

	private function loadProgram( $userId = 0 ) {

		global $currentProgram;

		if ( isset( $currentProgram->id ) && ( $currentProgram->id === false ) ) {

			if ( is_user_logged_in() && ( $userId != 0 ) ) {

				$programId = get_user_meta( $userId, 'e20r-tracker-program-id', true );

				if ( $programId !== false ) {

					$this->init( $programId );
				}
			}
			dbg( "e20rProgram::loadProgram() - User's programID: " . isset( $currentProgram->id ) ? $currentProgram->id : 'null' );
		}
		else {
			$this->init();
		}
	}
    /**
     * Action Hook to add the E20R Tracker user specific settings (like adding a program for the user)
     *
     * @param $user -- WP_User object
     */
    public function selectProgramForUser( $user ) {

        $programlist = $this->getProgramList();
        $activeProgram = $this->getProgramIdForUser( $user->ID, null );

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
    public function startdate( $userId, $program_id = null ) {

	    global $currentProgram;

        if ( ( $currentProgram->id === false ) || ( $program_id != $currentProgram->id ) ) {

	        if ( $program_id == null ) {

		        $program_id = get_user_meta( $userId, 'e20r-tracker-program-id', true );
	        }

            if ( $program_id !== false ) {

		        dbg( "e20rProgram::startdate() - Loading settings for Program ID: {$program_id}" );
		        $this->model->loadSettings( $program_id );
		    }
        }

        if ( !isset( $currentProgram->startdate ) ) {

	        dbg( "e20rProgram::startdate() - NO valid program start date found for user with ID {$userId}" );

	        // No program setting was configured so we'll use the start date for the users active membership level.
	        if ( function_exists( 'pmpro_getMemberStartdate' ) ) {

		        dbg( "e20rProgram::startdate() - Using PMPro's member startdate for user ID {$userId}");
		        return pmpro_getMemberStartdate( $userId );
	        }

	        return false;
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

        if ( ! current_user_can( 'edit_user', $userId ) ) {
            return false;
        }

        dbg("e20rProgram::updateProgramForUser() - Setting program ID for user with ID of {$userId}");

        $programId = isset( $_POST['e20r-tracker-user-program'] ) ? intval( $_POST['e20r-tracker-user-program'] ) : 0;

        if ( $programId != 0 ) {

            update_user_meta( $userId, 'e20r-tracker-program-id', $programId );

            if ( get_user_meta( $userId, 'e20r-tracker-program-id', true ) != $programId ) {

                wp_die( 'Unable to save the program for this user' );
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