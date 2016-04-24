var HomePanel  = require('./home'),
    RulesPanel = require('./rules'),
    EntryPanel = require('./entry'),
    Detail = require('./detail'),
    Footer = require('./footer');

function init() {
    // 路由应用入口
    var App = React.createClass({
        displayName: "App",
        render: function() {
            return (
                React.createElement("div", {className: "main"}, 
                    React.createElement("div", {className: "app", id: "app"},
                        React.createElement(RouteHandler, null)
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
            //React.createElement(Route, {name: "download", handler: DownloadPanel}),
            React.createElement(DefaultRoute, {handler: HomePanel})
        )
    );

    // 将匹配的路由渲染到 DOM 中
    Router.run(routes, Router.HashLocation, function(Root, options){
        var routeName = options.path.split('/')[1] || 'home';
        if (routeName) {
            route_name = routeName;
        }

        React.render(React.createElement(Root, {datakey: route_name}), document.getElementById('vote'));
    });
}


if ((is_weixin && !openId)) {
    if (wechat_session == 0) {
        location.href = '/wap/wechat/oauth/go?redirect_url='+ location.origin +'/wap/xinnian&is_scope_userinfo=1';
    } else {
        K.aModal({
            title: '微信授权',
            content: '微信授权成功即可参与点赞，点击确定去授权',
            okCallback: function() {
                location.href = '/wap/wechat/oauth/go?redirect_url='+ location.origin +'/wap/xinnian&is_scope_userinfo=1';
            }
        });
    }
} else {
    init();
}