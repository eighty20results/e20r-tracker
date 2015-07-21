=== E20R Tracker ===
Contributors: eighty20results
Tags: content management, fitness, nutrition coaching, tracking
Requires at least: 3.7
Requires PHP 5.2 or later.
Tested up to: 4.2.2
Stable tag: 0.8.19

A platform for managing nutrition and fitness coaching programs. Recommend using it in conjunction with the Paid Memberships Pro plugin and the PMPro Seuqences plugin.

== Description ==
The plugin is designed to coach clients through various change processes, whether they're behavioral, nutritional, fitness related, or otherwise.

We developed the plugin to meet our own specific coaching platform needs which revolved around a year long nutrition coaching approach.
During its development, we discovered a side-benefit which also allows us to manage an online personal training membership.

== ChangeLog ==

= 0.9.1 =
Removed console logging of various objects
Add "working" graphic when saving notes.
Set default measurement day to be Saturday (day #6)
Didn't attempt to list all defined mebership levels for Paid Memberships Pro
Make measurement day setting a program specific setting.

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