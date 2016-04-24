/**
 * Created by Administrator on 2015/8/10.
 */
$(function () {
    var $main = $('#main'),
        //存储单前页
	    current = {},
        //数据model,整个个js只有一个
        model = {},
        aid = $('#aid').val(),
        sub_status = $('#sub_status').val();//活动结束标识

    var server = Util.server,
        utils = Util.utils;

    (function(){
        var $phoneInput = $('.section-form').children('input').eq(0);
        $phoneInput.attr('type','tel').addClass('tel-input');
    })();

    /*[2015-10-27]*/
    if (location.hash.slice(-2) == 'p2') {
        history.pushState({"page": "p2"}, "" , "?activity_id="+aid+"#page=p2");
        current.page = "p2";

        $('#p1')
            .addClass('page-prev')
            .removeClass("page-active");
        $('#p2')
            .addClass('page-active')
            .removeClass('page-next');
        $('.section-footer').hide();

        if ( sub_status == 4 ) {
            $('#next').hide();
            oAlert('活动已结束');
        } else if ( sub_status == 5 ) {
            $('#next').hide();
            oAlert('报名已结束');
        }
    } else {
        //初始化history.state ,并设置current page id 为1
        history.pushState({"page": "p1"}, "" , "?activity_id="+aid+"#page=p1");
        current.page = "p1";
    }
    
    //过渡动画函数
    function pageSlideOver(){
        $('.page-out').on('transitionend', function(){
            $(this).removeClass('page-out');
        });
        $('.page-in').on('transitionend', function(){
            $(this).removeClass('page-in');
        });
    }
	//页面切换通用逻辑
	function transform(seletor,page){
		var $pageTo = $('#'+$(seletor).data("page")),
            //name = $(this).data("page").substring(1);
            name = $(seletor).data("page");
		//缓存目标页的page id
		current.page = name;
        console.log($pageTo);
        console.log(name);

        if ( page ) {
             $(page).removeClass('page-active').addClass('page-prev page-out');
        }else {
            $(seletor).parents('.page').removeClass('page-active').addClass('page-prev page-out');
        }

        $pageTo.removeClass('page-next').addClass('page-active page-in');
        pageSlideOver();

        history.pushState({"page": name}, "" , "?activity_id="+aid+"#page="+name);
	}

    //活动结束标识
    if ( sub_status == 5 ) {
        $('#baoming-Btn').css('background','#bebebe').text('活动已结束');
    } else if ( sub_status == 4 ) {
        $('#baoming-Btn').css('background','#bebebe').text('报名已结束');
    }
    /**
     * 对应dom元素事件监听
     */
    //展开活动信息
    $('#ac-more').on('click', function(event) {
        var ac = $('.ac-des'),
            or = '';
        ac.toggleClass('ac-des--hidden');
        if (ac.hasClass('ac-des--hidden')) {
            $(this)[0].innerHTML = '展开&or;';
        }else {
            $(this)[0].innerHTML = '收起&and;';
        }
    });
    //点击报名查询
    $('#baoming-query').on('click', function(event) {
        event.preventDefault();
        //清空所有表单的的信息，和错误提示信息
        $('input[type="text"]').val('');
        transform(this);
        $("footer").hide();
    });
    //报名查询下一步按钮
    $('#query-next').on('click', function(event) {
        event.preventDefault();
        var $queryMobile = $('#query-num').val(),
            self = this,
            parms = {
                mobile : $queryMobile,
                activity_id : aid
            }
        //查询用户的报名状态
        server.queryUserStatus(parms,function(resp){
            if (resp.code) {
                alert(resp.message);
            }else {
                //模拟状态
                // resp.applicant = {
                //     status : 1
                // };
                if (!resp.applicant) {
                    $(self).data('page','p2');
                    $('.query-no-w').removeClass('hide').siblings('h3').addClass('hide');
                    transform(self);
                }else {
                    if (resp.applicant.status == 2) {
                        var url = 'http://' + window.location.host + '/wap/activity/pay?order_no=' + resp.applicant.order_no;
                        console.log(url);
                        console.log('/wap/wechat/oauth/go?redirect_url=' + encodeURIComponent(url));
                        window.location.href = '/wap/wechat/oauth/go?redirect_url=' + encodeURIComponent(url);
                         // window.location.href = 'http://192.168.1.102/wap/activity/pay?order_no=JH20150922210649560152698B581398';
                        
                    }else {
                        window.location.href = '/wap/activity/userStatus?activity_id='+aid+'&mobile='+resp.mobile+'&status='+resp.applicant.status+'&key=query';
                    }
                }
            }
        });
    });
    //点击报名按钮
    $('#baoming-Btn').on('click', function(e){
        //判断活动是否到期
        if ( sub_status == 5 || sub_status == 4 ) {
            return;
        }
        $('#p1')[0].scrollTop = 0;
        //清空所有表单信息
        $('input[type="text"]').val('');
        transform(this,'#p1');
        $("footer").hide();
    });
    //点击下一步
    $('#next').on('click', function(e){
        var status = 1,
            $input = $('#p2').find('.section-form').children('input'),
            mobile = $input.eq(0).val(),
            self = this;
        model.attrs = [];
        //错误信息清空
        $(".error-mes").text('');

        $input.each(function(index) {
            if (!$(this).val()) {
                alert($(this).attr('name')+'不能为空');
                status = 0;
                return false;
            }
            model.attrs.push({"key":$(this).attr('name'),"value":$(this).val()});
        });
        if ( !status ) {
            status = 1;
            return;
        }
        if (!K.isMobile(mobile)) {
            alert("手机号码格式不正确");
            return;
        }
        //查询用户是否报名
        var parms = {
            mobile: mobile,
            activity_id: aid
        }
            //请求报名状态接口
        server.queryUserStatus(parms,function(resp){
            if (resp.code) {
                alert(resp.message);
            }else {
                //模拟状态
                // resp.applicant = {
                //     status : 3
                // };
                if (!resp.applicant) {
                     console.log(model.attrs);
                    $('#phone-num').text(utils.addSpace(mobile));
                    transform(self);
                }else {
                    if (resp.applicant.status == 2) {
                        var url = 'http://' + window.location.host + '/wap/activity/pay?order_no=' + resp.applicant.order_no;
                        console.log(url);
                        console.log('/wap/wechat/oauth/go?redirect_url=' + encodeURIComponent(url));
                        window.location.href = '/wap/wechat/oauth/go?redirect_url=' + encodeURIComponent(url);
                        // window.location.href = 'http://192.168.1.102/wap/activity/pay?order_no=JH20150922210649560152698B581398';
                    }else {
                        window.location.href = '/wap/activity/userStatus?activity_id='+aid+'&mobile='+resp.mobile+'&status='+resp.applicant.status+'&key=next';
                    }
                }
            }
        });
    });
    //获取验证码
    $('#get-captcha').on('click', function(e){
        // if (!$('#captcha').val()) {
        //     alert('请输入验证码。');
        //     return false;
        // }
        // transform(this);
        //判断是否在短信等待时间内
        if ($(this).hasClass('waiting')){
            return;
        }
        //发送验证码时清除错误信息
         $(".error-mes").text('');

        var captchaBtn = $(this); 
        var parms = {};
        parms.mobile = $('.section-form').children('input').eq(0).val();
        console.log(parms);
        server.getCaptcha(parms,function(resp){
            console.log(resp);
            if ( resp.code == 0 ) {
                captchaBtn.addClass('waiting');
                alert(resp.message);
                K.captchaCountDown(captchaBtn,resp.send_interval,function(){
                    captchaBtn.removeClass('waiting');
                });
            }else {
                $(".error-mes").text(resp.message || "发送失败。");
            }
        });
    });
    //报名验证
    $('#submit').on('click', function(e){
        var self= this;
        if (!$('#captcha').val()) {
            $('.error-mes').text('请输入验证码。');
            return false;
        }
        var parms = {};
        parms.activity_id = $("#aid").val();
        parms.code = $("#captcha").val();
        parms.attrs = JSON.stringify(model.attrs);//controller里要求传的是字符串
        parms._token = $('input[name="_token"]').val();

        // if ($('#auditing').val() == 0 && $('#ft').val() == 3) {
        //     var url = 'http://' + window.location.host + '/wap/activity/pay?activity_id='+aid+'&mobile='+$('.section-form').children('input').eq(0).val()+'&order_no=1231231231qwerqwer12121212121212';
        //     console.log(url);
        //     console.log('wap/wechat/oauth/go?redirect_url=' + encodeURIComponent(url));
        //     window.location.href = '/wap/wechat/oauth/go?redirect_url=' + encodeURIComponent(url);
        // }
        server.regist(parms,function(resp){
            console.log(resp);
            if (resp.code == 0) {
                //如果有订单
                // if ( resp.info.order_no ) {
                    if ($('#auditing').val() == 0 && $('#ft').val() == 3) {
                        var url = 'http://' + window.location.host + '/wap/activity/pay?order_no='+resp.info.order_no;
                        console.log(url);
                        console.log('/wap/wechat/oauth/go?redirect_url=' + encodeURIComponent(url));
                        window.location.href = '/wap/wechat/oauth/go?redirect_url=' + encodeURIComponent(url);
                        // window.location.href = 'http://192.168.1.102/wap/activity/pay?order_no=JH20150922210649560152698B581398';
                    }else {
                        transform(self);
                    }
                // }
                // transform(self);
            }else if (resp.code == 10401) {
                $('.section-succeed').hide();
                $('.section-refuse').show();
                transform(self);
            }else{
                $('.error-mes').text(resp.message);
                return false;
            }
        });
    });

    //监听浏览器返回和前进按钮
    window.addEventListener('popstate', function(e) {
		console.log('current.page:'+current.page);
        // oAlert(current.page);
        // oAlert(e.state.page);
        if ( !e.state ) {
            return;
        }
        var pageOut = $("#"+current.page),
            pageIn = $("#"+e.state.page);
        if ( e.state.page && e.state.page == 'p1' ){
            $('footer').show();
        }
        if ( current.page == 'p1' ) {
            // oAlert('aa');
            window.location.reload();
            return;
        }
        //如果为p4，则退回到主页
        if ( current.page == 'p4' ) {
            window.location.reload();
            return;
        }
        if ( current.page == 'p2' && !$('.query-no-w').hasClass('hide')) {
            window.location.reload();
            return;
        }
        if( e.state.page.substring(1) < current.page.substring(1) ){//后退
            pageOut.removeClass('page-active').addClass('page-next page-out');
            pageIn.removeClass('page-prev').addClass('page-active page-in');
            pageSlideOver();
        }else if ( e.state.page.substring(1) > current.page.substring(1) ) {//前进
            pageOut.removeClass('page-active').addClass('page-prev page-out');
            pageIn.removeClass('page-next').addClass('page-active page-in');
            pageSlideOver();
        }else if( !e.state || (current.page === 'p1' && current.page === e.state.page) ) {
            console.log('已经是第一页了，不能再往后退了');
            return;
        }

		current.page = e.state.page;
        console.log(current.page);
    });
});




//工具函数
(function($){
	var _utils = {
		//手机号增加空格处理
        addSpace : function(mobile){
            //13211112222
            var a = mobile.split('');
            a.splice(3, 0, '-');
            a.splice(8, 0 ,'-');
            var b = a;
            var c = b.join('').replace(/-/g,' ');
            return c;
        },
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
		},
        is_weixin : function(){//判断是否为微信浏览器
            var ua = window.navigator.userAgent.toLowerCase();
            if ( ua.match(/MicroMessenger/i) == 'micromessenger' ) {
                return true;
            } else {
                return false;
            }
        },
        tempHide : function(b){
            if (!b) {
                $('.down-banner').hide();
                $('#p4').find('.t-b').hide();
                $('#p4').find('.b-b').hide();
            }else {
                $('.down-banner').show();
                $('#p4').find('.t-b').show();
                $('#p4').find('.b-b').show();
            }
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
					callback(data);
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
			this.send('get','/community/web/activity/applicant/verifycode',parms,callback);
		},
        queryUserStatus: function(parms,callback){
            this.send('get','/wap/activity/applicant/search',parms,callback);
        }
	}	

    window.Util = {
        server : server,
        utils : _utils
    }
})(Zepto)
