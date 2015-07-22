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
        $this->settings->programs = array();
        $this->settings->post_id = null;
        $this->settings->activity_id = array();
        $this->settings->release_day = null;
        $this->settings->release_date = null;
        $this->settings->assignments = array();
        $this->settings->checkins = array();
        $this->settings->measurement_day = false;
        $this->settings->photo_day = false;
        $this->settings->prefix = "Lesson";

        // dbg("e20rArticleModel::defaultSettings() - Defaults loaded");
        return $this->settings;
    }

	public function loadSettings( $id ) {

        global $currentArticle;

		$this->settings = parent::loadSettings($id);

		if ( empty( $this->settings->programs ) ) {

			$this->settings->programs = array();
		}

		if ( empty( $this->settings->activity_id ) ) {

			$this->settings->activity_id = array();
		}

		if ( empty( $this->settings->assignments ) ) {

			$this->settings->assignments = array();
		}
		else {
            dbg("e20rArticleModel::loadSettings() - Found preconfigured assignments.");
			foreach( $this->settings->assignments as $k => $assignmentId ) {

				if ( empty( $assignmentId ) ) {

					dbg("e20rArticleModel::loadSettings() - Removing empty assignment key #{$k} with value " . empty( $assignmentId ) ? 'null' : $assignmentId );
					unset( $this->settings->assignments[$k] );
				}
			}

		}

		if ( empty( $this->settings->checkins ) ) {

			$this->settings->checkins = array();
		}

		// Check if the post_id has defined excerpt we can use for this article.
		if ( isset( $this->settings->post_id ) && ( ! empty($this->settings->post_id ) ) ) {

			$post = get_post( $this->settings->post_id );
			setup_postdata( $post );

			$article = get_post( $this->settings->id );
			setup_postdata( $article );

			if ( ! empty( $post->post_excerpt ) && ( empty( $article->post_excerpt ) ) ) {

				$article->post_excerpt = $post->post_excerpt;
				wp_update_post( $article );
			}

		}

        if ( isset( $currentArticle->id ) && ( !is_null( $currentArticle->id )) ) {
            $currentArticle = $this->settings;
        }
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
/*				array(
					'key' => "_e20r-article-programs",
					'value' => $programId,
					'compare' => 'IN'
				) */
			),
		);

		$a_list = $this->loadForQuery( $args );

		if ( is_array( $a_list ) ) {

			foreach( $a_list as $k => $a ) {

				$programs = get_post_meta( $a->id, "_e20r-article-programs", true );

				if ( ! in_array( $programId, $programs ) ) {
						unset( $a_list[$k] );
				}
			}
		}
		else {

			$programs = get_post_meta( $a_list->id, "_e20r-article-programs", true);

			if ( ! in_array( $programId, $programs ) ) {
				$a_list = false;
			}
		}

		dbg("e20rArticleModel()::findClosestArticle() - List of articles:");
		dbg( $a_list );

		return $a_list;
	}

    public function findArticle($key, $value, $type = 'NUMERIC', $programId = -1, $comp = '=', $multi = NULL ) {

	    $article = null;

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
/*				    array(
					    'key' => "_e20r-article-programs",
					    'value' => $programId,
					    'compare' => 'IN'
				    ) */
			    )
		    );
	    }
	    else {
		    $args = array(
			    'posts_per_page' => -1,
			    'post_type' => 'e20r_articles',
			    /* 'post_status' => 'publish', */
			    'p' => $value,
		    );
	    }

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
*/
        $list = $this->loadForQuery( $args );

	    dbg("e20rArticleModel::findArticle() - Loaded " . count($list) . " articles");

	    foreach ( $list as $a ) {

		    if ( ( $programId !== -1 ) && ( isset( $a->programs ) && in_array( $programId, $a->programs ) ) ) {

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

/* 			if ( $query->post_count > 1 ) {

				$articleList[] = $new;
			}
			else {
				$articleList = $new;
			}
*/
			$articleList[] = $new;
		}

		return $articleList;
	}

    public function getSettings() {

        return $this->settings;
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

	        if ( 'assignments' == $key ) {

                dbg("e20rArticleModel::saveSettings() - Processing assignments (include program info):");
                dbg($value);

		        foreach( $settings->{$key} as $k => $assignmentId ) {

			        if ( empty( $assignmentId ) ) {

				        dbg("e20rArticleModel::saveSettings() - Removing empty assignment key #{$k} with value {$assignmentId}");
				        unset( $value[$k] );
			        }

                    dbg("e20rArticleModel::saveSettings() - Adding program IDs for assignment {$assignmentId}");
					if (! $e20rAssignment->addPrograms( $assignmentId, $settings->programs ) ) {

                        dbg("e20rArticleModel::saveSettings() - ERROR: Unable to save program list for assignment {$assignmentId}");
                        dbg($settings->programs);
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