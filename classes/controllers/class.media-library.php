<?php
/**
 * The E20R Tracker Plugin – a coaching client management plugin for WordPress. Tracks client training, habits, educational reminders, etc.
 * Copyright (c) 2018, Wicked Strong Chicks, LLC
 *
 * The E20R Tracker Plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 * You can contact us at info@eighty20results.com
 *
 *
 */

namespace E20R\Tracker\Controllers;


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
	 * @return Media_Library|null;
	 */
	static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Loading hooks for the Media Library (as needed)
	 */
	public function loadHooks() {
	
	}
	
	public function pre_upload( $file ) {
		
		E20R_Tracker::dbg("Tracker::pre_upload() -- Set upload directory path for the progress photos.");
		add_filter( 'upload_dir', array( &$this, "e20r_set_upload_dir" ) );
		E20R_Tracker::dbg("Tracker::pre_upload() -- New upload directory path for the progress photos has been configured.");
		
		return $file;
	}
	
	public function post_upload( $fileinfo ) {
		
		E20R_Tracker::dbg("Tracker::post_upload() -- Removing upload directory path hook.");
		remove_filter( "upload_dir", array( $this, "e20r_set_upload_dir") );
		return $fileinfo;
	}
	
	public function e20r_set_upload_dir( $param ) {
		
		$Client = Client::getInstance();
		global $current_user;
		global $post;
		
		E20R_Tracker::dbg("Tracker::set_progress_upload_dir() - Do we need to modify the upload directory?");
		E20R_Tracker::dbg("Post ID: {$post->ID}" );
		/*
				if ( ! class_exists( "\E20R\Tracker\Controller\Client" ) ) {

					E20R_Tracker::dbg("Tracker::set_progress_upload_dir() - No client class defined?!??");
					return $param;
				}

				if ( ! isset( $post->ID ) ) {

					E20R_Tracker::dbg("Tracker::set_progress_upload_dir() - No page ID defined...");
					return $param;
				}


				if ( ! $Client->client_loaded ) {

					E20R_Tracker::dbg("Tracker::set_progress_upload_dir() - Need to load the Client information/settings...");
					E20R_Tracker::dbg("Tracker::set_progress_upload_dir() - Set ID for client info");
					$Client->setClient( $current_user->ID );
					E20R_Tracker::dbg("Tracker::set_progress_upload_dir() - Load default Client info");
					$Client->init();
				}

				E20R_Tracker::dbg("Tracker::set_progress_upload_dir() - Fetching the upload path for client ID: " . $Client->clientId());
				$path = $Client->getUploadPath( $Client->clientId() );

				$param['path'] = $param['basedir'] . "/{$path}";
				$param['url'] = $param['baseurl'] . "/{$path}";
				*/
		E20R_Tracker::dbg("Tracker::set_progress_upload_dir() - Directory: {$param['path']}");
		return $param;
	}
	
	/**
	 * @param \WP_Query $wp_query_obj
	 */
	public function restrict_media_library( $wp_query_obj) {
		
		global $current_user, $pagenow;
		
		E20R_Tracker::dbg("Tracker::restrict_media_library() - Check whether to restrict access to the media library...");
		
		if ( !is_a( $current_user, "\\WP_User") ) {
			
			return;
		}
		
		if( 'admin-ajax.php' != $pagenow || $_REQUEST['action'] != 'query-attachments' ) {
			return;
		}
		
		if( ! current_user_can( 'manage_media_library') ) {
			
			E20R_Tracker::dbg("Tracker::restrict_media_library() - User {$current_user->ID} is an author or better and has access to managing the media library");
			$wp_query_obj->set( 'author', $current_user->ID );
		}
		
		return;
	}
	
	public function default_media_tab( $tabName ) {
		
		if ( isset( $_REQUEST['post_id'] ) && ! empty( $_REQUEST['post_id'] ) ) {
			$post_type = get_post_type( absint( $_REQUEST['post_id'] ) );
			
			if ( $post_type ) {
				if ( 'page' == $post_type ) {
					E20R_Tracker::dbg("Tracker::default_media_tab: {$tabName}");
					// return 'type';
				}
			}
		}
		
		return $tabName;
	}
	
	public function clientMediaUploader( $strings ) {
		
		E20R_Tracker::dbg("Measurements::clientMediaUploader() -- Do we remove tab(s) from teh media uploader?");
		
		if ( current_user_can('edit_posts') ) {
			
			E20R_Tracker::dbg("Measurements::clientMediaUploader() -- User is an administrator so don't remove anything.");
			return $strings;
		}
		
		E20R_Tracker::dbg("Measurements::clientMediaUploader() -- Regular user.");
		unset( $strings['mediaLibraryTitle'] ); //Media Library
		unset( $strings['createGalleryTitle'] ); //Create Gallery
		unset( $strings['setFeaturedImageTitle'] ); //Set Featured Image
		
		unset( $strings['insertFromUrlTitle'] ); //Insert from URL
		
		return $strings;
	}
	
	public function setFilenameForClientUpload( $file ) {
		
		global $current_user;
		
		$Program = Program::getInstance();
		$Measurements = Measurements::getInstance();
		
		$imageFormats = array(
			'image/bmp',
			'image/gif',
			'image/jpeg',
			'image/png',
			'image/tiff'
		);
		
		E20R_Tracker::dbg("Measurements::setFilenameForClientUpload() - Data: ");
		E20R_Tracker::dbg( $file );
		E20R_Tracker::dbg( $_REQUEST );
		
		/* Skip non-image uploads. */
		if ( ! in_array( $file['type'], $imageFormats ) ) {
			return $file;
		}
		
		$user_id = $Measurements->getUserId();
		
		if ( ( $user_id == 0 ) || ( $user_id === null ) ) {
			
			E20R_Tracker::dbg("Measurements::setFilenameForClientUpload() - No Client ID available...");
			$user_id = get_current_user_id();
		}
		
		if ( !is_a( $current_user, "\WP_User") ) {
			E20R_Tracker::dbg("Measurements::setFilenameForClientUpload() - Not a user");
			return $file;
		}
		
		$pgmId = $Program->getProgramIdForUser( $user_id );
		
		E20R_Tracker::dbg( "Measurements::setFilenameForClientUpload() - Filename was: {$file['name']}" );
		$timestamp = date( "Ymd", current_time( 'timestamp' ) );
		$side      = 'REPLACEME';
		
		// $fileName[0] = name, $fileName[(count($fileName)] = Extension
		$fileName = explode( '.', $file['name'] );
		$ext      = $fileName[ ( count( $fileName ) - 1 ) ];
		
		E20R_Tracker::dbg( "Measurements::setFilenameForClientUpload(): " );
		E20R_Tracker::dbg( $_FILES );
		
		$file['name'] = "{$pgmId}-{$user_id}-{$timestamp}-{$side}.{$ext}";
		E20R_Tracker::dbg( "Measurements::setFilenameForClientUpload() - New filename: {$file['name']}" );
		
		$img = getimagesize( $file['tmp_name'] );
		
		$minimum = array('width' => '1280', 'height' => '1024');
		
		$width= $img[0];
		$height =$img[1];
		
		if ($width < $minimum['width'] ) {
			
			return array( "error" => "Image dimensions are too small. Minimum width is {$minimum['width']}px. Uploaded image width is $width px" );
		}
		elseif ($height <  $minimum['height']) {
			
			return array( "error" => "Image dimensions are too small. Minimum height is {$minimum['height']}px. Uploaded image height is $height px" );
		}
		
		return $file;
	}
	
	public function current_user_only( $wp_query ) {
		
		if ( strpos( $_SERVER[ 'REQUEST_URI' ], 'wp-admin/upload.php' )
		     || strpos( $_SERVER[ 'REQUETST_URI' ], 'wp-admin/edit.php' )
		     || strpos( $_SERVER[ 'REQUEST_URI' ], 'wp-admin/ajax-upload.php' ) ) {
			
			E20R_Tracker::dbg("Media_Library::current_user_only - Uploading files...");
			
			if ( current_user_can( 'upload_files' )) {
				global $current_user;
				$wp_query->set( 'author', $current_user->ID);
			}
		}
	}
	/*
    public function media_view_settings( $settings, $post ) {

        global $e20rMeasurementDate, $Tracker;

//        unset( $settings['mimeTypes']['audio'] );
//        unset( $settings['mimeTypes']['video'] );

        E20R_Tracker::dbg("Tracker::media_view_settings() - Measurement date: {$e20rMeasurementDate}");

        $monthYear = date('F Y',strtotime( $e20rMeasurementDate ) );
        $settings['currentMonth'] = $monthYear;

        foreach ( $settings['months'] as $key => $month ) {

            if ( $month->text != $monthYear ) {
                unset( $settings['months'][$key]);
            }
        }

        E20R_Tracker::dbg("Tracker::media_view_settings() - Now using: ");
        E20R_Tracker::dbg($settings);

        return $settings;
    }
*/
}