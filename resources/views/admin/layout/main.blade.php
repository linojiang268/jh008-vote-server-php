<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <title>集合啦后台管理系统 - @yield('title')</title>

    <link rel="stylesheet" href="/static/css/common/reset.css"/>
    <link rel="stylesheet" href="/static/css/common/base.css"/>
    <link rel="stylesheet" href="/static/css/iconfont/iconfont.css"/>
    <link rel="stylesheet" href="/static/css/common/lc_ui.css"/>
    <link rel="stylesheet" href="/static/css/common/lc_layer.css"/>
    <link rel="stylesheet" href="/static/plugins/ktable/skins/k-table.css"/>
    <link rel="stylesheet" href="/static/css/common/common.css"/>
    <link rel="stylesheet" href="/static/admin/css/common.css"/>
    @yield('stylesheet')

</head>
<body>

    <div class="head-warp">
        <div class="header-bot"></div>
        <div class="head-body">
            <ul class="head-nav clearfix">
                <li><a class="head-nav-item logout" href="/admin/logout" ><i class="icon exit-icon"></i><span class="tip">退出</span></a></li>
            </ul>
            <a class="name" href="javascript:;">                
                欢迎你</a>
            <a href="/admin" class="logo"></a>
        </div>
    </div>
    
    <div class="main-warp">
        <div class="browser" id="browser">
            <div class="p15">
                @yield('content')
            </div>
        </div>
        <div class="menu-warp">
            @if (Auth::user()->role === 'admin')
            <div class="menu">
                <div class="menu-head wallet">账号管理</div>
                <ul>
                    <li class="@if ($key == 'userList') active @endif"><a href="/admin/user">账号列表</a></li>
                    <li class="@if ($key == 'createUser') active @endif"><a href="/admin/user/create">创建用户</a></li>
                </ul>
            </div>
            @endif

            @if (Auth::user()->role === 'accountant')
            <div class="menu">
                <div class="menu-head wallet">财务管理</div>
                <ul>
                    <li class="active"><a href="/admin/accountant">财务列表</a></li>
                   <!--  <li class=""><a href="/admin/accountant/team">社团财务</a></li> -->
                </ul>
            </div>
            @endif

            @if (Auth::user()->role === 'operator')
            <div class="menu">
                <div class="menu-head wallet">社团管理</div>
                <ul>
                    <li class="@if ($key == 'teams') active @endif"><a href="/admin/teams">社团列表</a></li>
                    <li class="@if ($key == 'teamVerify') active @endif"><a href="/admin/teams/verify">审核列表</a></li>
                    <li class="@if ($key == 'teamAuthentication') active @endif"><a href="/admin/teams/authentication">认证列表</a></li>
                </ul>
            </div>
            <div class="menu">
                <div class="menu-head wallet">活动管理</div>
                <ul>
                    <li class="@if ($key == 'activities') active @endif"><a href="/admin/activities">活动列表</a></li>
                </ul>
            </div>
            <div class="menu">
                <div class="menu-head wallet">用户管理</div>
                <ul>
                    <li class="@if ($key == 'members') active @endif"><a href="/admin/members">用户列表</a></li>
                </ul>
            </div>
            <div class="menu">
                <div class="menu-head wallet">消息管理</div>
                <ul>
                    <li class="@if ($key == 'notices') active @endif"><a href="/admin/notices">消息推送</a></li>
                    <li class="@if ($key == 'systemNotices') active @endif"><a href="/admin/notices/system">系统消息</a></li>
                </ul>
            </div>
            <div class="menu">
                <div class="menu-head wallet">标签管理</div>
                <ul>
                    <li class="@if ($key == 'tags') active @endif"><a href="/admin/tags">标签列表</a></li>
                </ul>
            </div>
            @endif
            
            <div class="menu">
                <div class="menu-head wallet">我的设置</div>
                <ul>
                    <li class="@if ($key == 'updatePassword') active @endif"><a href="/admin/user/password/update">修改密码</a></li>
                </ul>
            </div>

            <a href="" id="sub-open" style=""></a>
        </div>
        <div class="clearfix"></div>
    </div>

    <div class="foot-warp">
        <div class="foot-body">
            <a href="/org_v2/default/about">关于我们</a> <span class="foot-split">&nbsp;</span>
            <a href="/org_v2/default/contact">联系我们</a> <span class="foot-split">&nbsp;</span>
            <a href="/org_v2/default/partners">合作伙伴</a>
            <br/>
            <span class="copyright">
                <a href="/org_v2/default/main">www.jhla.com.cn</a> 2015 &copy; All Rights Reserved <a href="http://www.miitbeian.gov.cn/publish/query/indexFirst.action" target="_blank">蜀ICP备14010211号</a>
            </span>
        </div>
    </div>

    <script src="/static/plugins/jquery-1.7.1.min.js"></script>
    <script src="/static/plugins/layer/layer.js"></script>
    <script src="/static/plugins/jquery.validate.js"></script>
    <script src="/static/plugins/ktable/utilHelper.js"></script>
    <script src="/static/plugins/ktable/k-paginate.js"></script>
    <script src="/static/plugins/ktable/k-table.js"></script>
    <script src="/static/js/common/json2.js"></script>
    <script src="/static/js/common/K.js"></script>
    <script src="/static/js/common/lc.js"></script>
    <script src="/static/js/common/base.js"></script>
    <script src="/static/js/common/dialogUi.js"></script>
    <script src="/static/admin/js/common/common.js"></script>
    <script src="/static/admin/js/common/server.js"></script>
    
    @yield('javascript')
</body>
</html>