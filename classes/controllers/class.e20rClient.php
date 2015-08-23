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
            $this->model->load_basic_clientdata( $currentClient->user_id );
            $this->client_loaded = true;
        }

    }

	public function record_login( $user_login ) {

		$user = get_user_by( 'login', $user_login );
		update_user_meta( $user->ID, '_e20r-tracker-last-login', current_time('timestamp') );
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

        return $this->model->get_data( $currentClient->user_id, 'lengthunits');
    }

    public function getWeightUnit() {

        global $currentClient;

        return $this->model->get_data( $currentClient->user_id, 'weightunits' );
    }

    public function getBirthdate( $user_id ) {

        return $this->model->get_data( $user_id, 'birthdate' );
    }

    public function getUploadPath( $user_id ) {

        return $this->model->get_data( $user_id, 'program_photo_dir' );
    }

    public function getUserImgUrl( $who, $when, $imageSide ) {

        global $e20rTables;

        if ( $this->isNourishClient( $who ) &&  $e20rTables->isBetaClient() ) {

            return $this->model->getBetaUserUrl( $who, $when, $imageSide );
        }

        return false;
    }

    public function setClient( $userId ) {

        $this->client_loaded = false;
        $this->model->setUser( $userId );
        $this->init();
    }

    public function get_data( $clientId, $private = false, $basic = false ) {

        if ( ! $this->client_loaded ) {

            dbg("e20rClient::get_data() - No client data loaded yet...");
            $this->setClient( $clientId );
            // $this->get_data( $clientId );
        }

		if ( true === $basic ) {
            dbg("e20rClient::get_data() - Loading basic client data - not full survey.");
			$data = $this->model->load_basic_clientdata( $clientId );
		}
		else {
            dbg("e20rClient::get_data() - Loading all client data including survey");
			$data = $this->model->get_data($clientId);
		}

	    if ( true === $private ) {

		    dbg("e20rClient::get_data() - Removing private data");
		    $data = $this->strip_private_data( $data );
	    }

	    dbg("e20rClient::get_data() - Returned data for {$clientId} from client_info table:");
        // dbg($data);

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

			$data->display_birthdate = false;
		}

		if ( isset( $data->first_name ) && ( isset( $data->id ) ) && isset( $data->display_birthdate ) ) {

			$data->incomplete_interview = false;
		}

		return $data;
	}

	public function getGender() {

        global $currentClient;

		return strtolower( $this->model->get_data( $currentClient->user_id, 'gender') );
	}

	public function get_client_info( $client_id ) {

		return $this->model->load_client_settings( $client_id );
	}

    public function completeInterview( $userId ) {

        global $e20rTracker;

		dbg("e20rClient::completeInterview() - Checking if interview was completed");
        // $data = $this->model->get_data( $userId, 'completed_date');

		$is_complete = $this->model->interview_complete( $userId );

        // dbg("e20rClient::completeInterview() - completed_date field contains: ");
        // dbg($data);

        return ( !$is_complete ? false : true );
    }

	public function load_interview( $form ) {

		dbg("e20rTracker::load_interview() - Start" );

		global $e20rTracker;
		global $current_user;
		global $post;

        // dbg( $form );

		if ( stripos( $form['cssClass'], 'nourish-interview-identifier' ) === false ) {

			dbg('e20rTracker::load_interview()  - Not the BitBetter Interview form: ' . $form['cssClass']);
			return $form;
		}

		if ( ! is_user_logged_in() ) {

			dbg("e20rTracker::load_interview()  - User accessing form without being logged in.");
			return;
		}

		if ( ! $e20rTracker->hasAccess( $current_user->ID, $post->ID ) ) {

			dbg("e20rTracker::load_interview()  - User doesn't have access to this form.");
			return false;
		}

		dbg("e20rTracker::load_interview() - Loading form data: ");
		// dbg( "Form: " . print_r( $form, true) );

		return $this->loadClientInterviewData( $current_user->ID, $form );

		// return $form;
	}

    public function loadClientMessages( $clientId ) {

        global $currentClient;
        global $currentProgram;

        if ( !isset( $currentClient->user_id ) ) {
            $this->setClient($clientId);
        }

        if ( !isset( $currentProgram->id ) ) {
            global $e20rProgram;

            $e20rProgram->getProgramIdForUser( $clientId );
        }

        $client_messages = $this->model->load_message_history( $clientId );

        return $this->view->viewMessageHistory( $clientId, $client_messages );
    }

	public function loadClientInterviewData( $clientId, $form ) {

		$this->setClient($clientId);

		$c_data = $this->model->get_data( $clientId );

		dbg("e20rClient::loadClientInterviewData() - Client Data from DB:");
		// dbg($c_data);

		if ( isset($c_data->incomplete_interview) && ( 1 == $c_data->incomplete_interview ) ) {

			dbg("e20rClient::loadClientInterviewData() - No client data found in DB for user w/ID: {$clientId}");
			return $form;
		}

		$cFields = array( 'GF_Field_Radio', 'GF_Field_Checkbox', 'GF_Field', 'GF_Field_Select', 'GF_Field_MultiSelect' );
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

                if ( !is_array( $item['choices' ] ) ) {
                    dbg("e20rClient::loadClientInterviewData() - Processing {$classType} object {$item['label']} Isn't an array of values?");
                }

				foreach( $item['choices'] as $cId => $i ) {

					// Split any checkbox list values in $c_data by semicolon...
					$itArr = preg_split( '/;/', $c_data->{$item['label']} );

                    $msArr = preg_split( '/,/', $c_data->{$item['label']} );

                    if  ( empty( $msArr) && !isset( $c_data->{$item['label']} ) && empty( $c_data->{$item['label']} ) && ( 0 !== $c_data->{$item['label']} ) ) {
                        dbg("e20rClient::loadClientInterviewData() - {$item['label']} is empty? ". $c_data->{$item['label']});
                        continue;
                    }

                    // dbg("e20rClient::loadClientInterviewData() - Is value {$c_data->{$item['label']}} for object {$item['label']} numeric? " . ( is_numeric( $c_data->{$item['label']}) ? 'Yes' : 'No' ) );
					/** Process special cases where the DB field is numeric but the value in the form is text (Yes/No values) */
					if ( ( 'GF_Field_MultiSelect' != $classType ) && is_numeric( $c_data->{$item['label']}) && ( 'likert' != $item['inputType'] ) ) {

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

					if ( ( ( 0 === $c_data->{$item['label']} ) || !empty( $c_data->{$item['label']} ) ) && ( $c_data->{$item['label']} == $i[ $key ] ) ) {

						dbg( "e20rClient::loadClientInterviewData() - Choosing value " . $i[ $key ] . " for {$item['label']} - it's supposed to have key # {$cId}" );
						dbg( "e20rClient::loadClientInterviewData() - Form value: {$form['fields'][ $id ]['choices'][ $cId ][$key]}");

						$choiceField[ $cId ]['isSelected'] = 1;

					}

                    if ( is_array( $msArr ) && ( count( $msArr ) > 0) ) {

                        dbg( "e20rClient::loadClientInterviewData() - List of values. Processing {$choiceField[$cId][$key]}" );

                        if ( in_array( $choiceField[ $cId ][ $key ], $msArr ) ) {
                            dbg( "e20rClient::loadClientInterviewData() - Found {$i[$key]} as a saved value!" );
                            $choiceField[ $cId ]['isSelected'] = 1;
                        }
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

					dbg("e20rClient::loadClientInterviewData() - Restoring value: " . $c_data->{$item['label']}  . " for field: " . $item['label']);
                    $form['fields'][ $id ]['defaultValue'] = $c_data->{$item['label']};
				}
			}
		}

		return $form;
	}

    private function find_gf_id( $content ) {

        preg_match_all("/\[[^\]]*\]/", $content, $matches);

        foreach( $matches as $k => $match ) {

            foreach( $match as $sc ) {

                preg_match( '/id="(\d*)"/', $sc, $ids );
            }
        }

        return $ids;
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

        global $currentProgram;
        global $currentArticle;

        $ids = array();

        if ( has_shortcode( $post->post_content, 'gravityform' ) && ( $post->ID == $currentProgram->intake_form ) ) {

            dbg("e20rClient::remove_survey_form_entry() - Processing on the intake form page with the gravityform shortcode present");
            $ids = $this->find_gf_id( $post->post_content );
        }

        if ( has_shortcode( $post->post_content, 'gravityform' ) && ( $currentArticle->is_survey == 1) && ( $currentArticle->post_id == $post->ID ) ) {

            dbg("e20rClient::remove_survey_form_entry() - Processing on a survey article page with the gravityform shortcode present");
            $ids = $this->find_gf_id( $post->post_content );
        }

        if ( empty( $ids ) ) {

            dbg("e20rTracker::remove_survey_form_entry()  - This is not the BitBetter Interview form!");
            return;
        }

		if ( !is_user_logged_in() ) {

			dbg("e20rTracker::remove_survey_form_entry()  - User accessing form without being logged in.");
			return;
		}

		if ( ! $e20rTracker->hasAccess( $current_user->ID, $post->ID ) ) {

			dbg("e20rTracker::remove_survey_form_entry()  - User doesn't have access to this form.");
			return false;
		}

		global $wpdb;

		// Prepare variables.
		$lead_id                = $entry['id'];
		$lead_table             = RGFormsModel::get_lead_table_name();
		$lead_notes_table       = RGFormsModel::get_lead_notes_table_name();
		$lead_detail_table      = RGFormsModel::get_lead_details_table_name();
		$lead_detail_long_table = RGFormsModel::get_lead_details_long_table_name();

        dbg("e20rTracker::remove_survey_form_entry()  - Removing entries from the lead_detail_long_table");
		// Delete from lead detail long.
		$sql = $wpdb->prepare( "DELETE FROM $lead_detail_long_table WHERE lead_detail_id IN(SELECT id FROM $lead_detail_table WHERE lead_id = %d)", $lead_id );
		$wpdb->query( $sql );

        dbg("e20rTracker::remove_survey_form_entry()  - Removing entries from the lead_detail_table");
		// Delete from lead details.
		$sql = $wpdb->prepare( "DELETE FROM $lead_detail_table WHERE lead_id = %d", $lead_id );
		$wpdb->query( $sql );

		// Delete from lead notes.
        dbg("e20rTracker::remove_survey_form_entry()  - Removing entries from the lead_notes_table");
		$sql = $wpdb->prepare( "DELETE FROM $lead_notes_table WHERE lead_id = %d", $lead_id );
		$wpdb->query( $sql );

		// Delete from lead.
        dbg("e20rTracker::remove_survey_form_entry()  - Removing entries from the lead_table");
		$sql = $wpdb->prepare( "DELETE FROM $lead_table WHERE id = %d", $lead_id );
		$wpdb->query( $sql );

        dbg("e20rTracker::remove_survey_form_entry()  - Removing entries from any addons");
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
			return false;
		}

		if ( ! is_user_logged_in() ) {

			dbg("e20rTracker::save_interview()  - User accessing form without being logged in.");
			return false;
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

		global $currentProgram;
		global $currentArticle;

		$userId = $current_user->ID;
		$userProgramId = !empty( $currentProgram->id ) ? $currentProgram->id : $e20rProgram->getProgramIdForUser( $userId );
		$userProgramStart = $currentProgram->startdate;
		$eKey = $e20rTracker->getUserKey( $userId );
		$articleId = $currentArticle->id;

        dbg( $currentProgram );
        dbg( $currentArticle );

		$db_Data = array(
			'user_id' => $userId,
			'program_id' => $currentProgram->id,
            'page_id' => ( isset( $page->ID ) ? $page->ID : CONST_NULL_ARTICLE ),
			'article_id' => $currentArticle->id,
			'program_start' => $currentProgram->startdate,
	        'user_enc_key' => $eKey,
			'progress_photo_dir'=> "e20r_pics/client_{$userProgramId}_{$userId}"
		);

		$fieldList = array( 'text', 'textarea', 'number', 'email', 'phone' );

		dbg("e20rClient::save_interview() - Processing the Welcome Interview form");
/*        dbg($form['fields']);
        dbg($entry);
*/
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

						$db_Data[ $fieldName ] = join( ';', $checked );

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
							$db_Data[ $fieldName ] = $this->filterResponse( $val );
						}

					}

					$skip = true;
				}

                if ( $item['type'] == 'multiselect' ) {

                    dbg("e20rClient::save_interview() - Processing MultiSelect");

                    if ( isset( $entry[$subm_key]) ) {

//                        $selections = explode(",", $entry[$subm_key]);
//                        dbg( $selections );

                        dbg("e20rClient::save_interview() - Multiselect - Field: {$fieldName}, subm_key={$subm_key}, entryVal={$entry[$subm_key]}, item={$item['choices'][$k]['value']}");
                        $db_Data[ $fieldName ] = $this->filterResponse( $entry[$subm_key]);
                    }
                }

				if ( $item['type'] == 'select' ) {

					if ( !empty( $entry[$subm_key] ) ) {

						foreach ($item['choices'] as $k => $v ) {

							dbg("e20rClient::save_interview() - Select item: Field: {$fieldName}, subm_key={$subm_key}, entryVal={$entry[$subm_key]}, item={$item['choices'][$k]['value']}");

							if ( $item['choices'][$k]['value'] == $entry[$subm_key] ) {

								$db_Data[ $fieldName ] = $this->filterResponse( $item['choices'][$k]['value'] );
							}
						}
					}
				}

				if ( $item['type'] == 'radio' ) {

					if ( !empty( $entry[$subm_key] ) ) {

                        // Handle cases where Yes/No fields have 0/1 values.
                        dbg("e20rClient::save_interview() - Processing numeric radio button value: {$entry[$subm_key]}");
/*
                        if ( in_array( $entry[$subm_key], array( 'Yes', 'No' ) ) ) {

                            switch ( $entry[$subm_key] ) {
                                case 'No':
                                    $db_Data[ $fieldName ] = 0;
                                    break;

                                case 'Yes':
                                    $db_Data[ $fieldName ] = 1;
                                    break;
                            }
                            // $data[ $fieldName ] = $this->filterResponse( $entry[$subm_key] );
                            $skip = true;
                        }
*/
                        foreach ( $item['choices'] as $k => $v ) {

                            // dbg($item['choices'][$k]);

							if ( $item['choices'][ $k ]['value'] == $entry[ $subm_key ] ) {

                                dbg("e20rClient::save_interview() - Processing radio button: Value: {$item['choices'][ $k ]['value']}");
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
					dbg("e20rClient::save_interview() - Data being stored: .{$data}.");

					if ( 'textarea' == $item['type'] ) {

						$data = wp_kses( $data );
					}

					// Encrypt the data.
					$encData = $this->filterResponse( $data );
					$db_Data[ $fieldName ] = $encData;
				}
				else {
					dbg("e20rClient::save_interview() - Skipped field of type: {$item['type']} and with value: " . ( isset( $entry[ $item['id'] ] ) ? $entry[ $item['id'] ] : 'null' ) );
				}
			}
		} // End of foreach loop for submitted form

		if ( WP_DEBUG ) {
            dbg("e20rClient::save_interview() - Data to save: ");
            dbg($db_Data);
		}

		dbg("e20rClient::save_interview() - Saving the client interview data to the DB. ");

		if ( $this->model->save_client_interview( $db_Data ) ) {

			dbg("e20rClient::save_interview() - Saved data to the database ");

            dbg("e20rClient::save_interview() - Removing any GF entry data from database.");
            $this->remove_survey_form_entry( $entry );

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

        if ( ( 'multichoice' == $type ) && $field->allowsPrepopulate ) {

            $col_value = null;

            foreach( $field->choices as $i ) {

                if ( 1 == $i['isSelected'] ) {

                    return $i['value'];
                }
            }
        }

		if ( ( 'address' == $type ) && $field->allowsPrepopulate && !is_null( $currentClient->user_id ) ) {

			$val = $this->model->get_data($currentClient->user_id, $name );

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

		// Preserve \n in textboxes
		$data = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $data ) ) );
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
            $this->model->get_data( $user_id );

        }
        catch ( Exception $e ) {

            dbg("Error loading user data for ({$user_id}): " . $e->getMessage() );
        }

    }

    /**
     * Render page on back-end for client data (admin selectable).
     */
    public function render_client_page( $lvlName = '', $client_id = 0 ) {

        global $current_user;
        global $currentClient;
        global $currentProgram;

        global $e20rProgram;
        global $e20rTracker;

		if ( !$e20rTracker->is_a_coach( $current_user->ID ) ) {

			dbg("e20rClient::render_client_page() - User isn't a coach. Return error & force redirect");

            $error = '<div class="error">';
            $error .= '    <p>' . __("Sorry, as far as the Web Monkey knows, you are not a coach and will not be allowed to access the Coach's Page.", "e20rtracker") . '</p>';
            $error .= '</div><!-- /.error -->';

            $e20rTracker->updateSetting( 'unserialize_notice', $error );
			wp_redirect( admin_url() );
		}

		if ( $client_id != 0 ) {

			dbg("e20rClient::render_client_page() - Forcing client ID to {$client_id}");
            $this->setClient( $client_id );
        }

        $this->init();

        $this->model->get_data( $client_id );

        dbg("e20rClient::render_client_page() - Loading admin page for the Client {$client_id}");
        echo $this->view->viewClientAdminPage( $lvlName );
		dbg("e20rClient::render_client_page() - Admin page for client {$client_id} has been loaded");
    }

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

        dbg("e20rClient::getMemberListForLevel() - Program requested: {$levelId}");

        if ( $levelId != 0 ) {

            // $levels = $e20rTracker->getMembershipLevels( $levelId );
            // $this->load_levels( $levelObj->name );
            // dbg("e20rClient::getMemberListForLevel() - Loading members:");
	        // dbg($levels);

            $data = $this->view->viewMemberSelect(  $levelId );
        }
        else {

            // $this->view->load_levels();
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
<!--        <h1 style="padding:5px 0 0 0; font-family:georgia;font-weight:500;font-size:16px;color:#000;border-bottom:1px solid #bbb">
            <?php echo $subject; ?>
        </h1> -->
        <div id="the_content">
            <?php echo $content; ?>
        </div>
    </body>
</html><?php
        $html = ob_get_clean();
        return $html;
    }

    public function updateRoleForUser( $userId ) {

        // global $currentProgram;
        global $e20rTracker;

        if ( ! current_user_can( 'edit_user' ) ) {
            return false;
        }

        $role_name = isset( $_POST['e20r-tracker-user-role'] ) ? $e20rTracker->sanitize( $_POST['e20r-tracker-user-role'] ) : null;

        dbg("e20rTracker::updateRoleForUser() - Setting role name to: ({$role_name}) for user with ID of {$userId}");

        $u = get_user_by('id', $userId );

        if ( !is_null( $role_name ) ) {

            $u->add_role( $role_name );
        }
        else {
            if ( $u->has_cap( 'e20r_coach' ) ) {
                dbg("e20rTracker::updateRoleForUser() - Removing 'e20r_coach' capability/role for user {$userId}");
                $u->remove_cap( 'e20r_coach' );
            }

            // wp_die( "Unable to remove the {$role_name} role for this user ({$userId})" );
        }

        dbg("e20rTracker::updateRoleForUser() - User roles are now: ");
        dbg( $u->caps );

        return true;
    }

    public function selectRoleForUser( $user ) {

        dbg("e20rClient::selectRoleForUser() - Various roles & capabilities for user {$user->ID}");

        echo $this->view->view_userProfile( $user->ID );
    }

	public function schedule_email( $email_args, $when = null ) {

		if ( is_null( $when ) ) {
			dbg("e20rClient::schedule_email() - No need to schedule the email for transmission. We're sending it right away.");
			return $this->send_email_to_client( $email_args );
		}
		else {
			// Send message to user at specified time.
			dbg("e20rClient::schedule_email() - Schedule the email for transmission. {$when}");
			$ret = wp_schedule_single_event( $when, 'e20r_schedule_email_for_client', array( $email_args ));

			if ( is_null( $ret ) ) {
				return true;
			}
		}

		return false;
	}

	public function send_email_to_client( $email_array ) {

		dbg( $email_array );
		$headers[] = "Content-type: text/html";
		$headers[] = "Cc: " . $email_array['cc'];
		$headers[] = "From: " . $email_array['from'];

		$message = $this->createEmailBody( $email_array['subject'], $email_array['content'] );

		// $headers[] = "From: \"{$from_name}\" <{$from}>";

		dbg($email_array['to_email']);
		dbg($headers);
		dbg($email_array['subject']);
		dbg($message);

		add_filter('wp_mail', array( $this, 'test_wp_mail') );

		add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type') );

		$status = wp_mail( $email_array['to_email'], $email_array['subject'], $message, $headers, null );

		remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type') );

		if ( true ==  $status ) {
			dbg("e20rClient::ajax_sendClientMessage() - Successfully transferred the info to wp_mail()");

			if ( ! $this->model->save_message_to_history( $email_array['to_user']->ID, $email_array['program_id'], $email_array['from_uid'], $message, $email_array['subject'] ) ) {
				dbg("e20rClient::ajax_sendClientMessage() - Error while saving message history for {$email_array['to_user']->ID}");
				return false;
			}

			dbg("e20rClient::ajax_sendClientMessage() - Successfully saved the message to the user message history table");
			return true;
		}

		return false;
	}

    public function ajax_sendClientMessage() {

        global $e20rTracker;
        global $e20rProgram;

        global $currentProgram;

        $headers = array();
		$when = null;
        dbg('e20rClient::ajax_sendClientMessage() - Requesting client detail');

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-clients-nonce');

        dbg("e20rClient::ajax_sendClientMessage() - Nonce is OK");

        dbg("e20rClient::ajax_sendClientMessage() - Request: " . print_r($_REQUEST, true));

        // $to_uid = isset( $_POST['email-to-id']) ? $e20rTracker->sanitize( $_POST['email-to-id']) : null;
        $email_args['to_email'] = isset( $_POST['email-to'] ) ? $e20rTracker->sanitize( $_POST['email-to']) : null;
		$email_args['cc'] = isset( $_POST['email-cc'] ) ? $e20rTracker->sanitize( $_POST['email-cc']) : null;
		$email_args['from_uid'] = isset( $_POST['email-from-id'] ) ? $e20rTracker->sanitize( $_POST['email-from-id']) : null;
		$email_args['from'] = isset( $_POST['email-from'] ) ? $e20rTracker->sanitize( $_POST['email-from']) : null;
		$email_args['from_name'] = isset( $_POST['email-from-name'] ) ? $e20rTracker->sanitize( $_POST['email-from-name']) : null;
		$email_args['subject'] = isset( $_POST['subject'] ) ? $e20rTracker->sanitize( $_POST['subject']) : ' ';
		$email_args['content'] = isset( $_POST['content'] ) ? wp_kses_post( $_POST['content'] ) : null;
		$email_args['time'] = isset( $_POST['when-to-send'] ) ? $e20rTracker->sanitize( $_POST['when-to-send'] ) : null;
		$email_args['content'] = stripslashes_deep( $email_args['content'] );


		dbg("e20rClient::ajax_sendClientMessage() - Checking whether to schedule sending this message: {$email_args['time']}");
		if ( !empty( $email_args['time'] ) ) {

			if ( false === ( $when = strtotime( $email_args['time'] . " " . get_option('timezone_string') ) ) ) {
                wp_send_json_error( array( 'error' => 3 ) ); // 3 == 'Incorrect date/time provided'.
            }

            dbg("e20rClient::ajax_sendClientMessage() - Scheduled to be sent at: {$when}");
		}

		dbg("e20rClient::ajax_sendClientMessage() - Get the User info for the sender");
        if (! is_null( $email_args['from_uid'] ) ) {
            $f = get_user_by( 'id', $email_args['from_uid'] );
        }

		dbg("e20rClient::ajax_sendClientMessage() - Get the User info for the receiver");
        $email_args['to_user'] = get_user_by( 'email', $email_args['to_email'] );
        $e20rProgram->getProgramIdForUser( $email_args['to_user']->ID );

        $email_args['program_id'] = $currentProgram->id;

        // $sendTo = "{$to->display_name} <{$to_email}>";
		dbg("e20rClient::ajax_sendClientMessage() - Try to schedule the email for transmission");
		$status = $this->schedule_email( $email_args, $when );

		if ( true ==  $status ) {
            dbg("e20rClient::ajax_sendClientMessage() - Successfully scheduled the message to be sent");

            wp_send_json_success();
            wp_die();
        }

        dbg("e20rClient::ajax_sendClientMessage() - Error while scheduling message to be sent");
        wp_send_json_error();
    }

    public function set_html_content_type() {

        return 'text/html';
    }

    public function test_wp_mail( $args ) {

        $debug = var_export($args, true);
        dbg($debug);
    }

    public function ajax_ClientMessageHistory() {

        dbg('e20rClient::ajax_ClientMessageHistory() - Requesting client detail');

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-clients-nonce');

        dbg("e20rClient::ajax_ClientMessageHistory() - Nonce is OK");

        dbg("e20rClient::ajax_ClientMessageHistory() - Request: " . print_r($_REQUEST, true));

        global $current_user;
        global $e20rProgram;
        global $e20rTracker;

        global $currentProgram;
        global $currentClient;

        if ( !$e20rTracker->is_a_coach( $current_user->ID ) ) {

            dbg("e20rClient::ajax_showClientMessage() - User isn't a coach. Return error & force redirect");
            wp_send_json_error( array( 'error' => 403 ) );
        }

        $userId = isset( $_POST['client-id'] ) ? $e20rTracker->sanitize( $_POST['client-id']) : $current_user->ID;
        $e20rProgram->getProgramIdForUser( $userId );

        dbg("e20rClient::ajax_showClientMessage() - Loading message history from DB for {$userId}");
        $html = $this->loadClientMessages( $userId );

        dbg("e20rClient::ajax_showClientMessage() - Generating message history HTML");
        // $html = $this->view->viewMessageHistory( $userId, $messages );

        wp_send_json_success( array( 'html' => $html ) );
    }

    public function ajax_showClientMessage() {

        dbg('e20rClient::ajax_showClientMessage() - Requesting client detail');

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-clients-nonce');

        dbg("e20rClient::ajax_showClientMessage() - Nonce is OK");

        dbg("e20rClient::ajax_showClientMessage() - Request: " . print_r($_REQUEST, true));

        global $current_user;
        global $e20rProgram;
        global $e20rMeasurements;
        global $e20rArticle;

        global $e20rTracker;

        global $currentProgram;
        global $currentClient;

        if ( !$e20rTracker->is_a_coach( $current_user->ID ) ) {

            dbg("e20rClient::ajax_showClientMessage() - User isn't a coach. Return error & force redirect");
            wp_send_json_error( array( 'error' => 403 ) );
        }

        $userId = isset( $_POST['client-id'] ) ? $e20rTracker->sanitize( $_POST['client-id']) : $current_user->ID;
        $e20rProgram->getProgramIdForUser( $userId );

        $articles = $e20rArticle->findArticles( 'post_id', $currentProgram->intake_form, 'numeric', $currentProgram->id );
        $a = $articles[0];

        dbg("e20rClient::ajax_showClientMessage() - Article ID: ");
        dbg( $a->id );

        if ( !$e20rArticle->isSurvey( $a->id ) ) {
            wp_send_json_error( array( 'error' => 'Configuration error. Please report to tech support.' ) );
        }
        else {
            dbg("e20rClient::ajax_showClientMessage() - Loading article configuration for the survey!");
            $e20rArticle->init( $a->id );
        }

        dbg("e20rClient::ajax_showClientMessage() - Load client data...");

        // Loads the program specific client information we've got stored.
        $this->model->get_data( $userId );

        $html = $this->view->viewClientContact( $userId );

        wp_send_json_success( array( 'html' => $html ));
    }

	public function ajax_clientDetail() {

		global $current_user;
        global $e20rTracker;

		if ( !is_user_logged_in() ) {

			dbg("e20rClient::ajax_clientDetail() - User isn't logged in. Return error & force redirect");
			wp_send_json_error( array( 'error' => 403 ) );
		}

		if ( !$e20rTracker->is_a_coach( $current_user->ID ) ) {

			dbg("e20rClient::ajax_clientDetail() - User isn't a coach. Return error & force redirect");
			wp_send_json_error( array( 'error' => 403 ) );
		}
		dbg('e20rClient::ajax_clientDetail() - Requesting client detail');

		check_ajax_referer('e20r-tracker-data', 'e20r-tracker-clients-nonce');

		dbg("e20rClient::ajax_clientDetail() - Nonce is OK");

		dbg("e20rClient::ajax_clientDetail() - Request: " . print_r($_REQUEST, true));

		global $e20rProgram;
		global $e20rMeasurements;
        global $e20rArticle;

		global $currentProgram;
        global $currentArticle;
		global $currentClient;

		$userId = isset( $_POST['client-id'] ) ? $e20rTracker->sanitize( $_POST['client-id']) : $current_user->ID;
		$type = isset( $_POST['tab-id'] ) ? $e20rTracker->sanitize( $_POST['tab-id']) : 'client-info';
		$e20rProgram->getProgramIdForUser( $userId );

        $articles = $e20rArticle->findArticles( 'post_id', $currentProgram->intake_form, 'numeric', $currentProgram->id );
        $a = $articles[0];

        dbg("e20rClient::ajax_clientDetail() - Article ID: ");
        dbg( $a->id );

        if ( !$e20rArticle->isSurvey( $a->id ) ) {
            wp_send_json_error( array( 'error' => 'Configuration error. Please report to tech support.' ) );
        }
        else {
            dbg("e20rClient::ajax_clientDetail() - Loading article configuration for the survey!");
            $e20rArticle->init( $a->id );
        }

		switch ( $type ) {
			case 'client-info':
                dbg("e20rClient::ajax_clientDetail() - Loading client data");
				$html = $this->load_clientDetail( $userId, $currentProgram->id, $currentArticle->id );
				break;

			case 'achievements':
                dbg("e20rClient::ajax_clientDetail() - Loading client achievement data");
				$html = $this->load_achievementsData( $userId );
                // dbg($html);
				break;

			case 'assignments':
                dbg("e20rClient::ajax_clientDetail() - Loading client assignment data");
				$html = $this->load_assignmentsData( $userId );
				break;

            case 'activities':
                dbg("e20rClient::ajax_clientDetail() - Loading client activity data");
                $html = $this->load_activityData( $userId );
                break;

			default:
                dbg("e20rClient::ajax_clientDetail() - Default: Loading client information");
				$html = $this->load_clientDetail( $userId );
		}

		wp_send_json_success( array( 'html' => $html ));
	}

	public function load_clientDetail( $clientId ) {

		dbg("e20rClient::load_clientDetail() - Load client data...");
        global $e20rProgram;
        global $e20rArticle;

        global $currentProgram;
        global $currentArticle;

        dbg("e20rClient::load_clientDetail() - Load program info for this clientID.");
        // Loads the program specific client information we've got stored.
        $e20rProgram->getProgramIdForUser( $clientId );

        if ( empty( $currentProgram->id ) ) {
            dbg("e20rClient::load_clientDetail() - ERROR: No program ID defined for user {$clientId}!!!");
            return null;
        }

        dbg("e20rClient::load_clientDetail() - Find article ID for the intake form {$currentProgram->intake_form} for the program ({$currentProgram->id}).");
        $article = $e20rArticle->findArticles('post_id', $currentProgram->intake_form, 'numeric', $currentProgram->id );

        dbg("e20rClient::load_clientDetail() - Returned " . count($article) . " articles on behalf of the intake form");

        if ( !empty( $article ) ) {

            dbg("e20rClient::load_clientDetail() - Load article configuration.");
            dbg( $article[0] );
            $e20rArticle->init( $article[0]->id );
        }
        else {
            dbg("e20rClient::load_clientDetail() - ERROR: No article defined for the Welcome Survey!!!");
            return null;
        }

        dbg("e20rClient::load_clientDetail() - Load the client information for {$clientId} in program {$currentProgram->id} for article {$currentArticle->id}");
        $this->model->get_data( $clientId );

        dbg("e20rClient::ajax_clientDetail() - Show client detail for {$clientId} related to {$currentArticle->id} and {$currentProgram->id}");
        return $this->view->viewClientDetail( $clientId );
    }

    function load_achievementsData( $clientId ) {

		global $e20rCheckin;

        return $e20rCheckin->listUserAccomplishments( $clientId );
    }

    function load_assignmentsData( $clientId ) {

		global $e20rAssignment;

        return $e20rAssignment->listUserAssignments( $clientId );
    }

    function load_activityData( $clientId ) {

        global $e20rWorkout;

        return $e20rWorkout->listUserActivities( $clientId );
    }

    public function validateAccess( $clientId ) {

        global $current_user;
		global $e20rTracker;

        dbg("e20rClient::validateAccess() - Client being validated: " . $clientId );

        if ( $clientId ) {

            $client = get_user_by( 'id', $clientId );
            dbg("e20rClient::validateAccess() - Real user Id provided ");

            if ( ($current_user->ID != $clientId ) &&
                ( ( $e20rTracker->isActiveClient( $clientId )  ) ||
                ( $e20rTracker->is_a_coach( $current_user->ID ) ) ) ) {

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