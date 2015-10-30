<?php

/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 12/15/14
 * Time: 1:20 PM
 */
class e20rExerciseView
{

    private $exercises = null;

    public function e20rExerciseView($exerciseData = null)
    {

        $this->exercises = $exerciseData;

    }

    private function generate_video_view()
    {

        global $currentExercise;
        $html = null;

        if (empty($currentExercise->video_link)) {

            $data = wp_get_attachment_image_src(get_post_thumbnail_id($currentExercise->id), 'single-post-thumbnail');

            // No featured image specified and no video link included.
            if (is_null($data[0]) && empty($currentExercise->video_link)) {

                dbg("e20rExerciseViews::generate_video_view() - Using default placeholder image...");
                $currentExercise->image = '<img class="e20r-resize" src="' . E20R_PLUGINS_URL . '/img/strong-cubed-fitness-default.png" alt="' . $currentExercise->title . '">';
            } else {

                $currentExercise->image = '<img class="e20r-resize" src="' . $data[0] . '" alt="' . $currentExercise->title . '">';
            }

            $html = $currentExercise->image;
        }

        if (!empty($currentExercise->video_link)) { ?>
            <?php
            if (!is_ssl()) {

                str_ireplace('https', 'http', $currentExercise->video_link);
            }

            if (($yID = $this->extract_youtube_id($currentExercise->video_link)) === null) {
                // $poster = wp_get_attachment_image_src( get_post_thumbnail_id( $currentExercise->id), 'single-post-thumbnail' );
                // $display = $this->get_embed_video( $currentExercise->video_link, 'center', '16:9', '100', 0 );
                $html = $this->get_embed_video($currentExercise->video_link, 'center', '4:3', '100', 0);
            } else {
                ob_start(); ?>

                <div class="e20r-youtube-container">
                <div class="youtube-player" data-id="<?php echo $yID; ?>"></div>
                </div><?php

                $html = ob_get_clean();
            }
        }

        return $html;
    }

    public function view_exercise_as_columns($show = true)
    {

        global $currentExercise;
        global $e20rExercise;

        $display = null;
        $type_label = '';

        if ($currentExercise->type == 1) {

            dbg("e20rExerciseView::view_exercise_as_columns() - Time is the selected exercise rep type");
            $type_label = __('seconds', 'e20rtracker');
        }

        dbg("e20rExerciseViews::view_exercise_as_columns() - Hidden status is: {$show}");
        $display = $this->generate_video_view();

        ob_start();
        ?>
        <div class="e20r-exercise-table e20r-exercise-detail">
            <div class="spacer">&nbsp;</div>
            <div class="e20r-exercise-table-header e20r-exercise-detail-row-1 clearfix">
                <div class="e20r-exercise-title">
                    <h4 class="e20r-tracker-detail-h4"><?php echo $currentExercise->title; ?></h4>
                </div>
            </div>
            <div class="spacer">&nbsp;</div>
            <div class="e20r-exercise-table-body<?php echo($show == true ? " show" : " startHidden"); ?> e20r-exercise-detail-row-2 clearfix">
                <div class="e20r-exercise-table-column first-column e20r-exercise-reps">
                    <p class="e20r-exercise-description">
                        <span
                            class="e20r-exercise-label"><?php echo $e20rExercise->getExerciseType($currentExercise->type); ?>
                            :</span>
                        <span
                            class="e20r-exercise-value"><?php echo(!in_array($currentExercise->type, array(1, 3)) ? "{$currentExercise->reps} {$type_label}" : "{$currentExercise->reps}"); ?></span>
                    </p>
                </div>
                <div class="e20r-exercise-table-column second-column e20r-right e20r-exercise-rest-time">
                    <p class="e20r-exercise-description">
                        <span class="e20r-exercise-label"><?php _e('Rest', 'e20rtracker'); ?>:</span>
                        <?php
                        if (!empty($currentExercise->rest)) { ?>
                            <span
                                class="e20r-exercise-value"><?php echo $currentExercise->rest; ?><?php _e('seconds', 'e20rtracker'); ?></span><?php
                        } else { ?>
                            <span class="e20r-exercise-value"><?php _e('N/A', 'e20rtracker'); ?></span><?php
                        } ?>
                    </p>
                </div>
            </div>
            <div class="e20r-exercise-detail-row-3 clearfix">
                <div class="e20r-video e20r-exercise-table-column first-column e20r-exercise-video-column clearfix">
                    <div class="e20r-exercise-video">
                        <?php echo(!empty($display) ? $display : ''); ?>
                    </div>
                    <input type="hidden" class="e20r-display-exercise-id" name="e20r-activity-exercise-id[]"
                           value="<?php echo $currentExercise->id; ?>"/>
                </div>
                <div class="e20r-exercise-table-column second-column e20r-exercise-description clearfix">
                    <p><?php echo wpautop($currentExercise->descr); ?></p>
                </div>
            </div>
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    // Display the exercise entry for an activity page
    public function view_exercise_as_row($show = true, $show_reps = false )
    {

        global $currentExercise;
        global $e20rExercise;

        $display = null;
        $type_label = '';

        if ($currentExercise->type == 1) {

            dbg("e20rExerciseView::view_exercise_as_row() - Time is the selected exercise rep type");
            $type_label = __('seconds', 'e20rtracker');
        }

        dbg("e20rExerciseViews::view_exercise_as_row() - Hidden status is: {$show}");
        $display = $this->generate_video_view();

        // dbg("e20rExerciseView::view_exercise_as_row() - Display: {$display}");

        ob_start();
        ?>
        <div class="e20r-exercise-table e20r-exercise-detail">
            <div class="spacer">&nbsp;</div>
            <div class="e20r-exercise-table-header e20r-exercise-detail-row">
                <div class="e20r-exercise-table-column first-column e20r-exercise-title">
                    <h4 class="e20r-tracker-detail-h4"><?php echo $currentExercise->title; ?></h4>
                </div>
            </div>
            <div class="spacer">&nbsp;</div>
            <div class="e20r-exercise-table-body<?php echo($show == true ? " show" : " startHidden"); ?>">
                <div class="e20r-exercise-detail-row e20r-video">
                    <div class="e20r-exercise-table-column">
                        <div class="e20r-exercise-video">
                            <?php echo(!empty($display) ? $display : ''); ?>
                        </div>
                        <input type="hidden" class="e20r-display-exercise-id" name="e20r-activity-exercise-id[]"
                               value="<?php echo $currentExercise->id; ?>"/>
                    </div>
                </div>
                <div class="spacer">&nbsp;</div>
                <div class="e20r-exercise-detail-row">
                    <div class="e20r-exercise-table-column first-column e20r-exercise-reps">
                        <p class="e20r-exercise-description">
                            <span
                                class="e20r-exercise-label"><?php echo $e20rExercise->getExerciseType($currentExercise->type); ?>
                                :</span>
                            <span
                                class="e20r-exercise-value"><?php echo(!in_array($currentExercise->type, array(1, 3)) ? "{$currentExercise->reps} {$type_label}" : "{$currentExercise->reps}"); ?></span>
                        </p>
                    </div>
                    <div class="e20r-exercise-table-column second-column e20r-exercise-rest-time">
                        <p class="e20r-exercise-description">
                            <span class="e20r-exercise-label"><?php _e('Rest', 'e20rtracker'); ?>:</span>
                            <?php
                            if (!empty($currentExercise->rest)) { ?>
                                <span
                                    class="e20r-exercise-value"><?php echo $currentExercise->rest; ?><?php _e('seconds', 'e20rtracker'); ?></span><?php
                            } else { ?>
                                <span class="e20r-exercise-value"><?php _e('N/A', 'e20rtracker'); ?></span><?php
                            } ?>
                        </p>
                    </div>
                </div>
                <div class="spacer">&nbsp;</div>
                <div class="e20r-exercise-detail-row">
                    <div class="e20r-exercise-table-column first-column e20r-exercise-description">
                        <p><?php echo wpautop($currentExercise->descr); ?></p>
                    </div>
                </div>
                <div class="spacer">&nbsp;</div>
            </div>
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    /**
     * Returns the embedded video.
     *
     * This method is utilized by both shortcode and widget.
     *
     * @param string $url the URL of the video
     * @param string $align the alignment of the video
     * @param string $aspect the aspect ratio of the video
     * @param int $width the width of the video in percent
     * @param int $autoplay either 0 for autoplay off or 1 for autoplay on
     * @return string the whole HTML code with the embed and the containing divs
     * @since 1.2.0
     */
    public function get_embed_video($url, $align, $aspect, $width = null, $autoplay = 0)
    {

        $code = $this->before_video($align, $aspect, $width);
        $code .= $this->embed_video($url, $autoplay);
        $code .= $this->after_video();

        return $code;
    }

    /**
     * Returns content to be printed before the video.
     *
     * @param string $align the alignment of the video
     * @param string $aspect the aspect ratio of the video
     * @param int $width the width of the video in percent
     * @return string HTML code containing two divs with the necessary CSS classes attached
     * @since 1.0.0
     */
    private function before_video($align, $aspect, $width = null)
    {

        $code = '<div class="e20r-exercise-video-' . $align . '"';

        if (isset ($width)) {

            $code .= ' style="width: ' . $width . '%;"';
        }

        $code .= '>';
        $code .= '<div class="e20r-exercise-video-wrapper size-' . $aspect . '">';

        return $code;
    }

    private function extract_youtube_id($url)
    {

        $video_id = null;

        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
            $video_id = $match[1];
        }

        return $video_id;
    }

    /**
     * Returns the output for the actual oEmbed media element.
     *
     * Width or height parameters in the oEmbed code are removed so that the element can size dynamically.
     *
     * @param string $url the URL of the video
     * @param int $autoplay either 0 for autoplay off or 1 for autoplay on
     * @return string HTML code containing the oEmbed
     * @since 1.0.0
     */
    private function embed_video($url, $autoplay = 0)
    {

        $regex = "/ (width|height)=\"[0-9\%]*\"/";

        $embed_code = wp_oembed_get($url, array('width' => '100%', 'height' => '100%', 'autoplay' => $autoplay, 'rel' => 0));

        if (!$embed_code) {

            return '<strong>' . __('Error: Unsupported video host/service', 'e20rtracker') . '</strong>';
        }

        return preg_replace($regex, '', $embed_code);
    }

    /**
     * Returns content to be printed after the video.
     *
     * @return string HTML code containing two closing divs
     * @since 1.0.0
     */
    private function after_video()
    {

        $code = '</div>';
        $code .= '</div>';

        return $code;
    }

    public function viewSettingsBox($exerciseData, $types)
    {

        dbg("e20rExerciseView::viewExerciseSettingsBox() - Supplied data: ");
        dbg($exerciseData);

        ?>
        <form action="" method="post">
            <?php wp_nonce_field('e20r-tracker-data', 'e20r-tracker-exercise-settings'); ?>
            <div class="e20r-editform">
                <input type="hidden" name="hidden-e20r-program-id" id="hidden-e20r-exercise-id"
                       value="<?php echo((isset($exerciseData->id)) ? $exerciseData->id : 0); ?>">
                <table id="e20r-exercise-settings" class="e20r-exercise-settings wp-list-table widefat fixed">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label
                                for="e20r-exercise-type"><?php _e("Type", "e20rtracker"); ?></label></th>
                        <th class="e20r-label header"><label
                                for="e20r-exercise-reps"><?php _e("Repetitions / Duration", "e20rtracker"); ?></label>
                        </th>
                        <th class="e20r-label header"><label
                                for="e20r-exercise-rest"><?php _e("Rest (seconds)", "e20rtracker"); ?></label></th>
                        <th class="e20r-label header"><label
                                for="e20r-exercise-shortcode"><?php _e("Shortcode", "e20rtracker"); ?></label></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr id="e20r-exercise-<?php echo isset($exerciseData->ID) ? $exerciseData->ID : $exerciseData->id; ?>"
                        class="program-inputs">
                        <td class="text-input">
                            <select class="select2-container" name="e20r-exercise-type" id="e20r-exercise-type">
                                <option value="0" <?php selected(0, $exerciseData->type); ?>></option>
                                <?php
                                foreach ($types as $key => $descr) { ?>
                                    <option value="<?php echo $key; ?>"<?php selected($key, $exerciseData->type); ?>><?php echo $descr; ?></option><?php
                                }
                                ?>
                            </select>
                        </td>
                        <td class="text-input">
                            <input type="number" id="e20r-exercise-reps" name="e20r-exercise-reps"
                                   value="<?php echo $exerciseData->reps; ?>">
                        </td>
                        <td class="text-input">
                            <input type="number" id="e20r-exercise-rest" name="e20r-exercise-rest"
                                   value="<?php echo $exerciseData->rest; ?>">
                        </td>
                        <td class="text-input">
                            <input type="text" id="e20r-exercise-shortcode" name="e20r-exercise-shortcode"
                                   value="<?php echo $exerciseData->shortcode; ?>">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <hr width="100%"/>
                        </td>
                    </tr>
                    <tr class="program-inputs">
                        <th class="e20r-label header"><label
                                for="e20r-exercise-video_link"><strong><?php _e("Video link", "e20rtracker"); ?>
                                    :</strong></label></th>
                        <td class="text-input" colspan="3">
                            <input type="text" style="width: 100%;" id="e20r-exercise-video_link"
                                   name="e20r-exercise-video_link" value="<?php echo $exerciseData->video_link; ?>">
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </form>
        <?php
    }
}