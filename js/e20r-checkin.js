/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

jQuery.noConflict();
jQuery(document).ready(function() {

    var $body = jQuery("body");

    jQuery(document).on({
        ajaxStart: function() { $body.addClass("loading");   },
         ajaxStop: function() { $body.removeClass("loading"); }
    });

    var e20rCheckinEvent = {
        init: function() {

            this.$checkinOptions = jQuery('#e20r-daily-checkin-canvas fieldset.did-you input:radio');
            this.$checkinDate = jQuery('#e20r-checkin-checkin_date').val();
            this.$checkinArticleId = jQuery('#e20r-checkin-article_id').val();
            this.$checkinProgramId = jQuery('#e20r-checkin-program_id').val();
            this.$nonce = jQuery('#e20r-checkin-nonce').val();;
            this.$itemHeight = jQuery('#e20r-daily-checkin-canvas fieldset.did-you ul li').outerHeight();
            this.$ulList = this.$checkinOptions.parents('ul');
/*            this.$tomorrowBtn = jQuery("#e20r-checkin-daynav").find("#e20r-checkin-tomorrow-lnk"); */
/*            this.$yesterdayBtn = jQuery("#e20r-checkin-daynav").find("#e20r-checkin-yesterday-lnk"); */

            var me = this;

            this.bindProgressElements( me );
        },
        bindProgressElements: function(self) {

            self.$tomorrowBtn = jQuery("#e20r-daily-progress").find("#e20r-checkin-daynav").find("#e20r-checkin-tomorrow-lnk");
            self.$yesterdayBtn = jQuery("#e20r-daily-progress").find("#e20r-checkin-daynav").find("#e20r-checkin-yesterday-lnk");

            jQuery("#e20r-daily-progress").find('#e20r-daily-checkin-canvas fieldset.did-you input:radio').on('click', function(){

                var $radioBtn = this;

                console.log("Checkin button: ", this);
                self.$actionOrActivity = jQuery(this).attr('name').split('-')[1].title();
                console.log("Action or activity? ", self.$actionOrActivity);
                self.saveCheckin(this, self.$actionOrActivity, self);
            });

            self.showBtn( self );

            jQuery(self.$tomorrowBtn).on('click', function() {

                event.preventDefault();
                self.dayNav(self, this);
            });

            jQuery(self.$yesterdayBtn).on('click', function() {

                event.preventDefault();
                self.dayNav(self, this);
            });
        },
        showBtn: function( self ) {

            var radioFieldsActivity = jQuery('fieldset.did-you:eq(0) input:radio', '#e20r-daily-checkin-canvas');
            var radioFieldsAction = jQuery('fieldset.did-you:eq(1) input:radio', '#e20r-daily-checkin-canvas');

            jQuery(radioFieldsActivity)
                .add(radioFieldsAction)
                .filter(':checked')
                .parent('li')
                .addClass('active');

            var setEditState = function(set) {
                jQuery(set)
                    .not(':checked')
                    .parent('li')
                    .addClass('concealed')
                    .parents('fieldset.did-you')
                    .append('<button class="e20r-button edit-selection">Edit check-in</button>');
            }

            if (bool(jQuery(radioFieldsActivity).filter(':checked').length)) {

                setEditState(radioFieldsActivity);

                jQuery(document).on('click', '#e20r-daily-checkin-canvas .edit-selection', function(){

                    self.editCheckin( this );
                });
            }

            if (bool(jQuery(radioFieldsAction).filter(':checked').length)) {

                setEditState(radioFieldsAction);

                jQuery(document).on('click', '#e20r-daily-checkin-canvas .edit-selection', function(){

                    self.editCheckin( this );
                });
            }
        },
        saveCheckin: function( elem, $a, self ){

//            console.log("Element is: ", elem );

//            console.log("Type: ", jQuery(elem).closest('fieldset.did-you > div').find('.e20r-checkin-checkin_type:first') );

            var $data = {
                action: 'saveCheckin',
                'checkin-action': $a,
                'e20r-checkin-nonce': self.$nonce,
                'checkin-date': self.$checkinDate,
                'article-id': self.$checkinArticleId,
                'program-id': self.$checkinProgramId,
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

                    jQuery(elem).data('__persisted', true);

                    var ul = jQuery(elem).parents('ul');

                    var index = jQuery(ul).children('li').index(jQuery(elem).parent());
                    console.log("index: ", index);

                    if (null !== jQuery(ul).data('activeIndex')
                        && index == jQuery(ul).data('activeIndex'))
                        return false;

                    console.log("activeIndex..??");

                    jQuery(ul)
                        .find('input[type=radio]')
                        .parent('li')
                        .removeClass('active');

                    jQuery(elem)
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

                    var selectionSlideUp = function( self ) {
                        var activeItemOffsetTop = index * self.$itemHeight;

                        jQuery(ul)
                            .css('margin-top', (activeItemOffsetTop) + 'px')
                            .animate({
                                marginTop: 0
                            }, 200 * index, function() {
                                notificationAnimation( function() {
                                    jQuery(ul)
                                        .parents('fieldset.did-you')
                                        .append('<button class="e20r-button edit-selection">Edit check-in</button>');

                                    jQuery(document).on('click', '#e20r-daily-checkin-canvas .edit-selection', function(){
                                        console.log("Clicked the edit-selection button");
                                        console.log("This = ", this );
                                        console.log("Self = ", self);
                                        self.editCheckin( this );
                                    });

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

                            selectionSlideUp( self );

                            once = 1;
                        });

                    jQuery(ul)
                        .data('activeIndex', index)
                        .data('updateMode', true);

                    console.log("Set .data: ");
                }

            });

        },
        editCheckin: function( elem ) {

            var button = jQuery(elem);

            jQuery(button)
                .parents('fieldset.did-you')
                .find('ul')
                .find('li')
                .removeClass('concealed')
                .css('display', 'block');

            jQuery(button)
                .remove();

        },
        dayNav: function( self, elem ) {

            var NextDay = jQuery(elem).next("input[name='e20r-checkin-day']").val();

            var data = {
                action: 'daynav',
                'e20r-checkin-nonce': self.$nonce,
                'checkin-date': self.$checkinDate,
                'article-id': self.$checkinArticleId,
                'program-id': self.$checkinProgramId,
                'e20r-checkin-day': jQuery(elem).next("input[name='e20r-checkin-day']").val()
            }

            console.log("toNext data: ", data);

            jQuery.post(e20r_checkin.url, data, function(response) {

                if ( ! response.success ) {

                    var $string;
                    $string = "An error occurred while trying to load the requested page. If you\'d like to try again, please reload ";
                    $string += "this page, and click your selection once more. \n\nIf you get this error a second time, ";
                    $string += "please contact Technical Support by using our Contact form ";
                    $string += "at the top of this page.";

                    alert($string);

                    return;
                }
                else {

                    jQuery('#e20r-daily-progress').html(response.data);

                    self.bindProgressElements( self );

                }

            });

        }
    };

    e20rCheckinEvent.init();
});
