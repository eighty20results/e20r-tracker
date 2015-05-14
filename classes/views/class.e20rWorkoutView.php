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

	public function displayActivity( $config, $workoutData ) {

		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		global $current_user;
		global $currentExercise;
		global $e20rExercise;

		dbg("e20rWorkoutView::displayActivity() - Content of workoutData object: ");
		dbg( $workoutData );

		if ( isset( $workoutData['error'] ) ) {
			ob_start();

			?>
		<div class="red-notice">
			<h3><?php _e( "No planned activity", "e20rtracker" ); ?></h3>
			<p><?php _e( "There are no scheduled/planned activities for your coaching program, but don't let that stop you from enjoying nature!", "e20rtracker" ); ?></p>
		</div>
			<?php

			return ob_get_clean();
		}

		ob_start();

		foreach ( $workoutData as $w ) {

			$groups = isset( $w->groups ) ? $w->groups : null;

			?>
			<h2><?php echo $w->title; ?></h2>
			<div class="e20r-activity-description">
				<h4><?php _e( "Summary", "e20rtracker" ); ?></h4>
				<p><?php echo $w->excerpt; ?></p>
			</div>
			<form id="e20r-activity-input-form">
				<?php wp_nonce_field('e20r-tracker-activity', 'e20r-tracker-activity-input-nonce'); ?>
				<div class="e20r-activity-overview-table e20r-print-activity e20r-screen">
					<!-- <div class="spacer">&nbsp;</div> -->
					<div class="e20r-activity-table-header">
						<div class="e20r-exercise-row">
							<div class="e20r-activity-info-col">
								<input type="hidden" id="e20r-activity-input-user_id" name="e20r-activity-exercise-user_id" value="<?php echo $config->userId; ?>" />
								<input type="hidden" id="e20r-activity-input-program_id" name="e20r-activity-exercise-program_id" value="<?php echo $config->programId; ?>" />
								<input type="hidden" id="e20r-activity-input-activity_id" name="e20r-activity-exercise-activity_id" value="<?php echo $w->id; ?>" />
								<input type="hidden" id="e20r-activity-input-for_date" name="e20r-activity-exercise-for_date" value="<?php echo $config->date; ?>" />
								<div class="e20r-int-table">
									<div class="e20r-act-content-row">
										<p class="e20r-content-col">
											<span class="e20r-exercise-label"><?php _e( "Phase", "e20rtracker"); ?>: </span>
											<span class="e20r-exercise-value"><?php echo $w->phase; ?></span>
										</p>
										<p class="e20r-content-col">
											<span class="e20r-exercise-label"><?php _e( "Workout", "e20rtracker"); ?>: </span>
											<span class="e20r-exercise-value"><?php echo $w->workout_ident; ?></span>
										</p>
									</div>
								</div> <!-- End of e20r-int-table -->
							</div>
							<div class="e20r-activity-info-col alignright">
								<div class="e20r-int-table">
									<div class="e20r-act-content-row">
										<p class="e20r-content-col">
											<span class="e20r-exercise-label"><?php _e("Client", "e20rtracker"); ?>: </span>
											<span class="e20r-exercise-value"><?php echo $current_user->user_firstname; ?></span>
										</p>
									</div>
								</div>
							</div>
							<!-- <div class="spacer">&nbsp;</div> -->
						</div><!-- end of e20r-exercise-row -->
						<div class="spacer">&nbsp;</div>
					</div><!-- COMPLETE: end of table header -->
					<div class="spacer">&nbsp;</div>
					<div class="e20r-activity-table-body">
					<?php
					foreach ( $groups as $k => $g ) {

						$recorded = isset( $g->saved_exercises ) ? $g->saved_exercises : array();
						$gcount = $k + 1;

						?>
						<div class="e20r-exercise-row">
							<div class="e20r-activity-info-col">
								<div class="e20r-int-table exercise-header">
									<div class="e20r-act-content-row">
										<p class="e20r-content-col">
											<span class="e20r-activity-label"><?php _e( "Group", "e20rtracker"); ?>: </span>
											<span class="e20r-activity-var"><?php echo $gcount; ?></span>
										</p>
										<p class="e20r-content-col">
											<span class="e20r-activity-label"><?php _e( "Sets", "e20rtracker"); ?>: </span>
											<span class="e20r-activity-var"><?php echo $g->group_set_count; ?></span>
										</p>
										<p class="e20r-content-col">
											<span class="e20r-activity-label"><?php _e( "Tempo", "e20rtracker"); ?>: </span>
											<span class="e20r-activity-var"><?php echo $g->group_tempo; ?></span>
										</p>
										<p class="e20r-content-col">
											<span class="e20r-activity-label"><?php _e( "Rest", "e20rtracker"); ?>: </span>
											<span class="e20r-activity-var"><?php echo $g->group_rest; ?></span>
										</p>
									</div>
								</div> <!-- End of e20r-int-table -->
							</div>
							<!-- <div class="spacer">&nbsp;</div> -->
						</div> <!-- end of exercise-row -->
						<div class="spacer">&nbsp;</div>
						<?php
							foreach( $g->exercises as $exKey => $exId ) {

								$e20rExercise->set_currentExercise( $exId );
								?>
						<div class="e20r-exercise-row">
							<div class="e20r-activity-info-col">
								<div class="e20r-activity-exercise"><?php echo $e20rExercise->print_exercise( true ); ?></div>
							</div>
							<!-- <div class="spacer">&nbsp;</div> -->
						</div><!-- end of exercise-row -->
						<div class="spacer">&nbsp;</div>
						<div class="e20r-exercise-row e20r-exercise-tracking-row startHidden">
							<div class="e20r-activity-info-col">
								<div class="e20r-activity-exercise-tracking">
								<table class="e20r-resp-table">
									<thead class="e20r-resp-table-header">
									<tr>
										<td class="e20r-td-input-count"><div class="e20r-activity-group-track-s e20r-activity-var"><?php _e("Set", "e20rtracker"); ?></div></td>
										<td class="e20r-td-input-activity"><div class="e20r-activity-group-track-l e20r-activity-var"><?php _e("Weight", "e20rtracker"); ?></div></td>
										<td class="e20r-td-input-activity"><div class="e20r-activity-group-track-r e20r-activity-var"><?php _e("Reps", "e20rtracker"); ?></div></td>
										<td></td>
									</tr>
									</thead>
									<tbody class="e20r-resp-table-body">
									<?php
										for ( $i = 1 ; $i <= $g->group_set_count ; $i++ ) {

											$weight = isset($recorded[$exKey]->set[$i]->weight) ? $recorded[$exKey]->set[$i]->weight : null;
											$reps = isset($recorded[$exKey]->set[$i]->reps) ? $recorded[$exKey]->set[$i]->reps : null;
											$when= isset($recorded[$exKey]->set[$i]->recorded) ? $recorded[$exKey]->set[$i]->recorded : null;
											$ex_id = isset($recorded[$exKey]->set[$i]->id) ? $recorded[$exKey]->set[$i]->ex_id : null;
											$id = isset($recorded[$exKey]->set[$i]->id) ? $recorded[$exKey]->set[$i]->id : null;
											?>
										<tr class="e20r-edit e20r-exercise-set-row">
											<td data-th="Set" class="e20r-td-input-count">
												<input type="hidden" class="e20r-activity-input-group_no" name="e20r-activity-exercise-group_no[]" value="<?php echo $k; ?>" >
												<input type="hidden" class="e20r-activity-input-set_no" name="e20r-activity-exercise-set_no[]" value="<?php echo $i; ?>" >
												<input type="hidden" class="e20r-activity-input-record_id" name="e20r-activity-exercise-record_id[]" value="<?php echo $id; ?>" >
												<input type="hidden" class="e20r-activity-input-recorded" name="e20r-activity-exercise-recorded[]" value="<?php echo $when; ?>" >
												<input type="hidden" class="e20r-activity-input-ex_id" name="e20r-activity-exercise-ex_id[]" value="<?php echo $ex_id; ?>" >
												<input type="hidden" class="e20r-activity-input-ex_key" name="e20r-activity-exercise-ex_key[]" value="<?php echo $exKey; ?>" >
												<input type="hidden" class="e20r-activity-input-weight_h" name="e20r-activity-exercise-weight_h[]" value="<?php echo $weight; ?>" >
												<input type="hidden" class="e20r-activity-input-reps_h" name="e20r-activity-exercise-reps_h[]" value="<?php echo $reps; ?>" >
												<span class="e20r-saved-set-number"><?php echo $i; ?>:</span>
											</td>
	                                        <td data-th="Weight" class="e20r-td-input-activity"><input type="text" class="e20r-activity-input-weight" name="e20r-activity-exercise-weight[]" value="<?php echo $weight; ?>" ></td>
									        <td data-th="Reps" class="e20r-td-input-activity"><input type="number" class="e20r-activity-input-reps" name="e20r-activity-exercise-reps[]" value="<?php echo $reps; ?>" ></td>
									        <td data-th="" class="e20r-td-input-button"><button class="e20r-save-set-row alignright e20r-button<?php echo ( empty( $weight ) || empty($reps) ) ? '' : ' startHidden'; ?>">Save</button></td>
	                                    </tr>
										<tr class="e20r-saved startHidden">
											<td data-th="Set" class="e20r-td-input-count"><span class="e20r-saved-set-number"><?php echo $i; ?>:</span></td>
											<td data-th="Weight" class="e20r-td-input-activity"><span class="e20r-saved-weight-value"><a href="javascript:" class="e20r-edit-weight-value"><?php echo empty( $weight ) ? 0 : $weight; ?></a></span></td>
											<td data-th="Reps" class="e20r-td-input-activity"><span class="e20r-saved-rep-value"><a href="javascript:" class="e20r-edit-rep-value"><?php echo empty( $reps ) ? 0 : $reps; ?></a></span></td>
										</tr>
									<?php } ?>
									</tbody>
								</table>
								<div class="spacer">&nbsp;</div>
							</div>
							</div>
						</div> <!-- end of exercise tracking row -->
						<div class="spacer">&nbsp;</div>
					<?php }
					} ?>
					</div>
					<div class="spacer">&nbsp;</div>
					<div class="e20r-activity-table-footer">
						<div class="e20r-activity-exercise-row">
							<button id="e20r-activity-input-button" class="e20r-button alignright startHidden"><?php _e("Click to complete", "e20rtracker" ); ?></button>
						</div>
						<div class="spacer">&nbsp;</div>
					</div>
					<div class="spacer">&nbsp;</div>
			</div>
			<div class="modal"><!-- At end of form --></div>
			</form>
		<?php
		}

		$html = ob_get_clean();

		return $html;
	}

    public function viewSettingsBox( $workoutData ) {

        global $post;
	    global $e20rWorkout;
	    global $e20rTracker;
	    global $e20rProgram;

	    dbg("e20rWorkoutView::viewWorkoutSettingsBox() - Loading program list for future use:");

	    $programs = $e20rProgram->getProgramList();

        // dbg("e20rWorkoutView::viewWorkoutSettingsBox() - Supplied data: " . print_r($workoutData, true));

	    if ( !isset( $workoutData->days ) || count( $workoutData->days ) == 0 ) {
		    dbg("e20rWorkoutView::viewWorkoutSettingsBox() - Days contains: ");
		    dbg($workoutData->days);
		    $workoutData->days = array();
	    }

        ?>
        <style>
            .select2-container { min-width: 75px; max-width: 250px; width: 100%;}
        </style>
        <?php wp_nonce_field('e20r-tracker-data', 'e20r-tracker-workout-settings-nonce'); ?>
        <div class="e20r-editform" style="width: 100%;">
            <input type="hidden" name="hidden-e20r-program-id" id="hidden-e20r-workout-id" value="<?php echo ( ( isset($workoutData->id) ) ? $workoutData->id : $post->ID ); ?>">
            <table id="e20r-workout-settings" class="wp-list-table widefat fixed">
                <tbody id="e20r-workout-tbody">
                    <tr id="<?php echo ( isset($workoutData->id) ) ? $workoutData->id : $post->ID; ?>" class="workout-inputs">
                        <td colspan="3">
                            <table class="sub-table wp-list-table widefat fixed">
                                <tbody>
                                <tr>
                                    <th class="e20r-label header" style="width: 10%;"><label for="e20r-workout-workout_ident">Workout</label></th>
                                    <th class="e20r-label header" style="width: 10%;"><label for="e20r-workout-phase">Phase</label></th>
	                                <th class="e20r-label header" style="width: 40%;"><?php _e("On what weekdays", "e20rtracker"); ?></th>
	                                <th class="e20r-label header" style="width: 40%;"><?php _e("Programs", "e20rtracker"); ?></th>
                                </tr>
                                <tr>
                                    <td class="select-input" style="width: 10%;">
                                        <select style="width: 95%;" id="e20r-workout-workout_ident" name="e20r-workout-workout_ident">
                                            <option value="A" <?php selected( $workoutData->workout_ident, 'A'); ?>>A</option>
                                            <option value="B" <?php selected( $workoutData->workout_ident, 'B'); ?>>B</option>
                                            <option value="C" <?php selected( $workoutData->workout_ident, 'C'); ?>>C</option>
                                            <option value="D" <?php selected( $workoutData->workout_ident, 'D'); ?>>D</option>
                                        </select>
                                    </td>
                                    <td class="text-input" style="width: 10%;">
                                        <input style="width: 95%;" type="number" id="e20r-workout-phase" name="e20r-workout-phase" value="<?php echo $workoutData->phase; ?>">
                                    </td>
	                                <td class="select-input" style="width: 40%;">
		                                <select id="e20r-workout-days" name="e20r-workout-days[]" class="select2-container" multiple="multiple">
			                                <option value="1" <?php echo in_array(1, $workoutData->days) ? 'selected="selected"' : ''; ?>><?php _e("Monday", "e20rtracker");?></option>
			                                <option value="2" <?php echo in_array(2, $workoutData->days) ? 'selected="selected"' : ''; ?>><?php _e("Tuesday", "e20rtracker");?></option>
			                                <option value="3" <?php echo in_array(3, $workoutData->days) ? 'selected="selected"' : ''; ?>><?php _e("Wednesday", "e20rtracker");?></option>
			                                <option value="4" <?php echo in_array(4, $workoutData->days) ? 'selected="selected"' : ''; ?>><?php _e("Thursday", "e20rtracker");?></option>
			                                <option value="5" <?php echo in_array(5, $workoutData->days) ? 'selected="selected"' : ''; ?>><?php _e("Friday", "e20rtracker");?></option>
			                                <option value="6" <?php echo in_array(6, $workoutData->days) ? 'selected="selected"' : ''; ?>><?php _e("Saturday", "e20rtracker");?></option>
			                                <option value="7" <?php echo in_array(7, $workoutData->days) ? 'selected="selected"' : ''; ?>><?php _e("Sunday", "e20rtracker");?></option>
		                                </select>
	                                </td>
	                                <td class="select-input" style="width: 40%;">
		                                <select class="select2-container" id="e20r-workout-programs" name="e20r-workout-programs[]" multiple="multiple">
			                                <option value="0">Not configured</option>
			                                <?php
			                                foreach ( $programs as $pgm ) {

				                                if ( !empty( $workoutData->programs ) ) {
				                                    $selected = ( in_array( $pgm->id, $workoutData->programs ) ? ' selected="selected" ' : null);
				                                }
				                                else {
					                                $selected = '';
				                                }
				                                ?>
				                                <option value="<?php echo $pgm->id; ?>"<?php echo $selected; ?>>
					                                <?php echo esc_textarea( $pgm->title ); ?> (#<?php echo $pgm->id; ?>)
				                                </option>
			                                <?php } ?>
		                                </select>
		                                <style>
			                                #e20r-workout-programs {
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
                                        <select id="e20r-workout-assigned_usergroups" name="e20r-workout-assigned_usergroups[]" class="select2-container" multiple="multiple">
	                                        <option id="-1">All Users</option>
                                            <?php

                                            $member_groups = $e20rWorkout->getMemberGroups();

                                            foreach ( $member_groups as $id => $name ) {

	                                            if ( !empty( $workoutData->assigned_usergroups ) ) {
		                                            $selected = in_array( $id, $workoutData->assigned_usergroups ) ? 'selected="selected"' : null;
	                                            }
	                                            else {
		                                            $selected = null;
	                                            }
	                                            ?>
                                                <option value="<?php echo $id; ?>"<?php echo $selected; ?>><?php echo $name; ?></option> <?php
                                            } ?>

                                        </select>
                                    </td>
                                    <td rowspan="2" style="font-size: 50px; font-color: #5c5c5c; font-weight: bold; vertical-align: middle; text-align: center; padding: 20px; margin: 0; position: relative;">
                                        or
                                    </td>
                                    <td class="select-input ">
                                        <select id="e20r-workout-assigned_user_id" name="e20r-workout-assigned_user_id[]" class="select2-container" multiple="multiple">
	                                        <option id="-1">All Users</option>
                                            <?php

                                            $memberArgs = array( 'orderby' => 'display_name' );
                                            $members = get_users( $memberArgs );

                                            foreach ( $members as $userData ) {

                                                $active = $e20rTracker->isActiveUser( $userData->ID );

                                                if ( $active ) { ?>

                                                    <option value="<?php echo $userData->ID; ?>"<?php echo in_array( $userData->ID, $workoutData->assigned_user_id) ? 'selected="selected"' : ''; ?>>
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
                    <tr><td colspan="3"><hr width="100%"/></td></tr>
                    <tr>
                        <td colspan="3">
                            <table class="sub-table wp-list-table widefat fixed">
                                <tbody>
                                <tr>
                                    <th class="e20r-label header indent"><?php _e( "First workout (date)", "e20rtracker" ); ?></th>
                                    <th class="e20r-label header indent"><?php _e( "Last workout (date)", "e20rtracker" ); ?></th>
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
    <?php
    }

    public function newExerciseGroup( $group = null , $group_id = null ) {

        global $e20rExercise;

	    $empty_group = new stdClass();

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
				<th style="width: 15px;" class="e20r-label header"><label for="exercise-order"></label></th>
				<th colspan="2" class="e20r-label header"><label for="e20r-workout-exercise-name"><?php _e('Exercises', 'e20rtracker'); ?></label></th>
				<th class="e20r-label header"><label for="e20r-workout-exercise-type"><?php _e('Type', 'e20rtracker'); ?></label></th>
				<th class="e20r-label header"><label for="e20r-workout-exercise-reps"><?php _e('Reps / Duration', 'e20rtracker'); ?></label></th>
				<th class="e20r-label header"><label for="e20r-workout-exercise-rest"><?php _e('Rest', 'e20rtracker'); ?></label></th>
				<th colspan="2" class="e20r-label header"><?php _e('Actions', 'e20rtracker'); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php

			if ( count( $group->exercises ) > 0 )  {
				$count = 1;

				foreach ( $group->exercises as $exId ) {

					if ( $exId !== 0 ) {
						$exSettings = $e20rExercise->getExerciseSettings( $exId );

						$type = $e20rExercise->getExerciseType( $exSettings->type );

						$exSettings->reps = empty( $exSettings->reps ) ? __( "None", "e20rtracker" ) : $exSettings->reps;
						$exSettings->rest = empty( $exSettings->rest ) ? __( "None", "e20rtracker" ) : $exSettings->rest;

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
						echo '<td><a href="javascript:e20rActivity.editExercise( \'group:' . $groupId . '\', ' . $exSettings->id . ', ' . $count . ')" class="e20r-exercise-edit">' . __( "Update", "e20rtracker" ) . '</a></td>';
						echo '<td><a href="javascript:e20rActivity.removeExercise( \'group:' . $groupId . '\', ' . $exSettings->id . ', ' . $count . ')" class="e20r-exercise-remove">' . __( "Remove", "e20rtracker" ) . '</a></td>';
						echo "</tr>";

						$count++;
						unset( $exSettings );
					}
					else {
						?>
						<tr>
							<td colspan="8">
								<?php _e("No exercises found.", 'e20rtracker'); ?>
							</td>
						</tr>
					<?php

					}
				}
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
		return ob_get_clean();
	}
}