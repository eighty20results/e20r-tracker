=== E20R Tracker ===
Contributors: eighty20results
Tags: content management, fitness, nutrition coaching, tracking
Requires at least: 3.7
Requires PHP 5.2 or later.
Tested up to: 4.2.4
Stable tag: 1.1.4
License: GPLv2

A platform for managing nutrition and fitness coaching programs. Use with the Paid Memberships Pro and PMPro Seuqences plugins.

== Description ==
The plugin is designed to coach clients through various change processes, whether they're behavioral, nutritional, fitness related, or otherwise.

We developed the plugin to meet our own specific coaching platform needs which revolved around a year long nutrition coaching approach.
During its development, we discovered a side-benefit which also allows us to manage an online personal training membership.

== Developers ==

=== ToDo ===
* Add translation support to all english messages & javascript alerts. (Very partially done).

== Additional Info One ==

=== Shortcode: [weekly_progress] ===
    Shows a weekly progress capture form. The form requests input in terms of weight, girth measurements,
    photos (configurable), free form input for "other things" the client may be tracking as well as
    a "am I making progress" question.

Arguments:
    ** day
        *** Description: Show on day X after the member's startdate
        *** Type: numeric
        *** Values: 0 or greater.
        *** Default: N/A
    ** from_programstart
        *** Description: Whether to start the count from the start of the program or from the start of the membership
        *** Type: numeric
        *** Values: 1 or 0
        *** Default: 1 (true)
    ** use_article_id
        *** Description: Whether to use the specified article id (if one is defined) to decide how to display the form
        *** Type: numeric
        *** Values: 1 or 0
        *** Default: 1 (true)
    ** demo_form
        *** Desription: Whether this is a demo form (i.e. dummy) or not
        *** Type: numeric
        *** Values: 0 or 1
        *** Default: 0 (false)

=== Shortcode: [daily_progress] ===
    Shows the daily progress dashboard or the Assignment input form for the program that the user is a member of on that day.

Arguments:
    ** type
        *** Description: What type of progress form to show
        *** Type: text
        *** Values: assignment, action, show_assignments
        *** Default: action (when not specified)

=== Shortcode: [progress_overview] ===
    Shows a progress overview (summary) for the user. Includes Measurements, Assignment input, Achievements summary and Workout history.

Arguments: N/A

=== Shortcode: [e20r_activity] ===
    Based on the delay value for "today" of the user logged in on the system, this shortcode will display the Daily Workout (Activity) for that user.
    If no activity is defined, will show an "error" message that no activity is scheduled for that day.

Arguments:
    ** activity_id
        *** Description: The ID (post ID) for the e20r_workouts CPT defining this activity
    ** show_tracking
        *** Description: Whether to include the tracking input (or not) for the defined activity
        *** Type: Numeric
        *** Values: 0 | 1 (1 == include the tracking input fields)
        *** Default: 1

=== Shortcode: [e20r_activity_archive] ===
    Lists the scheduled workouts (activities) for the specified time period.

Arguments:
    ** period
        *** Description: The week for which to show an activity list
        *** Type: text
        *** Values: 'current', 'next', 'previous'
        *** Default: 'current'

=== Shortcode:  [e20r_exercise] ===
    Show the exercise definition for the specified exercise.

Arguments:
    ** id
        *** Description: The ID (post id) for the exercise definition
        *** Type: numeric
        *** Values: valid post ids for e20r_exercise CPTs
        *** Default: N/A
    ** short_code
        *** Description: The short code name for the exercise.
        *** Type: text
        *** Values: N/A
        *** Default: N/A

== ChangeLog ==

== 1.1.4 ==

* Fix the interview completion check.
* Add expiry for whether to allow editing the Program welcome interview survey.
* Check client_info table to see if the interview was completed or not.

== 1.1.3 ==

* Remove unused styles
* Add background image for gender selector landing page info (proprietary)
* Make headline location for gender selector landing page info relative
* Add Facebook app page styles for gender selection landing page.

== 1.1.2 ==

* Saving options sometimes caused the DB updater to run
* Incorrect default timeout values when logging in caused really long sessions to be allowed.
* Reset long sessions to correct timeouts for the user.
* Redirect to login page if user isn't logged in and attempts to load a page containing one of our short codes.
* Fix validation of settings on save.
* Remove program specific settings from settings page
* Remove the lesson (drip-feed) render function for settings.

== 1.1.1 ==

* Update DB version number to 9 (from 7)
* Clean up DB updates, etc.

== 1.1 ==

* Style the Welcome Interview (e20r-confirmation-background) confirmation information
* Fix style of multi-select in interview forms
* Show color coded warning in the client info tab in wp-admin if user hasn't logged in for a while
* Didn't include ID of existing record so would save a new one when saving to e20r_client_info table.
* Record the  current timestamp for a user upon login
* Only skip actually empty entries when loading the client Interview data.
* Don't process multiselect fields for Yes/No substitution
* Show the actual value if the saved data is set to the number 0.
* Correct how we escape content in an input or textarea.
* Correct how we display escaped content in a textarea.
* Force a page ID (Set to -9999) if no page is found.
* Hook into the wp_login action to save user login timestamp.
* Fix test for encrypted survey data in decryptData()
* Force last login time to be set for all users on activation

== 1.0.1 ==

* Fix constructor for e20rTracker class
* Set currentProgram->id before attempting to use it in model
* Test whether global variable is set before trying to use it.
* Make client message UI translatable.
* Set 'From' to 'Coach <name>'
* Don't set program when User ID = 0 (not logged in).
* Fix warning message about uninitialized variable.

== 1.0 ==

* Release v1.0
* Fix issue where activity settings didn't always get loaded correctly.
* Use the post content TinyMCE editor for the activity description.
* Saving the activity description twice in the DB
* Fix formatting when displaying e20r_exercise description
* Fix formatting when displaying e20r_workout description

== 0.9.26 ==

* Set/Group listing has ugly wrap in activity progress overview.
* Fixed issue where the workout history would include workouts for the program that ocurred before the program started (shouldn't happen unless we're forcing it, but worth fixing)
* Force resize/redraw of charts/graphs when the tabs for the progress overview page is clicked.
* Fix issue where a hidden plot was attempted redrawn causing errors.
* Set link for quick-nav class to blue and underlined.
* Added styling fixes for responsive e20r-measurement-table
* Made measurements history table responsive and translatable.
* Return 'No answer recorded' for ranking fields if no answer is present.
* Remove debug output

== 0.9.25 ==

* Fix erroneous static startdate in getExerciseHistory()

== 0.9.24 ==

* Fix PHP4 constructor to avoid deprecated warning in Wordpress 4.3.
* Add fields array to model object.
* Allow 3rd party callers to map fields in DB against default names.
* Add getExerciseHistory() for user/program/exercise ID combination - Returns jqPlot friendly data structure.
* Make sure the currentProgram data is available when loading weight/girth graphs.
* Add AJAX handler to support loading exercise specific graphing data for weight/reps.
* Integrate Weight/Rep graphs in the viewExerciseProgress() rendering.
* Add graphing (loadable via button) to the view/page.
* Fix even/odd coloring problem for rows of data.
* Clean up UNIT object names.
* Fix path toe ElegantIcons font(s).
* Add styling for the exercise specific weight/rep statistics.
* Add support for graphs of weight/rep statistics per exercise in the Activity tab.
* Add load button 'click' event handling for weight/rep statistics.
* Force resize of  weight/rep chart(s) 1 second after the page has loaded.
* Redraw all visible graphs if window is resized.
* AJAX function to load activity statistics for a specific exercise/user combination.
* Redraw and resize the graphs whenever the user clicks one of the Weight/Girth tabs.
* Go to PHP5 based constructors (avoid deprecated warning from Wordpress 4.3)

== 0.9.23 ==

* Fix color problem for input field (button)
* Add assignments to list of post types to allow duplication of.
* Make Achievements tab in progress_overview short code responsive.

== 0.9.22 ==

* Redirect users if their startdate for the program is after today and they're attempting to access a program related page.
* Add support for defining a preparatory welcome page.
* Fix text when no activities have been found for the user/client. (It's not that it isn't implemented, it's that nothing has been recorded).
* Fix handling of incoming message
* Strip unneeded heading for Subject (duplication)

== 0.9.21 ==

* Fix redirect loop in has_* shortcode checks.

== 0.9.20 ==

* Fix header formatting when creating readme_changelog.txt
* Move runtime check to its own e20r_should_we_run() function since it's repeated for every update function.
* Rename all of the update functions and exists checks to use e20r_ prefix.
* Fix OB1 error when selecting upgrade scripts to run for DB
* Rename upgrade scripts to include e20r prefix
* Include array of arguments when calling update script

== 0.9.19 ==

* Support passing the $version variable to the update_db_to_*() function
* Add support for version argument in when calling the update_db_to_$version() function.
* Can't attempt to use result data as array entries when they're stdClass().
* Split update_db_to for OB1 errors into 2 separate functions w/2 separate DB version numbers.
* Update DB Version to upgrade OB1 errors in dates for check-ins & activities

== 0.9.18 ==

* Set new DB version
* Transition to using findArticles() whenever searching for articles
* findArticles() will always return an array of articles or FALSE.
* Set the tempo type (string) for fetched activity groups in archives
* Add ability to turn on/off tracking fields in e20r_activity shortcode.
* Handle new allowedActivityAccess() return values.
* Add filter to determine which CPTs to allow duplication of
* Add 'Duplicate' option for specific CPT types
* Duplicate a post (CPT) and all of its e20rTracker specific metadata, etc.
* Avoid loading program ID for users that aren't logged in.
* Transition to using findArticles() whenever looking up an article
* Handle cases where the Workout speed (type) ID is null.
* Set program ID whenever an AJAX call is triggered.
* Transition to using findArticles() whenever looking up an article.
* Only select articles that are in the correct program and has the correct delay (day of release since start)
* Set default (empty) article & ID if no article is found.
* Set program ID when processing AJAX calls
* If no article is found, define a default (empty) one and set the id for it to CONST_NULL_ARTICLE
* Make sure the activity is checked against the user ID (if the User ID is defined)
* Include check & update of the e20r_checkin table as well
* Add load_client_settings() function to only load client_information required to manage progress forms, etc.
* Only load the required client_info data on init()
* Force refresh of data in setClient() if new user gets loaded.
* Use simplified $e20rArticle->findArticles() function to locate articles
* Load default settings if user/admin/program specifies the CONST_DEFAULT_ARTICLE id (-9999)
* Only ignore an article with a release date of -9999 if the user isn't adamant we don't.
* Add parent::find() for e20rSettings class.
* Load the $currentProgram configuration when e20rProgram::init() is called
* Transition loadProgram() to use configure_startdate() function.
* Create dedicated 'configure_startdate' function - it currently supports using the defined program startdate, or to use the membership start date for the user.
* Modify program config in startdate() if a different programId from what we have in $currentProgram is specified in the function arguments.
* Refactor e20rArticle class Use article ID when init()
* Load $currentArticle in init()
* emptyArticle() didn't return the default settings when called.
* Reduce the number of article search & return functions available (only really need findArticles(), but may want findArticlesNear() later)
* Clean up getActivity() function and return the activity ID when calling it. (or false if error)
* getExcerpt() now handles multiple activities defined for an article (and returns the most likely candidate for the logged in user).
* Do nothing in the has_*_shortcode() functions if the user isn't logged in.
* Init $currentProgram global in has_*_shortcode() functions
* Simplify the article search functions (use 'findArticles() consistently)
* Use I18N compatible date function in getDateForPost()
* Fix display function for Workout Archive didn't always run
* Fix date for when the check-in was made (i.e. when it's scheduled to be done based on the delay value)
* Add code to fix for_date field in e20r_workout table.
* Only init the plugin if the user is already logged in
* Make show/hide logic more obvious.
* Remove unused if/then clauses for debug logging.
* Add activity_id for the e20r_articles CPT to the list of variables to de-serialize.
* Simplified the allowedActivityAccess function and have it return better granularity access information
* Allow selection of multiple activities for an article definition
* Remove unneeded code (Doesn't do anything useful) Add activity_id as a field to save/load as an array (from postmeta)
* Builds the Change Log entries automatically
* Don't create a new default assignment if one with the same title already exists
* Fix paths & clean up temporary files
* fonts/ is now included in the css/ directory so no need to specify it separately

= 0.9.16 =
* Make sure we have a valid 'To' address in 'Client Contact' form for email messages.

= 0.9.15 =
* Add e20r_coach role to system on load if needed.
* Add actions for updating the user profile with the 'coach' role (if applicable)
* Implemented is_a_coach() - returns true if the user has the e20r_coach capability.
* Small typo in debug info from load_from_survey_table()
* Add support for setting a user as a program coach in the user profile screen for the E20R-Tracker plugin
* Add fonts directory to installation kit
* Move ElegantIcons fonts to a sub-dir of css/
* Add iThemes' ElegantIcons font files
* Stop debug listing for activity datastructure.
* Stop debug listing for achievement datastructure.
* Stop output of arguments debug for query.
* Check for empty() rather than false after $wpdb->get_results() call,
* Avoid double-checking whether the article ID is present or not before adding it to the list of articles to return.
* Remove echo of actual user key (used during debug of functionality).
* Support using DB column value as indicator for type of decryption to do.
* Remove superfluous check of encryption indicator.
* Move un-implemented is_a_coach() function to e20rTracker class.
* Don't display the encryption key for the user in the client information data.
* Use I18N capable date function when calculating date/time for updates/edits.
* Always use program Id & article when loading data.
* Avoid superfluous config loads for programs and articles in load_from_survey_table()
* Force the survey type if we're running from the back-end.
* Select decryption type based on whether or not the DB records indicates its encryption status
* Wouldn't always load the client data for the specified user/client ID
* Only allow coaches to access a users data ( test whether is_a_coach() returns true/false )
* Load program and article config when a coach is attempting to view the data for a user in the back-end.
* Would occasionally return the incorrect date for a post/article.
* Would occasionally load check-in data for the wrong user ID
* Wouldn't always identify the correct article by ID on init.
* Add survey check (returns true/false) based on article ID (isSurvey())
* Only allow coaches to access a users data ( test whether is_a_coach() returns true/false )
* Would occasionally return the incorrect date for a post/article.
* Would occasionally load check-in data for the wrong user ID
* Always set program ID & Article ID when a coach is attempting to view E20R-Tracker data in /wp-admin/
* Would sometimes treat a regular user as a coach
* Update DB version number


= 0.9.14 =
* Load css for admin to render client information page(s) correctly sortByFields() needs to return the sorted data.
* Get the correct startdate for the specified user id
* Fix client message display and add alert if the client hasn't completed their interview.
* Sort the assignment replies by delay (date) and order
* Whitespace for readability
* Return false if key is empty.
* Use namespace for Crypto library and its exception handler
* Handle cases where no UserId was supplied and the user is logged in.
* Encode metadata when saving and loading the key.
* Default to Base64 if we're having problems with the key
* Upgraded Crypto library and renamed Crypto sources
* Renamed Crypto library & its autoloader
* Wouldn't handle all cases of a non-existing record.
* Fix encryption (AES) and masking (base64).
* Support any GravityForm based survey with the correct CSS class defined for processing.
* Find the survey form we're processing and try to match it to an article that has a survey configured.
* Ensure hover color of the text remains white.
* Remove superflous loads of the survey & client_info data.
* Typo in Encrypt Surveys option
* Renamed getData() to get_data()
* Added is_encrypted column to the e20r_surveys.
* Add 'is_encrypted' field for a row in the surveys table.
* Don't try to save dynamic client_info fields (incomplete_interview and loadedDefaults)
* Check variables before using them.
* Set survey type based on post ID
* Correctly merge data from the e20r_surveys and e20r_client_info tables on load.
* Handle multi-choice options when saving Gravity Form surveys.
* Handle multi-choice fields when loading previously saved Gravity Form surveys
* Disable encryption of individual DB fields.
* Remove a survey entry after it's been encrypted and saved by the app.
* Handle \n in text boxes.
* Force color for buttons
* Add definitions for a SURVEY_TYPE constant.
* Didn't load the article settings for the specified articleId if it wasn't already loaded
* Add e20r_surveys table definition
* Add article_id field to e20r_client_info table
* Rename getData() function to get_data() Return false in place of 0 for boolean flags
* Remove wrong/weird set/group calculation
* Superfluous warning thrown if article was empty.
* Add support for survey_type field.
* Remove wrong/weird set/group calculation
* Typos and counter errors
* Clean up group/set display for activity history.
* Display multichoice arrays correctly in assignment view
* Add support for e20r_surveys table
* Don't show Measurements alert if measurements page isn't defined in the program
* Make placeholder text translatable (and update to match button text)

= 0.9.13 =
* Load e20r_db_update.php for database updates. Omitted in last plugin build.

= 0.9.12 =
* Load select2 code for assignments in daily_progress shortcode.
* Correctly identify the DB upgrade routine(s) to run.
* Set DB version number
* We haven't got handlers for a simple checkbox assignment yet, so remove it as an option Decode array of answers when field_type = multichoice.
* Handle multichoice and hidden input field for the multichoice answer(s).
* Include pre-existing answer in text input for assignment.
* Add formatting for inputs in Yes/No fields.
* Fix SQL for update function
* Rename '*assignment-survey*' identifiers/classes to '*assignment-ranking*' Init select2 if multichoice field is present on page/in post.
* Same formatting for multichoice and other inputs on assignments form
* Add support for multichoice field(s) on the assignment form
* Make 'save' button available if multichoice field is clicked (and save is hidden).
* Convert multichoice answers to json and save in hidden assignment-answer input (to keep saving simple).
* Handle survey configuration(s) in dailyProgress shortcode.
* Add check-box for article that hosts/manages a survey
* Set DB Version number
* Fix CSS for new multichoice field option in Assignments
* Add support for managing multi-option select fields for assignments
* DB table upgrade script for DB v2.
* Add support for upgrading DB based on external e20r_db_upgrade.php file & functions
* Add manage_option_list callback for multichoice field support. (May not be needed)
* Handle cases where there are select_options defined but the admin changed the field type.
* Rename answer inputs (requires change to DB enum()) Rename survey rating to ranking
* Rename sortAssignments() to sortByField().
* Transition to sortByFields() in Assignments class.
* Move sortByFields() to e20rTracker class
* Make sortByFields() support user defined fields to sort by.
* Add select_options setting/postmeta field.
* Add support for multichoice field in assignment configuration.
* Add support for displaying a multichoice field in an assignment.
* Rename 'survey' fields to 'ranking' fields (the 1 - 10 ranking/likert fields)
* Show an actual input field for text input fields.
* Fix JS warnings
* Enh: Add support for managing multi-option select fields for assignments
* First update DB function created/loaded.
* Reflect actual purpose of input: 'survey' versus 'ranking'
* Use E20R_DB_VERSION constant to indicate change in DB version.
* Use renamed activation/deactivation functions
* Add e20r_db_version option
* Create e20r_surveys table definition on plugin activation.
* Transition from 'key' to 'index' for table indexes.
* Move DB management to its own manage_tables() function
* Rename plugin activiation and deactivation functions.
* Handle situations where the user has assigned the same post/page to two different articles.
* Test for access against a valid $article object.


= 0.9.11 =
* Unnecessary version bump due to mistake during commit.

= 0.9.10 =
* Add timeout error handling to jQuery.ajax() or .post() operations
* Update shortcode section in Readme
* Remove or comment out shortcode arguments that aren't used
* Use $currentProgram global in getDelay.
* Make sure startdate gets converted to a timestamp value before use.
* We don't use 0 as the first day of the program, so correct the daysBetween() calculation.
* Be consistent in loading settings for a program. Set program startdate for the current user based on their membership startdate.
* Remove special handling for membership systems in $e20rProgram->startdate() (It's now in the loadProgram() code).
* Remove obsolete code
* Handle startdate correctly (it's set to monday)

= 0.9.9 =

* Fix situations where the order number winds up getting 'wonky' (negative)
* Handle situations where an unexpected variable type is attempted saved (should be array but isn't so we'll need to convert it).
* Didn't clear the actual NULL (or 0) setting.
* Didn't save assignment_ids for article when user clicked 'Save' in back-end
* Add data filter for PMPro emails.
* Include message to user (in confirmation email) about the status of their membership (depends on day of sign-up).
* Removed unneeded level look-up
* When it's requested, make sure we actually load Achievements (not Assignments) data. (Only applied to the back-end client view)
* Set timeout for AJAX operations to 10 seconds (10000)

= 0.9.8 =
* Use the delay value for the article that is manages the postId to verify access. If the delay value is <= the the userIds current delay value, grant access.
* Set the program start date to that of the user (i.e. force the startdate to be keyed off of the users membership start date).
* Force use of the membership plugin's start date for the specified user.
* Base access on the delay value for the specified user(s).
* Handle the 'First day of the program, not allowed to proceed backwards' error message (ecode == 2)
* On the first day of the program, it allowed you to attempt to navigate backwards without having any defined articles available. This caused error messages.
* Return a specific error code to the AJAX caller if we're on the first day of the program for this user and they try to go backwards.

= 0.9.7 =
* Add styles for legend/description on Achievements tab
* Add legend/description for Achievements tab
* Didn't load assignment & lesson/reminder for the "to" day in the Dashboard day navigation.

= 0.9.6 =
* Handle migration from articles/assignments/checkins settings/postmeta to using article_ids/assignment_ids/checkin_ids
* Set program id for measurement entry in progress overview "measurement history" table
* Reset postdata whenever using WP_Query(), get_posts() or get_pages();
* Add private sort function (sortAssignments()): Will sort based on delay and order_num value ( delay, then order_num )
* Nits for setting up arguments in loadAssignmentByMeta()
* Skip entries with field_type = 0 ("Completed" button) unless searching based on field_type.
* Also include article_id from database ({wp_prefix}_e20r_assignments table when loading user data for an assignment.
* Fix issue where the system would always load default settings if an assignment question had been defined.
* Move addPrograms() function to parent class.
* Add addPrograms() function to allow adding of a single program_id to the existing settings->program_ids array.
* Load from the correct select2 CDN (assume HTTPS) Reset postdata after using WP_Query() or get_posts()
* Add URL to weekly_progress form to the javascript settings array.
* Because we moved to using the parent::find() function we have to deal with testing whether the data returned is an array or an object before attempting to use it
* Need a program id setting when configured when we list the assignments for the user
* Use the e20r_progress.settings.weekly_progress constant to locate the URL for the measurement form
* Fix layout/styles on measurement page(s)
* Fix layout/style for photo upload buttons on measurement page.
* Use page_id setting for generating link to the correct measurement form when listing the table of measurements.
* Use correct URL to check-mark graphic for viewLessonComplete()
* Use measurement_page_id setting for loading link to measurement form.
* Use parent class find() function to load article data.
* Add rudimentary client_info data listing
* Load & view available client information in /wp-admin/ back end
* Add support for handling redirection for logged in user when attempting to access multiple sales pages in contentFilter()
* Add support for handling multiple sales pages in contentFilter()
* Rename save button from "Complete" to "Save Answers"
* Make sure the dashboard page ID has been defined.
* Add redirect to dashboard if a logged in user (who's a member of the program) is attempting to access the sales page/post.
* URL to client info page was incomplete

= 0.9.5 =
* Handle situations where user has defined a workout with start/end day number or dates.
* Include support for handling proper WP meta_query for program_id limited searches
* Use $currentArticle rather than loading the same data again.
* viewExerciseProgress() - Test activity list being received against empty() not is_null()
* Fix issue when searching for article definitions belonging to a specific program.
* Simplify findArticle() and leverage the find() function from the parent class while still ignoring "always available" articles (Possibly not what we want!)
* Simplify and use WP meta_query when searching for program specific check-in definitions
* Remove debug output for loadOption() function - too noisy
* Fix: Achievements statistics in progress overview.
* Fix: Attempted to show excerpt when there was no action or activity defined.
* For consistency use $currentArticle for accessing article settings
* Fix: Achievements statistics in progress overview.
* Automated metadata conversion (from serialized array to independent meta_key values) on plugin activation.
* Added constant to determine whether to convert/unserialize the metadata.
* Converted from serialized program id & article id metadata (programs|program_ids|article_id|other keys) to proper meta data (for those settings) that is WP query searchable.
* Indicate link to assignment page when listing assignments in the progress_overview short code.

= 0.9.4 =
* Use the article summary - if it exists - for the action excerpt.
* Set background color to white for activity history list

= 0.9.3 =
* Simplify processing of workout history for user's progress overview.
* Allow metadata upgrade to proceed
* White-screen of death issue (undeclared variable)

= 0.9.2 =
* Fix static/non-static call warnings in e20rTracker
* Removed unused global declaration
* Add support for showing Activity history (i.e. the exercise rep statistics)
* Ensure all assignment data gets loaded when requested.
* Hide annoying alert dialog when there are no measurements to be found for the user.
* Fix text for default setting (no page) in Program definition meta
* Add program list to assignment definition (may hide at some point in future)
* Don't limit number of records returned in loadAllSettings()
* Refactor parameters
* Transition to using currentClient global variable.
* Save program ID(s) for each assignment used by the article.
* Add update_metadata() at init for Assignments
* Load all required scripts and styles to front-end.
* Transition isEmpty() to the global e20rTracker class.
* Use the isEmpty() function to check whether objects/arrays contain data
* Only load assignment if it's defined as belonging to the program(s) (Or there are not programs defined for this assignment)
* Force program_ids settings to be an array (if it's not when loaded).
* Support updating assignment options (name and values) on init.
* Add program list to assignment definition
* Correctly process assignments array during save
* Didn't always recognize if the e20rMeasurements object was empty or not.
* Load Activities tab for progress_overview short code
* Set user id & program info for weekly_progress short code
* Fix formatting for progress_overview short code
* Program-specific setting for weekly_progress page
* Fix typos in change log

= 0.9.1 =
* Removed console logging of various objects
* Add "working" graphic when saving notes.
* Set default measurement day to be Saturday (day #6)
* Didn't attempt to list all defined mebership levels for Paid Memberships Pro
* Make measurement day setting a program specific setting.

= 0.9 =
* Display daily progress notes (decoupled from action check-in data)

= 0.8.19 =
* Check-in action would sometimes overwrite check-in data for other days.
* Set activity override if user navigates to next/previous day.
* Bind to the "Read more" activity link
* Removed dummy class function
* Add redirect with POST when user clicks teh "read more" link for a future/past activity.
* Load new settings for the correct article when the user is navigating through daily_progress days
* Remove commented out callback handler(s)
* Remove debug for arguments in find() (log file hog!)
* Handle cases where the activity short code is loaded as part of a redirect with specific activity ID & article information present. Note: Short code attributes take precedence over $_POST entries. Only load data for specific activities if authorized to do so
* Load jquery.redirect.js for daily_progress short code
* Add ID to Read more link for activity & action excerpts
* Add event handler for the Activity excerpt "Read more" link
* Add function to execute on click event for "Read more" link
* Add support for specifying activity to display when "Read more" link is selected/clicked.

= 0.8.18 =
* Handle situations where the delay value is 0 (i.e. the beginning of the program)
* Handle (first day of program - 1 day) correctly: Nothing will be scheduled
* Never prevent users from scrolling back, all the way to the beginning of the program

= 0.8.17 =
* Handle navigation to a different day than the current one.
* Handle success/failure during processing of next/previous navigation operations
* Admin access filter was a bit too "aggressive" in granting access.
* Cleaned up hasAccess() function
* Didn't consistently return an object from findArticleByDelay().

= 0.8.16 =
* Updated e20r_assignments table definition

= 0.8.15 =
* Let the admin specify a "# of days since start of membership" for when the activity/workout is scheduled to become available/unavailable for the client.
* Format exercise settings window in backend
* Reformat layout of activity/workout settings in backend.
* Correctly return the found article ID if a single article was located.

= 0.8.14 =
* Allow user to save whenever they update a response/answer.
* Didn't always reflect prior answers given. Fix: Didn't handle Yes/No answers correctly
* Support updates to survey and yes/no fields
* Handle previously given answers for a daily assignment.

= 0.8.12 =
* Buffer output in showYesNoQuestion()
* Configure the dashboard page and progress page as part of the program definition
* Set background color for the heading settings
* Use $currentProgram global to set the start timestamp and page URL for the dashboard.
* Load style for wp-admin when defining programs

= 0.8.11 =
* Clean up CSS for lesson button

= 0.8.10 =
* Add support for survey ranking field responses to assignments
* Add support for Yes/No check box responses to assignments
* Use E20R_VERSION constant to version JavaScript and CSS files
* Didn't add new assignments in the correct order in all situations
* Didn't always save article settings
* Didn't always save assignment settings
* Force page reload if the article is newly created and an assignment is being added

= 0.8.8 =
* Fix: Prevent the daily assignment short code from bleeding into the actual post/page.
* Fix: Only list assignments with a delay value == release_day for the article.

= 0.8.7 =
* Fix: Wouldn't save assignment settings
* Fix: Didn't always set the correct assignment ID

= 0.8.6 =
* Fix: Didn't always load the user's previously saved data when loading workout definition.
* Fix: Make sure user is logged in before accepting AJAX save of workout data.
* Fix: Simplified userCanEdit()
* Fix: Set correct timestamp value in shortcode

= 0.8.5 =
* Fix: Height of the date navigator in the daily_progress shortcode output
* Fix: Layout of program definition settings in wp-admin
* Fix: Set default $users setting for program on load.
* Fix: Make definition of e20r_activity page more robust for a program
* Fix: Didn't always load an appropriate length excerpt Fix: Didn't always select the url to the page containing the defined activity Fix: Didn't always add a "Click to read" link Fix: Didn't load check-ins from article definition in getCheckins()
* Add debug for getActions()
* Fix: Loading of check-ins, actions & activity display.
* Add config setting for a program activity_page_id

= 0.8.4 =
* Fix: Didn't always add a new default assignment for the article.
* Fix: Didn't always load the correct status for the assignment.

= 0.8.3 =
* Fix numerous issues when listing [daily_progress type='assignment'] output

= 0.8.2 =
* Fix program data load and add highlight around lesson complete button in daily assignment

= 0.8.1 =
* Fix: Handle next week correctly in e20r_activity_archive.

= 0.8.0 =
* Added e20r_activity_archive short code (param: 'period="current|previous|next"')

= 0.7.1 =
* Version bump to test auto-update.

= 0.7.0 =
* Adding README / documentation. Deleted obsolete update functionality
* Fix: Author URL
* Enh: Add automatic update support & bump version number
* Enh: Add metadata.json for automatic update support
* Fix: Add metadata.json handling for auto update
* Fix: Remove defunct auto update
* Fix: Remove debug output

= 0.6.2 =
* Bumped version number
* Fix: e20r_activitiy shortcode didn't respect specified activity_id
* Fix: e20r_activity shortcode didn't always load the right activity in default mode.

= 0.6.1 =
* Bumped version number

= 0.6.0 =
* Fix: Timeout needs to be in seconds
* Fix: Correct link to settings page