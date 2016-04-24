<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, minimal-ui" />
    <title>创业不孤单 牵手过新年-2016武侯区单身联谊会</title>
    <link rel="stylesheet" href="{{$prefix}}/static/xinnian/css/main.min.css?201510171227">
    <script src="{{$prefix}}/static/xinnian/js/build/react-with-addons-v0.13.3.min.js"></script>
    <script src="{{$prefix}}/static/xinnian/js/build/ReactRouter-v0.13.3.min.js"></script>
    <script src="{{$prefix}}/static/xinnian/js/jquery-1.11.3.min.js"></script>
    <script src="{{$prefix}}/static/xinnian/js/common.min.js"></script>

    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
</head>
</head>
<body>

<div id="vote" class="vote"></div>

<script>
    var openId = '{{$wechat_openid}}';
    var wechat_success = '{{$wechat_success}}';
    var wechat_session = '{{$wechat_session}}';
    var route_name = null;
    var is_weixin = false;
    var ua = window.navigator.userAgent.toLowerCase();
    if (ua.match(/MicroMessenger/i) == 'micromessenger') { // 微信浏览器
        is_weixin = true;
    }
    if ((is_weixin && !openId && wechat_session == 0)) {
        location.href = '/wap/wechat/oauth/go?redirect_url='+ location.origin +'/wap/xinnian&is_scope_userinfo=1';
    }
</script>

{!! csrf_field() !!}

<script type="text/javascript">
    var Router = ReactRouter;
    var Route = ReactRouter.Route;
    var RouteHandler = ReactRouter.RouteHandler;
    var Link = ReactRouter.Link;
    var StateMixin = ReactRouter.State;
    var DefaultRoute = ReactRouter.DefaultRoute;
    var prefix = '{{ $prefix }}';
    var isDue = '{{ $isDue }}' == '1';
</script>


{{--<script type="text/jsx" src="/static/xinnian/js/home.js"></script>--}}
{{--<script type="text/jsx" src="/static/xinnian/js/entry.js"></script>--}}
{{--<script type="text/jsx" src="/static/xinnian/js/detail.js"></script>--}}
{{--<script type="text/jsx" src="/static/xinnian/js/rules.js"></script>--}}
{{--<script type="text/jsx" src="/static/xinnian/js/footer.js"></script>--}}
<script type="text/javascript" src="{{$prefix}}/static/xinnian/js/bundle.min.js?v=100"></script>

<script src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
<script>
    var appId = '{{ $sign_package["appId"] }}',
        timestamp = '{{ $sign_package["timestamp"] }}',
        nonceStr = '{{ $sign_package["nonceStr"] }}',
        signature = '{{ $sign_package["signature"] }}';

    var actTitle = '创业不孤单 牵手过新年-2016武侯区单身联谊会',
        actLink  = location.origin + '/wap/xinnian',
        actDesc  = '2016成都武侯区单身青年联谊会是服务武侯区内外青年的跨年度活动。',
        actImg   = 'http://' + location.host + '/static/xinnian/images/title.png';

    wx.config({
       // debug: true,
        appId: appId,
        timestamp: timestamp,
        nonceStr: nonceStr,
        signature: signature,
        jsApiList: ['onMenuShareTimeline', 'onMenuShareAppMessage']
    });

    wx.ready(function() {
        wx.onMenuShareTimeline({
            title: actTitle, // 分享标题
            link: actLink, // 分享链接
            imgUrl: actImg, // 分享图标
            success: function () { 
                // 用户确认分享后执行的回调函数
            },
            cancel: function () { 
                // 用户取消分享后执行的回调函数
            }
        });  

        wx.onMenuShareAppMessage({
            title: actTitle, // 分享标题
            desc: actDesc, // 分享描述
            link: actLink, // 分享链接
            imgUrl: actImg, // 分享图标
            dataUrl: '', // 如果type是music或video，则要提供数据链接，默认为空
            success: function () { 
                // 用户确认分享后执行的回调函数
            },
            cancel: function () { 
                // 用户取消分享后执行的回调函数
            }
        });
    })
</script>
</body>
</html>