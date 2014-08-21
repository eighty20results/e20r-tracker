SET @userID = 27;

/* Calculate the per-recorded-habit compliance for a specific user ID */
SELECT
  a.habit_name AS Habit,
  a.created_by AS user_id,
  a.user_name AS username,
  COUNT(a.check_in_value) / b.TotalCount AS Compliance
FROM wp_s3f_nourishHabits AS a
  JOIN (
         SELECT
           COUNT(*) AS TotalCount,
           habit_name AS habit
         FROM wp_s3f_nourishHabits
         WHERE created_by = @userID
         GROUP BY habit_name) AS b
    ON a.habit_name = b.habit
WHERE created_by = @userID AND a.check_in_value = 'Yes'
GROUP BY habit_name;

/* Figure out the start date & end date for a habit by user */
SELECT
  MIN(submit_date) AS Startdate,
  created_by AS user_id,
  habit_name AS habit,
  DATEDIFF(MAX(submit_date), MIN(submit_date)) AS TotalDays
FROM wp_s3f_nourishHabits
WHERE created_by = @userID
GROUP BY habit_name
ORDER BY Startdate;

/* Total Compliance */
SET @userID = 20;
SELECT
  COUNT(check_in_count) / (
    SELECT DATEDIFF(NOW(), MIN(submit_date)) AS TotalDays
    FROM wp_s3f_nourishHabits
    WHERE created_by = @userID
  ) AS Compliance
FROM wp_s3f_nourishHabits
WHERE created_by = @userID AND check_in_value = 'Yes';

/* Calculate Start & Stop dates for habit(s) */
SELECT
  MIN(submit_date) AS startdate,
  DATE_ADD(MIN(submit_date), INTERVAL 2 WEEK) AS SupposedEnd,
  MAX(submit_date) AS lastEnteredDate,
  habit_name AS Habit
FROM wp_s3f_nourishHabits
GROUP BY habit_name
ORDER BY startdate;




SET @userID = 27;

/* Calculate the per-recorded-habit compliance for a specific user ID */
SELECT
  a.habit_name AS Habit,
  a.created_by AS user_id,
  a.user_name AS username,
  COUNT(a.check_in_value) / b.TotalCount AS Compliance
FROM wp_s3f_nourishHabits AS a
  JOIN (
         SELECT
           COUNT(*) AS TotalCount,
           habit_name AS habit
         FROM wp_s3f_nourishHabits
         WHERE created_by = @userID
         GROUP BY habit_name) AS b
    ON a.habit_name = b.habit
WHERE created_by = @userID AND a.check_in_value = 'Yes'
GROUP BY habit_name;

/* Figure out the start date & end date for a habit by user */
SELECT
  MIN(submit_date) AS Startdate,
  created_by AS user_id,
  habit_name AS habit,
  DATEDIFF(MAX(submit_date), MIN(submit_date)) AS TotalDays
FROM wp_s3f_nourishHabits
WHERE created_by = @userID
GROUP BY habit_name
ORDER BY Startdate;

/* Total Compliance */
SET @userID = 20;
SELECT
  COUNT(check_in_count) / (
    SELECT DATEDIFF(NOW(), MIN(submit_date)) AS TotalDays
    FROM wp_s3f_nourishHabits
    WHERE created_by = @userID
  ) AS Compliance
FROM wp_s3f_nourishHabits
WHERE created_by = @userID AND check_in_value = 'Yes';

/* Start and stop dates for habits */
SELECT
  MIN(submit_date) AS startdate,
  DATE_ADD(MIN(submit_date), INTERVAL 2 WEEK) AS SupposedEnd,
  MAX(submit_date) AS lastEnteredDate,
  habit_name AS Habit
FROM wp_s3f_nourishHabits
GROUP BY habit_name
ORDER BY startdate;

/* */
SELECT
  COUNT(created_by) AS mealCount,
  habit_name AS Habit,
  created_by AS user_id,
  user_name AS username,
  submit_date AS saved
FROM wp_wp_s3f_nourishMeals
GROUP BY created_by;

SELECT
  MIN(submit_date) AS startRecording,
  MAX(submit_date) AS endRecording,
  DATE_ADD(MIN(submit_date), INTERVAL 2 WEEK) AS supposedEnd,
  DATEDIFF(MAX(submit_date), MIN(submit_date)) AS DaysRecorded,
  COUNT(habit_day) AS TotalMeals,
  created_by AS user_id,
  user_name AS username,
  habit_name AS habit
FROM wp_wp_s3f_nourishMeals
GROUP BY user_name;

/* Calculate number of meals per day */
SELECT (m1.counted + m2.counted + m3.counted + m4.counted + m5.counted + m6.counted + m7.counted) AS dailyMealCnt,
       a.created_by AS user_id,
       a.user_name AS username
FROM wp_wp_s3f_nourishMeals AS a
  INNER JOIN (
               SELECT COUNT(meal1_descr) AS counted, created_by FROM wp_wp_s3f_nourishMeals WHERE meal1_descr != '' AND created_by = @userID
             ) AS m1
    ON m1.created_by = a.created_by
  INNER JOIN (
               SELECT COUNT(meal2_descr) AS counted, created_by FROM wp_wp_s3f_nourishMeals WHERE meal2_descr != '' AND created_by = @userID
             ) AS m2
    ON m2.created_by = a.created_by
  INNER JOIN (
               SELECT COUNT(meal3_descr) AS counted, created_by FROM wp_wp_s3f_nourishMeals WHERE meal3_descr != '' AND created_by = @userID
             ) AS m3
    ON m3.created_by = a.created_by
  INNER JOIN (
               SELECT COUNT(meal4_descr) AS counted, created_by FROM wp_wp_s3f_nourishMeals WHERE meal4_descr != '' AND created_by = @userID
             ) AS m4
    ON m4.created_by = a.created_by
  INNER JOIN (
               SELECT COUNT(meal5_descr) AS counted, created_by FROM wp_wp_s3f_nourishMeals WHERE meal5_descr != '' AND created_by = @userID
             ) AS m5
    ON m5.created_by = a.created_by
  INNER JOIN (
               SELECT COUNT(meal6_descr) AS counted, created_by FROM wp_wp_s3f_nourishMeals WHERE meal6_descr != '' AND created_by = @userID
             ) AS m6
    ON m6.created_by = a.created_by
  INNER JOIN (
               SELECT COUNT(meal7_descr) AS counted, created_by FROM wp_wp_s3f_nourishMeals WHERE meal7_descr != '' AND created_by = @userID
             ) AS m7
    ON m7.created_by = a.created_by
;