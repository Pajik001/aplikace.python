jQuery(document).ready(function() {
    initkckevidence();
    $('.categoryItem').on('click', function() {
        var categoryId = $(this).data('category-id');
        $.ajax({
            url: ipAjaxVar.ajaxurl,
            type: 'POST',
            data: {
                action: 'kck_get_members_by_category',
                category_id: categoryId
            },
            success: function(response) {
                if (response.success) {
                    $('#membersList').html(response.data.html);
                } else {
                    alert(response.data.message);
                }
            }
        });
    });
});

function initkckevidence() {
    //save booking
    jQuery('button.saveMember').on("click", function(e) {
        e.preventDefault();

        var $fname = jQuery('input[name="firstName"]').val();
        var $sname = jQuery('input[name="secondName"]').val();
        var $email = jQuery('input[name="email"]').val();
        var $phone = jQuery('input[name="phone"]').val();
        var $birth_Date = jQuery('input[name="birthDate"]').val();
        var $weight = jQuery('input[name="weight"]').val();

        jQuery.ajax({
            method: 'post',
            url: ipAjaxVar.ajaxurl, 
            data: {
                action: 'kck_create_member',
                firstName: $fname,
                secondName: $sname,
                email: $email,
                phone: $phone,
                birthDate: $birth_Date,
                weight: $weight
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