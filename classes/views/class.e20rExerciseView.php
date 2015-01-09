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

    public function viewSettingsBox( $exerciseData ) {

        dbg("e20rExerciseView::viewExerciseSettingsBox() - Supplied data: " . print_r($exerciseData, true));
        ?>
        <form action="" method="post">
            <?php wp_nonce_field('e20r-tracker-data', 'e20r-tracker-exercise-settings'); ?>
            <div class="e20r-editform">
                <input type="hidden" name="hidden-e20r-program-id" id="hidden-e20r-exercise-id" value="<?php echo ( ( ! empty($exerciseData) ) ? $exerciseData->ID : 0 ); ?>">
                <table id="e20r-exercise-settings">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label for="e20r-exercise-reps">Repetitions</label></th>
                        <th class="e20r-label header"><label for="e20r-exercise-rest">Rest (seconds)</label></th>
                    </tr>
                    <tr>
                        <td colspan="5"><hr width="100%"/></td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php

                        ?>
                        <tr id="<?php echo $exerciseData->ID; ?>" class="program-inputs">
                            <td class="text-input">
                                <input type="number" id="e20r-exercise-reps" name="e20r-exercise-reps" value="<?php echo $exerciseData->reps; ?>">
                            </td>
                            <td class="text-input">
                                <input type="number" id="e20r-exercise-rest" name="e20r-exercise-rest" value="<?php echo $exerciseData->rest; ?>">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </form>
    <?php
    }
}