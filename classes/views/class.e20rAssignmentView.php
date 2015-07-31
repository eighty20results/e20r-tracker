<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rAssignmentView extends e20rSettingsView {

    private $assignments = null;

    public function __construct() {

        parent::__construct('assignment', 'e20r_assignments' );
    }

    public function setAssignment( $type, $config ) {

        $this->assignments[$type] = $config;

    }
    
    public function viewSettingsBox( $assignmentData, $answerTypes ) {

        global $e20rProgram;

        dbg( "e20rAssignmentView::viewSettingsBox() - Supplied data: " . print_r( $assignmentData, true ) );

        $pList = $e20rProgram->getProgramList();
        if ( is_null( $assignmentData->program_ids ) ) {
            $assignmentData->program_ids = array();
        }

        $hide_options = ( 4 == $assignmentData->field_type ) ? true : false;

        if ( !is_array( $assignmentData->select_options) && empty( $assignmentData->select_options ) ) {
            $assignmentData->select_options = array();
        }

        // ob_start();
        ?>
        <div id="e20r-editform">
            <?php wp_nonce_field( 'e20r-assignment-data', 'e20r-tracker-assignment-settings-nonce' ); ?>
            <input type="hidden" name="hidden-e20r-assignment-id" id="hidden-e20r-assignment-id"
                   value="<?php echo( ( ! empty( $assignmentData ) ) ? $assignmentData->id : 0 ); ?>">
            <table id="e20r-assignment-settings" class="wp-list-table widefat fixed">
                <thead>
                <tr>
                    <th class="e20r-label header"><label for="e20r-assignment-order_num">Order #</label></th>
                    <th class="e20r-label header"><label for="e20r-assignment-field_type">Answer type</label></th>
                    <th class="e20r-label header"><label for="e20r-assignment-delay">Day #</label></th>
                    <th class="e20r-label header"><label for="e20r-assignment-program_ids">Programs</label></th>
                </tr>
                <tr>
                    <td colspan="4">
                        <hr width="100%"/>
                    </td>
                </tr>
                </thead>
                <tbody>
                <tr id="<?php echo $assignmentData->id; ?>" class="assignment-inputs">
                    <td class="text-input">
                        <input type="number" id="e20r-assignment-order_num" name="e20r-assignment-order_num" value="<?php echo ( ! isset( $assignmentData->order_num ) ? 1 : $assignmentData->order_num ); ?>">
                    </td>
                    <td class="text-input" style="width: 50%;">
                        <select id="e20r-assignment-field_type" class="select2-container" name="e20r-assignment-field_type" style="width: 100%;">
                            <?php
                            foreach ( $answerTypes as $key => $descr ) { ?>
                                <option value="<?php echo $key; ?>" <?php selected( $assignmentData->field_type, $key ); ?>><?php echo $descr; ?></option><?php
                            }
                            ?>
                        </select>
                    </td>
                    <td class="text-input">
                        <input type="number" id="e20r-assignment-delay" name="e20r-assignment-delay" value="<?php echo ( ! isset( $assignmentData->delay ) ? '' : $assignmentData->delay ); ?>">
                    </td>
                    <td class="select-input">
                        <select class="select2-container" id="e20r-assignment-program_ids" name="e20r-assignment-program_ids" multiple="multiple">
                            <option value="-1" <?php echo empty( $assignmentData->program_ids) || in_array( -1, $assignmentData->program_ids) ? 'selected="selected"' : null; ?>><?php _e("No program defined", "e20rtracker");?></option><?php

                            foreach( $pList as $p ) { ?>
                                <option value="<?php echo $p->id;?>"<?php echo in_array( $p->id, $assignmentData->program_ids) ? 'selected="selected"' : null; ?>><?php echo esc_textarea($p->title);?></option><?php
                            } ?>
                        </select>
                        <style>
                            .select2-container {min-width: 75px; max-width: 300px; width: 90%;}
                        </style>
                        <script>
                            jQuery("#e20r-assignment-program_ids").select2();
                        </script>
                    </td>
                </tr>
                </tbody>
            </table>
            <hr class="e20r-assignment-separator" />
            <div id="e20r-assignment-option-div">
                <?php echo $this->viewOptionListTable( $assignmentData ); ?>
            </div>
        </div><?php
        // echo ob_get_clean();
    }

    public function viewOptionListTable( $assignmentData ) {

        $hide_options = ( 4 == $assignmentData->field_type ) ? true : false;
        ob_start();
        ?>
            <table id="e20r-assignment-options" class="wp-list-table widefat fixed" <?php echo ( $hide_options ? 'style="display: none;"' : null); ?>>
            <thead>
            <tr>
                <th class="e20r-label header">
                    <label for="e20r-assignment-select_option-checked">
                        <input type="checkbox" id="e20r-select_option-check_all" name="e20r-add-option-checkbox[]" value="all">
                    </label>
                </th>
                <th colspan="2" class="e20r-label header"><label for="e20r-assignment-select_options"><?php _e("Option", "e20rtracker"); ?></label></th>
            </tr>
            <tr>
                <td colspan="3">
                    <hr width="100%"/>
                </td>
            </tr>
            </thead>
            <tbody><?php

                if ( empty( $assignmentData->select_options ) ) { ?>
                <tr class="assignment-select-options">
                    <td class="e20r-row-counter checkbox"></td>
                    <td colspan="2" class="e20r-row-value"><?php _e("No options found", "e20rtracker" ); ?></td>
                </tr><?php
                }

                foreach( $assignmentData->select_options as $k => $option ) { ?>
                <tr class="assignment-select-options">
                    <td class="e20r-row-counter checkbox">
                        <input type="checkbox" class="e20r-checked-assignment-option" name="e20r-add-option-checkbox[]" value="<?php echo $k; ?>">
                    </td>
                    <td colspan="2" class="e20r-row-value">
                        <input type="hidden" name="e20r-option_key[]" id="e20r-option-key" value="<?php echo $k; ?>">
                        <input type="hidden" name="e20r-assignment-select_options[]" id="e20r-assignment-select_option_<?php echo $k; ?>" value="<?php echo !empty($option) || 0 != $option  ? $option : null; ?>">
                        <?php echo !empty($option) || 0 == $option  ? $option : null; ?>
                    </td>
                </tr><?php
                }?>
            </tbody>
            <tfoot>
            <tr>
                <td colspan="3">
                    <hr width="100%"/>
                </td>
            </tr>
            <tr>
                <td class="e20r-row-counter"></td>
                <td colspan="2" class="e20r-row-value text-input">
                    <input type="text" id="e20r-new-assignment-option" name="e20r-new-assignment-option" placeholder="Type option text">
                </td>
            </tr>
            <tr>
                <td class="e20r-add-assignment-manage-options">
                    <button class="button button-secondary" id="e20r-add-assignment-delete-option"><?php _e("Delete Option(s)", "e20rtracker"); ?></button>
                </td>
                <td class="e20r-add-assignment-manage-options">
                    <button class="button button-primary" id="e20r-add-assignment-save-option"><?php _e("Add Option", "e20rtracker"); ?></button>
                </td>

                <td class="e20r-add-assignment-manage-options">
                    <!-- <button class="button button-secondary" id="e20r-add-assignment-new-option"><?php _e("New Option", "e20rtracker"); ?></button> -->
                </td>
            </tr>
            </tfoot>
        </table><?php

        $html = ob_get_clean();
        return $html;
    }

    public function viewArticle_Assignments( $articleId = CONST_NULL_ARTICLE, $assignments, $answerDefs = null  ) {

        global $e20rAssignment;
		global $currentArticle;

        $multi_select = false;

        if ( ! current_user_can( 'edit_posts' ) ) {
            return false;
        }

        // $this->displayError();
        dbg("e20rAssignments::viewArticle_Assignments() - Processing " . count($assignments) . " assignments for display");

        ob_start();
        ?>
        <table id="e20r-tracker-article-assignment-list" class="wp-list-table widefat fixed">
            <thead>
            <tr>
                <th class="e20r-label header"><?php _e("Order", "e20rtracker"); ?></th>
                <th class="e20r-label header" style="width: 30%;"><?php _e("Assignment", "e20rtracker"); ?></th>
                <th class="e20r-label header" style="width: 30%;"><?php _e("Answer Type", "e20rtracker"); ?></th>
                <th colspan="2" class="e20r-label header"><?php _e("Operation", "e20rtracker"); ?></th>
            </tr>
            </thead>
            <tbody class="e20r-settings-list-tbody">
            <?php

            $count = 1;
            dbg("e20rAssignmentView::viewArticle_Assignments() - Processing previously defined assignment definitions.");

            foreach ( $assignments as $a ) {

                if ( !empty( $a->select_options ) ) {

                    $multi_select = true;
                }

                dbg("e20rAssignmentView::viewArticle_Assignments() - Processing assignment w/id: {$a->question_id}");
                dbg( $a );
                ?>

                <tr class="e20r-assignment-list">
                    <td class="e20r-assignment-hdr_order"><?php echo $a->order_num . "."; ?></td>
                    <td class="e20r-assignment-hdr_title"><?php echo $a->question; ?></td>
                    <td class="e20r-assignment-hdr_type">
                        <?php
                            dbg("e20rAssignmentView::viewArticle_Assignments() - field type = {$a->field_type}");
                            if ( $answerDefs !== null ) {

                                echo $answerDefs[ isset( $a->field_type ) ? $a->field_type : 0 ];
                            }
                            else {
                                echo $answerDefs[0];
                            }
                        ?>
                        <input type="hidden" class="e20r-assignment-type" name="e20r-assignment-field_type[]" value="<?php echo $a->field_type; ?>" />
                    </td>
                    <td class="e20r-assignment-buttons">
                        <a class="e20r-assignment-edit" href="javascript:e20r_assignmentEdit(<?php echo $a->question_id; ?>, <?php echo $a->order_num; ?>); void(0);"><?php _e("Edit", "e20rtracker"); ?></a>
                    </td>
                    <td class="e20r-assignment-buttons">
                        <a class="e20r-assignment-remove" href="javascript:e20r_assignmentRemove(<?php echo $a->question_id; ?>); void(0);"><?php _e("Remove", "e20rtracker"); ?></a>
                        <input type="hidden" class="e20r-assignment-id" name="e20r-assignment-id[]" value="<?php echo $a->question_id ?>" />
	                    <input type="hidden" class="e20r-article-assignment_ids" name="e20r-article-assignment_ids[]" value="<?php echo $a->question_id ?>" />
                    </td>
                </tr><?php
            } ?>
            </tbody>
        </table>
        <div id="postcustomstuff">
            <p><strong><?php _e('Add/Edit Assignments:', 'e20rtracker'); ?></strong></p>
            <table id="new-assignments">
                <thead>
                <tr>
                    <th id="new-assigment-header-order"><label for="e20r-add-assignment-order_num"><?php _e('Order', 'e20rtracker'); ?></label></th>
                    <th id="new-assignment-header-id"><label for="e20r-add-assignment-id"><?php _e('Select assignment', 'e20rtracker'); ?></label></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <input id="e20r-add-assignment-order_num" name="e20r-add-assignment-order_num" type="text" value="" size="5" />
                    </td>
                    <td>
                        <select class="e20r-select2-container select2" id="e20r-add-assignment-id" name="e20r-assignment-id">
							<option value="0">No defined Assignments</option><?php
                            dbg("e20rAssignmentView::viewArticle_Assignments() - Loading all possible assignments");

                            $all = $e20rAssignment->getAllAssignments();

                            dbg("e20rAssignmentView::viewArticle_Assignments() - Loaded " . count($all) . ' assignments');

                            foreach( $all as $id => $assignment ) {

	                            if ( ( $id != 0 ) && ( $assignment->delay == $currentArticle->release_day ) ) { ?>
		                            <option value="<?php echo $id; ?>"><?php echo $assignment->question . " (Day # {$assignment->delay})"; ?></option><?php
	                            }

                            } ?>
                        </select>
                    </td>
                    <td>
                        <button style="width: 100%; padding: 5px; vertical-align: baseline;" class="e20r-button" id="e20r-article-assignment-save" onclick="javascript:e20r_assignmentSave(); return false;"> <?php _e('Add', 'e20rtracker'); ?> </button>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    <?php

        dbg("e20rAssignmentView::viewArticle_Assignments() - Done generating metabox");

        $html = ob_get_clean();
        return $html;
    }

	public function viewAssignment( $assignmentData, $articleConfig ) {

		global $currentArticle;

        $articleComplete = $articleConfig->complete;
		$html = null;

		ob_start();

		?>
		<hr class="e20r-assignment-separator"/>
		<h2 class="e20r-daily-assignment-headline"><?php _e("Your Daily Assignment", "e20rtracker"); ?></h2>
		<div id="e20r-article-assignment">
			<form id="e20r-assignment-answers">
				<?php wp_nonce_field( 'e20r-tracker-data', 'e20r-tracker-assignment-answer' ); ?>
				<input type="hidden" value="<?php echo $currentArticle->id; ?>" name="e20r-article-id" id="e20r-article-id" />
				<input type="hidden" value="<?php echo $articleConfig->userId; ?>" name="e20r-article-user_id" id="e20r-article-user_id" />
				<input type="hidden" value="<?php echo $currentArticle->release_day; ?>" name="e20r-article-release_day" id="e20r-article-release_day" />
				<input type="hidden" value="<?php echo date('Y-m-d', current_time('timestamp') ); ?>" name="e20r-assignment-answer_date" id="e20r-assignment-answer_date" />
		<?php

		dbg("e20rAssignmentView::viewAssignment() - Processing for " . count($assignmentData) . " assignments");
		foreach( $assignmentData as $orderId => $assignment ) { ?>

                <input type="hidden" value="<?php echo ( isset( $assignment->id ) && ( 0 != $assignment->id ) ? $assignment->id : null ); ?>" name="e20r-assignment-id[]" class="e20r-assignment-id" /><?php

            dbg($assignment);

            if ( ( $assignment->field_type  == 0 ) && ( isset( $assignment->id ) ) &&
                ( !is_null( $assignment->id ) && ( 0 != $assignment->id ) ) ) {

                dbg("e20rAssignmentView::viewAssignment() - Forcing 'complete' to true since there's a non-zero/non-null assignment Id configured");
                $articleConfig->complete = true;
            }

			switch ( $assignment->field_type ) {

				case 1: // <input>
					echo $this->showAssignmentInput( $assignment );
					break;

				case 2: // <textbox>

					echo $this->showAssignmentParagraph( $assignment );
					break;

				case 3: // Checkbox ( array ?)

					break;

				case 4: // Multiple choice (select2 list) list of assignments
                    echo $this->showMultipleChoice( $assignment );
					break;

                case 5: // Likert/Survey (1 - 10)

                    echo $this->showAssignmentRanking( $assignment );
                    break;

                case 6: // Yes/No question

                    echo $this->showYesNoQuestion( $assignment );
                    break;

                default: // Button "Assignment read"
                    dbg("e20rAssignmentView::viewAssignment() - Default field_type value. Using showAssignmentButton()");
                    echo $this->showAssignmentButton( $assignment, $articleConfig->complete );

            }
		}

		dbg("e20rAssignmentView::viewAssignment() - Assignments: " . count($assignmentData) . " and last field type: {$assignment->field_type}");
		dbg("e20rAssignmentView::viewAssignment() - Is article assignment/check-in complete: {$articleConfig->complete}");

		if ( ( count($assignmentData) >= 1 )  && ($assignment->field_type != 0 ) ) {

            dbg("e20rAssignmentView::viewAssignment() -  Have assignment data to process.");

			if ( true != $articleConfig->complete ) { ?>
				<div id="e20r-assignment-save-btn"><?php
			}
			else { ?>
				<div id="e20r-assignment-save-btn" style="display:none;"><?php
            } ?>
				    <button id="e20r-assignment-save" class="e20r-button"><?php _e("Save Answers", 'e20rtracker'); ?></button>
				</div><?php
		}

        dbg("e20rAssignmentView::viewAssignment() - Config for article: ");
        dbg($articleConfig);

        if ( true == $articleConfig->complete ) { ?>

                <div id="e20r-assignment-complete"><?php
        }
        else {
            dbg("e20rAssignmentView::viewAssignment() -  Assignment isn't complete: " . ($articleConfig->complete ? 'Yes' : 'No'));?>

                <div id="e20r-assignment-complete" style="display: none;"><?php
        }
                    echo $this->assignmentComplete() ;?>
                </div>
			</form>
		</div>
		<hr class="e20r-assignment-separator"/>
		<?php
		$html .= ob_get_clean();
        dbg("e20rAssignmentView::viewAssignment() -  Returning HTML");
		return $html;
	}

    private function showMultipleChoice( $assignment ) {

        ob_start();

        if ( is_array( $assignment->answer ) ) {
            dbg("e20rAssignmentView::showMultipleChoice() - Answer is an array and contains: " . json_encode( $assignment->answer ));
            $answer = json_encode( $assignment->answer);
        }
        else {
            $answer = $assignment->answer;
        }

        ?>
        <div class="e20r-assignment-paragraph">
            <input type="hidden" value="<?php echo isset( $assignment->id ) ? $assignment->id : -1; ?>" name="e20r-assignment-record_id[]" class="e20r-assignment-record_id" />
            <input type="hidden" value="<?php echo $assignment->question_id; ?>" name="e20r-assignment-question_id[]" class="e20r-assignment-question_id" />
            <input type="hidden" value="<?php echo $assignment->field_type; ?>" name="e20r-assignment-field_type[]" class="e20r-assignment-field_type" />
            <h5 class="e20r-assignment-question"><?php echo $assignment->question; ?></h5><?php
            if ( isset( $assignment->descr ) && !empty( $assignment->descr ) ) { ?>
                <div class="e20r-assignment-descr">
                    <?php echo $assignment->descr; ?>
                    <p class="e20r-assignment-select"><?php _e("Select one or more applicable responses", "e20rtracker"); ?></p>
                </div><?php
            }?>
            <select class="select2-container e20r-select2-assignment-options e20r-assignment-response" name="e20r-multiplechoice_answer[]" multiple="multiple"><?php

                if ( !is_array( $assignment->answer ) ) {

                    $assignment->answer = array( $assignment->answer );
                }

                if ( empty( $assignment->answer ) || ( -1 == $assignment->answer[0] ) ) { ?>

                    <option value="-1"><?php _e("Not applicable", "e20rtracker"); ?></option><?php
                }

                foreach( $assignment->select_options as $option_id => $option_text ) {

                    $selected = ( !empty( $assignment->answer ) && in_array( $option_id, $assignment->answer ) ) ? 'selected="selected"' : null; ?>
                    <option value="<?php echo $option_id; ?>" <?php echo $selected; ?>><?php echo $option_text; ?></option><?php
                }?>
            </select>
            <input type="hidden" value="<?php echo $answer; ?>" name="e20r-assignment-answer[]" class="e20r-assignment-select2-hidden">
        </div><?php

        return ob_get_clean();
    }

    private function showAssignmentRanking( $assignment ) {

        ob_start();
        ?>
        <div class="e20r-assignment-ranking">
            <input type="hidden" value="<?php echo isset( $assignment->id ) ? $assignment->id : -1; ?>" name="e20r-assignment-record_id[]" class="e20r-assignment-record_id" />
            <input type="hidden" value="<?php echo $assignment->question_id; ?>" name="e20r-assignment-question_id[]" class="e20r-assignment-question_id" />
            <input type="hidden" value="<?php echo $assignment->field_type; ?>" name="e20r-assignment-field_type[]" class="e20r-assignment-field_type" />
            <h5 class="e20r-assignment-question"><?php echo $assignment->question; ?></h5><?php

            if ( isset( $assignment->descr ) && !empty( $assignment->descr ) ) { ?>
                <div class="e20r-assignment-descr"><?php echo $assignment->descr; ?></div><?php
            }?>
            <table class="e20r-assignment-ranking-question">
                <tbody>
                <tr><?php
                    foreach( range(1, 10) as $cnt ) { ?>
                        <td class="e20r-assignment-ranking-question-choice-label"><?php echo $cnt; ?></td><?php
                    }?>
                </tr>
                <tr><?php
                    foreach( range(1, 10) as $cnt ) {?>
                        <td class="e20r-assignment-ranking-question-choice">
                        <input name="e20r-assignment-answer[]" type="radio" class="e20r-assignment-response" value="<?php echo $cnt; ?>" tabindex="<?php echo $cnt; ?>" <?php checked( $assignment->answer, $cnt); ?>>
                        </td><?php
                    }?>
                </tr>
                </tbody>
            </table>
        </div><?php

        return ob_get_clean();
    }

    private function showYesNoQuestion( $assignment ) {

        ob_start();
        ?>
        <div class="e20r-assignment-ranking">
            <input type="hidden" value="<?php echo isset( $assignment->id ) ? $assignment->id : -1; ?>" name="e20r-assignment-record_id[]" class="e20r-assignment-record_id" />
            <input type="hidden" value="<?php echo $assignment->question_id; ?>" name="e20r-assignment-question_id[]" class="e20r-assignment-question_id" />
            <input type="hidden" value="<?php echo $assignment->field_type; ?>" name="e20r-assignment-field_type[]" class="e20r-assignment-field_type" />
            <h5 class="e20r-assignment-question"><?php echo $assignment->question; ?></h5><?php

            if ( isset( $assignment->descr ) && !empty( $assignment->descr ) ) { ?>
                <div class="e20r-assignment-descr"><?php echo $assignment->descr; ?></div><?php
            }?>
            <table class="e20r-assignment-ranking-question">
                <tbody>
                <tr>
                    <td class="e20r-assignment-ranking-question-choice-label"><?php _e("Yes", "e20rtracker"); ?></td>
                    <td class="e20r-assignment-ranking-question-choice-label"><?php _e("No", "e20rtracker"); ?></td>
                </tr>
                <tr>
                    <td class="e20r-assignment-ranking-question-choice">
                        <input class="e20r-assignment-response" name="e20r-assignment-answer[]" type="checkbox" value="yes" <?php checked( $assignment->answer, 'yes' );?>>
                    </td>
                    <td class="e20r-assignment-ranking-question-choice">
                        <input class="e20r-assignment-response" name="e20r-assignment-answer[]" type="checkbox" value="no" <?php checked( $assignment->answer, 'no' );?>>
                    </td>
                </tr>
                </tbody>
            </table>
        </div><?php

        return ob_get_clean();

    }

    private function showAssignmentInput( $assignment ) {
		ob_start();
		?>
		<div class="e20r-assignment-paragraph">
            <input type="hidden" value="<?php echo isset( $assignment->id ) ? $assignment->id : -1; ?>" name="e20r-assignment-record_id[]" class="e20r-assignment-record_id" />
			<input type="hidden" value="<?php echo $assignment->question_id; ?>" name="e20r-assignment-question_id[]" class="e20r-assignment-question_id" />
			<input type="hidden" value="<?php echo $assignment->field_type; ?>" name="e20r-assignment-field_type[]" class="e20r-assignment-field_type" />
			<h5 class="e20r-assignment-question"><?php echo $assignment->question; ?></h5><?php
			if ( isset( $assignment->descr ) && !empty( $assignment->descr ) ) { ?>
				<div class="e20r-assignment-descr"><?php echo $assignment->descr; ?></div><?php
			}?>
            <input type="text" class="e20r-assignment-response" name="e20r-assignment-answer[]" placeholder="Type your response" value="<?php echo $assignment->answer; ?>"/>
		</div>
		<?php
		return ob_get_clean();

	}

	private function showAssignmentParagraph( $assignment ) {

		ob_start();
		?>
		<div class="e20r-assignment-paragraph">
            <input type="hidden" value="<?php echo isset( $assignment->id ) ? $assignment->id : -1; ?>" name="e20r-assignment-record_id[]" class="e20r-assignment-record_id" />
			<input type="hidden" value="<?php echo $assignment->question_id; ?>" name="e20r-assignment-question_id[]" class="e20r-assignment-question_id" />
			<input type="hidden" value="<?php echo $assignment->field_type; ?>" name="e20r-assignment-field_type[]" class="e20r-assignment-field_type" />
			<h5 class="e20r-assignment-question"><?php echo $assignment->question; ?></h5><?php
			if ( ! empty( $assignment->descr ) ) { ?>
				<div class="e20r-assignment-descr"><?php echo $assignment->descr; ?></div><?php
			}?>
			<textarea class="e20r-assignment-response e20r-textarea" name="e20r-assignment-answer[]" rows="7" cols="80" placeholder="Type your response and click 'Complete', please..."><?php
				if ( ! empty( $assignment->answer ) ) {
					dbg("e20rAssignmentView::showAssignmentParagraph() - Loading actual answer...");
					echo trim(stripslashes($assignment->answer));
				}?></textarea>
		</div>
		<?php
		return ob_get_clean();
	}

	private function showAssignmentButton( $assignment, $isComplete = false ) {

        dbg("e20rAssignmentView::showAssignmentButton() - Using assignment configuration: ");

		ob_start();
		?>
        <input type="hidden" value="<?php echo isset( $assignment->id ) ? $assignment->id : -1; ?>" name="e20r-assignment-record_id[]" class="e20r-assignment-record_id" />
		<input type="hidden" value="<?php echo ( $assignment->question_id == 0 ? CONST_DEFAULT_ASSIGNMENT : $assignment->question_id ); ?>" name="e20r-assignment-question_id[]" class="e20r-assignment-question_id" />
		<input type="hidden" value="0" name="e20r-assignment-field_type[]" class="e20r-assignment-field_type" />
		<div class="e20r-lesson-highlight <?php echo ( $isComplete ? 'lesson-completed startHidden' : null ); ?>">
			<button id="e20r-lesson-complete" class="e20r-button assignment-btn"><?php _e("I have read this", "e20rtracker"); ?></button>
		</div>
		<?php
/*		if ( isset( $article->completed ) && ( $article->complete == true ) ) { ?>
		<div id="e20r-assignment-complete">
  <?php }
		else { ?>
		<div id="e20r-assignment-complete" style="display: none;">
  <?php } ?>
		<?php echo $this->assignmentComplete() ;?>
		</div><?php
*/
		$html = ob_get_clean();

		return $html;
	}

	public function assignmentComplete() {

		$html = '';

		ob_start();
		?>
		<div class="green-notice big" style="background-image: url( <?php echo E20R_PLUGINS_URL; ?>/images/checked.png ); margin: 12px 0pt; background-position: 24px 9px;">
			<p><strong><?php _e( "You have completed this lesson.", "e20rTracker" ); ?></strong></p>
		</div>
		<?php

		$html = ob_get_clean();
		return $html;
	}

	public function viewAssignmentList( $config, $answers ) {

		global $current_user;
		global $e20rTracker;
		global $e20rArticle;

		ob_start();
		?>
		<hr class="e20r-big-hr" />
		<div id="e20r-assignment-answer-list" class="e20r-measurements-container">
		<h4>Assignments</h4>
		<a class="close" href="#">X</a>
		<div class="quick-nav other">
			<table class="e20r-measurement-table">
				<tbody>
				<?php
				$counter = 0;
				if ( ! empty( $answers ) ) {

					dbg("e20rAssignmentView::viewAssignmentList() - User has supplied answers...");

					foreach ( $answers as $key => $answer ) {

						if ( $answer->field_type == 0 ) {

							dbg("e20rAssignmentView::viewAssignmentList() - {$answer->id} has no answer (It's a 'lesson complete' button).");
							continue;
						}

						$when     = date_i18n( "Y-m-d", strtotime( $e20rTracker->getDateForPost( $answer->delay ) ) );
						$showLink = ( $config->userId == $current_user->ID ? true : false );
						?>
						<tr class="<?php echo( ( $counter % 2 == 0 ) ? "e20rEven" : "e20rOdd" ) ?>">
							<td class="measurement-date">
								<div class="date">
									<form method="POST" action="">
										<input type="hidden" name="assignment_id" id="assignment_id" data-measurement-type="id" value="<?php echo $answer->id; ?>">
										<input type="hidden" name="date" id="delay" data-measurement-type="delay" value="<?php echo $answer->delay; ?>">
										<input type="hidden" name="article_id" id="article_id" data-measurement-type="article_id" value="<?php echo $answer->article_id; ?>">
										<input type="hidden" name="program_id" id="program_id" data-measurement-type="program_id" value="<?php echo $config->programId; ?>">
									</form>
									<!-- <span> -->
									<?php
									if ( $showLink ) {
                                        //dbg( "e20rAssignmentView::viewAssignmentList() - Want to show link for article {$answer->article_id}" );
										?>
										<a href="<?php echo $e20rArticle->getPostUrl( $answer->article_id ); ?>" target="_blank" alt="<?php _e( "Opens in a separate window", 'e20r-tracker' ); ?>">
											<?php echo date_i18n( 'M j, Y', strtotime( $e20rTracker->getDateForPost( $answer->delay ) ) ); ?>
										</a>
									<?php
									} else {
										?>
										<?php echo date_i18n( 'M j, Y', strtotime( $e20rTracker->getDateForPost( $answer->delay ) ) ); ?>
									<?php
									}
									?>
								</div>
								<div
									class="timeago timeagosize"><?php echo date_i18n( "Y/m/d", strtotime( $e20rTracker->getDateForPost( $answer->delay ) ) ); ?></div>
							</td>
							<td>
								<table class="e20r-answers">
									<tbody>
									<tr>
										<td>
											<div class="e20r-assignments-question">
												<?php echo stripslashes($answer->question); ?>
											</div>
										</td>
									</tr>
									<tr>
										<td>
											<div class="e20r-assignments-answer">
												<?php echo empty( $answer->answer ) ? __("No response recorded", "e20rtracker") : stripslashes($answer->answer); ?>
											</div>
										</td>
									</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<?php
						$counter ++;
					}
				}
				else { ?>
					<tr>
						<td colspan="2"><?php _e("No assignments or answers found.", "e20rtracker"); ?></td>
					</tr>
		<?php   } ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php

		$html = ob_get_clean();
		return $html;
	}
}