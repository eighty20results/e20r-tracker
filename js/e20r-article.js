/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

var e20rDailyProgress = {
    init: function( assignmentElem ) {
        this.$assignment = assignmentElem;
        this.$saveAssignmentBtn = this.$assignment.closest("button#e20r-assignment-save");

        var self = this;

        this.$saveAssignmentBtn.on('click', function(){
            console.log("Clicked 'Save' for the assignment");
            self.saveAssignment();
        });
    },
    saveAssignment: function() {
        console.log("saveAssignment() - ...");
    }
};

jQuery(document).ready(function(){
   e20rDailyProgress.init( jQuery('#e20r-article-assignment') );
});