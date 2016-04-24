$(function(){

	var myValidate = K.util.myValidate,
		regexp = K.util.myValidate.regexp,
		server = K.server;

	var scrollStatus = 1;
	$(".loading-mask").fadeOut(350,function() {
		$(this).remove();
	})

	$(".anchor-login").click(function() {
		$(".container").fadeIn(20);
	})

	$('.anchor-reg').click(function() {
		layer.open({
		   	type: 1,
		    title: false,
		    closeBtn: false,
		    shadeClose: true,
		    area: ['430px', '380px'],
			content: $('.qr-warp'),
			success: function(layero, index) {
				layero.find('.qr-close').click(function() {
					layer.close(index);
				})
			}
		});
	})

	$(".login .closer").click(function() {
		$(".container").fadeOut(20);
	})

	$('#loginForm').validate({
		rules: {
			mobile: "isPhone",
			password: "required"
		},
		debug: true,
		messages: {
		  isPhone: '请输入正确的电话号码',
		  password: "密码不能为空"
		},
		keyup: false,
		errorPlacement: function(error, element) {
			$("#error-tip").text(error[0].innerHTML);
		},
		submitHandler: function(form) {
			$("#error-tip").text('');
			var result = {};
			result.mobile = $.trim($('#mobile').val());
			result.password = $.trim($('#password').val());
			if ($('#remember:checked').length) {
				result.remember = 1;
			}
			result._token = $('input[name="_token"]').val();
			server.login(result, function(resp) {
				if (resp.code == 0) {
					location.href = '/community/team/setting/profile';
				} else {
					$("#error-tip").text(resp.message || '登录出错');
				}
			})
		}
	});

	// pageinit
    (function(){
        function initHeight(){
            var wHeight = $(window).height();
            if ( wHeight <= 975 ) {
            	$('.page3 .bg,.page4 .bg').children('img').height(975);
            } else {
            	$('.page3 .bg,.page4 .bg').children('img').height(wHeight);
            }
            
        }
        $(window).resize(function () { initHeight(); });
        initHeight();

        $(window).scrollTop(0);

        // $('#fullpage').fullpage();
    })();
    
    $('.nav').on('click','li', function (e) {
    	scrollStatus = 0;
        var index = $(this).index();
        var pid = $(this).children('a').data('pid');
       	// console.log(pid);
        $.scrollTo('.page'+pid,850,{onAfter:function(){ scrollStatus = 1; }});
        $(this).addClass('on').siblings().removeClass('on');
        return false;
    });

    $('.close').click(function () {
        $('.aa').hide();
        $('.tip').show();
        $('#top-icon').removeClass('show');
    });

    $('.regist').click(function(){
        $('.aa').show();
        $('.tip').hide();
        $('#top-icon').addClass('show');
    });

    $('#top-icon').on('click', function(event) {
    	if (!$(this).hasClass('show')) {
    		$('.aa').show();
        	$('.tip').hide();
        	$(this).addClass('show');
    	}else {
    		$('.aa').hide();
        	$('.tip').show();
        	$(this).removeClass('show');
    	}
    });

    $(window).scroll(function(){
        if (!scrollStatus) {
            return;
        }
        var st = $(window).scrollTop();
        var n = $('.nav li');
        if (st < 1074) {
            n.eq(0).addClass('on').siblings().removeClass('on');
        }else if(st > 1074 && st < 4195) {
            n.eq(1).addClass('on').siblings().removeClass('on');
        }else if (st > 4195 && st < 4995){
            n.eq(2).addClass('on').siblings().removeClass('on');
        }else if (st > 4995) {
            n.eq(3).addClass('on').siblings().removeClass('on');
        }
    });
})