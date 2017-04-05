=== E20R Tracker ===
Contributors: eighty20results
Tags: content management, fitness, nutrition coaching, tracking
Requires at least: 4.7
Requires PHP 5.6 or later.
Tested up to: 4.7.3
Stable tag: 1.6.6
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

=== Shortcode: [e20r_profile] ===
    Show a tabbed view of daily progress page, current progress overview pages, and the Welcome Interview.

Arguments:
    ** use_cards
        *** Description: The layout type to use. Setting this to true, yes, or 1 gives a card based dashboard.
            Not including it or using false, no, or 1, displays the old-style dashboard view.
        *** Type: text
        *** Values: yes, true, 1, no, false, 0
        *** Default: false (when not specified)

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

    ** use_cards
        *** Description: The layout type to use. Setting this to true, yes, or 1 gives a card based dashboard.
            Not including it or using false, no, or 1, displays the old-style dashboard view.
        *** Type: text
        *** Values: yes, true, 1, no, false, 0
        *** Default: false (when not specified)

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

== 1.6.6 ==

* BUG/FIX: Couldn't navigate back or forwards on dashboard


== 1.6.5 ==

* BUG/FIX: Not all text is/was translatable
* BUG/FIX: Didn't make sure there was an actual post to process
* BUG/FIX: Translation slug

== 1.6.3 ==

* BUG/FIX: Didn't properly set the Exerpt/Article Summary

== 1.6.2 ==

* ENHANCEMENT: Automatically load the Yoast meta description to the 'article summary' field

== 1.6.1 ==

* BUG/FIX: Resize Daily Progress content windows
* BUG/FIX: wp_mkdir_p() would fail to create debug directory

== 1.6.0 ==

* ENHANCEMENT: Use singleton pattern to speed up plugin load time
* ENHANCEMENT: Transition to e20r-tracker as translation slug

== 1.5.68 ==

* BUG: Didn't escape the activity excerpt properly

== 1.5.67 ==

* BUG/FIX: Didn't always show the questions and proper paragraph text for assignment(s)

== 1.5.66 ==

* BUG/FIX: Didn't always show the questions and proper paragraph text for assignment(s)
* ENH: Prevent direct execution of class file

== 1.5.65 ==

* BUG/FIX: Didn't flag the delay value for the assignment correctly
* BUG/FIX: Avoid PHP Warnings during summary post display
* ENH: Remove debug data
* ENH: Only include the Post summary header if there are posts to summarize

== 1.5.64 ==

* ENH/FIX: Didn't include all settings for Assignment answer display/save.
* ENH/FIX: Escape more of the assignment output forms

== 1.5.63 ==

* BUG/FIX: Remind Me button didn't work
* BUG/FIX: Error when there's no measurement graph/data to plot
* FIX/ENH: Width of header for Assignments
* ENH/FIX: Colors and positioning for Fix/Remind buttons on pop-up

== 1.5.62 ==

* BUG/FIX: Add delay to measurement data loader

== 1.5.61 ==

* ENH: Sort Assignments progress list by most recent first
* ENH: Add infrastructure to support pagination for assignment progress page
* ENH: Update/modernize plugin build script
* ENH: Pagination for assignment status page
* BUG: Would attempt to use DateTime class method on null/string
* BUG: Fix JS bug when attempting to bind for pagination
* BUG/FIX: Notice when attempting to access empty data object
* BUG/FIX: Program ID wasn't always configured correctly
* BUG/FIX: Correctly identify the article for the measurement page
* BUG/FIX: Return the user to the Dashboard after completing the measurements
* BUG/FIX: Buttons size on measurement(s) page
* ENH: Styling for pagination links/buttons
* BUG/FIX: Error while showing 'loading' page
* ENH: Replace 'Return to Lesson' with 'Return to Dashboard'
* ENH/FIX: Allow user to get a single Program setting (getValue())
* BUG/FIX: Load program/day specific article for measurements
* ENH: Remove 'Page'
* ENH: Improved styling for Pagination text on Assignment Progress
* ENH/FIX: Clean up styling for pagination links
* BUG/FIX: Handle cases where no image is uploaded for user
* ENH/FIX: Button width on measurements page
* BUG/FIX: Hide top level navigation for now
* BUG/FIX: Agree/Disagree buttons on pop-up overlay
* FIX: Removed debug output
* BUG/FIX: Set the currentArticle global
* BUG/FIX/WORKAROUND: Handle uploaded images if/when needed

== 1.5.60 ==

* BUG: Error loading required JavaScript
* BUG: Didn't always save the assignmnet data correctly
* BUG: Multi-select responses weren't decoded properly
* BUG: Undefined property warning
* BUG: Invalid message body error

== 1.5.59 ==

* FIX: Removed wp.heartbeat dependency

== 1.5.58 ==

* BUG: Dependency caused required library to not load
* REFACTOR: Removed stale code & regenerated .min. file

== 1.5.57 ==

* BUG: Edit button width & placement on dashboard page.
* BUG: Didn't save changes to yes/no responses correctly
* ENH: Base64 library update
* ENH: Load minified JS & CSS files if not running w/WP_DEBUG.

== 1.5.56 ==

* FIX: Base64 library went missing. Now using local version of it.

== 1.5.55 ==

* FIX: Didn't check whether the client ID and program ID were configured before attempting to load client data
* FIX: Didn't return the correct value if the Membership Level wasn't set for the user.
* FIX: Didn't always handle AJAX based actions when configuring the program start date for the user(s).
* FIX: Increase AJAX timeout value to 30 seconds
* FIX: Restore old behavior for assignment_info record ID
* FIX: Didn't always verify that the data existed.
* FIX: Verify that user ID & program ID is defined before attempting to load data for user in program
* FIX: Would sometimes incorrectly drop the Welcome Survey article.
* FIX: Didn't always handle surveys correctly while loading data
* ENH: Load tabs for Coach view on click (speed up page loading).
* ENH: Using polling for messages rather than heartbeat (reduce server load) - every 300 seconds.

== 1.5.54 ==

* BUG: Weekly Progress Update dialog didn't display properly in all standard screen sizes

== 1.5.53 ==

* BUG: Would sometimes show the wrong workout level to a user in the archives

== 1.5.52 ==

* ENHANCEMENT: Documentation for loadSettings()
* ENHANCEMENT: Fix debug output for constructor() method
* ENHANCEMENT: (optional) Future use of $delay value in e20rProgram::init() method
* ENHANCEMENT: Fix debug output
* ENHANCEMENT/BUG: Add permissions to role definitions
* ENHANCEMENT/BUG: Use WP Roles to grant/deny group access
* BUG: Couldn't figure out the correct startdate for the user/program because the program wasn't initiated yet
* BUG: Wouldn't set correct startdate for user when using navigation in dashboard
* BUG: Fix syntax error/parser error
* BUG: Would sometimes fail to drop invalid (unexpected) articles
* BUG: Ensure that only articles w/a valid release day are used (valid values: 1 - infinite)
* BUG: Would sometimes incorrectly assume the currentProgram object was configured
* BUG: Would set default access permissions to 'all users' or 'all groups' if none was specified
* BUG: Would sometimes override appropriate group access level (deny what should be permitted)
* BUG: Could occasionally reset login timestamps for users
* BUG: Didn't correctly load E20R Tracker roles on activation
* BUG: Debug output for prepare_activity()
* REFACTOR: Remove stale code
* REFACTOR: e20rTracker.php

== 1.5.51 ==

* FIX: Didn't center the pop-up on large/multiple screens
* FIX: Didn't include 'upcoming week' as a valid period
* FIX: Button positioning for Interview pop-over

== 1.5.50 ==

* FIX: Correctly identified the selected option for user group permission
* FIX: Remove static getExerciseLevels() function - now using the 'e20r-tracker-configured-roles' filter instead (more flexible)
* BUG: Assignment Model didn't include the ID of the post/record when loading the assignment settings.
* ENHANCEMENT/FIX: Use new role-based member groups for activity/workout.
* ENHANCEMENT/FIX: Allow caller-defined sort order (ASC/DESC) for assignments
* ENHANCEMENT/FIX: Allow caller-defined ordering of settings when loading all settings
* ENHANCEMENT/FIX: Automatically upgrade/transition an activity to the new role based group/intentsity settings when loading settings.
* ENHANCEMENT/FIX: Use new role-based Exercise level definitions to select member groups for activity
* ENHANCEMENT/FIX: Moved definition of roles to plugin activation.
* ENHANCEMENT/FIX: Handle unlimited role definitions during activation (defined by Workout/exercise levels)
* ENHANCEMENT: Validate user's Exercise Experience level on login
* ENHANCEMENT: Use 'e20r-tracker-configured-roles' filter to define user/coach roles
* ENHANCEMENT: check_role_setting() for user ID to make sure the user has a valid exercise experience role in the system (defaults to 'beginner')
* ENHANCEMENT: add_default_roles() is the default filter for defining Exercise level roles on the system (array of arrays)
* ENHANCEMENT: Use the default definitions when setting/processing exercise experience level roles
* ENHANCEMENT: Use the default exercise level definitions when doing a user search for the available coaches
* ENHANCEMENT: Lowering the threshold for when we get insistent on completing the Welcome interview
* ENHANCEMENT: Use the 'e20r-tracker-configured-roles' filter for program definition metabox
* ENHANCEMENT: Use the 'e20r-tracker-configured-roles' filter for Workout definition metabox
* ENHANCEMENT: Use 'e20r-tracker-configured-roles' filtered roles/labels for group membership
* ENHANCEMENT: Define exercise levels/exercise roles to determine group/intensity for workout
* ENHANCEMENT: Fixed translation issue for assigned user group selection
* ENHANCEMENT: Automatically assign exercise level based on customer's Welcome interview
* ENHANCEMENT: Use roles defined in WorkoutModel class
* ENHANCEMENT: Convert workout definition to use Exercise level roles
* ENHANCEMENT: Add support for 3 exercise experience roles (New, Intermediate, Experienced)
* ENHANCEMENT: Manually set exercise experience level in user profile
* REFACTOR: Remove stale code
* REFACTOR: Removed unused variable(s) & cleaned up duplicate DEBUG info
* REFACTOR: Remove old (stale) code from plugin
* REFACTOR: Clean up duplicate DEBUG info
* NIT: Removed FixMe
* NIT: Add DEBUG output for Exercise Experience level validation

== 1.5.48 ==

* FIX: Updated version number to v1.5.48
* FIX: Cleaned up layout of 'Notes' field
* FIX: Remove borders for <hr> in the progress update container
* FIX: Removed border(s) for the notes headline.
* FIX: Removed borders under the 'did-you' fields on the daily progress page
* FIX: Load the article defined for the Welcome Survey
* FIX: Didn't always save sanitized text for survey entries.
* FIX: Didn't select the correct survey type when the user edited/added/updated their welcome survey from the Dashboard.
* FIX: Add article ID when looking for pre-existing survey results.
* FIX: Add support for Likert fields in Gravity Forms surveys/questionnaires.
* FIX: Alignment in New Assignment metabox
* FIX: Check validity of the assigned startdate when returning the delay value for the current/specified user ID
* FIX: Initialize the Program ID variable
* FIX: Include the shortname in the program definition (settings)
* FIX: Didn't return false if there were no settings
* FIX: Didn't properly show the selected/assigned male or female coach.
* FIX: Avoid PHP warnings
* FIX: Return explicit false if program ID for a user isn't located
* FIX: Use Membership level to assign program for new user on checkout
* FIX: Set startdate for user's membership based on program they've signed up for
* FIX: Escape attributes
* ENHANCEMENT: Add action description for video
* ENHANCEMENT: Removed confusing date/day-since-start option in Activity setup
* ENHANCEMENT: Changed the log-file size during debug operations
* REFACTOR: Reorder functions

== 1.5.47 ==

* ENH: Support shortcodes in exercise descriptions

== 1.5.46 ==

* FIX: Remove button definition CSS

== 1.5.45 ==

* FIX: Sort by check-in type
* FIX: Properly sort Assignments by assignment day number
* FIX: Wouldn't allow admins/editors to duplicate posts unless they also were a coach.
* FIX: PHP Notice in error log: Undefined variable

== 1.5.44 ==

* FIX: Didn't load the Weekly Progress page
* FIX: Didn't always save the measurement on blur
* FIX: Only load minified JS scripts when the system is running w/o DEBUG enabled
* FIX: Set a specific variable to indicate that the interview is complete for JS
* FIX: Meaning on interview_complete (was interview_incomplete) inverted
* FIX: Set the 'Null' for a field to 0 if there's no data
* FIX: Didn't always load the correct survey type for the page/shortcode
* FIX: Submit form and prevent ourselves from following link
* FIX: Use PHP 5.x style constructors
* ENH: Start working on underscore.js based front-end JavaScript templates
* ENH: Remove whitespace
* ENH: Add debug info for whenever we have to load NULL measurement records
* ENH: Return a NULL record if there's no data in the DB
* ENH: Make debug info more specific
* ENH: Enable the exception for an empty field
* ENH: Updated the plugin upgrade library

== 1.5.43 ==

* Fix: Various nits in CSS
* Fix: Simplify path to debug log
* Fix: Sometimes would clobber existing function definition
* Fix: Didn't always return correct access permissions for pmpro protected content
* Enh: Start investigation into using underscore.js templates for front-end.
* Enh: Add e20rActivityView function to manage template(s) and workout timer/wizard
* Enh: Add initial outline for showing activity as wizard/on-screen timer
* Enh: Started working on on-screen countdown/wizard for workout/activity

== 1.5.41 ==

* Fix: Handle unexpected date format(s) for start & end date settings in metabox
* Rename archive tags for CPTs
* Fix: Would include daily message when front-end scripts to handle/manage it wasn't available.

== 1.5.40 ==

* Fix: Would sometimes generate PHP warning message
* Fix: Would sometimes attempt to access the wrong namespace (and exit ungracefully)

== 1.5.39 ==

* Fix: Would sometimes trigger PHP warning message while processing prerequisite javascripts on page load
* Fix: Didn't always return a valid assignment ID
* Fix: Didn't always return the correct program information on init()
* Fix: Would not always display the article summary

== 1.5.38 ==

* Fix: Didn't always load the correct values for the program_ids setting
* Fix: Change label for Activity settings
* Fix: Return empty array of program_ids for workout/activity if none is specified
* Fix: Didn't always show duplicate link to users with the correct privileges.
* Fix: Didn't always save the program ID(s) that a post_type belonged to (Article/Activity/Action/etc)
* Fix: Sometimes would include a weird program name/id
* Fix: Doesn't always save the Program id the workout belongs to
* Fix: Possible warning during DEBUG logging
* Fix: Don't use the article's editor content (post_content) in the summary. Enh: Allow user to specify a title for the article_summary shortcode.
* Enh: Allow user to specify title for Article history/summary shortcode
* Enh: Allow user to specify title for Article history/summary shortcode
* Add support for an HTML/Text field to be displayed in the Assignment (daily_progress shortcode)
* Add support for HTML/Text only entry in Assignment (daily_progress shortcode)
* Add support for explicitly excluding content from the kit
* Allow use of article description as the content for the weekly reminder text.


== 1.5.36 ==

* Fix: Couldn't always save a note in the Dashboard
* Enh: Expand .gitignore to exclude composer content

== 1.5.35 ==

* Fix: Namespace use by new Sequences plugin.

== 1.5.34 ==

* Fix: Reformat nav bar on dashboard
* Fix: Use 3 character day name in nav labels
* Enh: Add 'current day' label in navigation box for dashboard
* Refactor: e20rAction class & remove old code

== 1.5.33 ==

* Fix: Wouldn't update/save activity check-in consistently
* Add error correction for assignment_id value(s)
* Refactor link for excerpts
* Remove unused debug output.

== 1.5.32 ==

* Fix: Simplified test for whether to run content filter
* Fix: Didn't include 'lesson complete' info in post/page on load.
* Fix: Reordered message banners
* Fix: Make constructor PHP 5 compliant (and ready for PHP 7)
* Fix: Wouldn't always handle buffering correctly for measurement alerts
* Fix: Didn't always display the expected activity for the day the user had navigated to
* Refactor: Clean up comments and removed code
* Refactor: e20rArticleView class

== 1.5.31 ==

* Force redirect to use POST method for data.
* Add debug for POST on submit/click for daily workout/activity.

== 1.5.30 ==

* Select the correct name for the hidden activity ID input field
* Fix: Get the URL constant for the activity url to redirect to
* Rename the input field for the activity
* Fix: Would not show the correct unit type (seconds) when using AMRAP and time based sets

== 1.5.29 ==

* Fix: Didn't show time designation when using AMRAP type exercises

== 1.5.28 ==

* Make frame for progress container gray
* Refactored Controller for Articles
* Fix: Didn't show 'Weekly Status Update' on pages/posts for days where status update was requested and not complete
* Fix: Order message notification, weekly status update, and other alerts to top of page/post
* Fix: Would sometimes change the existing article during access check. Refactored code
* Initial commit for new(ish) autogrow implementation

== 1.5.26 ==

* Fix: Would not load all required scripts for the daily_progress short code
* Fix: Handle default style (already included by Wordpress) dependencies
* Fix thickbox css load

== 1.5.25 ==

* Fix paths to jQuery.ui.touch-punch plug-in

== 1.5.24 ==

* Load local touch-punch plugin for jQuery.ui
* Force regneration of minified file & initial commit
* Initial commit for locally loaded jQuery.ui touch-punch plug-in.

== 1.5.23 ==

* Fix: Would not save assignments answers
* Refactored e20r-assignments.js

== 1.5.22 ==

* Fix: The label for the tempo wasn't being printed
* Fix: Init of the speed types for the Workout/Activity model
* Fix: Spacing for text when printing exercise description(s)
* Refactor obsolete CSS and JavaScript files
* Changelog and version number updates


== 1.5.20 ==

* Load the correct icon for active.png

== 1.5.19 ==

* Didn't always load assignments related CSS & JavaScript for daily_progress shortcode

== 1.5.18 ==

* Modify loading behavior (try to avoid heartbeats causing loading screen to pop up)
* Add list of actions to default check-in structure if the check-in type is an action
* Update debug text to help clarify status
* Modify loading behavior (try to avoid heartbeats causing loading screen to pop up)
* Fix typo in error message during check-in save operation
* Fix URL to access new message from client in alert email

== 1.5.17 ==

* Rename e20rActionModel::loadUserCheckin() to e20rActionModel::get_user_checkin()
* Couldn't handle situations where the card setting was empty (i.e. default value)
* Add preventDefault() for day/date navigation in dashboard (ensure the browser doesn't actually follow the link back to the dashboard)

== 1.5.16 ==

* Wouldn't send e-mail alerts when client responded to coach.
* Would inadvertently return the first message as the last

== 1.5.15 ==

* Send e-mail notice if user sends a message to their coach.
* Remove inactive (no longer member) clients from list of clients to display in drop-down
* Used wrong identifier in get_user_by()
* Only attempt to update the login timestamp if the User ID is something other than 0
* Didn't properly account for cases where the user's login timestamp wasn't correctly recorded.
* Didn't include the date when displaying message summary in thickbox
* Don't include timestamp (avoid UTC/TZ issues)
* Include instant(ish) messaing history in back-end sorted message-history page/tab
* Add method for extracting the Assignment question based on assignment_id value
* Sort based on 'sent' attribute in array(s)

== 1.5.14 ==

* Update version number (1.5.14)
* Add update functionality for v11 of the DB tables
* Would sometimes fail to display the profile/daily progress check-in content.
* Not displaying e20r_profile when some of the expected check-in definitions for the article are missing
* Showing 'day in the past' warning when some of the expected check-in definitions for the article are missing
* Tweak border & font weight for inner tabs when displaying measurements, etc.
* Add debug for unread message count Correctly handle show/hide of new message alert on page load.
* Load new message alert to post if daily_progress shortcode is present in content
* Incorrect use of currentArticle->id before the article had been initialized/loaded
* Use new load_user_assignment_info() so we include message history as well
* Add AJAX handler for message status updates
* Add AJAX handler to save message replies from front/back-end
* Add AJAX handler to return assignment list (HTML, including messages & history) for a specific client ID
* Include controller functions for heartbeat handling and identifying how many new messages a client has
* Would return a positive message count for user, even when all messages were either read or archived has_unread_messages() included messages sent from the current user when looking for new messages
* thread_is_archvied() would include messages sent from the current user when reporting that a message wasn't archived
* Include recipient_id when looking up message history
* Include the new_message_warning() HTML in the content whenever we're looking at a page/post with a e20r_profile, e20_progress_overview or e20r_activity_archive shortcode.
* Working to isolate issue where profile isn't displaying if there isn't at least one action, activity and check-in included for dailyProgress in the article definition
* Process message history and insert in the Assignments list (feedback goes with assignments, for now(?))
* Refactor and move to e20r-assignments CSS file
* Refactor and handle message transmission/reception better
* Force initial heartbeat within 2 seconds of page loading.
* Add e20r_response fields to better manage to/from of messages
* Add support for archiving a message (by the recipient)
* Remove invalid null entries for settings that are arrays.
* Add handling for e20r_response table (messages between clients & coaches)
* Simplify loading of user assignment data & have it include any messages.
* Add formatting for message alert, history & transmission
* Add heartbeat handling so we can use the heartbeat to update new message status on front
* Add AJAX handler to reload assignment list on progress page/tabs
* Load e20r-assignments.js for back-end (handle messages)
* Add autoGrown for message input
* Include assignments scripts (message handling) to list of scripts to load for e20r_profile and progress_overview shortcodes
* Clean up unused code from load_frontend_scripts()
* Add handler for assignments to load_frontend_scripts()
* Add thickbox support to progress overview in load_frontend_scripts()
* Add e20r_response table to database & ensure client ID and recipient ID are indexed
* Add alert for new coach messages to pages with progress overview and profile view
* Refactored is_user_logged_in() for daily_progress shortcode
* Add basic handler for coach/client messaging AJAX
* Commented out the saveAssignment_callback() (apparantly unused?) function.
* Add handler for client/coach messaging system
* Init the e20rClientAssignment class when the AJAX load of the assignments table has been completed (if applicable)
* Fix color management for back-end buttons
* Format size of feedback textarea
* Add feedback messages/buttons for assignments
* Add AJAX handler for assignment replies/feedback
* Load assignments JavaScript in back-end to handle submitting/reading new assinment feedback
* Add 'read' flag in e20r_response table
* Return result of dbDelta() for the e20r_response table
* Add coach specific feedback buttons for assignments table.
* Update response table definition
* Move response fields out of assignments table, use response_id for the assignment and save response data in its own table (e20r_responses).
* Add response table definition
* Add response fields to e20r_assignments table

== 1.5.13 ==

* Didn't use specified post_id when looking up a post for the card configuration
* Rename loadWorkoutData() to load_activity();
* Didn't always include the exercise ID when saving workout record.
* Rename loadWorkoutData() to load_activity();

== 1.5.12 ==

* Properly align text in email display when showing the sent email message
* Remove old HTML Escape content in emails being sent to clients
* Escape subject line (strip slashes, etc)

== 1.5.11 ==

* Transition to new class name for check-in actions
* Fix JavaScript error in daily_checkin and e20r_profile shortcodes
* If we have nothing to convert, exit from the conversion function early.
* Rename e20rCheckin* classes to e20rAction*
* Rename e20rCheckin variable to e20rAction (and currentCheckin to currentAction)
* From Custom Post Type e20r_checkins to e20r_actions
* Transition from *-checkin_ids to *-action_ids as meta data tag for articles
* Only change the startdate to that of the user's program start if we're not in the back-end and we're not processing for another user (i.e. a coach looking up data for a user)
* Correctly format the start/end date for a program if the startdate/enddate variables are configured
* Add constants for feedback types on cards
* Add hook for e20r_article_archive shortcode
* Whitespace clean-up Remove unneeded loop in load_for_archive()
* Remove unused variable ($gv)
* Use autoloader for classes

== 1.5.10 ==

* Not all instances of the getArticles() arguments were correct after the refactoring in v1.5.9
* Change when we load the font awesome library in wp-admin/

== 1.5.9 ==

* Need to account for new find() argument order Removed unused variables from scope
* More precise debug info to help locate problems
* Would sometimes add the program ID to the search when searching for explicit post IDs
* Let filter override class name for dialog class

== 1.5.8 ==

* Remove old add_popup_overlay from e20rArticleView class
* Add formatting for pop-up dialog for incomplete interviews
* Fix formatting on small screens when using default (old) daily checking page.
* Add debug for record_login function
* Fix uninitialized variable warning
* Add load_for_archive() which returns an array of all articles w/post_id & release_day values available to the user at this time
* Add more infrastructure for generating archive of articles (not yet implemented)
* Removed old pop-up warning from contentFilter() and moved it to the ClientView class.
* Don't attempt to add date variable to permalink if user isn't logged in
* Add pop-up warning to profiles where the user hasn't completed the Client Interview within two weeks of starting the program
* Add a functional 'incomplete interview' pop-over warning for end-users when accessing their profile page
* Reordered arguments for find*() functions
* Removed unused variable
* Reorder hooks & load only what's needed based on users login status.
* Simplify nopriv AJAX response (always an error if the action is one of ours)
* Update all AJAX action names to add the e20r_ prefix
* Update URL for fontawesome
* Redirect to login page if user isn't logged in & is attempting to access one of our shortcodes
* Would sometimes attempt to display dependency warning to users while they weren't on the dashboard page
* Changed order for find() functions
* Didn't redirect to login page if user who isn't logged in attempted to view the profile page.
* Add e20r prefix to all AJAX calls
* Add getters and setters for active_delay and previous_delay settings. Remove unused code
* Don't save active_delay and previous_delay settings values
* Remove inactive code from saveSettings();
* Set active_delay and previous_delay to settings.
* $active_delay is always the current delay value for the current active user.
* Prevent XSS vector when manipulating permalinks

== 1.5.7 ==

* Attempt to shorten the amount of time spent loading data and leverage cached data instead.
* Added the date of the post/article as part of the URL, using it in article_summary shortcode
* Skip loading of scripts & styles when doing AJAX calls
* Fix debug output in hasAccess()
* Remove clearfix class for day navigation bar
* Add day value as hidden input field for card based dashboard
* Always include today's 'release_day' for the user in the action and activity check-in dashboard
* Add filters that allow us to add date(s) to posts that have articles defined for them (used to help process shortcodes, etc)
* Added article_date query variable
* Added rewrite tag for article_date variable
* Adding rewrite rule to process date values when available

== 1.5.6 ==

* Didn't include the shortcode attribute (days) override in e20r_article_summary shortcode
* Didn't always load post summaries when using the e20r_article_summary shortcode
* Remove inner border for activity archive
* Remove the clearfix class from the day navigation bar in the traditional dashboard view

== 1.5.5 ==

* Respect dont_drop setting in e20rArticleModel::find()
* Don't drop future articles if we're processing the activity archives
* Archive page should not include tracking fields in display
* Hide buttons and tracking fields in activity definition on request (set $hide_print to true)
* Archive page didn't correctly format all of the layout on display

== 1.5.4 ==

* Fix warning during debug output
* Would sometimes generate warning while loading daily_progress shortcode
* Refactor for formatting purposes
* Removed clearfix from date navigation bar

== 1.5.3 ==

* Didn't always respect the article Id passed to the releaseDate function.
* Fix whitescreen of death (undefined method)

== 1.5.2 ==

* Would sometimes allow access to articles that were scheduled for a future date because the post in the article is a repeating posts that the user currently _does_ have access to (with a different Article ID)
* Add pmpro_has_membership_access_filter processing back to has_access() function
* Don't ignore current user 'days since start' (delay) value vs article release_date when checking for access and PMPro is installed
* Find the closest release day to a specified key (delay) value for articles.
* Add current day's delay value to the navigation $_REQUEST[]
* Clarify debug info

== 1.5.1 ==

* Add clearfix so layout of page is correct.
* Don't throw warning when user isn't logged in and we're sending email messages.

== 1.5.0 ==

* Limit size of the traditional daily check-in dashboard to 720px max width
* Lesson and activity summary boxes weren't formatted correctly
* Increase size of font for check-in text
* Double size of empty note textarea
* Use class names for activity & lesson cards
* Fix how shortcode_dailyProgress() handles arguments/attributes
* Set use_cards in config variable while preserving any passed values
* Fix shortcode attribute handling for the e20r_profile shortcode
* Update function name in debug output for shortcode_clientProfile() function
* Make sure the correct style is applied to the specified layout for the action & activity lesson/summary cards/dashboard entries
* Include hidden variable for layout style in both layout options
* For card based layout, move the notes section to the bottom of the list for the day.
* Fix line-width for activity check-in
* Whitespace removal
* Add update_period setting for config (to be used by the card display)
* Add support for capturing status of card based dashboard (yes or no) from $_POST
* Select the type of display for the dashboard ('cards' or 'old-style' dashboards)
* Use cards setting for 'getExcerpt()' function
* Initial commit the minimized article-summary css file
* Add formatting for navigation to next trackable exercise/activity
* Display navigation link as a button Set location for navigation link/button
* Transition fully to a print friendly activity page.
* Adding support for configuring data to be used by the card-based dashboard display
* Handle card based display(s) in excerpts
* Add formatting to the link for the excerpt (allow displaying links as buttons, for instance)
* Fix warning when displaying user information.
* Add update_period setting for config (to be used by the card display)
* Add support for capturing status of card based dashboard (yes or no) from $_POST
* Select the type of display for the dashboard ('cards' or 'old-style' dashboards)
* Use cards setting for 'getExcerpt()' function
* Add support for use_cards attribute in e20r_profile shortcode.
* Always use card layout for action & activity display (text lesson info)
* Initial commit of new autogrowtextarea.js plugin for jQuery (used for Notes field on daily update page)
* Initial commit to support future 'card based' dashboard layouts
* Add support for passing whether to use action/activity cards vs the current dashboard layout
* Didn't always save the note entered (in spite of what the feedback to the user was - yes, we lied!)
* Fix size calculations for note overlay
* Refactor the Notes class (make it more of a class!)
* Add clearfix class
* Fix margin for check-in fieldsets (daily activity)
* Fix issue(s) with notes area on daily check-in page
* Allow using a 'card' view for actions/activities (load CSS file for cards)
* Upgrade to autoResize by @jevin on https://github.com/jevin/Autogrow-Textarea for notes area on dashboard page.
* Notes section would indicate 'saved' even when the save failed.
* Fix typo for jQuery()
* Hide navigation buttons Force hide elements while printing
* Simplify navigation between input fields for activity overview (daily activity view).
* Fix for handling for edit button during activity & action check-in pane in dashboard
* Avoid double events on click in dashboard
* Remove unneeded debug logging to console
* Refactor for style compliance
* Adding infrastructure for displaying dashboard as cards of 'stuff' rather than a check-in/lesson configuration (old-style) triggered by 'use_cards' attribute in shortcode
* Refactor how the dashboard is created/generated
* Fixed issue where activity check-ins didn't quite work (highlight of completed activity check-in)
* Remove link symbol (too broadly applied)

== 1.4.8 ==

* Limit link character to the pages where the link symbol should exist (i.e. not menus, back-end, etc)

== 1.4.7 ==

* Fix Phase description
* Refactor formatting for printable activity/activity summary pages.

== 1.4.6 ==

* Add print icon to daily activity page
* Hide title on print page(s) for activities on load
* Add support for clicking print icon to print daily activity page.
* Hide printer icon when page is too small (i.e. on phone)
* Tentative CSS support for down arrow (to indicate scroll direction for additional content)
* Hide printer icon on page being printed
* Remove all navigation and header elements when printing the page

== 1.4.5 ==

* Transition to .clear-after class (from .clearfix)
* Refactor @media screen formatting Add new group handling/formatting
* Make the new-group print independent
* Make new exercise header bar visible on screen & print
* Add link symbol for <a href>
* Make weight/rep updates work in printable view.
* Refactor CSS for print formatting
* Refactor for Wordpress formatted file(s)
* Fix formatting for saved data in weight/rep table inputs
* Fix small-screen video size (Phone in Portrait mode)
* Set font/display for 'print_only' attribute based displays while @media == screen

== 1.4.4 ==

* Handle multiple variable values for shortcode attributes in the affirmative (and return error message(s) if input isn't supported/understood)
* Support print mode for [e20r_activity] Start escaping variables in HTML form(s)
* Remove @media print formatting from the activities.css file and place it in print.css instead
* Partially duplicate e20r_activity HTML in support of the display_printable_activity() view
* Escape most attributes in web form(s) for activity.
* Support display_type='print' for the [e20r_activity] shortcode
* Add @media print formatting for individual activity listing (i.e. when e20r_activity shortcode has display_type='print')
* Update background picture for the gender selector page CSS.
* Updated dimensions of alert.png image
* Refactor @media screen entries
* Remove HTML element styling & place in .css file.

== 1.4.3 ==

* Fix formatting for weekly progress reminder

== 1.4.2 ==

* Move styles from HTML to css for weekly progress reminder
* Make text translatable for the weekly progress reminder
* Update formatting for weekly progress reminder.

== 1.4.1 ==

* Add e20rTracker formatting for gravity forms.
* Refactor & format to comply w/Wordpress standards
* Support a printable format for the activity display.
* Fix formatting for links on printed pages.
* Add initial framework for printable activity display
* Add support for a show_reps setting in view_exercise_as_*() functions.
* Add print_only attribute to activity archive shortcode
* Use summary of activity list if user requests a printable list of activities
* Remove unused variables from shortcode_activity()
* Add 'display_type' attribute to e20r_activity shortcode.
* Load print specific CSS file separately
* Fix issue when loading css & javascript for exercise shortcode
* Add print_only attribute to activity archive shortcode
* Allow summary of activity list if user request printable list of activities
* Remove unused variables from shortcode_activity()
* Add 'display_type' attribute to e20r_activity shortcode.
* Split print specific CSS to its own CSS file
* Add print specific CSS for exercises and activities.
* Add support for a printable list of activity descriptions as part of the e20r_activity_archive shortcode
* Fix error message when there are no activities found for the specified period
* Differentiate between upcoming weeks and current/previous weeks in archive display.
* Include minified file in repo.
* Update to reflect page dimensions for print view.
* Refactor file to use Wordpress formatting
* Refactor video content view
* Split old-style (row based) and new-style (column based) exercise views.
* Add support for old style or new-style display of exercise information in print_exercise( $show, $display )
* Add support for specifying display type in e20r_exercise shortcode (i.e. 'display='column|row'')
* Separate CSS for exercise display out from overall activity display.

== 1.4 ==

* Make sure the 'Assignment Complete' logic works for cases when posts are being repeated on different days in contentFilter()
* e20r_article_summary shortcode would sometimes cause page to go blank
* Return the date of the last post included in the post summary list, not the date of the article generating the list.
* Fix padding/spacing for article summary date values
* Make heading for the article summary list match the formatting for the [daily_progress type='assignment'] formatting.

== 1.3.9 ==

* Add support in article definition for summary_day and max_summaries settings. summary_day is boolean, max_summaries is numeric.
* New shortcode: [e20r_article_summary days=''] - Generates a summary of articles for the specified # of days. #of days can be configured in the shortcode, or in the article that defines the lesson/reminder/action/activity.
* Only trim the excerpt if the content is being pulled from the post itself.
* Avoid warning message due to incorrect array element address assumption.
* Refactor loadAllHooks()
* Load article summary specific CSS file for article summary pages/posts. (in e20r-article-summary.css)
* Remove shortcode_check() as it’s now inactive.
* Add support for editor in e20_articles CPT definition
* Fix issue where dates on dashboard navigation was “off-by-one”.
* Fix cases where the activity archive would be empty due to timezone confusions.
* Rename debug for prepare_activity() function in e20rWorkout.php
* Reformatted e20rArticleModel.php
* Add display/view for article summary shortcode.
* Refactor the metabox for the article settings.
* Add onClick() event for “Read more” link for lesson/reminder/post/page on daily progress dashboard.
* Pass article ID and other config information to e20r_article_summary shortcode.
* Avoid duplicate event handler actions in onClick() events for daily progress dashboard.

== 1.3.7 ==

* Load FontAwesome from CDN (used by plugin for wp-admin icons)
* Removed debug output
* Add CSS for pop-up overlay
* Move pop-up for interview completion to e20rArticle classes.
* Add pop-up overlay view (can contain custom message and client related info).
* Load timeout value for back-end client info page.
* Add interview_complete boolean for front-end scripts
* Removed check of whether to skip the Intake form ( It's always loaded as part of the profile page so redirection, etc should be handled there )
* Added content filter check for whether Welcome interview is complete. If not, load NAG screen.
* Wouldn't load user info on back-end due to variable mismatch
* Made timeout variable for jQuery.ajax() calls more generic
* Add Pop-Up management functions (Init, open/close, etc).
* Didn't always return the correct date for getDateForPost()
* Make sure we test for a type specific value when checking the user's ID.

== 1.3.6 ==

* Add debug output to completeInterview()
* Add caching of interview completion status
* Move loading of client interview data to the model class (out of the controller class)
* Rename loadClientInterviewData to load_interview_data_for_client()
* Return debug output if the client interview form ID isn't available/not configured.
* Updated issues list

== 1.3.5 ==

* Change version number (1.3.5)
* Refactored for style
* Removed some variable dumps in debug output

== 1.3.4 ==

* Updated minified CSS files for font-awesome fix.
* Ensure program ID is loaded for the requested user when loading client data.
* Remove (likely) unneeded access check for post->ID and user ID.
* Filter for hasAccess value (PMPro related) broke Gravity Forms load.
* Add fontawesome CSS fix

== 1.3.3 ==

* Didn't always handle next/previous week for activity archive
* Sometimes cause warning while checking for e20r_client_overview shortcode
* Use new static function sequences_for_post() to obtain list of PMPro Sequences associated with the post id
* Use new static function post_details() to obtain sequence configuration for post_id from PMPro Sequences
* Use new all_sequences() static PMPro Sequences function to obtain list of available sequences
* Remove superfluous load of Font-Awesome css file

== 1.3.2 ==

* Configure default/global timeout setting for AJAX operations (currently 12 seconds)
* Load jqPlot whenever it's needed
* Clean up naming for javascript files
* Include complete setting
* Fix debug message
* Add fontawesome icons to admin pages.

== 1.3.1 ==

* Load client-detail page the same way from both e20r_client_overview short-code and directly from the /wp-admin menu.

== 1.3 ==

* Use timestamp (not formatted date) for message history
* Fix get_clients() to it uses the coach specific program/client list usermeta setting (e20r-tracker-client-program-list), now returns an array of programs w/lists of clients coached by that coach for the program(s)
* Enh: Add get_program_name( $id ) to return the name of the program specified as an ID
* Enh: get_program_start( $program_id, $user_id) to configure & return the startdate for a specific users program.
* Enh: get_coaches_for_program() - Grab all program coaches and their display names.
* Enh: setProgram( $program_id ) to load a program's settings
* Enh: Load the client detail /wp-admin/ page from the e20r_client_overview shortcode.
* Enh: Add short code for list based client overview (a coach specific page summarizing status of client & linking to client details)
* Make contentFilter() a little more readable & optimized.
* View timestamp version of send time for client message as date/time.
* Add view for e20r_client_overview short code
* Fix viewLevelSelect() so it supports the selection of a specified level ID
* Fix viewMemberSelect() so it supports the selection of the $currentClient->user_id on load
* Didn't always load the users for the specified level to the back-end client detail page.
* Add support for pre-selected user ID/membership level in viewClientAdminPage()
* Load client data by passing $_GET variables to the 'e20r-client-data' admin page ('e20r-client-id' and 'e20r-level-id')
* Complete support for e20r_client_overview shortcode for system coaches.
* Add support for the e20r_client_overview shortcode
* Refactor CSS load for the weeky_progress shortcode
* Only load the jqplot scripts if they're needed.
* Add support for a global 'getLevelIdForUser()' function (returns the program ID the user is a member of).
* Add formatting for new client overview short code (reserved for coaches)
* Make labels in back-end for user profile more descriptive Didn't consider that the data about the users coach is passed as an array ( ID => name )
* Assign and save a program coach to a user when in the back-end user profile view if the user doesn't already have a coach but is a member of a program.
* Save the client assignment details for the coach ID
* Whitespace clean-up
* Fix typo in meta-key finding a coach based on a list of programs
* Assign a coach to the user whenever the welcome interview is being saved.
* Add function to handle coach assignment based on the pool of coaches (and how 'loaded' they are) for the gender (if appropriate) of the client.
* Allow reuse of the 'user isn't a coach' error message for /wp-admin/
* Assign programs for the user to be a coach for in user profile view (/wp-admin/)
* Rename view_userProfile to better document its purpose. Enh: Allow admin to configure the programs this user is a coach for
* Add coach_id to the client settings/profile Fix load_client_settings() so it uses the $clientId variable it's been passed (can't count on $currentClient to be populated already) Add get_coach() function to load coach information for specified client id in program_id
* Fix activity archive time calculations to make sense regardless of day of the week they're being requested.
* Return no activities if the request is for the next week of activities and the current day of the week isn't Saturday or Sunday.
* Only process the unsorted/sorted articles if the count of activities is != 0.
* Fix warning/reminder about future activities & include probable visibility date(s)
* Enh: Allow bucketing of coaches for male and female clients in the program as part of the program definition.
* Whitespace fixes
* Rename wp-admin profile views
* Assign & save the coach for the user (in wp-admin/users.php view).
* Rename views used on the user profile page(s).
* Add coach information to the profile page for the user(s).
* Fix colspans
* Support multiple male & female coaches per program
* Handle arrays of coaches (male & female) on load/save.
* Add support for listing male/female coaches to the program definition (used when assigning coaches when the client completes their welcome interview).
* Change 'lesson' to whatever the $currentArticle->prefix value is set to (Lesson/Reminder/whatever)
* Convert ID to class for e20r-assignment-complete div to allow two instances of the banner (top & bottom of page/post).
* Insert hidden or visible 'assignment complete' banner at top of article/post depending on $currentArticle->complete setting (i.e. whether the assignment is completed or not).
* Clean up the text for the incomplete interview information note.
* Update incomplete interview text for sub-header & make it translatable.

== 1.2.11 ==

* Fix hasAccess() so it respects the access permissions from the drip-feed plugin.
* Transition back to minified scripts

== 1.2.10 ==

* Fix lookup of e20r_progress constant (for when in wp-admin).
* Only load status tabs if on a page where the tabs elements exist (i.e. not a daily_progress assignments page).
* Fix daily_progress javascript to allow navigating to next/previous days in dashboard.
* Don't process content filter for article if running on page with e20r_profile short code
* Clean up comments
* Fix article look-up when article ID or delay is defined in $_POST variable.
* Don't process content filter for article if running on page with e20r_profile short code
* Update formatting for edit buttons in check-in
* Make 'Incomplete Interview' text more visible (bolder)
* Load minified e20r-progress-measurements.js script
* Fix text color for primary-button in back-end (Send Message)

== 1.2.8 ==

* Load codetabs for back-end as well.
* Show progress info correctly on both front & back-end
* Fix tab formatting for back-end client info page

== 1.2.7 ==

* Clear progress overview info from profile tabs where it's not supposed to appear.
* Include minified .js file for progress-measurements info
* Fix path to image(s) in post_type_icon()

== 1.2.6 ==

* Fix group tempo setting in backend

== 1.2.5 ==

* Add e20r_exercises to list of CPTs that can be duplicated.

== 1.2.4 ==

* Fix button coloring for presale button(s)

== 1.2.3 ==

* Only load scripts that are needed for the e20r_profile shortcode.
* Always load minified CSS & Javascript.
* Add minified CSS to repository

== 1.2.2 ==

* Include refactored images

== 1.2.1 ==

* Fix syntax error in e20r-progress-measurements.js

== 1.2 ==

* Remove commented out tabs functionality.
* Fix display when nesting profile and status tabs.
* Fix codetabs() padding for smaller screens when using flatbox layout.
* Make survey forms responsive
* Comment out codetabs() from #inner-tabs
* Remove all traces of zozoTabs() (defunct)
* Make sure we only load the e20rCheckinEvent() class once.
* Add codetabs() javascript tabs
* Add CSS for codetabs() javascript tabs
* Refactor e20r_profile page display to simplify (minimize) load of unnecessary data.
* Refactor loading for css, prerequisite & script (javascript) for short_codes.
* Load both codetabs() and jquery.ui.tabs() scripts for profile & progress_overview shortcodes.
* Use codetabs() for progresss_overview (i.e. #status-tabs) tabs Use jquery.ui.tabs() for the Weight & Girth graph (#inner-tabs) tabs
* Refactor code for #inner-tabs
* Use relative positioning of quote character in <blockquote>
* Force red warning box around e20r-paragraphs.
* Configure profile-tabs and status-tabs on init. Use codetabs() to manage profile & status tabs.
* Use e20r_progress variable/object on load (if available)
* Fix warnings in loadClientInterviewData()
* Check whether interview is complete & select corresponding description for the Interview tab in e20r_profile shortcode.
* Limit tabs on e20r_profile shortcode to the dashboard, the progress overview and the interview info.
* Refactor /images/ to /img/ Add debug info for progress_overview shortcode
* Incorrectly defaulted to a specific user ID when loading user activities.
* Fix possible corruption when loading the type of a workout group.
* Load activity info/settings independently of short code (so it can be used in other short_codes as well)
* Change how front-end scripts & css is loaded for the variou short_codes.
* Return if the user hasn't defined an activity (prevent 'dummy' activities from being displayed)
* Add label for interview completion (warning/informational) when listing the interview info
* Fix issue where plural was incorrect for estimated times to read between whole minutes.
* Fix warning for articleId Fix warning for show_tracking Fix warning/corruption for group_tempo setting(s)
* Refactor /images/ to /img/
* Load content for e20r_profile tabs as well as tab heading & descriptive text.
* Don't use the modal div in the profile-tabs (it's included in the dashboard already)
* Remove unused code
* Add debug output to help identify issues with workout intensity type/speed
* Fix the url constant (make it consistently 'ajaxurl')
* Fix button styles
* Make sure the layering (z-index) is correct for button vs floating theme menu
* Add formatting for the zozoui tabs and the standard jquery UI Tabs
* Refactor to concentrate javascript & CSS load functions
* Make the 'release day' column sortable in the edit.php screen for the e20r_assignments post type.
* Move the column management functions to the e20r_assignments class file.
* Add support for using zozoui tabs
* Support loading the welcome interview survey if the user is on the e20r_profile page/short code.
* Refactored path to images for plugin.
* Renamed images directory to img/
* Check that the post status for the activity is one of the defined statuses we require.
* Add warning icon
* Add warning/message dialog for interview page(s)
* Fix formatting for tabs
* Formatting updates to lesson display in e20r_profile shortcode.
* Reformat date info (excludes year) for when post was released (for the current user ID)
* Remove author link
* Refactor e20r_profile processing to avoid unneeded activity during preview lessons/reminders
* Add article ID as hidden field for workout(s)
* Add validity check for an array of database fields to enter into the checkin table.
* Add function to save array of data to check-in table.
* Fix translation for Dashboard error/warning messages
* When user clicks 'Activity complete' button, save the activity check-in too.
* Add support for saving an activity status from the front-end to the check-in table in the database.
* Support saving a check-in whenever the user clicks the 'Activity complete' button on the front end
* Include the article id in the workout definition
* Add ability to return a short_name for a specified check-in ID
* Support saving (replacing) a check-in of a specific type.
* Function to fetch the short_name for the specified article and check-in type.
* Remove unused functionality
* Add support for e20r_profile shortcode
* Add e20r_profile shortcode hook
* Include progress javascript files when using the e20r_profile shortcode
* Differentiate between when the e20r_profile shortcode loads the progress info and when the progress_overview shortcode does so.
* View for tabs when using the e20r_profile shortcode
* Simplify isNourishClient() test (nobody is a nourish client from a data collection perspective)
* Load client specific data if the currentClient info isn't already loaded in loadClientInterviewData()
* Define a common dailyProgress configuration function
* Simplify shortcode processing for dailyProgress by using shared configuration function.
* Various formatting for e20r_profile shortcode
* Let admin select page to display for 'contact the coach' information/forms/feedback
* Let admin select page to display to manage membership account data
* Let user defined whether to show the modal 'loading' feedback or not as part of the tabbed progress overview view
* Add formatting for whenever we display the incompleteIntakeForm() content/page.
* Add show_progress() function to load tabbed progress overview view from external class/service.
* Add support for defining a 'Contact the coach' page/post
* Add support for defining a 'Your Account Information' page/post (contains membership plugin profile info & billing info, for instance)
* Add support for loading a lesson/reminder/post for an article (to a 3rd party entity)
* Add support for indicating an article that's part of the 'preparation sequence' for a program

== 1.1.21 ==

* Fixed a 'No Interview' redirect loop

== 1.1.20 ==

* Whitespace changes to force minification
* Save updated/changed weight/length unit setting
* If the welcome interview is incomplete, redirect the user to a page describing the problem.
* Configure user settings before changing & updating weight/length unit info.
* If the welcome interview is incomplete, redirect the user to a page describing the problem.
* Fix layout for weight/length unit change
* Support cancel option for weight/length unit change
* Fix typo in weight units setting
* Return true if there is no data to convert in updateMeasurementsForType()
* Add default conversion factors between same unit types
* Add 'incomplete interview description' page setting
* Add debug info for interview_complete() function
* Fix warning message during load of weekly progress related data to front-end.
* Load bare minimum of the client data for front-end JavaScript.
* Add ability to only load the basic client data rather than the full survey.
* getDateForPost() assumed calculations start on day 1 (they start on day 0)
* Support returning HTML for incomplete measurements page if interview isn't completed yet.
* Make text translatable.
* Force setting of program startdate on init (allows startdate to be set by users startdate if needed)
* Removed init of article object when loading program (Should be handled in Article class).
* Added support for how to handle incomplete intake form(s).
* Adds descriptive page for whenever the user hasn't completed the intake form(s)
* Add support for simplified minimum DB load on init of e20rClient class.
* Incorrect date calculated when looking for activities based on delay values.
* Remove empty & unused function
* Fix variable init

== 1.1.19 ==

* Make sure $currentProgram gets initiated.
* Remove defunct statistics tab.
* Added debug output to show end of achievement list.
* Didn't terminate div & caused issues w/showing achievements (4th tab)
* Fix non-object warning when displaying username
* Fixes following JSHint run

== 1.1.18 ==

* Fix the unprivileged ajax call error info

== 1.1.17 ==

* Redirect to log-in URL if ajax call returns error code 3 (Not logged in)
* Add login_url constant for client checkin page
* Return error message to front end when user isn't logged in.
* Fix copyright notice in license
* Correct Text Domain

== 1.1.16 ==

* Fix formatting for datetimepicker() info in Client Message schedule fields
* Configure allowed times for the Client message scheduling datetimepicker() fields
* Add a group tempo setting of 'Varying'

== 1.1.15 ==

* get_user_by() calls to lower case 'id'
* Don't use deprecated 'id' when getting the id from the WP_User object
* Fix deprecated 'id' use for $current_user
* Use user_id when indicating the $currentClient's ID

== 1.1.14 ==

* Use received variable values when updating message history table, not globals
* Save program ID in $email_args array for use when updating message history.

== 1.1.13 ==

* Use $currentUser->id in place of $clientId (which could be empty?)
* Use 'ID' in get_user_by()

== 1.1.12 ==

* Attempt to fix array passing to send_email_to_client when scheduling message.
* Add help text for 'send on date/time'.
* Change type of input field for datetimepicker() field
* Fix loading datepicker() in back-end
* Add error handling to time conversion for entered schedule

== 1.1.11 ==

* Add hook for scheduled client messages by coaches
* Add datetimepicker jQuery-ui library
* Add CSS for datetimepicker functionality.
* Add support for scheduled messages to clients by a coach.
* Remove extra debug info
* Add scheduling of email message to coaching clients by the coach(es)
* Support scheduling messages to clients by the coaches.
* Use UTC when calculating auth timeout values (keep it consistent)
* Load change & click events after updating the program list using AJAX.
* Add bind function for member select dialogs
* Now filtering Coaching page's client info by program name & not membership level
* Add support for loading programs in an array (programID => program name)
* Add support for loading users belonging to the specified programID
* Handle special program IDs (-1= All, 0 = None)
* Load users for a program (not membership level) on coaching page.
* Fix label for drop-down when loading members belonging to the same program ID
* Fix typo in headline for Coaching page
* Use Wordpress timezone setting for last_login()
* Use currentUser->user_id rather than clientId
* Set timezone for auth cookie calculations

== 1.1.10 ==

* Would allow users to see all content in program(s) if their membership start date wasn't configured.
* Filter to set UNIX epoch timestamp for program start date ('e20r-tracker-program-start-timestamp')

== 1.1.9 ==

* Add language (i18n) info to plugin header.
* Add license text to e20r-tracker.php file.
* Add debug info for auth_timeout_reset() function Clean up login_timeout() function

== 1.1.8 ==

* Reload the updated message history after successfully sending a new message.
* Renaming 'Client Info'  to 'Coach's page'
* Left align heading on message history tab
* Add formatting for client message history tab on Coaching page
* Add e20r_client_messages table definition.
* Add client message history support for Coach's page.
* Record message information when sending from Coaching page
* Support fetching message history by AJAX call
* Show table of sent messages for a specific client.
* Record the fact that v10 of the DB adds the e20r_message_history table.
* Rename convert_postmeta_notice() to display_admin_notice().
* Add support for fetching message history from Coach interface
* Fix logic error in how manage_tables() works (to make sure it updates table structures if needed)
* Add e20r_client_messages table & add sender_id column
* Print status from dbDelta() in manage_tables().
* ClientModel - Record and retrieve messages sent from the Coach interface
* Add support for message history in Coach interface
* Typo in function name when configuring the currentProject in listUserAccomplishments().
* Add message history table to DB

== 1.1.7 ==

* Test whether the clientId variable was received. If not, try to load it from the page.
* Load client specific exercise statistics on /wp-admin/e20r-client-info page.

== 1.1.6 ==

* Move click event for Statistics button in Activity overview to apply to both front & back end.
* Fix last login warning for client data page in wp-admin/

== 1.1.5 ==

* Fix warning message (syntax was odd) Only show date when there's a last_login recorded.
* Actually display the last login info on the client detail page
* Fix buffering for admin interface.
* Correctly save the error notice option (don't sanitize the HTML)
* Clean up convert_postmeta_notice
* Remove debug info
* Show admin error notice if user who isn't a coach is attempting to access the client data.
* Add unbind to .on event.
* Fix ability to open/close activities in wp-admin
* Make activity set/group boxes flexible & aligned.

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