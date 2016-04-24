$(function() {
    var DialogUi = K.dialogUi,
        server = K.server;
        
    function getRole() {
        var roleTarget = $('#roleSelect'),
            roleName   = roleTarget.find('.ui-select-text').text(),
            dropMenu   = roleTarget.find('.dropdown-menu a'),
            role;
        $.each(dropMenu, function(i, dropItem) {
            if (roleName == dropMenu.eq(i).text()) {
                role = dropMenu.eq(i).attr('data-role');
            }
        })
        return role;
    }

    $('#registerForm').validate({
        rules: {
            user_name: "required",
            password: "required",
            confirm_password: {
                equalTo: "#password",
                required: true
            }
        },
        debug: true,
        messages: {
            user_name: '用户名不能为空',
            password: "密码不能为空",
            confirm_password: {
                required: '确认密码不能为空',
                equalTo: '两次密码输入不相同'
            }
        },
        keyup: false,
        submitHandler: function(form) {
            var parms = {};
            parms.user_name = $('#user_name').val().trim();
            parms.password  = $('#password').val().trim();
            parms._token    = $('input[name="_token"]').val();
            parms.role = getRole();
            server.createAccount(parms, function(resp) {
                if (resp.code == 0) {
                    DialogUi.alert(parms.user_name + '已创建成功!');
                    $('#user_name').val('');
                    $('#password').val('');
                    $('#confirm_password').val('');
                } else {
                    DialogUi.alert(resp.message || '创建账户失败!');
                }
            })
        }
    });

})