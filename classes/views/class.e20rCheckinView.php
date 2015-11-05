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

    public function view_card( $config, $data ) {

        $html = null;

        ob_start(); ?>
        <div class="e20r-checkin-<?php echo $data->card_type; ?> e20r-content-cell">
            <article id="<?php echo esc_attr( $config->articleId ); ?>" class="e20r-display-as-data-card e20r-as-grid e20r-full-size">
                <a href="<?php echo esc_url( $data->card_url ); ?>">
                    <header class="e20r-data-card-header <?php echo "e20r-card-{$config->card_type}"; ?>">
                        <div class="e20r-as-grid e20r-full-size">
                            <div class="e20r-grid-item e20r-grid-half right">
                                <time datetime="<?php echo $data->card_date; ?>"><?php echo $data->card_date_title; ?></time>
                            </div>
                        </div>
                    </header>
                    <h2 class="e20r-card-header"><?php echo esc_attr( $data->card_title); ?></h2>
                    <p class="e20r-card-description">
                        <?php echo $data->card_description; ?>
                    </p>
                </a>
                <footer class="e20r-checkin-footer <?php echo ( true === $data->dismissable_card ? 'e20r-can-dismiss' : null ); ?>">

                </footer>
            </article>
        </div>
        <?php
        $html = ob_get_clean();
        return $html;
    }

    public function view_action_activity_cards( $config ) {

        ob_start(); ?>
        <div id="e20r-checkin-content">
            <div class="e20r-checkin-lesson e20r-content-cell">
                <?php echo ( ! isset( $config->activityExcerpt ) ? '<h4 class="e20r-checkin-header">'.  __("Activity", "e20rtracker") . '</h4><p class="e20r-descr e20r-descr-text">' . __("No activity scheduled.", "e20rtracker") .'</p>' : $config->activityExcerpt ); ?>
            </div>
            <div class="e20r-checkin-lesson e20r-content-cell">
                <?php echo ( ! isset( $config->actionExcerpt ) ? '<h4 class="e20r-checkin-header">'. sprintf( __("%s", "e20rtracker"), esc_attr( $config->action_type ) ). '</h4><p class="e20r-descr e20r-descr-text">' . sprintf( __("No %s scheduled", "e20rtracker"), esc_attr( $config->action_type ) ) . '</p>' : wpautop( $config->actionExcerpt ) ); ?>
            </div>
        </div><?php

        $html = ob_get_clean();

        return $html;

    }

/*
    public function view_action_activity_cards( $config ) {

        ob_start(); ?>
<!--
        <?php//  if ( false === $config->use_cards ): ?>
            <table id="e20r-checkin-content clear-after">
                <tbody>
                <tr>
                    <td id="e20r-checkin-activity" class="e20r-content-cell">
                        <?php echo ( ! isset( $config->activityExcerpt ) ? '<h4 class="e20r-checkin-header">'.  __("Activity", "e20rtracker") . '</h4><p class="e20r-descr e20r-descr-text">' . __("No activity scheduled.", "e20rtracker") .'</p>' : wpautop( $config->activityExcerpt ) ); ?>
                    </td>
                    <td id="e20r-checkin-lesson" class="e20r-content-cell">
                        <?php echo ( ! isset( $config->actionExcerpt ) ? '<h4 class="e20r-checkin-header">'. __("Lesson", "e20rtracker") . '</h4><p class="e20r-descr e20r-descr-text">' . __("No lesson scheduled", "e20rtracker") . '</p>' : wpautop( $config->actionExcerpt ) ); ?>
                    </td>
                </tr>
                </tbody>
            </table> -->
        <?php // else: ?>
                <div id="e20r-checkin-activity" class="e20r-content-cell">
                    <?php echo ( ! isset( $config->activityExcerpt ) ? '<h4 class="e20r-checkin-header">'.  __("Activity", "e20rtracker") . '</h4><p class="e20r-descr e20r-descr-text">' . __("No activity scheduled.", "e20rtracker") .'</p>' : $config->activityExcerpt ); ?>
                </div>
                <div id="e20r-checkin-lesson" class="e20r-content-cell">
                    <?php echo ( ! isset( $config->actionExcerpt ) ? '<h4 class="e20r-checkin-header">'. sprintf( __("%s", "e20rtracker"), esc_attr( $config->action_type ) ). '</h4><p class="e20r-descr e20r-descr-text">' . sprintf( __("No %s scheduled", "e20rtracker"), esc_attr( $config->action_type ) ) . '</p>' : wpautop( $config->actionExcerpt ) ); ?>
                </div><?php
        // endif;

        $html = ob_get_clean();

        return $html;

    }
*/
    public function view_card_activity_checkin( $config, $activity ) {

        $yes = __("Yes", "e20rtracker");
        $no = __("No", "e20rtracker");
        $partially = __("Partially", "e20rtracker");
        $not_scheduled = __("Not scheduled", "e20rtracker");

        ob_start(); ?>
        <div class="e20r-activity-card">
            <fieldset class="did-you workout">
                <legend><?php _e("Did you do your daily activity?", "e20rtracker"); ?></legend>
                <div>
                    <input type="hidden" name="e20r-checkin-id" class="e20r-checkin-id" value="<?php echo $activity->id; ?>" />
                    <input type="hidden" name="e20r-checkin-checkin_type" class="e20r-checkin-checkin_type" value="<?php echo CHECKIN_ACTIVITY; ?>" />
                    <input type="hidden" name="e20r-checkin-checkin_short_name" class="e20r-checkin-checkin_short_name" value="<?php echo $activity->checkin_short_name; ?>" />
                    <div class="e20r-descr e20r-toggle-button-group">
                        <div>
                            <input type="radio" value="1" <?php checked( $activity->checkedin, 1 ); ?> name="did-activity-today" id="did-activity-today-radio-1" />
                            <label onclick="" class="toggle-btn" for="did-activity-today-radio-1"><?php echo $yes; ?></label>
                        </div>
                        <div>
                            <input type="radio" value="0" <?php checked( $activity->checkedin, 0 ); ?> name="did-activity-today" id="did-activity-today-radio-3" />
                            <label onclick="" class="toggle-btn" for="did-activity-today-radio-3"><?php echo $no; ?></label>
                        </div>
                        <div>
                            <input type="radio" value="2" <?php checked( $activity->checkedin, 2 ); ?> name="did-activity-today" id="did-activity-today-radio-2" />
                            <label onclick="" class="toggle-btn" for="did-activity-today-radio-2"><?php echo $partially; ?></label>
                        </div>
                        <div>
                            <input type="radio" value="3" <?php checked( $activity->checkedin, 3 ); ?> name="did-activity-today" id="did-activity-today-radio-4" />
                            <label onclick="" class="toggle-btn" for="did-activity-today-radio-4"><?php echo $not_scheduled; ?></label>
                        </div>
                    </div>
                    <div class="notification-entry-saved" style="width: 300px; display: none;">
                        <div><?php _e("Activity check-in saved", "e20rtracker"); ?></div>
                    </div>
                </div>
            </fieldset><!--//left-->
        </div>
        <?php

        $html = ob_get_clean();

        return $html;

    }

    public function view_activity_checkin( $config, $activity ) {

        ob_start();

        if ( false === $config->use_cards ) {
            $yes = __("I did my activity", "e20rtracker");
            $no = __("I missed my activity", "e20rtracker");
            $partially = __("I partially did my activity", "e20rtracker");
            $not_scheduled = __("No activity scheduled", "e20rtracker");
        }
        else {
            $yes = __("Yes", "e20rtracker");
            $no = __("No", "e20rtracker");
            $partially = __("Partially", "e20rtracker");
            $not_scheduled = __("None scheduled", "e20rtracker");
        }
        ?>
        <div class="e20r-activity-card clearfix">
            <fieldset class="did-you workout">
                <legend><?php _e("Did you complete your activity today?", "e20rtracker"); ?></legend>
                <div class="clearfix">
                    <input type="hidden" name="e20r-checkin-id" class="e20r-checkin-id" value="<?php echo $activity->id; ?>" />
                    <input type="hidden" name="e20r-checkin-checkin_type" class="e20r-checkin-checkin_type" value="<?php echo CHECKIN_ACTIVITY; ?>" />
                    <input type="hidden" name="e20r-checkin-checkin_short_name" class="e20r-checkin-checkin_short_name" value="<?php echo $activity->checkin_short_name; ?>" />
                    <ul> <!-- style="max-width: 300px; min-width: 200px; width: 290px;" -->
                        <li <?php echo is_null( $activity->checkedin) ? null : ( $activity->checkedin == 1 ? 'class="active";' : 'style="display: none;"'); ?>>
                            <input type="radio" value="1" <?php checked( $activity->checkedin, 1 ); ?> name="did-activity-today" id="did-activity-today-radio-1" />
                            <label for="did-activity-today-radio-1"><?php echo $yes; ?></label>
                        </li>
                        <li <?php echo is_null( $activity->checkedin) ? null : ( $activity->checkedin == 2 ? 'class="active";' : 'style="display: none;"'); ?>>
                            <input type="radio" value="2" <?php checked( $activity->checkedin, 2 ); ?> name="did-activity-today" id="did-activity-today-radio-2" />
                            <label for="did-activity-today-radio-2"><?php echo $partially; ?></label>
                        </li>
                        <li <?php echo is_null( $activity->checkedin) ? null : ( $activity->checkedin == 0 ? 'class="active";' : 'style="display: none;"'); ?>>
                            <input type="radio" value="0" <?php checked( $activity->checkedin, 0 ); ?> name="did-activity-today" id="did-activity-today-radio-3" />
                            <label for="did-activity-today-radio-3"><?php echo $no; ?></label>
                        </li>
                        <li <?php echo is_null( $activity->checkedin) ? null : ( $activity->checkedin == 3 ? 'class="active";' : 'style="display: none;"'); ?>>
                            <input type="radio" value="3" <?php checked( $activity->checkedin, 3 ); ?> name="did-activity-today" id="did-activity-today-radio-4" />
                            <label for="did-activity-today-radio-4"><?php echo $not_scheduled; ?></label>
                        </li>
                    </ul>
                    <div class="notification-entry-saved" style="width: 300px; display: none;">
                        <div><?php _e("Activity check-in saved", "e20rtracker"); ?></div>
                    </div>
                </div>
            </fieldset><!--//left-->
        </div>
        <?php

        $html = ob_get_clean();

        return $html;
    }

    public function view_action_checkin( $config, $action, $habit_entries ) {

        $skip_yn = false;

        if ( $habit_entries[0]->short_name == 'null_action') {

            dbg("e20rCheckinView::view_actionAndActivityCheckin() - Have a null action, so skip Yes/No radio buttons");
            $skip_yn = true;
        }

        ob_start();

        ?>
        <div class="e20r-action-card">
            <fieldset class="did-you habit">
            <legend style="padding-bottom: 9px;"><?php _e("Did you complete your action today?", "e20rtracker"); ?></legend>
            <div class="clearfix">
                <p style="margin-bottom: 4px;" id="habit-names">
                    <?php

                    $cnt = count($habit_entries);
                    dbg("e20rCheckinView::viewCheckinField() - We're dealing with {$cnt} habits today");

                    switch ( $cnt ) {
                        case 3: ?>
                            <span class="faded"><?php echo $habit_entries[2]->item_text; ?><br/></span><?php

                        case 2: ?>
                            <span class="faded"><?php echo $habit_entries[1]->item_text; ?><br/></span><?php

                        case 1: ?>
                            <strong><?php echo $habit_entries[0]->item_text; ?></strong></p><?php
                     }?>
                <input type="hidden" name="e20r-checkin-id" class="e20r-checkin-id" value="<?php echo $action->id; ?>" />
                <input type="hidden" name="e20r-checkin-checkin_type" class="e20r-checkin-checkin_type" value="<?php echo CHECKIN_ACTION; ?>" />
                <input type="hidden" name="e20r-checkin-checkin_short_name" class="e20r-checkin-checkin_short_name" value="<?php echo $action->checkin_short_name; ?>" />
                <?php if ( ! $skip_yn ): ?>
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
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public function view_card_action_checkin( $config, $action, $habit_entries ) {

        $skip_yn = false;

        if ( $habit_entries[0]->short_name == 'null_action') {

            dbg("e20rCheckinView::view_card_action_checkin() - Have a null action, so skip Yes/No radio buttons");
            $skip_yn = true;
        }

        ob_start();

        ?>
        <div class="e20r-action-card">
            <fieldset class="did-you habit">
                <h4><?php echo $habit_entries[0]->item_text; ?></h4>
                <input type="hidden" name="e20r-checkin-id" class="e20r-checkin-id" value="<?php echo $action->id; ?>" />
                <input type="hidden" name="e20r-checkin-checkin_type" class="e20r-checkin-checkin_type" value="<?php echo CHECKIN_ACTION; ?>" />
                <input type="hidden" name="e20r-checkin-checkin_short_name" class="e20r-checkin-checkin_short_name" value="<?php echo $action->checkin_short_name; ?>" /><?php
                if ( ! $skip_yn ): ?>
                <div class="e20r-descr e20r-toggle-button-group">
                    <div>
                        <input type="radio" value="1" <?php checked( $action->checkedin, 1 ); ?> name="did-action-today" id="did-action-today-radio-1" />
                        <label onclick="" class="toggle-btn" for="did-action-today-radio-1"><?php _e("Yes", "e20rtracker");?></label>
                    </div>
                    <div>
                        <input type="radio" value="0" <?php checked( $action->checkedin, 0 ); ?> name="did-action-today" id="did-action-today-radio-2" />
                        <label onclick="" class="toggle-btn" for="did-action-today-radio-2"><?php _e("No", "e20rtracker"); ?></label>
                    </div>
                </div><?php
                else: ?>
                    <p class="e20r-descr e20r-descr-button-none">
                        <?php _e("Take time off", "e20rtracker"); ?>
                    </p>
                <?php endif; ?>
                <div class="notification-entry-saved hidden" style="width: 295px; display:none;">
                    <div><?php _e("Action check-in saved", "e20rtracker"); ?></div>
                </div>
            </fieldset><!--.did-you //right-->
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public function view_notes_card( $config, $action, $note ) {

        ob_start();

        ?>
        <div class="e20r-checkin-notes">
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
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public function validate_delay_info( $config ) {

        ob_start();

        if ( ( $config->delay < $config->delay_byDate ) && is_null( $config->maxDelayFlag ) ){ ?>
            <div class="date-past-future notice"><?php echo sprintf( __('You are viewing a day in the past.  <a href="%s">Back to Today</a>', "e20rtracker"), $config->url );?></div><?php
        }

        if ( ( $config->delay > $config->delay_byDate ) && is_null( $config->maxDelayFlag ) ) { ?>
            <div class="date-past-future notice"><?php echo sprintf( __('You are viewing a day in the future.  <a href="%s">Back to Today</a>', "e20rtracker"), $config->url );?></div><?php
        }

        // The user is attempting to view a day >2 days after today.
        if ( isset( $config->maxDelayFlag ) && ( $config->maxDelayFlag >= CONST_MAXDAYS_FUTURE ) ) { ?>
            <div class="date-past-future orange-notice">
                <h4><?php _e("We love that you're interested!", "e20rtracker"); ?></h4>
                <p><?php echo sprintf( __('However, we feel there\'s already plenty to keep yourself busy with for now, so please return
                    <a href="%s">to the dashboard</a> for today\'s lesson.', "e20rtracker" ), $config->url ); ?></p>
            </div><?php
        }

        $html = ob_get_clean();

        return $html;

    }

    public function view_action_and_activity( $config, $action, $activity, $habit_entries, $note_content = null ) {

        global $e20rTracker;
        global $currentArticle;
        global $currentProgram;

        $action_date = $e20rTracker->getDateFromDelay( $config->delay );
        $today = $e20rTracker->getDateFromDelay();

        dbg("e20rCheckinView::view_action_and_activity() - We're requesting info for: $today vs $action_date ");

        if ($action_date == $today ) {

            //dbg("e20rCheckinView::view_action_and_activity() - We're requesting info for: $today vs $action_date ");
            $date = __("Today", "e20rtracker");
        }
        else {

            $date = esc_attr( $config->update_period );
        }

        ob_start();

        echo $this->validate_delay_info( $config );

        if ( ! ( isset( $config->maxDelayFlag ) && ( $config->maxDelayFlag >= CONST_MAXDAYS_FUTURE ) ) ) {

        echo $this->load_noscript_notice($config->maxDelayFlag); ?>
        <div class="e20r-action-activity-overview e20r-as-cards">
            <?php wp_nonce_field('e20r-checkin-data', 'e20r-checkin-nonce'); ?>
            <input type="hidden" name="e20r-checkin-article_id" id="e20r-checkin-article_id" value="<?php echo isset( $currentArticle->id ) ? esc_attr( $currentArticle->id ) : null; ?>" />
            <input type="hidden" name="e20r-checkin-assignment_id" id="e20r-checkin-assignment_id" value="<?php echo ( isset( $config->assignment_id ) ? esc_attr( $config->assignment_id ) : null ); ?>" />
            <input type="hidden" name="e20r-checkin-checkin_date" id="e20r-checkin-checkin_date" value="<?php echo esc_attr( $e20rTracker->getDateFromDelay( ( $config->delay - 1) ) ); ?>" />
            <input type="hidden" name="e20r-checkin-checkedin_date" id="e20r-checkin-checkedin_date" value="<?php echo date('Y-m-d', current_time('timestamp') ); ?>" />
            <input type="hidden" name="e20r-checkin-program_id" id="e20r-checkin-program_id" value="<?php echo isset( $currentProgram->id ) ? esc_attr( $currentProgram->id ) : -1 ; ?>" />
            <div class="e20r-daily-checkin-row e20r-as-cards">
                <?php echo $this->view_card_header( $date  ); ?>
            </div><!-- end of row -->
            <div class="e20r-daily-checkin-row e20r-as-cards">
                <?php echo $this->view_notes_card( $config, $action, $note_content ); ?>
            </div>
            <div class="e20r-daily-checkin-row e20r-as-cards">
                <?php echo $this->view_action_activity_cards($config); ?>
            </div><!-- end of row -->
            <div class="e20r-daily-checkin-row e20r-as-cards">
                <?php echo $this->view_card_activity_checkin( $config, $activity ); ?>
                <?php echo $this->view_card_action_checkin( $config, $action, $habit_entries ); ?>
            </div>
        </div>
            <?php

        }

        return ob_get_clean();
    }

    public function view_card_header( $header_content ) {

        ob_start(); ?>
        <div class="e20r-action-activity-banner today"><h3><?php echo esc_html( $header_content ); ?></h3></div>
        <?php

        return ob_get_clean();
    }

    private function load_noscript_notice() {

        global $currentProgram;

        ob_start();
        ?>
        <noscript>
            <div class="red-notice" style="font-size: 18px; line-height: 22px;">
                <strong style="display: block; margin-bottom: 8px;"><?php _e("There's a little problem...", "e20rtracker"); ?></strong>
                <?php echo sprintf(__("You are using a web browser that doesn't have JavaScript enabled! <br/><br/>
                JavaScript is a technology that lets your web browser do cool stuff and we use it a lot throughout this site.
                To get something useful from the %s platform, you will need to enable JavaScript.
                Start by checking your browser's help pages or support forums or \"the Google\". You'll probably find
                step by step instructions.<br/>
                Or you can contact your coach and we\'ll be happy to help you.", "e20rtracker"), $currentProgram->title); ?>
            </div>
        </noscript><?php

        return ob_get_clean();
    }

    public function view_actionAndActivityCheckin( $config, $action, $activity, $habitEntries, $note_content = null) {

        global $e20rTracker;
        global $currentArticle;
        global $currentProgram;

        if ( ! is_array( $action ) ) {
            $this->setError("No check-in recorded");
        }

        // $trackerOpts = get_option('e20r-tracker');
        // $article = $currentArticle;

        ob_start();

        echo $this->validate_delay_info( $config );

        if ( ! ( isset( $config->maxDelayFlag ) && ( $config->maxDelayFlag >= CONST_MAXDAYS_FUTURE ) ) ) {

            echo $this->load_noscript_notice( $config->maxDelayFlag ); ?>
        <div id="e20r-checkin-daynav" class="clearfix">
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
        <?php echo $this->view_action_activity_cards( $config ); ?>
        <div class="clear-after"></div>
        <div id="e20r-daily-checkin-container" class="progress-container">
            <h3><?php _e("Daily Coaching <span>Update</span>", "e20rtracker"); ?></h3>
            <div id="e20r-daily-checkin-canvas" class="progress-canvas">
                <?php wp_nonce_field('e20r-checkin-data', 'e20r-checkin-nonce'); ?>
                <input type="hidden" name="e20r-checkin-article_id" id="e20r-checkin-article_id" value="<?php echo isset( $currentArticle->id ) ? esc_attr( $currentArticle->id ) : null; ?>" />
	            <input type="hidden" name="e20r-checkin-assignment_id" id="e20r-checkin-assignment_id" value="<?php echo ( isset( $config->assignment_id ) ? esc_attr( $config->assignment_id ) : null ); ?>" />
                <input type="hidden" name="e20r-checkin-checkin_date" id="e20r-checkin-checkin_date" value="<?php echo esc_attr( $e20rTracker->getDateFromDelay( ( $config->delay - 1) ) ); ?>" />
	            <input type="hidden" name="e20r-checkin-checkedin_date" id="e20r-checkin-checkedin_date" value="<?php echo date('Y-m-d', current_time('timestamp') ); ?>" />
                <input type="hidden" name="e20r-checkin-program_id" id="e20r-checkin-program_id" value="<?php echo isset( $currentProgram->id ) ? esc_attr( $currentProgram->id ) : -1 ; ?>" />
                <div class="e20r-daily-checkin-row">
                    <?php echo $this->view_activity_checkin( $config, $activity ); ?>
                    <?php echo $this->view_action_checkin( $config, $action, $habitEntries ); ?>
                </div> <!--Action/Activity row -->
                <hr />
                <div class="e20r-daily-checkin-row"?>
                    <?php echo $this->view_notes_card( $config, $action, $note_content  ); ?>
                </div>
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