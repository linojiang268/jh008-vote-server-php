$(function(){
	var server = K.server;

	var myValidate = K.util.myValidate,
		regexp = K.util.myValidate.regexp,
		server = K.server;

	$('#loginForm').validate({
		rules: {
			username: "required",
			password: "required"
		},
		debug: true,
		messages: {
		  username: '用户名不能为空',
		  password: "密码不能为空"
		},
		keyup: false,
		errorPlacement: function(error, element) {
			$("#error-tip").text(error[0].innerHTML);
		},
		submitHandler: function(form) {
			$("#error-tip").text('');
			var result = {};
			result.user_name = $.trim($('#username').val());
			result.password = $.trim($('#password').val());
			if ($('#remember:checked').length) {
				result.remember = 1;
			}
			result._token = $('input[name="_token"]').val();
			/*server.login(result, function(resp) {
				if (resp.code == 0) {
				} else {
					$("#error-tip").text(resp.message || '登录出错');
				}
			})*/
			form.submit();
		}
	});

})


 
