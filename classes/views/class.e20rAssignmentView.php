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

		parent::__construct( 'assignment', 'e20r_assignments' );
	}

	public function setAssignment( $type, $config ) {

		$this->assignments[ $type ] = $config;

	}

	public function viewSettingsBox( $assignmentData, $answerTypes ) {

		global $e20rProgram;

		dbg( "e20rAssignmentView::viewSettingsBox() - Supplied data: " . print_r( $assignmentData, true ) );

		$pList = $e20rProgram->getProgramList();
		if ( is_null( $assignmentData->program_ids ) ) {
			$assignmentData->program_ids = array();
		}

		$hide_options = ( 4 == $assignmentData->field_type ) ? true : false;

		if ( ! is_array( $assignmentData->select_options ) && empty( $assignmentData->select_options ) ) {
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
					<input type="number" id="e20r-assignment-order_num" name="e20r-assignment-order_num"
					       value="<?php echo( ! isset( $assignmentData->order_num ) ? 1 : $assignmentData->order_num ); ?>">
				</td>
				<td class="text-input" style="width: 50%;">
					<select id="e20r-assignment-field_type" class="select2-container" name="e20r-assignment-field_type"
					        style="width: 100%;">
						<?php
						foreach ( $answerTypes as $key => $descr ) { ?>
							<option
							value="<?php echo $key; ?>" <?php selected( $assignmentData->field_type, $key ); ?>><?php echo $descr; ?></option><?php
						}
						?>
					</select>
				</td>
				<td class="text-input">
					<input type="number" id="e20r-assignment-delay" name="e20r-assignment-delay"
					       value="<?php echo( ! isset( $assignmentData->delay ) ? '' : $assignmentData->delay ); ?>">
				</td>
				<td class="select-input">
					<select class="select2-container" id="e20r-assignment-program_ids"
					        name="e20r-assignment-program_ids" multiple="multiple">
						<option
							value="-1" <?php echo empty( $assignmentData->program_ids ) || in_array( - 1, $assignmentData->program_ids ) ? 'selected="selected"' : null; ?>><?php _e( "No program defined", "e20rtracker" ); ?></option><?php

						foreach ( $pList as $p ) { ?>
							<option value="<?php echo $p->id; ?>"<?php echo in_array( $p->id, $assignmentData->program_ids ) ? 'selected="selected"' : null; ?>><?php echo esc_textarea( $p->title ); ?></option><?php
						} ?>
					</select>
					<style>
						.select2-container {
							min-width: 75px;
							max-width: 300px;
							width: 90%;
						}
					</style>
					<script>
						jQuery("#e20r-assignment-program_ids").select2();
					</script>
				</td>
			</tr>
			</tbody>
		</table>
		<hr class="e20r-assignment-separator"/>
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
	<table id="e20r-assignment-options"
	       class="wp-list-table widefat fixed" <?php echo( $hide_options ? 'style="display: none;"' : null ); ?>>
		<thead>
		<tr>
			<th class="e20r-label header">
				<label for="e20r-assignment-select_option-checked">
					<input type="checkbox" id="e20r-select_option-check_all" name="e20r-add-option-checkbox[]"
					       value="all">
				</label>
			</th>
			<th colspan="2" class="e20r-label header"><label
					for="e20r-assignment-select_options"><?php _e( "Option", "e20rtracker" ); ?></label></th>
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
			<td colspan="2" class="e20r-row-value"><?php _e( "No options found", "e20rtracker" ); ?></td>
			</tr><?php
		}

		foreach ( $assignmentData->select_options as $k => $option ) { ?>
			<tr class="assignment-select-options">
			<td class="e20r-row-counter checkbox">
				<input type="checkbox" class="e20r-checked-assignment-option" name="e20r-add-option-checkbox[]"
				       value="<?php echo $k; ?>">
			</td>
			<td colspan="2" class="e20r-row-value">
				<input type="hidden" name="e20r-option_key[]" id="e20r-option-key" value="<?php echo $k; ?>">
				<input type="hidden" name="e20r-assignment-select_options[]"
				       id="e20r-assignment-select_option_<?php echo $k; ?>"
				       value="<?php echo ! empty( $option ) || 0 != $option ? $option : null; ?>">
				<?php echo ! empty( $option ) || 0 == $option ? $option : null; ?>
			</td>
			</tr><?php
		} ?>
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
				<input type="text" id="e20r-new-assignment-option" name="e20r-new-assignment-option"
				       placeholder="Type option text">
			</td>
		</tr>
		<tr>
			<td class="e20r-add-assignment-manage-options">
				<button class="button button-secondary"
				        id="e20r-add-assignment-delete-option"><?php _e( "Delete Option(s)", "e20rtracker" ); ?></button>
			</td>
			<td class="e20r-add-assignment-manage-options">
				<button class="button button-primary"
				        id="e20r-add-assignment-save-option"><?php _e( "Add Option", "e20rtracker" ); ?></button>
			</td>

			<td class="e20r-add-assignment-manage-options">
				<!-- <button class="button button-secondary" id="e20r-add-assignment-new-option"><?php _e( "New Option", "e20rtracker" ); ?></button> -->
			</td>
		</tr>
		</tfoot>
		</table><?php

		$html = ob_get_clean();

		return $html;
	}

	public function viewArticle_Assignments( $articleId = CONST_NULL_ARTICLE, $assignments, $answerDefs = null ) {

		global $e20rAssignment;
		global $currentArticle;

		$multi_select = false;

		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		// $this->displayError();
		dbg( "e20rAssignments::viewArticle_Assignments() - Processing " . count( $assignments ) . " assignments for display" );

		ob_start();
		?>
		<table id="e20r-tracker-article-assignment-list" class="wp-list-table widefat fixed">
			<thead>
			<tr>
				<th class="e20r-label header"><?php _e( "Order", "e20rtracker" ); ?></th>
				<th class="e20r-label header" style="width: 30%;"><?php _e( "Assignment", "e20rtracker" ); ?></th>
				<th class="e20r-label header" style="width: 30%;"><?php _e( "Answer Type", "e20rtracker" ); ?></th>
				<th colspan="2" class="e20r-label header"><?php _e( "Operation", "e20rtracker" ); ?></th>
			</tr>
			</thead>
			<tbody class="e20r-settings-list-tbody">
			<?php

			$count = 1;
			dbg( "e20rAssignmentView::viewArticle_Assignments() - Processing previously defined assignment definitions." );

			foreach ( $assignments as $a ) {

				if ( ! empty( $a->select_options ) ) {

					$multi_select = true;
				}

				dbg( "e20rAssignmentView::viewArticle_Assignments() - Processing assignment w/id: {$a->question_id}" );
				dbg( $a );
				?>

				<tr class="e20r-assignment-list">
				<td class="e20r-assignment-hdr_order"><?php echo $a->order_num . "."; ?></td>
				<td class="e20r-assignment-hdr_title"><?php echo $a->question; ?></td>
				<td class="e20r-assignment-hdr_type">
					<?php
					dbg( "e20rAssignmentView::viewArticle_Assignments() - field type = {$a->field_type}" );
					if ( $answerDefs !== null ) {

						echo $answerDefs[ isset( $a->field_type ) ? $a->field_type : 0 ];
					} else {
						echo $answerDefs[0];
					}
					?>
					<input type="hidden" class="e20r-assignment-type" name="e20r-assignment-field_type[]"
					       value="<?php echo $a->field_type; ?>"/>
				</td>
				<td class="e20r-assignment-buttons">
					<a class="e20r-assignment-edit"
					   href="javascript:e20r_assignmentEdit(<?php echo $a->question_id; ?>, <?php echo $a->order_num; ?>); void(0);"><?php _e( "Edit", "e20rtracker" ); ?></a>
				</td>
				<td class="e20r-assignment-buttons">
					<a class="e20r-assignment-remove"
					   href="javascript:e20r_assignmentRemove(<?php echo $a->question_id; ?>); void(0);"><?php _e( "Remove", "e20rtracker" ); ?></a>
					<input type="hidden" class="e20r-assignment-id" name="e20r-assignment-id[]"
					       value="<?php echo $a->question_id ?>"/>
					<input type="hidden" class="e20r-article-assignment_ids" name="e20r-article-assignment_ids[]"
					       value="<?php echo $a->question_id ?>"/>
				</td>
				</tr><?php
			} ?>
			</tbody>
		</table>
		<div id="postcustomstuff">
			<p><strong><?php _e( 'Add/Edit Assignments:', 'e20rtracker' ); ?></strong></p>
			<table id="new-assignments">
				<thead>
				<tr>
					<th id="new-assigment-header-order"><label
							for="e20r-add-assignment-order_num"><?php _e( 'Order', 'e20rtracker' ); ?></label></th>
					<th id="new-assignment-header-id"><label
							for="e20r-add-assignment-id"><?php _e( 'Select assignment', 'e20rtracker' ); ?></label></th>
					<th></th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td>
						<input id="e20r-add-assignment-order_num" name="e20r-add-assignment-order_num" type="text"
						       value="" size="5"/>
					</td>
					<td>
						<select class="e20r-select2-container select2" id="e20r-add-assignment-id"
						        name="e20r-assignment-id">
							<option value="0">No defined Assignments</option><?php
							dbg( "e20rAssignmentView::viewArticle_Assignments() - Loading all possible assignments" );

							$all = $e20rAssignment->getAllAssignments();

							dbg( "e20rAssignmentView::viewArticle_Assignments() - Loaded " . count( $all ) . ' assignments' );

							foreach ( $all as $id => $assignment ) {

								if ( ( $id != 0 ) && ( $assignment->delay == $currentArticle->release_day ) ) { ?>
									<option
									value="<?php echo $id; ?>"><?php echo $assignment->question . " (Day # {$assignment->delay}/{$assignment->order_num})"; ?></option><?php
								}

							} ?>
						</select>
					</td>
					<td>
						<button style="width: 100%; padding: 5px; vertical-align: baseline;" class="e20r-button"
						        id="e20r-article-assignment-save"
						        onclick="javascript:e20r_assignmentSave(); return false;"> <?php _e( 'Add', 'e20rtracker' ); ?> </button>
					</td>
				</tr>
				</tbody>
			</table>
		</div>
		<?php

		dbg( "e20rAssignmentView::viewArticle_Assignments() - Done generating metabox" );

		$html = ob_get_clean();

		return $html;
	}

	public function viewAssignment( $assignmentData, $articleConfig ) {

		global $currentArticle;

		$articleComplete = $articleConfig->complete;
		$html            = null;

		ob_start();

		?>
		<hr class="e20r-assignment-separator"/>
		<h2 class="e20r-daily-assignment-headline"><?php _e( "Your Daily Assignment", "e20rtracker" ); ?></h2>
		<div id="e20r-article-assignment">
			<form id="e20r-assignment-answers">
				<?php wp_nonce_field( 'e20r-tracker-data', 'e20r-tracker-assignment-answer' ); ?>
				<input type="hidden" value="<?php echo $currentArticle->id; ?>" name="e20r-article-id"
				       id="e20r-article-id"/>
				<input type="hidden" value="<?php echo $articleConfig->userId; ?>" name="e20r-article-user_id"
				       id="e20r-article-user_id"/>
				<input type="hidden" value="<?php echo $currentArticle->release_day; ?>" name="e20r-article-release_day"
				       id="e20r-article-release_day"/>
				<input type="hidden" value="<?php echo date( 'Y-m-d', current_time( 'timestamp' ) ); ?>"
				       name="e20r-assignment-answer_date" id="e20r-assignment-answer_date"/>
				<?php

				dbg( "e20rAssignmentView::viewAssignment() - Processing for " . count( $assignmentData ) . " assignments" );
				foreach ( $assignmentData as $orderId => $assignment ) { ?>

					<input type="hidden"
					       value="<?php echo( isset( $assignment->id ) && ( 0 != $assignment->id ) ? $assignment->id : null ); ?>"
					       name="e20r-assignment-id[]" class="e20r-assignment-id" /><?php

					dbg( $assignment );

					if ( ( $assignment->field_type == 0 ) && ( isset( $assignment->id ) ) &&
					     ( ! is_null( $assignment->id ) && ( 0 != $assignment->id ) )
					) {

						dbg( "e20rAssignmentView::viewAssignment() - Forcing 'complete' to true since there's a non-zero/non-null assignment Id configured" );
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

						case 7: // Display the HTML included
							echo $this->showHTMLField( $assignment );
							break;

						default: // Button "Assignment read"
							dbg( "e20rAssignmentView::viewAssignment() - Default field_type value. Using showAssignmentButton()" );
							echo $this->showAssignmentButton( $assignment, $articleConfig->complete );

					}
				}

				dbg( "e20rAssignmentView::viewAssignment() - Assignments: " . count( $assignmentData ) . " and last field type: {$assignment->field_type}" );
				dbg( "e20rAssignmentView::viewAssignment() - Is article assignment/check-in complete: {$articleConfig->complete}" );

				if ( ( count( $assignmentData ) >= 1 ) && ( $assignment->field_type != 0 ) ) {

				dbg( "e20rAssignmentView::viewAssignment() -  Have assignment data to process." );

				if ( isset( $articleConfig->complete ) && true != $articleConfig->complete ) { ?>
				<div id="e20r-assignment-save-btn"><?php
					}
					else { ?>
					<div id="e20r-assignment-save-btn" style="display:none;"><?php
						} ?>
						<button id="e20r-assignment-save"
						        class="e20r-button"><?php _e( "Save Answers", 'e20rtracker' ); ?></button>
					</div><?php
					}

					dbg( "e20rAssignmentView::viewAssignment() - Config for article: " );
					dbg( $articleConfig );

					if ( true == $articleConfig->complete ) { ?>

					<div class="e20r-assignment-complete"><?php
						}
						else {
						dbg( "e20rAssignmentView::viewAssignment() -  Assignment isn't complete: " . ( $articleConfig->complete ? 'Yes' : 'No' ) ); ?>

						<div class="e20r-assignment-complete" style="display: none;"><?php
							}
							echo $this->assignmentComplete(); ?>
						</div>
			</form>
		</div>
		<hr class="e20r-assignment-separator"/>
		<?php
		$html .= ob_get_clean();
		dbg( "e20rAssignmentView::viewAssignment() -  Returning HTML" );

		return $html;
	}

	private function showHtmlField( $assignment ) {

		ob_start();

		?>
		<div class="e20r-assignment-html-data">
			<?php echo esc_html( $assignment->descr ); ?>
		</div>
		<?php

		return ob_get_clean();
	}

	private function showMultipleChoice( $assignment ) {

		ob_start();

		if ( is_array( $assignment->answer ) ) {
			dbg( "e20rAssignmentView::showMultipleChoice() - Answer is an array and contains: " . json_encode( $assignment->answer ) );
			$answer = json_encode( $assignment->answer );
		} else {
			$answer = $assignment->answer;
		}

		?>
		<div class="e20r-assignment-paragraph">
		<input type="hidden" value="<?php echo isset( $assignment->id ) ? $assignment->id : - 1; ?>"
		       name="e20r-assignment-record_id[]" class="e20r-assignment-record_id"/>
		<input type="hidden" value="<?php echo $assignment->question_id; ?>" name="e20r-assignment-question_id[]"
		       class="e20r-assignment-question_id"/>
		<input type="hidden" value="<?php echo $assignment->field_type; ?>" name="e20r-assignment-field_type[]"
		       class="e20r-assignment-field_type"/>
		<h5 class="e20r-assignment-question"><?php echo $assignment->question; ?></h5><?php
		if ( isset( $assignment->descr ) && ! empty( $assignment->descr ) ) { ?>
			<div class="e20r-assignment-descr">
			<?php echo $assignment->descr; ?>
			<p class="e20r-assignment-select"><?php _e( "Select one or more applicable responses", "e20rtracker" ); ?></p>
			</div><?php
		} ?>
		<select class="select2-container e20r-select2-assignment-options e20r-assignment-response"
		        name="e20r-multiplechoice_answer[]" multiple="multiple"><?php

			if ( ! is_array( $assignment->answer ) ) {

				$assignment->answer = array( $assignment->answer );
			}

			if ( empty( $assignment->answer ) || ( - 1 == $assignment->answer[0] ) ) { ?>

				<option value="-1"><?php _e( "Not applicable", "e20rtracker" ); ?></option><?php
			}

			foreach ( $assignment->select_options as $option_id => $option_text ) {

				$selected = ( ! empty( $assignment->answer ) && in_array( $option_id, $assignment->answer ) ) ? 'selected="selected"' : null; ?>
				<option
				value="<?php echo $option_id; ?>" <?php echo $selected; ?>><?php echo $option_text; ?></option><?php
			} ?>
		</select>
		<input type="hidden" value="<?php echo $answer; ?>" name="e20r-assignment-answer[]"
		       class="e20r-assignment-select2-hidden">
		</div><?php

		return ob_get_clean();
	}

	private function showAssignmentRanking( $assignment ) {

		ob_start();
		?>
		<div class="e20r-assignment-ranking">
		<input type="hidden" value="<?php echo isset( $assignment->id ) ? $assignment->id : - 1; ?>"
		       name="e20r-assignment-record_id[]" class="e20r-assignment-record_id"/>
		<input type="hidden" value="<?php echo $assignment->question_id; ?>" name="e20r-assignment-question_id[]"
		       class="e20r-assignment-question_id"/>
		<input type="hidden" value="<?php echo $assignment->field_type; ?>" name="e20r-assignment-field_type[]"
		       class="e20r-assignment-field_type"/>
		<h5 class="e20r-assignment-question"><?php echo $assignment->question; ?></h5><?php

		if ( isset( $assignment->descr ) && ! empty( $assignment->descr ) ) { ?>
			<div class="e20r-assignment-descr"><?php echo $assignment->descr; ?></div><?php
		} ?>
		<table class="e20r-assignment-ranking-question">
			<tbody>
			<tr><?php
				foreach ( range( 1, 10 ) as $cnt ) { ?>
					<td class="e20r-assignment-ranking-question-choice-label"><?php echo $cnt; ?></td><?php
				} ?>
			</tr>
			<tr><?php
				foreach ( range( 1, 10 ) as $cnt ) { ?>
					<td class="e20r-assignment-ranking-question-choice">
					<input name="e20r-assignment-answer[]" type="radio" class="e20r-assignment-response"
					       value="<?php echo $cnt; ?>"
					       tabindex="<?php echo $cnt; ?>" <?php checked( $assignment->answer, $cnt ); ?>>
					</td><?php
				} ?>
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
		<input type="hidden" value="<?php echo isset( $assignment->id ) ? $assignment->id : - 1; ?>"
		       name="e20r-assignment-record_id[]" class="e20r-assignment-record_id"/>
		<input type="hidden" value="<?php echo $assignment->question_id; ?>" name="e20r-assignment-question_id[]"
		       class="e20r-assignment-question_id"/>
		<input type="hidden" value="<?php echo $assignment->field_type; ?>" name="e20r-assignment-field_type[]"
		       class="e20r-assignment-field_type"/>
		<h5 class="e20r-assignment-question"><?php echo $assignment->question; ?></h5><?php

		if ( isset( $assignment->descr ) && ! empty( $assignment->descr ) ) { ?>
			<div class="e20r-assignment-descr"><?php echo $assignment->descr; ?></div><?php
		} ?>
		<table class="e20r-assignment-ranking-question">
			<tbody>
			<tr>
				<td class="e20r-assignment-ranking-question-choice-label"><?php _e( "Yes", "e20rtracker" ); ?></td>
				<td class="e20r-assignment-ranking-question-choice-label"><?php _e( "No", "e20rtracker" ); ?></td>
			</tr>
			<tr>
				<td class="e20r-assignment-ranking-question-choice e20r-yes-checkbox">
					<input class="e20r-assignment-response" name="e20r-assignment-answer[]" type="checkbox"
					       value="yes" <?php checked( $assignment->answer, 'yes' ); ?>>
				</td>
				<td class="e20r-assignment-ranking-question-choice e20r-no-checkbox">
					<input class="e20r-assignment-response" name="e20r-assignment-answer[]" type="checkbox"
					       value="no" <?php checked( $assignment->answer, 'no' ); ?>>
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
			<input type="hidden" value="<?php echo isset( $assignment->id ) ? $assignment->id : - 1; ?>"
			       name="e20r-assignment-record_id[]" class="e20r-assignment-record_id"/>
			<input type="hidden" value="<?php echo $assignment->question_id; ?>" name="e20r-assignment-question_id[]"
			       class="e20r-assignment-question_id"/>
			<input type="hidden" value="<?php echo $assignment->field_type; ?>" name="e20r-assignment-field_type[]"
			       class="e20r-assignment-field_type"/>
			<h5 class="e20r-assignment-question"><?php echo $assignment->question; ?></h5><?php
			if ( isset( $assignment->descr ) && ! empty( $assignment->descr ) ) { ?>
				<div class="e20r-assignment-descr"><?php echo $assignment->descr; ?></div><?php
			} ?>
			<input type="text" class="e20r-assignment-response" name="e20r-assignment-answer[]"
			       placeholder="<?php _e( "Type your response and click 'Save Answers', please...", "e20rtracker" ); ?>"
			       value="<?php echo $assignment->answer; ?>"/>
		</div>
		<?php
		return ob_get_clean();

	}

	private function showAssignmentParagraph( $assignment ) {

		ob_start();
		?>
		<div class="e20r-assignment-paragraph">
			<input type="hidden" value="<?php echo isset( $assignment->id ) ? $assignment->id : - 1; ?>"
			       name="e20r-assignment-record_id[]" class="e20r-assignment-record_id"/>
			<input type="hidden" value="<?php echo $assignment->question_id; ?>" name="e20r-assignment-question_id[]"
			       class="e20r-assignment-question_id"/>
			<input type="hidden" value="<?php echo $assignment->field_type; ?>" name="e20r-assignment-field_type[]"
			       class="e20r-assignment-field_type"/>
			<h5 class="e20r-assignment-question"><?php echo $assignment->question; ?></h5><?php
			if ( ! empty( $assignment->descr ) ) { ?>
				<div class="e20r-assignment-descr"><?php echo $assignment->descr; ?></div><?php
			} ?>
			<textarea class="e20r-assignment-response e20r-textarea" name="e20r-assignment-answer[]" rows="7" cols="80"
			          placeholder="<?php _e( "Type your response and click 'Save Answers', please...", "e20rtracker" ); ?>"><?php
				if ( ! empty( $assignment->answer ) ) {
					dbg( "e20rAssignmentView::showAssignmentParagraph() - Loading actual answer..." );
					echo trim( stripslashes( $assignment->answer ) );
				} ?></textarea>
		</div>
		<?php
		return ob_get_clean();
	}

	private function showAssignmentButton( $assignment, $isComplete = false ) {

		dbg( "e20rAssignmentView::showAssignmentButton() - Using assignment configuration: " );

		ob_start();
		?>
		<input type="hidden" value="<?php echo isset( $assignment->id ) ? $assignment->id : - 1; ?>"
		       name="e20r-assignment-record_id[]" class="e20r-assignment-record_id"/>
		<input type="hidden"
		       value="<?php echo( $assignment->question_id == 0 ? CONST_DEFAULT_ASSIGNMENT : $assignment->question_id ); ?>"
		       name="e20r-assignment-question_id[]" class="e20r-assignment-question_id"/>
		<input type="hidden" value="0" name="e20r-assignment-field_type[]" class="e20r-assignment-field_type"/>
		<div class="e20r-lesson-highlight <?php echo( $isComplete ? 'lesson-completed startHidden' : null ); ?>">
			<button id="e20r-lesson-complete"
			        class="e20r-button assignment-btn"><?php _e( "I have read this", "e20rtracker" ); ?></button>
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

		global $currentArticle;
		$html = '';

		ob_start();
		$prefix = preg_replace( '/\[|\]/', '', $currentArticle->prefix );
		?>
		<div class="green-notice big"
		     style="background-image: url( <?php echo E20R_PLUGINS_URL; ?>/img/checked.png ); margin: 12px 0pt; background-position: 24px 9px;">
			<p>
				<strong><?php echo sprintf( __( "You have completed this %s.", "e20rTracker" ), lcfirst( $prefix ) ); ?></strong>
			</p>
		</div>
		<?php

		$html = ob_get_clean();

		return $html;
	}

	public function viewAssignmentList( $config, $answers ) {

		global $current_user;
		global $e20rTracker;
		global $e20rArticle;
		global $e20rClient;

		$add_message = false;
		$is_coach    = $e20rTracker->is_a_coach( $current_user->ID );
		$is_client   = $is_coach ? false : true;

		if ( isset( $answers['max_num_pages'] ) ) {

			$max_num_pages = $answers['max_num_pages'];
			unset( $answers['max_num_pages'] );
		} else {
			$max_num_pages = null;
		}

		if ( $is_coach && is_admin() ) {
			dbg( "e20rAssignmentView::viewAssignmentList() - Include Coaching UI info." );
			$add_message = true;
		}

		if (isset( $answers['current_page'] ) ) {
			$current_page = $answers['current_page'];
			unset( $answers['current_page'] );
		} else {
			$current_page = 1;
		}

		ob_start();

		// TODO: Use $e20rArticle::get_feedback() function to load a feedback item for user (or coach).
        /*
		if ( !is_null($max_num_pages) && $max_num_pages > 1 ) {

			$big        = 99999999;

			$config->userId;

			error_log("Current page: {$current_page}");
			// $translated = __( "Page", "e20rtracker" );
			$translated = ''; ?>


            <nav class="e20r-pagination-links navigation" role="navigation">
            <input type="hidden" id="e20r-page-name" value="assignment_answer_list">
            <input type="hidden" id="e20r-assignment-pagination-cid" value="<?php esc_attr_e( $config->userId ); ?>">
			<?php wp_nonce_field( 'e20r-tracker-data', 'e20r-assignment-nonce' ); ?>
            <!-- <span class="e20r-page-nav"><?php echo $translated; ?></span> -->
			<?php
			// structure of "format" depends on whether we're using pretty permalinks

			$pagination_args = array(
				'base'               => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
				'format'             => '?paged=%#%',
				'current'            => $current_page,
				'total'              => $max_num_pages,
				'mid_size'           => 2,
				'end_size'           => 1,
				'prev_next'          => true,
				'before_page_number' => '<span class="screen-reader-text">' .$translated . '</span>',
			);

			global $wp_rewrite;
			if ( $wp_rewrite->using_permalinks() ) {
				$pagination_args['base'] = user_trailingslashit( trailingslashit( remove_query_arg('paged', get_pagenum_link(1) ) ) . 'page/%#%/', 'paged');
			}

			echo paginate_links( $pagination_args );
			?>
            </nav><?php
		}
        */
		?>
		<div id="e20r-assignment_answer_list" class="e20r-measurements-container">
			<h4><?php _e( "Assignments", "e20rtracker" ) ?></h4>
			<a class="close" href="#">X</a>
			<div class="quick-nav other">
				<?php wp_nonce_field( 'e20r-tracker-data', 'e20r-assignment-nonce' ); ?>
				<table class="e20r-measurement-table">
					<tbody>
					<?php
					$counter = 0;
					if ( ! empty( $answers ) ) {

						dbg( "e20rAssignmentView::viewAssignmentList() - User has supplied answers..." );
						// dbg($answers);

						foreach ( $answers as $key => $answer ) {

							if ( $answer->field_type == 0 ) {

								dbg( "e20rAssignmentView::viewAssignmentList() - {$answer->id} has no answer (It's a 'lesson complete' button)." );
								continue;
							}

							$when     = date_i18n( "Y-m-d", strtotime( $e20rTracker->getDateForPost( $answer->delay, $config->userId ) ) );
							$showLink = ( $config->userId == $current_user->ID ? true : false );

							if ( ! $is_coach && ( isset( $answer->new_messages ) && ( 1 >= $answer->new_messages ) ) ) {
								$add_message = true;
							}

							if ( ! $is_coach && ( isset( $answer->new_messages ) && ( false !== $answer->new_messages ) ) && ( $config->userId == $current_user->ID ) ) {

								$new_message_for_user = true;
							} else {
								$new_message_for_user = false;
							}

							if ( $is_coach && ( ( isset( $answer->new_messages ) && ( false !== $answer->new_messages ) ) || ( empty( $answer->messages ) ) ) && ( $config->userId != $current_user->ID ) ) {
								$new_message_for_coach = true;
							} else {
								$new_message_for_coach = false;
							}


							if ( 1 < count( $answer->article_ids ) ) {
								dbg( "e20rAssignmentView::viewAssignmentList() - Error: Number of article IDs for answer # {$answer->id}/{$answer->question_id}: " . count( $answer->article_ids ) . " Content: " . print_r( $answer->article_ids, true ) );
							} elseif ( 1 == count( $answer->article_ids ) ) {
								$answer->article_ids = array_pop( $answer->article_ids );
							} else {
								$answer->article_ids = null;
							}

							/**
							 *
							 * case 0: Coach while in wp-admin and new message from client.
							 * case 1: Coach, while in wp-admin and no new message from client.
							 * case 2: Coach, on front-end WITH a new message from the client
							 * case 3: Coach, on front-end WITHOUT a new message from the client.
							 * case 4: User, on front-end and WITH a new message from the coach.
							 * case 5: User, on front-end and WITHOUT a new message from the coach.
							 *
							 */

							$client_id = $config->userId;

							if ( false === $is_coach ) {

								$coaches = $e20rClient->get_coach( $current_user->ID, $config->programId );

								if ( empty( $coaches ) ) {

									$e20rClient->assign_coach( $current_user->ID );
									$coaches = $e20rClient->get_coach( $current_user->ID, $config->programId );
								}

								dbg( "e20rAssignmentView::viewAssignmentList() - Found coach info for current_user->ID: " );
								dbg( $coaches );

							} else {
								dbg( "e20rAssignmentView::viewAssignmentList() - current_user->ID IS a coach: " );
								$coaches = array( $current_user->ID => $current_user->display_name );
								dbg( $coaches );
							}

							foreach ( $coaches as $c_id => $name ) {

								dbg( "e20rAssignmentView::viewAssignmentList() - Assigned coach will be: {$c_id}" );
								$coach_id = $c_id;
							}

							dbg( "e20rAssignmentView::viewAssignmentList() - Client ID: {$client_id}, Coach ID: {$coach_id} " );

							$button_status = null;

							if ( ( true === is_admin() ) && ( true === $is_coach ) && ( true === $new_message_for_coach ) ) {
								$button_status = 0;
							}

							if ( ( true === is_admin() ) && ( true === $is_coach ) && ( false === $new_message_for_coach ) ) {
								$button_status = 1;
							}

							if ( ( false === is_admin() ) && ( true === $is_coach ) && ( true === $new_message_for_coach ) ) {
								$button_status = 2;
							}

							if ( ( false === is_admin() ) && ( true === $is_coach ) && ( false === $new_message_for_coach ) ) {
								$button_status = 3;
							}

							if ( ( false === is_admin() ) && ( true === $is_client ) && ( true === $new_message_for_user ) ) {
								$button_status = 4;
							}

							if ( ( false === is_admin() ) && ( true === $is_client ) && ( false === $new_message_for_user ) ) {
								$button_status = 5;
							}

							$not_from_self = true;

							dbg( "e20rAssignmentView::viewAssignmentList() - Assignment/Question ID: {$answer->question_id}" );
							dbg( "e20rAssignmentView::viewAssignmentList() - New message for the coach: " . ( $new_message_for_coach ? 'true' : 'false' ) );
							dbg( "e20rAssignmentView::viewAssignmentList() - New message for the client: " . ( $new_message_for_user ? 'true' : 'false' ) );

							?>
							<tr class="<?php echo( ( $counter % 2 == 0 ) ? "e20rEven" : "e20rOdd" ) ?> <?php echo( true == $answer->new_messages ? 'e20r-messages-new' : null ); ?>">
								<td class="measurement-date">
									<div class="date">
										<form method="POST" action="">
											<input type="hidden" name="assignment_id" id="assignment_id"
											       data-measurement-type="id" value="<?php echo $answer->id; ?>">
											<input type="hidden" name="date" id="delay" data-measurement-type="delay"
											       value="<?php echo $answer->delay; ?>">
											<input type="hidden" name="article_id" id="article_id"
											       data-measurement-type="article_id"
											       value="<?php echo $answer->article_id; ?>">
											<input type="hidden" name="program_id" id="program_id"
											       data-measurement-type="program_id"
											       value="<?php echo $config->programId; ?>">
										</form>
										<!-- <span> -->
										<?php
										if ( $showLink ) {
											//dbg( "e20rAssignmentView::viewAssignmentList() - Want to show link for article {$answer->article_id}" );
											?>
											<a href="<?php echo $e20rArticle->getPostUrl( $answer->article_id ); ?>"
											   target="_blank"
											   alt="<?php _e( "Opens in a separate window", 'e20r-tracker' ); ?>">
												<?php echo date_i18n( 'M j, Y', strtotime( $when ) ); ?>
											</a>
											<?php
										} else {
											?>
											<?php echo date_i18n( 'M j, Y', strtotime( $when ) ); ?>
											<?php
										}
										?>
									</div>
									<div
										class="timeago timeagosize"><?php echo date_i18n( "Y/m/d", strtotime( $when ) ); ?></div>
								</td>
								<td class="e20r-measurement-responses">
									<table class="e20r-answers">
										<tbody>
										<tr>
											<td>
												<div class="e20r-assignments-question"
												     title="<?php echo preg_replace( '/\"/', '\'', wp_strip_all_tags( stripslashes( $answer->descr ) ) ); ?>">
													<?php echo stripslashes( $answer->question ); ?>
												</div>
											</td>
										</tr>
										<tr>
											<td>
												<div class="e20r-assignments-answer"><?php

													if ( is_array( $answer->answer ) ) {

														$info = '';
														// Build the string of options selected.
														foreach ( $answer->answer as $key ) {

															$info .= "{$answer->select_options[$key]}, ";
														}

														// Remove trailing comma and whitespace.
														$info = preg_replace( "/,\s$/", '', $info );
													} elseif ( 5 == $answer->field_type ) {
														$info = sprintf( __( "On a scale from 1 to 10: <strong>%s</strong>", "e20rtracker" ), ( empty( $answer->answer ) ? __( "No response recorded", "e20rtracker" ) : $answer->answer ) );
													} else {
														$info = $answer->answer;
													}


													echo empty( $info ) ? __( "No response recorded", "e20rtracker" ) : stripslashes( $info ); ?>
												</div>
											</td>
										</tr>
										</tbody>
									</table>
								</td><?php
								if ( true == $add_message ) { ?>
									<td class="e20r-coach-reply"><?php
									if ( ( $is_client && ! empty( $answer->messages ) ) || ( $is_coach && ( ! is_null( $answer->answer ) || ! empty( $answer->answer ) ) ) ) { ?>
										<a href="#TB_inline?width=500&height=600&inlineId=assignment_reply_<?php echo $answer->id; ?>"
										   class="e20r-assignment-reply-link thickbox button">
											<?php

											switch ( $button_status ) {
												case 0:
												case 2:
													$button_text = __( "Respond", "e20rtracker" );
													break;

												case 1:
												case 3:
													$button_text = sprintf( __( "Total: %d", "e20rtracker" ), count( $answer->messages ) );
													break;

												case 4:
													$button_text = __( "Reply", "e20rtracker" );
													break;

												case 5:
													$button_text = __( "Review", "e20rtracker" );
													break;

												default:

											}
											echo $button_text;
											?>
										</a>
									<div id="assignment_reply_<?php echo $answer->id; ?>"
									     class="e20r-message-history-content" style="display:none">
										<?php dbg( "e20rAssignmentView::viewAssignmentList() - Loaded answer information:" ); ?>
										<?php dbg( $answer );

										// I'm a coach, but not "the" coach, or I'm "the" coach; Use the coaches ID...
										if ( ! $is_coach ) {
											$recipient_id = $coach_id;
										} elseif ( $is_coach ) {
											$recipient_id = $client_id;
										}

										dbg( "e20rAssignmentView::viewAssignmentList() - Recipient Id: {$recipient_id}" );
										?>
										<input type="hidden" name="e20r-assignment-client_id[]"
										       value="<?php echo esc_attr( $client_id ); ?>">
										<input type="hidden" name="e20r-assignment-article_id[]"
										       value="<?php echo esc_attr( $answer->article_ids ); ?>">
										<input type="hidden" name="e20r-assignment-assignment_id[]"
										       value="<?php echo esc_attr( $answer->id ); ?>">
										<input type="hidden" name="e20r-assignment-delay[]"
										       value="<?php echo esc_attr( $answer->delay ); ?>">
										<input type="hidden" name="e20r-assignment-program_id[]"
										       value="<?php echo esc_attr( $config->programId ); ?>">
										<input type="hidden" name="e20r-assignment-message_date[]"
										       value="<?php echo esc_attr( current_time( 'mysql' ) ); ?>">
										<input type="hidden" name="e20r-assignment-sent_by_id[]"
										       value="<?php echo esc_attr( $current_user->ID ); ?>">
										<input type="hidden" name="e20r-assignment-replied_to_id[]"
										       value="<?php echo( $recipient_id ); ?>">
										<input type="hidden" name="e20r-assignment-recipient_id[]"
										       value="<?php echo esc_attr( $recipient_id ); ?>">
										<input type="hidden" name="e20r-assignment-question[]"
										       value="<?php echo esc_attr( stripslashes( $answer->question ) ); ?>"><?php

										if ( empty( $answer->messages ) ) { ?>
											<input type="hidden" name="e20r-assignment-answer[]"
											       value="<?php echo esc_attr( stripslashes( $answer->answer ) ); ?>"><?php
										} ?>
										<div class="e20r-message-content">
											<?php echo $this->message_history( $answer->messages, $recipient_id, $answer->article_ids ); ?>
										</div>
										<div class="e20r-message-reply">
											<h3 class="e20r-message-reply-header"><?php _e( "Write a reply", "e20rtracker" ); ?></h3>
											<div class="e20r-message-status-text startHidden"></div>
											<textarea class="e20r-assignment-reply_area"
											          name="e20r-assignment-message[]"></textarea>
										</div>
										<button
											class="button secondary e20r-assignment-reply-button"><?php _e( "Send", "e20rtracker" ); ?></button><?php
										//                                    } ?>
										</div><?php
									} ?>
									</td><?php
								} else { ?>
									<td></td><?php
								} ?>
							</tr>
							<?php
							$counter ++;
						}
					} else { ?>
						<tr>
							<td colspan="3"><?php _e( "No assignments or answers found.", "e20rtracker" ); ?></td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
				<?php

				// TODO: Make shortcode and pagination AJAX based
				error_log( "Total # of pages: " . $max_num_pages );

				// only bother with the rest if we have more than 1 page!
				if ( !is_null($max_num_pages) && $max_num_pages > 1 ) {

					$big        = 99999999;

					$config->userId;

					error_log("Current page: {$current_page}");
					// $translated = __( "Page", "e20rtracker" );
                    $translated = ''; ?>


				<nav class="e20r-pagination-links navigation" role="navigation">
					<input type="hidden" id="e20r-page-name" value="assignment_answer_list">
					<input type="hidden" id="e20r-assignment-pagination-cid" value="<?php esc_attr_e( $config->userId ); ?>">
					<?php wp_nonce_field( 'e20r-tracker-data', 'e20r-assignment-nonce' ); ?>
					<!-- <span class="e20r-page-nav"><?php echo $translated; ?></span> -->
					<?php
					// structure of "format" depends on whether we're using pretty permalinks

					$pagination_args = array(
						'base'               => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
						'format'             => '?paged=%#%',
						'current'            => $current_page,
						'total'              => $max_num_pages,
						'mid_size'           => 2,
						'end_size'           => 1,
						'prev_next'          => true,
						'before_page_number' => '<span class="screen-reader-text">' .$translated . '</span>',
					);

					global $wp_rewrite;
					if ( $wp_rewrite->using_permalinks() ) {
						$pagination_args['base'] = user_trailingslashit( trailingslashit( remove_query_arg('paged', get_pagenum_link(1) ) ) . 'page/%#%/', 'paged');
					}

					echo paginate_links( $pagination_args );
					?>
				</nav><?php
				}
				?>
			</div>
		</div>
		<?php

		$html = ob_get_clean();

		return $html;
	}

	public function message_history( $history, $user_id, $assignment_id ) {

		global $e20rTables;
		global $e20rTracker;

		global $current_user;

		// $reply_fields = $e20rTables->getFields('response');
		$record_num = 0;
		$total      = count( $history ) - 1;

		ob_start();

		if ( ! empty( $history ) ) { ?>

			<hr class="e20r-message-history-start"><?php

			foreach ( $history as $r ) {

				dbg( "e20rAssignmentView::message_history() - Loading the view for message: {$r->response_id}, record # {$record_num}" );
				dbg( $r );

				$read_message = false;
				$user         = get_user_by( 'id', $r->message_sender_id );

				if ( $r->message_sender_id == $current_user->ID ) {

					$from_me = true;
					$from    = __( "me", "e20rtracker" );

				} else {

					$from_me = false;
					$from    = $user->display_name;
				}

				$is_archived   = ( $r->archived == 1 ? true : false );
				$user_is_coach = $e20rTracker->is_a_coach( $current_user->ID );
				$is_read       = ( $r->read_status == 1 ? true : false );
				$is_last       = ( ++ $record_num <= $total ? false : true );

				dbg( "e20rAssignmentView::message_history() - For {$r->response_id}: is_read: " . ( $is_read ? 'true' : 'false' ) );
				dbg( "e20rAssignmentView::message_history() -              : from_me: " . ( $from_me ? 'true' : 'false' ) );
				dbg( "e20rAssignmentView::message_history() -              : is_archived: " . ( $is_archived ? 'true' : 'false' ) );
				dbg( "e20rAssignmentView::message_history() -              : user_is_coach: " . ( $user_is_coach ? 'true' : 'false' ) );
				dbg( "e20rAssignmentView::message_history() -              : is_last: " . ( $is_last ? 'true' : 'false' ) );

				if ( ( ( false === $is_archived ) && ( false === $user_is_coach ) ) || ( true === $user_is_coach ) ) {

					dbg( "e20rAssignmentView::message_history() - Loading history HTML for non-archived messages" );

					if ( false === $is_last ) {
						dbg( "e20rAssignmentView::message_history() - Older unread message ID {$r->response_id} with timestamp: {$r->message_time}" ); ?>
						<div class="e20r-message-history-message clearfix"><?php
					} else {
						dbg( "e20rAssignmentView::message_history() - Most recent message: {$r->response_id} with timestamp: {$r->message_time}" ); ?>
						<input type="hidden" name="e20r-assignment-answer[]"
						       value="<?php echo esc_attr( stripslashes( $r->message ) ); ?>">
						<div class="e20r-message-history-message clearfix e20r-message-most-recent"><?php
					} ?>

					<div
						class="<?php echo( false === $is_read ? 'e20r-message-history-unread ' : 'e20r-message-history ' ); ?><?php echo( ( $record_num <= $total ) ? 'e20r-message-history-not-last ' : null ); ?><?php echo( $from_me ? 'e20r-message-from-self ' : null ); ?>clearfix">
						<div class="e20r-message-history-who">
							<input type="hidden" class="e20r-message-id-hidden" name="e20r-message-id[]"
							       value="<?php echo esc_attr( $r->response_id ); ?>">
							<input type="hidden" name="e20r-message-sent-by-hidden[]"
							       value="<?php echo esc_attr( $r->message_sender_id ); ?>">
							<input type="hidden" name="e20r-message-timestamp[]"
							       value="<?php echo esc_attr( $r->message_time ); ?>">
							<?php echo sprintf( __( "From %s on %s", "e20rtracker" ), esc_attr( $from ), esc_attr( date( get_option( 'date_format' ), strtotime( $r->message_time ) ) ) ); ?>
						</div>
						<div class="e20r-message-history-message-body">
							<?php echo stripslashes( $r->message ); ?>
						</div><?php
						if ( ( false === $from_me ) && ( false === $is_archived ) ) { ?>
							<div class="e20r-message-ack">
							<button
								class="button primary e20r-message-ack-button"><?php _e( "Archive", "e20rtracker" ); ?></button>
							</div><?php
						} ?>
					</div>
					</div><?php
				}

				/*
								if ( $e20rTracker->is_a_coach( $current_user->ID ) ) {

									dbg("e20rAssignmentView::message_history() - Loading history HTML for unread messages to the back-end coaching page");

									if ( false === $is_last ) {
										dbg("e20rAssignmentView::message_history() - Older message with ID {$r->response_id} and timestamp: {$r->message_time}");?>
									<div class="e20r-message-history-message clearfix"><?php
									} else {
										dbg("e20rAssignmentView::message_history() - Most recent message: {$r->response_id} with timestamp: {$r->message_time}");?>
									<input type="hidden" name="e20r-assignment-answer[]" value="<?php echo esc_attr(stripslashes( $r->message )); ?>">
									<div class="e20r-message-history-message clearfix e20r-message-most-recent"><?php
									}

									if ( false == $r->read_status ) {
										dbg("e20rAssignmentView::message_history() - Message is not acked by user/admin");?>
										<div class="<?php echo ($from_me == false ? 'e20r-message-history-unread ' : 'e20r-message-history '); ?><?php echo ( ($record_num <= $total) ? 'e20r-message-history-not-last ' : null); ?>clearfix"><?php
									}?>
											<div class="e20r-message-history-who">
												<input type="hidden" class="e20r-message-id-hidden" name="e20r-message-id[]" value="<?php echo esc_attr($r->response_id); ?>">
												<input type="hidden" name="e20r-message-sent-by-hidden[]" value="<?php echo esc_attr($r->message_sender_id); ?>">
												<input type="hidden" name="e20r-message-timestamp[]" value="<?php echo esc_attr($r->message_time); ?>">
												<?php echo sprintf(__("From %s on %s", "e20rtracker"), esc_attr($user->display_name), esc_attr($r->message_time)); ?>
											</div>
											<div class="e20r-message-history-message-body">
												<?php echo stripslashes($r->message); ?>
											</div><?php

									if ( ( false === $is_read ) && ( false == $from_me ) ) {
										dbg("e20rAssignmentView::message_history() - Adding Archive (button)"); ?>
											<div class="e20r-message-ack">
												<button class="button primary e20r-message-ack-button"><?php _e( "Archive", "e20rtracker"); ?></button>
											</div><?php
									}

									if ( false === $r->read_status ) { ?>
										</div><?php
									} ?>
								</div><?php
								}
				*/
			} // End of foreach loop
		} else {
			dbg( "e20rAssignmentView::message_history() - No messages found for {$user_id} about {$assignment_id}" );
		}

		$message_history = ob_get_clean();

		dbg( "e20rAssignmentView::message_history() - Returned message history for {$user_id} and {$assignment_id}" );

		return $message_history;
	}
}