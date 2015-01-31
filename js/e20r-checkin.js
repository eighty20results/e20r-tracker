/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

jQuery.noConflict();
jQuery(document).ready(function() {

    var e20rCheckinEvent = {
        init: function() {

            this.$checkinOptions = jQuery('#e20r-daily-checkin-canvas fieldset.did-you input:radio');
            this.$checkinDate = jQuery('#e20r-checkin-checkin_date').val();
            this.$checkinArticleId = jQuery('#e20r-checkin-article_id').val();
            this.$checkinProgramId = jQuery('#e20r-checkin-program_id').val();
            this.$nonce = jQuery('#e20r-checkin-nonce').val();;
            this.$itemHeight = jQuery('#e20r-daily-checkin-canvas fieldset.did-you ul li').outerHeight();

            var self = this;

            jQuery(this.$checkinOptions).on('click', function() {

                var $radioBtn = this;

                console.log("Checkin button: ", this );
                self.$actionOrActivity = jQuery(this).attr('name').split('-')[1].title();
                console.log("Action or activity? ", self.$actionOrActivity);
                self.saveCheckin( this, self.$actionOrActivity );
            });

        },
        saveCheckin: function( elem, $a ){

//            console.log("Element is: ", elem );

//            console.log("Type: ", jQuery(elem).closest('fieldset.did-you > div').find('.e20r-checkin-checkin_type:first') );

            var $data = {
                action: 'saveCheckin',
                'checkin-action': $a,
                'e20r-checkin-nonce': this.$nonce,
                'checkin-date': this.$checkinDate,
                'article-id': this.$checkinArticleId,
                'program-id': this.$checkinProgramId,
                'checkedin': jQuery(elem).val(),
                'checkin-type': jQuery(elem).closest('fieldset.did-you > div').find('.e20r-checkin-checkin_type:first').val(),
                'id': jQuery(elem).closest('fieldset.did-you > div').find('.e20r-checkin-id:first').val(),
                'checkin-short-name': jQuery(elem).closest('fieldset.did-you > div').find('.e20r-checkin-checkin_short_name:first').val()
            };

            jQuery.post( e20r_checkin.url, $data, function(response) {

                if ( ! response.success ) {

                    var $string;
                    $string = "An error occurred when trying to save your choice to the S3F Nourish Coaching database. If you\'d like to try again, reload ";
                    $string += "this page, and click your selection once more. \n\nIf you get this error a second time, please contact Technical Support by using our Contact form ";
                    $string += "at the top of this page.";

                    alert($string);

                    return;
                }
                else {

                    jQuery(self).data('__persisted', true);

                    var ul = jQuery(self).parents('ul');

                    var index = jQuery(ul).children('li').index(jQuery(self).parent());

                    if (null !== jQuery(ul).data('activeIndex')
                        && index == jQuery(ul).data('activeIndex'))
                        return false;


                    jQuery(ul)
                        .find('input[type=radio]')
                        .parent('li')
                        .removeClass('active');

                    jQuery(self)
                        .parent('li')
                        .addClass('active');

                    var notificationAnimation = function(fadeOutCallback) {
                        jQuery(ul)
                            .next('.notification-entry-saved')
                            .children('div')
                            .fadeIn('medium', function() {
                                var _self = this;
                                setTimeout(function() {
                                    jQuery(_self)
                                        .fadeOut('slow', fadeOutCallback);
                                }, 2200);
                            });
                    };

                    var selectionSlideUp = function() {
                        var activeItemOffsetTop = index * listItemHeight;

                        jQuery(ul)
                            .css('margin-top', (activeItemOffsetTop) + 'px')
                            .animate({
                                marginTop: 0
                            }, 200 * index, function() {
                                notificationAnimation(function() {
                                    jQuery(ul)
                                        .parents('fieldset.did-you')
                                        .append('<button class="edit-selection">Edit Selection</button>');
                                });
                            });
                    }

                    var once = 0;

                    jQuery(ul)
                        .children('li')
                        .not('.active')
                        .fadeOut('medium', function() {
                            if (1 == once) // only run this callback once
                                return;

                            selectionSlideUp();

                            once = 1;
                        });

                    jQuery(ul)
                        .data('activeIndex', index)
                        .data('updateMode', true);
                }

            });

        }
    };

    e20rCheckinEvent.init();
});
