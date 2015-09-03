<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rCheckinView extends e20rSettingsView {

    private $checkins = null;

    public function __construct() {

        parent::__construct('checkin', 'e20r_checkins' );
    }

    public function setCheckin( $type, $config ) {

        $this->checkins[$type] = $config;

    }

    public function view_actionAndActivityCheckin( $config, $action, $activity, $habitEntries, $note = null) {

        global $e20rTracker;
        global $e20rArticle;
        global $currentArticle;
        global $currentProgram;

        $skipYN = false;

        if ( ! is_array( $action ) ) {
            $this->setError("No check-in recorded");
        }

        $trackerOpts = get_option('e20r-tracker');
        // $article = $e20rArticle->getSettings($config->articleId);
        $article = $currentArticle;

        if ( $habitEntries[0]->short_name == 'null_action') {

            dbg("e20rCheckinView::view_actionAndActivityCheckin() - Have a null action, so skip Yes/No radio buttons");
            $skipYN = true;
        }

        ob_start();

        if ( ( $config->delay < $config->delay_byDate ) && is_null( $config->maxDelayFlag ) ){ ?>
            <div class="date-past-future notice"><?php echo sprintf( __('You are viewing a day in the past.  <a href="%s">Back to Today</a>', "e20rtracker"), $config->url );?></div>
            <?php
        }

        if ( ( $config->delay > $config->delay_byDate ) && is_null( $config->maxDelayFlag ) ) { ?>
            <div class="date-past-future notice"><?php echo sprintf( __('You are viewing a day in the future.  <a href="%s">Back to Today</a>', "e20rtracker"), $config->url );?></div>
            <?php
        }

        if ( isset( $config->maxDelayFlag ) && ( $config->maxDelayFlag >= CONST_MAXDAYS_FUTURE ) ) {

            // The user is attempting to view a day >2 days after today.
            ?>
            <div class="date-past-future orange-notice">
                <h4><?php _e("We love that you're interested!", "e20rtracker"); ?></h4>
                <p><?php echo sprintf( __('However, we feel there\'s already plenty to keep yourself busy with for now, so please return
                    <a href="%s">to the dashboard</a> for today\'s lesson.', "e20rtracker" ), $config->url ); ?></p>
            </div><?php
        }
		else {
        ?>
        <noscript>
            <div class="red-notice" style="font-size: 18px; line-height: 22px;">
                <strong style="display: block; margin-bottom: 8px;"><?php _e("There's a little problem...", "e20rtracker"); ?></strong>
                <?php echo sprintf( __('You are using a web browser that doesn\'t have JavaScript enabled! <br/><br/>
                JavaScript is a technology that lets your web browser do cool stuff and we use it a lot throughout this site.
                To get something useful from the %s platform, you will need to enable JavaScript.
                Start by checking your browser\'s help pages or support forums or "the Google". You\'ll probably find
                step by step instructions.<br/>
                Or you can contact your coach and we\'ll be happy to help you.', "e20rtracker"), $currentProgram->title ); ?></div>
        </noscript>
        <div class="clear-after"></div>
        <div id="e20r-checkin-daynav">
	        <?php if ( $config->delay >= 1 ): ?>
                <p class="e20r-checkin-yesterday-nav">
                    <a id="e20r-checkin-yesterday-lnk" href="<?php echo $config->url; ?>"><?php echo $config->yesterday; ?></a>
                    <input type="hidden" name="e20r-checkin-day-yesterday" id="e20r-checkin-yesterday" value="<?php echo ( ( $config->prev ) >= 0 ? ( $config->prev ) : 0 ); ?>">
                </p>
			<?php endif; ?>
                <p class="e20r-checkin-tomorrow-nav">
                    <a id="e20r-checkin-tomorrow-lnk" href="<?php echo $config->url; ?>"><?php echo $config->tomorrow; ?></a>
                    <input type="hidden" name="e20r-checkin-day-tomorrow" id="e20r-checkin-tomorrow" value="<?php echo ( $config->next  ); ?>">
                </p>
        </div>
        <div class="clear-after"></div>
        <table id="e20r-checkin-content">
            <tbody>
            <tr>
                <td id="e20r-checkin-activity" class="e20r-content-cell">
                    <?php echo ( ! isset( $config->activityExcerpt ) ? '<h4 class="e20r-checkin-header">'.  __("Activity", "e20rtracker") . '</h4><p class="e20r-descr">' . __("No activity scheduled.", "e20rtracker") .'</p>' : $config->activityExcerpt ); ?>
                </td>
                <td id="e20r-checkin-lesson" class="e20r-content-cell">
                    <?php echo ( ! isset( $config->actionExcerpt ) ? '<h4 class="e20r-checkin-header">'. __("Lesson", "e20rtracker") . '</h4><p class="e20r-descr">' . __("No lesson scheduled", "e20rtracker") . '</p>' : $config->actionExcerpt ); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <div class="clear-after"></div>
        <div id="e20r-daily-checkin-container" class="progress-container">
            <h3><?php _e("Daily Coaching <span>Update</span>", "e20rtracker"); ?></h3>
            <div id="e20r-daily-checkin-canvas" class="progress-canvas">
                <?php wp_nonce_field('e20r-checkin-data', 'e20r-checkin-nonce'); ?>
                <input type="hidden" name="e20r-checkin-article_id" id="e20r-checkin-article_id" value="<?php echo isset( $article->id ) ? $article->id : null; ?>" />
	            <input type="hidden" name="e20r-checkin-assignment_id" id="e20r-checkin-assignment_id" value="<?php echo ( isset( $config->assignment_id ) ? $config->assignment_id : null ); ?>" />
                <input type="hidden" name="e20r-checkin-checkin_date" id="e20r-checkin-checkin_date" value="<?php echo $e20rTracker->getDateFromDelay( ( $config->delay - 1) ); ?>" />
	            <input type="hidden" name="e20r-checkin-checkedin_date" id="e20r-checkin-checkedin_date" value="<?php echo date('Y-m-d', current_time('timestamp') ); ?>" />
                <input type="hidden" name="e20r-checkin-program_id" id="e20r-checkin-program_id" value="<?php echo isset( $action->program_id ) ? $action->program_id : -1 ; ?>" />
                <div class="clear-after">
                    <div class="action-activity-row">
                    <fieldset class="did-you workout">
                        <legend><?php _e("Did you complete your activity today?", "e20rtracker"); ?></legend>
                        <div>
                            <input type="hidden" name="e20r-checkin-id" class="e20r-checkin-id" value="<?php echo $activity->id; ?>" />
                            <input type="hidden" name="e20r-checkin-checkin_type" class="e20r-checkin-checkin_type" value="<?php echo CHECKIN_ACTIVITY; ?>" />
                            <input type="hidden" name="e20r-checkin-checkin_short_name" class="e20r-checkin-checkin_short_name" value="<?php echo $activity->checkin_short_name; ?>" />
                            <ul> <!-- style="max-width: 300px; min-width: 200px; width: 290px;" -->
                                <li>
                                    <input type="radio" value="1" <?php checked( $activity->checkedin, 1 ); ?> name="did-activity-today" id="did-activity-today-radio-1" />
                                    <label for="did-activity-today-radio-1"><?php _e("I did my activity", "e20rtracker"); ?></label>
                                </li>
                                <li>
                                    <input type="radio" value="2" <?php checked( $activity->checkedin, 2 ); ?> name="did-activity-today" id="did-activity-today-radio-2" />
                                    <label for="did-activity-today-radio-2"><?php _e("I was active, but activity isn't complete", "e20rtracker"); ?></label>
                                </li>
                                <li>
                                    <input type="radio" value="0" <?php checked( $activity->checkedin, 0 ); ?> name="did-activity-today" id="did-activity-today-radio-3" />
                                    <label for="did-activity-today-radio-3"><?php _e("I missed my activity", "e20rtracker"); ?></label>
                                </li>
                                <li>
                                    <input type="radio" value="3" <?php checked( $activity->checkedin, 3 ); ?> name="did-activity-today" id="did-activity-today-radio-4" />
                                    <label for="did-activity-today-radio-4"><?php _e("There was no activity scheduled", "e20rtracker"); ?></label>
                                </li>
                            </ul>
                            <div class="notification-entry-saved" style="width: 300px; display: none;">
                                <div><?php _e("Activity check-in saved", "e20rtracker"); ?></div>
                            </div>
                        </div>

                    </fieldset><!--//left-->
                    <fieldset class="did-you habit">
                        <legend style="padding-bottom: 9px;"><?php _e("Did you complete your action today?", "e20rtracker"); ?></legend>
                        <div>
                            <p style="margin-bottom: 4px;" id="habit-names">
                            <?php
                            $cnt = count($habitEntries);

                            dbg("e20rCheckinView::viewCheckinField() - We're dealing with {$cnt} habits today");
                            switch ( $cnt ) {
                                case 3: ?>
                                    <span class="faded"><?php echo $habitEntries[2]->item_text; ?><br/></span>
                                <?php

                                case 2: ?>
                                    <span class="faded"><?php echo $habitEntries[1]->item_text; ?><br/></span>
                                <?php

                                case 1: ?>
                                    <strong><?php echo $habitEntries[0]->item_text; ?></strong></p>
                            <?php
                            }
                            ?>
                            <input type="hidden" name="e20r-checkin-id" class="e20r-checkin-id" value="<?php echo $action->id; ?>" />
                            <input type="hidden" name="e20r-checkin-checkin_type" class="e20r-checkin-checkin_type" value="<?php echo CHECKIN_ACTION; ?>" />
                            <input type="hidden" name="e20r-checkin-checkin_short_name" class="e20r-checkin-checkin_short_name" value="<?php echo $action->checkin_short_name; ?>" />
                            <?php if ( ! $skipYN ): ?>
                            <ul> <!-- style="max-width: 300px; width: 285px;" -->
                                <li <?php echo is_null( $action->checkedin) ? null : ( $action->checkedin == 1 ? 'class="active";' : 'style="display: none;"'); ?>><input type="radio" value="1" <?php checked( $action->checkedin, 1 ); ?> name="did-action-today" id="did-action-today-radio-1" /><label for="did-action-today-radio-1"><?php _e("Yes", "e20rtracker");?></label></li>
                                <li <?php echo is_null( $action->checkedin) ? null : ( $action->checkedin == 0 ? 'class="active";' : 'style="display: none;"'); ?>><input type="radio" value="0" <?php checked( $action->checkedin, 0 ); ?> name="did-action-today" id="did-action-today-radio-2" /><label for="did-action-today-radio-2"><?php _e("No", "e20rtracker"); ?></label></li>
                            </ul>
                            <?php endif; ?>
                            <div class="notification-entry-saved" style="width: 295px; display:none;">
                                <div><?php _e("Action check-in saved", "e20rtracker"); ?></div>
                            </div>
                        </div>
                    </fieldset><!--.did-you //right-->
                    </div> <!--Action/Activity row -->
                </div><!--.clear-after-->

                <hr />

                <fieldset class="notes">
	                <input type="hidden" name="e20r-checkin-id" class="e20r-checkin-id" value="<?php echo $action->id; ?>" />
	                <input type="hidden" name="e20r-checkin-checkin_type" class="e20r-checkin-checkin_type" value="<?php echo CHECKIN_NOTE; ?>" />
	                <input type="hidden" name="e20r-checkin-checkin_short_name" class="e20r-checkin-checkin_short_name" value="<?php echo $note->checkin_short_name; ?>" />

	                <legend><?php _e("Notes", "e20rtracker"); ?></legend>

                    <p><?php _e("Please, feel free to add any notes that you'd like to record for this day. The notes are for your benefit; your coaches won't read them unless you ask them to.", "e20rtracker"); ?></p>

                    <div id="note-display">
                        <div style="margin: 8px;"><?php isset( $note->checkin_note ) ? base64_decode( $note->checkin_note ) : 'hidden text'; ?></div>
                    </div>

                    <textarea name="value" id="note-textarea"><?php echo isset( $note->checkin_note ) ? base64_decode( $note->checkin_note ) : null ; ?></textarea>

                    <div id="note-display-overflow-pad"></div>

                    <div class="notification-entry-saved" style="width: auto; height: 30px; position: absolute;">
                        <div style="border: 1px solid #84c37e; width: 140px;"><?php _e("Note saved", "e20rtracker"); ?></div>
                    </div>


                    <div class="button-container">
                        <button id="save-note" class="e20r-button"><?php _e("Save Note", "e20rtracker"); ?></button>
                    </div>

                </fieldset><!--.notes-->
            </div><!--#e20r-daily-checkin-canvas-->
        </div><!--#e20r-daily-checkin-container-->
	<?php } ?>
    <div class="modal"></div>
    <?php
        $html = ob_get_clean();
        return $html;
    }

    public function viewSettingsBox( $checkinData, $programs ) {

        dbg( "e20rCheckinView::viewSettingsBox() - Supplied data: " . print_r( $checkinData, true ) );
        ?>
        <form action="" method="post">
            <?php wp_nonce_field( 'e20r-tracker-data', 'e20r-tracker-checkin-settings' ); ?>
            <div class="e20r-editform">
                <input type="hidden" name="hidden-e20r-checkin-id" id="hidden-e20r-checkin-id"
                       value="<?php echo( ( ! empty( $checkinData ) ) ? $checkinData->id : 0 ); ?>">
                <table id="e20r-checkin-settings wp-list-table widefat fixed">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label for="e20r-checkin-checkin_type">Type</label></th>
                        <th class="e20r-label header"><label for="e20r-checkin-maxcount">Max # Check-ins</label></th>
                        <th class="e20r-label header"><label for="e20r-checkin-startdate">Starts on</label></th>
                        <th class="e20r-label header"><label for="e20r-checkin-enddate">Ends on</label></th>
                        <th class="e20r-label header"><label for="e20r-checkin-program_ids">Program</label></th>
                    </tr>
                    <tr>
                        <td colspan="5">
                            <hr width="100%"/>
                        </td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php

                    if ( is_null( $checkinData->startdate ) ) {

                        $start = '';
                    } else {

                        $start = new DateTime( $checkinData->startdate );
                        $start = $start->format( 'Y-m-d' );
                    }

                    if ( is_null( $checkinData->enddate ) ) {

                        $end = '';
                    } else {

                        $end = new DateTime( $checkinData->enddate );
                        $end = $end->format( 'Y-m-d' );
                    }

                    if ( ( $checkinData->maxcount <= 0 ) && ( ! empty( $checkinData->enddate ) ) ) {

                        $interval              = $start->diff( $end );
                        $checkinData->maxcount = $interval->format( '%a' );
                    }

                    dbg( "Checkin - Start: {$start}, End: {$end}" );
                    ?>
                    <tr id="<?php echo $checkinData->id; ?>" class="checkin-inputs">
                        <td>
                            <select id="e20r-checkin-checkin_type" name="e20r-checkin-checkin_type">
                                <option value="0" <?php selected( $checkinData->checkin_type, 0 ); ?><?php _e("Not configured", "e20rtracker"); ?></option>
                                <option value="<?php echo CHECKIN_ACTION; ?>" <?php selected( $checkinData->checkin_type, CHECKIN_ACTION ); ?>><?php _e("Action", "e20rtracker"); ?></option>
                                <option value="<?php echo CHECKIN_ASSIGNMENT; ?>" <?php selected( $checkinData->checkin_type, CHECKIN_ASSIGNMENT ); ?>><?php _e("Assignment", "e20rtracker"); ?></option>
                                <option value="<?php echo CHECKIN_SURVEY; ?>" <?php selected( $checkinData->checkin_type, CHECKIN_SURVEY ); ?>><?php _e("Survey", "e20rtracker"); ?></option>
                                <option value="<?php echo CHECKIN_ACTIVITY; ?>" <?php selected( $checkinData->checkin_type, CHECKIN_ACTIVITY ); ?>><?php _e("Activity", "e20rtracker"); ?></option>
                            </select>
                        </td>
                        <td class="text-input">
                            <input type="number" id="e20r-checkin-maxcount" name="e20r-checkin-maxcount" value="<?php echo $checkinData->maxcount; ?>">
                        </td>
                        <td class="text-input">
                            <input type="text" id="e20r-checkin-startdate" name="e20r-checkin-startdate" value="<?php echo $start; ?>">
                        </td>
                        <td class="text-input">
                            <input type="text" id="e20r-checkin-enddate" name="e20r-checkin-enddate" value="<?php echo $end; ?>">
                        </td>
                        <td>
                            <select class="select2-container" id="e20r-checkin-program_ids" name="e20r-checkin-program_ids[]" multiple="multiple">
                                <option value="0">Not configured</option>
                                <?php
                                foreach ( $programs as $pgm ) {

                                    $selected = ( in_array( $pgm->ID, $checkinData->program_ids ) ? ' selected="selected" ' : null); ?>
                                    <option
                                        value="<?php echo $pgm->ID; ?>"<?php echo $selected; ?>><?php echo esc_textarea( $pgm->post_title ); ?>
                                        (#<?php echo $pgm->ID; ?>)
                                    </option>
                                <?php } ?>
                            </select>
                            <style>
                                .select2-container {
                                    min-width: 150px;
                                    max-width: 300px;
                                    width: 90%;
                                }
                            </style>
                            <script>
                                jQuery('#e20r-checkin-program_ids').select2();
                            </script>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </form>
    <?php
    }

	public function view_user_achievements( $achievements ) {

		global $current_user;
		global $e20rTracker;

		$program_days = $achievements['program_days'];
		$program_score = $achievements['program_score'];

		unset($achievements['program_days']);
		unset($achievements['program_score']);

		ob_start();
		?>
        <div class="e20r-achievement-description">
            <div class="e20r-description-row clear-after">
                <div class="column_1_3">
                    <h4 class="e20r-ribbon-small"><?php _e("Gold Ribbon", "e20rtracker" ); ?></h4>
                    <img class="e20r-ribbon-small" src="<?php echo E20R_PLUGINS_URL . '/img/gold-badge.png'; ?>">
                    <p class="achivement-descr-small"><?php _e("The gold ribbon is awarded when your consistency is greater than 80 percent", "e20rtracker" ); ?></p>
                </div>
                <div class="column_2_3">
                    <h4 class="e20r-ribbon-small"><?php _e("Silver Ribbon", "e20rtracker"); ?></h4>
                    <img class="e20r-ribbon-small" src="<?php echo E20R_PLUGINS_URL . '/img/silver-badge.png'; ?>">
                    <p class="achivement-descr-small"><?php _e("The sliver ribbon is awarded when your consistency is between 70 and 80 percent", "e20rtracker" ); ?></p>
                </div>
                <div class="column_3_3">
                    <h4 class="e20r-ribbon-small"><?php _e("Bronze Ribbon", "e20rtracker"); ?></h4>
                    <img class="e20r-ribbon-small" src="<?php echo E20R_PLUGINS_URL . '/img/bronze-badge.png'; ?>">
                    <p class="achivement-descr-small"><?php _e("The bronze ribbon is awarded when your consistency is 70 percent or less", "e20rtracekr" ); ?></p>
                </div>
            </div>
        </div>
		<div id="e20r-assignment-answer-list" class="e20r-measurements-container">
			<h4>Achievements</h4>
			<a class="close" href="#">X</a>
			<div class="quick-nav other">
                <div class="e20r-measurement-table">
                    <div class="e20r-achievement-row-header large-viewport clear-after">
                        <div class="e20r-achievements-col_1_4 e20r-achievement-header"></div>
                        <div class="e20r-achievements-col_2_4 e20r-achievement-header"><a title="<?php _e("The daily actions", "e20rtracker"); ?>"><?php _e("Action", "e20rtracker" ); ?></a></div>
                        <div class="e20r-achievements-col_3_4 e20r-achievement-header"><a title="<?php _e("The activities (exercise, etc)", "e20rtracker"); ?>"><?php _e("Activity", "e20rtracker" ); ?></a></div>
                        <div class="e20r-achievements-col_4_4 e20r-achievement-header"><a title="<?php _e("The daily assignments", "e20rtracker"); ?>"><?php _e("Assignments", "e20rtracker" ); ?></a></div>
                    </div><?php

                $counter = 0;

                if ( ! empty( $achievements ) ) {

                    dbg("e20rCheckinView::view_user_achievements() - User has supplied answers...");
                    // dbg($achievements);
                    $achievements = array_reverse( $achievements, true);

                    foreach ( $achievements as $key => $answer ) {

                        if ( isset( $answer->action ) ) { ?>

                    <div class="e20r-achievement-row-bg <?php echo ($counter % 2) == 0 ? 'e20rEven' : 'e20rOdd'?>" >

                        <div class="e20r-achievement-row <?php echo ($counter % 2) == 0 ? 'e20rEven' : 'e20rOdd'?> clear-after" >
                            <div class="e20r-achievements-col_1_4 e20r-tracker-action-descr"><?php echo $answer->actionText ?></div>
                            <div class="e20r-achievements-col_2_4 e20r-tracker-action">
                                <div class="e20r-action-table clear-after">
                                    <div class="e20r-action-row e20r-achievement-header small-viewport">
                                        <div class="e20r-action-col-1_1"><a title="<?php _e("The daily actions", "e20rtracker"); ?>"><?php _e("Action", "e20rtracker" ); ?></a></div>
                                    </div>
                                    <div class="e20r-action-row">
                                        <div class="e20r-action-col-1_1 e20r-tracker-<?php echo isset( $answer->action->badge ) ? $answer->action->badge : 'no'; ?>-badge"></div>
                                    </div>
                                    <div class="e20r-action-row">
                                        <div class="e20r-action-col-1_1 e20r-tracker-score"><?php echo( $answer->action->score * 100 ); ?> &#37;</div>
                                    </div>
                                </div>
                            </div>
                            <div class="e20r-achievements-col_3_4 e20r-tracker-activity">
                                <div class="e20r-activity-table clear-after">
                                    <div class="e20r-activity-row e20r-achievement-header small-viewport">
                                        <div class="e20r-activity-col-1_1"><a title="<?php _e("The activities (exercise, etc)", "e20rtracker"); ?>"><?php _e("Activity", "e20rtracker" ); ?></a></div>
                                    </div>
                                    <div class="e20r-activity-row">
                                        <div class="e20r-activity-col-1_1 e20r-tracker-<?php echo isset( $answer->activity->badge ) ? $answer->activity->badge : 'no'; ?>-badge"></div>
                                    </div>
                                    <div class="e20r-activity-row">
                                        <div class="e20r-activity-col-1_1 e20r-tracker-score"><?php echo( $answer->activity->score * 100 ); ?> &#37;</div>
                                    </div>
                                </div>
                            </div>
                            <div class="e20r-achievements-col_3_4 e20r-tracker-assignment">
                                <div class="e20r-assignment-table clear-after">
                                    <div class="e20r-assignment-row e20r-achievement-header small-viewport">
                                        <div class="e20r-assignment-col-1_1"><a title="<?php _e("The daily assignments", "e20rtracker"); ?>"><?php _e("Assignments", "e20rtracker" ); ?></a></div>
                                    </div>
                                    <div class="clear-after"></div>
                                    <div class="e20r-assignment-row">
                                        <div class="e20r-assignment-col-1_1 e20r-tracker-<?php echo isset( $answer->assignment->badge ) ? $answer->assignment->badge : 'no'; ?>-badge"></div>
                                    </div>
                                    <div class="e20r-assignment-row">
                                        <div class="e20r-assignment-col-1_1 e20r-tracker-score"><?php echo( $answer->assignment->score * 100 ); ?> &#37;</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                        $counter ++;
                        }
                    }
                }
                else { ?>
                    <div class="e20r-achievement-none" >
                        <div class="e20r-achievements-col_1_1">
                            <?php _e("Don't worry. Your achievements will start piling up soon!", "e20rtracker"); ?>
                        </div>
                        <div class="clear-after"></div>
                    </div>
        <?php   } ?>
                </div>
            </div>
		</div>
		<?php
        dbg("e20rCheckinView::view_user_achievement() - Finished generating view..");
		$html = ob_get_clean();
		return $html;
	}
}