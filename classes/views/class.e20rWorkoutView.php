<?php

/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 12/15/14
 * Time: 1:20 PM
 */
class e20rWorkoutView extends e20rSettingsView
{

    private $workouts = null;

    private static $instance = null;

    public function __construct($data = null, $error = null)
    {

        parent::__construct('workout', 'e20r_workout');

        $this->workouts = $data;
        $this->error = $error;
    }

	/**
	 * @return e20rWorkoutView
	 */
	static function getInstance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

    public function display_printable_activity($config, $workoutData, $hide_print = false)
    {

        if (!is_user_logged_in()) {
            auth_redirect();
        }

        global $current_user;
        global $currentProgram;
        global $currentArticle;
        global $currentExercise;
        $e20rExercise = e20rExercise::getInstance();

        dbg("e20rWorkoutView::display_printable_activity() - Display a single activity entry for print ");

        if (isset($workoutData['error'])) {
            ob_start(); ?>
            <div class="red-notice">
            <h3><?php _e("No planned activity", "e20r-tracker"); ?></h3>

            <p><?php _e("There are no scheduled/planned activities for your coaching program today, but don't let that stop you from enjoying nature!", "e20r-tracker"); ?></p>
            </div><?php

            return ob_get_clean();
        }

        ob_start();
        foreach ($workoutData as $w) {
            
            $print_args = array(
                'activity-id' => $currentArticle->activity_id,
                'article-id' => $currentArticle->id,
                'for-date' => $currentArticle->release_date,
                'activity-override' => $config->activity_override,
            );
            
            $activity_page_url = add_query_arg( $print_args , get_permalink($currentProgram->activity_page_id) );
            $groups = isset($w->groups) ? $w->groups : null; ?>

            <h2><?php echo esc_attr($w->title); ?></h2>
            <?php if ( false === $hide_print ): ?>
            <div class="e20r-activity-printer-icon">
                <img onClick="e20rActivity.print_page('<?php echo esc_url($activity_page_url); ?>');"
                     id="e20r-activity-print" src="<?php echo E20R_PLUGINS_URL . '/img/printer-icon.png' ?>">
                <span class="e20r-activity-print-label"><?php _e("Click to print", "e20r-tracker"); ?></span>
            </div>
            <?php endif; ?>
            <div class="e20r-activity-description">
                <h4><?php _e("Summary", "e20r-tracker"); ?></h4>

                <p><?php echo wpautop($w->excerpt); ?></p>
            </div>
            <?php if ( false === $hide_print || (isset($config->show_tracking) && true === $config->show_tracking) ): ?>
            <div class="e20r-activity-tracking-next right clearfix top-link">
                <a href="#e20r-exercise-tracking-input-1-1"><?php _e("Click to input tracking information", "e20r-tracker"); ?></a>
            </div>
            <?php endif; ?>
            <form id="e20r-activity-input-form">
            <?php wp_nonce_field('e20r-tracker-activity', 'e20r-tracker-activity-input-nonce'); ?>
            <div class="e20r-activity-overview-table e20r-print-activity clearfix">
                <div class="e20r-activity-table-header">
                    <div class="e20r-exercise-row e20r-workout-summary clearfix">
                        <!-- <div class="e20r-activity-info-col "> -->
                        <input type="hidden" id="e20r-activity-input-user_id" name="e20r-activity-exercise-user_id"
                               value="<?php echo esc_attr($config->userId); ?>"/>
                        <input type="hidden" id="e20r-activity-input-program_id"
                               name="e20r-activity-exercise-program_id"
                               value="<?php echo esc_attr($config->programId); ?>"/>
                        <input type="hidden" id="e20r-activity-input-article_id" name="e20r-activity-article_id"
                               value="<?php echo isset($config->articleId) ? esc_attr($config->articleId) : null; ?>"/>
                        <input type="hidden" id="e20r-activity-input-activity_id"
                               name="e20r-activity-exercise-activity_id" value="<?php echo esc_attr($w->id); ?>"/>
                        <input type="hidden" id="e20r-activity-input-for_date" name="e20r-activity-exercise-for_date"
                               value="<?php echo(!empty($config->date) ? esc_attr($config->date) : null); ?>"/>

                        <div class="e20r-int-table">
                            <div class="e20r-act-content-row clearfix">
                                <p class="e20r-content-col">
                                    <span class="e20r-exercise-label"><?php _e("Phase", "e20r-tracker"); ?>: </span>
                                    <span class="e20r-exercise-value"><?php echo esc_attr($w->phase); ?></span>
                                </p>

                                <p class="e20r-content-col">
                                    <span class="e20r-exercise-label"><?php _e("Workout", "e20r-tracker"); ?>: </span>
                                    <span class="e20r-exercise-value"><?php echo esc_attr($w->workout_ident); ?></span>
                                </p>

                                <p class="e20r-content-col right alignright">
                                    <span class="e20r-exercise-label"><?php _e("Name", "e20r-tracker"); ?>: </span>
                                    <span
                                        class="e20r-exercise-value"><?php echo esc_attr($current_user->user_firstname); ?></span>
                                </p>

                            </div>
                        </div> <!-- End of e20r-int-table -->
                        <!-- </div> -->
                    </div><!-- COMPLETE: end of table header -->
                </div><!-- end of e20r-exercise-row -->
                <div class="e20r-activity-table-body">
                    <div class="e20r-exercise-row"><?php
                        $grp = 1;
                        
                        foreach ($groups as $k => $g) {

                            $recorded = isset($g->saved_exercises) ? $g->saved_exercises : array();
                            $gcount = $k + 1;
                            $next_grp = $grp;
                            ?>

                            <div class="e20r-int-table exercise-header clear-after">
                                <div class="e20r-act-content-row">
                                    <p class="e20r-content-col">
                                        <span class="e20r-activity-label"><?php _e("Group", "e20r-tracker"); ?>: </span>
                                        <span class="e20r-activity-var"><?php echo esc_attr($gcount); ?></span>
                                    </p>

                                    <p class="e20r-content-col">
                                        <span class="e20r-activity-label"><?php _e("Sets", "e20r-tracker"); ?>: </span>
                                        <span
                                            class="e20r-activity-var"><?php echo esc_attr($g->group_set_count); ?></span>
                                    </p>

                                    <p class="e20r-content-col">
                                        <span class="e20r-activity-label"><?php _e("Tempo", "e20r-tracker"); ?>: </span>
                                        <span class="e20r-activity-var"><?php echo esc_attr($g->group_tempo); ?></span>
                                    </p>

                                    <p class="e20r-content-col">
                                        <span class="e20r-activity-label"><?php _e("Rest", "e20r-tracker"); ?>: </span>
                                        <span class="e20r-activity-var"><?php echo esc_attr($g->group_rest); ?></span>
                                    </p>
                                </div><!-- e20r-act-content-row -->
                            </div><!-- End of e20r-int-table -->
                            <?php

                            $exercise_cnt = 1;

                            foreach ($g->exercises as $exKey => $exId) {

                                $next_cnt = ($exercise_cnt + 1);

                                if ( $next_cnt > count( $g->exercises ) ) {

                                    $next_grp = ($grp + 1);

                                    if ( $next_grp > count( $groups ) ) {
                                        $next_grp = 1;
                                    }

                                    $next_cnt = 1;
                                }

                                dbg("e20rWorkoutView::display_printable_activity() - Generate link target for next exercise to track");
                                $next_lnk = "e20r-exercise-tracking-input-{$next_grp}-{$next_cnt}";
                                $tracking_input_id = "e20r-exercise-tracking-input-{$grp}-{$exercise_cnt}";

                                $e20rExercise->set_currentExercise($exId); ?>
                                <div class="e20r-exercise-content">
                                    <div class="e20r-exercise-row fullwidth clearfix">
                                            <?php echo $e20rExercise->print_exercise(false, 'new', false); ?>
                                    </div><!-- End of exercise-row -->
                                    <?php if ( false === $hide_print ||  (isset($config->show_tracking) && true === $config->show_tracking) ): ?>
                                    <div id="<?php echo $tracking_input_id; ?>"></div>
                                    <div class="e20r-exercise-row e20r-exercise-tracking-row">
                                        <div class="e20r-activity-tracking-next right">
                                            <a href="#<?php echo $next_lnk; ?>"><?php ( $next_cnt == 1 && $next_grp == 1 ) ? _e("Back to first", "e20r-tracker") : _e("Record next", "e20r-tracker"); ?></a>
                                        </div>
                                        <div class="e20r-activity-info-col">
                                            <div class="e20r-activity-exercise-tracking">
                                                <table class="e20r-resp-table">
                                                    <thead class="e20r-resp-table-header">
                                                    <tr>
                                                        <th class="e20r-td-input-count">
                                                            <div
                                                                    class="e20r-activity-group-track-s e20r-activity-var"><?php _e( "Set", "e20r-tracker" ); ?></div>
                                                        </th>
                                                        <th class="e20r-td-input-activity">
                                                            <div
                                                                    class="e20r-activity-group-track-l e20r-activity-var"><?php echo ( $currentExercise->type == 1 ) ? __( "Weight", "e20r-tracker" ) : __( "Time", "e20r-tracker" ); ?></div>
                                                        </th>
                                                        <th class="e20r-td-input-activity">
                                                            <div
                                                                    class="e20r-activity-group-track-r e20r-activity-var"><?php echo ( $currentExercise->type != 1 ) ? __( "Reps", "e20r-tracker" ) : __( "Reps", "e20r-tracker" ); ?></div>
                                                        </th>
                                                        <th></th>
                                                    </tr>
                                                    </thead>
                                                    <tbody class="e20r-resp-table-body">
                                                    <?php
                                                    for ( $i = 1; $i <= $g->group_set_count; $i ++ ) {
                            
                                                        if ( $currentExercise->type == 2 ) {
                                
                                                            dbg( "e20rWorkoutView::display_printable_activity() - Time/Reps type of exercise" );
                                                            $weight = $currentExercise->reps;
                                                        } else {
                                                            dbg( "e20rWorkoutView::display_printable_activity() - Weight/Reps type of exercise" );
                                                            $weight = isset( $recorded[ $exKey ]->set[ $i ]->weight ) ? $recorded[ $exKey ]->set[ $i ]->weight : null;
                                                        }
                            
                                                        $reps  = isset( $recorded[ $exKey ]->set[ $i ]->reps ) ? $recorded[ $exKey ]->set[ $i ]->reps : null;
                                                        $when  = isset( $recorded[ $exKey ]->set[ $i ]->recorded ) ? $recorded[ $exKey ]->set[ $i ]->recorded : null;
                                                        $ex_id = isset( $recorded[ $exKey ]->set[ $i ]->ex_id ) ? $recorded[ $exKey ]->set[ $i ]->ex_id : null;
                                                        $id    = isset( $recorded[ $exKey ]->set[ $i ]->id ) ? $recorded[ $exKey ]->set[ $i ]->id : null;
                                                        ?>
                                                        <tr class="e20r-edit e20r-exercise-set-row">
                                                            <td data-th="Set" class="e20r-td-input-activity-set">
                                                                <input type="hidden"
                                                                       class="e20r-activity-input-group_no"
                                                                       name="e20r-activity-exercise-group_no[]"
                                                                       value="<?php echo esc_attr( $k ); ?>">
                                                                <input type="hidden" class="e20r-activity-input-set_no"
                                                                       name="e20r-activity-exercise-set_no[]"
                                                                       value="<?php echo esc_attr( $i ); ?>">
                                                                <input type="hidden"
                                                                       class="e20r-activity-input-record_id"
                                                                       name="e20r-activity-exercise-record_id[]"
                                                                       value="<?php echo esc_attr( $id ); ?>">
                                                                <input type="hidden"
                                                                       class="e20r-activity-input-recorded"
                                                                       name="e20r-activity-exercise-recorded[]"
                                                                       value="<?php echo esc_attr( $when ); ?>">
                                                                <input type="hidden" class="e20r-activity-input-ex_id"
                                                                       name="e20r-activity-exercise-ex_id[]"
                                                                       value="<?php echo esc_attr( $ex_id ); ?>">
                                                                <input type="hidden" class="e20r-activity-input-ex_key"
                                                                       name="e20r-activity-exercise-ex_key[]"
                                                                       value="<?php echo esc_attr( $exKey ); ?>">
                                                                <input type="hidden"
                                                                       class="e20r-activity-input-weight_h"
                                                                       name="e20r-activity-exercise-weight_h[]"
                                                                       value="<?php echo esc_attr( $weight ); ?>">
                                                                <input type="hidden" class="e20r-activity-input-reps_h"
                                                                       name="e20r-activity-exercise-reps_h[]"
                                                                       value="<?php echo esc_attr( $reps ); ?>">
                                                                <span
                                                                        class="e20r-td-activity-label-set"><?php echo esc_attr( $i ); ?></span>
                                                            </td>
                                                            <td data-th="Weight" class="e20r-td-input-activity-weight">
                                                                <input type="text" class="e20r-activity-input-weight"
                                                                       name="e20r-activity-exercise-weight[]"
                                                                       value="<?php echo esc_attr( $weight ); ?>"></td>
                                                            <td data-th="Reps" class="e20r-td-input-activity-reps">
                                                                <input type="number" class="e20r-activity-input-reps"
                                                                       name="e20r-activity-exercise-reps[]"
                                                                       value="<?php echo esc_attr( $reps ); ?>"></td>
                                                            <td data-th="" class="e20r-td-input-button">
                                                                <button
                                                                        class="e20r-save-set-row alignright e20r-button<?php echo ( empty( $weight ) || empty( $reps ) ) ? '' : ' startHidden'; ?>">
                                                                    Save
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <tr class="e20r-saved startHidden">
                                                            <td data-th="Set" class="e20r-td-input-count">
                                                                    <span
                                                                            class="e20r-saved-set-number"><?php echo esc_attr( $i ); ?></span>
                                                            </td>
                                                            <td data-th="Weight" class="e20r-td-input-activity">
                                                                <!-- <span class="e20r-saved-weight-value">--><a
                                                                        href="javascript:"
                                                                        class="e20r-edit-weight-value"><?php echo empty( $weight ) ? 0 : esc_attr( $weight ); ?></a>
                                                                <!--</span>-->
                                                            </td>
                                                            <td data-th="Reps" class="e20r-td-input-activity">
                                                                <!-- <span class="e20r-saved-rep-value">--><a
                                                                        href="javascript:"
                                                                        class="e20r-edit-rep-value"><?php echo empty( $reps ) ? 0 : esc_attr( $reps ); ?></a>
                                                                <!--</span>--></td>
                                                            <td></td>
                                                        </tr>
                                                    <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div> <!-- end of activity-exercise-tracking -->
                                        </div> <!-- End of info-col -->
                                    </div> <!-- end of exercise tracking row -->
	                            <?php endif; ?>
                                </div><?php

                                $exercise_cnt++;
                            } // End of for loop for exercise list

                            $grp++;
                        } // End of loop for Groups ?>
                    </div> <!-- End of exercise-row -->
                </div><!-- end of e20r-table-body -->
            </div><!-- end of e20r-activity-overview-table -->
            </form><?php
        }
        $html = ob_get_clean();

        return $html;
    }

    public function display_printable_list($activities, $config)
    {

        $e20rExercise = e20rExercise::getInstance();
        $e20rTracker = e20rTracker::getInstance();

        global $currentProgram;

        if (!is_user_logged_in()) {
            auth_redirect();
        }

        ob_start(); ?>
        <div class="e20r-activity-overview-printable">
        <h1 class="e20r-activity-overview-h1"><?php _e("Activity summary for", "e20r-tracker"); ?></h1>
        <h2 class="e20r-activity-overview-h2"><?php echo esc_html($currentProgram->title); ?></h2>
        <hr/><?php

        if (empty($activities) && ('upcoming' == $config->period)) { ?>
            <div class="red-notice">
            <h3><?php printf(__("Today is %s, so...", "e20r-tracker"), date('l', current_time('timestamp'))); ?></h3>

            <p><?php
                $currentDate = date('Y-m-d', current_time('timestamp', true));
                $sat = date('jS', strtotime("next saturday {$currentDate} "));
                $sun = date('jS', strtotime("next sunday {$currentDate}"));

                echo sprintf(__("Sorry, we're not quite ready to share the activities for next week.<br/>Please come back on Saturday the %s, or Sunday the %s.", "e20r-tracker"), $sat, $sun); ?></p>
            </div><?php
        } elseif (empty($activities)) { ?>
            <div class="red-notice">
            <h3><?php _e("Unexpected error!", "e20r-tracker"); ?></h3>

            <p><?php

                dbg("e20rWorkoutView::display_printable_list() - Period setting: {$config->period}");

                if ('current' == $config->period) {
                    $period = 'current week';
                } elseif ('previous' == $config->period) {
                    $period = 'prior week';
                } else {
                    $period = "upcoming week";
                }

                echo sprintf(__('Sorry, there are no activities to found for the %s.<br/>Please report this error to the <a href="mailto:%s">webmaster</a>', "e20r-tracker"), $period, get_bloginfo('admin_email')); ?>
            </p>
            </div><?php
        }

        foreach ($activities as $id => $activity_summary) {

            $day_list = array();

            foreach ($activity_summary->days as $day) {

                $day_list[] = $e20rTracker->displayWeekdayName($day);
            }

            ?>
            <div class="e20r-activity-summary-table">
                <h3 class="e20r-activity-summary-routine-start"><?php echo esc_html($activity_summary->name); ?></h3>

                <p class="e20r-activity-summary-routine-paragraph"><?php

                    if (isset($config->phase)) {
                        echo sprintf(__("Activity description for phase %s of this program.", "e20r-tracker"), $activity_summary->phase);
                    }
                    ?></p>

                <div class="e20r-activity-summary-printable-row e20r-activity-summary-headline">
                    <?php echo sprintf(__("Starting on %s and until %s, this is the activity on %s."), date('F j', strtotime($activity_summary->started)), date('F j', strtotime($activity_summary->ends)), join(", ", $day_list)); ?>
                </div>
                <div class="e20r-activity-summary-printable-row e20r-activity-summary-description">
                    <?php echo wpautop($activity_summary->description); ?>
                </div><?php
                foreach ($activity_summary->exercises as $id) { ?>
                    <div class="e20r-activity-summary-printable-row e20r-activity-summary-exercise"><?php
                    $e20rExercise->set_currentExercise($id);
                    echo $e20rExercise->print_exercise(false, 'new'); ?>
                    </div><?php
                } ?>
            </div>
            <?php
        } ?>
        </div><?php
        $html = ob_get_clean();
        return $html;

    }

    public function viewExerciseProgress($activities = null, $error = null, $userId = null, $dimensions = null)
    {

        global $currentProgram;
        $e20rExercise = e20rExercise::getInstance();

        ob_start();

        dbg("e20rWorkoutView::viewExerciseProgress() - Listing exercise history");
        // dbg($activities);

        if (empty($activities)) { ?>

            <div class="red-notice">
                <h2>I wonder...</h2>

                <p>It seems like there are no records of completed activities in the database? :-(</p>

                <p>We're sorry for the inconvenience, and the possible issue! Please do report this to the Web Monkey so
                    he can whip himself into shape!</p>
            </div><?php

            $html = ob_get_clean();

            return $html;
        }

        if (!is_null($error)) { ?>

            <div class="red-notice">
            <h3>Error</h3>

            <p><?php echo $error ?></p>
            </div><?php
        }

        ?>
        <div class="e20r-activity-list">
        <div class="e20r-row clear-after"> <?php
            if (!empty($dimensions)) {
                ?>
                <input type="hidden" name="user_id" id="user_id" value="<?php echo $userId; ?>">
                <input type="hidden" name="wh_h_dimension" id="wh_h_dimension"
                       value="<?php echo $dimensions['height']; ?>">
                <input type="hidden" name="wh_h_dimension_type" id="wh_h_dimension_type"
                       value="<?php echo $dimensions['htype']; ?>">
                <input type="hidden" name="wh_w_dimension" id="wh_w_dimension"
                       value="<?php echo $dimensions['width']; ?>">
                <input type="hidden" name="wh_w_dimension_type" id="wh_w_dimension_type"
                       value="<?php echo $dimensions['wtype']; ?>">
                <?php wp_nonce_field('e20r-tracker-data', 'e20r-weight-rep-chart');
            } ?>

            <h2 style="text-align: center; margin-bottom: 30px;">History
                for <?php echo $currentProgram->title ?></h2><?php

            foreach ($activities as $exId => $info) {

                dbg("e20rWorkoutView::viewExerciseProgress() - Exercise ID: {$exId}");

                $type = $info->type;
                $title = $info->name;

                unset($info->type);
                unset($info->name);

                switch ($type) {
                    case 1: // weight
                        $wType = __("Resistance");
                        $unit_pre = __("Weight", "e20r-tracker");
                        $wType = null;
                        $unit_post = null;
                        break;

                    case 2: // time
                    case 3: // AMRAP
                        $unit_pre = __("Time", "e20r-tracker");
                        $wType = $e20rExercise->getExerciseType($type);
                        $unit_post = __("seconds", "e20r-tracker");
                        break;

                    default:
                        $wType = null;
                        $unit_post = null;
                        $unit_pre = null;
                }

                ?>

                <div class="e20r-column e20r-column_1_1">
                    <div class="e20r-faq-container e20r-toggle-close">
                        <h3 class="e20r-faq-question"><?php echo $title; ?></h3>

                        <div class="e20r-faq-answer-container clear-after">
                            <div class="e20r-activity-history-graph">
                                <?php echo $this->view_WorkoutStats($userId, $exId, $dimensions); ?>
                            </div><?php

                            unset($info->name);
                            $row = 0;

                            foreach ($info->when as $time => $group) { ?>
                                <div class="e20r-activity-history-row clear-after">
                                <div class="e20r-activity-container-inner">
                                    <div class="e20r-activity-container-date-col">
                                        <div class="e20r-history-date"><?php echo date_i18n('M j, Y', $time); ?></div>
                                    </div> <!-- date (sidebar) column -->
                                    <div class="e20r-activity-container-info-col">
                                        <?php
                                        $row = $row + 1;

                                        foreach ($group->group as $gid => $set) { ?>

                                            <div class="e20r-activity-history-sets clear-after"><?php
                                                if ((1 == $type) && (0.00 == $set->weight)) {
                                                    $unit = "BW? ({$set->weight})";
                                                } else {
                                                    $unit = $set->weight;
                                                }
                                                ?>
                                                <h4 class="e20r-activity-history-set-title"><?php echo sprintf(__("Set %d in Group %d", "e20r-tracker"), $set->set, ($gid + 1)); ?></h4>

                                                <div class="e20r-activity-history-set-detail">
                                                    <p class="e20r-history-type">
                                                        <strong><?php echo __("Type", "e20r-tracker") . "</strong>: {$wType}"; ?>
                                                    </p>

                                                    <p class="e20r-history-unit"><?php echo "<strong>{$unit_pre}</strong>: {$unit} {$unit_post}"; ?> </p>

                                                    <p class="e20r-history-reps"><?php echo "<strong>{$set->reps}</strong> " . ($set->reps == 1 ? __("rep", "e20r-tracker") : __("reps", "e20r-tracker")); ?></p>
                                                </div>
                                            </div><!-- history-sets --><?php
                                        } ?>
                                        <!-- <div class="clear-after"></div> -->
                                    </div><!-- Groups/sets (content) container -->
                                </div><!-- Inner container -->
                                </div><?php
                            } ?>
                        </div> <!-- FAQ answer container -->
                    </div> <!-- FAQ container -->
                </div><!-- Column --> <?php
            } ?>
        </div>
        </div><?php

        $html = ob_get_clean();

        return $html;
    }

    /**
     * Displays the html for the e20r_activity_archive short code
     *
     * @param $activityList - Associative list of activities (sorted by day)
     * @param $config - Configuration data to display activity archive
     * @return mixed - HTML of archive.
     *
     * @since v0.8.0
     */
    public function displayArchive($activityList, $config)
    {

        $e20rTracker = e20rTracker::getInstance();
        if (!is_user_logged_in()) {
            auth_redirect();
        }

        dbg("e20rWorkoutView::displayArchive() - Display archive");
        // dbg( $activityList );

        $activityHeader = $activityList['header'];
        unset($activityList['header']);

        ob_start(); ?>
        <h2 class="e20r-faq-headline"><?php echo $activityHeader; ?></h2>
        <hr/><?php

        dbg("e20rWorkoutView::displayArchive() - Check whether the archive is empty");
        dbg("e20rWorkoutView::displayArchive() - # of entries in archive: " . count($activityList));

        if (empty($activityList) && ('upcoming' == $config->period)) { ?>
            <div class="red-notice">
            <h3><?php printf(__("Today is %s, so...", "e20r-tracker"), date('l', current_time('timestamp'))); ?></h3>

            <p><?php
                $currentDate = date('Y-m-d', current_time('timestamp', true));
                $sat = date('jS', strtotime("next saturday {$currentDate} "));
                $sun = date('jS', strtotime("next sunday {$currentDate}"));

                echo sprintf(__("Sorry, we're not quite ready to share the activities for next week.<br/>Please come back on Saturday the %s, or Sunday the %s.", "e20r-tracker"), $sat, $sun); ?></p>
            </div><?php
        } elseif (empty($activityList)) { ?>
            <div class="red-notice">
            <h3><?php _e("Unexpected error!", "e20r-tracker"); ?></h3>

            <p><?php
                $period = ('current' == $config->period ? 'current week' : 'prior week');
                echo sprintf(__('Sorry, there are no activities to found for the %s.<br/>Please report this error to the <a href="mailto:%s">webmaster</a>', "e20r-tracker"), $period, get_bloginfo('admin_email')); ?>
            </div><?php
        }

        dbg("e20rWorkoutView::displayArchive() - Iterating through list of activities.");
        foreach ($activityList as $day => $activity) { ?>
            <div id="e20r-daily-activity-page">
                <div class="e20r-faq-container e20r-toggle-close">
                    <h3 class="e20r-faq-question"><?php echo $e20rTracker->displayWeekdayName($day); ?></h3>
                    <div class="e20r-faq-answer-container clear-after">
                        <?php // echo $this->displayActivity($config, array($activity)); ?>
                        <?php echo $this->display_printable_activity($config, array($activity), true); ?>
                    </div>
                </div>
            </div><?php
        }

        $html = ob_get_clean();

        return $html;
    }

    public function displayActivityOverlay($config, $workoutData) {


    }

    private function display_currentExercise($activtiy, $config) {


    }
    /**
     * Generates the HTML for the activity. Typically used by the activity short code or the activity archive short code.
     *
     * @param $config -- Configuration data to facilitate display of the activity
     * @param $workoutData -- The activity definition (array)
     * @return mixed - The HTML of the activity to display.
     *
     * @since v0.5.0
     */
    public function displayActivity($config, $workoutData)
    {

        if (!is_user_logged_in()) {
            auth_redirect();
        }

        global $current_user;
        global $currentExercise;
        $e20rExercise = e20rExercise::getInstance();

        dbg("e20rWorkoutView::displayActivity() - Content of workoutData object: ");
        // dbg( $workoutData );

        if (isset($workoutData['error'])) {
            ob_start();

            ?>
            <div class="red-notice">
                <h3><?php _e("No planned activity", "e20r-tracker"); ?></h3>

                <p><?php _e("There are no scheduled/planned activities for your coaching program today, but don't let that stop you from enjoying nature!", "e20r-tracker"); ?></p>
            </div>
            <?php

            return ob_get_clean();
        }

        ob_start();

        foreach ($workoutData as $w) {

            $groups = isset($w->groups) ? $w->groups : null;

            ?>
            <h2><?php echo esc_attr($w->title); ?></h2>
            <div class="e20r-activity-description">
                <h4><?php _e("Summary", "e20r-tracker"); ?></h4>

                <p><?php echo wpautop($w->excerpt); ?></p>
            </div>
            <form id="e20r-activity-input-form">
                <?php wp_nonce_field('e20r-tracker-activity', 'e20r-tracker-activity-input-nonce'); ?>
                <div class="e20r-activity-overview-table e20r-print-activity e20r-screen">
                    <div class="e20r-activity-table-header">
                        <div class="e20r-exercise-row e20r-workout-summary clearfix">
                            <!-- <div class="e20r-activity-info-col"> -->
                            <input type="hidden" id="e20r-activity-input-user_id" name="e20r-activity-exercise-user_id"
                                   value="<?php echo esc_attr($config->userId); ?>"/>
                            <input type="hidden" id="e20r-activity-input-program_id"
                                   name="e20r-activity-exercise-program_id"
                                   value="<?php echo esc_attr($config->programId); ?>"/>
                            <input type="hidden" id="e20r-activity-input-article_id" name="e20r-activity-article_id"
                                   value="<?php echo isset($config->articleId) ? esc_attr($config->articleId) : null; ?>"/>
                            <input type="hidden" id="e20r-activity-input-activity_id"
                                   name="e20r-activity-exercise-activity_id" value="<?php echo esc_attr($w->id); ?>"/>
                            <input type="hidden" id="e20r-activity-input-for_date"
                                   name="e20r-activity-exercise-for_date"
                                   value="<?php echo(!empty($config->date) ? esc_attr($config->date) : null); ?>"/>

                            <div class="e20r-int-table">
                                <div class="e20r-act-content-row clearfix">
                                    <p class="e20r-content-col">
                                        <span class="e20r-exercise-label"><?php _e("Phase", "e20r-tracker"); ?>: </span>
                                        <span class="e20r-exercise-value"><?php echo esc_attr($w->phase); ?></span>
                                    </p>

                                    <p class="e20r-content-col">
                                        <span class="e20r-exercise-label"><?php _e("Workout", "e20r-tracker"); ?>
                                            : </span>
                                        <span
                                            class="e20r-exercise-value"><?php echo esc_attr($w->workout_ident); ?></span>
                                    </p>

                                    <p class="e20r-content-col right alignright">
                                        <span class="e20r-exercise-label"><?php _e("Name", "e20r-tracker"); ?>: </span>
                                        <span
                                            class="e20r-exercise-value"><?php echo esc_attr($current_user->user_firstname); ?></span>
                                    </p>
                                </div>
                            </div> <!-- End of e20r-int-table -->
                        </div><!-- end of e20r-exercise-row -->
                    </div><!-- COMPLETE: end of table header -->
                    <!-- <div class="spacer">&nbsp;</div> -->
                    <div class="e20r-activity-table-body">
                        <?php
                        foreach ($groups as $k => $g) {

                            $recorded = isset($g->saved_exercises) ? $g->saved_exercises : array();
                            $gcount = $k + 1;

                            ?>
                            <div class="e20r-exercise-row">
                                <div class="e20r-activity-info-col">
                                    <div class="e20r-int-table exercise-header">
                                        <div class="e20r-act-content-row">
                                            <p class="e20r-content-col">
                                                <span class="e20r-activity-label"><?php _e("Group", "e20r-tracker"); ?>
                                                    : </span>
                                                <span class="e20r-activity-var"><?php echo esc_attr($gcount); ?></span>
                                            </p>

                                            <p class="e20r-content-col">
                                                <span class="e20r-activity-label"><?php _e("Sets", "e20r-tracker"); ?>
                                                    : </span>
                                                <span
                                                    class="e20r-activity-var"><?php echo esc_attr($g->group_set_count); ?></span>
                                            </p>
                                            <p class="e20r-content-col">
                                                <span class="e20r-activity-label"><?php _e("Tempo", "e20r-tracker"); ?>
                                                    : </span>
                                                <span
                                                    class="e20r-activity-var"><?php echo esc_attr($g->group_tempo); ?></span>
                                            </p>
                                            <p class="e20r-content-col">
                                                <span class="e20r-activity-label"><?php _e("Rest", "e20r-tracker"); ?>
                                                    : </span>
                                                <span
                                                    class="e20r-activity-var"><?php echo esc_attr($g->group_rest); ?></span>
                                            </p>

                                        </div><!-- e20r-act-content-row -->
                                    </div><!-- End of e20r-int-table -->
                                </div> <!-- End of info-col -->
                            </div> <!-- End of exercise-row -->
                            <?php
                            foreach ($g->exercises as $exKey => $exId) {

                                $e20rExercise->set_currentExercise($exId);
                                ?>
                                <div class="e20r-exercise-row fullwidth clearfix">
                                    <?php echo $e20rExercise->print_exercise($config->expanded); ?>
                                </div><!-- End of exercise-row --><?php

                                if ((isset($config->show_tracking) && true === $config->show_tracking)) { ?>
                                    <div class="e20r-exercise-row e20r-exercise-tracking-row startHidden">
                                        <div class="e20r-activity-info-col">
                                            <div class="e20r-activity-exercise-tracking">
                                                <table class="e20r-resp-table">
                                                    <thead class="e20r-resp-table-header">
                                                    <tr>
                                                        <th class="e20r-td-input-count">
                                                            <!-- div class="e20r-activity-group-track-s e20r-activity-var">-->
                                                            <?php _e("Set", "e20r-tracker"); ?><!-- </div> --></th>
                                                        <th class="e20r-td-input-activity">
                                                            <div
                                                                class="e20r-activity-group-track-l e20r-activity-var"><?php echo ($currentExercise->type == 1) ? __("Weight", "e20r-tracker") : __("Time", "e20r-tracker"); ?></div>
                                                        </th>
                                                        <th class="e20r-td-input-activity">
                                                            <div
                                                                class="e20r-activity-group-track-r e20r-activity-var"><?php echo ($currentExercise->type != 1) ? __("Reps (click to edit)", "e20r-tracker") : __("Reps", "e20r-tracker"); ?></div>
                                                        </th>
                                                        <th></th>
                                                    </tr>
                                                    </thead>
                                                    <tbody class="e20r-resp-table-body">
                                                    <?php
                                                    for ($i = 1; $i <= $g->group_set_count; $i++) {

                                                        if ($currentExercise->type == 2) {

                                                            dbg("e20rWorkoutView::displayActivity() - Time/Reps type of exercise");
                                                            $weight = $currentExercise->reps;
                                                        } else {
                                                            dbg("e20rWorkoutView::displayActivity() - Weight/Reps type of exercise");
                                                            $weight = isset($recorded[$exKey]->set[$i]->weight) ? $recorded[$exKey]->set[$i]->weight : null;
                                                        }

                                                        $reps = isset($recorded[$exKey]->set[$i]->reps) ? $recorded[$exKey]->set[$i]->reps : null;
                                                        $when = isset($recorded[$exKey]->set[$i]->recorded) ? $recorded[$exKey]->set[$i]->recorded : null;
                                                        $ex_id = isset($recorded[$exKey]->set[$i]->id) ? $recorded[$exKey]->set[$i]->ex_id : null;
                                                        $id = isset($recorded[$exKey]->set[$i]->id) ? $recorded[$exKey]->set[$i]->id : null;

                                                        ?>
                                                        <tr class="e20r-edit e20r-exercise-set-row">
                                                            <td data-th="Set" class="e20r-td-input-count">
                                                                <input type="hidden"
                                                                       class="e20r-activity-input-group_no"
                                                                       name="e20r-activity-exercise-group_no[]"
                                                                       value="<?php echo esc_attr($k); ?>">
                                                                <input type="hidden" class="e20r-activity-input-set_no"
                                                                       name="e20r-activity-exercise-set_no[]"
                                                                       value="<?php echo esc_attr($i); ?>">
                                                                <input type="hidden"
                                                                       class="e20r-activity-input-record_id"
                                                                       name="e20r-activity-exercise-record_id[]"
                                                                       value="<?php echo esc_attr($id); ?>">
                                                                <input type="hidden"
                                                                       class="e20r-activity-input-recorded"
                                                                       name="e20r-activity-exercise-recorded[]"
                                                                       value="<?php echo esc_attr($when); ?>">
                                                                <input type="hidden" class="e20r-activity-input-ex_id"
                                                                       name="e20r-activity-exercise-ex_id[]"
                                                                       value="<?php echo esc_attr($ex_id); ?>">
                                                                <input type="hidden" class="e20r-activity-input-ex_key"
                                                                       name="e20r-activity-exercise-ex_key[]"
                                                                       value="<?php echo esc_attr($exKey); ?>">
                                                                <input type="hidden"
                                                                       class="e20r-activity-input-weight_h"
                                                                       name="e20r-activity-exercise-weight_h[]"
                                                                       value="<?php echo esc_attr($weight); ?>">
                                                                <input type="hidden" class="e20r-activity-input-reps_h"
                                                                       name="e20r-activity-exercise-reps_h[]"
                                                                       value="<?php echo esc_attr($reps); ?>">
                                                                <!-- <span class="e20r-saved-set-number">-->
                                                                <?php echo $i; ?><!--</span>-->
                                                            </td>
                                                            <td data-th="Weight" class="e20r-td-input-activity"><input
                                                                    type="text" class="e20r-activity-input-weight"
                                                                    name="e20r-activity-exercise-weight[]"
                                                                    value="<?php echo esc_attr($weight); ?>"></td>
                                                            <td data-th="Reps" class="e20r-td-input-activity"><input
                                                                    type="number" class="e20r-activity-input-reps"
                                                                    name="e20r-activity-exercise-reps[]"
                                                                    value="<?php echo esc_attr($reps); ?>"></td>
                                                            <td data-th="" class="e20r-td-input-button">
                                                                <button
                                                                    class="e20r-save-set-row alignright e20r-button<?php echo (empty($weight) || empty($reps)) ? '' : ' startHidden'; ?>">
                                                                    Save
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <tr class="e20r-saved startHidden">
                                                            <td data-th="Set" class="e20r-td-input-count">
                                                                <!--<span class="e20r-saved-set-number">-->
                                                                <?php echo esc_attr($i); ?><!--</span>-->
                                                            </td>
                                                            <td data-th="Weight" class="e20r-td-input-activity">
                                                                <!-- <span class="e20r-saved-weight-value">--><a
                                                                    href="javascript:"
                                                                    class="e20r-edit-weight-value"><?php echo empty($weight) ? 0 : esc_attr($weight); ?></a>
                                                                <!--</span>-->
                                                            </td>
                                                            <td data-th="Reps" class="e20r-td-input-activity">
                                                                <!-- <span class="e20r-saved-rep-value">--><a
                                                                    href="javascript:"
                                                                    class="e20r-edit-rep-value"><?php echo empty($reps) ? 0 : esc_attr($reps); ?></a>
                                                                <!--</span>--></td>
                                                            <td class="e20r-td-input-hidden"></td>
                                                        </tr>
                                                    <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div> <!-- end of activity-exercise-tracking -->
                                        </div> <!-- End of info-col -->
                                    </div> <!-- end of exercise tracking row --><?php
                                } ?>
                            <?php } // End of for loop for exercise list
                        } // End of loop for Groups ?>
                    </div> <!-- End of table-body -->
                    <div class="e20r-activity-table-footer clearfix">
                        <div class="e20r-exercise-row">
                            <div class="e20r-activity-info-col">
                                <p class="e20r-content-col alignright">
                                    <button id="e20r-activity-input-button"
                                            class="e20r-button alignright startHidden"><?php _e("Click to complete", "e20r-tracker"); ?></button>
                                </p>
                            </div> <!-- End of info-col -->
                        </div> <!-- End of exercise-row -->
                    </div><!-- end of table-footer -->
                </div>
                <div class="modal"><!-- At end of form --></div>
            </form>
            <?php
        }

        $html = ob_get_clean();

        return $html;
    }

    public function viewSettingsBox($workoutData)
    {

        global $post;
        $e20rWorkout = e20rWorkout::getInstance();
        $e20rTracker = e20rTracker::getInstance();
        $e20rProgram = e20rProgram::getInstance();

        dbg("e20rWorkoutView::viewSettingsBox() - Loading program list for future use:");

        $programs = $e20rProgram->getProgramList();

        dbg("e20rWorkoutView::viewSettingsBox() - Supplied data: " . print_r($workoutData, true));

        if (isset($workoutData->days) && empty($workoutData->days)) {

            dbg("e20rWorkoutView::viewSettingsBox() - Days contains: ");
            $workoutData->days = array();
        } elseif (!isset($workoutData->days)) {
            dbg("e20rWorkoutView::viewSettingsBox() - No 'days' defined??");
        }


        ?>
        <style>
            .select2-container {
                min-width: 75px;
                max-width: 250px;
                width: 100%;
            }
        </style>
        <?php wp_nonce_field('e20r-tracker-data', 'e20r-tracker-workout-settings-nonce'); ?>
        <div class="e20r-editform" style="width: 100%;">
            <input type="hidden" name="hidden-e20r-program-id" id="hidden-e20r-workout-id"
                   value="<?php echo((isset($workoutData->id)) ? $workoutData->id : $post->ID); ?>">
            <table id="e20r-workout-settings" class="wp-list-table widefat fixed">
                <tbody id="e20r-workout-tbody">
                <tr id="<?php echo (isset($workoutData->id)) ? $workoutData->id : $post->ID; ?>" class="workout-inputs">
                    <td colspan="3">
                        <table class="e20r-workout-settings sub-table wp-list-table widefat fixed">
                            <thead>
                            <tr>
                                <th class="e20r-label header" style="width: 10%;"><label
                                        for="e20r-workout-workout_ident">Workout</label></th>
                                <th class="e20r-label header" style="width: 10%;"><label
                                        for="e20r-workout-phase">Phase</label></th>
                                <th class="e20r-label header"
                                    style="width: 40%;"><?php _e("On what weekdays", "e20r-tracker"); ?></th>
                                <th class="e20r-label header"
                                    style="width: 40%;"><?php _e("Programs", "e20r-tracker"); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class="select-input" style="width: 10%;">
                                    <select style="width: 95%;" id="e20r-workout-workout_ident"
                                            name="e20r-workout-workout_ident">
                                        <option value="A" <?php selected($workoutData->workout_ident, 'A'); ?>>A
                                        </option>
                                        <option value="B" <?php selected($workoutData->workout_ident, 'B'); ?>>B
                                        </option>
                                        <option value="C" <?php selected($workoutData->workout_ident, 'C'); ?>>C
                                        </option>
                                        <option value="D" <?php selected($workoutData->workout_ident, 'D'); ?>>D
                                        </option>
                                    </select>
                                </td>
                                <td class="text-input" style="width: 10%;">
                                    <input style="width: 95%;" type="number" id="e20r-workout-phase"
                                           name="e20r-workout-phase" value="<?php echo $workoutData->phase; ?>">
                                </td>
                                <td class="select-input" style="width: 40%;">
                                    <select id="e20r-workout-days" name="e20r-workout-days[]" class="select2-container"
                                            multiple="multiple">
                                        <option
                                            value="1" <?php echo in_array(1, $workoutData->days) ? 'selected="selected"' : ''; ?>><?php _e("Monday", "e20r-tracker"); ?></option>
                                        <option
                                            value="2" <?php echo in_array(2, $workoutData->days) ? 'selected="selected"' : ''; ?>><?php _e("Tuesday", "e20r-tracker"); ?></option>
                                        <option
                                            value="3" <?php echo in_array(3, $workoutData->days) ? 'selected="selected"' : ''; ?>><?php _e("Wednesday", "e20r-tracker"); ?></option>
                                        <option
                                            value="4" <?php echo in_array(4, $workoutData->days) ? 'selected="selected"' : ''; ?>><?php _e("Thursday", "e20r-tracker"); ?></option>
                                        <option
                                            value="5" <?php echo in_array(5, $workoutData->days) ? 'selected="selected"' : ''; ?>><?php _e("Friday", "e20r-tracker"); ?></option>
                                        <option
                                            value="6" <?php echo in_array(6, $workoutData->days) ? 'selected="selected"' : ''; ?>><?php _e("Saturday", "e20r-tracker"); ?></option>
                                        <option
                                            value="7" <?php echo in_array(7, $workoutData->days) ? 'selected="selected"' : ''; ?>><?php _e("Sunday", "e20r-tracker"); ?></option>
                                    </select>
                                </td>
                                <td class="select-input" style="width: 40%;">
                                    <select class="select2-container" id="e20r-workout-program_ids"
                                            name="e20r-workout-program_ids[]" multiple="multiple">
                                        <option value="0">Not configured</option>
                                        <?php
                                        foreach ($programs as $pgm) {

                                            if (!empty($workoutData->program_ids)) {
                                                $selected = (in_array($pgm->id, $workoutData->program_ids) ? ' selected="selected" ' : null);
                                            } else {
                                                $selected = '';
                                            }
                                            ?>
                                            <option value="<?php echo $pgm->id; ?>"<?php echo $selected; ?>>
                                                <?php echo esc_textarea($pgm->title); ?> (#<?php echo $pgm->id; ?>)
                                            </option>
                                        <?php } ?>
                                    </select>
                                    <style>
                                        #e20r-workout-program_ids {
                                            min-width: 150px;
                                            max-width: 300px;
                                            width: 90%;
                                        }
                                    </style>
                                    <script>

                                    </script>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="3">
                        <hr width="100%"/>
                    </td>
                </tr>
                <tr>
                    <td colspan="3">
                        <table class="sub-table wp-list-table widefat fixed">
                            <thead>
                            <tr>
                                <th class="e20r-label header"
                                    style="width: 40%; vertical-align: top; text-align: left;"><label
                                        for="e20r-workout-assigned_usergroups">Client Group(s)</label></th>
                                <th style="width: 20%;"></th>
                                <th class="e20r-label header"
                                    style="width: 40%; vertical-align: top; text-align: left;"><label
                                        for="e20r-workout-assigned_user_id">Client(s)</label></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class="select-input">
                                    <select id="e20r-workout-assigned_usergroups"
                                            name="e20r-workout-assigned_usergroups[]" class="select2-container"
                                            multiple="multiple">
                                        <option
                                            value="0" <?php echo in_array(0, $workoutData->assigned_usergroups, true) ? 'selected="selected"' : null; ?>>
                                            <?php _e("Not Applicable", "e20r-tracker"); ?>
                                        </option>
                                        <option
                                            value="-1" <?php echo in_array(-1, $workoutData->assigned_usergroups, true) ? 'selected="selected"' : null; ?>>
                                            <?php _e("All Users", "e20r-tracker"); ?>
                                        </option>
                                        <?php

                                        $member_groups = $e20rWorkout->getMemberGroups();
                                        
                                        foreach ($member_groups as $key => $r_def) {
                                            
                                            if (!empty($workoutData->assigned_usergroups)) {
                                                $selected = in_array($r_def['role'], $workoutData->assigned_usergroups) ? 'selected="selected"' : null;
                                            } else {
                                                $selected = null;
                                            }
                                            ?>
                                            <option
                                                value="<?php echo $r_def['role']; ?>" <?php echo $selected; ?>><?php echo $r_def['label']; ?></option> <?php
                                        } ?>

                                    </select>
                                </td>
                                <td rowspan="2"
                                    style="font-size: 50px; font-color: #5c5c5c; font-weight: bold; vertical-align: middle; text-align: center; padding: 20px; margin: 0; position: relative;">
                                    or
                                </td>
                                <td class="select-input ">
                                    <select id="e20r-workout-assigned_user_id" name="e20r-workout-assigned_user_id[]"
                                            class="select2-container" multiple="multiple">
                                        <option
                                            value="0" <?php echo in_array(0, $workoutData->assigned_user_id) ? 'selected="selected"' : null; ?>>
                                            Not Applicable
                                        </option>
                                        <option
                                            value="-1" <?php echo in_array(-1, $workoutData->assigned_user_id) ? 'selected="selected"' : null; ?>>
                                            All Users
                                        </option>
                                        <?php

                                        $memberArgs = array('orderby' => 'display_name');
                                        $members = get_users($memberArgs);

                                        foreach ($members as $userData) {

                                            $active = $e20rTracker->isActiveUser($userData->ID);

                                            if ($active) { ?>

                                                <option
                                                    value="<?php echo $userData->ID; ?>"<?php echo in_array($userData->ID, $workoutData->assigned_user_id) ? 'selected="selected"' : ''; ?>>
                                                    <?php echo $userData->display_name; ?>
                                                </option> <?php
                                            }
                                        } ?>
                                    </select>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="3">
                        <hr width="100%"/>
                    </td>
                </tr>
<!--                <tr>
                    <td colspan="3" style="background-color: #C5C5C5;"><?php
                        $text = "<p style='font-size: 1.1em;'>The <em><strong>date values will take precedence over day number configurations</strong></em>. If you want to use day numbers, you <strong>have to clear the dates</strong> for the 'First workout (date)' or 'Last workout (date)' fields, <strong>before saving this workout/activity.</strong></p>";
                        $text .= "<p style='font-size: 1.1em;'>If you use the '(day number)' to indicate delay for a workout, we will use the start date value for the clients membership as day 1. Then we will add the startday/endday values to the start date when verifying the clients access to the activity/workout.</p>";
                        _e($text, "e20r-tracker");
                        ?>
                    </td>
                </tr>
                 <tr>
                    <td colspan="3">
                        <table class="sub-table wp-list-table widefat fixed">
                            <thead>
                            <tr>
                                <th class="e20r-label header indent"><?php _e("First workout (date)", "e20r-tracker"); ?></th>
                                <th class="e20r-label header indent"><?php _e("Last workout (date)", "e20r-tracker"); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class="text-input">
                                    <input type="date" id="e20r-workout-startdate" name="e20r-workout-startdate"
                                           value="<?php echo empty($workoutData->startdate) ? '' : $workoutData->startdate; ?>">
                                </td>
                                <td class="text-input">
                                    <input type="date" id="e20r-workout-enddate" name="e20r-workout-enddate"
                                           value="<?php echo empty($workoutData->enddate) ? '' : $workoutData->enddate; ?>">
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
-->
                <tr>
                    <td colspan="3">
                        <table class="sub-table wp-list-table widefat fixed">
                            <thead>
                            <tr>
                                <th class="e20r-label header indent"><?php _e("First workout (day number)", "e20r-tracker"); ?></th>
                                <th class="e20r-label header indent"><?php _e("Last workout (day number)", "e20r-tracker"); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class="text-input">
                                    <input type="number" id="e20r-workout-startday" name="e20r-workout-startday"
                                           value="<?php echo $workoutData->startday; ?>">
                                </td>
                                <td class="text-input">
                                    <input type="number" id="e20r-workout-endday" name="e20r-workout-endday"
                                           value="<?php echo $workoutData->endday; ?>">
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>

                </tr>
                <tr>
                    <td colspan="3">
                        <div id="e20r-workout-add-groups">
                            <?php
                            dbg("e20rWorkoutView::viewSettingsBox() - Loading " . count($workoutData->groups) . " groups of exercises");
                            dbg($workoutData->groups);

                            foreach ($workoutData->groups as $key => $group) {

                                dbg("e20rWorkoutView::viewSettingsBox() - Group # {$key} for workout {$workoutData->id}");
                                echo $this->newExerciseGroup($workoutData->groups[$key], $key);
                            }
                            ?>
                        </div>
                    </td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right;"><a href="javascript:" class="button"
                                                                  id="e20r-new-group-button">Add Exercise Group</a></td>
                </tr>
                </tfoot>
            </table>
        </div>
        <?php
    }

    public function newExerciseGroup($group = null, $group_id = null)
    {

        $e20rExercise = e20rExercise::getInstance();

        $empty_group = new stdClass();

        ob_start();
        ?>
        <table class="e20r-exercise-group">
            <tbody>
            <tr class="e20r-workout-exercise-group-hr">
                <td colspan="3">
                    <hr width="100%"/>
                </td>
            </tr>
            <tr class="e20r-workout-exercise-group-header">
                <td <?php ($group_id == 0) ? ' colspan="2" ' : null; ?>class="e20r-group-header">
                    <input type="hidden" name="e20r-group-id[]" value="<?php echo $group_id; ?>" class="e20r-group-id"/>

                    <h3>Group #<span class="group-id"><?php echo($group_id + 1); ?></span></h3>
                </td>
                <?php if ($group_id != 0) : ?>
                    <td style="text-align: right"><a href="javascript:" class="e20r-remove-group button"
                                                     id="e20r-workout-group_set_count-<?php echo $group_id; ?>">Remove
                            Group #<span class="group-id"><?php echo($group_id + 1); ?></span></a></td>
                <?php endif; ?>
            </tr>
            <tr class="e20r-workout-exercise-group-data">
                <td colspan="2">
                    <table class="sub-table wp-list-table widefat fixed">
                        <thead>
                        <tr>
                            <th class="e20r-label header indent"><label for="e20r-workout-group_set_count">Number of
                                    sets</label></th>
                            <th class="e20r-label header" style="width: 40%;"><label for="e20r-workout-group_rests">Rest
                                    between groups (Seconds)</label></th>
                            <th class="e20r-label header indent"><label
                                    for="e20r-workout-group_tempo-<?php echo $group_id; ?>">Tempo</label></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td class="text-input">
                                <input type="number" class="e20r-workout-groups-group_set_count"
                                       name="e20r-workout-group_set_count[]"
                                       value="<?php echo $group->group_set_count; ?>" style="width: 100%;">
                            </td>
                            <td class="text-input" style="width: 40%;">
                                <input type="number" class="e20r-workout-groups-group_rest"
                                       name="e20r-workout-groups-group_rest[]" value="<?php echo $group->group_rest; ?>"
                                       style="width: 100%;">
                            </td>
                            <td class="text-input">
                                <select class="e20r-select2-container select2" class="e20r-workout-groups-group_tempo"
                                        name="e20r-workout-groups-group_tempo[]" style="width: 100%;">
                                    <option value="0" <?php selected(0, $group->group_tempo); ?>></option>
                                    <option
                                        value="1" <?php selected(1, $group->group_tempo); ?>><?php _e("Slow", "e20r-tracker"); ?></option>
                                    <option
                                        value="2" <?php selected(2, $group->group_tempo); ?>><?php _e("Normal", "e20r-tracker"); ?></option>
                                    <option
                                        value="3" <?php selected(3, $group->group_tempo); ?>><?php _e("Fast", "e20r-tracker"); ?></option>
                                    <option
                                        value="4" <?php selected(4, $group->group_tempo); ?>><?php _e("Varying", "e20r-tracker"); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <div class="e20r-list-exercises-for-group">
                                    <?php echo $this->generateExerciseList($group, $group_id); ?>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            </tbody>
        </table>
        <?php

        dbg("e20rWorkoutView::newExerciseGroup() -- HTML generation completed.");
        return ob_get_clean();
    }

    public function generateExerciseList($group, $groupId)
    {
        $e20rExercise = e20rExercise::getInstance();

        ob_start();
        ?>
        <table class="e20r-exercise-list sub-table wp-list-table widefat fixed">
            <thead>
            <tr>
                <th style="width: 15px;" class="e20r-label header"><label for="exercise-order"></label></th>
                <th colspan="2" class="e20r-label header"><label
                        for="e20r-workout-exercise-name"><?php _e('Exercises', 'e20r-tracker'); ?></label></th>
                <th class="e20r-label header"><label
                        for="e20r-workout-exercise-type"><?php _e('Type', 'e20r-tracker'); ?></label></th>
                <th class="e20r-label header"><label
                        for="e20r-workout-exercise-reps"><?php _e('Reps / Duration', 'e20r-tracker'); ?></label></th>
                <th class="e20r-label header"><label
                        for="e20r-workout-exercise-rest"><?php _e('Rest', 'e20r-tracker'); ?></label></th>
                <th colspan="2" class="e20r-label header"><?php _e('Actions', 'e20r-tracker'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php

            if (count($group->exercises) > 0) {
                $count = 1;

                foreach ($group->exercises as $exId) {

                    if ($exId !== 0) {
                        $exSettings = $e20rExercise->getExerciseSettings($exId);

                        $type = $e20rExercise->getExerciseType($exSettings->type);
                        dbg("e20rWorkoutView::generateExerciseList() - Setting type to: {$type}");
                        $exSettings->reps = empty($exSettings->reps) ? __("None", "e20r-tracker") : $exSettings->reps;
                        $exSettings->rest = empty($exSettings->rest) ? __("None", "e20r-tracker") : $exSettings->rest;

                        echo "<tr>";
                        echo '<td class="exercise-order" style="width: 15px;">' . $count . '</td>';
                        echo "<td colspan='2'> {$exSettings->title}  ( {$exSettings->shortcode} )</td>";
                        echo "<td>{$type}</td>";
                        echo "<td>{$exSettings->reps}</td>";
                        echo "<td>{$exSettings->rest} ";
                        echo '<input type="hidden" class="e20r-workout-group_exercise_id" name="e20r-workout-group_exercise_id[]" value="' . $exSettings->id . '" >';
                        echo '<input type="hidden" class="e20r-workout-group_exercise_order" name="e20r-workout-group_exercise_order[]" value="' . $count . '" >';
                        echo '<input type="hidden" class="e20r-workout-group" name="e20r-workout-group[]" value="' . $groupId . '" >';
                        echo "</td>";
                        echo '<td><a href="javascript:e20rActivity.editExercise( \'group:' . $groupId . '\', ' . $exSettings->id . ', ' . $count . ')" class="e20r-exercise-edit">' . __("Update", "e20r-tracker") . '</a></td>';
                        echo '<td><a href="javascript:e20rActivity.removeExercise( \'group:' . $groupId . '\', ' . $exSettings->id . ', ' . $count . ')" class="e20r-exercise-remove">' . __("Remove", "e20r-tracker") . '</a></td>';
                        echo "</tr>";

                        $count++;
                        unset($exSettings);
                    } else {
                        ?>
                        <tr>
                            <td colspan="8">
                                <?php _e("No exercises found.", 'e20r-tracker'); ?>
                            </td>
                        </tr>
                        <?php

                    }
                }
            }
            ?>
            </tbody>
        </table>
        <hr style="width: 100%;"/>
        <div class="postcustomstuff">
            <p><strong><?php _e('Add/Edit:', 'e20r-tracker'); ?></strong></p>
            <table class="new-exercises" style="width: 100%;">
                <thead>
                <tr>
                    <th class="new-exercise-header-exercise-key"><label
                            for="e20r-workout-add-exercise-key"><?php _e('Order', 'e20r-tracker'); ?></label></th>
                    <th class="new-exercise-header-exercise-id"><label
                            for="e20r-workout-add-exercise-id"><?php _e('Exercise', 'e20r-tracker'); ?></label></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td style="width: 30%;">
                        <input class="e20r-workout-add-exercise-key" name="e20r-workout-add-exercise-key" type="number"
                               value="" size="5"/>
                    </td>
                    <td style="width: 70%">
                        <select class="e20r-select2-container select2 e20r-workout-add-exercise-id"
                                name="e20r-workout-add-exercise-id">
                            <option value="0"></option><?php
                            dbg("e20rWorkoutView::generateExerciseList() - Loading all possible exercises");

                            $all = $e20rExercise->getAllExercises();

                            dbg("e20rWorkoutView::generateExerciseList() - Loaded " . count($all) . ' exercises');

                            foreach ($all as $exercise) {
                                // dbg("e20rWorkoutView::generateExerciseList() - Got " . count($all) . ' exercises');
                                ?>
                                <option
                                value="<?php echo $exercise->id; ?>"><?php echo $exercise->title . " ({$exercise->shortcode})"; ?></option><?php
                            } ?>
                        </select>
                    </td>
                    <td style="vertical-align: middle;">
                        <button style="width: 100%; padding: 5px;"
                                class="e20r-button e20r-workout-add-exercise-save"> <?php _e('Add', 'e20r-tracker'); ?> </button>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    public function view_WorkoutStats($clientId = null, $exercise_id = null, $dimensions = null, $records = null)
    {

        // TESTING: using $clientId = 12;
        // $clientId = 116;

        $e20rClient = e20rClient::getInstance();
        global $currentProgram;
        global $currentExercise;
        global $current_user;

        if ($dimensions === null) {

            $dimensions = array('width' => '650', 'height' => '300', 'htype' => 'px', 'wtype' => 'px');
        }

        if ($dimensions['htype'] != '%') {

            $maxHeight = (((int)$dimensions['height']) + 95);
            $height = (((int)$dimensions['height']) + 75);
        } else {
            $maxHeight = ((int)$dimensions['height'] + 10) <= 100 ? ((int)$dimensions['height'] + 10) : $dimensions['height'];
            $height = ((int)$dimensions['height'] + 5) <= 100 ? ((int)$dimensions['height'] + 5) : $dimensions['height'];
        }

        if ($dimensions['wtype'] != '%') {

            $minWidth = ((int)$dimensions['width'] + 15);
            $width = ((int)$dimensions['width'] + 95);
        } else {
            $minWidth = (((int)$dimensions['width'] - 5) <= 100 ? ((int)$dimensions['width'] - 5) : $dimensions['width']);
            $width = (((int)$dimensions['width'] + 5) <= 100 ? ((int)$dimensions['width'] + 5) : $dimensions['width']);
        }

        $maxHeight = $maxHeight . $dimensions['htype'];
        $height = $height . $dimensions['htype'];

        $minWidth = $minWidth . $dimensions['wtype'];
        $width = $width . $dimensions['wtype'];

        $user = get_user_by('id', $clientId);

        $reloadBtn = '
                    <div id="e20r_reload_btn">
                        <a href="#e20r_tracker_data" id="e20r-reload-statistics" class="e20r-choice-button button e20r-button" > ' . __("Reload Activity Statistics", "e20r-tracker") . '</a>
                    </div>
                ';

        /*        if ( count( $records ) < 1 ) {

                    ob_start(); ?>
                    <div id="e20r_errorMsg"><em><?php sprintf(__("No records found for %s", "e20r-tracker"), $user->first_name . " " . $user->last_name ); ?></em></div><?php
                    $html = ob_get_clean();
                }
                else { */

        ob_start(); ?>
        <div class="e20r-exercise-statistics clear-after">
        <input type="hidden" name="exercise_id[]" class="e20r-workout-statistics-exercise_id"
               value="<?php echo $exercise_id; ?>">
        <button
            class="e20r-button button-primary e20r-choice-button e20r-workout-statistics-loader"><?php _e("Load statistics", "e20r-tracker"); ?></button>
        <div class="startHidden" id="exercise_stats_<?php echo $exercise_id; ?>"
             style="height: <?php echo $height; ?>; width: <?php echo $width; ?>;"></div>
        </div><?php
        $html = ob_get_clean();
        /* } */

        return $html;
    }

}