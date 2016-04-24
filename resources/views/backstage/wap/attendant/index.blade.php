<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, minimal-ui" />
    <title>2015国际空乘就业推介大赛</title>
    <!--<link rel="stylesheet" href="/static/attendant/css/normalize.css">
    <link rel="stylesheet" href="/static/plugins/webuploader/webuploader.css">
    <link rel="stylesheet" href="/static/attendant/css/main.css">
    <link rel="stylesheet" href="/static/attendant/css/modal.css">-->
    <link rel="stylesheet" href="{{$prefix}}/static/attendant/css/main.min.css">
    <script src="{{$prefix}}/static/attendant/js/build/react-with-addons-v0.13.3.min.js"></script>
    <!--<script src="/static/attendant/js/build/JSXTransformer.js"></script>-->
    <script src="{{$prefix}}/static/attendant/js/build/ReactRouter-v0.13.3.min.js"></script>
    <script src="{{$prefix}}/static/attendant/js/jquery-1.11.3.min.js"></script>
    <script src="{{$prefix}}/static/attendant/js/common.min.js"></script>
    <!--<script src="/static/attendant/js/common.js"></script>
    <script src="/static/attendant/js/server.js"></script>
    <script src="/static/attendant/js/ajaxfileuploader.js"></script>-->
</head>
</head>
<body>

<div id="vote" class="vote"></div>

<script>
    var mobile = '{{$mobile}}';
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
        location.href = '/wap/wechat/oauth/go?redirect_url='+ location.origin +'/wap/attendant&is_scope_userinfo=1';
    }
</script>

{!! csrf_field() !!}

<script type="text/javascript">
    var Router = ReactRouter; // 由于是html直接引用的库，所以 ReactRouter 是以全局变量的形式挂在 window 上
    var Route = ReactRouter.Route;
    var RouteHandler = ReactRouter.RouteHandler;
    var Link = ReactRouter.Link;
    var StateMixin = ReactRouter.State;
    var DefaultRoute = ReactRouter.DefaultRoute;
    var prefix = '{{ $prefix }}';
</script>



<!--<script type="text/jsx" src="/static/attendant/js/home.js"></script>
<script type="text/jsx" src="/static/attendant/js/entry.js"></script>
<script type="text/jsx" src="/static/attendant/js/download.js"></script>
<script type="text/jsx" src="/static/attendant/js/detail.js"></script>
<script type="text/jsx" src="/static/attendant/js/rules.js"></script>
<script type="text/jsx" src="/static/attendant/js/footer.js"></script>-->
<script type="text/javascript" src="{{$prefix}}/static/attendant/js/bundle.js?v=3334"></script>
<script type="text/javascript">

function init(){
    // init
    //React.render(<Footer />, document.getElementById('footer'));

    // 路由应用入口
    var App = React.createClass({displayName: "App",
        render: function() {
            return (
                React.createElement("div", {className: "main"}, 
                    React.createElement("div", {className: "app", id: "app"}, 
                        this.props.isUs ? React.createElement(DownloadPanel, {isUs: true}) : React.createElement(RouteHandler, null)
                    ), 
                    React.createElement("footer", {className: "footer", id: "footer"}, 
                        React.createElement(Footer, null)
                    )
                )
            );
        }
    });

    // 定义页面上的路由
    var routes = (
        React.createElement(Route, {handler: App}, 
            React.createElement(Route, {name: "home", handler: HomePanel}), 
            React.createElement(Route, {name: "entry", handler: EntryPanel}), 
            React.createElement(Route, {name: "rules", handler: RulesPanel}), 
            React.createElement(Route, {name: "detail", path: "/user/:id", handler: Detail}), 
            React.createElement(DefaultRoute, {handler: HomePanel})
        )
    );

    // 将匹配的路由渲染到 DOM 中
    Router.run(routes, Router.HashLocation, function(Root, options){
        var routeName = options.path.split('/')[1] || 'home';
        if (routeName) {
            route_name = routeName;
        }

        if(mobile || openId) {
            React.render(React.createElement(Root, {datakey: route_name}), document.getElementById('vote'));
        } else {
            React.render(React.createElement(Root, {isUs: true, datakey: routeName}), document.getElementById('vote'));
        }
    });
}

if ((is_weixin && !openId)) {
    if (wechat_session == 0)
    {
        location.href = '/wap/wechat/oauth/go?redirect_url='+ location.origin +'/wap/attendant&is_scope_userinfo=1';
    } else {
        K.aModal({
            title: '微信授权',
            content: '微信授权成功即可参与投票，点击确定去授权',
            okCallback: function() {
                location.href = '/wap/wechat/oauth/go?redirect_url='+ location.origin +'/wap/attendant&is_scope_userinfo=1';
            }
        });
    }
} else {
    init();
}
</script>

<!-- <script src="/static/plugins/webuploader/webuploader.min.js"></script> -->
<script src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
<script>

    var appId = '{{ $sign_package["appId"] }}',
        timestamp = '{{ $sign_package["timestamp"] }}',
        nonceStr = '{{ $sign_package["nonceStr"] }}',
        signature = '{{ $sign_package["signature"] }}';

    var actTitle = '想飞就飞！2015国际空乘就业推介大赛火热报名中',
        actLink  = location.origin + '/wap/attendant',
        actDesc  = '报名、投票火热进行中，更有iPhone 6S等神秘大奖等你拿！';
        actImg   = 'http://' + location.host + '/static/attendant/images/title.png';

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