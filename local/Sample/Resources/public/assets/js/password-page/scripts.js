var authForm = {
    onSubmit: function() {
        var pwdInput = $("input:first");

        $.ajax({
            method: 'POST',
            data: $('#authForm').serialize(),
            dataType: "json",
            success: function(data) {
                if (data && data.status && data.status === true) {
                    location.reload();
                } else {
                    pwdInput.addClass('error').val('');
                }
            },
            error: function () {
                pwdInput.addClass('error').val('');
            }
        });

        return false;
    }
};

$(function() {
    var form = $('#authForm');
    form.on('submit', authForm.onSubmit);
});