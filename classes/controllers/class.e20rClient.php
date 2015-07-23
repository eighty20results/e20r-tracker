<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 10/22/14
 * Time: 1:16 PM
 */

class e20rClient {

    private $id;
    private $model = null; // Client Model.
    private $view = null; // Client Views.

    private $weightunits;
    private $lengthunits;

    public $client_loaded = false;
    public $actionsLoaded = false;
    public $scriptsLoaded = false;

    public function __construct( $user_id = null) {

        global $currentClient;

        $this->model = new e20rClientModel();
        $this->view = new e20rClientViews( $user_id );

        if ( $user_id !== null ) {

            $currentClient->user_id = $user_id;
        }

    }

    public function init() {

        global $e20rTracker;
        global $currentClient;

        if ( empty( $currentClient->user_id ) ) {

            global $current_user;
            // $this->id = $current_user->ID;
            $currentClient->user_id = $current_user->ID;
        }

        dbg('e20rClient::init() - Running INIT for e20rClient Controller');
        dbg('e20rClient::init() - ' . $e20rTracker->whoCalledMe() );

        if ( $this->client_loaded !== true ) {

            $this->model->setUser( $currentClient->user_id );
            $this->model->getData( $currentClient->user_id );
            $this->client_loaded = true;
        }

    }

    public function isNourishClient( $user_id = 0 ) {

	    global $e20r_isClient;

	    if ( ! is_user_logged_in() ) {

		    $e20r_isClient = false;
		    return $e20r_isClient;
	    }

	    if ( is_null( $e20r_isClient ) ) {

		    $e20r_isClient = false;

		    if ( function_exists( 'pmpro_hasMembershipLevel' ) ) {

			    dbg("e20rClient::isNourishClient() - Checking against Paid Memberships Pro");
			    // TODO: Fetch this from an option (multi-select on plugin settings page)
			    $nourish_levels = array( 16, 21, 22, 23, 18 );

			    if ( pmpro_hasMembershipLevel( $nourish_levels, $user_id ) ) {

				    dbg("e20rClient::isNourishClient() - User with id {$user_id} has a Nourish Coaching membership");
				    $e20r_isClient = true;
			    }
		    }
	    }

        dbg("e20rClient::isNourishClient() - Is user with id {$user_id} a nourish client? " . ( $e20r_isClient ? 'Yes' : 'No'));
        return $e20r_isClient;
    }

    public function clientId() {

        global $currentClient;

        return $currentClient->user_id;
    }

    public function getLengthUnit() {

        global $currentClient;

        return $this->model->getData( $currentClient->user_id, 'lengthunits');
    }

    public function getWeightUnit() {

        global $currentClient;

        return $this->model->getData( $currentClient->user_id, 'weightunits' );
    }

    public function getBirthdate( $user_id ) {

        return $this->model->getData( $user_id, 'birthdate' );
    }

    public function getUploadPath( $user_id ) {

        return $this->model->getData( $user_id, 'program_photo_dir' );
    }

    public function getUserImgUrl( $who, $when, $imageSide ) {

        global $e20rTables;

        if ( $this->isNourishClient( $who ) &&  $e20rTables->isBetaClient() ) {

            return $this->model->getBetaUserUrl( $who, $when, $imageSide );
        }

        return false;
    }

    public function setClient( $userId ) {

        $this->model->setUser( $userId );
        $this->init();
    }

    public function getData( $clientId, $private = false ) {

        if ( ! $this->client_loaded ) {

            dbg("e20rClient::getData() - No client data loaded yet...");
            $this->setClient( $clientId );
            // $this->getData( $clientId );
        }

        $data = $this->model->getData( $clientId );

	    if ( $private ) {

		    dbg("e20rClient::getData() - Removing private data");
		    $data = $this->strip_private_data( $data );
	    }

	    dbg("e20rClient::getData() - Returned data for {$clientId} from client_info table:");
        dbg($data);

        return $data;
    }

	private function strip_private_data( $cData ) {

		$birthdateSet = false;
		$genderSet = false;

		$data = $cData;

		$data->display_birthdate = 1;
		$data->incomplete_interview = 1;

		$include = array(
			'id', 'birthdate', 'first_name', 'gender', 'lengthunits', 'weightunits', 'incomplete_interview',
			'program_id', 'progress_photo_dir', 'program_start', 'user_id'
		);

		foreach ( $data as $field => $value ) {

			if ( ! in_array( $field, $include ) ) {

				unset($data->{$field} );
			}
		}

		if ( strtotime( $data->birthdate ) ) {

			$data->display_birthdate = 0;
		}

		if ( isset( $data->first_name ) && ( isset( $data->id ) ) && isset( $data->display_birthdate ) ) {

			$data->incomplete_interview = 0;
		}

		return $data;
	}

	public function getGender() {

        global $currentClient;

		return strtolower( $this->model->getData( $currentClient->user_id, 'gender') );
	}

    public function completeInterview( $userId ) {

        global $e20rTracker;

        $data = $this->model->getData( $userId, 'completed_date');

        dbg("e20rClient::completeInterview() - completed_date field contains: ");
        dbg($data);

        return ( empty( $data ) ? 0 : 1 );
    }

	public function load_interview( $form ) {

		dbg("e20rTracker::gravityform_preload() - Start" );

		global $e20rTracker;
		global $current_user;
		global $post;

		if ( stripos( $form['cssClass'], 'nourish-interview-identifier' ) === false ) {

			dbg('e20rTracker::gravityform_preload()  - Not the BitBetter Interview form: ' . $form['cssClass']);
			return $form;
		}

		if ( ! is_user_logged_in() ) {

			dbg("e20rTracker::save_interview()  - User accessing form without being logged in.");
			return;
		}

		if ( ! $e20rTracker->hasAccess( $current_user->ID, $post->ID ) ) {

			dbg("e20rTracker::save_interview()  - User doesn't have access to this form.");
			return false;
		}

		dbg("e20rTracker::gravityform_preload() - Loading form data: ");
		// dbg( "Form: " . print_r( $form, true) );

		return $this->loadClientInterviewData( $current_user->ID, $form );

		// return $form;
	}

	public function loadClientInterviewData( $clientId, $form ) {

		$this->setClient($clientId);

		$c_data = $this->model->getData( $clientId );

		dbg("e20rClient::loadClientInterviewData() - Client Data from DB:");
		dbg($c_data);

		if ( isset($c_data->incomplete_interview) && ( 1 == $c_data->incomplete_interview ) ) {

			dbg("e20rClient::loadClientInterviewData() - No client data found in DB for user w/ID: {$clientId}");
			return $form;
		}

		$cFields = array( 'GF_Field_Radio', 'GF_Field_Checkbox', 'GF_Field', 'GF_Field_Select' );
		$txtFields = array( 'GF_Field_Phone', 'GF_Field_Text', 'GF_Field_Email', 'GF_Field_Date', 'GF_Field_Number', 'GF_Field_Textarea');
		$skipLabels = array( 'Comments', 'Name', 'Email', 'Phone' );

		foreach( $form['fields'] as $id => $item ) {

			$classType = get_class($item);

			// if ( ( 'GF_Field_Section' == $classType ) || ( 'GF_Field_HTML' == $classType ) ) {
			if ( !( in_array( $classType, $cFields) || in_array( $classType, $txtFields ) ) ) {

				dbg("e20rClient::loadClientInterviewData() - Skipping object: {$classType}");
				continue;
			}

			if (  $classType == 'GF_Field' && in_array( $item['label'], $skipLabels) ) {

				dbg("e20rClient::loadClientInterviewData() - Skipping: {$item['label']}");
				continue;
			}

			if ( in_array( $classType, $cFields ) ) {

				// Option/select fields - Use ['choices'] to set the current/default value.
				dbg("e20rClient::loadClientInterviewData() - Processing {$classType} object {$item['label']}");

				foreach( $item['choices'] as $cId => $i ) {

					// Split any checkbox list values in $c_data by semicolon...
					$itArr = preg_split( '/;/', $c_data->{$item['label']} );

					/** Process special cases where the DB field is numeric but the value in the form is text (Yes/No values) */
					if ( is_numeric( $c_data->{$item['label']}) && ( 'likert' != $item['inputType'] ) ) {

						switch ( $c_data->{$item['label']} ) {

							case 0:
								dbg( "e20rClient::loadClientInterviewData() - Convert bit to text (N): {$c_data->{$item['label']}}" );
								$c_data->{$item['label']} = 'No';
								break;

							case 1:
								dbg( "e20rClient::loadClientInterviewData() - Convert bit to text (Y): {$c_data->{$item['label']}}" );
								$c_data->{$item['label']} = 'Yes';
								break;
						}
					}

					/** Process special cases where the DB field to indicate gender */
					if ( in_array( $c_data->{$item['label']}, array('m', 'f') ) )  {

						dbg( "e20rClient::loadClientInterviewData() - Convert for gender: {$c_data->{$item['label']}}" );
						switch( $c_data->{$item['label']}) {

							case 'm':
								$c_data->{$item['label']} = 'M';
								break;

							case 'f':
								$c_data->{$item['label']} = 'F';
								break;
						}
					}

					$choiceField = $form['fields'][$id]['choices'];

					if ( 'likert' == $item['inputType'] ) {

						$key = 'score';
					}
					else {

						$key = 'value';
					}

					if ( ( !empty( $c_data->{$item['label']} ) ) && ( $c_data->{$item['label']} == $i[ $key ] ) ) {

						dbg( "e20rClient::loadClientInterviewData() - Choosing value " . $i[ $key ] . " for {$item['label']} - it's supposed to have key # {$cId}" );
						dbg( "e20rClient::loadClientInterviewData() - Form value: {$form['fields'][ $id ]['choices'][ $cId ][$key]}");

						$choiceField[ $cId ]['isSelected'] = 1;

					}

					if ( is_array($itArr) && ( count( $itArr ) > 1 ) ) {

						dbg( "e20rClient::loadClientInterviewData() - List of values. Processing {$choiceField[$cId][$key]}" );

						if ( in_array( $choiceField[$cId][$key], $itArr ) ) {

							dbg( "e20rClient::loadClientInterviewData() - Found {$i[$key]} as a saved value!" );
							$choiceField[ $cId ]['isSelected'] = 1;
						}
					}

					$form['fields'][$id]['choices'] = $choiceField;
				}

				$cId = null;
			}

			if ( in_array( $classType, $txtFields ) ) {

				if ( !empty( $c_data->{$item['label']} ) )  {

					dbg("e20rClient::loadClientInterviewData() - Restoring value: " . $c_data->{$item['label']} . " for field: " . $item['label']);
					$form['fields'][ $id ]['defaultValue'] = $c_data->{$item['label']};
				}
			}
		}

		return $form;
	}

	/**
	 * Prevents Gravity Form entries from being stored in the database.
	 *
	 * @global object $wpdb The WP database object.
	 * @param array $entry  Array of entry data.
     *
     * @return bool - False or nothing if error/nothing to do.
	 */
	public function remove_survey_form_entry( $entry ) {

		global $e20rTracker;
		global $current_user;
		global $post;

		if ( ! in_array( $entry['id'], array( '1' ) ) ) {

			dbg("e20rTracker::gravityform_preload()  - Not the BitBetter Interview form!");
			return;
		}

		if ( ! is_user_logged_in() ) {

			dbg("e20rTracker::save_interview()  - User accessing form without being logged in.");
			return;
		}

		if ( ! $e20rTracker->hasAccess( $current_user->ID, $post->ID ) ) {

			dbg("e20rTracker::save_interview()  - User doesn't have access to this form.");
			return false;
		}

		global $wpdb;

		// Prepare variables.
		$lead_id                = $entry['id'];
		$lead_table             = RGFormsModel::get_lead_table_name();
		$lead_notes_table       = RGFormsModel::get_lead_notes_table_name();
		$lead_detail_table      = RGFormsModel::get_lead_details_table_name();
		$lead_detail_long_table = RGFormsModel::get_lead_details_long_table_name();

		// Delete from lead detail long.
		$sql = $wpdb->prepare( "DELETE FROM $lead_detail_long_table WHERE lead_detail_id IN(SELECT id FROM $lead_detail_table WHERE lead_id = %d)", $lead_id );
		$wpdb->query( $sql );

		// Delete from lead details.
		$sql = $wpdb->prepare( "DELETE FROM $lead_detail_table WHERE lead_id = %d", $lead_id );
		$wpdb->query( $sql );

		// Delete from lead notes.
		$sql = $wpdb->prepare( "DELETE FROM $lead_notes_table WHERE lead_id = %d", $lead_id );
		$wpdb->query( $sql );

		// Delete from lead.
		$sql = $wpdb->prepare( "DELETE FROM $lead_table WHERE id = %d", $lead_id );
		$wpdb->query( $sql );

		// Finally, ensure everything is deleted (like stuff from Addons).
		GFAPI::delete_entry( $lead_id );

	}

    /**
     * @param $entry -- Survey entry (gravity forms entry object)
     * @param $form -- Form object
     * @return bool -- True/False
     */
	public function save_interview( $entry, $form ) {

		global $e20rTracker;
		global $current_user;
		global $post;

		dbg("e20rTracker::save_interview() - Start");

		if ( false === stripos( $form['cssClass'], 'nourish-interview-identifier' ) ) {

			dbg('e20rTracker::save_interview()  - Not the BitBetter Interview form: ' . $form['cssClass']);
			return;
		}

		if ( ! is_user_logged_in() ) {

			dbg("e20rTracker::save_interview()  - User accessing form without being logged in.");
			return;
		}

		if ( ! $e20rTracker->hasAccess( $current_user->ID, $post->ID ) ) {

			dbg("e20rTracker::save_interview()  - User does NOT have access to this form.");
			return false;
		}

		dbg("e20rTracker::save_interview() - Processing the Bit Better Interview form(s).");

		global $e20rMeasurements;
		global $current_user;
		global $e20rProgram;
		global $e20rTracker;
        global $page;

		$userId = $current_user->ID;
		$userProgramId = $e20rProgram->getProgramIdForUser( $userId );
		$userProgramStart = $e20rProgram->startdate( $userId );
		$eKey = $e20rTracker->getUserKey( $userId );

		$db_Data = array(
			// 'id' => $userId,
			'user_id' => $userId,
			'program_id' => $userProgramId,
            'page_id' => $page->ID,
			'program_start' => date_i18n('Y-m-d', $userProgramStart ),
	        'user_enc_key' => $eKey,
			'progress_photo_dir'=> "e20r_pics/client_{$userProgramId}_{$userId}"
		);

		$fieldList = array( 'text', 'textarea', 'number', 'email', 'phone' );

		dbg("e20rClient::save_interview() - Processing the Welcome Interview form");

		foreach( $form['fields'] as $item ) {

			$skip = true;

			dbg("e20rClient::save_interview() - Processing field type: {$item['type']}");

			if ( 'section' != $item['type'] ) {

				$fieldName = $item['label'];
				$subm_key = $item['id'];

				if ( in_array( $item['type'], $fieldList ) ) {

					$skip = false;
				}

				if ( $item['type'] == 'date' ) {

					$skip = true;
					$db_Data[$fieldName] = date( 'Y-m-d', strtotime( $this->filterResponse( $entry[ $item['id'] ] ) ) );
				}

				if ( $item['type'] == 'checkbox' ) {

					$checked = array();

					foreach( $item['inputs'] as $k => $i ) {

						if ( !empty( $entry[ $i['id'] ] ) ) {

							$checked[] = $this->filterResponse( $item['choices'][$k]['value'] );
						}
					}

					if ( ! empty( $checked ) ) {

						$db_Data[ $fieldName ] = $e20rTracker->encryptData( join( ';', $checked ), $eKey );

					}

					$skip = true;
				}

				if ( ( $fieldName == 'calculated_weight_lbs') && ( ! empty( $entry[ $item['id'] ] ) )  ) {

					dbg("e20rClient::save_interview() - Saving weight as LBS...");
					$skip = false;
					$e20rMeasurements->saveMeasurement( 'weight', $entry[ $item['id'] ], -1, $userProgramId, $userProgramStart, $userId );
				}

				if ( ( $fieldName == 'calculated_weight_kg') && ( ! empty( $entry[$item['id']] ) )  ) {

					dbg("e20rClient::save_interview() - Saving weight as KG");
					$skip = false;
					$e20rMeasurements->saveMeasurement( 'weight', $entry[ $item['id'] ], -1, $userProgramId, $userProgramStart, $userId );
				}

				if ( $item['type'] == 'survey' ) {

					$key = $entry[$item['id']];
					$subm_key = $item['id'];

					if ( $item['inputType'] == 'likert' ) {

						foreach( $item['choices'] as $k => $i ) {

							foreach ( $i as $lk => $val ) {

								if ( $entry[$subm_key] == $item['choices'][$k]['value'] ) {

									$entry[$subm_key] = $item['choices'][$k]['score'];
									$skip = false;
								}
							}
						}
					}
				}

				if ( $item['type'] == 'address' ) {

					dbg("e20rClient::save_interview() - Saving address information: ");
					// dbg($entry);

					// $key = $item['id'];

					foreach( $item['inputs'] as $k => $i ) {

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

						dbg("e20rClient::save_interview() - Field: {$fieldName}, Item #: {$key} -> Value: {$val}");

						// dbg($entry[ {$i['id']} ]);


						if ( !empty( $val ) ) {

							dbg("e20rClient::save_interview() - Saving address item {$fieldName} -> {$val}");
							$db_Data[ $fieldName ] = $e20rTracker->encryptData( $this->filterResponse( $val ), $eKey );
						}

					}

					$skip = true;
				}

				if ( $item['type'] == 'select' ) {

					if ( !empty( $entry[$subm_key] ) ) {

						foreach ($item['choices'] as $k => $v ) {

							dbg("e20rClient::save_interview() - Select item: Field: {$fieldName}, subm_key={$subm_key}, entryVal={$entry[$subm_key]}, item={$item['choices'][$k]['value']}");

							if ( $item['choices'][$k]['value'] == $entry[$subm_key] ) {

								$db_Data[ $fieldName ] = $e20rTracker->encryptData( $this->filterResponse( $item['choices'][$k]['value'] ), $eKey );
							}
						}
					}
				}

				if ( $item['type'] == 'radio' ) {

					if ( !empty( $entry[$subm_key] ) ) {
						foreach ( $item['choices'] as $k => $v ) {

							if ( $item['choices'][ $k ]['value'] == $entry[ $subm_key ] ) {

								$db_Data[ $fieldName ] = $e20rTracker->encryptData( $this->filterResponse( $item['choices'][ $k ]['value'] ), $eKey );
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
					dbg("e20rClient::save_interview() - Data being stored: .{$data}.");

					// Encrypt the data.
					$encData = $e20rTracker->encryptData( $this->filterResponse( $data ), $eKey );
					$db_Data[ $fieldName ] = $encData;
				}
				else {
					dbg("e20rClient::save_interview() - Skipped field of type: {$item['type']} and with value: " . ( isset( $entry[ $item['id'] ] ) ? $entry[ $item['id'] ] : 'null' ) );
				}
			}
		} // End of foreach loop for submitted form

		if ( ! WP_DEBUG ) {

			dbg("e20rClient::save_interview() - Removing any GF entry data from database.");
			$this->remove_survey_form_entry( $entry );
		}

		dbg("e20rClient::save_interview() - Saving the client interview data to the DB. ");

		if ( $this->model->save_client_interview( $db_Data ) ) {

			dbg("e20rClient::save_interview() - Saved data to the database ");
			return true;
		}

		return false;
	}

	public function process_gf_fields( $value, $field, $name ) {

        global $currentClient;

		$type   = GFFormsModel::get_input_type( $field );

		if ( ( 'likert' == $type ) && $field->allowsPrepopulate ) {

			$col_value = null;

			foreach ( $field->choices as $i ) {

				if ( $i['isSelected'] == 1 ) {

					return $i['value'];
				}
			}
		}

		if ( ( 'address' == $type ) && $field->allowsPrepopulate && !is_null( $currentClient->user_id ) ) {

			$val = $this->model->getData($currentClient->user_id, $name );

			dbg("e20rClient::process_gf_fields() - Found the {$name} field for user ({$currentClient->user_id}): {$val}");
			return $val;
		}

		return $value;
	}

	private function filterResponse( $data ) {

		switch( $data ) {

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

		return $data;
	}
    /*
    public function saveNewUnit( $type, $unit ) {

        switch ($type) {

            case 'length':

                dbg("e20rClient::saveNewUnit() - Saving new length unit: {$unit}");
                $this->model->info->saveUnitInfo( $unit, $this->getWeightUnit() );
                break;

            case 'weight':

                dbg("e20rClient::saveNewUnit() - Saving new weight unit: {$unit}");
                $this->model->info->saveUnitInfo( $this->getLengthUnit(), $unit );
                break;
        }

        return true;
    }
*/
    public function loadClientInfo( $user_id ) {

        try {

            dbg("e20rClient::loadClientInfo() - Loading data for client model");
            $this->model->setUser( $user_id );
            $this->model->getData( $user_id );

        }
        catch ( Exception $e ) {

            dbg("Error loading user data for ({$user_id}): " . $e->getMessage() );
        }

    }

    /*
public function loadClient( $id = null ) {

    global $currentClient;

    if ( $id ) {
        $currrentClient->user_id = $id;
    }

    $this->model->setUser( $currrentClient->user_id );
    $this->model->getData( $user_id );

}
*/
    /*
    public function afterGFSubmission( $entry, $form ) {

        dbg("gf_after_submission - entry: ". print_r( $entry, true));
        dbg("gf_after_submission - form: ". print_r( $form, true));
    }
    */
/*
    public function ajax_userInfo_callback() {

        dbg("ajax_userInfo_Callback() - Checking access");
        dbg("Received data: " . print_r($_POST, true ) );

        check_ajax_referer( 'e20r-tracker-progress', 'e20r-progress-nonce');

        dbg("ajax_userInfo_Callback() - Access approved");

        global $wpdb;

        $var = ( isset( $_POST['measurement-type']) ? sanitize_text_field( $_POST['measurement-type']): null );

        try {

            if ( empty( $this->model ) ) {
                $this->init();
            }

            $userData = $this->model->info->getInfo();
            $retVal = $userData->{$var};

            dbg("Requested variable: {$var} = {$retVal}" );
            echo json_encode( $retVal, JSON_FORCE_OBJECT );
            exit;
        }
        catch ( Exception $e ) {
            dbg("Error loading and returning user data: " . $e->getMessage() );
        }
    }
*/
    /**
     * Render page on back-end for client data (admin selectable).
     */
    public function render_client_page( $lvlName = '', $client_id = 0 ) {

        global $current_user;
        global $currentClient;
        global $currentProgram;

        global $e20rProgram;

        if ( $client_id != 0 ) {

            $this->setClient( $client_id );
        }

        $this->init();

        $this->model->getData( $client_id );

        echo $this->view->viewClientAdminPage( $lvlName );
    }

    /*
    public function getInfo() {

        if ( empty( $this->model->info ) ) {

            try {
                $this->loadInfo();
            }
            catch ( Exception $e ) {
                dbg('Error loading user info from the database: ' . $e->getMessage() );
            }
        }

        return $this->info;
    }
*/
    public function updateUnitTypes() {

        dbg( "e20rClient::updateUnitTypes() - Attempting to update the Length or weight Units via AJAX");

        check_ajax_referer( 'e20r-tracker-progress', 'e20r-progress-nonce');

        dbg("e20rClient::updateUnitTypes() - POST content: " . print_r($_POST, true));

        global $current_user;
        global $currentClient;
        global $e20rMeasurements;

        $currentClient->user_id = isset( $_POST['user-id'] ) ? intval( $_POST['user-id'] ) : $current_user->ID;
        $dimension = isset( $_POST['dimension'] ) ? sanitize_text_field( $_POST['dimension'] ) : null;
        $value = isset( $_POST['value'] ) ? sanitize_text_field( $_POST['value'] ) : null;

        // Configure the client data object(s).
        $this->init();
        $e20rMeasurements->setClient( $currentClient->user_id );

        // Update the data for this user in the measurements table.
        try {

            $e20rMeasurements->updateMeasurementsForType( $dimension, $value );
        }
        catch ( Exception $e ) {
            dbg("e20rClient::updateUnitTypes() - Error updating measurements for new measurement type(s): " . $e->getMessage() );
            wp_send_json_error( "Error updating existing data: " . $e->getMessage() );
        }

        // Save the actual setting for the current user

        $this->weightunits = $this->getWeightUnit();
        $this->lengthunits = $this->getLengthUnit();

        if ( $dimension == 'weight' ) {
            $this->weightunits = $value;
        }

        if ( $dimension == 'length' ) {
            $this->lengthunits = $value;
        }

        // Update the settings for the user
        try {
            $this->model->saveUnitInfo( $this->lengthunits, $this->weightunits );
        }
        catch ( Exception $e ) {
            dbg("e20rClient::updateUnitTypes() - Error updating measurement unit for {$dimension}");
            wp_send_json_error( "Unable to save new {$dimension} type " );
        }

        dbg("e20rClient::updateUnitTypes() - Unit type updated");
        wp_send_json_success( "All data updated ");
        wp_die();
    }

    function ajax_getMemberlistForLevel() {
        // dbg($_POST);
        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-clients-nonce');

        global $e20rTracker;

        $levelId = ( isset($_POST['hidden_e20r_level']) ? intval( $_POST['hidden_e20r_level']) : 0 );

        $this->init();

        dbg("e20rClient::getMemberListForLevel() - Level requested: {$levelId}");

        if ( $levelId != 0 ) {

            $levels = $e20rTracker->getMembershipLevels( $levelId );
            // $this->load_levels( $levelObj->name );
            dbg("e20rClient::getMemberListForLevel() - Loading members:");
	        dbg($levels);

            $data = $this->view->viewMemberSelect(  $levelId );
        }
        else {

            $this->view->load_levels();
            $data = $this->view->viewMemberSelect();
        }

        wp_send_json_success( $data );

    }

    private function createEmailBody( $subject, $content ) {

        ob_start(); ?>
<html>
    <head>
        <title><?php echo $subject; ?></title>
    </head>
    <body>
        <h1 style="padding:5px 0 0 0; font-family:georgia;font-weight:500;font-size:16px;color:#000;border-bottom:1px solid #bbb">
            <?php echo $subject; ?>
        </h1>
        <div id="the_content" style="">
            <?php echo $content; ?>
        </div>
    </body>
</html><?php
        $html = ob_get_clean();
        return $html;
    }

    public function ajax_sendClientMessage() {

        global $e20rTracker;

        $headers = array();

        dbg('e20rClient::ajax_sendClientMessage() - Requesting client detail');

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-clients-nonce');

        dbg("e20rClient::ajax_sendClientMessage() - Nonce is OK");

        dbg("e20rClient::ajax_sendClientMessage() - Request: " . print_r($_REQUEST, true));

        // $to_uid = isset( $_POST['email-to-id']) ? $e20rTracker->sanitize( $_POST['email-to-id']) : null;
        $to_email = isset( $_POST['email-to'] ) ? $e20rTracker->sanitize( $_POST['email-to']) : null;
        $cc = isset( $_POST['email-cc'] ) ? $e20rTracker->sanitize( $_POST['email-cc']) : null;
        $from_uid = isset( $_POST['email-from-id'] ) ? $e20rTracker->sanitize( $_POST['email-from-id']) : null;
        $from = isset( $_POST['email-from'] ) ? $e20rTracker->sanitize( $_POST['email-from']) : null;
        $from_name = isset( $_POST['email-from-name'] ) ? $e20rTracker->sanitize( $_POST['email-from-name']) : null;
        $subject = isset( $_POST['subject'] ) ? $e20rTracker->sanitize( $_POST['subject']) : ' ';
        $content = isset( $_POST['content'] ) ? $e20rTracker->sanitize( $_POST['content']) : '&nbsp;';

        $content = stripslashes_deep( $content );

        $message = $this->createEmailBody( $subject, $content );

        if (! is_null( $from_uid ) ) {
            $f= get_user_by( 'id', $from_uid );
        }

        // $sendTo = "{$to->display_name} <{$to_email}>";

        $headers[] = "Content-type: text/html";
        $headers[] = "Cc: " . $cc;
        $headers[] = "From: " . $from;
        // $headers[] = "From: \"{$from_name}\" <{$from}>";

        dbg($to_email);
        dbg($headers);
        dbg($subject);
        dbg($message);

        add_filter('wp_mail', array( $this, 'test_wp_mail') );

        add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type') );
        // add_filter( 'wp_mail_charset', 'utf8' );
        $status = wp_mail( $to_email, $subject, $message, $headers, null );
        // remove_filter( 'wp_mail_charset', 'utf8' );
        remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type') );

		if ( true ==  $status ) {
            dbg("e20rClient::ajax_sendClientMessage() - Successfully transferred the info to wp_mail()");
            wp_send_json_success();
            wp_die();
        }

        dbg("e20rClient::ajax_sendClientMessage() - Error while transferring info to wp_mail()");
        wp_send_json_error();
    }

    public function set_html_content_type() {

        return 'text/html';
    }

    public function test_wp_mail( $args ) {

        $debug = var_export($args, true);
        dbg($debug);
    }

    public function ajax_showClientMessage() {
        dbg('e20rClient::ajax_showClientMessage() - Requesting client detail');

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-clients-nonce');

        dbg("e20rClient::ajax_showClientMessage() - Nonce is OK");

        dbg("e20rClient::ajax_showClientMessage() - Request: " . print_r($_REQUEST, true));

        global $current_user;
        global $e20rProgram;
        global $e20rMeasurements;
        global $e20rTracker;

        global $currentProgram;
        global $currentClient;

        $userId = isset( $_POST['client-id'] ) ? $e20rTracker->sanitize( $_POST['client-id']) : $current_user->ID;
        $e20rProgram->getProgramIdForUser( $userId );

        dbg("e20rClient::ajax_showClientMessage() - Load client data...");

        // Loads the program specific client information we've got stored.
        $this->model->getData( $userId );

        $html = $this->view->viewClientContact( $userId );

        wp_send_json_success( array( 'html' => $html ));
    }

    public function ajax_clientDetail() {
        dbg('e20rClient::ajax_clientDetail() - Requesting client detail');

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-clients-nonce');

        dbg("e20rClient::ajax_clientDetail() - Nonce is OK");

        dbg("e20rClient::ajax_clientDetail() - Request: " . print_r($_REQUEST, true));

        global $current_user;
		global $e20rProgram;
		global $e20rMeasurements;
		global $e20rTracker;

		global $currentProgram;
        global $currentClient;

		$userId = isset( $_POST['client-id'] ) ? $e20rTracker->sanitize( $_POST['client-id']) : $current_user->ID;
		$e20rProgram->getProgramIdForUser( $userId );

		dbg("e20rClient::ajax_clientDetail() - Load client data...");

        // Loads the program specific client information we've got stored.
        $this->model->getData( $userId );

        $html = $this->view->viewClientDetail( $userId );

        wp_send_json_success( array( 'html' => $html ));
    }

    function ajax_complianceData() {

        dbg('Requesting Check-In details');

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-clients-nonce');

        dbg("Nonce is OK");

        $this->init();
//        $this->initClientViews();

        $checkins = new E20Rcheckin();

        // TODO: 10/02/2014 - Multiple steps: For different habits, get & generate different graphs.
        // NOTE: Special care for existing Nourish group... :(
        // Get the list of check-ins so far - SQL.
        // Calculate the max # of check-ins per check-in type (day/calendar based)
        //


    }

    function ajax_assignmentData() {
        dbg('Requesting Assignment details');

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-clients-nonce');

        dbg("Nonce is OK");

        $this->init();
//        $this->initClientViews();

    }

    public function validateAccess( $clientId ) {

        global $current_user;
		global $e20rTracker;

        dbg("e20rClient::validateAccess() - Client being validated: " . $clientId );

        if ( $clientId ) {

            $client = get_user_by("id", $clientId );
            dbg("e20rClient::validateAccess() - Real user Id provided ");

            if ( ($current_user->ID != $clientId ) &&  ( $e20rTracker->isActiveClient( $clientId )  ) ) {
                return true;
            }
            elseif ( $current_user->ID == $clientId ) {
                return true;
            }
            // Make sure the $current_user has the right to view the data for $clientId

        }

        return false;
    }
}