<?php
namespace E20R\Tracker\Tools;
use E20R\Tracker\Tools as Tools;

class trackerTemplates
{
    static private $_this;

    public function __construct()
    {
        if (isset(self::$_this)) {
            wp_die(sprintf(__("%s is already instantiated and cannot be re-delcared", "e20r-tracker"), get_class($this)));
        }

        self::$_this = $this;

        add_filter('get_trackerTemplates_class_instance', [$this, 'get_instance']);
        add_action('wp_footer', array($this, 'template_exercise'), 25 );
    }

    public function get_instance() {

        return self::$_this;
    }

    public function template_exercise()
    { ?>
        <script type="text/html" id="tmpl-exercise">
            <article class="e20r-activity-overlay e20r-amrap-exercise">
                <header class="entry-header e20r-activity-timer">
                    <h1 class="entry-title e20r-activity-timer-h1">{{data.current_activity_title}}</h1>
                    <span class="e20r-activity-next-header">Next: {{data.next_activity_title}}</span>
                </header>
                <section class="e20r-exercise-video e20r-video-background">
                    <div class="youtube-player" data-src="{{data.exercise_fallback_image}}"
                         data-placeholder="{{data.exercise_video_placeholder}}"
                         data-video="{{data.exercise_video_link}}"
                         data-id="{{ data.exercise_video_id}}"></div>
                </section>
                <section class="e20r-exercise-overlay">
                    <input type="hidden" value="{{data.exericse_duration}}" name="e20r-duration-value">
                    <input type="hidden" value="{{data.exericse_rest}}" name="e20r-rest-value">
                    <div class="e20r-overlay-timer"></div>
                    <div class="e20r-overlay-row">
                        <div class="e20r-overlay--reps column-1">
                            <label for="e20r-exercise-reps">Completed repetitions</label>
                            <input id="e20r-exercise-reps" type="number" class="e20r-overlay-input"
                                   name="e20r-exercise-reps">
                        </div>
                        <div class="e20r-overlay-weight column-2">
                            <label for="e20r-exercise-weight">Completed repetitions</label>
                            <input id="e20r-exercise-weight" type="number" class="e20r-overlay-input"
                                   name="e20r-exercise-weight">
                        </div>
                        <div class="e20r-overlay-buttons">
                            <button class="e20r-overlay-start-timer">Start Timer</button>
                            <button class="e20r-overlay-start-timer">Stop</button>
                        </div>
                    </div>
                </section>
            </article>
        </script><?php
    }
}