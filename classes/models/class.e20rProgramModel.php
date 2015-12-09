<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rProgramModel extends e20rSettingsModel {

	protected $settings;

    public function __construct() {

        parent::__construct( 'program', 'e20r_programs');

    }

    public function findByMembershipId( $mID ) {

        $args = array(
            'posts_per_page' => -1,
            'post_type' => $this->cpt_slug,
            'post_status' => apply_filters( 'e20r-tracker-model-data-status', array( 'publish' )),
            'order_by' => 'meta_value',
            'meta_query' => array(
                array(
                    'key' => "_e20r-program-group",
                    'value' => $mID,
                    'compare' => '=',
                ),
            )
        );

        $query = new WP_Query( $args );

        dbg("e20rProgramModel::findByMembershipId() - Returned: {$query->post_count} programs for group w/ID: {$mID}" );
        // dbg($query);

        if ( $query->post_count == 0 ) {

            dbg("e20rProgramModel::findByMembershipId() - Error: No program IDs returned?!?" );
            return false;
        }

        if ( $query->post_count > 1 ) {
            dbg("e20rProgramModel::findByMembershipId() - Error: Incorrect program/membership definition! More than one entry was returned" );
            return false;
        }

        while ( $query->have_posts() ) {

            $query->the_post();

            $pId = get_the_ID();

            // $new = $this->loadSettings( get_the_ID() );
            // $new->id = get_the_ID();

            // $pList[] = $new;
        }

        wp_reset_postdata();

        dbg("e20rProgramModel::findByMembershipId() - Located program # {$pId}" );

        return $pId;
    }

    public function defaultSettings() {

        global $post;

        $settings = parent::defaultSettings();
	    $settings->id = -1;
        $settings->program_shortname = ( isset( $post->post_name ) ? $post->post_name : null );
        $settings->startdate = date_i18n( 'Y-m-d h:i:s', current_time('timestamp') );
        $settings->enddate = null;
	    $settings->intake_form = null;
        $settings->incomplete_intake_form_page = null;
        $settings->measurement_day = 6;
        $settings->activity_page_id = null;
        $settings->dashboard_page_id = null;
        $settings->measurements_page_id = null;
        $settings->progress_page_id = null;
        $settings->sales_page_ids = array();
        $settings->welcome_page_id = null;
        $settings->contact_page_id = null;
        $settings->account_page_id = null;
        $settings->group = -1;
        $settings->users = array(); // TODO: Figure out how to add current_user->ID to  this array.
        $settings->female_coaches = array();
        $settings->male_coaches = array();
        $settings->sequences = array();
        $settings->title = null;
        $settings->excerpt = null;
        
        return $settings;
    }

    public function load_program_members( $programId ) {

        global $e20rTracker;

        dbg("e20rProgram::load_program_members() - Loading users with Program ID: {$programId}");

        if ( 0 == $programId ) {
            $args = array(
                'order_by' => 'display_name'
            );

        }
        elseif ( -1 == $programId ) {

            return array();
        }
        else {
            $args = array(
                'meta_key' => 'e20r-tracker-program-id',
                'meta_value' => $programId,
                'order_by' => 'user_nicename'
            );

        }

        $user_list = get_users( $args );

        foreach( $user_list as $k => $u ) {

            if ( !$e20rTracker->isActiveClient( $u->ID ) ) {
                unset( $user_list[$k] );
            }
        }

        dbg("e20rProgram::load_program_members() - User Objects returned: " . count( $user_list ) );

        return $user_list;
    }

    /**
     * Save the Program Settings to the metadata table.
     *
     * @param $settings - Array of settings for the specific program.
     *
     * @return bool - True if successful at updating program settings
     */
    public function saveSettings( $settings ) {

        $programId = $settings->id;

        $defaults = $this->defaultSettings();

        dbg("e20rProgramModel::saveSettings() - Saving program Metadata: " . print_r( $settings, true ) );

        $error = false;

        foreach ( $defaults as $key => $value ) {

            if ( in_array( $key, array( 'id', 'program_shortname', 'title', 'excerpt', 'active_delay', 'previous_delay' ) ) ) {
                continue;
            }

            if ( false === $this->settings( $programId, 'update', $key, $settings->{$key} ) ) {

                dbg( "e20rProgram::saveSettings() - ERROR saving {$key} setting ({$settings->{$key}}) for program definition with ID: {$programId}" );

                $error = true;
            }
        }

        return ( !$error ) ;
    }

	public function loadSettings( $id ) {

		global $post;

        global $e20rTracker;

		global $currentProgram;

		if ( ! empty( $currentProgram ) && ( $currentProgram->id == $id ) ) {

			return $currentProgram;
		}

		if ( $id == 0 ) {

			$this->settings              = $this->defaultSettings( $id );
			$this->settings->id          = $id;

		} else {

			$savePost = $post;

			$this->settings = parent::loadSettings( $id );

            if ( ! is_array( $this->settings->sequences ) ) {
                $this->settings->sequences = array(
                    !empty( $this->settings->sequences ) ? array( $this->settings->sequences ) : array()
                );
            }

            if ( !is_array( $this->settings->male_coaches ) ) {
                $this->settings->male_coaches = array(
                    !empty( $this->settings->male_coaches ) ? array( $this->settings->male_coaches ) : array()
                );
            }

            if ( !is_array( $this->settings->female_coaches ) ) {
                $this->settings->female_coaches = array(
                    !empty( $this->settings->female_coaches ) ? array( $this->settings->female_coaches ) : array()
                );
            }

            $post = get_post( $id );
			setup_postdata( $post );

			if ( ! empty( $post->post_title ) ) {

				$this->settings->excerpt       = $post->post_excerpt;
				$this->settings->title    = $post->post_title;
				$this->settings->id          = $id;
			}

            if ( !isset( $this->settings->users ) || empty( $this->settings->users ) ) {

                $this->settings->users = array();
            }

			wp_reset_postdata();
			$post = $savePost;
		}

        $this->settings->active_delay = $e20rTracker->getDelay('now');
        $this->settings->previous_delay = null;

		$currentProgram = $this->settings;
		return $currentProgram;
	}

    /**
     * Save program settings to the post_meta table.
     *
     * @param $data - Array of data to insert/update/delete.
     * /
    public function saveProgram( $data ) {

        global $wpdb;

        if ( ( ! isset( $data['id'] ) ) || ( $data['id'] === null ) ) {

            // New program being added.
            dbg( "e20rProgramModel::saveProgram() - ADDING: " . print_r( $data, true ) );

            if ( false === $wpdb->insert( $this->table, $data ) ) {

                dbg("e20rProgramModel::saveProgram() - ERROR on ADD: {$wpdb->last_error}" );
            }

        } elseif ( $this->recordExists( $data['id'] )
                   && ( isset( $data['program_shortname'] ) ) ) {

            dbg( "e20rProgramModel::saveProgram() - UPDATING: " . print_r( $data, true ) );
            $where = array( 'id' => $data['id'] );

            if ( false === $wpdb->update( $this->table, $data, $where, array( '%d' ) ) ) {

                dbg("e20rProgramModel::saveProgram() - ERROR on UPDATE: {$wpdb->last_error}" );
            }
        } elseif ( ( $this->recordExists( $data['id'] ) ) && ( ! isset($data['program_shortname'])) ) {

            dbg( "e20rProgramModel::saveProgram() - Deleting record ({$data['id']})" );

            if ( false === $wpdb->delete( $this->table, array( 'id' => $data['id'] ) ) ) {

                dbg("e20rProgramModel::saveProgram() - ERROR on DELETE: {$wpdb->last_error}" );
            }
        }
    }
    */
}