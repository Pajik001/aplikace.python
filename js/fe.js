jQuery(document).ready(function() {
   
    initLsgReservation();

});

function initkckevidence() {
 
    //save booking
    jQuery('button.saveMember').on("click", function(e) {
        e.preventDefault();

        var $fname = jQuery('input[name="firstName"]').val();
        var $sname = jQuery('input[name="secondName"]').val();

            jQuery.ajax({
                method: 'post',
                url: ipAjaxVar.ajaxurl,
                data: {
                    action: 'kck_create_booking',
                    userId:$uId,
                    firstName: $fname,
                    secondName: $sname,
                    slots: JSON.stringify($slotList)
                }
            }).done(function(result) {
                jQuery("#kck-reservation").html(result);
                initKCKReservation();
            });
        });

    }
