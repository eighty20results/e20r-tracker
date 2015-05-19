<?php
/**
 * The remote host file to process update requests
 *
 */
if ( !isset( $_POST['action'] ) ) {
	echo '0';
	exit;
}

//set up the properties common to both requests 
$obj = new stdClass();
$obj->slug = 'e20r-tracker.php';
$obj->name = 'Eighty/20 Tracker';
$obj->plugin_name = 'e20r-tracker.php';
$obj->new_version = '0.5.1-beta';
// the url for the plugin homepage
$obj->url = 'http://www.eighty20results.com/plugins/e20r-tracker';
//the download location for the plugin zip file (can be any internet host)
// $obj->package = 'http://eighty20results.s3.amazonaws.com/plugin/e20r-tracker.zip';
$obj->package = 'https://eighty20results.com/protected-downloads/e20r-tracker/e20r-tracker.zip';

switch ( $_POST['action'] ) {

case 'version':  
	echo serialize( $obj );
	break;  
case 'info':   
	$obj->requires = '4.2';
	$obj->tested = '4.2';
	$obj->downloaded = 12540;  
	$obj->last_updated = '2015-05-18';
	$obj->sections = array(  
		'description' => 'Fitness and habit tracking plugin',
		'another_section' => 'Create, access and track habit based programs',
		'changelog' => '0.5.1-beta: Use default values when no program has been defined for the user.'
	);
	$obj->download_link = $obj->package;  
	echo serialize($obj);  
case 'license':  
	echo serialize( $obj );  
	break;  
}  

?>
