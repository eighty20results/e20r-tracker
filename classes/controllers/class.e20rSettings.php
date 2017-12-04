<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rSettings {
	
	/**
	 * @var null|e20rProgramModel|e20rClientModel|e20rMeasurementModel|e20rWorkoutModel|e20rAssignmentModel|e20rArticleModel|e20rActionModel
	 */
    protected $model;
    protected $view;

    protected $cpt_slug;
    protected $type;

    private static $instance = null;

    protected function __construct( $type = null, $cpt_slug = null, $model = null , $view = null ) {

        $this->cpt_slug = $cpt_slug;
        $this->type = $type;

        $this->model = $model;
        $this->view = $view;
    }

	/**
	 * @return e20rSettings
	 */
	static function getInstance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

    protected function init( $postId = null ) {

        if ( ! $postId ) {
            return false;
        }

	    dbg("e20r" . ucfirst($this->type) . "::init() - Loading basic {$this->type} settings for id: {$postId}");

        $settingsId = get_post_meta( $postId, "_e20r-{$this->type}-id", true);

        if ( $settingsId == false ) {

	        $settingsId = $postId;
        }

	    dbg("e20r" . ucfirst($this->type) . "::init() - Loaded {$this->type} id: {$settingsId}");

	    if ( false === ( $settings = $this->model->loadSettings( $settingsId ) ) ) {

            dbg("e20r" . ucfirst($this->type) . "::init() - FAILED to load settings for {$settingsId}");
            return false;
        }

	    $settings->id = $settingsId;

        return $settings;
    }

    public function find( $key, $value, $programId = -1, $comp = '=', $order = 'DESC', $dataType = 'numeric' ) {

        return $this->model->find( $key, $value, $programId, $comp, $order, $dataType);
    }

    public function findByName( $shortName ) {

        $list = $this->model->loadAllSettings( 'any' );
        $key = false;

	    if (empty($list))
		    return false;

        foreach ($list as $settings ) {

            if ( isset($settings->short_name) ) {
                $key = 'short_name';
            }

            if ( isset( $settings->program_shortname ) ) {
                $key = 'program_shortname';
            }

            if ( ! $key ) {
                return false;
            }

            if ( $settings->{$key} == $shortName ) {
                unset($list);
                return $settings;
            }
        }

        unset($list);

        return false; // Returns false if the requested settings aren't found
    }

    public function getAll() {

        return $this->model->loadAllSettings();

    }

    public function getSettings( $id ) {

        return $this->model->loadSettings( $id );
    }

    public function editor_metabox_setup( $post ) {

        $e20rTracker = e20rTracker::getInstance();

        $title =  ucfirst( $this->type ) . ' Settings';

        add_meta_box("e20r-tracker-{$this->type}-settings", __($title, 'e20r-tracker'), array(&$this, "addMeta_Settings"), $this->cpt_slug, 'normal', 'high');

    }

    public function get_cpt_slug() {

        return $this->cpt_slug;
    }

    public function get_cpt_type() {

        return $this->type;
    }

    public function get_defaults() {

        return $this->model->defaultSettings();
    }
    /*
    public function getID( $userId = null ) {

        if (is_null( $userId ) ) {
            global $current_user;

            $userId = $current_user->ID;
        }
*/
        /*
         * Fetch all programs this user is a member of.
         *
         * On user sign-up (to Nourish), select the program that has a startdate that matches the membership level.
         *
         * Set program_id in wp_usermeta ('e20r-tracker-program')
         *
         * in_array( user->membership_level, $e20rPrograms->levels ) or in_array( 'e20r-tracker-program'  + member_level->ID for user ==> program
         * Set PROGRAM ID when lesson (or measurement page) is being loaded.
         */
/*            return $this->model->id;
    }
*/
    public function addMeta_Settings() {

        global $post;

        $this->init( $post->ID );

	    remove_meta_box( 'postexcerpt', $this->cpt_slug, 'side' );
	    remove_meta_box( 'wpseo_meta', $this->cpt_slug, 'side' );
	    add_meta_box('postexcerpt', __( ucfirst($this->type) . ' Summary'), 'post_excerpt_meta_box', $this->cpt_slug, 'normal', 'high');

        // dbg("e20rSettings::addMeta_Settings() - Loading metabox for {$this->type} settings");
        echo $this->view->viewSettingsBox( $this->model->loadSettings( $post->ID ), $this->loadDripFeed( 'all' ) );
    }

    public function saveSettings( $post_id ) {

        global $post;
	
	    $e20rTracker = e20rTracker::getInstance();

        if ( (! isset( $post->post_type ) ) || ( $post->post_type != $this->model->get_slug()) ) {
            dbg("e20r" .ucfirst($this->type) . "::saveSettings() - Incorrect post type for " . $this->model->get_slug());
            return $post_id;
        }

        if ( empty( $post_id ) ) {
            dbg("e20r" .ucfirst($this->type) . "::saveSettings() - No post ID supplied");
            return false;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return $post_id;
        }

        if ( defined( 'DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return $post_id;
        }

        dbg("e20r" . ucfirst($this->type) . "::saveSettings()  - Saving metadata for the {$this->type} post type");
        $this->model->init( $post_id );

        $settings = $this->model->loadSettings( $post_id );
        $defaults = $this->model->defaultSettings();

        if ( ! $settings ) {

	        dbg("e20r" . ucfirst($this->type) . "::saveSettings()  - No previous settings found. Using defaults!");
            $settings = $defaults;
        }

        foreach( $settings as $field => $setting ) {

	        $tmp = isset( $_POST["e20r-{$this->type}-{$field}"] ) ? $e20rTracker->sanitize( $_POST["e20r-{$this->type}-{$field}"] ) : null;

            dbg( "e20r" . ucfirst( $this->type ) . "::saveSettings() - Being saved : {$field} -> " );
	        dbg($tmp);

            if ( empty( $tmp ) ) {

                $tmp = $defaults->{$field};
            }

            $settings->{$field} = $tmp;
        }

        // Add post ID (checkin ID)
        $settings->id = isset( $_REQUEST["post_ID"] ) ? intval( $_REQUEST["post_ID"] ) : null;

        dbg("e20r" .ucfirst($this->type) . "::saveSettings() - Saving: " . print_r( $settings, true ) );

        if ( ! $this->model->saveSettings( $settings ) ) {

            dbg("e20r" .ucfirst($this->type) . "::saveSettings() - Error saving settings!");
        }

    }

    public function addPrograms( $gid, $programIds ) {

        dbg("e20r" .ucfirst($this->type) . "::addProgram() - Adding " . count($programIds) . " programs to $this->type {$gid}");

        $settings = $this->model->loadSettings( $gid );

        // Clean up settings with empty program id values.
        foreach( $settings->program_ids as $k => $value ) {

            if ( empty($value ) || ( 0 == $value ) ) {
                unset( $settings->program_ids[$k]);
            }
        }

        foreach( $programIds as $pId ) {

            if ( !in_array( $pId, $settings->program_ids ) ) {

                dbg("e20r" .ucfirst($this->type) . "::addProgram() - Adding program {$pId} to {$this->type} {$gid}");
                $settings->program_ids[] = $pId;
            }
        }

        return $this->model->saveSettings( $settings );
    }

	public function getPeers( $id = null ) {

		if ( is_null( $id ) ) {

			global $post;
			// Use the parent value for the current post to get all of its peers.
			$id = $post->post_parent;
		}

		$items = new WP_Query( array(
			'post_type' => 'page',
			'post_parent' => $id,
			'posts_per_page' => -1,
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'fields' => 'ids',
		) );

		$itemList = array(
			'pages' => $items->posts,
		);

		foreach ( $itemList->posts as $k => $v ) {

			if ( $v == get_the_ID() ) {

				if( isset( $items->posts[$k-1] ) ) {

					$itemList['prev'] = $items->posts[ $k - 1 ];
				}

				if( isset( $items->posts[$k+1] ) ) {

					$itemList['next'] = $items->posts[ $k + 1 ];
				}
			}
		}

        wp_reset_postdata();

		return $itemList;
	}
}