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
        var $phone = jQuery('input[name="phone"]').val();

        jQuery.ajax({
            method: 'post',
            url: ipAjaxVar.ajaxurl, 
            data: {
                action: 'kck_create_member',
                firstName: $fname,
                secondName: $sname,
                email: $email,
                phone: $phone
            }
        }).done(function(response) {
            if (response.success) {
                alert('Member created successfully');
                // Optionally, refresh the member list or update the UI
            } else {
                alert('Failed to create member: ' + response.data.message);
            }
        }).fail(function() {
            alert('AJAX request failed');
        });
    });
}