<?php
/**
 * The E20R Tracker Plugin – a coaching client management plugin for WordPress. Tracks client training, habits,
 * educational reminders, etc. Copyright (c) 2018, Wicked Strong Chicks, LLC
 *
 * The E20R Tracker Plugin is free software: you can redistribute it and/or modify it under the terms of the GNU
 * General Public License as published by the Free Software Foundation, either version 2 of the License or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <http://www.gnu.org/licenses/>
 *
 * You can contact us at info@eighty20results.com
 *
 *
 */

namespace E20R\Tracker\Controllers;

use E20R\Utilities\Utilities;

/**
 * Class Media_Library
 * @package E20R\Tracker\Controllers
 *
 * @since 3.0 - ENHANCEMENT: Refactored media library management to own class (Media_Library)
 */
class Media_Library {
	
	/**
	 * @var null|Media_Library
	 */
	static private $instance = null;
	
	/**
	 * Media_Library constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * Loading hooks for the Media Library (as needed)
	 */
	public function loadHooks() {
		
		
		Utilities::get_instance()->log( "Loading filter to change the upload directory for Nourish clients" );
		add_filter( 'media_view_strings', array( $this, 'clientMediaUploader' ), 10 );
		
		Utilities::get_instance()->log( "Loaded filter to change the Media Library settings for client uploads" );
		add_filter( "wp_handle_upload_prefilter", array( $this, "pre_upload" ) );
		add_filter( "wp_handle_upload", array( $this, "post_upload" ) );
		Utilities::get_instance()->log( "Loaded filter to change the upload directory for Nourish clients" );
		
		Utilities::get_instance()->log( "Control Access to media uploader for Tracker users" );
		
		/* Control access to the media uploader for Nourish users */
		add_action( 'pre_get_posts', array( $this, 'restrict_media_library' ) );
	}
	
	/**
	 * Get or instantiate the Media_Library class
	 *
	 * @return Media_Library|null;
	 */
	static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Add the custom upload dir filter handler when on the right page(s)
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	public function pre_upload( $file ) {
		
		Utilities::get_instance()->log( "Set upload directory path for the progress photos." );
		add_filter( 'upload_dir', array( &$this, "e20r_set_upload_dir" ) );
		Utilities::get_instance()->log( "New upload directory path for the progress photos has been configured." );
		
		return $file;
	}
	
	/**
	 * Remove the custom upload directory destination
	 *
	 * @param mixed $fileinfo
	 *
	 * @return mixed
	 */
	public function post_upload( $fileinfo ) {
		
		Utilities::get_instance()->log( "Removing upload directory path hook." );
		remove_filter( "upload_dir", array( $this, "e20r_set_upload_dir" ) );
		
		return $fileinfo;
	}
	
	/**
	 * Configure the upload directory for members when using the Tracker
	 *
	 * @param array $param
	 *
	 * @return array
	 */
	public function e20r_set_upload_dir( $param ) {
		
		$Client = Client::getInstance();
		global $currentClient;
		global $current_user;
		global $post;
		
		Utilities::get_instance()->log( "Do we need to modify the upload directory when processing post {$post->ID}?" );
		
		if ( ! class_exists( '\E20R\Tracker\Controller\Client' ) ) {
			
			Utilities::get_instance()->log( "No client class defined?!??" );
			
			return $param;
		}
		
		if ( ! isset( $post->ID ) ) {
			
			Utilities::get_instance()->log( "No page ID defined..." );
			
			return $param;
		}
		
		
		if ( ! $Client->client_loaded ) {
			
			Utilities::get_instance()->log( "Need to load the Client information/settings..." );
			Utilities::get_instance()->log( "Set ID for client info" );
			$Client->setClient( $current_user->ID );
			Utilities::get_instance()->log( "Load default Client info" );
			$Client->init();
		}
		
		Utilities::get_instance()->log( "Fetching the upload path for client ID: " . $currentClient->user_id );
		$path = $Client->getClientDataField( $currentClient->user_id, 'program_photo_dir' );
		
		$param['path'] = $param['basedir'] . "/{$path}";
		$param['url']  = $param['baseurl'] . "/{$path}";
		
		Utilities::get_instance()->log( "Directory: {$param['path']}" );
		
		return $param;
	}
	
	/**
	 * Don't allow non-authors (or higher) edit the media library
	 *
	 * @param \WP_Query $wp_query_obj
	 */
	public function restrict_media_library( $wp_query_obj ) {
		
		global $current_user, $pagenow;
		
		Utilities::get_instance()->log( "Check whether to restrict access to the media library..." );
		
		if ( ! is_a( $current_user, '\WP_User' ) ) {
			Utilities::get_instance()->log('Current user is not a WP_User object!');
			return;
		}
		
		if ( 'admin-ajax.php' != $pagenow || $_REQUEST['action'] != 'query-attachments' ) {
			Utilities::get_instance()->log('Not processing ajax and querying attachments');
			return;
		}
		
		if ( ! current_user_can( 'manage_media_library' ) ) {
			
			Utilities::get_instance()->log( "User {$current_user->ID} is an author or better and has access to managing the media library" );
			$wp_query_obj->set( 'author', $current_user->ID );
		}
		
		return;
	}
	
	/**
	 * Potentially update the media tab type
	 *
	 * @param string $tabName
	 *
	 * @return mixed
	 */
	public function default_media_tab( $tabName ) {
		
		if ( isset( $_REQUEST['post_id'] ) && ! empty( $_REQUEST['post_id'] ) ) {
			$post_type = get_post_type( absint( $_REQUEST['post_id'] ) );
			
			if ( $post_type ) {
				if ( 'page' == $post_type ) {
					Utilities::get_instance()->log( "Tracker::default_media_tab: {$tabName}" );
					// return 'type';
				}
			}
		}
		
		return $tabName;
	}
	
	/**
	 * Remove tabs from the Media Uploader when a regular user (aka a client) is attempting to access it
	 *
	 * @param array $strings
	 *
	 * @return array
	 */
	public function clientMediaUploader( $strings ) {
		
		Utilities::get_instance()->log( "Do we remove tab(s) from the media uploader?" );
		
		if ( ! is_user_logged_in() ) {
			return $strings;
		}
		
		if ( current_user_can( 'edit_posts' ) ) {
			
			Utilities::get_instance()->log( "User is an administrator/contributor so don't remove anything." );
			
			return $strings;
		}
		
		Utilities::get_instance()->log( "Regular user." );
		
		unset( $strings['mediaLibraryTitle'] ); //Media Library
		unset( $strings['createGalleryTitle'] ); //Create Gallery
		unset( $strings['setFeaturedImageTitle'] ); //Set Featured Image
		
		unset( $strings['insertFromUrlTitle'] ); //Insert from URL
		
		return $strings;
	}
	
	/**
	 * Set the expected file name for the upload (when the client is uploading images)
	 *
	 * @param array $file
	 *
	 * @return array
	 */
	public function setFilenameForClientUpload( $file ) {
		
		global $current_user;
		
		$Program      = Program::getInstance();
		$Measurements = Measurements::getInstance();
		
		$imageFormats = array(
			'image/bmp',
			'image/gif',
			'image/jpeg',
			'image/png',
			'image/tiff',
		);
		
		Utilities::get_instance()->log( "Data: " . print_r( $file, true ) );
		Utilities::get_instance()->log( "Request: " . print_r( $_REQUEST, true)  );
		
		/* Skip non-image uploads. */
		if ( ! in_array( $file['type'], $imageFormats ) ) {
			return $file;
		}
		
		$user_id = $Measurements->getUserId();
		
		if ( ( $user_id == 0 ) || ( $user_id === null ) ) {
			
			Utilities::get_instance()->log( "No Client ID available..." );
			$user_id = get_current_user_id();
		}
		
		if ( ! is_a( $current_user, '\WP_User' ) ) {
			Utilities::get_instance()->log( "Not a user" );
			
			return $file;
		}
		
		$pgmId = $Program->getProgramIdForUser( $user_id );
		
		Utilities::get_instance()->log( "Filename was: {$file['name']}" );
		$timestamp = date( "Ymd", current_time( 'timestamp' ) );
		$side      = 'REPLACEME';
		
		// $fileName[0] = name, $fileName[(count($fileName)] = Extension
		$fileName = explode( '.', $file['name'] );
		$ext      = $fileName[ ( count( $fileName ) - 1 ) ];
		
		Utilities::get_instance()->log( print_r( $_FILES, true ) );
		
		$file['name'] = "{$pgmId}-{$user_id}-{$timestamp}-{$side}.{$ext}";
		Utilities::get_instance()->log( "New filename: {$file['name']}" );
		
		$img = getimagesize( $file['tmp_name'] );
		
		$minimum = array( 'width' => '1280', 'height' => '1024' );
		
		$width  = $img[0];
		$height = $img[1];
		
		if ( $width < $minimum['width'] ) {
			
			return array( "error" => "Image dimensions are too small. Minimum width is {$minimum['width']}px. Uploaded image width is $width px" );
		} else if ( $height < $minimum['height'] ) {
			
			return array( "error" => "Image dimensions are too small. Minimum height is {$minimum['height']}px. Uploaded image height is $height px" );
		}
		
		return $file;
	}
	
	/**
	 * Allow user to upload to the(ir) media library
	 *
	 * @param \WP_Query $wp_query
	 */
	public function current_user_only( $wp_query ) {
		
		if ( strpos( $_SERVER['REQUEST_URI'], 'wp-admin/upload.php' )
		     || strpos( $_SERVER['REQUETST_URI'], 'wp-admin/edit.php' )
		     || strpos( $_SERVER['REQUEST_URI'], 'wp-admin/ajax-upload.php' ) ) {
			
			Utilities::get_instance()->log( "Media_Library::current_user_only - Uploading files..." );
			
			if ( current_user_can( 'upload_files' ) ) {
				global $current_user;
				$wp_query->set( 'author', $current_user->ID );
			}
		}
	}
	
	/*
	public function media_view_settings( $settings, $post ) {
	
		global $e20rMeasurementDate, $Tracker;
	
	//        unset( $settings['mimeTypes']['audio'] );
	//        unset( $settings['mimeTypes']['video'] );
	
		Utilities::get_instance()->log("Measurement date: {$e20rMeasurementDate}");
	
		$monthYear = date('F Y',strtotime( $e20rMeasurementDate ) );
		$settings['currentMonth'] = $monthYear;
	
		foreach ( $settings['months'] as $key => $month ) {
	
			if ( $month->text != $monthYear ) {
				unset( $settings['months'][$key]);
			}
		}
	
		Utilities::get_instance()->log("Now using: ");
		Utilities::get_instance()->log($settings);
	
		return $settings;
	}
	*/
}