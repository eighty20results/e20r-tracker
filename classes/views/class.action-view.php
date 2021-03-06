<?php

namespace E20R\Tracker\Views;

/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

use E20R\Tracker\Models\Action_Model;

use E20R\Tracker\Controllers\Tracker;
use E20R\Tracker\Controllers\Tracker_Access;
use E20R\Utilities\Utilities;

/**
 * Class Action_View
 * @package E20R\Tracker\Views
 */
class Action_View extends Settings_View {
	
	/**
	 * @var null|Action_View
	 */
	private static $instance = null;
	
	/**
	 * Action_View constructor.
	 */
	public function __construct() {
		
		parent::__construct( 'action', Action_Model::post_type );
	}
	
	/**
	 * Return or instantiate this class
	 *
	 * @return Action_View
	 */
	static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Generate the HTML for the actual card(s) used in the Card based Dashboard
	 *
	 * @param \stdClass $config
	 * @param \stdClass $data
	 *
	 * @return null|string
	 */
	public function view_card( $config, $data ) {
		
		$html = null;
		
		ob_start(); ?>
        <div class="e20r-action-<?php echo $data->card_type; ?> e20r-content-cell">
            <article id="<?php echo esc_attr( $config->articleId ); ?>"
                     class="e20r-display-as-data-card e20r-as-grid e20r-full-size">
                <a href="<?php echo esc_url_raw( $data->card_url ); ?>">
                    <header class="e20r-data-card-header <?php echo "e20r-card-{$config->card_type}"; ?>">
                        <div class="e20r-as-grid e20r-full-size">
                            <div class="e20r-grid-item e20r-grid-half right">
                                <time datetime="<?php echo $data->card_date; ?>"><?php echo $data->card_date_title; ?></time>
                            </div>
                        </div>
                    </header>
                    <h2 class="e20r-card-header"><?php echo esc_attr( $data->card_title ); ?></h2>
                    <p class="e20r-card-description">
						<?php echo $data->card_description; ?>
                    </p>
                </a>
                <footer class="e20r-action-footer <?php echo( true === $data->dismissable_card ? 'e20r-can-dismiss' : null ); ?>">

                </footer>
            </article>
        </div><?php
		$html = ob_get_clean();
		
		return $html;
	}
	
	/**
	 * Generates the New (card based) Dashboard for the tracker
	 *
	 * @param      $config
	 * @param      $action
	 * @param      $activity
	 * @param      $habit_entries
	 * @param null $note_content
	 *
	 * @return string
	 */
	public function view_action_and_activity( $config, $action, $activity, $habit_entries, $note_content = null ) {
		
		$Tracker = Tracker::getInstance();
		global $currentArticle;
		global $currentProgram;
		
		$action_date = $Tracker->getDateFromDelay( $config->delay );
		$today       = $Tracker->getDateFromDelay();
		
		Utilities::get_instance()->log( "We're requesting info for: $today vs $action_date " );
		ob_start();
		
		echo $this->validate_delay_info( $config );
		
		if ( ! ( isset( $config->maxDelayFlag ) && ( $config->maxDelayFlag >= CONST_MAXDAYS_FUTURE ) ) ) {
			
			echo $this->load_noscript_notice(); ?>
            <div class="e20r-action-activity-overview e20r-as-cards">
				<?php wp_nonce_field( 'e20r-action-data', 'e20r-action-nonce' ); ?>
                <input type="hidden" value="<?php esc_attr_e( $config->use_cards ); ?>"
                       name="e20r-use-card-based-display">
                <input type="hidden" name="e20r-action-day-today" id="e20r-action-today"
                       value="<?php esc_attr_e( $config->delay ); ?>">
                <input type="hidden" name="e20r-action-article_id" id="e20r-action-article_id"
                       value="<?php echo isset( $currentArticle->id ) ? esc_attr( $currentArticle->id ) : null; ?>"/>
                <input type="hidden" name="e20r-action-assignment_id" id="e20r-action-assignment_id"
                       value="<?php echo( isset( $config->assignment_id ) && ! empty( $config->assignment_id ) ? esc_attr( $config->assignment_id ) : 0 ); ?>"/>
                <input type="hidden" name="e20r-action-checkin_date" id="e20r-action-checkin_date"
                       value="<?php esc_attr_e( $Tracker->getDateFromDelay( ( $config->delay - 1 ) ) ); ?>"/>
                <input type="hidden" name="e20r-action-checkedin_date" id="e20r-action-checkedin_date"
                       value="<?php echo date( 'Y-m-d', current_time( 'timestamp' ) ); ?>"/>
                <input type="hidden" name="e20r-action-program_id" id="e20r-action-program_id"
                       value="<?php echo isset( $currentProgram->id ) ? esc_attr( $currentProgram->id ) : - 1; ?>"/>
                <div class="e20r-daily-action-row e20r-as-cards">
					<?php echo $this->view_card_header( $this->view_date_navigation( $config ) ); ?>
                </div><!-- end of row -->
                <div class="e20r-daily-action-row e20r-as-cards">
					<?php echo $this->view_action_activity_cards( $config ); ?>
                </div><!-- end of row -->
                <div class="e20r-daily-action-row e20r-as-cards">
					<?php echo $this->view_card_activity_action( $config, $activity ); ?>
					<?php echo $this->view_card_action( $config, $action, $habit_entries ); ?>
                </div>
                <div class="e20r-daily-action-row e20r-as-cards">
					<?php echo $this->view_notes_card( $config, $action, $note_content ); ?>
                </div>
                <div class="modal"></div>
            </div><?php
			
		}
		
		return ob_get_clean();
	}
	
	/**
	 * Generate the info banner for previous/future dashboard content
	 *
	 * @param $config
	 *
	 * @return string
	 */
	public function validate_delay_info( $config ) {
		
		ob_start();
		
		if ( ( $config->delay < $config->delay_byDate ) && is_null( $config->maxDelayFlag ) ) { ?>
            <div class="date-past-future notice">
			<?php printf( '%1$s  <a href="%2$s">%3$s</a>',
				__( 'You are viewing a day in the past.', "e20r-tracker" ),
				esc_url_raw( $config->url ),
				__( 'Back to Today', 'e20r-tracker' )
			); ?>
            </div><?php
		}
		
		if ( ( $config->delay > $config->delay_byDate ) && is_null( $config->maxDelayFlag ) ) { ?>
            <div class="date-past-future notice">
			<?php printf(
				'%1$s  <a href="%1%s">%2$s</a>',
				__( 'You are viewing a day in the future.', "e20r-tracker" ),
				esc_url_raw( $config->url ),
				__( 'Back to Today', 'e20r-tracker' )
			); ?>
            </div><?php
		}
		
		// The user is attempting to view a day >2 days after today.
		if ( isset( $config->maxDelayFlag ) && ( $config->maxDelayFlag >= CONST_MAXDAYS_FUTURE ) ) { ?>
            <div class="date-past-future orange-notice">
            <h4><?php _e( "We love that you're interested!", "e20r-tracker" ); ?></h4>
            <p><?php printf(
					__( 'However, we feel there is a lot to keep yourself busy with for now, so please return
                    %1$sto the dashboard%2$s for today\'s reminder/lesson.', "e20r-tracker" ),
					sprintf( '<a href="%s">',
						esc_url_raw( $config->url ) ),
					'</a>'
				); ?></p>
            </div><?php
		}
		
		$html = ob_get_clean();
		
		return $html;
		
	}
	
	/**
	 * Generate and return the <noscript> content
	 *
	 * @return string
	 */
	private function load_noscript_notice() {
		
		global $currentProgram;
		
		ob_start();?>
        <noscript>
        <div class="red-notice" style="font-size: 18px; line-height: 22px;">
            <strong style="display: block; margin-bottom: 8px;"><?php _e( "There's a little problem...", "e20r-tracker" ); ?></strong>
			<?php printf( __( '%1$sYou are using a web browser that doesn\'t have JavaScript enabled!!%2$s
                %1$sJavaScript is a technology that lets your web browser do cool stuff and we use it a lot throughout this site.
                To get something useful from the %3$s platform, you will need to enable JavaScript.
                Start by checking your browser\'s help pages or support forums or "the Google". You\'ll probably find
                step by step instructions.%2$s
                %1$sOr you can contact your coach and we\'ll be happy to help you.%2$s', "e20r-tracker" ), '<p>', '</p>', $currentProgram->title ); ?>
        </div>
        </noscript><?php
		
		return ob_get_clean();
	}
	
	/**
	 * Generate the HTML for the Dashboard banner (navigation banner)
	 *
	 * @param string $header_content
	 *
	 * @return string
	 */
	public function view_card_header( $header_content ) {
		
		return sprintf( '<div class="e20r-action-activity-banner today">%s</div>', $header_content );
	}
	
	/**
	 * Generate Dashboard HTML for the date based navigation bar
	 *
	 * @param $config
	 *
	 * @return string
	 */
	public function view_date_navigation( $config ) {
		
		$Tracker = Tracker::getInstance();
		
		$date_for_today = $Tracker->getDateFromDelay( $config->delay - 1 );
		$current        = date_i18n( 'D M. jS', strtotime( $date_for_today ) );
		
		ob_start(); ?>
        <div id="e20r-action-daynav" class="clear">
            <input type="hidden" value="<?php esc_attr_e( $config->use_cards ); ?>" name="e20r-use-card-based-display">
            <input type="hidden" name="e20r-action-day-today" id="e20r-action-today"
                   value="<?php esc_attr_e( $config->delay ); ?>">
			<?php if ( $config->delay >= 1 ): ?>
                <div class="e20r-action-yesterday-nav">
                    <a id="e20r-action-yesterday-lnk"
                       href="<?php echo esc_url_raw( $config->url ); ?>"><?php esc_attr_e( $config->yesterday ); ?></a>
                    <input type="hidden" name="e20r-action-day-yesterday" id="e20r-action-yesterday"
                           value="<?php echo( ( $config->prev ) >= 0 ? ( esc_attr( $config->prev ) ) : 0 ); ?>">
                </div>
			<?php endif; ?>
            <div class="e20r-action-current"><?php esc_attr_e( $current ); ?></div>
            <div class="e20r-action-tomorrow-nav">
                <a id="e20r-action-tomorrow-lnk"
                   href="<?php echo esc_url_raw( $config->url ); ?>"><?php esc_attr_e( $config->tomorrow ); ?></a>
                <input type="hidden" name="e20r-action-day-tomorrow" id="e20r-action-tomorrow"
                       value="<?php esc_attr_e( $config->next ); ?>">
            </div>
        </div><?php
		
		return ob_get_clean();
	}
	
	/**
	 * Show both action and activity info (cards) in old layout
	 *
	 * @param $config
	 *
	 * @return string
	 */
	public function view_action_activity_cards( $config ) {
		
		$action_type = empty( $config->action_type ) ? 'Update' : $config->action_type;
		
		ob_start(); ?>
        <div class="e20r-action-activity e20r-content-cell">
			<?php echo( ! isset( $config->activityExcerpt ) ? '<h4 class="e20r-action-header">' . __( "Activity", "e20r-tracker" ) . '</h4><p class="e20r-descr e20r-descr-text">' . __( "No activity scheduled.", "e20r-tracker" ) . '</p>' : $config->activityExcerpt ); ?>
        </div>
        <div class="e20r-action-lesson e20r-content-cell">
			<?php echo( ! isset( $config->actionExcerpt ) ? '<h4 class="e20r-action-header">' . sprintf( __( "%s", "e20r-tracker" ), esc_attr( $action_type ) ) . '</h4><p class="e20r-descr e20r-descr-text">' . sprintf( __( "No %s scheduled", "e20r-tracker" ), esc_attr( lcfirst( $action_type ) ) ) . '</p>' : $config->actionExcerpt ); ?>
        </div><?php
		
		$html = ob_get_clean();
		
		return $html;
		
	}
	
	/**
	 * Show the "Activity" card (new layout)
	 *
	 * @param \stdClass $config
	 * @param \stdClass $activity
	 *
	 * @return string
	 */
	public function view_card_activity_action( $config, $activity ) {
		
		$yes           = __( "Yes", "e20r-tracker" );
		$no            = __( "No", "e20r-tracker" );
		$partially     = __( "Partially", "e20r-tracker" );
		$not_scheduled = __( "Not scheduled", "e20r-tracker" );
		
		ob_start(); ?>
        <div class="e20r-activity-card">
            <fieldset class="did-you workout">
                <h4><?php _e( "Did you do your daily activity?", "e20r-tracker" ); ?></h4>
                <div>
                    <input type="hidden" name="e20r-action-id" class="e20r-action-id"
                           value="<?php echo $activity->id; ?>"/>
                    <input type="hidden" name="e20r-action-checkin_type" class="e20r-action-checkin_type"
                           value="<?php echo CHECKIN_ACTIVITY; ?>"/>
                    <input type="hidden" name="e20r-action-checkin_short_name" class="e20r-action-checkin_short_name"
                           value="<?php echo $activity->checkin_short_name; ?>"/>
                    <div class="e20r-descr e20r-toggle-button-group">
                        <div class="e20r-left-button">
                            <input type="radio" value="1" <?php checked( $activity->checkedin, 1 ); ?>
                                   name="did-activity-today" id="did-activity-today-radio-1"/>
                            <label onclick="" class="toggle-btn"
                                   for="did-activity-today-radio-1"><?php echo $yes; ?></label>
                        </div>
                        <div class="e20r-right-button">
                            <input type="radio" value="0" <?php checked( $activity->checkedin, 0 ); ?>
                                   name="did-activity-today" id="did-activity-today-radio-3"/>
                            <label onclick="" class="toggle-btn"
                                   for="did-activity-today-radio-3"><?php echo $no; ?></label>
                        </div>
                        <div class="e20r-left-button">
                            <input type="radio" value="2" <?php checked( $activity->checkedin, 2 ); ?>
                                   name="did-activity-today" id="did-activity-today-radio-2"/>
                            <label onclick="" class="toggle-btn"
                                   for="did-activity-today-radio-2"><?php echo $partially; ?></label>
                        </div>
                        <div class="e20r-right-button">
                            <input type="radio" value="3" <?php checked( $activity->checkedin, 3 ); ?>
                                   name="did-activity-today" id="did-activity-today-radio-4"/>
                            <label onclick="" class="toggle-btn"
                                   for="did-activity-today-radio-4"><?php echo $not_scheduled; ?></label>
                        </div>
                    </div>
                    <div class="notification-entry-saved" style="width: 300px; display: none;">
                        <div><?php _e( "Activity check-in saved", "e20r-tracker" ); ?></div>
                    </div>
                </div>
            </fieldset><!--//left-->
        </div><?php
		
		$html = ob_get_clean();
		
		return $html;
		
	}
	
	/**
	 * Show the habit card for the new dashboard layout
	 *
	 * @param \stdClass $config
	 * @param \stdClass $action
	 * @param array     $habit_entries
	 *
	 * @return string
	 */
	public function view_card_action( $config, $action, $habit_entries ) {
		
		$skip_yn = false;
		
		if ( $habit_entries[0]->short_name == 'null_action' ) {
			
			Utilities::get_instance()->log( "Have a null action, so skip Yes/No radio buttons" );
			$skip_yn = true;
		}
		
		ob_start(); ?>
        <div class="e20r-action-card">
            <fieldset class="did-you habit">
                <h4><?php esc_attr_e( wp_unslash( $habit_entries[0]->item_text ) ); ?></h4>
                <input type="hidden" name="e20r-action-id" class="e20r-action-id"
                       value="<?php esc_attr_e( $action->id ); ?>"/>
                <input type="hidden" name="e20r-action-checkin_type" class="e20r-action-checkin_type"
                       value="<?php esc_attr_e( CHECKIN_ACTION ); ?>"/>
                <input type="hidden" name="e20r-action-checkin_short_name" class="e20r-action-checkin_short_name"
                       value="<?php esc_attr_e( $action->checkin_short_name ); ?>"/><?php
				if ( ! $skip_yn ): ?>
                    <div class="e20r-descr e20r-toggle-button-group">
                    <div class="e20r-left-button">
                        <input type="radio" value="1" <?php checked( $action->checkedin, 1 ); ?> name="did-action-today"
                               id="did-action-today-radio-1"/>
                        <label onclick="" class="toggle-btn"
                               for="did-action-today-radio-1"><?php _e( "Yes", "e20r-tracker" ); ?></label>
                    </div>
                    <div class="e20r-right-button">
                        <input type="radio" value="0" <?php checked( $action->checkedin, 0 ); ?> name="did-action-today"
                               id="did-action-today-radio-2"/>
                        <label onclick="" class="toggle-btn"
                               for="did-action-today-radio-2"><?php _e( "No", "e20r-tracker" ); ?></label>
                    </div>
                    </div><?php
				else: ?>
                    <p class="e20r-descr e20r-descr-button-none">
						<?php _e( "Take time off", "e20r-tracker" ); ?>
                    </p>
				<?php endif; ?>
                <div class="notification-entry-saved hidden" style="width: 295px; display:none;">
                    <div><?php _e( "Action check-in saved", "e20r-tracker" ); ?></div>
                </div>
            </fieldset><!--.did-you //right-->
        </div><?php
		$html = ob_get_clean();
		
		return $html;
	}
	
	/**
	 * Display the Daily Progress notes field
	 *
	 * @param \stdClass $config
	 * @param array     $action
	 * @param \stdClass $note
	 *
	 * @return string
	 *
	 * @since 2.0 - ENHANCEMENT: Add ability to let coach override the hiding of the member's notes when they have
	 *        explicit permission
	 */
	public function view_notes_card( $config, $action, $note ) {
		
		ob_start();
		global $current_user;
		$Access = Tracker_Access::getInstance();
		
		Utilities::get_instance()->log("Action content: " . print_r( $action, true ));
		
		$is_a_coach = $Access->is_a_coach( $current_user->ID );
		$default_text = true === $is_a_coach ? __( 'Encrypted notes from the member', 'e20r-tracker' ) : __( 'Add notes', 'e20r-tracker') ?>
        <div class="e20r-action-notes">
            <fieldset class="notes">
                <input type="hidden" name="e20r-action-id" class="e20r-action-id"
                       value="<?php esc_attr_e( $action->id ); ?>"/>
                <input type="hidden" name="e20r-action-checkin_type" class="e20r-action-checkin_type"
                       value="<?php esc_attr_e( CHECKIN_NOTE ); ?>"/>
                <input type="hidden" name="e20r-action-checkin_short_name" class="e20r-action-checkin_short_name"
                       value="<?php esc_attr_e( $note->checkin_short_name ); ?>"/>
                <legend><?php _e( "Notes", "e20r-tracker" ); ?></legend>
                <p><?php _e( "Please, feel free to add any notes that you'd like to record for this day. The notes are for your benefit; your coaches won't read them unless you ask them to.", "e20r-tracker" ); ?></p>
                <div id="note-display">
                    <div style="margin: 12px 22px;"><?php echo( ! empty( $note->checkin_note ) && false === $is_a_coach ? base64_decode( $note->checkin_note ) : $default_text ); ?></div>
                </div>
                <textarea name="value"
                          id="note-textarea" <?php echo( true === $is_a_coach ? 'class="coaching-notice"' : null ); ?>><?php echo ( ! empty( $note->checkin_note ) && false === $is_a_coach ) ? base64_decode( $note->checkin_note ) : $default_text; ?></textarea>
                <div id="note-display-overflow-pad"></div>
                <div class="notification-entry-saved" style="width: auto; height: 30px; position: absolute;">
                    <div style="border: 1px solid #84c37e; width: 140px;"><?php _e( "Note saved", "e20r-tracker" ); ?></div>
                </div>
                <div class="button-container">
                    <button id="save-note" class="e20r-button"><?php _e( "Save Note", "e20r-tracker" ); ?></button>
                </div>
            </fieldset><!--.notes-->
        </div><?php
		$html = ob_get_clean();
		
		return $html;
	}
	
	/**
	 * Generate Dashboard HTML (uses old layout, now deprecated)
	 *
	 * @param      $config
	 * @param      $action
	 * @param      $activity
	 * @param      $habitEntries
	 * @param null $note_content
	 *
	 * @return string
	 */
	public function view_actionAndActivityCheckin( $config, $action, $activity, $habitEntries, $note_content = null ) {
		
		$Tracker = Tracker::getInstance();
		global $currentArticle;
		global $currentProgram;
		
		if ( ! is_array( $action ) ) {
			$this->setError( "No check-in recorded" );
		}
		
		// $trackerOpts = get_option('e20r-tracker');
		// $article = $currentArticle;
		
		ob_start();
		
		$date_for_today = $Tracker->getDateFromDelay( $config->delay - 1 );
		$current        = date_i18n( 'D M. jS', strtotime( $date_for_today ) );
		Utilities::get_instance()->log( "Date for today ({$config->delay}): {$date_for_today} -> {$current}" );
		
		echo $this->validate_delay_info( $config );
		
		if ( ! ( isset( $config->maxDelayFlag ) && ( $config->maxDelayFlag >= CONST_MAXDAYS_FUTURE ) ) ) {
			
			echo $this->load_noscript_notice();
			echo $this->view_date_navigation( $config ); ?>
            <div class="clear-after"></div>
            <?php echo $this->view_action_activity_cards( $config ); ?>
            <div class="clear-after"></div>
            <div id="e20r-daily-action-container" class="progress-container">
                <h3><?php _e( "Daily Coaching <span>Update</span>", "e20r-tracker" ); ?></h3>
                <div id="e20r-daily-action-canvas" class="progress-canvas">
                    <?php wp_nonce_field( 'e20r-action-data', 'e20r-action-nonce' ); ?>
                    <input type="hidden" name="e20r-action-article_id" id="e20r-action-article_id"
                           value="<?php echo isset( $currentArticle->id ) ? esc_attr( $currentArticle->id ) : null; ?>"/>
                    <input type="hidden" name="e20r-action-assignment_id" id="e20r-action-assignment_id"
                           value="<?php echo( isset( $config->assignment_id ) && ! empty( $config->assignment_id ) ? esc_attr( $config->assignment_id ) : 0 ); ?>"/>
                    <input type="hidden" name="e20r-action-checkin_date" id="e20r-action-checkin_date"
                           value="<?php esc_attr_e( $Tracker->getDateFromDelay( ( $config->delay - 1 ) ) ); ?>"/>
                    <input type="hidden" name="e20r-action-checkedin_date" id="e20r-action-checkedin_date"
                           value="<?php esc_attr( date( 'Y-m-d', current_time( 'timestamp' ) ) ); ?>"/>
                    <input type="hidden" name="e20r-action-program_id" id="e20r-action-program_id"
                           value="<?php echo isset( $currentProgram->id ) ? esc_attr( $currentProgram->id ) : - 1; ?>"/>
                    <div class="e20r-daily-action-row clearfix">
                        <?php echo $this->view_activity( $config, $activity ); ?>
                        <?php echo $this->view_action( $config, $action, $habitEntries ); ?>
                    </div> <!--Action/Activity row -->
                    <hr/>
                    <div class="e20r-daily-action-row clearfix">
                        <?php echo $this->view_notes_card( $config, $action, $note_content ); ?>
                    </div>
                </div><!--#e20r-daily-action-canvas-->
            </div><!--#e20r-daily-action-container--><?php
		 } ?>
            <div class="modal"></div><?php
		$html = ob_get_clean();
		
		return $html;
	}
	
	/**
     * Add the Activity entry (old-style layout)
     *
	 * @param array $config
	 * @param \stdClass $activity
	 *
	 * @return string
	 */
	public function view_activity( $config, $activity ) {
		
		ob_start();
		
		if ( false === $config->use_cards ) {
			$yes           = __( "I did my activity", "e20r-tracker" );
			$no            = __( "I missed my activity", "e20r-tracker" );
			$partially     = __( "I partially did my activity", "e20r-tracker" );
			$not_scheduled = __( "No activity scheduled", "e20r-tracker" );
		} else {
			$yes           = __( "Yes", "e20r-tracker" );
			$no            = __( "No", "e20r-tracker" );
			$partially     = __( "Partially", "e20r-tracker" );
			$not_scheduled = __( "None scheduled", "e20r-tracker" );
		} ?>
        <div class="e20r-activity-card clearfix">
            <fieldset class="did-you workout">
                <legend><?php _e( "Did you complete your activity today?", "e20r-tracker" ); ?></legend>
                <div class="clearfix">
                    <input type="hidden" name="e20r-action-id" class="e20r-action-id"
                           value="<?php esc_attr_e( $activity->id ); ?>"/>
                    <input type="hidden" name="e20r-action-checkin_type" class="e20r-action-checkin_type"
                           value="<?php esc_attr_e( CHECKIN_ACTIVITY ); ?>"/>
                    <input type="hidden" name="e20r-action-checkin_short_name" class="e20r-action-checkin_short_name"
                           value="<?php echo $activity->checkin_short_name; ?>"/>
                    <ul> <!-- style="max-width: 300px; min-width: 200px; width: 290px;" -->
                        <li <?php echo is_null( $activity->checkedin ) ? null : ( $activity->checkedin == 1 ? 'class="active";' : 'style="display: none;"' ); ?>>
                            <input type="radio" value="1" <?php checked( $activity->checkedin, 1 ); ?>
                                   name="did-activity-today" id="did-activity-today-radio-1"/>
                            <label for="did-activity-today-radio-1"><?php echo $yes; ?></label>
                        </li>
                        <li <?php echo is_null( $activity->checkedin ) ? null : ( $activity->checkedin == 2 ? 'class="active";' : 'style="display: none;"' ); ?>>
                            <input type="radio" value="2" <?php checked( $activity->checkedin, 2 ); ?>
                                   name="did-activity-today" id="did-activity-today-radio-2"/>
                            <label for="did-activity-today-radio-2"><?php echo $partially; ?></label>
                        </li>
                        <li <?php echo is_null( $activity->checkedin ) ? null : ( $activity->checkedin == 0 ? 'class="active";' : 'style="display: none;"' ); ?>>
                            <input type="radio" value="0" <?php checked( $activity->checkedin, 0 ); ?>
                                   name="did-activity-today" id="did-activity-today-radio-3"/>
                            <label for="did-activity-today-radio-3"><?php echo $no; ?></label>
                        </li>
                        <li <?php echo is_null( $activity->checkedin ) ? null : ( $activity->checkedin == 3 ? 'class="active";' : 'style="display: none;"' ); ?>>
                            <input type="radio" value="3" <?php checked( $activity->checkedin, 3 ); ?>
                                   name="did-activity-today" id="did-activity-today-radio-4"/>
                            <label for="did-activity-today-radio-4"><?php esc_attr_e( $not_scheduled ); ?></label>
                        </li>
                    </ul>
                    <div class="notification-entry-saved" style="width: 300px; display: none;">
                        <div><?php _e( "Activity check-in saved", "e20r-tracker" ); ?></div>
                    </div>
                </div>
            </fieldset><!--//left-->
        </div><?php
		
		$html = ob_get_clean();
		
		return $html;
	}
	
	/**
     * Add the Action entry (old-style layout)
     *
	 * @param array $config
	 * @param \stdClass $action
	 * @param array $habit_entries
	 *
	 * @return string
	 */
	public function view_action( $config, $action, $habit_entries ) {
		
		$skip_yn = false;
		
		if ( $habit_entries[0]->short_name == 'null_action' ) {
			
			Utilities::get_instance()->log( "Have a null action, so skip Yes/No radio buttons" );
			$skip_yn = true;
		}
		
		ob_start();?>
        <div class="e20r-action-card">
            <fieldset class="did-you habit">
                <legend style="padding-bottom: 9px;"><?php _e( "Did you complete your action today?", "e20r-tracker" ); ?></legend>
                <div class="clearfix">
                    <p style="margin-bottom: 4px;" id="habit-names"><?php
						
						$cnt = count( $habit_entries );
						Utilities::get_instance()->log( "We're dealing with {$cnt} habits today" );
						
						switch ( $cnt ) {
						case 3: ?>
                            <span class="faded"><?php esc_attr_e( wp_unslash( $habit_entries[2]->item_text ) ); ?><br/>
                            </span><?php
						
						case 2: ?>
                            <span class="faded"><?php esc_attr_e( wp_unslash( $habit_entries[1]->item_text ) ); ?><br/>
                            </span><?php
						
						case 1: ?>
                        <strong><?php esc_attr_e( wp_unslash( $habit_entries[0]->item_text ) ); ?></strong></p><?php
					} ?>
                    <input type="hidden" name="e20r-action-id" class="e20r-action-id"
                           value="<?php esc_attr_e( $action->id ); ?>"/>
                    <input type="hidden" name="e20r-action-checkin_type" class="e20r-action-checkin_type"
                           value="<?php esc_attr_e( CHECKIN_ACTION ); ?>"/>
                    <input type="hidden" name="e20r-action-checkin_short_name" class="e20r-action-checkin_short_name"
                           value="<?php esc_attr_e( $action->checkin_short_name ); ?>"/>
					<?php if ( ! $skip_yn ): ?>
                        <ul> <!-- style="max-width: 300px; width: 285px;" -->
                            <li <?php echo is_null( $action->checkedin ) ? null : ( $action->checkedin == 1 ? 'class="active";' : 'style="display: none;"' ); ?>>
                                <input type="radio" value="1" <?php checked( $action->checkedin, 1 ); ?>
                                       name="did-action-today" id="did-action-today-radio-1"/><label
                                        for="did-action-today-radio-1"><?php _e( "Yes", "e20r-tracker" ); ?></label>
                            </li>
                            <li <?php echo is_null( $action->checkedin ) ? null : ( $action->checkedin == 0 ? 'class="active";' : 'style="display: none;"' ); ?>>
                                <input type="radio" value="0" <?php checked( $action->checkedin, 0 ); ?>
                                       name="did-action-today" id="did-action-today-radio-2"/><label
                                        for="did-action-today-radio-2"><?php _e( "No", "e20r-tracker" ); ?></label></li>
                        </ul>
					<?php endif; ?>
                    <div class="notification-entry-saved" style="width: 295px; display:none;">
                        <div><?php _e( "Action check-in saved", "e20r-tracker" ); ?></div>
                    </div>
                </div>
            </fieldset><!--.did-you //right-->
        </div><?php
		$html = ob_get_clean();
		
		return $html;
	}
	
	/**
     * Load metabox for Action Settings
     *
	 * @param \stdClass $checkinData
	 * @param \WP_Post[] $programs
	 */
	public function viewSettingsBox( $checkinData, $programs ) {
		
		Utilities::get_instance()->log( "Supplied data: " . print_r( $checkinData, true ) ); ?>
        <form action="" method="post">
			<?php wp_nonce_field( 'e20r-tracker-data', 'e20r-tracker-action-settings' ); ?>
            <div class="e20r-editform">
                <input type="hidden" name="hidden-e20r-action-id" id="hidden-e20r-action-id"
                       value="<?php echo( ( isset( $checkinData->id ) ) ? esc_attr( $checkinData->id ) : 0 ); ?>">
                <table id="e20r-action-settings wp-list-table widefat fixed">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label
                                    for="e20r-action-checkin_type"><?php _e( 'Type', 'e20r-tracker' ); ?></label></th>
                        <th class="e20r-label header"><label
                                    for="e20r-action-maxcount"><?php _e( 'Max # Check-ins', 'e20r-tracker' ); ?></label>
                        </th>
                        <th class="e20r-label header"><label
                                    for="e20r-action-startdate"><?php _e( 'Starts on', 'e20r-tracker' ); ?></label></th>
                        <th class="e20r-label header"><label
                                    for="e20r-action-enddate"><?php _e( 'Ends on', 'e20r-tracker' ); ?></label></th>
                        <th class="e20r-label header"><label
                                    for="e20r-action-program_ids"><?php _e( 'Program', 'e20r-tracker' ); ?></label></th>
                    </tr>
                    <tr>
                        <td colspan="5">
                            <hr width="100%"/>
                        </td>
                    </tr>
                    </thead>
                    <tbody>
					<?php
					
					/**
					 * @var \DateTime|null $start
					 */
					$start = null;
					/**
					 * @var \DateTime|null $end
					 */
					$end = null;
					
					Utilities::get_instance()->log( "Processing startdate: {$checkinData->startdate}" );
					if ( is_null( $checkinData->startdate ) ) {
						
						$start = null;
					} else {
						try {
							$start = new \DateTime( $checkinData->startdate );
							$start = $start->format( 'Y-m-d' );
						} catch ( \Exception $e ) {
							Utilities::get_instance()->log( " Error: " . $e->getMessage() );
							$start = "1970-01-01";
						}
					}
					
					Utilities::get_instance()->log( "Processing enddate: {$checkinData->enddate}" );
					if ( is_null( $checkinData->enddate ) ) {
						
						$end = null;
					} else {
						try {
							$end = new \DateTime( $checkinData->enddate );
							$end = $end->format( 'Y-m-d' );
						} catch ( \Exception $e ) {
							Utilities::get_instance()->log( " Error: " . $e->getMessage() );
							$end = "2031-01-01";
						}
					}
					
					Utilities::get_instance()->log( "Processing maxcount, etc: {maxcount}" );
					if ( ( $checkinData->maxcount <= 0 ) && ( ! empty( $checkinData->enddate ) ) ) {
						
						$interval              = ! empty( $start ) ? $start->diff( $end ) : null;
						$checkinData->maxcount = ! empty( $interval ) ? $interval->format( '%a' ) : null;
					}
					
					Utilities::get_instance()->log( "Checkin - Start: {$start}, End: {$end}" ); ?>
                    <tr id="<?php esc_attr_e( $checkinData->id ); ?>" class="action-inputs">
                        <td>
                            <select id="e20r-action-checkin_type" name="e20r-action-checkin_type">
                                <option value="0" <?php selected( $checkinData->checkin_type, 0 ); ?>><?php _e( "Not configured", "e20r-tracker" ); ?></option>
                                <option value="<?php esc_attr_e( CHECKIN_ACTION ); ?>" <?php selected( $checkinData->checkin_type, CHECKIN_ACTION ); ?>><?php _e( "Action", "e20r-tracker" ); ?></option>
                                <option value="<?php esc_attr_e( CHECKIN_ASSIGNMENT ); ?>" <?php selected( $checkinData->checkin_type, CHECKIN_ASSIGNMENT ); ?>><?php _e( "Assignment", "e20r-tracker" ); ?></option>
                                <option value="<?php esc_attr_e( CHECKIN_SURVEY ); ?>" <?php selected( $checkinData->checkin_type, CHECKIN_SURVEY ); ?>><?php _e( "Survey", "e20r-tracker" ); ?></option>
                                <option value="<?php esc_attr_e( CHECKIN_ACTIVITY ); ?>" <?php selected( $checkinData->checkin_type, CHECKIN_ACTIVITY ); ?>><?php _e( "Activity", "e20r-tracker" ); ?></option>
                            </select>
                        </td>
                        <td class="text-input">
                            <input type="number" id="e20r-action-maxcount" name="e20r-action-maxcount"
                                   value="<?php esc_attr_e( $checkinData->maxcount ); ?>">
                        </td>
                        <td class="text-input">
                            <input type="text" id="e20r-action-startdate" name="e20r-action-startdate"
                                   value="<?php esc_attr_e( $start ); ?>">
                        </td>
                        <td class="text-input">
                            <input type="text" id="e20r-action-enddate" name="e20r-action-enddate"
                                   value="<?php esc_attr_e( $end ); ?>">
                        </td>
                        <td>
                            <select class="select2-container" id="e20r-action-program_ids"
                                    name="e20r-action-program_ids[]" multiple="multiple">
                                <option value="0"><?php _e( 'Not configured', 'e20r-tracker' ); ?></option>
								<?php
								foreach ( $programs as $pgm ) {
									
									$selected = ( in_array( $pgm->ID, $checkinData->program_ids ) ? ' selected="selected" ' : null ); ?>
                                    <option
                                            value="<?php esc_attr_e( $pgm->ID ); ?>"<?php esc_attr_e( $selected ); ?>><?php echo esc_textarea( wp_unslash( $pgm->post_title ) ); ?>
                                        (#<?php esc_attr_e( $pgm->ID ); ?>)
                                    </option>
								<?php } ?>
                            </select>
                            <script type="text/javascript" language="JavaScript">
                                jQuery('#e20r-action-program_ids').select2();
                            </script>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </form><?php
	}
	
	/**
	 * Generate the User status page for achievements
	 *
	 * @param $achievements
	 *
	 * @return string
	 */
	public function view_user_achievements( $achievements ) {
		
		$program_days  = $achievements['program_days'];
		$program_score = $achievements['program_score'];
		
		unset( $achievements['program_days'] );
		unset( $achievements['program_score'] );
		
		$bronze_max = apply_filters( 'e20r-tracker-achievement-score-bronze-max', 0.6 );
		$silver_min = apply_filters( 'e20r-tracker-achievement-score-silver-min', 0.6 );
		$gold_min   = apply_filters( 'e20r-tracker-achievement-score-gold-min', 0.80 );
		
		ob_start(); ?>
        <!-- TODO: Add Celebration/Icon related to the program days (participation trophy) -->
        <div class="e20r-achievement-description">
            <div class="e20r-description-row clear-after">
                <div class="column_1_3">
                    <h4 class="e20r-ribbon-small"><?php _e( "Gold Ribbon", "e20r-tracker" ); ?></h4>
                    <img class="e20r-ribbon-small"
                         src="<?php echo esc_url( E20R_PLUGINS_URL . '/img/gold-badge.png' ); ?>">
                    <p class="achivement-descr-small"><?php printf( __( "The gold ribbon is awarded when your consistency is greater than %d percent", "e20r-tracker" ), ($gold_min * 100 ) ); ?></p>
                </div>
                <div class="column_2_3">
                    <h4 class="e20r-ribbon-small"><?php _e( "Silver Ribbon", "e20r-tracker" ); ?></h4>
                    <img class="e20r-ribbon-small"
                         src="<?php echo esc_url( E20R_PLUGINS_URL . '/img/silver-badge.png' ); ?>">
                    <p class="achivement-descr-small"><?php printf( __( "The sliver ribbon is awarded when your consistency is between %d and %d percent", "e20r-tracker" ),( $silver_min * 100 ), ($gold_min * 100 ) ); ?></p>
                </div>
                <div class="column_3_3">
                    <h4 class="e20r-ribbon-small"><?php _e( "Bronze Ribbon", "e20r-tracker" ); ?></h4>
                    <img class="e20r-ribbon-small"
                         src="<?php echo esc_url( E20R_PLUGINS_URL . '/img/bronze-badge.png' ); ?>">
                    <p class="achivement-descr-small"><?php printf( __( "The bronze ribbon is awarded when your consistency is %d percent or less", "e20rtracekr" ), $bronze_max * 100 ); ?></p>
                </div>
            </div>
        </div>
        <div id="e20r-assignment-answer-list" class="e20r-measurements-container">
            <h4><?php _e( 'Achievements', 'e20r-tracker' ); ?></h4>
            <a class="close" href="#">X</a>
            <div class="quick-nav other">
                <div class="e20r-measurement-table">
                    <div class="e20r-achievement-row-header large-viewport clear-after">
                        <div class="e20r-achievements-col_1_4 e20r-achievement-header"></div>
                        <div class="e20r-achievements-col_2_4 e20r-achievement-header"><a
                                    title="<?php _e( "The daily actions", "e20r-tracker" ); ?>"><?php _e( "Action", "e20r-tracker" ); ?></a>
                        </div>
                        <div class="e20r-achievements-col_3_4 e20r-achievement-header"><a
                                    title="<?php _e( "The activities (exercise, etc)", "e20r-tracker" ); ?>"><?php _e( "Activity", "e20r-tracker" ); ?></a>
                        </div>
                        <div class="e20r-achievements-col_4_4 e20r-achievement-header"><a
                                    title="<?php _e( "The daily assignments", "e20r-tracker" ); ?>"><?php _e( "Assignments", "e20r-tracker" ); ?></a>
                        </div>
                    </div><?php
					
					$counter = 0;
					
					if ( ! empty( $achievements ) ) {
						
						Utilities::get_instance()->log( "User has supplied answers..." );
						// Utilities::get_instance()->log($achievements);
						// $achievements = array_reverse( $achievements, true );
						
						foreach( $achievements as $date => $answer ) { ?>
						<div class="e20r-achievement-row-bg <?php echo ( $counter % 2 ) == 0 ? 'e20rEven' : 'e20rOdd' ?>">
							<div class="e20r-achievement-row <?php echo ( $counter % 2 ) == 0 ? 'e20rEven' : 'e20rOdd' ?> clear-after">
								<div class="e20r-achievements-col_1_4 e20r-tracker-action-descr"><?php echo $answer['action_title'];  ?></div>
								<div class="e20r-achievements-col_2_4 e20r-tracker-action">
									<div class="e20r-action-table clear-after">
										<div class="e20r-action-row e20r-achievement-header small-viewport">
											<div class="e20r-action-col-1_1"><a
														title="<?php _e( "The daily actions", "e20r-tracker" ); ?>"><?php _e( "Action", "e20r-tracker" ); ?></a>
											</div>
										</div>
										<div class="e20r-action-row">
											<div class="e20r-action-col-1_1 e20r-tracker-<?php echo isset( $answer['action']->badge ) ? esc_attr( $answer['action']->badge ) : 'no'; ?>-badge"></div>
										</div>
										<div class="e20r-action-row">
											<div class="e20r-action-col-1_1 e20r-tracker-score"><?php esc_attr_e( $answer['action']->score * 100 ); ?>
												&#37;
											</div>
										</div>
									</div>
								</div>
								<div class="e20r-achievements-col_3_4 e20r-tracker-activity">
									<div class="e20r-activity-table clear-after">
										<div class="e20r-activity-row e20r-achievement-header small-viewport">
											<div class="e20r-activity-col-1_1"><a
														title="<?php _e( "The activities (exercise, etc)", "e20r-tracker" ); ?>"><?php _e( "Activity", "e20r-tracker" ); ?></a>
											</div>
										</div>
										<div class="e20r-activity-row">
											<div class="e20r-activity-col-1_1 e20r-tracker-<?php echo isset( $answer->badge ) ? esc_attr( $answer['activity']->badge ) : 'no'; ?>-badge"></div>
										</div>
										<div class="e20r-activity-row">
											<div class="e20r-activity-col-1_1 e20r-tracker-score"><?php esc_attr_e( $answer['activity']->score * 100 ); ?>
												&#37;
											</div>
										</div>
									</div>
								</div>
								<div class="e20r-achievements-col_3_4 e20r-tracker-assignment">
									<div class="e20r-assignment-table clear-after">
										<div class="e20r-assignment-row e20r-achievement-header small-viewport">
											<div class="e20r-assignment-col-1_1"><a
														title="<?php _e( "The daily assignments", "e20r-tracker" ); ?>"><?php _e( "Assignments", "e20r-tracker" ); ?></a>
											</div>
										</div>
										<div class="clear-after"></div>
										<div class="e20r-assignment-row">
											<div class="e20r-assignment-col-1_1 e20r-tracker-<?php echo isset( $answer['assignment']->badge ) ? esc_attr( $answer['assignment']->badge ) : 'no'; ?>-badge"></div>
										</div>
										<div class="e20r-assignment-row">
											<div class="e20r-assignment-col-1_1 e20r-tracker-score"><?php esc_attr_e( $answer['assignment']->score * 100 ); ?>
												&#37;
											</div>
										</div>
									</div>
								</div>
							</div>
						</div><?php
							$counter ++;
						}
					} else { ?>
                        <div class="e20r-achievement-none">
                            <div class="e20r-achievements-col_1_1">
								<?php _e( "Don't worry. Your achievements will start piling up soon!", "e20r-tracker" ); ?>
                            </div>
                            <div class="clear-after"></div>
                        </div>
					<?php } ?>
                </div>
            </div>
        </div><?php
		
        Utilities::get_instance()->log( "Finished generating view.." );
		$html = ob_get_clean();
		return $html;
	}
}