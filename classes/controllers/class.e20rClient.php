<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 10/22/14
 * Time: 1:16 PM
 */

class e20rClient {

    private $id;
    public $client_loaded = false;

    public $actionsLoaded = false;
    public $scriptsLoaded = false;

    private $model = null; // Client Model.
    private $view = null; // Client Views.

    private $weightunits;
    private $lengthunits;

    public function __construct( $user_id = null) {

        if ( $user_id !== null ) {

            $this->id = $user_id;
        }

        $this->model = new e20rClientModel();
        $this->view = new e20rClientViews( $this->id );
    }

    public function init() {

        global $e20rTracker;

        dbg('e20rClient::init() - Running INIT for e20rClient Controller');
        dbg('e20rClient::init() - ' . $e20rTracker->whoCalledMe() );

        if ( $this->client_loaded !== true ) {

            $this->model->setUser( $this->id );
            $this->model->load();
            $this->client_loaded = true;
        }

    }

    public function isNourishClient( $user_id = 0 ) {

	    global $e20r_isClient;

	    if ( is_null( $e20r_isClient ) ) {

		    dbg("e20rClient::isNourishClient() - Is user with id {$user_id} a nourish client?");
		    $e20rNourishClient = false;

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

        return $e20r_isClient;
    }

    public function clientId() {

        return $this->id;
    }

    public function getLengthUnit() {

        return $this->model->getData( $this->id, 'lengthunits');
    }

    public function getWeightUnit() {

        return $this->model->getData( $this->id, 'weightunits' );
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

        $this->id = $userId;
        $this->model->setUser( $this->id );
    }

    public function getData( $clientId, $private = false ) {

        if ( ! $this->client_loaded ) {

            $this->setClient( $clientId );
            $this->init();
        }

        $data = $this->model->getData( $this->id );

	    if ( $private ) {

		    dbg("e20rClient::getData() - Removing private data");
		    $data = $this->strip_private_data( $data );
	    }

	    dbg("e20rClient::getData() - Returned data for {$this->id} from client_info table:");
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

		return strtolower( $this->model->getData( $this->id, 'gender') );
	}

    public function completeInterview( $userId ) {

        $data = $this->model->getData( $userId, 'completed_date');

        return ( empty($data) ? 0 : 1 );
    }

	public function load_interview( $form ) {

		dbg("e20rTracker::gravityform_preload() - Start" );

		if ( stripos( $form['cssClass'], 'bitbetter-interview-identifier' ) === false ) {

			dbg('e20rTracker::gravityform_preload()  - Not the BitBetter Interview form: ' . $form['cssClass']);
			return $form;
		}

		dbg("e20rTracker::gravityform_preload() - Loading form data: ");
		// dbg( "Form: " . print_r( $form, true) );

		return $form;
	}

	public function save_interview( $entry, $form ) {

		global $e20rTracker;
		global $current_user;
		global $post;

		dbg("e20rTracker::save_interview() - Start");

		if ( stripos( $form['cssClass'], 'bitbetter-interview-identifier' ) === false ) {

			dbg('e20rTracker::save_interview()  - Not the BitBetter Interview form: ' . $form['cssClass']);
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

		dbg("e20rTracker::save_interview() - Processing the Bit Better Interview form(s).");

		global $e20rMeasurements;
		global $current_user;
		global $e20rProgram;
		global $e20rTracker;

		$userId = $current_user->ID;
		$userProgramId = $e20rProgram->getProgramIdForUser( $userId );
		$userProgramStart = $e20rProgram->startdate( $userId );

		$db_Data = array(
			'id' => $userId,
			'user_id' => $userId,
			'program_id' => $userProgramId,
			'program_start' => date_i18n('Y-m-d',$userProgramStart ),
//	        'user_enc_key' => $this->getUserKey( $user_id ),
			'program_photo_dir'=> "e20r_pics/client_{$userProgramId}_{$userId}"
		);

		$fieldList = array( 'text', 'textarea', 'number', 'email', 'phone' );

		dbg("e20rClient::save_interview() - Processing the Welcome Interview form");

		foreach( $form['fields'] as $item ) {

			$skip = true;

			dbg("e20rClient::save_interview() - Processing field type: {$item['type']}");

			if ( $item['type'] != 'section' ) {

				$fieldName = $item['label'];
				$subm_key = $item['id'];

				if ( in_array( $item['type'], $fieldList ) ) {

					$skip = false;
				}

				if ( $item['type'] == 'date' ) {

					$skip = true;
					$db_Data[$fieldName] = esc_sql( date( 'Y-m-d', strtotime( $this->filterResponse( $entry[ $item['id'] ] ) ) ) );
				}

				if ( $item['type'] == 'checkbox' ) {

					$checked = array();

					foreach( $item['inputs'] as $k => $i ) {

						if ( !empty( $entry[ $i['id'] ] ) ) {
							$checked[] = $item['choices'][$k]['text'];
						}
					}

					if ( ! empty( $checked ) ) {

						$db_Data[ $fieldName ] = esc_sql( $e20rTracker->encryptData( join( ', ', $checked ) ) );

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

					$key = $item['id'];

					foreach( $item['inputs'] as $k => $aItem ) {

						$splt = preg_split( "/\./", $aItem['id'] );

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

						if ( !empty( $entry[$aItem['id']])) {

							$db_Data[ $fieldName ] = esc_sql( $e20rTracker->encryptData( $this->filterResponse( $entry[ $aItem['id'] ] ) ) );
						}
					}

					$skip = true;
				}

				if ( $item['type'] == 'select' ) {

					if ( !empty( $entry[$subm_key] ) ) {

						foreach ($item['choices'] as $k => $v ) {

							dbg("e20rClient::save_interview() - Select item: Field: {$fieldName}, subm_key={$subm_key}, entryVal={$entry[$subm_key]}, item={$item['choices'][$k]['value']}");

							if ( $item['choices'][$k]['value'] == $entry[$subm_key] ) {

								$db_Data[ $fieldName ] = esc_sql( $e20rTracker->encryptData( $this->filterResponse( $item['choices'][$k]['text'] ) ) );
							}
						}
					}
				}

				if ( $item['type'] == 'radio' ) {

					if ( !empty( $entry[$subm_key] ) ) {
						foreach ( $item['choices'] as $k => $v ) {

							if ( $item['choices'][ $k ]['value'] == $entry[ $subm_key ] ) {

								$db_Data[ $fieldName ] = esc_sql( $e20rTracker->encryptData( $this->filterResponse( $item['choices'][ $k ]['value'] ) ) );
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
					$encData = $e20rTracker->encryptData( $this->filterResponse( $data ) );
					$db_Data[ $fieldName ] = esc_sql( $encData );
				}
				else {
					dbg("e20rClient::save_interview() - Skipped field of type: {$item['type']} and with value: " . ( isset( $entry[ $item['id'] ] ) ? $entry[ $item['id'] ] : 'null' ) );
				}
			}
		} // End of foreach loop for submitted form

		dbg("e20rClient::save_interview() - The current state of the data to feed to the DB: ");

		if ( $this->model->save_client_interview( $db_Data ) ) {

			dbg("e20rClient::save_interview() - Saved data to the database ");
		}

		return false;
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
            $this->model->setUser($user_id);
            $this->model->load();

        }
        catch ( Exception $e ) {

            dbg("Error loading user data for ({$user_id}): " . $e->getMessage() );
        }

    }

    /*
public function loadClient( $id = null ) {

    if ( $id ) {
        $this->id = $id;
    }

    $this->model->setUser( $this->id );
    $this->model->load();

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

        if ( $client_id != 0 ) {

            $this->setClient( $client_id );
        }

        $this->init();

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
        global $e20rMeasurements;

        $this->id = isset( $_POST['user-id'] ) ? intval( $_POST['user-id'] ) : $current_user->ID;
        $dimension = isset( $_POST['dimension'] ) ? sanitize_text_field( $_POST['dimension'] ) : null;
        $value = isset( $_POST['value'] ) ? sanitize_text_field( $_POST['value'] ) : null;

        // Configure the client data object(s).
        $this->init();
        $e20rMeasurements->setClient( $this->id );

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
            dbg("e20rClient::getMemberListForLevel() - Loading members for {$levels->name}");
            $data = $this->view->viewMemberSelect(  $levelId );
        }
        else {

            $this->view->load_levels();
            $data = $this->view->viewMemberSelect();
        }

        wp_send_json_success( $data );

    }

    function ajax_clientDetail() {
        dbg('Requesting client detail');

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-clients-nonce');

        dbg("Nonce is OK");

        dbg("Request: " . print_r($_REQUEST, true));

        $this->init();
        // $this->initClientViews();

        /*
        global $wpdb, $current_user, $e20rClient, $e20rMeasurements;

        // TODO: Don't init the client here. No need until it's actually used by something (i.e. in the has_weeklyProgress_shortcode, etc)
        if ( ! $e20rClient->client_loaded ) {
            dbg("e20rTracker::init() - Loading Client info");
            $e20rClient->init();
        }
        */


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

        dbg("e20rClient::validateAccess() - Client being validated: " . $clientId );

        if ( $clientId ) {

            dbg("Real user Id provided ");
            $client = get_user_by("id", $clientId );

            if ( ($current_user->ID != $clientId ) &&  ( $current_user->membership_level->id == 18 ) ) {
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