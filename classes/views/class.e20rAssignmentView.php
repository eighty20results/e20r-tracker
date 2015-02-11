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
    
    public function viewSettingsBox( $assignmentData ) {

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
                            <input type="number" id="e20r-assignment-order_num" name="e20r-assignment-order_num" value="<?php echo $assignmentData->order_num; ?>">
                        </td>
                        <td class="text-input">
                            <select id="e20r-assignment-field_type select2-container" name="e20r-assignment-field_type">
                                <option value="0" <?php selected( $assignmentData->field_type, 0 ); ?>><?php _e("Button", "e20rtracker"); ?></option>
                                <option value="1" <?php selected( $assignmentData->field_type, 1 ); ?>><?php _e("Line of text (input)", "e20rtracker"); ?></option>
                                <option value="2" <?php selected( $assignmentData->field_type, 2 ); ?>><?php _e("Checkbox", "e20rtracker"); ?></option>
                                <option value="3" <?php selected( $assignmentData->field_type, 3 ); ?>><?php _e("Multiple Choice", "e20rtracker"); ?></option>
                            </select>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </form>
    <?php
    }

    public function viewArticle_Assignments( $articleId = CONST_NULL_ARTICLE, $assignments  ) {

        global $post;
        global $e20rArticle;
        global $e20rTracker;

        if ( ! current_user_can( 'edit_posts' ) ) {
            return false;
        }

        $this->displayError();

        ?>
        <table id="e20r-tracker-article-assignment-list" class="wp-list-table widefat fixed">
            <thead>
            <tr>
                <th class="e20r-label header"><label for=""><?php _e("Order", "e20rtracker"); ?></label></th>
                <th class="e20r-label header" style="width: 50%;"><label for=""><?php _e("Assignment", "e20rtracker"); ?></label></th>
                <th class="e20r-label header"><label for=""><?php _e("Answer Type", "e20rtracker"); ?></label</th>
                <th colspan="2">Operation</th>
            </tr>
            </thead>
            <tbody class="e20r-settings-list-tbody">
            <?php

            $count = 1;

            if ( empty( $assignments ) ) {
                ?>
                <tr class="e20r-article-list">
                <td>1.</td>
                <td><?php _e("Lesson complete (default)", 'e20rtracker'); ?></td>
                <td><?php _e("Button", 'e20rtracker'); ?></td>
                <td><?php _e("Edit", "e20rtracker"); ?></td>
                <td><?php _e("Remove", "e20rtracker"); ?></td>
                </tr><?php
            }
            else {
                foreach ( $assignments as $a ) { ?>

                    <tr class="e20r-article-list">
                    <td class="e20r-assignment-hdr_order"><?php echo $a->order_num; ?></td>
                    <td class="e20r-assignment-hdr_title"><?php echo $a->question; ?></td>
                    <td class="e20r-assignment-hdr_type">
                        <?php
                        // TODO: Properly display the assignment type selected.
                        echo $a->field_type;
                        ?>
                        <input type="hidden" class="e20r-assignment-type" name="e20r-assignment-field_type[]" value="<?php echo $a->field_type; ?>" />
                    </td>
                    <td class="e20r-assignment-buttons">
                        <a class="e20r-assignment-edit" href="javascript:e20r_assignmentEdit(<?php echo $a->id; ?>); void(0);"><?php _e("Edit", "e20rtracker"); ?></a>
                    </td>
                    <td class="e20r-assignment-buttons">
                        <a class="e20r-assignment-remove" href="javascript:e20r_assignmentRemove(<?php echo $a->id; ?>); void(0);"><?php _e("Remove", "e20rtracker"); ?></a>
                        <input type="hidden" class="e20r-assignment-id" name="e20r-assignment-id[]" value="<?php echo $a->id ?>" />
                    </td>
                    </tr><?php
                }
            } ?>
            </tbody>
        </table>
        <div id="postcustomstuff">
            <p><strong><?php _e('Add/Edit Assignments:', 'e20rtracker'); ?></strong></p>
            <table id="new-assignments">
                <thead>
                <tr>
                    <th id="new-assigments-header-order"><label for="e20r-assignments-order_num"><?php _e('Order', 'e20rtracker'); ?></label></th>
                    <th id="new-assignments-header-id"><label for="e20r-assignments-id"><?php _e('Assignment', 'e20rtracker'); ?></label></th>
                    <th id="new-assignments-header-answer_type"><label for="e20r-assignments-answer_type"><?php _e("Answer Type", 'e20rtracker'); ?></label></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <input id="e20r-assignments-order" name="e20r-assignments-order_num" type="text" value="" size="5" />
                    </td>
                    <td>
                        <select class="e20r-select2-container" id="e20r-assignments-id" name="e20r-assignments-id">
                            <option value="0"><?php _e("Button: Assignment complete", 'e20rtracker'); ?></option><?php
                            // TODO: Add logic to list all possible assignments (Read from CPT: e20r-assignments )
                            // $e20rAssignment->
                            ?>
                        </select>
                    </td>
                    <td>
                        <select class="e20r-select2-container" id="e20r-assignments-field_type" name="e20r-assignments-field_type">
                            <option value="0"><?php _e("Button: Assignment complete", 'e20rtracker'); ?></option><?php
                            // TODO: Add logic to list all possible assignments (Read from CPT: e20r-assignments )
                            ?>
                        </select>
                    </td>
                    <td>
                        <a class="e20r-button" id="e20r-article-assignment-save" onclick="javascript:e20r_assignmentSave(); return false;"><?php _e('Save Assignment', 'e20rtracker'); ?></a>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    <?php
    }
}