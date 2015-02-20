<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 12/15/14
 * Time: 1:20 PM
 */

class e20rWorkoutView extends e20rSettingsView {

    private $workouts = null;

	public function e20rWorkoutView( $data = null, $error = null ) {

		parent::__construct( 'workout', 'e20r_workout' );

		$this->workouts = $data;
		$this->error = $error;
	}

    public function viewSettingsBox( $workoutData ) {

        global $post, $e20rWorkout, $e20rTracker;

        dbg("e20rWorkoutView::viewWorkoutSettingsBox() - Supplied data: " . print_r($workoutData, true));
        ?>
        <style>
            .select2-container { min-width: 75px; max-width: 250px; width: 100%;}
        </style>
        <script type="text/javascript">
            jQuery('.e20r-workout-group-exercise-type').select2({
                placeholder: "Select Exercise",
                allowClear: true
            });
        </script>
        <form action="" method="post">
            <?php wp_nonce_field('e20r-tracker-data', 'e20r-tracker-workout-settings-nonce'); ?>
            <div class="e20r-editform" style="width: 100%;">
                <input type="hidden" name="hidden-e20r-program-id" id="hidden-e20r-workout-id" value="<?php echo ( ( isset($workoutData->ID) ) ? $workoutData->ID : $post->ID ); ?>">
                <table id="e20r-workout-settings" class="wp-list-table widefat fixed">
                    <tbody id="e20r-workout-tbody">
                        <tr id="<?php echo $post->ID; ?>" class="workout-inputs">
                            <td colspan="2">
                                <table class="sub-table wp-list-table widefat fixed">
                                    <tbody>
                                    <tr>
                                        <th class="e20r-label header" style="width: 20%;"><label for="e20r-workout-workout_id">Workout (A/B/C/D)</label></th>
                                        <th class="e20r-label header" style="width: 40%;"><label for="e20r-workout-phase">Phase (number)</label></th>
                                        <th class="e20r-label header" style="width: 40%;"><label for="e20r-workout-rest">Rest between sets (Seconds)</label></th>
                                    </tr>
                                    <tr>
                                        <td class="select-input" style="width: 20%;">
                                            <select id="e20r-workout-workout_id">
                                                <option value="A" <?php selected( $workoutData->workout_id, 'A'); ?>>A</option>
                                                <option value="B" <?php selected( $workoutData->workout_id, 'B'); ?>>B</option>
                                                <option value="C" <?php selected( $workoutData->workout_id, 'C'); ?>>C</option>
                                                <option value="D" <?php selected( $workoutData->workout_id, 'D'); ?>>D</option>
                                            </select>
                                        </td>
                                        <td class="text-input" style="width: 40%;">
                                            <input type="number" id="e20r-workout-phase" name="e20r-workout-phase" value="<?php echo $workoutData->phase; ?>">
                                        </td>
                                        <td class="text-input" style="width: 40%;">
                                            <input type="number" id="e20r-workout-set_rest" name="e20r-workout-set_rest" value="<?php echo $workoutData->set_rest; ?>">
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
                                        <th class="e20r-label header" style="width: 40%; vertical-align: top; text-align: left;"><label for="e20r-workout-group_id">Client Group(s)</label></th>
                                        <th style="width: 20%;"></th>
                                        <th class="e20r-label header" style="width: 40%; vertical-align: top; text-align: left;"><label for="e20r-workout-user_id">Client(s)</label></th>
                                    </tr>
                                    <tr>
                                        <td class="select-input">
                                            <select id="e20r-workout-group_id" class="select2-container" multiple="multiple">
                                                <?php

                                                $member_groups = $e20rWorkout->getMemberGroups();

                                                foreach ( $member_groups as $id => $name ) { ?>
                                                    <option value="<?php echo $id; ?>"<?php selected( $workoutData->group_id, $id); ?>><?php echo $name; ?></option> <?php
                                                } ?>

                                            </select>
                                            <script type="text/javascript">
                                                jQuery('#e20r-workout-group_id').select2({
                                                    placeholder: "Select group(s)",
                                                    allowClear: true
                                                });
                                            </script>
                                        </td>
                                        <td rowspan="2" style="font-size: 50px; font-color: #5c5c5c; font-weight: bold; vertical-align: middle; text-align: center; padding: 20px; margin: 0; position: relative;">
                                            or
                                        </td>
                                        <td class="select-input ">
                                            <select id="e20r-workout-user_id" class="select2-container" multiple="multiple">
                                                <?php

                                                $memberArgs = array( 'orderby' => 'display_name' );
                                                $members = get_users( $memberArgs );

                                                foreach ( $members as $userData ) {

                                                    $active = $e20rTracker->isActiveUser( $userData->ID );
                                                    if ( $active ) { ?>

                                                        <option value="<?php echo $userData->ID; ?>"<?php selected( $workoutData->user_id, $userData->ID ); ?>>
                                                            <?php echo $userData->display_name; ?>
                                                        </option> <?php
                                                    }
                                                } ?>
                                            </select>
                                            <script type="text/javascript">
                                                jQuery('#e20r-workout-user_id').select2({
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
                                        <th class="e20r-label header indent"><label for="e20r-workout-startdate">First workout (date)</label></th>
                                        <th class="e20r-label header indent"><label for="e20r-workout-enddate">Last workout (date)</label></th>
                                    </tr>
                                    <tr>
                                        <td class="text-input">
                                            <input type="date" id="e20r-workout-startdate" name="e20r-workout-startdate" value="<?php echo $workoutData->startdate; ?>">
                                        </td>
                                        <td class="text-input">
                                            <input type="date" id="e20r-workout-enddate" name="e20r-workout-enddate" value="<?php echo $workoutData->enddate; ?>">
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <?php
                        dbg("e20rWorkoutView::viewSettingsBox() - Loading " . count($workoutData->groups) . " groups of exercises");
                        foreach( $workoutData->groups as $key => $group ) {

                            dbg("e20rWorkoutView::viewSettingsBox() - Group # {$key} for workout {$workoutData->ID}");
                            echo $this->newExerciseGroup( $workoutData->groups[$key], $key);
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
                <td style="text-align: right"><a href="javascript:" class="e20r-remove-group button" id="e20r-workout-group-id-<?php echo ($group_id); ?>">Remove Group #<?php echo ($group_id + 1); ?></a></td>
            <?php endif; ?>
        </tr>
        <tr>
            <td colspan="2">
                <table class="sub-table wp-list-table widefat fixed">
                    <thead>
                        <tr>
                            <th class="e20r-label header indent"><label for="e20r-workout-group_sets-<?php echo $group_id; ?>">Number of sets</label></th>
                            <th class="e20r-label header indent"><label for="e20r-workout-group_tempo-<?php echo $group_id; ?>">Rep tempo</label></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-input">
                                <input type="number" id="e20r-workout-group_sets-<?php echo $group_id; ?>" name="e20r-workout-group_sets[]" value="<?php echo $group->group_sets; ?>">
                            </td>
                            <td class="text-input">
                                <input type="number" class="e20r-workout-group_tempo-<?php echo $group_id; ?>" name="e20r-workout-group_tempo[]" value="<?php echo $group->tempo; ?>">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <table class="e20r-exercise-list sub-table wp-list-table widefat fixed">
                                    <thead>
                                        <tr>
                                            <th colspan="2" class="e20r-label header"><label for="e20r-workout-exercise_id-<?php echo $group_id; ?>"><?php _e('Exercise', 'e20rtracker'); ?></label></th>
                                            <th class="e20r-label header"><label for="e20r-workout-exercise_type-<?php echo $group_id; ?>"><?php _e('Type', 'e20rtracker'); ?></label></th>
                                            <th class="e20r-label header"><label for="e20r-workout-exercise_reps-<?php echo $group_id; ?>"><?php _e('Reps / Secs', 'e20rtracker'); ?></label></th>
                                            <th class="e20r-label header"><label for="e20r-workout-exercise_rest-<?php echo $group_id; ?>"><?php _e('Rest', 'e20rtracker'); ?></label></th>
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
                                                echo '<input type="hidden" id="e20r-workout-exercise_id-' . $group_id .'" name="hidden-e20r-workout-exercise_id[]" value="' . $exId . '" >';
                                                echo '<input type="hidden" id="e20r-workout-exercise_type-' . $group_id .'" name="hidden-e20r-workout-exercise_type[]" value="' . $type . '" >';
                                                echo '<input type="hidden" id="e20r-workout-exercise_reps-' . $group_id .'" name="hidden-e20r-workout-exercise_reps[]" value="' . $info->reps . '" >';
                                                echo '<input type="hidden" id="e20r-workout-exercise_rest-' . $group_id .'" name="hidden-e20r-workout-exercise_rest[]" value="' . $info->rest . '" >';
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
                                            <select class="e20r-workout-group-exercise-type select2-container" name="e20r-workout-group_exercise_<?php echo $group_id; ?>[]">
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
                                            <select id="e20r-workout-add-exercise-type_<?php echo $group_id; ?>" name="e20r-add-exercise-type">
                                                <option value="0"><?php _e("Reps", "e20rtracker" ); ?></option>
                                                <option value="1"><?php _e("Time", "e20rtracker" ); ?></option>
                                                <option value="2"><?php _e("AMRAP", "e20rtracker" ); ?></option>
                                            </select>
                                        </td>

                                        <td class="text-input">
                                            <input type="number" id="e20r-workout-add-exercise-reps_<?php echo $group_id; ?>" name="e20r-add-exercise-reps" value="">
                                        </td>
                                        <td class="text-input">
                                            <input type="number" id="e20r-workout-add-exercise-rest_<?php echo $group_id; ?>" name="e20r-add-exercise-rest" value="">
                                        </td>
                                        <td colspan="2">
                                            <input type="hidden" id="e20r-workout-group_id-<?php echo $group_id; ?>" name="e20r-workout-group_id[]" value="<?php echo $group_id; ?>" >
                                            <a href="javascript:" class="e20r-workout-save-exercise button" style="width: 80%; text-align: center;">Save</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th colspan="2" class="e20r-label header"><label for="e20r-workout-exercises-<?php echo $group_id; ?>"><?php _e('Exercise', 'e20rtracker'); ?></label></th>
                                        <th class="e20r-label header"><label for="e20r-workout-exercise_type-<?php echo $group_id; ?>"><?php _e('Type', 'e20rtracker'); ?></label></th>
                                        <th class="e20r-label header"><label for="e20r-workout-exercise_reps-<?php echo $group_id; ?>"><?php _e('Reps / Secs', 'e20rtracker'); ?></label></th>
                                        <th class="e20r-label header"><label for="e20r-workout-exercise-rest-<?php echo $group_id; ?>"><?php _e('Rest', 'e20rtracker'); ?></label></th>
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

        dbg("e20rWorkoutView::newExerciseGroup() -- HTML generation completed.");
        return ob_get_clean();
    }
}