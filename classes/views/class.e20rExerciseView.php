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

	public function printExercise() {
		global $currentExercise;

		ob_start();
		?>
		<div class="e20r-display-exercise-div">
			<table class="e20r-exercise-detail">
				<tbody>
					<tr class="e20r-display-exercise-row">
						<td rowspan="2" class="e20r-display-exercise-image">
							<?php echo $currentExercise->image; ?>
						</td>
					</tr>
					<tr class="e20r-display-exercise-row">
						<td></td>
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
                <input type="hidden" name="hidden-e20r-program-id" id="hidden-e20r-exercise-id" value="<?php echo ( ( ! empty($exerciseData) ) ? $exerciseData->id : 0 ); ?>">
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