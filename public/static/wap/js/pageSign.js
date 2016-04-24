//工具函数
(function($){
	var _utils = {
		validateChinese : function(word){
            var reg = /^[u4E00-u9FA5]+$/;
      			if(!reg.test(str)){
					alert('请您输入汉字。');
       				return false;
      			}
      		return true;
        },
		validateStr : function(str){
			var reg = /[0-9]/g;
				if(reg.test(str)){
					alert("不能含有数字。");
					return false;		
				}
			return true;
		}
	}
	//ajax 封装
	var server = {
		send : function(t,url,option,callback){
			$.ajax({
				type: t,
				url: url,
				data: option,
				dataType: 'json',
				success: function(data){
					if (callback) {
						callback(data);
					}
 				},
				error: function(xhr, errorType, error){
					console.log(error);
				}
			});
		},
		regist : function(parms,callback){
			this.send('post','/community/web/activity/applicant',parms,callback)	
		},
		getCaptcha : function(parms,callback){
			this.send('get','/api/activity/checkin/verifycode',parms,callback);
		},
		getImgCaptcha : function(parms,callback){
			this.send('get','/captcha',parms,callback);
		},
		phoneSubmit : function(parms,callback) {
			this.send('post','/api/activity/checkin/quick',parms,callback);
		} 
	}	

    window.Util = {
        server : server
    }
})(Zepto)

$(function(){
	
	var server = Util.server;

	var aid = $('#aid').val(),
		_token = $('input[name="_token"]').val();
	
	//获取验证码
	// $('#get-captcha').on('click', function(event) {
	// 	//如果在60秒的等待时间内，则不能再次点击
	// 	if ($(this).hasClass('waiting')) {
	// 		return;
	// 	}
	// 	var mobile = $('#mobile_num').val();
	// 	var parm = {},
	// 		captchaBtn = $(this);

	// 	if (!mobile) {
	// 		alert('请输入手机号');
	// 		return;
	// 	}
	// 	if (!K.isMobile(mobile)) {
	// 		alert('手机号码格式不正确');
	// 		return;
	// 	}

	// 	parm.mobile = mobile;
	// 	parm.activity_id = aid;

	// 	server.getCaptcha(parm,function(resp) {
	// 		console.log(resp);
	// 		if (resp.code == 0) {
	// 			alert(resp.message);
	// 			captchaBtn.addClass('waiting');
	// 			K.captchaCountDown(captchaBtn,resp.send_interval,function(){
	// 				captchaBtn.removeClass('waiting');
	// 			});
	// 		}else {
	// 			window.location.href = '/wap/activity/failed';
	// 		}
	// 	});
	// });
	function refreshCaprcha(){
		console.log('captcha start refresh。');
		$('.captcha-loading').show();
		var $captcha = $('#img-captcha');
		$captcha.attr('src','/captcha?'+Math.random());
	}

	(function(){
		var captcha = $('#img-captcha')[0];
		captcha.onload = function(){
			$('.captcha-loading').hide();
			console.log('captcha onload');
		}
	})();

	//图形验证码点击
	$('#img-captcha').on('click', function(e) {
		refreshCaprcha();
		$('#captcha').val('');
		$('.error-mes').text('');
	});

	$('#captcha').on('focus', function(e) {
		$('.error-mes').text('');
	});
	//提交
	$('#submit').on('click', function(event) {
		var captcha = $('#captcha').val(),
			mobile = $('#mobile_num').val();
		if (!K.isMobile(mobile)) {
			alert('手机号错误，请重新输入');
			return;
		}
		if (!captcha) {
			alert('请输入验证码');
			return;
		}
		var parm = {};
		parm.captcha =  captcha;
		parm.mobile = mobile;
		parm.activity = aid;
		parm._token = _token;
		
		server.phoneSubmit(parm,function(resp){
			console.log(resp);
			if (resp.code == 0) {
				// window.location.href='/wap/activity/info?activity_id=' + aid + '&mobile=' + mobile;	
				window.location.href = '/wap/activity/failed';
			}else {
				$('.error-mes').text(resp.message);
				refreshCaprcha();
				$('#captcha').val('');
			}
			
		});
	});

	$(".annex-w").on('click', 'a', function(event) {
		alert('请下载app查看');
	});

	$('.more-arrow').on('click', function(event) {
		var pitem = $('.process-item');
		pitem.toggleClass('hiding');
		if (!pitem.hasClass('hiding')) {
			$(this)[0].innerHTML = "收起&and;"
		}else {
			$(this)[0].innerHTML = "<i>......</i>more&or;"
		}
	});
	if ($('.process-item').height() > 232) {
		$('.process-item').addClass('hiding');
		$('.more-arrow').removeClass('hiding');
	}
});