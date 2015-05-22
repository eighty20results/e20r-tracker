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

        dbg("e20rArticleModel::defaultSettings() - Defaults loaded");
        return $this->settings;
    }

	public function loadSettings( $id ) {

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

		if ( empty( $this->settings->checkins ) ) {

			$this->settings->checkins = array();
		}

		return $this->settings;
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

    public function findArticle($key, $value, $type = 'numeric', $programId = -1, $comp = '=' ) {

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
			    'post_status' => 'publish',
			    'page_id' => $value,
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

	    dbg("e20rArticleModel::findArticle() - Found " . count($list) . " articles");

	    foreach ( $list as $a ) {

		    if ( isset( $a->programs ) && in_array( $programId, $a->programs ) ) {

			    dbg( "e20rArticleModel::findArticle() - Returned program ID == {$programId}" );
			    $article = $a;
		    }
	    }

	    return $article;
    }

	private function loadForQuery( $args ) {

		$articleList = array();

		$query = new WP_Query( $args );

		dbg("e20rArticleModel::loadForQuery() - Returned articles: {$query->post_count}" );

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