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
        <!-- <form id="e20r-workout-group-data" method="post"> -->
            <?php wp_nonce_field('e20r-tracker-data', 'e20r-tracker-workout-settings-nonce'); ?>
            <div class="e20r-editform" style="width: 100%;">
                <input type="hidden" name="hidden-e20r-program-id" id="hidden-e20r-workout-id" value="<?php echo ( ( isset($workoutData->ID) ) ? $workoutData->ID : $post->ID ); ?>">
                <table id="e20r-workout-settings" class="wp-list-table widefat fixed">
                    <tbody id="e20r-workout-tbody">
                        <tr id="<?php echo $post->ID; ?>" class="workout-inputs">
                            <td colspan="3">
                                <table class="sub-table wp-list-table widefat fixed">
                                    <tbody>
                                    <tr>
                                        <th class="e20r-label header" style="width: 20%;"><label for="e20r-workout-workout_ident">Workout (A/B/C/D)</label></th>
                                        <th class="e20r-label header" style="width: 40%;"><label for="e20r-workout-phase">Phase (number)</label></th>
                                    </tr>
                                    <tr>
                                        <td class="select-input" style="width: 20%;">
                                            <select id="e20r-workout-workout_ident" name="e20r-workout-workout_ident">
                                                <option value="A" <?php selected( $workoutData->workout_ident, 'A'); ?>>A</option>
                                                <option value="B" <?php selected( $workoutData->workout_ident, 'B'); ?>>B</option>
                                                <option value="C" <?php selected( $workoutData->workout_ident, 'C'); ?>>C</option>
                                                <option value="D" <?php selected( $workoutData->workout_ident, 'D'); ?>>D</option>
                                            </select>
                                        </td>
                                        <td class="text-input" style="width: 40%;">
                                            <input type="number" id="e20r-workout-phase" name="e20r-workout-phase" value="<?php echo $workoutData->phase; ?>">
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr><td colspan="3"><hr width="100%"/></td></tr>
                        <tr>
                            <td colspan="3">
                                <table class="sub-table wp-list-table widefat fixed">
                                    <tbody>
                                    <tr>
                                        <th class="e20r-label header" style="width: 40%; vertical-align: top; text-align: left;"><label for="e20r-workout-assigned_usergroups">Client Group(s)</label></th>
                                        <th style="width: 20%;"></th>
                                        <th class="e20r-label header" style="width: 40%; vertical-align: top; text-align: left;"><label for="e20r-workout-assigned_user_id">Client(s)</label></th>
                                    </tr>
                                    <tr>
                                        <td class="select-input">
                                            <select id="e20r-workout-assigned_usergroups" name="e20r-workout-assigned_usergroups" class="select2-container" multiple="multiple">
                                                <?php

                                                $member_groups = $e20rWorkout->getMemberGroups();

                                                foreach ( $member_groups as $id => $name ) { ?>
                                                    <option value="<?php echo $id; ?>"<?php selected( $workoutData->assigned_usergroups, $id); ?>><?php echo $name; ?></option> <?php
                                                } ?>

                                            </select>
                                            <script type="text/javascript">
                                                jQuery('#e20r-workout-assigned_usergroups').select2({
                                                    placeholder: "Select group(s)",
                                                    allowClear: true
                                                });
                                            </script>
                                        </td>
                                        <td rowspan="2" style="font-size: 50px; font-color: #5c5c5c; font-weight: bold; vertical-align: middle; text-align: center; padding: 20px; margin: 0; position: relative;">
                                            or
                                        </td>
                                        <td class="select-input ">
                                            <select id="e20r-workout-assigned_user_id" name="e20r-workout-assigned_user_id" class="select2-container" multiple="multiple">
                                                <?php

                                                $memberArgs = array( 'orderby' => 'display_name' );
                                                $members = get_users( $memberArgs );

                                                foreach ( $members as $userData ) {

                                                    $active = $e20rTracker->isActiveUser( $userData->ID );
                                                    if ( $active ) { ?>

                                                        <option value="<?php echo $userData->ID; ?>"<?php selected( $workoutData->assigned_user_id, $userData->ID ); ?>>
                                                            <?php echo $userData->display_name; ?>
                                                        </option> <?php
                                                    }
                                                } ?>
                                            </select>
                                            <script type="text/javascript">
                                                jQuery('#e20r-workout-assigned_user_id').select2({
                                                    placeholder: "Select User",
                                                    allowClear: true
                                                });
                                            </script>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr><td colspan="3"><hr width="100%"/></td></tr>
                        <tr>
                            <td colspan="3">
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
                        <tr>
	                        <td colspan="3">
		                        <div id="e20r-workout-add-groups">
			                        <?php
			                        dbg("e20rWorkoutView::viewSettingsBox() - Loading " . count($workoutData->groups) . " groups of exercises");
			                        foreach( $workoutData->groups as $key => $group ) {

				                        dbg("e20rWorkoutView::viewSettingsBox() - Group # {$key} for workout {$workoutData->id}");
				                        echo $this->newExerciseGroup( $workoutData->groups[$key], $key);
			                        }
			                        ?>

		                        </div>
	                        </td>
                        </tr>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right;"><a href="javascript:" class="button" id="e20r-new-group-button">Add Exercise Group</a></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        <!-- </form> -->
    <?php
    }

    public function newExerciseGroup( $group, $group_id ) {

        global $e20rExercise;
        ob_start();
    ?>
	    <table class="e20r-exercise-group">
		    <tbody>
		    <tr class="e20r-workout-exercise-group-hr"><td colspan="3"><hr width="100%" /></td></tr>
		    <tr class="e20r-workout-exercise-group-header">
			    <td <?php ($group_id == 0 ) ? ' colspan="2" ': null; ?>class="e20r-group-header">
				    <input type="hidden" name="e20r-group-id[]" value="<?php echo $group_id; ?>" class="e20r-group-id" />
				    <h3>Group #<span class="group-id"><?php echo ($group_id + 1); ?></span></h3>
			    </td>
			    <?php if ( $group_id != 0 ) : ?>
				    <td style="text-align: right"><a href="javascript:" class="e20r-remove-group button" id="e20r-workout-group_set_count-<?php echo $group_id; ?>">Remove Group #<span class="group-id"><?php echo ($group_id + 1); ?></span></a></td>
			    <?php endif; ?>
		    </tr>
		    <tr class="e20r-workout-exercise-group-data">
			    <td colspan="2">
				    <table class="sub-table wp-list-table widefat fixed">
					    <thead>
					    <tr>
						    <th class="e20r-label header indent"><label for="e20r-workout-group_set_count">Number of sets</label></th>
						    <th class="e20r-label header" style="width: 40%;"><label for="e20r-workout-group_rests">Rest between groups (Seconds)</label></th>
						    <th class="e20r-label header indent"><label for="e20r-workout-group_tempo-<?php echo $group_id; ?>">Tempo</label></th>
					    </tr>
					    </thead>
					    <tbody>
					    <tr>
						    <td class="text-input">
							    <input type="number" class="e20r-workout-groups-group_set_count" name="e20r-workout-group_set_count[]" value="<?php echo $group->group_set_count; ?>" style="width: 100%;">
						    </td>
						    <td class="text-input" style="width: 40%;">
							    <input type="number" class="e20r-workout-groups-group_rest" name="e20r-workout-groups-group_rest[]" value="<?php echo $group->group_rest; ?>" style="width: 100%;">
						    </td>
						    <td class="text-input">
							    <select class="e20r-select2-container select2" class="e20r-workout-groups-group_tempo" name="e20r-workout-groups-group_tempo[]" style="width: 100%;">
								    <option value="0" <?php selected( 0, $group->group_tempo ); ?>></option>
								    <option value="1" <?php selected( 1, $group->group_tempo ); ?>>Slow</option>
								    <option value="2" <?php selected( 2, $group->group_tempo ); ?>>Normal</option>
								    <option value="3" <?php selected( 3, $group->group_tempo ); ?>>Fast</option>
							    </select>
						    </td>
					    </tr>
					    <tr>
						    <td colspan="3">
							    <div class="e20r-list-exercises-for-group">
								    <?php echo $this->generateExerciseList( $group, $group_id ); ?>
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

	public function generateExerciseList( $group, $groupId ) {
		global $e20rExercise;

		ob_start();
		?>
		<table class="e20r-exercise-list sub-table wp-list-table widefat fixed">
			<thead>
			<tr>
				<th colspan="2" class="e20r-label header"><label for="e20r-workout-exercise-name"><?php _e('Exercises', 'e20rtracker'); ?></label></th>
				<th class="e20r-label header"><label for="e20r-workout-exercise-type"><?php _e('Type', 'e20rtracker'); ?></label></th>
				<th class="e20r-label header"><label for="e20r-workout-exercise-reps"><?php _e('Reps / Duration', 'e20rtracker'); ?></label></th>
				<th class="e20r-label header"><label for="e20r-workout-exercise-rest"><?php _e('Rest', 'e20rtracker'); ?></label></th>
				<th colspan="2" class="e20r-label header"><?php _e('Actions', 'e20rtracker'); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php

			if ( ( $group->exercises[0] != 0 ) && ( count( $group->exercises ) > 0 ) ) {
				$count = 1;

				foreach ( $group->exercises as $exId ) {

					if ( $exId !== 0 ) {

						$exSettings = $e20rExercise->getExerciseSettings( $exId );

						echo "<tr>";
						echo "<td colspan='2'>{$count}. {$exSettings->title}  ( {$exSettings->shortcode} )</td>";
						echo "<td>{$exSettings->type}</td>";
						echo "<td>{$exSettings->reps}</td>";
						echo "<td>{$exSettings->rest} ";
				        echo '<input type="hidden" class="e20r-workout-group_exercise_id" name="e20r-workout-group_exercise_id[]" value="' . $exSettings->id . '" >';
						echo '<input type="hidden" class="e20r-workout-group_exercise_order" name="e20r-workout-group_exercise_order[]" value="' . $count . '" >';
						echo '<input type="hidden" class="e20r-workout-group" name="e20r-workout-group[]" value="' . $groupId . '" >';
						echo "</td>";

						foreach ( array( __( 'Edit', 'e20rtracker' ), __( 'Remove', 'e20rtracker' ) ) as $btnName ) {

							echo '<td><a href="javascript:e20rActivity.' . strtolower($btnName) . 'Exercise(' . $exSettings->id . ')"" class="e20r-exercise-' . strtolower( $btnName ) . '">' . $btnName . '</a></td>';
						}
						echo "</tr>";
						$count++;
					}
				}
			}
			else {
				?>
				<tr>
					<td colspan="7">
						<?php _e("No exercises found.", 'e20rtracker'); ?>
					</td>
				</tr>
			<?php
			}
			?>
			</tbody>
		</table>
		<hr style="width: 100%;" />
		<div class="postcustomstuff">
			<p><strong><?php _e('Add/Edit:', 'e20rtracker'); ?></strong></p>
			<table class="new-exercises" style="width: 100%;">
				<thead>
				<tr>
					<th class="new-exercise-header-exercise-key"><label for="e20r-workout-add-exercise-key"><?php _e('Order', 'e20rtracker'); ?></label></th>
					<th class="new-exercise-header-exercise-id"><label for="e20r-workout-add-exercise-id"><?php _e('Exercise', 'e20rtracker'); ?></label></th>
					<th></th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td style="width: 30%;">
						<input class="e20r-workout-add-exercise-key" name="e20r-workout-add-exercise-key" type="number" value="" size="5" />
					</td>
					<td style="width: 70%">
						<select class="e20r-select2-container select2 e20r-workout-add-exercise-id" name="e20r-workout-add-exercise-id">
							<option value="0"></option><?php
							dbg("e20rWorkoutView::generateExerciseList() - Loading all possible exercises");

							$all = $e20rExercise->getAllExercises();

							dbg("e20rWorkoutView::generateExerciseList() - Loaded " . count($all) . ' exercises');

							foreach( $all as $exercise ) {
								dbg("e20rWorkoutView::generateExerciseList() - Got  " . count($all) . ' exercises');
								?>
								<option value="<?php echo $exercise->id; ?>"><?php echo $exercise->title . " ({$exercise->shortcode})"; ?></option><?php
							} ?>
						</select>
					</td>
					<td style="vertical-align: middle;">
						<button style="width: 100%; padding: 5px;" class="e20r-button e20r-workout-add-exercise-save"> <?php _e('Add', 'e20rtracker'); ?> </button>
					</td>
				</tr>
				</tbody>
			</table>
		</div>
	<?php
	}
}