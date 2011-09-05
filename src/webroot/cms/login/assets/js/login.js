jQuery(function($){
    $('input:first').focus();
    $('#login_form').submit(function(){
        for (var id in {username:'',password:''}) {
            var input = $('#'+id);
            if (!input.val()) {
                input.focus().parents('tr').addClass('error');
                return false;
            } else {
                input.parents('tr').removeClass('error');
            }
        }
    }).find('a').click(function(){
        $(this).parents('form').submit();
        return false;
    });
});
