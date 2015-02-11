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

    public function viewLessonComplete( $day, $measurements = 0, $articleId ) {

        global $e20rTracker;
        $postDate = $e20rTracker->getDateForPost( $day );

        // $progressLink = '<a href="' . home_url("/nutrition-coaching/weekly-progress/?for={$postDate}") . '" target="_blank">Click to edit</a> your measurements';

        ob_start();
        ?>
        <div class="green-notice big" style="background-image: url( http://home.strongcubedfitness.com/wp-content/plugins/e20r-tracker/images/checked.png ); margin: 12px 0pt; background-position: 24px 9px;">
            <p><strong><?php _e("You have completed this lesson.", "e20rTracker"); ?></strong>
            <?php
                if ( $measurements != 0 ) { ?>
                <a href="javascript:document.getElementById('e20r-start').submit();" id="e20r-begin-btn" style="display: inline;"><strong><?php _e("Update progress", "e20rTracker"); ?></strong>  &raquo;</a>
                <form action="<?php echo URL_TO_PROGRESS_FORM; ?>" method="POST" id="e20r-start" style="display: none;">
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

        ob_start();
        ?>
        <div id="saturday-progress-container" class="progress-container" style="margin-bottom: 16px;">
            <h3>Weekly Progress <span>Update</span></h3>
            <div id="e20r-progress-canvas" style="min-height: 255px;">
                <img src="<?php echo E20R_PLUGINS_URL; ?>/images/alert.png" class="tooltip-handle" data-tooltip="" data-tooltip-mleft="-83" data-tooltip-mtop="126" id="weekly-alarm-clock" style="float: left;"/>
                <div style="float: left; width: 360px;">

                    <h4 style="font-size: 22px; margin-top: 8px; height: 28px; line-height: 30px;"><span class="highlighted">We&nbsp;need&nbsp;your&nbsp;measurements.</span></h4>
                    <p style="font-size: 16px; color: black;">Today is a measurement day. Here's what we need you to collect:</p>

                    <ul style="font-size: 16px;">
                        <li style="line-height: 20px;">Body Weight</li>
                        <li style="line-height: 20px;">Girth Measurements</li>
                <?php if ( $photos == 1): ?>
                        <li style="line-height: 20px;">Photos</li>
                <?php endif; ?>
                    </ul>

                    <form action="<?php echo URL_TO_PROGRESS_FORM; ?>" method="POST" id="e20r-start">
                        <input type="hidden" value="<?php echo $e20rTracker->getDateForPost( $day ); ?>" name="e20r-progress-form-date" id="e20r-progress-form-date">
                        <input type="hidden" value="<?php echo $articleId; ?>" name="e20r-progress-form-article" id="e20r-progress-form-article">
                    </form>
                    <a href="javascript:document.getElementById('e20r-start').submit();" id="e20r-begin-btn" style="font-size: 18px; line-height: 20px; font-weight: bold; margin-top: 16px; display: block;">Begin  &raquo;</a>
                </div>
            </div>
        </div>
        <?php
        $html = ob_get_clean();

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

        if ( ! current_user_can( 'edit_posts' ) ) {
            return false;
        }

        ?>
        <form action="" method="post">
            <?php wp_nonce_field( 'e20r-tracker-data', 'e20r-tracker-article-settings-nonce' ); ?>
            <div class="e20r-editform">
                <input type="hidden" name="hidden-e20r-article-id" id="hidden-e20r-article-id"
                       value="<?php echo $post->ID; ?>">
                <table id="e20r-article-settings" class="wp-list-table widefat fixed">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label for="e20r-article-post_id"><?php _e("Article", "e20rtracker"); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-article-release_day"><?php _e("Delay", "e20rtracker"); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-article-measurement_day"><?php _e("Measurements", "e20rtracker"); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-article-photo_day"><?php _e("Pictures", "e20rtracker"); ?></label></th>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <hr width="100%"/>
                        </td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr id="<?php echo $settings->ID; ?>" class="checkin-inputs">
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
                            <script>
                                jQuery('#e20r-article-post_id').select2();
                            </script>
                        </td>
                        <td style="width: 50px !important;">
                            <input type="number" id="e20r-article-release_day" name="e20r-article-release_day" value="<?php echo $settings->release_day; ?>">
                        </td>
                        <td class="checkbox"  style="text-align: center;">
                            <input type="checkbox" id="e20r-article-measurement_day" name="e20r-article-measurement_day" value="1"<?php checked( $settings->measurement_day, 1); ?>>
                        </td>
                        <td class="checkbox"  style="text-align: center;">
                            <input type="checkbox" id="e20r-article-photo_day" name="e20r-article-photo_day" value="1"<?php checked( $settings->photo_day, 1); ?>>
                        </td>
                    </tr>
                    <tr><td colspan="4"><hr width="100%" /></td></tr>
                    <tr>
                        <th class="e20r-label header"><label for="e20r-article-prefix"><?php _e("Prefix", "e20rtracker"); ?></label></th>
                        <th colspan="3" class="e20r-label header"><label for="e20r-article-programs"><?php _e("Programs", "e20rtracker"); ?></label></th>
                    </tr>
                    <tr>
                        <td style="vertical-align: top;">
                            <input style="width: 100%;" type="text" id="e20r-article-prefix" name="e20r-article-prefix" value="<?php echo $settings->prefix; ?>">
                        </td>
                        <td colspan="3">
                            <select class="select2-container" id="e20r-article-programs" name="e20r-article-programs[]" multiple="multiple"> <?php

                                $programs = new WP_Query( array(
                                    'post_type' => 'e20r_programs',
                                    'posts_per_page' => -1,
                                    'order_by' => 'title',
                                    'order' => 'ASC',
                                    'fields' => array( 'ids', 'title')
                                ));

                                // $programs = $e20rTracker->getMembershipLevels();
                                dbg("e20rArticleView::viewArticleSettings() - Grabbed {$programs->post_count} programs");

                                while ( $programs->have_posts() ) {

                                    $programs->the_post();

                                    $selected = ( in_array( $programs->post->ID, $settings->programs ) ? ' selected="selected"' : null );
                                    ?><option value="<?php echo $programs->post->ID; ?>" <?php echo $selected; ?>><?php echo $programs->post->post_title; ?></option><?php
                                } ?>
                            </select>
                            <script>
                                jQuery('#e20r-article-programs').select2();
                            </script>

                        </td>
                    </tr>
                    <tr><td colspan="4"><hr width="100%" /></td></tr>
                    <tr>
                        <th class="e20r-label header"><label for="e20r-article-checkins"><?php _e("Check-Ins", "e20rtracker"); ?></label></th>
                    </tr>
                    <tr>
                        <td>
                            <select class="select2-container" id="e20r-article-checkins" name="e20r-article-checkins[]" multiple="multiple"><?php

                                $checkins = new WP_Query( array(
                                    'post_type' => 'e20r_checkins',
                                    'posts_per_page' => -1,
                                    'order_by' => 'title',
                                    'order' => 'ASC',
                                    'fields' => array( 'id', 'title')
                                ));

                                // $programs = $e20rTracker->getMembershipLevels();
                                dbg("e20rArticleView::viewArticleSettings() - Grabbed " . count( $checkins ) . " checkin options");
                                // dbg("e20rArticleView::viewArticleSettings() - " . print_r( $programs, true));
                                while ( $checkins->have_posts() ) {

                                    $checkins->the_post();

                                    $selected = ( in_array( $checkins->post->ID, $settings->checkins ) ? ' selected="selected"' : null );
                                    ?><option value="<?php echo $checkins->post->ID; ?>" <?php echo $selected; ?>><?php echo $checkins->post->post_title; ?></option><?php
                                } ?>
                            </select>
                            <script>
                                jQuery('#e20r-article-checkins').select2();
                            </script>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </form>
        <?php
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
                                                } ?>
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