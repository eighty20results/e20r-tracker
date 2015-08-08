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
    // protected $settings;

    public function __construct() {

        parent::__construct( 'article', 'e20r_articles' );
    }

    public function defaultSettings() {

        $defaults = parent::defaultSettings();

        $defaults->id = null;
        $defaults->program_ids = array();
        $defaults->post_id = null;
        $defaults->activity_id = array();
        $defaults->release_day = null;
        $defaults->release_date = null;
        $defaults->assignment_ids = array();
        $defaults->checkin_ids = array();
		$defaults->is_survey = false;
//        $defaults->assignments = array();
//        $defaults->checkins = array();
        $defaults->measurement_day = false;
        $defaults->photo_day = false;
        $defaults->prefix = "Lesson";

        // dbg("e20rArticleModel::defaultSettings() - Defaults loaded");
        return $defaults;
    }

	public function loadSettings( $id ) {

        global $currentArticle;

        if ( -9999 === $id ) {
            dbg("e20rArticleModel::loadSettings() - Loading default for the NULL article ID (-9999)");
            $currentArticle = $this->defaultSettings();
            return $currentArticle;
        }

		$currentArticle = parent::loadSettings($id);

		if ( empty( $currentArticle->program_ids ) ) {

            $currentArticle->program_ids = array();
		}

		if ( empty( $currentArticle->activity_id ) ) {

            $currentArticle->activity_id = array();
		}

		if ( empty( $currentArticle->assignment_ids ) ) {

            $currentArticle->assignment_ids = array();
		}
		else {
            dbg("e20rArticleModel::loadSettings() - Found preconfigured assignments.");
			foreach( $currentArticle->assignment_ids as $k => $assignmentId ) {

				if ( empty( $assignmentId ) ) {

					dbg("e20rArticleModel::loadSettings() - Removing empty assignment key #{$k} with value " . empty( $assignmentId ) ? 'null' : $assignmentId );
					unset( $currentArticle->assignment_ids[$k] );
				}
			}

		}

		if ( empty( $currentArticle->checkin_ids ) ) {

            $currentArticle->checkin_ids = array();
		}
        else {
            dbg("e20rArticleModel::loadSettings() - Found preconfigured assignments.");
            foreach( $currentArticle->checkin_ids as $k => $checkinId ) {

                if ( empty( $checkinId ) ) {

                    dbg("e20rArticleModel::loadSettings() - Removing empty assignment key #{$k} with value " . empty( $checkinId ) ? 'null' : $checkinId );
                    unset( $currentArticle->checkin_ids[$k] );
                }
            }

        }


        // Check if the post_id has defined excerpt we can use for this article.
		if ( isset( $currentArticle->post_id ) && ( ! empty($currentArticle->post_id ) ) ) {

			$post = get_post( $currentArticle->post_id );
			setup_postdata( $post );

			$article = get_post( $currentArticle->id );
			setup_postdata( $article );

			if ( ! empty( $post->post_excerpt ) && ( empty( $article->post_excerpt ) ) ) {

				$article->post_excerpt = $post->post_excerpt;
				wp_update_post( $article );
			}

		}

        /*
        if ( isset( $currentArticle->id ) && ( !is_null( $currentArticle->id )) ) {
            $currentArticle = $this->settings;
        }
        */
        // dbg( $currentArticle );
		return $currentArticle;
	}

	/*    public function getProgramID() {

			global $current_user;
			global $currentProgram;

			$userPrograms = get_user_meta( $current_user->ID, '_e20r-user-programs' );

			if ( $userPrograms == false ) {
				return false;
			}

			// Combination of program from usermeta & the $settings-Programs;
		}
	*/

	public function find( $key, $value, $dataType = 'numeric', $programId = -1, $comp = 'LIKE', $order = 'DESC', $dont_drop = false ) {

		$result = parent::find( $key, $value, $dataType, $programId, $comp, $order );

		foreach( $result as $k => $data ) {

			if ( ( -9999 == $data->release_day ) && ( $dont_drop == false ) ) {
				// Dropping articles containing the "Always released" indicator ( -9999 )
				dbg("e20rArticleModel::find() - Dropping article {$data->id} since it's a 'default' article");
				unset( $result[$k]);
			}
		}

		return $result;
	}

	public function findClosestArticle( $key, $value, $programId = -1, $comp = '<=', $limit = 1, $type = 'numeric', $sort_order = 'DESC' ) {

		$args = array(
			'posts_per_page' => $limit,
			'post_type' => 'e20r_articles',
			'post_status' => 'publish',
			'order_by' => 'meta_value_num',
			'meta_key' => "_e20r-article-{$key}",
			'order' => $sort_order,
			'meta_query' => array(
				array(
					'key' => "_e20r-article-{$key}",
					'value' => $value,
					'compare' => $comp,
					'type' => $type,
				),
				array(
					'key' => "_e20r-article-program_ids",
					'value' => $programId,
					'compare' => '='
				),
                array(
                    'key' => "_e20r-article-release_day",
                    'value' => -9999,
                    'compare' => '!='
                )
			),
		);

		$a_list = $this->loadForQuery( $args );
/*
		if ( is_array( $a_list ) ) {

			foreach( $a_list as $k => $a ) {

				$programs = get_post_meta( $a->id, "_e20r-article-programs", true );

				if ( ( -9999 == $a->release_day ) || $this->inProgram( $programId, '_e20r-article-programs', $programs ) ) {
						unset( $a_list[$k] );
				}
			}
		}
		else {

			$programs = get_post_meta( $a_list->id, "_e20r-article-programs", true);

			if ( ( -9999 == $a_list->release_day ) || $this->inProgram( $programId, '_e20r-article-programs', $programs ) ) {
				$a_list = false;
			}
		}
*/
		dbg("e20rArticleModel()::findClosestArticle() - List of articles:");
		dbg( $a_list );

		return $a_list;
	}

    public function findArticle($key, $value, $type = 'NUMERIC', $programId = -1, $comp = '=', $order = 'DESC', $multi = NULL ) {

	    $article = null;

        // $key, $value, $dataType = 'numeric', $programId = -1, $comp = 'LIKE', $order = 'DESC'
        $list = parent::find( $key, $value, $type, $programId, $comp, $order );

        /*
	    if ( $key != 'id' ) {
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
					    'compare' => $comp,
					    'type' => $type,
				    ),
				    array(
					    'key' => "_e20r-article-program_ids",
					    'value' => $programId,
					    'compare' => '='
				    ),
                    array(
                        'key' => "_e20r-article-release_day",
                        'value' => -9999,
                        'compare' => '!='

                    )
                )
		    );
	    }
	    else {
		    $args = array(
			    'posts_per_page' => -1,
			    'post_type' => 'e20r_articles',
			    // 'post_status' => 'publish',
			    'p' => $value,
		    );
	    }
*/
/*
        $articleList = array();
	    dbg( $args );

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

        $list = $this->loadForQuery( $args );
*/
	    dbg("e20rArticleModel::findArticle() - Loaded " . count($list) . " articles");

	    foreach ( $list as $a ) {

		    if ( ( -9999 != $a->release_day ) && ( $programId !== -1 ) ) {

			    dbg( "e20rArticleModel::findArticle() - Returning {$a->id} because it matches program ID {$programId}" );
			    $article[] = $a;
		    }

/*            if ( ( !is_null( $multi ) ) &&
				( ( $programId !== -1 ) && ( isset( $a->programs ) && in_array( $programId, $a->programs ) ) ) ) {

				dbg( "e20rArticleModel::findArticle() - Returning more than one article for program ID == {$programId}" );
				$article[] = $a;
			}
*/
	    }

        if ( count( $article) == 1 ) {
            $article = array_pop( $article );
        }

	    return empty( $article ) ? $list : $article;
    }

	private function loadForQuery( $args ) {

		$articleList = array();

		$query = new WP_Query( $args );

		dbg("e20rArticleModel::loadForQuery() - Returned articles: {$query->post_count}" );

		while ( $query->have_posts() ) {

			$query->the_post();

			$new = $this->loadSettings( get_the_ID() );
			$new->id = get_the_ID();

			$articleList[] = $new;
		}

        wp_reset_postdata();

		return $articleList;
	}

    // TODO: Get rid of this and use $currentArticle instead.
    public function getSettings() {

        global $currentArticle;

        return $currentArticle;
    }

    /**
     * Save the Article Settings to the metadata table.
     *
     * @param $settings - Array of settings for the specific article.
     *
     * @return bool - True if successful at updating article settings
     */
    public function saveSettings( stdClass $settings ) {

		global $e20rAssignment;
        global $e20rCheckin;

        $articleId = $settings->id;

        $defaults = self::defaultSettings();

        dbg("e20rArticleModel::saveSettings() - Saving article Metadata" );

        $error = false;

        foreach ( $defaults as $key => $value ) {

            if ( in_array( $key, array( 'id' ) ) ) {
                continue;
            }

            if ( 'post_id' == $key ) {

                dbg("e20rArticleModel::saveSettings() - Saving the article ID with the post ");
                update_post_meta( $settings->{$key}, '_e20r-article-id', $articleId );
            }

	        // if ( 'assignments' == $key ) {
            if ( ( 'assignment_ids' == $key ) || ( 'checkin_ids' == $key ) ) {

                dbg("e20rArticleModel::saveSettings() - Processing assignments (include program info):");
                dbg($settings->{$key});

		        foreach( $settings->{$key} as $k => $id ) {

			        if ( empty( $id ) || ( 0 == $id ) ) {

				        dbg("e20rArticleModel::saveSettings() - Removing empty assignment key #{$k} with value {$id}");
				        unset( $settings->{$key}[$k] );
			        }

                    dbg("e20rArticleModel::saveSettings() - Adding program IDs for assignment {$id}");

					if ( ( 'assignment_ids' == $key ) && (! $e20rAssignment->addPrograms( $id, $settings->program_ids ) ) ) {

                        dbg("e20rArticleModel::saveSettings() - ERROR: Unable to save program list for assignment {$id}");
                        dbg($settings->program_ids);
                    }

                    if ( ( 'checkin_ids' == $key ) && (! $e20rCheckin->addPrograms( $id, $settings->program_ids ) ) ) {

                        dbg("e20rArticleModel::saveSettings() - ERROR: Unable to save program list for checkin {$id}");
                        dbg($settings->program_ids);
                    }
		        }
	        }

            if ( false === $this->settings( $articleId, 'update', $key, $settings->{$key} ) ) {

                dbg( "e20rArticleModel::saveSettings() - ERROR saving {$key} setting ({$settings->{$key}}) for article definition with ID: {$articleId}" );

                $error = true;
            }
        }

        return ( !$error ) ;
    }

}