/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */
jQuery(document).ready( function(){

    console.log("Load fitVids library");
    jQuery('.e20r-exercise-video').fitVids();

    var v = document.getElementsByClassName("youtube-player");

    for (var n = 0; n < v.length; n++) {

        var p = document.createElement("div");
        p.innerHTML = e20rThumb(v[n].dataset.id);
        p.onclick = e20rIframe;
        v[n].appendChild(p);

    }

/*    jQuery(document).bind("contextmenu",function(e){
        return false;
    });
*/
    setRespTable();

    jQuery('.e20r-tracker-detail-h4').on('click', function(){

        console.log("User clicked on the title of the exercise.");

        var elem = jQuery(this);

        var exInfo = elem.closest('div.e20r-exercise-detail').find('div.e20r-exercise-table-body');
        var exTrack = elem.closest('div.e20r-exercise-row').next().next('.e20r-exercise-row.e20r-exercise-tracking-row');

        exInfo.fadeToggle();

        if ( ( exTrack.length !== 0 ) && ( exTrack.hasClass('startHidden') ) ) {
            exTrack.fadeToggle();
        }
    });

    jQuery( '.e20r-exercise-info-toggle' ).unbind('click').on('click', function( ev ) {

        ev.preventDefault();

        var exercise_info = jQuery( this ).closest('.e20r-exercise-detail-row-3');

        exercise_info.find( 'div.e20r-exercise-video-column').toggle();
        exercise_info.find( 'div.e20r-exercise-description').toggle();
    });
});

function e20rThumb(id) {
    return '<img class="youtube-thumb" src="//i.ytimg.com/vi/' + id + '/hqdefault.jpg"><div class="play-button"></div>';
}

function e20rIframe() {

    var iframe = document.createElement("iframe");

    iframe.setAttribute("src", "//www.youtube.com/embed/" + this.parentNode.dataset.id + "?autoplay=1&autohide=2&border=0&wmode=opaque&enablejsapi=1&controls=0&showinfo=0&rel=0");

    iframe.setAttribute("frameborder", "0");
    iframe.setAttribute("id", "youtube-iframe");
    this.parentNode.replaceChild(iframe, this);
}

function setRespTable() {
    var headertext = [];
    var headers = document.querySelectorAll(".e20r-resp-table thead");
    var tablebody = document.querySelectorAll(".e20r-resp-table tbody");

    for (var i = 0; i < headers.length; i++) {
        headertext[i]=[];
        for (var j = 0, headrow; headrow = headers[i].rows[0].cells[j]; j++) {

            headertext[i].push(headrow.textContent.replace(/\r?\n|\r/,""));
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