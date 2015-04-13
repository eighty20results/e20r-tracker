<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 12/15/14
 * Time: 1:20 PM
 */

class e20rExerciseView {

    private $exercises = null;

    public function e20rExerciseView( $exerciseData = null ) {

        $this->exercises = $exerciseData;

    }

	// Display the exercise entry for an activity page
	public function printExercise() {

		global $currentExercise;
		global $e20rExercise;

		$type_label = '';

		if ( $currentExercise->type == 1 ) {

			dbg("e20rExerciseView::printExercise() - Time is the selected exercise rep type");
			$type_label = __('seconds', 'e20rtracker');
		}

		$display = null;

		ob_start();
		?>
		<div class="e20r-display-exercise-div">
			<table class="e20r-exercise-detail">
				<input type="hidden" class="e20r-display-exercise-id" name="e20r-activity-exercise-id[]" value="<?php echo $currentExercise->id; ?>" >
				<tbody>
				<tr class="e20r-display-exercise-row">
						<td rowspan="4" class="e20r-display-exercise-image">
							<div class="video">
							<?php

							if ( empty( $currentExercise->video_link ) ) {

								$data = wp_get_attachment_image_src( get_post_thumbnail_id( $currentExercise->id), 'single-post-thumbnail' );
								$currentExercise->image = '<img class="e20r-resize" src="' . $data[0] .'" height="221" width="393" alt="Exercise">';
								$display = $currentExercise->image;
							}

							if ( ! empty( $currentExercise->video_link )) {

								$poster = wp_get_attachment_image_src( get_post_thumbnail_id( $currentExercise->id), 'single-post-thumbnail' );

								$args = array(
									'src'      => esc_url( $currentExercise->video_link ),
									'width'    => 393,
									'height'    => 221,
									'poster'   => ( empty( $currentExercise->image ) ) ? $poster[0] : null,
								);

								$display = wp_video_shortcode( $args );
							}

							echo $display;
							?>
							</div>
						</td>
					</tr>
				<tr class="e20r-display-exercise-row">
					<td class="e20r-exercise-title"><h4><?php echo $currentExercise->title; ?></h4></td>
				</tr>
				<tr class="e20r-display-exercise-row">
						<td class="e20r-exercise-reps">
							<span class="e20r-exercise-label"><?php echo $e20rExercise->getExerciseType( $currentExercise->type ); ?>:</span>
							<span class="e20r-exercise-value"><h4><?php echo ( !in_array( $currentExercise->type, array( 0, 2 ) ) ? "{$currentExercise->reps} {$type_label}" : "{$currentExercise->reps}" ); ?></span></h4>
						</td>
					</tr>
					<tr class="e20r-display-exercise-row">
						<td class="e20r-exercise-rest-time">
							<span class="e20r-exercise-label"><?php _e('Rest', 'e20rtracker'); ?>:</span>
							<h4>
							<?php
							if ( ! empty( $currentExercise->rest ) ) { ?>
								<span class="e20r-exercise-value"><?php echo $currentExercise->rest; ?> <?php _e('seconds', 'e20rtracker'); ?></span><?php
							}
							else { ?>
								<span class="e20r-exercise-value"><?php _e('N/A', 'e20rtracker'); ?></span><?php
							}
							?>
							</h4>
						</td>
					</tr>
					<tr class="e20r-display-exercise-descr-row">
						<td colspan="2" style="padding-left: 0;"><div class="e20r-exercise-description"><?php echo $currentExercise->descr ; ?></div></td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
		$html = ob_get_clean();

		return $html;
	}

    public function viewSettingsBox( $exerciseData, $types ) {

        dbg( "e20rExerciseView::viewExerciseSettingsBox() - Supplied data: " );
	    dbg( $exerciseData );

        ?>
        <form action="" method="post">
            <?php wp_nonce_field('e20r-tracker-data', 'e20r-tracker-exercise-settings'); ?>
            <div class="e20r-editform">
                <input type="hidden" name="hidden-e20r-program-id" id="hidden-e20r-exercise-id" value="<?php echo ( ( ! isset($exerciseData->id) ) ? $exerciseData->id : 0 ); ?>">
                <table id="e20r-exercise-settings">
                    <thead>
	                    <tr>
		                    <th class="e20r-label header"><label for="e20r-exercise-type"><?php _e("Type", "e20rtracker");?></label></th>
	                        <th class="e20r-label header"><label for="e20r-exercise-reps"><?php _e("Repetitions / Duration", "e20rtracker");?></label></th>
	                        <th class="e20r-label header"><label for="e20r-exercise-rest"><?php _e("Rest (seconds)", "e20rtracker");?></label></th>
		                    <th class="e20r-label header"><label for="e20r-exercise-shortcode"><?php _e("Shortcode", "e20rtracker");?></label></th>
	                    </tr>
                    </thead>
                    <tbody>
                        <tr id="e20r-exercise-<?php echo isset( $exerciseData->ID ) ? $exerciseData->ID : $exerciseData->id; ?>" class="program-inputs">
	                        <td class="text-input">
		                        <select class="select2-container" name="e20r-exercise-type" id="e20r-exercise-type">
			                        <option value="0" <?php selected(0, $exerciseData->type); ?>></option>
			                        <?php
			                            foreach( $types as $key => $descr ) { ?>
				                            <option value="<?php echo $key; ?>"<?php selected( $key, $exerciseData->type); ?>><?php echo $descr; ?></option><?php
			                            }
			                        ?>
		                        </select>
	                        </td>
	                        <td class="text-input">
                                <input type="number" id="e20r-exercise-reps" name="e20r-exercise-reps" value="<?php echo $exerciseData->reps; ?>">
                            </td>
                            <td class="text-input">
                                <input type="number" id="e20r-exercise-rest" name="e20r-exercise-rest" value="<?php echo $exerciseData->rest; ?>">
                            </td>
	                        <td class="text-input">
		                        <input type="text" id="e20r-exercise-shortcode" name="e20r-exercise-shortcode" value="<?php echo $exerciseData->shortcode; ?>">
	                        </td>
                        </tr>
                        <tr>
	                        <td colspan="4"><hr width="100%"/></td>
                        </tr>
                        <tr class="program-inputs">
	                        <th class="e20r-label header"><label for="e20r-exercise-video_link"><strong><?php _e("Video link", "e20rtracker");?>:</strong></label></th>
	                        <td class="text-input" colspan="3">
		                        <input type="text" style="width: 100%;" id="e20r-exercise-video_link" name="e20r-exercise-video_link" value="<?php echo $exerciseData->video_link; ?>">
	                        </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </form>
    <?php
    }
}