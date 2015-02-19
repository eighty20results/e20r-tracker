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

        dbg( "e20rAssignmentView::viewSettingsBox() - Supplied data: " . print_r( $assignmentData, true ) );
        ?>
        <form action="" method="post">
            <?php wp_nonce_field( 'e20r-tracker-data', 'e20r-tracker-assignment-settings' ); ?>
            <div class="e20r-editform">
                <input type="hidden" name="hidden-e20r-assignment-id" id="hidden-e20r-assignment-id"
                       value="<?php echo( ( ! empty( $assignmentData ) ) ? $assignmentData->ID : 0 ); ?>">
                <table id="e20r-assignment-settings wp-list-table widefat fixed">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label for="e20r-assignment-order_num">Order #</label></th>
                        <th class="e20r-label header"><label for="e20r-assignment-field_type">Answer type</label></th>
                        <th class="e20r-label header"><label for="e20r-assignment-delay">Day #</label></th>
                    </tr>
                    <tr>
                        <td colspan="5">
                            <hr width="100%"/>
                        </td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr id="<?php echo $assignmentData->ID; ?>" class="assignment-inputs">
                        <td class="text-input">
                            <input type="number" id="e20r-assignment-order_num" name="e20r-assignment-order_num" value="<?php echo ( ! isset( $assignmentData->order_num ) ? 1 : $assignmentData->order_num ); ?>">
                        </td>
                        <td class="text-input" style="width: 50%;">
                            <select id="e20r-assignment-field_type select2-container" name="e20r-assignment-field_type" style="width: 100%;">
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

                    </tr>
                    </tbody>
                </table>
            </div>
        </form>
    <?php
    }

    public function viewArticle_Assignments( $articleId = CONST_NULL_ARTICLE, $assignments, $answerDefs = null  ) {

        global $e20rAssignment;

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
                dbg("e20rAssignmentView::viewArticle_Assignments() - Processing assignment w/id: {$a->id}");
                dbg( $a );
                ?>

                <tr class="e20r-assignment-list">
                    <td class="e20r-assignment-hdr_order"><?php echo $a->order_num . "."; ?></td>
                    <td class="e20r-assignment-hdr_title"><?php echo $a->question; ?></td>
                    <td class="e20r-assignment-hdr_type">
                        <?php
                            dbg("e20rAssignmentView::viewArticle_Assignments() - field type = {$a->field_type}");
                            if ( $answerDefs !== null ) {

                                echo $answerDefs[ $a->field_type ];
                            }
                            else {
                                echo $answerDefs[0];
                            }
                        ?>
                        <input type="hidden" class="e20r-assignment-type" name="e20r-assignment-field_type[]" value="<?php echo $a->field_type; ?>" />
                    </td>
                    <td class="e20r-assignment-buttons">
                        <a class="e20r-assignment-edit" href="javascript:e20r_assignmentEdit(<?php echo $a->id; ?>, <?php echo $a->order_num; ?>); void(0);"><?php _e("Edit", "e20rtracker"); ?></a>
                    </td>
                    <td class="e20r-assignment-buttons">
                        <a class="e20r-assignment-remove" href="javascript:e20r_assignmentRemove(<?php echo $a->id; ?>); void(0);"><?php _e("Remove", "e20rtracker"); ?></a>
                        <input type="hidden" class="e20r-assignment-id" name="e20r-assignment-id[]" value="<?php echo $a->id ?>" />
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
                    <th id="new-assignment-header-id"><label for="e20r-add-assignment-id"><?php _e('Assignment', 'e20rtracker'); ?></label></th>
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
	                        <option value="0"></option><?php
                            dbg("e20rAssignmentView::viewArticle_Assignments() - Loading all possible assignments");

                            $all = $e20rAssignment->getAllAssignments();

                            dbg("e20rAssignmentView::viewArticle_Assignments() - Loaded " . count($all) . ' assignments');

                            foreach( $all as $id => $assignment ) { ?>
                                <option value="<?php echo $id; ?>"><?php echo $assignment->question . " (Day # {$assignment->delay})"; ?></option><?php
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

	}
}