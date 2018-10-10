<?php

namespace E20R\Tracker\Views;

use E20R\Tracker\Controllers\Exercise;
use E20R\Utilities\Utilities;

class Exercise_View {
	
	private static $instance = null;
	private $exercises = null;
	
	public function __construct( $exerciseData = null ) {
		
		$this->exercises = $exerciseData;
		
	}
	
	/**
	 * @return Exercise_View
	 */
	static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	public function view_exercise_as_columns( $show = true, $printing = false ) {
		
		global $currentExercise;
		$Exercise = Exercise::getInstance();
		
		$display    = null;
		$type_label = '';
		
		if ( in_array( $currentExercise->type, array( 2, 3 ) ) ) {
			
			Utilities::get_instance()->log( "Time/AMRAP is the selected exercise rep type" );
			$type_label = __( 'seconds', 'e20r-tracker' );
		}
		
		Utilities::get_instance()->log( "Hidden status is: {$show}" );
		$display = $this->generate_video_view();
		
		ob_start(); ?>
        <div class="e20r-exercise-table e20r-exercise-detail">
            <div class="spacer">&nbsp;</div>
            <div class="e20r-exercise-table-header e20r-exercise-detail-row-1 clearfix">
                <div class="e20r-exercise-title clearfix">
                    <h4 class="e20r-exercise-title-h4"><?php esc_html_e( $currentExercise->title ); ?></h4>
                    <div class="e20r-exercise-details">
                        <span class="e20r-exercise-label"><?php esc_html_e( $Exercise->getExerciseType( $currentExercise->type ) ); ?>
                            :</span>
                        <span class="e20r-exercise-value"><?php esc_html_e( "{$currentExercise->reps} {$type_label}" ); ?></span>
                        <span class="e20r-exercise-label"><?php _e( 'Rest', 'e20r-tracker' ); ?>:</span>
						<?php
						if ( ! empty( $currentExercise->rest ) ) { ?>
                            <span class="e20r-exercise-value"><?php esc_attr_e( $currentExercise->rest ); ?><?php _e( 'seconds', 'e20r-tracker' ); ?></span><?php
						} else { ?>
                            <span class="e20r-exercise-value"><?php _e( 'N/A', 'e20r-tracker' ); ?></span><?php
						} ?>
                    </div>
                </div>
            </div>
			<?php if ( false === $printing ): ?>
                <div class="e20r-exercise-detail-row-3 clearfix">
                    <div class="e20r-show-description-link">
                        <button class="e20r-exercise-info-toggle"><?php _e( 'Click for exercise video and description', 'e20r-tracker' ); ?></button>
                    </div>
                    <div class="e20r-video e20r-exercise-table-column first-column e20r-exercise-video-column startHidden clearfix">
                        <div class="e20r-exercise-video">
							<?php echo( ! empty( $display ) ? $display : '' ); ?>
                        </div>
                        <input type="hidden" class="e20r-display-exercise-id" name="e20r-activity-exercise-id[]"
                               value="<?php esc_attr_e( $currentExercise->id ); ?>"/>
                    </div>
                    <div class="e20r-exercise-table-column second-column e20r-exercise-description clearfix startHidden">
                        <p><?php echo wpautop( do_shortcode( $currentExercise->descr ) ); ?></p>
                    </div>
                </div>
			<?php endif; ?>
        </div>
		<?php
		$html = ob_get_clean();
		
		return $html;
	}
	
	private function generate_video_view() {
		
		global $currentExercise;
		$html = null;
		
		if ( empty( $currentExercise->video_link ) ) {
			
			$data = wp_get_attachment_image_src( get_post_thumbnail_id( $currentExercise->id ), 'single-post-thumbnail' );
			
			// No featured image specified and no video link included.
			if ( is_null( $data[0] ) && empty( $currentExercise->video_link ) ) {
				
				Utilities::get_instance()->log( "Using default placeholder image..." );
				$currentExercise->image = '<img class="e20r-resize" src="' . E20R_PLUGINS_URL . '/img/strong-cubed-fitness-default.png" alt="' . esc_attr( $currentExercise->title ) . '">';
			} else {
				
				$currentExercise->image = '<img class="e20r-resize" src="' . esc_url( $data[0] ) . '" alt="' . esc_attr( $currentExercise->title ) . '">';
			}
			
			$html = $currentExercise->image;
		}
		
		if ( ! empty( $currentExercise->video_link ) ) { ?>
			<?php
			if ( ! is_ssl() ) {
				
				str_ireplace( 'https', 'http', $currentExercise->video_link );
			}
			
			if ( ( $yID = $this->extract_youtube_id( $currentExercise->video_link ) ) === null ) {
				// $poster = wp_get_attachment_image_src( get_post_thumbnail_id( $currentExercise->id), 'single-post-thumbnail' );
				// $display = $this->get_embed_video( $currentExercise->video_link, 'center', '16:9', '100', 0 );
				$html = $this->get_embed_video( $currentExercise->video_link, 'center', '4:3', '100', 0 );
			} else {
				ob_start(); ?>

                <div class="e20r-youtube-container">
                    <div class="youtube-player" data-id="<?php echo esc_attr( $yID ); ?>"></div>
                </div>
                <div class="e20r-video-descr"><?php _e( 'Click to view the video', 'e20r-tracker' ); ?></div>
				<?php
				
				$html = ob_get_clean();
			}
		}
		
		return $html;
	}
	
	// Display the exercise entry for an activity page

	private function extract_youtube_id( $url ) {
		
		$video_id = null;
		
		if ( preg_match( '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match ) ) {
			$video_id = $match[1];
		}
		
		return $video_id;
	}
	
	/**
	 * Returns the embedded video.
	 *
	 * This method is utilized by both shortcode and widget.
	 *
	 * @param string $url      the URL of the video
	 * @param string $align    the alignment of the video
	 * @param string $aspect   the aspect ratio of the video
	 * @param int    $width    the width of the video in percent
	 * @param int    $autoplay either 0 for autoplay off or 1 for autoplay on
	 *
	 * @return string the whole HTML code with the embed and the containing divs
	 * @since 1.2.0
	 */
	public function get_embed_video( $url, $align, $aspect, $width = null, $autoplay = 0 ) {
		
		$code = $this->before_video( $align, $aspect, $width );
		$code .= $this->embed_video( $url, $autoplay );
		$code .= $this->after_video();
		
		return $code;
	}
	
	/**
	 * Returns content to be printed before the video.
	 *
	 * @param string $align  the alignment of the video
	 * @param string $aspect the aspect ratio of the video
	 * @param int    $width  the width of the video in percent
	 *
	 * @return string HTML code containing two divs with the necessary CSS classes attached
	 * @since 1.0.0
	 */
	private function before_video( $align, $aspect, $width = null ) {
		
		$code = '<div class="e20r-exercise-video-' . $align . '"';
		
		if ( isset ( $width ) ) {
			
			$code .= ' style="width: ' . $width . '%;"';
		}
		
		$code .= '>';
		$code .= '<div class="e20r-exercise-video-wrapper size-' . $aspect . '">';
		
		return $code;
	}
	
	/**
	 * Returns the output for the actual oEmbed media element.
	 *
	 * Width or height parameters in the oEmbed code are removed so that the element can size dynamically.
	 *
	 * @param string $url      the URL of the video
	 * @param int    $autoplay either 0 for autoplay off or 1 for autoplay on
	 *
	 * @return string HTML code containing the oEmbed
	 * @since 1.0.0
	 */
	private function embed_video( $url, $autoplay = 0 ) {
		
		$regex = "/ (width|height)=\"[0-9\%]*\"/";
		
		$embed_code = wp_oembed_get( $url, array(
			'width'    => '100%',
			'height'   => '100%',
			'autoplay' => $autoplay,
			'rel'      => 0,
		) );
		
		if ( ! $embed_code ) {
			
			return '<strong>' . __( 'Error: Unsupported video host/service', 'e20r-tracker' ) . '</strong>';
		}
		
		return preg_replace( $regex, '', $embed_code );
	}
	
	/**
	 * Returns content to be printed after the video.
	 *
	 * @return string HTML code containing two closing divs
	 * @since 1.0.0
	 */
	private function after_video() {
		
		$code = '</div>';
		$code .= '</div>';
		
		return $code;
	}
	
	public function view_exercise_as_row( $show = true, $printing = false ) {
		
		global $currentExercise;
		$Exercise = Exercise::getInstance();
		
		$display    = null;
		$type_label = '';
		
		if ( $currentExercise->type == 1 ) {
			
			Utilities::get_instance()->log( "Time is the selected exercise rep type" );
			$type_label = __( 'seconds', 'e20r-tracker' );
		}
		?>
        <div class="e20r-exercise-content">
            <div class="e20r-show-desription-link"></div>
			<?php
			Utilities::get_instance()->log( "Hidden status is: {$show}" );
			$display = $this->generate_video_view();
			
			// Utilities::get_instance()->log("Display: {$display}");
			
			ob_start(); ?>
            <div class="e20r-exercise-table e20r-exercise-detail">
                <div class="e20r-exercise-instructions"><?php _e( "Click the image to play video demonstration", "e20r-tracker" ); ?></div>
                <div class="e20r-exercise-table-header e20r-exercise-detail-row">
                    <div class="e20r-exercise-table-column first-column e20r-exercise-title">
                        <h4 class="e20r-tracker-detail-h4"><?php echo $currentExercise->title; ?></h4>
                    </div>
                </div>
                <div class="spacer">&nbsp;</div>
                <div class="e20r-exercise-table-body<?php echo( $show == true ? " show" : " startHidden" ); ?>">
                    <div class="e20r-exercise-detail-row e20r-video">
                        <div class="e20r-exercise-table-column">
                            <div class="e20r-exercise-video">
								<?php echo( ! empty( $display ) ? $display : '' ); ?>
                            </div>
                            <input type="hidden" class="e20r-display-exercise-id" name="e20r-activity-exercise-id[]"
                                   value="<?php esc_attr_e( $currentExercise->id ); ?>"/>
                        </div>
                    </div>
                    <div class="spacer">&nbsp;</div>
                    <div class="e20r-exercise-detail-row">
                        <div class="e20r-exercise-table-column first-column e20r-exercise-reps">
                            <p class="e20r-exercise-description">
                            <span
                                    class="e20r-exercise-label"><?php esc_attr_e( $Exercise->getExerciseType( $currentExercise->type ) ); ?>
                                :</span>
                                <span
                                        class="e20r-exercise-value"><?php echo( ! in_array( $currentExercise->type, array(
										1,
										3,
									) ) ? "{$currentExercise->reps} {$type_label}" : "{$currentExercise->reps}" ); ?></span>
                            </p>
                        </div>
                        <div class="e20r-exercise-table-column second-column e20r-exercise-rest-time">
                            <p class="e20r-exercise-description">
                                <span class="e20r-exercise-label"><?php _e( 'Rest', 'e20r-tracker' ); ?>:</span>
								<?php
								if ( ! empty( $currentExercise->rest ) ) { ?>
                                    <span
                                            class="e20r-exercise-value"><?php esc_attr_e( $currentExercise->rest ); ?><?php _e( 'seconds', 'e20r-tracker' ); ?></span><?php
								} else { ?>
                                    <span class="e20r-exercise-value"><?php _e( 'N/A', 'e20r-tracker' ); ?></span><?php
								} ?>
                            </p>
                        </div>
                    </div>
                    <div class="spacer">&nbsp;</div>
                    <div class="e20r-exercise-detail-row">
                        <div class="e20r-exercise-table-column first-column e20r-exercise-description">
                            <p><?php echo wpautop( do_shortcode( $currentExercise->descr ) ); ?></p>
                        </div>
                    </div>
                    <div class="spacer">&nbsp;</div>

                </div>
            </div>
        </div>
		<?php
		$html = ob_get_clean();
		
		return $html;
	}
	
	/**
     * Add Exercise settings meta box
     *
	 * @param array $exerciseData
	 * @param array $types
	 */
	public function viewSettingsBox( $exerciseData, $types ) {
		
		Utilities::get_instance()->log( "Supplied data: " . print_r( $exerciseData, true ) ); ?>
        <form action="" method="post">
			<?php wp_nonce_field( 'e20r-tracker-data', 'e20r-tracker-exercise-settings' ); ?>
            <div class="e20r-editform">
                <input type="hidden" name="hidden-e20r-program-id" id="hidden-e20r-exercise-id"
                       value="<?php echo( ( isset( $exerciseData->id ) ) ? esc_attr( $exerciseData->id ) : 0 ); ?>">
                <table id="e20r-exercise-settings" class="e20r-exercise-settings wp-list-table widefat fixed">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label
                                    for="e20r-exercise-type"><?php _e( "Type", "e20r-tracker" ); ?></label></th>
                        <th class="e20r-label header"><label
                                    for="e20r-exercise-reps"><?php _e( "Repetitions / Duration", "e20r-tracker" ); ?></label>
                        </th>
                        <th class="e20r-label header"><label
                                    for="e20r-exercise-rest"><?php _e( "Rest (seconds)", "e20r-tracker" ); ?></label>
                        </th>
                        <th class="e20r-label header"><label
                                    for="e20r-exercise-shortcode"><?php _e( "Shortcode", "e20r-tracker" ); ?></label>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr id="e20r-exercise-<?php echo isset( $exerciseData->ID ) ? esc_attr( $exerciseData->ID ) : esc_attr( $exerciseData->id ); ?>"
                        class="program-inputs">
                        <td class="text-input">
                            <select class="select2-container" name="e20r-exercise-type" id="e20r-exercise-type">
                                <option value="0" <?php selected( 0, $exerciseData->type ); ?>></option>
								<?php
								foreach ( $types as $key => $descr ) { ?>
                                    <option value="<?php esc_attr_e( $key ); ?>"<?php selected( $key, $exerciseData->type ); ?>><?php esc_attr_e( $descr ); ?></option><?php
								}
								?>
                            </select>
                        </td>
                        <td class="text-input">
                            <input type="number" id="e20r-exercise-reps" name="e20r-exercise-reps"
                                   value="<?php esc_attr_e( $exerciseData->reps ); ?>">
                        </td>
                        <td class="text-input">
                            <input type="number" id="e20r-exercise-rest" name="e20r-exercise-rest"
                                   value="<?php  esc_attr_e($exerciseData->rest ); ?>">
                        </td>
                        <td class="text-input">
                            <input type="text" id="e20r-exercise-shortcode" name="e20r-exercise-shortcode"
                                   value="<?php  esc_attr_e($exerciseData->shortcode ); ?>">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <hr width="100%"/>
                        </td>
                    </tr>
                    <tr class="program-inputs">
                        <th class="e20r-label header"><label
                                    for="e20r-exercise-video_link"><strong><?php _e( "Video link", "e20r-tracker" ); ?>
                                    :</strong></label></th>
                        <td class="text-input" colspan="3">
                            <input type="text" style="width: 100%;" id="e20r-exercise-video_link"
                                   name="e20r-exercise-video_link" value="<?php echo esc_url_raw( $exerciseData->video_link ); ?>">
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </form>
		<?php
	}
}