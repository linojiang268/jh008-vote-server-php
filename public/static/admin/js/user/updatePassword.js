$(function() {
    var DialogUi = K.dialogUi,
        server = K.server;

    $('#updatePasswordForm').validate({
        rules: {
            old_password: "required",
            password: "required",
            confirm_password: {
                equalTo: "#password",
                required: true
            }
        },
        messages: {
            old_password: "旧密码不能为空",
            password: "密码不能为空",
            confirm_password: {
                required: '确认密码不能为空',
                equalTo: '两次密码输入不相同'
            }
        },
        keyup: false,
        submitHandler: function(form) {
            var params = {};
            params.old_password = $('#old_password').val().trim();
            params.password = $('#password').val().trim();
            params._token = $('input[name="_token"]').val();
            server.updatePassword(params, function(resp) {
                if (resp.code == 0) {
                    DialogUi.message('修改成功');
                    $('#old_password').val('');
                    $('#password').val('');
                    $('#confirm_password').val('');
                } else {
                    DialogUi.message(resp.message || '修改密码出错了!');
                }
            })
        }
    });

})