= 0.9.12 =

Load select2 code for assignments in daily_progress shortcode.
Correctly identify the DB upgrade routine(s) to run.
Set DB version number
We haven't got handlers for a simple checkbox assignment yet, so remove it as an option Decode array of answers when field_type = multichoice.
Handle multichoice and hidden input field for the multichoice answer(s).
Include pre-existing answer in text input for assignment.
Add formatting for inputs in Yes/No fields.
Fix SQL for update function
Rename '*assignment-survey*' identifiers/classes to '*assignment-ranking*' Init select2 if multichoice field is present on page/in post.
Same formatting for multichoice and other inputs on assignments form
Add support for multichoice field(s) on the assignment form
Make "save" button available if multichoice field is clicked (and save is hidden).
Convert multichoice answers to json and save in hidden assignment-answer input (to keep saving simple).
Handle survey configuration(s) in dailyProgress shortcode.
Add check-box for article that hosts/manages a survey
Set DB Version number
Fix CSS for new multichoice field option in Assignments
Add support for managing multi-option select fields for assignments
DB table upgrade script for DB v2.
Add support for upgrading DB based on external e20r_db_upgrade.php file & functions
Add manage_option_list callback for multichoice field support. (May not be needed)
Handle cases where there are select_options defined but the admin changed the field type.
Rename answer inputs (requires change to DB enum()) Rename survey rating to ranking
Rename sortAssignments() to sortByField().
Transition to sortByFields() in Assignments class.
Move sortByFields() to e20rTracker class
Make sortByFields() support user defined fields to sort by.
Add select_options setting/postmeta field.
Add support for multichoice field in assignment configuration.
Add support for displaying a multichoice field in an assignment.
Rename "survey" fields to "ranking" fields (the 1 - 10 ranking/likert fields)
Show an actual input field for text input fields.
Fix JS warnings
Enh: Add support for managing multi-option select fields for assignments
First update DB function created/loaded.
Reflect actual purpose of input: 'survey' versus 'ranking'
Use E20R_DB_VERSION constant to indicate change in DB version.
Use renamed activation/deactivation functions
Add e20r_db_version option
Create e20r_surveys table definition on plugin activation.
Transition from 'key' to 'index' for table indexes.
Move DB management to its own manage_tables() function
Rename plugin activiation and deactivation functions.
Handle situations where the user has assigned the same post/page to two different articles.
Test for access against a valid $article object.

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