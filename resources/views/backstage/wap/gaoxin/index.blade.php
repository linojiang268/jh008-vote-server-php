<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, minimal-ui" />
    <title>高新区第八届单身青年联谊会</title>
    <link rel="stylesheet" href="{{$prefix}}/static/gaoxin/css/main.min.css?201510171227">
    <script src="{{$prefix}}/static/gaoxin/js/build/react-with-addons-v0.13.3.min.js"></script>
    <script src="{{$prefix}}/static/gaoxin/js/build/ReactRouter-v0.13.3.min.js"></script>
    <script src="{{$prefix}}/static/gaoxin/js/jquery-1.11.3.min.js"></script>
    <script src="{{$prefix}}/static/gaoxin/js/common.min.js"></script>
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
        location.href = '/wap/wechat/oauth/go?redirect_url='+ encodeURIComponent(location.origin +'/wap/gaoxin') + '&is_scope_userinfo=1';
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
</script>


{{--<script type="text/jsx" src="/static/gaoxin/js/home.js"></script>--}}
{{--<script type="text/jsx" src="/static/gaoxin/js/entry.js"></script>--}}
{{--<script type="text/jsx" src="/static/gaoxin/js/detail.js"></script>--}}
{{--<script type="text/jsx" src="/static/gaoxin/js/rules.js"></script>--}}
{{--<script type="text/jsx" src="/static/gaoxin/js/footer.js"></script>--}}
<script type="text/javascript" src="{{$prefix}}/static/gaoxin/js/bundle.min.js?v=3338"></script>

<script src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
<script>

    var appId = '{{ $sign_package["appId"] }}',
        timestamp = '{{ $sign_package["timestamp"] }}',
        nonceStr = '{{ $sign_package["nonceStr"] }}',
        signature = '{{ $sign_package["signature"] }}';

    var actTitle = '高新区第八届单身青年联谊会',
        actLink  = location.origin + '/wap/gaoxin',
        actDesc  = '青春同路、缘聚高新，公益性交友相亲联谊活动。';
        actImg   = 'http://' + location.host + '/static/gaoxin/images/title.jpg';

    wx.config({
       // debug: true, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
        appId: appId, // 必填，公众号的唯一标识
        timestamp: timestamp, // 必填，生成签名的时间戳
        nonceStr: nonceStr, // 必填，生成签名的随机串
        signature: signature,// 必填，签名，见附录1
        jsApiList: ['onMenuShareTimeline', 'onMenuShareAppMessage'] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2
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