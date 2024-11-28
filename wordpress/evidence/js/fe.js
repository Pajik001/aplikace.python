jQuery(document).ready(function() {
   
    initkckevidence();

});

function initkckevidence() {
 
    //save booking
    jQuery('button.saveMember').on("click", function(e) {
        e.preventDefault();

        var $fname = jQuery('input[name="firstName"]').val();
        var $sname = jQuery('input[name="secondName"]').val();
        var $email = jQuery('input[name="email"]').val();

            jQuery.ajax({
                method: 'post',
                url: ipAjaxVar.ajaxurl, 
                data: {
                    action: 'kck_create_member',
                    firstName: $fname,
                    secondName: $sname,
                    email: $email,
                }
            }).done(function(result) {
                jQuery("#kck-evidence").html(result);
            });
        });

    }
