/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */
jQuery(document).ready( function(){
    console.log("Load fitVids library");
    jQuery('div.e20r-exercise-video').fitVids();

    setRespTable();

    jQuery('.e20r-exercise-title > h4').on('click', function(){

        console.log("User clicked on the title of the exercise. We should toggle something.");

        var elem = jQuery(this);
        var exInfo = elem.closest('.e20r-exercise-detail').find('.e20r-exercise-table-body');
        var exTrack = elem.closest('.e20r-exercise-row').next('.e20r-exercise-tracking-row');

        exInfo.fadeToggle();

        if ( ( exTrack.length != 0 ) && ( exTrack.hasClass('startHidden') ) ) {
            exTrack.fadeToggle();
        }

    });
});

function setRespTable() {
    var headertext = [];
    var headers = document.querySelectorAll(".e20r-resp-table thead");
    var tablebody = document.querySelectorAll(".e20r-resp-table tbody");

    for (var i = 0; i < headers.length; i++) {
        headertext[i]=[];
        for (var j = 0, headrow; headrow = headers[i].rows[0].cells[j]; j++) {
            var current = headrow;
            headertext[i].push(current.textContent.replace(/\r?\n|\r/,""));
        }
    }

    for (var h = 0, tbody; tbody = tablebody[h]; h++) {
        for (var i = 0, row; row = tbody.rows[i]; i++) {
            for (var j = 0, col; col = row.cells[j]; j++) {
                col.setAttribute("data-th", headertext[h][j]);
            }
        }
    }
}