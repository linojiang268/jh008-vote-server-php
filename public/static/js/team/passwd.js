jQuery(function () {

    var submit = $("#submit"); 

    $('#loginForm').validate({
        rules: {
            oldPass: {
                required: true,
                rangelength: [6, 18]
            },
            newPass: {
                required: true,
                rangelength: [6, 18]
            },
            renewPass: {
                required: true,
                rangelength: [6, 18]
            }
        },
        messages: {
            oldPass: {
                required: '旧密码不能为空',
                rangelength: '密码长度必须在6-18位之间'
            },
            newPass: {
                required: '新密码不能为空',
                rangelength: '密码长度必须在6-18位之间'  
            },
            renewPass: {
                required: '再次输入新密码不能为空',
                rangelength: '密码长度必须在6-18位之间' 
            }
        },
        keyup: false,
        submitHandler: function(form) {
            var oldPass = $.trim($('#oldPass').val()),
                newPass = $.trim($('#newPass').val()),
                reNewPass = $.trim($('#reNewPass').val());
            // ajax 
        }
    });


});