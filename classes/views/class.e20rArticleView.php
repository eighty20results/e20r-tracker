<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rArticleView extends e20rSettingsView {

    private $article = null;

    public function e20rArticleView( $data = null, $error = null ) {

        parent::__construct( 'article', 'e20r_articles' );

        $this->article = $data;
        $this->error = $error;
    }

	public function viewLessonComplete( $day, $articleId ) {

        global $currentArticle;
		ob_start();

        dbg("e20rArticleView::viewLessonComplete() -  Assignment is complete: " . ( ( isset( $currentArticle->complete ) && ( $currentArticle->complete) ) ? 'Yes' : 'No') );
        dbg($currentArticle);

        $prefix = preg_replace('/\[|\]/', '', $currentArticle->prefix );

        if ( true == $currentArticle->complete ) { ?>

        <div class="e20r-assignment-complete"><?php
        }
        else { ?>

        <div class="e20r-assignment-complete" style="display: none;"><?php
        } ?>
            <div class="green-notice big" style="background-image: url( <?php echo E20R_PLUGINS_URL;  ?>/img/checked.png ); margin: 12px 0pt; background-position: 24px 9px;">
                <p><strong><?php echo sprintf( __("You have completed this %s.", "e20rTracker"), lcfirst( $prefix ) ); ?></strong></p>
            </div>
        </div>
		<?php
		$html = ob_get_clean();

		return $html;
	}

A    public function view_article_history( $type, $title, $articles, $start, $end, $article_summary = null ) {

        global $currentProgram;

        $startdate = date( get_option('date_format'), $start );
        $enddate = date( get_option('date_format'), $end );

        ob_start(); ?>
        <div class="e20r-article-post-summary">
            <h5 class="e20r-article-post-summary-heading"><?php echo ( !empty($title) ? esc_attr($title) : sprintf( __("%s summary", "e20rtracker"), esc_attr( ucfirst($type) ) )); ?></h5>
            <p class="e20r-article-post-summary-dates"><?php echo sprintf( __( "For the period between %s and %s", "e20rtracker" ), $startdate, $enddate ); ?></p>
            <?php if ( !empty( $article_summary ) ) {?>
            <div class="e20r-article-post-summary-info"><?php echo esc_html( $article_summary); ?></div><?php
            }
            foreach( $articles as $article ) {
                $days = $article['day'] - 1;
                $timestamp = strtotime("{$currentProgram->startdate} +{$days} days");
                $day_name = date('l', $timestamp);
            ?>
            <div class="e20r-article-post-summary-tile">
                <p class="e20r-article-post-summary-about"><?php echo sprintf( __("On %s the %s was titled '%s' and we discussed how...", "e20rtracker" ), $day_name, $type, $article['title'] ); ?></p>
                <p class="e20r-article-post-summary-text"><?php echo esc_html( $article['summary'] ) ; ?></p>
            </div><?php
            } ?>
        </div><?php
        $html = ob_get_clean();

        return $html;
    }

    public function viewInterviewComplete( $page_title, $is_complete ) {

        ob_start();
        if ( $is_complete ) {
            ?>
            <div class="green-notice big"
                 style="background-image: url( <?php echo E20R_PLUGINS_URL; ?>/img/checked.png ); margin: 12px 0pt; background-position: 24px 9px;">
                <p class="e20r-completed-notice"><?php echo sprintf(__("Great, you have already saved this '%s' interview.", 'e20rtracker'), $page_title) ?></p>
                <p class="e20r-completed-notice"><?php _e("If you need to update any information, make sure to save it before you leave. Or you can simply navigate away from the page without updating anything.", "e20rTracker"); ?></p>
            </div>
            <?php
        }
        else { ?>
            <div class="red-notice big" style="background-image: url( <?php echo E20R_PLUGINS_URL; ?>/img/warning.png ); margin: 12px 0pt; background-position: 24px 9px;">
                <p class="e20r-completed-notice"><?php echo sprintf(__("We noticed you haven't completed this '%s' interview yet.", 'e20rtracker'), $page_title) ?></p>
                <p class="e20r-completed-notice"><?php _e("To help us better understand your health profile and fitness level, and take that information to help you achieve your health and fitness goals, please complete the interview now. Then save it.", "e20rTracker"); ?></p>
            </div>
            <?php
        }
        $html = ob_get_clean();

        return $html;

    }

    public function new_message_warning() {

        global $currentProgram;
        global $currentArticle;

        global $e20rAssignment;
        global $current_user;

        $unread_messages = $e20rAssignment->client_has_unread_messages( $current_user->ID );

        dbg("e20rArticleView::new_message_warning() - Found unread messages: {$unread_messages}");

        ob_start(); ?>
        <div class="e20r-new-message-alert orange-notice <?php echo ( 0 == $unread_messages ? 'startHidden' : null );  ?>">
            <input type="hidden" name="e20r-message-user-id" value="<?php echo $current_user->ID;?>" id="e20r-message-user-id">
            <input type="hidden" name="e20r-message-new-count" value="<?php echo $unread_messages; ?>" id="e20r-messages-previous-count">
            <h4><span class="highlighted"><?php _e("New message!", "e20rtracker"); ?></span></h4>

            <div class="e20r-tracker-new-message-alert-txt">
                <li><?php _e("Your coach has sent you a new message.", "e20rtracker");?> <a href="javascript:void(0);" id="e20r-read-messages-btn"><?php _e("Click to read message(s)", "e20rtracker"); ?> &raquo;</a></li>
                <form action="<?php echo get_permalink($currentProgram->progress_page_id); ?>" method="POST" id="e20r-start">
                    <input type="hidden" value="<?php echo ( isset( $currentArticle->id ) ? $currentArticle->id : null) ; ?>" name="e20r-progress-form-article" id="e20r-progress-form-article">
                </form>
                <button id="e20r-new-message-dismiss-button" class="e20r-dismiss-button button"><?php _e("Hide", "e20rtracker"); ?></button>
                <li>
                    <span class="e20r-help-description-text">
                        <?php _e("You can read and reply to your messages via the 'Assignments' tab on the 'Progress' page/tab.", "e20rtracker"); ?>
                        <?php _e("The assignment response row will be an <span class='orange-background'>orange row</span> wherever there are unread messages.", "e20rtracker"); ?>
                    </span>
                </li>

            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    public function viewMeasurementComplete( $day, $measurements = 0, $articleId ) {

        global $e20rTracker;
        global $currentProgram;

        // $postDate = $e20rTracker->getDateForPost( $day );
        // $progressLink = '<a href="' . home_url("/nutrition-coaching/weekly-progress/?for={$postDate}") . '" target="_blank">Click to edit</a> your measurements';

        ob_start();
        ?>
        <div class="green-notice big" style="background-image: url( <?php echo E20R_PLUGINS_URL; ?>/img/checked.png ); margin: 12px 0pt; background-position: 24px 9px;">
            <p><strong><?php _e("You have completed this lesson.", "e20rTracker"); ?></strong>
            <?php
                if ( $measurements !== 0 ) { ?>
                <a href="javascript:document.getElementById('e20r-start').submit();" id="e20r-begin-btn" style="display: inline;"><strong><?php _e("Update progress", "e20rTracker"); ?></strong>  &raquo;</a>
                <form action="<?php echo get_permalink( $currentProgram->measurements_page_id ); ?>" method="POST" id="e20r-start" style="display: none;">
                    <input type="hidden" value="<?php echo $e20rTracker->getDateForPost( $day ); ?>" name="e20r-progress-form-date" id="e20r-progress-form-date">
                    <input type="hidden" value="<?php echo $articleId; ?>" name="e20r-progress-form-article" id="e20r-progress-form-article">
                </form>

        <?php } ?>
            </p>
        </div>

        <?php
        $html = ob_get_clean();

        return $html;
    }

    public function viewMeasurementAlert( $photos, $day, $articleId = null ) {

        dbg("e20rArticleView::viewMeasurementAlert() - Photos: {$photos} for {$day}");
        global $e20rTracker;
        global $currentProgram;

        $html = null;

        if ( !empty( $currentProgram->measurements_page_id ) ) {

            ob_start();
            ?>
            <div id="saturday-progress-container" class="progress-container clearfix" style="margin-bottom: 16px;">
                <h3><?php _e("Weekly Progress", "e20rtracker");?> <span><?php _e("Update", "e20tracker"); ?></span></h3>

                <div id="e20r-progress-canvas">
                    <img src="<?php echo E20R_PLUGINS_URL; ?>/img/alert.png" class="tooltip-handle" data-tooltip=""
                         data-tooltip-mleft="-83" data-tooltip-mtop="126" id="weekly-alarm-clock"/>

                    <div class="e20r-weekly-progress-reminder-text">

                        <h4><span class="highlighted"><?php _e("We need your measurements.", "e20rtracker"); ?></span></h4>

                        <p><?php _e("Today is a measurement day. Here's what we need you to collect:", "e20rtracker"); ?></p>
                        <ul>
                            <li><?php _e("Body Weight", "e20rtracker"); ?></li>
                            <li><?php _e("Girth Measurements", "e20tracker"); ?></li>
                            <?php if ($photos == 1): ?>
                                <li><?php _e("Photos", "e20rtracker");?></li>
                            <?php endif; ?>
                        </ul>
                        <form action="<?php echo get_permalink($currentProgram->measurements_page_id); ?>" method="POST" id="e20r-start">
                            <input type="hidden" value="<?php echo $e20rTracker->getDateForPost($day); ?>" name="e20r-progress-form-date" id="e20r-progress-form-date">
                            <input type="hidden" value="<?php echo $articleId; ?>" name="e20r-progress-form-article" id="e20r-progress-form-article">
                        </form>
                        <a href="javascript:document.getElementById('e20r-start').submit();" id="e20r-begin-btn"><?php _e("Begin", "e20rtracker"); ?> &raquo;</a>
                    </div>
                </div>
            </div>
            <br/>
            <?php
            $html = ob_get_clean();
        }

        return $html;
    }

    public function viewCheckinSettings( $settings, $checkinList ) {

        global $post;
        global $e20rTracker;

        if ( ! current_user_can( 'manage_options ') ) {
            return false;
        }

        ob_start();
        ?>

        <form action="" method="post">
            <?php wp_nonce_field( 'e20r-tracker-data', 'e20r-tracker-article-settings-nonce' ); ?>
            <div class="e20r-editform">
                <table id="e20r-article-checkin-settings" class="wp-list-table widefat fixed">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label for="e20r-article-checkin_id"><?php _e("Check-In", "e20rtracker"); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-article-checkin_id"><?php _e("Check-In", "e20rtracker"); ?></label></th>
                    </tr>
                    </thead>
                </table>
            </div>
        </form>
        <?php
        $checkinMeta = ob_get_clean();

        return $checkinMeta;
    }

    public function viewArticleSettings( $settings, $lessons, $checkinList = null ) {

        global $post;
        global $e20rTracker;

        dbg($settings);

        $savePost = $post;

        if ( ! current_user_can( 'edit_posts' ) ) {
            return false;
        }

	    if ( ( !isset( $settings->program_ids ) ) || ( $settings->program_ids == null ) ) {
		    $settings->program_ids = array();
	    }

        ?>
        <form action="" method="post">
            <?php wp_nonce_field( 'e20r-tracker-data', 'e20r-tracker-article-settings-nonce' ); ?>
            <div class="e20r-editform">
                <input type="hidden" name="hidden-e20r-article-id" id="hidden-e20r-article-id"
                       value="<?php echo $post->ID; ?>">
                <table class="e20r-article-settings wp-list-table widefat">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label for="e20r-article-post_id"><?php _e("Post/Page", "e20rtracker"); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-article-release_day"><?php _e("Day of Release", "e20rtracker"); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-article-prefix"><?php _e("Prefix", "e20rtracker"); ?></label></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr id="<?php echo $settings->id; ?>" class="checkin-inputs">
                        <td style="width: 50%;">
                            <select class="select2-container" id="e20r-article-post_id" name="e20r-article-post_id">
                                <option value="0">None</option>
                                <?php
                                while( $lessons->have_posts() ) {
                                    $lessons->the_post(); ?>
                                <option value="<?php echo get_the_ID(); ?>"<?php selected( $settings->post_id, get_the_ID()); ?>><?php echo get_the_title(); ?>
                                    (#<?php echo get_the_ID(); ?>)
                                </option>
                                <?php } ?>
                            </select>
                            <style>
                                .select2-container {
                                    min-width: 150px;
                                    max-width: 600px;
                                    width: 99%;
                                }
                            </style>
                        </td>
                        <td style="min-width: 50px !important; width: 33%;">
                            <input type="number" id="e20r-article-release_day" name="e20r-article-release_day" value="<?php echo esc_attr( $settings->release_day ); ?>">
                        </td>
                        <td style="vertical-align: top;">
                            <input style="width: 100%;" type="text" id="e20r-article-prefix" name="e20r-article-prefix" value="<?php echo esc_attr( $settings->prefix ); ?>">
                        </td>
                    </tr>
                    </tbody>
                </table>
                <table class="e20r-article-settings wp-list-table widefat">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label for="e20r-article-summary_day"><?php _e("Summary Article", "e20rtracker"); ?></label></th>
                        <th colspan="2" class="e20r-label header"><label for="e20r-article-max_summaries"><?php _e("Articles to summarize", "e20rtracker"); ?></label></th>
                    </tr>
                    </thead>
                    <tbody>
                    <!-- <tr><td colspan="3"><hr width="100%" /></td></tr> -->
                    <tr>
                        <td class="checkbox"  style="text-align: center;">
                            <input type="checkbox" id="e20r-article-summary_day" name="e20r-article-summary_day" value="1"<?php checked( $settings->summary_day, 1); ?>>
                        </td>
                        <td colspan="2" style="min-width: 50px !important;">
                            <input type="number" id="e20r-article-max_summaries" name="e20r-article-max_summaries" value="<?php echo esc_attr( $settings->max_summaries ); ?>">
                        </td>
                    </tr>
                    </tbody>
                </table>
                <table class="e20r-article-settings wp-list-table widefat">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label for="e20r-article-measurement_day"><?php _e("Measurements", "e20rtracker"); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-article-photo_day"><?php _e("Pictures", "e20rtracker"); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-article-is_survey"><?php _e("Survey", "e20rtracker"); ?></label></th>
                    </tr>
                    </thead>
                    <tbody>
                    <!-- <tr><td colspan="3"><hr width="100%" /></td></tr> -->

                    <tr>
                        <td class="checkbox"  style="text-align: center;">
                            <input type="checkbox" id="e20r-article-measurement_day" name="e20r-article-measurement_day" value="1"<?php checked( $settings->measurement_day, 1); ?>>
                        </td>
                        <td class="checkbox"  style="text-align: center;">
                            <input type="checkbox" id="e20r-article-photo_day" name="e20r-article-photo_day" value="1"<?php checked( $settings->photo_day, 1); ?>>
                        </td>
                        <td class="checkbox"  style="text-align: center;">
                            <input type="checkbox" id="e20r-article-is_survey" name="e20r-article-is_survey" value="1"<?php checked( $settings->is_survey, 1); ?>>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <!-- <tr><td colspan="3"><hr width="100%" /></td></tr> -->
                <table class="e20r-article-settings wp-list-table widefat">
                    <thead>
                    <tr>
                        <th colspan="2" class="e20r-label header"><label for="e20r-article-program_ids"><?php _e("Programs", "e20rtracker"); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-article-is_preview_day"><?php _e("Preparation", "e20rtracker"); ?></label></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td colspan="2">
                            <select class="select2-container" id="e20r-article-program_ids" name="e20r-article-program_ids[]" multiple="multiple"> <?php

                                wp_reset_query();

                                $programs = new WP_Query( array(
                                    'post_type' => 'e20r_programs',
                                    'posts_per_page' => -1,
	                                'post_status' => 'publish',
                                    'order_by' => 'title',
                                    'order' => 'ASC',
                                    'fields' => array( 'ID', 'title')
                                ));

                                // $programs = $e20rTracker->getMembershipLevels();
                                dbg("e20rArticleView::viewArticleSettings() - Grabbed {$programs->post_count} programs");

                                while ( $programs->have_posts() ) {

                                    $programs->the_post();

                                    $selected = ( in_array( $programs->post->ID, $settings->program_ids ) ? ' selected="selected"' : null );
                                    ?><option value="<?php echo $programs->post->ID; ?>" <?php echo $selected; ?>><?php echo $programs->post->post_title; ?></option><?php
                                }

                                wp_reset_postdata();
                                ?>
                            </select>
                        </td>
                        <td class="checkbox"  style="text-align: center;">
                            <input type="checkbox" id="e20r-article-is_preview_day" name="e20r-article-is_preview_day" value="1"<?php checked( $settings->is_preview_day, 1); ?>>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <!-- <tr><td colspan="3"><hr width="100%" /></td></tr> -->
                <table class="e20r-article-settings wp-list-table widefat">
                    <thead>
                    <tr>
                        <th colspan="2" class="e20r-label header"><label for="e20r-article-action_ids"><?php _e("Actions", "e20rtracker"); ?></label></th>
	                    <th class="e20r-label header"><label for="e20r-article-activity_id"><?php _e("Activity", "e20rtracker"); ?></label></th>
                        <th></th>
                    </tr>
                    </thead>
                    <!-- <tr><td colspan="3"><hr width="100%" /></td></tr> -->
                    <tbody>
                    <tr>
                        <td colspan="2">
                            <select class="select2-container" id="e20r-article-action_ids" name="e20r-article-action_ids[]" multiple="multiple"><?php

                                wp_reset_query();

                                $checkins = new WP_Query( array(
                                    'post_type' => 'e20r_actions',
                                    'posts_per_page' => -1,
                                    'order_by' => 'title',
	                                'post_status' => 'publish',
                                    'order' => 'ASC',
                                    'fields' => array( 'id', 'title')
                                ));

                                // $programs = $e20rTracker->getMembershipLevels();
                                dbg("e20rArticleView::viewArticleSettings() - Grabbed " .$checkins->post_count . " checkin options");
                                // dbg("e20rArticleView::viewArticleSettings() - " . print_r( $checkins, true));

                                while ( $checkins->have_posts() ) {

                                    $checkins->the_post();

                                    $selected = ( in_array( $checkins->post->ID, $settings->action_ids ) ? ' selected="selected"' : null );
                                    ?><option value="<?php echo $checkins->post->ID; ?>" <?php echo $selected; ?>><?php echo $checkins->post->post_title; ?></option><?php
                                }

                                wp_reset_postdata();
                                ?>
                            </select>
                        </td>
	                    <td><?php
		                    if ( is_array( $settings->activity_id ) ) {

			                    $selected = in_array( -1, $settings->activity_id ) ? 'selected="selected"' : null;
		                    }
		                    else {

			                    $selected = ( -1 == $settings->activity_id ? 'selected="selected"' : null );
		                    }
		                    ?>
		                    <select class="select2-container" id="e20r-article-activity_id" name="e20r-article-activity_id[]" multiple="multiple">
			                    <option value="-1" <?php echo $selected ?>>No defined activity</option>
			                    <?php
			                    global $e20rWorkout;

			                    dbg("e20rArticleView::viewArticleSettings() - Load all defined activities");
			                    $activities = $e20rWorkout->getActivities();

			                    foreach( $activities as $activity ) {

				                    dbg("e20rArticleView::viewArticleSettings() - Activity definition: {$activity->id}");
				                    // dbg($activity);

				                    if ( is_array( $settings->activity_id ) ) {

					                    $selected = in_array( $activity->id, $settings->activity_id ) ? 'selected="selected"' : null;
				                    }
				                    else {

					                    $selected = ( $activity->id == $settings->activity_id ? 'selected="selected"' : null );
				                    }

				                    ?>
				                    <option value="<?php echo $activity->id; ?>"<?php echo $selected; ?>><?php echo $activity->title; ?></option><?php
			                    }
			                    ?>
		                    </select>
	                    </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </form>
        <?php

        $post = $savePost;
    }

    public function viewSettings_Checkin( $data ) {

        global $post;
        global $e20rTracker;

        if ( ! current_user_can( 'edit_posts' ) ) {
            return false;
        }

        ?>
        <div id="e20r-article-checkinlist">
            <?php $this->viewSettings_Checkin($data); ?>
        </div>
        <?php
    }

    public function viewSettingsBox( $data, $posts ) {

        global $post, $e20rTracker;


        dbg("e20rArticleView::viewArticleSettingsBox() - Supplied data: " . print_r($data, true));
        ?>
        <style>
            .select2-container { min-width: 75px; max-width: 250px; width: 100%;}
        </style>
        <script type="text/javascript">
            jQuery('.e20r-article-posts').select2({
                placeholder: "Select Post",
                allowClear: true
            });
        </script>
        <form action="" method="post">
            <?php wp_nonce_field('e20r-tracker-data', 'e20r-tracker-article-settings-nonce'); ?>
            <div class="e20r-editform" style="width: 100%;">
                <input type="hidden" name="hidden-e20r-article-id" id="hidden-e20r-article-id" value="<?php echo ( ( isset($data->ID) ) ? $data->ID : $post->ID ); ?>">
                <table id="e20r-article-settings" class="wp-list-table widefat fixed">
                    <tbody id="e20r-article-tbody">
                        <tr id="<?php echo $post->ID; ?>" class="article-inputs">
                            <td colspan="2">
                                <table class="sub-table wp-list-table widefat fixed">
                                    <tbody>
                                    <tr>
                                        <th class="e20r-label header" style="width: 20%;"><label for="e20r-article-article_id">Article (A/B/C/D)</label></th>
                                        <th class="e20r-label header" style="width: 40%;"><label for="e20r-article-phase">Phase (number)</label></th>
                                        <th class="e20r-label header" style="width: 40%;"><label for="e20r-article-rest">Rest between sets (Seconds)</label></th>
                                    </tr>
                                    <tr>
                                        <td class="select-input" style="width: 20%;">
                                            <select id="e20r-article-article_id">
                                                <option value="A" <?php selected( $data->article_id, 'A'); ?>>A</option>
                                                <option value="B" <?php selected( $data->article_id, 'B'); ?>>B</option>
                                                <option value="C" <?php selected( $data->article_id, 'C'); ?>>C</option>
                                                <option value="D" <?php selected( $data->article_id, 'D'); ?>>D</option>
                                            </select>
                                        </td>
                                        <td class="text-input" style="width: 40%;">
                                            <input type="number" id="e20r-article-phase" name="e20r-article-phase" value="<?php echo $data->phase; ?>">
                                        </td>
                                        <td class="text-input" style="width: 40%;">
                                            <input type="number" id="e20r-article-set_rest" name="e20r-article-set_rest" value="<?php echo $data->set_rest; ?>">
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr><td colspan="2"><hr width="100%"/></td></tr>
                        <tr>
                            <td colspan="2">
                                <table class="sub-table wp-list-table widefat fixed">
                                    <tbody>
                                    <tr>
                                        <th class="e20r-label header" style="width: 40%; vertical-align: top; text-align: left;"><label for="e20r-article-group_id">Client Group(s)</label></th>
                                        <th style="width: 20%;"></th>
                                        <th class="e20r-label header" style="width: 40%; vertical-align: top; text-align: left;"><label for="e20r-article-user_id">Client(s)</label></th>
                                    </tr>
                                    <tr>
                                        <td class="select-input">
                                            <select id="e20r-article-group_id" class="select2-container" multiple="multiple">
                                                <option value="0">Not Applicable</option>
                                                <?php

                                                $member_groups = null;

                                                foreach ( $member_groups as $id => $name ) { ?>
                                                    <option value="<?php echo $id; ?>"<?php selected( $data->group_id, $id); ?>><?php echo $name; ?></option> <?php
                                                } ?>

                                            </select>
                                            <script type="text/javascript">
                                                jQuery('#e20r-article-group_id').select2({
                                                    placeholder: "Select group(s)",
                                                    allowClear: true
                                                });
                                            </script>
                                        </td>
                                        <td rowspan="2" style="font-size: 50px; font-color: #5c5c5c; font-weight: bold; vertical-align: middle; text-align: center; padding: 20px; margin: 0; position: relative;">
                                            or
                                        </td>
                                        <td class="select-input ">
                                            <select id="e20r-article-user_id" class="select2-container" multiple="multiple">
                                                <?php

                                                $memberArgs = array( 'orderby' => 'display_name' );
                                                $members = get_users( $memberArgs );

                                                foreach ( $members as $userData ) {

                                                    $active = $e20rTracker->isActiveUser( $userData->ID );
                                                    if ( $active ) { ?>

                                                        <option value="<?php echo $userData->ID; ?>"<?php selected( $data->user_id, $userData->ID ); ?>>
                                                            <?php echo $userData->display_name; ?>
                                                        </option> <?php
                                                    }
                                                } ?>
                                            </select>
                                            <script type="text/javascript">
                                                jQuery('#e20r-article-user_id').select2({
                                                    placeholder: "Select User(s)",
                                                    allowClear: true
                                                });
                                            </script>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr><td colspan="2"><hr width="100%"/></td></tr>
                        <tr>
                            <td colspan="2">
                                <table class="sub-table wp-list-table widefat fixed">
                                    <tbody>
                                    <tr>
                                        <th class="e20r-label header indent"><label for="e20r-article-startdate">First article (date)</label></th>
                                        <th class="e20r-label header indent"><label for="e20r-article-enddate">Last article (date)</label></th>
                                    </tr>
                                    <tr>
                                        <td class="text-input">
                                            <input type="date" id="e20r-article-startdate" name="e20r-article-startdate" value="<?php echo $data->startdate; ?>">
                                        </td>
                                        <td class="text-input">
                                            <input type="date" id="e20r-article-enddate" name="e20r-article-enddate" value="<?php echo $data->enddate; ?>">
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <?php
                        dbg("e20rArticleView::viewSettingsBox() - Loading " . count($data->groups) . " groups of exercises");
                        foreach( $data->groups as $key => $group ) {

                            dbg("e20rArticleView::viewSettingsBox() - Group # {$key} for article {$data->ID}");
                            echo $this->newExerciseGroup( $data->groups[$key], $key);
                        }
                        ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="2" style="text-align: right;"><a href="javascript:" class="button" id="e20r-new-group-button">Add Exercise Group</a></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </form>
    <?php
    }

    public function newExerciseGroup( $group, $group_id ) {

        global $e20rExercise;
        ob_start();
    ?>
        <tr><td colspan="2"><hr width="100%" /></td></tr>
        <tr>
            <td <?php ($group_id == 0 ) ? ' colspan="2" ': null; ?>class="e20r-group-header">Group #<?php echo ($group_id + 1); ?></td>
            <?php if ( $group_id != 0 ) : ?>
                <td style="text-align: right"><a href="javascript:" class="e20r-remove-group button" id="e20r-article-group-id-<?php echo ($group_id); ?>">Remove Group #<?php echo ($group_id + 1); ?></a></td>
            <?php endif; ?>
        </tr>
        <tr>
            <td colspan="2">
                <table class="sub-table wp-list-table widefat fixed">
                    <thead>
                        <tr>
                            <th class="e20r-label header indent"><label for="e20r-article-group_sets-<?php echo $group_id; ?>">Number of sets</label></th>
                            <th class="e20r-label header indent"><label for="e20r-article-group_tempo-<?php echo $group_id; ?>">Rep tempo</label></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-input">
                                <input type="number" id="e20r-article-group_sets-<?php echo $group_id; ?>" name="e20r-article-group_sets[]" value="<?php echo $group->group_sets; ?>">
                            </td>
                            <td class="text-input">
                                <input type="number" class="e20r-article-group_tempo-<?php echo $group_id; ?>" name="e20r-article-group_tempo[]" value="<?php echo $group->tempo; ?>">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <table class="e20r-exercise-list sub-table wp-list-table widefat fixed">
                                    <thead>
                                        <tr>
                                            <th colspan="2" class="e20r-label header"><label for="e20r-article-exercise_id-<?php echo $group_id; ?>"><?php _e('Exercise', 'e20rtracker'); ?></label></th>
                                            <th class="e20r-label header"><label for="e20r-article-exercise_type-<?php echo $group_id; ?>"><?php _e('Type', 'e20rtracker'); ?></label></th>
                                            <th class="e20r-label header"><label for="e20r-article-exercise_reps-<?php echo $group_id; ?>"><?php _e('Reps / Secs', 'e20rtracker'); ?></label></th>
                                            <th class="e20r-label header"><label for="e20r-article-exercise_rest-<?php echo $group_id; ?>"><?php _e('Rest', 'e20rtracker'); ?></label></th>
                                            <th colspan="2">&nbsp;</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php

                                        if ( count( $group->exercises ) > 0 ) {
                                            foreach ( $group->exercises as $exId => $info ) {

                                                $exercise   = get_post( $exId );
                                                // $exSettings = $e20rExercise->getExerciseSettings( $exId );
                                                switch ( $info->type ) {
                                                    case 0:
                                                        $type = 'Reps';
                                                        break;
                                                    case 1:
                                                        $type = 'Time';
                                                        break;
                                                    case 2:
                                                        $type = 'AMRAP';
                                                        break;
                                                }
                                                echo "<tr>";
                                                echo '<input type="hidden" id="e20r-article-exercise_id-' . $group_id .'" name="hidden-e20r-article-exercise_id[]" value="' . $exId . '" >';
                                                echo '<input type="hidden" id="e20r-article-exercise_type-' . $group_id .'" name="hidden-e20r-article-exercise_type[]" value="' . $type . '" >';
                                                echo '<input type="hidden" id="e20r-article-exercise_reps-' . $group_id .'" name="hidden-e20r-article-exercise_reps[]" value="' . $info->reps . '" >';
                                                echo '<input type="hidden" id="e20r-article-exercise_rest-' . $group_id .'" name="hidden-e20r-article-exercise_rest[]" value="' . $info->rest . '" >';
                                                echo "<td colspan='2'>{$exercise->title}</td>";
                                                echo "<td>{$type}</td>";
                                                echo "<td>{$info->reps}</td>";
                                                echo "<td>{$info->rest}</td>";

                                                foreach( array( __('Edit', 'e20rtracker'), __('Remove', 'e20rtracker') ) as $btnName ) {

                                                    echo '<td><a href="javascript:" class="e20r-exercise-' . strtolower($btnName) . '">'. $btnName . '</a></td>';
                                                }
                                                echo "</tr>";
                                            }
                                        }
                                        else {
                                            ?>
                                            <tr>
                                                <td colspan="7">
                                                    <?php echo sprintf( __("No exercises defined for group #%d. Please add exercises to this group", 'e20rtracker'), ($group_id + 1)); ?>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    ?>
                                    </tbody>
                                    <tfoot>
                                    <tr><td colspan="7">
                                            <hr width="100%">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="select-input">
                                            <select class="e20r-article-group-exercise-type select2-container" name="e20r-article-group_exercise_<?php echo $group_id; ?>[]">
                                                <?php

                                                $postArgs = array(
                                                    'post_type' => 'e20r_exercises',
                                                    'post_status' => 'publish',
                                                );

                                                $exercises = get_posts( $postArgs );

                                                foreach ( $exercises as $exercise ) { ?>
                                                    <option value="<?php echo $exercise->ID; ?>">
                                                        <?php echo $exercise->post_title; ?>
                                                    </option> <?php
                                                }

                                                wp_reset_postdata(); ?>
                                            </select>
                                        </td>
                                        <td class="text-input">
                                            <select id="e20r-article-add-exercise-type_<?php echo $group_id; ?>" name="e20r-add-exercise-type">
                                                <option value="0"><?php _e("Reps", "e20rtracker" ); ?></option>
                                                <option value="1"><?php _e("Time", "e20rtracker" ); ?></option>
                                                <option value="2"><?php _e("AMRAP", "e20rtracker" ); ?></option>
                                            </select>
                                        </td>

                                        <td class="text-input">
                                            <input type="number" id="e20r-article-add-exercise-reps_<?php echo $group_id; ?>" name="e20r-add-exercise-reps" value="">
                                        </td>
                                        <td class="text-input">
                                            <input type="number" id="e20r-article-add-exercise-rest_<?php echo $group_id; ?>" name="e20r-add-exercise-rest" value="">
                                        </td>
                                        <td colspan="2">
                                            <input type="hidden" id="e20r-article-group_id-<?php echo $group_id; ?>" name="e20r-article-group_id[]" value="<?php echo $group_id; ?>" >
                                            <a href="javascript:" class="e20r-article-save-exercise button" style="width: 80%; text-align: center;">Save</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th colspan="2" class="e20r-label header"><label for="e20r-article-exercises-<?php echo $group_id; ?>"><?php _e('Exercise', 'e20rtracker'); ?></label></th>
                                        <th class="e20r-label header"><label for="e20r-article-exercise_type-<?php echo $group_id; ?>"><?php _e('Type', 'e20rtracker'); ?></label></th>
                                        <th class="e20r-label header"><label for="e20r-article-exercise_reps-<?php echo $group_id; ?>"><?php _e('Reps / Secs', 'e20rtracker'); ?></label></th>
                                        <th class="e20r-label header"><label for="e20r-article-exercise-rest-<?php echo $group_id; ?>"><?php _e('Rest', 'e20rtracker'); ?></label></th>
                                        <th colspan="2">&nbsp;</th>
                                    </tr>
                                    </tfoot>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    <?php

        dbg("e20rArticleView::newExerciseGroup() -- HTML generation completed.");
        return ob_get_clean();
    }
}