<?php
/**
 * Copyright (c) 2018 - Eighty / 20 Results by Wicked Strong Chicks.
 * ALL RIGHTS RESERVED
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace E20R\Tracker\Models;

use E20R\Tracker\Controllers\Article;
use E20R\Tracker\Controllers\Client;
use E20R\Tracker\Controllers\Tracker_Access;
use E20R\Tracker\Controllers\Tracker_Crypto;
use E20R\Utilities\Utilities;
use E20R\Tracker\Controllers\Tracker;
use E20R\Tracker\Controllers\Measurements;
use E20R\Tracker\Controllers\Program;

class GF_Integration {
	
	/**
	 * @var null|GF_Integration
	 */
	private static $instance = null;
	
	/**
	 * GF_Integration constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * Instantiate or return the GF_Integration class
	 *
	 * @return GF_Integration|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Load Gravity Forms related hooks
	 */
	public function loadHooks() {
		
		/* Gravity Forms data capture for Check-Ins, Assignments, Surveys, etc */
		add_action( 'gform_after_submission', array( $this, 'save_interview' ), 10, 2 );
		add_filter( 'gform_pre_render', array( $this, 'load_interview' ), 10, 1 );
		add_filter( 'gform_field_value', array( $this, 'process_gf_fields' ), 10, 3 );
		
		//add_action( 'gform_entry_post_save', array( Client::getInstance(), 'save_interview'), 10, 2);
		//add_filter( 'gform_pre_validation', array( Client::getInstance(), 'load_interview' ) );
		//add_filter( 'gform_admin_pre_render', array( Client::getInstance(), 'load_interview' ) );
		//add_filter( 'gform_pre_submission_filter', array( Client::getInstance(), 'load_interview' ) );
		//add_filter( 'gform_confirmation', array( &$this, 'gravity_form_confirmation') , 10, 4 );
	}
	
	/**
	 * Save the interview (form) data to our DB (encrypted)
	 *
	 * @param mixed $entry -- Survey entry (gravity forms entry object)
	 * @param mixed $form  -- Form object
	 *
	 * @return bool -- True/False
	 */
	public function save_interview( $entry, $form ) {
		
		$Access = Tracker_Access::getInstance();
		
		global $current_user;
		global $post;
		
		Utilities::get_instance()->log( "Start" );
		
		if ( false === stripos( $form['cssClass'], 'nourish-interview-identifier' ) ) {
			
			Utilities::get_instance()->log( 'Not the BitBetter Interview form: ' . $form['cssClass'] );
			
			return false;
		}
		
		if ( ! is_user_logged_in() ) {
			
			Utilities::get_instance()->log( "User accessing form without being logged in." );
			
			return false;
		}
		
		if ( ! $Access->hasAccess( $current_user->ID, $post->ID ) ) {
			
			Utilities::get_instance()->log( "User does NOT have access to this form." );
			
			return false;
		}
		
		Utilities::get_instance()->log( "Processing the Bit Better Interview form(s)." );
		
		$Measurements = Measurements::getInstance();
		$Program      = Program::getInstance();
		$Article      = Article::getInstance();
		
		global $page;
		global $current_user;
		global $currentProgram;
		global $currentArticle;
		
		$userId           = $current_user->ID;
		$userProgramId    = ! empty( $currentProgram->id ) ? $currentProgram->id : $Program->getProgramIdForUser( $userId );
		$userProgramStart = $currentProgram->startdate;
		$surveyArticle    = $Article->findArticles( 'post_id', $currentProgram->intake_form, $userProgramId );
		
		// Utilities::get_instance()->log($currentProgram);
		Utilities::get_instance()->log( $surveyArticle );
		
		$db_Data = array(
			'user_id'            => $userId,
			'program_id'         => $currentProgram->id,
			'page_id'            => ( isset( $page->ID ) ? $page->ID : CONST_NULL_ARTICLE ),
			// 'article_id' => $currentArticle->id,
			'article_id'         => $surveyArticle[0]->id,
			'program_start'      => $currentProgram->startdate,
			'user_enc_key'       => null,
			'progress_photo_dir' => "e20r_pics/client_{$userProgramId}_{$userId}",
		);
		
		$fieldList = array( 'text', 'textarea', 'number', 'email', 'phone' );
		
		Utilities::get_instance()->log( "Processing the Welcome Interview form" );
		/*        Utilities::get_instance()->log($form['fields']);
                Utilities::get_instance()->log($entry);
        */
		foreach ( $form['fields'] as $item ) {
			
			$skip = true;
			
			Utilities::get_instance()->log( "Processing field type: {$item['type']}" );
			
			if ( 'section' != $item['type'] ) {
				
				$fieldName = $item['label'];
				$subm_key  = $item['id'];
				
				if ( in_array( $item['type'], $fieldList ) ) {
					
					$skip = false;
				}
				
				if ( $item['type'] == 'date' ) {
					
					$skip                  = true;
					$db_Data[ $fieldName ] = date( 'Y-m-d', strtotime( $this->filterResponse( $entry[ $item['id'] ] ) ) );
				}
				
				if ( $item['type'] == 'checkbox' ) {
					
					$checked = array();
					
					foreach ( $item['inputs'] as $k => $i ) {
						
						if ( ! empty( $entry[ $i['id'] ] ) ) {
							
							$checked[] = $this->filterResponse( $item['choices'][ $k ]['value'] );
						}
					}
					
					if ( ! empty( $checked ) ) {
						
						$db_Data[ $fieldName ] = join( ';', $checked );
						
					}
					
					$skip = true;
				}
				
				if ( ( $fieldName == 'calculated_weight_lbs' ) && ( ! empty( $entry[ $item['id'] ] ) ) ) {
					
					Utilities::get_instance()->log( "Saving weight as LBS..." );
					$skip = false;
					$Measurements->saveMeasurement( 'weight', $entry[ $item['id'] ], - 1, $userProgramId, $userProgramStart, $userId );
				}
				
				if ( ( $fieldName == 'calculated_weight_kg' ) && ( ! empty( $entry[ $item['id'] ] ) ) ) {
					
					Utilities::get_instance()->log( "Saving weight as KG" );
					$skip = false;
					$Measurements->saveMeasurement( 'weight', $entry[ $item['id'] ], - 1, $userProgramId, $userProgramStart, $userId );
				}
				
				if ( $item['type'] == 'survey' ) {
					
					$key      = $entry[ $item['id'] ];
					$subm_key = $item['id'];
					
					if ( $item['inputType'] == 'likert' ) {
						
						foreach ( $item['choices'] as $k => $i ) {
							
							foreach ( $i as $lk => $val ) {
								
								if ( $entry[ $subm_key ] == $item['choices'][ $k ]['value'] ) {
									
									$entry[ $subm_key ] = $item['choices'][ $k ]['score'];
									$skip               = false;
								}
							}
						}
					}
				}
				
				if ( $item['type'] == 'address' ) {
					
					Utilities::get_instance()->log( "Saving address information: " );
					// Utilities::get_instance()->log($entry);
					
					// $key = $item['id'];
					
					foreach ( $item['inputs'] as $k => $i ) {
						
						$key = $i['id'];
						$val = $entry["{$key}"];
						
						$splt = preg_split( "/\./", $key );
						
						switch ( $splt[1] ) {
							case '1':
								$fieldName = 'address_1';
								break;
							
							case '2':
								$fieldName = 'address_2';
								break;
							
							case '3':
								$fieldName = 'address_city';
								break;
							
							case '4':
								$fieldName = 'address_state';
								break;
							
							case '5':
								$fieldName = 'address_zip';
								break;
							
							case '6':
								$fieldName = 'address_country';
								break;
						}
						
						Utilities::get_instance()->log( "Field: {$fieldName}, Item #: {$key} -> Value: {$val}" );
						
						// Utilities::get_instance()->log($entry[ {$i['id']} ]);
						
						
						if ( ! empty( $val ) ) {
							
							Utilities::get_instance()->log( "Saving address item {$fieldName} -> {$val}" );
							$db_Data[ $fieldName ] = $this->filterResponse( $val );
						}
						
					}
					
					$skip = true;
				}
				
				if ( $item['type'] == 'multiselect' ) {
					
					Utilities::get_instance()->log( "Processing MultiSelect" );
					
					if ( isset( $entry[ $subm_key ] ) ) {

//                        $selections = explode(",", $entry[$subm_key]);
//                        Utilities::get_instance()->log( $selections );
						
						Utilities::get_instance()->log( "Multiselect - Field: {$fieldName}, subm_key={$subm_key}, entryVal={$entry[$subm_key]}, item={$item['choices'][$k]['value']}" );
						$db_Data[ $fieldName ] = $this->filterResponse( $entry[ $subm_key ] );
					}
				}
				
				if ( $item['type'] == 'select' ) {
					
					if ( ! empty( $entry[ $subm_key ] ) ) {
						
						foreach ( $item['choices'] as $k => $v ) {
							
							Utilities::get_instance()->log( "Select item: Field: {$fieldName}, subm_key={$subm_key}, entryVal={$entry[$subm_key]}, item={$item['choices'][$k]['value']}" );
							
							if ( $item['choices'][ $k ]['value'] == $entry[ $subm_key ] ) {
								
								$db_Data[ $fieldName ] = $this->filterResponse( $item['choices'][ $k ]['value'] );
							}
						}
					}
				}
				
				if ( $item['type'] == 'radio' ) {
					
					if ( ! empty( $entry[ $subm_key ] ) ) {
						
						// Handle cases where Yes/No fields have 0/1 values.
						Utilities::get_instance()->log( "Processing numeric radio button value: {$entry[$subm_key]}" );
						
						foreach ( $item['choices'] as $k => $v ) {
							
							// Utilities::get_instance()->log($item['choices'][$k]);
							
							if ( $item['choices'][ $k ]['value'] == $entry[ $subm_key ] ) {
								
								Utilities::get_instance()->log( "Processing radio button: Value: {$item['choices'][ $k ]['value']}" );
								$db_Data[ $fieldName ] = $this->filterResponse( $item['choices'][ $k ]['value'] );
								$skip                  = true;
							}
						}
					}
				}
				
				if ( ! $skip ) {
					
					if ( empty( $entry[ $subm_key ] ) ) {
						
						continue;
					}
					
					$data = trim( $entry[ $subm_key ] );
					Utilities::get_instance()->log( "Data being stored: .{$data}." );
					
					if ( 'textarea' == $item['type'] ) {
						
						$data = wp_kses_post( $data );
					}
					
					// Encrypt the data.
					$encData               = $this->filterResponse( $data );
					$db_Data[ $fieldName ] = $encData;
				} else {
					Utilities::get_instance()->log( "Skipped field of type: {$item['type']} and with value: " . ( isset( $entry[ $item['id'] ] ) ? $entry[ $item['id'] ] : 'null' ) );
				}
			}
		} // End of foreach loop for submitted form
		
		if ( WP_DEBUG ) {
			Utilities::get_instance()->log( "Data to save: " );
			Utilities::get_instance()->log( $db_Data );
		}
		
		Utilities::get_instance()->log( "Assigning a coach for this user " );
		$db_Data['coach_id'] = Client::getInstance()->assign_coach( $userId, $db_Data['gender'] );
		
		Utilities::get_instance()->log( "Configure the exercise level for this user" );
		Client::getInstance()->assign_exercise_level( $userId, $db_Data );
		
		Utilities::get_instance()->log( "Saving the client interview data to the DB. " );
		
		if ( true === Client::getInstance()->saveInterview( $db_Data, $entry ) ) {
			
			Utilities::get_instance()->log( "Saved data to the database " );
			Utilities::get_instance()->log( "Removing any GF entry data from database." );
			
			return $this->remove_survey_form_entry( $entry );
		}
		
		return false;
	}
	
	/**
	 * Convert some Gravity Form field values to expected values for Tracker
	 *
	 * @param string $data
	 *
	 * @return int|string
	 */
	private function filterResponse( $data ) {
		
		switch ( $data ) {
			
			case 'Yes':
				$data = 1;
				break;
			
			case 'No':
				$data = 0;
				break;
			
			case 'M':
				$data = 'm';
				break;
			
			case 'F':
				$data = 'f';
				break;
		}
		
		// Preserve \n in textboxes
		$data = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $data ) ) );
		
		return $data;
	}
	
	/**
	 * Prevents Gravity Form entries from being stored in the database.
	 *
	 * @global object $wpdb  The WP database object.
	 *
	 * @param array   $entry Array of entry data.
	 *
	 * @return bool - False or nothing if error/nothing to do.
	 */
	public function remove_survey_form_entry( $entry ) {
		
		$Access = Tracker_Access::getInstance();
		
		global $current_user;
		global $post;
		
		global $currentProgram;
		global $currentArticle;
		
		$ids = array();
		
		if ( has_shortcode( $post->post_content, 'gravityform' ) && ( $post->ID == $currentProgram->intake_form ) ) {
			
			Utilities::get_instance()->log( "Processing on the intake form page with the gravityform shortcode present" );
			$ids = $this->find_gf_id( $post->post_content );
		}
		
		if ( has_shortcode( $post->post_content, 'gravityform' ) && ( $currentArticle->is_survey == 1 ) && ( $currentArticle->post_id == $post->ID ) ) {
			
			Utilities::get_instance()->log( "Processing on a survey article page with the gravityform shortcode present" );
			$ids = $this->find_gf_id( $post->post_content );
		}
		
		if ( empty( $ids ) ) {
			
			Utilities::get_instance()->log( "This is not the BitBetter Interview form!" );
			
			return false;
		}
		
		if ( ! is_user_logged_in() ) {
			
			Utilities::get_instance()->log( "User accessing form without being logged in." );
			
			return false;
		}
		
		if ( ! $Access->hasAccess( $current_user->ID, $post->ID ) ) {
			
			Utilities::get_instance()->log( "User doesn't have access to this form." );
			
			return false;
		}
		
		global $wpdb;
		
		// Prepare variables.
		$lead_id                = $entry['id'];
		$lead_table             = \RGFormsModel::get_lead_table_name();
		$lead_notes_table       = \RGFormsModel::get_lead_notes_table_name();
		$lead_detail_table      = \RGFormsModel::get_lead_details_table_name();
		$lead_detail_long_table = \RGFormsModel::get_lead_details_long_table_name();
		
		Utilities::get_instance()->log( "Removing entries from the lead_detail_long_table" );
		// Delete from lead detail long.
		$sql = $wpdb->prepare( "DELETE FROM $lead_detail_long_table WHERE lead_detail_id IN(SELECT id FROM $lead_detail_table WHERE lead_id = %d)", $lead_id );
		$wpdb->query( $sql );
		
		Utilities::get_instance()->log( "Removing entries from the lead_detail_table" );
		
		// Delete from lead details.
		$wpdb->delete( $lead_detail_table, array( 'lead_id' => $lead_id ), array( '%d' ) );
		
		// Delete from lead notes.
		Utilities::get_instance()->log( "Removing entries from the lead_notes_table" );
		$wpdb->delete( $lead_notes_table, array( 'lead_id' => $lead_id ), array( '%d' ) );
		
		// Delete from lead.
		Utilities::get_instance()->log( "Removing entries from the lead_table" );
		$wpdb->delete( $lead_table, array( 'id' => $lead_id ), array( '%d' ) );
		
		Utilities::get_instance()->log( "Removing entries from any addons" );
		// Finally, ensure everything is deleted (like stuff from Addons).
		\GFAPI::delete_entry( $lead_id );
		
		return true;
	}
	
	
	/**
	 * Find the Form ID in the HTML
	 *
	 * @param string $content
	 *
	 * @return int
	 */
	private function find_gf_id( $content ) {
		
		preg_match_all( "/\[[^\]]*\]/", $content, $matches );
		
		foreach ( $matches as $k => $match ) {
			
			foreach ( $match as $sc ) {
				
				preg_match( '/id="(\d*)"/', $sc, $ids );
			}
		}
		
		return $ids;
	}
	
	/**
	 * Load the interview (info) for the form and user ID
	 *
	 * @param int $user_id
	 * @param \GFForms    $form
	 */
	public function loadInterview( $user_id, $form ) {
		$this->load_interview_data_for_client( $user_id, $form );
	}
	
	/**
	 * Load all interview data for the client
	 *
	 * @param int   $clientId
	 * @param mixed $form
	 *
	 * @return mixed
	 */
	public function load_interview_data_for_client( $clientId, $form ) {
		
		$Client = Client::getInstance();
		
		// $c_data = $this->get_data( $clientId );
		$c_data = $Client->get_client_info( $clientId );
		
		Utilities::get_instance()->log( "Client Data from DB:" );
		Utilities::get_instance()->log( $c_data );
		
		if ( isset( $c_data->incomplete_interview ) && ( 1 == $c_data->incomplete_interview ) ) {
			
			Utilities::get_instance()->log( "No client data found in DB for user w/ID: {$clientId}" );
			
			return $form;
		}
		
		$cFields    = array(
			'GF_Field_Radio',
			'GF_Field_Checkbox',
			'GF_Field',
			'GF_Field_Select',
			'GF_Field_MultiSelect',
			'GF_Field_Likert',
		);
		$txtFields  = array(
			'GF_Field_Phone',
			'GF_Field_Text',
			'GF_Field_Email',
			'GF_Field_Date',
			'GF_Field_Number',
			'GF_Field_Textarea',
		);
		$skipLabels = array( 'Comments', 'Name', 'Email', 'Phone' );
		
		foreach ( $form['fields'] as $id => $item ) {
			
			$classType = get_class( $item );
			
			// if ( ( 'GF_Field_Section' == $classType ) || ( 'GF_Field_HTML' == $classType ) ) {
			if ( ! ( in_array( $classType, $cFields ) || in_array( $classType, $txtFields ) ) ) {
				
				Utilities::get_instance()->log( "Skipping object: {$classType}" );
				continue;
			}
			
			if ( $classType == 'GF_Field' && in_array( $item['label'], $skipLabels ) ) {
				
				Utilities::get_instance()->log( "Skipping: {$item['label']}" );
				continue;
			}
			
			if ( in_array( $classType, $cFields ) ) {
				
				// Option/select fields - Use ['choices'] to set the current/default value.
				Utilities::get_instance()->log( "Processing {$classType} object {$item['label']}" );
				
				if ( ! is_array( $item['choices'] ) ) {
					Utilities::get_instance()->log( "Processing {$classType} object {$item['label']} Isn't an array of values?" );
				}
				
				foreach ( $item['choices'] as $cId => $i ) {
					
					// Split any checkbox list values in $c_data by semicolon...
					if ( isset( $c_data->{$item['label']} ) ) {
						$itArr = preg_split( '/;/', $c_data->{$item['label']} );
						
						$msArr = preg_split( '/,/', $c_data->{$item['label']} );
					}
					if ( empty( $msArr ) && ! isset( $c_data->{$item['label']} ) && empty( $c_data->{$item['label']} ) && ( 0 !== $c_data->{$item['label']} ) ) {
						Utilities::get_instance()->log( "{$item['label']} is empty? " . $c_data->{$item['label']} );
						continue;
					}
					
					// Utilities::get_instance()->log("Is value {$c_data->{$item['label']}} for object {$item['label']} numeric? " . ( is_numeric( $c_data->{$item['label']}) ? 'Yes' : 'No' ) );
					/** Process special cases where the DB field is numeric but the value in the form is text (Yes/No values) */
					if ( ( 'GF_Field_MultiSelect' != $classType ) && isset( $c_data->{$item['label']} ) && is_numeric( $c_data->{$item['label']} ) && ( 'likert' != $item['inputType'] ) ) {
						
						switch ( $c_data->{$item['label']} ) {
							
							case 0:
								Utilities::get_instance()->log( "Convert bit to text (N): {$c_data->{$item['label']}}" );
								$c_data->{$item['label']} = 'No';
								break;
							
							case 1:
								Utilities::get_instance()->log( "Convert bit to text (Y): {$c_data->{$item['label']}}" );
								$c_data->{$item['label']} = 'Yes';
								break;
						}
					}
					
					/** Process special cases where the DB field to indicate gender */
					if ( isset( $c_data->{$item['label']} ) && in_array( $c_data->{$item['label']}, array(
							'm',
							'f',
						) ) ) {
						
						Utilities::get_instance()->log( "Convert for gender: {$c_data->{$item['label']}}" );
						switch ( $c_data->{$item['label']} ) {
							
							case 'm':
								$c_data->{$item['label']} = 'M';
								break;
							
							case 'f':
								$c_data->{$item['label']} = 'F';
								break;
						}
					}
					
					$choiceField = $form['fields'][ $id ]['choices'];
					
					if ( 'likert' == $item['inputType'] ) {
						
						$key = 'score';
					} else {
						
						$key = 'value';
					}
					
					if ( isset( $c_data->{$item['label']} ) && ( ( 0 === $c_data->{$item['label']} ) || ! empty( $c_data->{$item['label']} ) ) && ( $c_data->{$item['label']} == $i[ $key ] ) ) {
						
						Utilities::get_instance()->log( "Choosing value " . $i[ $key ] . " for {$item['label']} - it's supposed to have key # {$cId}" );
						Utilities::get_instance()->log( "Form value: {$form['fields'][ $id ]['choices'][ $cId ][$key]}" );
						
						$choiceField[ $cId ]['isSelected'] = 1;
						
					}
					
					if ( is_array( $msArr ) && ( count( $msArr ) > 0 ) ) {
						
						Utilities::get_instance()->log( "List of values. Processing {$choiceField[$cId][$key]}" );
						
						if ( in_array( $choiceField[ $cId ][ $key ], $msArr ) ) {
							Utilities::get_instance()->log( "Found {$i[$key]} as a saved value!" );
							$choiceField[ $cId ]['isSelected'] = 1;
						}
					}
					
					if ( is_array( $itArr ) && ( count( $itArr ) > 1 ) ) {
						
						Utilities::get_instance()->log( "List of values. Processing {$choiceField[$cId][$key]}" );
						
						if ( in_array( $choiceField[ $cId ][ $key ], $itArr ) ) {
							
							Utilities::get_instance()->log( "Found {$i[$key]} as a saved value!" );
							$choiceField[ $cId ]['isSelected'] = 1;
						}
					}
					
					$form['fields'][ $id ]['choices'] = $choiceField;
				}
				
				$cId = null;
			}
			
			if ( in_array( $classType, $txtFields ) ) {
				
				if ( ! empty( $c_data->{$item['label']} ) ) {
					
					Utilities::get_instance()->log( "Restoring value: " . $c_data->{$item['label']} . " for field: " . $item['label'] );
					$form['fields'][ $id ]['defaultValue'] = $c_data->{$item['label']};
				}
			}
		}
		
		Utilities::get_instance()->log( "Returning form object with " . count( $form ) . " pieces for data" );
		
		// Utilities::get_instance()->log($form);
		return $form;
	}
	
	/**
	 * Load the Interview data from the database
	 *
	 * @param $form
	 *
	 * @return mixed
	 */
	public function load_interview( $form ) {
		
		$Tracker = Tracker::getInstance();
		global $currentClient;
		global $current_user;
		
		Utilities::get_instance()->log( "Start: " . $Tracker->whoCalledMe() );
		
		if ( stripos( $form['cssClass'], 'nourish-interview-identifier' ) === false ) {
			
			Utilities::get_instance()->log( 'Not the Program Interview form: ' . $form['cssClass'] );
			
			return $form;
		} else {
			Utilities::get_instance()->log( "Processing a Program Interview form" );
		}
		
		if ( ! is_user_logged_in() ) {
			
			Utilities::get_instance()->log( "User accessing form without being logged in." );
			
			return $form;
		}
		
		Utilities::get_instance()->log( "Loading form data: " . count( $form ) . " elements" );
		// Utilities::get_instance()->log( "Form: " . print_r( $form, true) );
		
		Utilities::get_instance()->log( "GF_Integration::load_interview() Processing form object as to load existing info if needed. " );
		
		if ( ! isset( $currentClient->user_id ) || ( $current_user->ID !== $currentClient->user_id ) ) {
			
			Utilities::get_instance()->log( "Loading interview for user ID {$current_user->ID} (currentClient->user_id is either undefined or different)" );
			
			Client::getInstance()->setClient( $current_user->ID );
		}
		
		return $this->load_interview_data_for_client( $current_user->ID, $form );
		
		// return $form;
	}
	
	/**
	 * Process/load data for Gravity Form field(s) from Tracker table(s)
	 *
	 * @param mixed     $value The field value
	 * @param \GF_Field $field The field type
	 * @param string    $name  The field name
	 *
	 * @return mixed
	 */
	public function process_gf_fields( $value, $field, $name ) {
		
		global $currentClient;
		$Client = Client::getInstance();
		
		$type = \GFFormsModel::get_input_type( $field );
		
		if ( ( 'likert' == $type ) && $field->allowsPrepopulate ) {
			
			$col_value = null;
			
			foreach ( $field->choices as $i ) {
				
				if ( $i['isSelected'] == 1 ) {
					
					return $i['value'];
				}
			}
		}
		
		if ( ( 'multichoice' == $type ) && $field->allowsPrepopulate ) {
			
			$col_value = null;
			
			foreach ( $field->choices as $i ) {
				
				if ( 1 == $i['isSelected'] ) {
					
					return $i['value'];
				}
			}
		}
		
		if ( ( 'address' == $type ) && $field->allowsPrepopulate && ! is_null( $currentClient->user_id ) ) {
			
			$val = $Client->getClientDataField( $currentClient->user_id, $name );
			
			Utilities::get_instance()->log( "Found the {$name} field for user ({$currentClient->user_id}): {$val}" );
			
			return $val;
		}
		
		return $value;
	}
}