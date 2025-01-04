jQuery(document).ready(function ($) {
    $('#bionic-form').on('submit', function (e) {
        e.preventDefault();
        let formData = new FormData(this);
        formData.append('action', 'submit_form');
        formData.append('nonce', bionicAjax.nonce);

        $.ajax({
            url: bionicAjax.ajax_url,
            type: 'POST',
            processData: false,
            contentType: false,
            data: formData,
            success: function (response) {
                alert(response.data.message);
                if (response.success) {
                    $('#bionic-form')[0].reset();
                }
            },
            error: function () {
                alert('An error occurred.');
            },
        });
    });
});
