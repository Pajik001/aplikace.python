function initkckevidence() {
 
    //save booking
    jQuery('button.saveEntry').on("click", function(e) {
        e.preventDefault();

        var $slotList = [];

        var $uId = jQuery('input[name="uID"]').val();
        var $fname = jQuery('input[name="firstName"]').val();
        var $sname = jQuery('input[name="secondName"]').val();

        jQuery(".entry-selected").each(function(index) {
            $slotList.push(new Slot(jQuery(this)));
        });

        //validation
        $dlgValid = false;
        $msg = validateValuesLsg($uId, $fname, $sname);
        if ($msg == "") {
            $dlgValid = true; 
        } else {
            ConfirmDialog($msg);
        }

        if ($dlgValid) {

            jQuery('.kck-inlinedialog-wrapper').hide();
            jQuery('.kck-msg-area').show();

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
        }

    });

    //show all bookings
    jQuery('button.bookingList').on("click", function(e) {
        e.preventDefault();

        jQuery.ajax({
            method: 'post',
            url: ipAjaxVar.ajaxurl,
            data: {
                action: 'kck_get_entry_list'
            }
        }).done(function(result) {
            jQuery("#kck-reservation").html(result);
            initKCKReservationList();
        });

    });

    jQuery('button.bookingPage').on("click", function(e) {
        e.preventDefault();

        jQuery.ajax({
            method: 'post',
            url: ipAjaxVar.ajaxurl,
            data: {
                action: 'kck_show_members'
            }
        }).done(function(result) {
            jQuery("#kck-reservation").html(result);
            initKCKReservation();
        });

    });
}
