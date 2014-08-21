<?php
global $wpdb;

$cItems = $wpdb->prefix . 'e20r_checkinItems';
$cRules = $wpdb->prefix . 'e20r_checkinRules';
$cTable = $wpdb->prefix . 'e20r_checkin';

$charset_collate = '';

if ( ! empty( $wpdb->charset ) ) {
  $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
}

if ( ! empty( $wpdb->collate ) ) {
  $charset_collate .= " COLLATE {$wpdb->collate}";
}
?>

CREATE TABLE IF NOT EXISTS {$cItems} (
    id int not null auto_increment,
    shortname varchar(20) null,
    program_id int null,
    itemname varchar(50) null,
    startdate datetime null,
    enddate datetime null,
    item_order int not null default 1,
    maxcount int null,
    membership_level_id int null,
primary key  (id) ,
unique key shortname_UNIQUE (shortname asc) )
{$charset_collate};

CREATE TABLE IF NOT EXISTS {$cRules} (
    id int not null auto_increment,
    checkin_id int null,
    success_rule mediumtext null,
    primary key  (id),
    key checkin_id (checkin_id asc) )
{$charset_collate};

CREATE TABLE IF NOT EXISTS {$cTable} (
    id int not null auto_increment,
    user_id int null,
    checkin_date datetime null,
    checkin_id int null,
    program_id int null,
    primary key  (id) )
{$charset_collate};
