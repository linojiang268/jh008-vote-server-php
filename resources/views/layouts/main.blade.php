<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="renderer" content="webkit">
    @yield('meta')
    <title>集合后台管理系统 - @yield('title')</title>
    <link rel="shortcut icon" href="/static/images/logo.ico" type="image/x-icon">
    <link rel="stylesheet" href="/static/css/common/reset.css"/>
    <link rel="stylesheet" href="/static/css/common/base.css"/>
    <link rel="stylesheet" href="/static/css/iconfont/iconfont.css"/>
    <link rel="stylesheet" href="/static/css/common/lc_ui.css"/>
    <link rel="stylesheet" href="/static/css/common/lc_layer.css"/>
    <link rel="stylesheet" href="/static/plugins/ktable/skins/k-table.css"/>
    <link rel="stylesheet" href="/static/css/common/common.css"/>
    <link type="text/css" rel="stylesheet" href="/static/plugins/jquery-ui-1.11.2.custom/jquery-ui.css">
    @yield('stylesheet')

</head>
<body>

    <div class="head-warp">
        <!-- <div class="header-bot"></div> -->
        <div class="head-body">
            <ul class="head-nav clearfix">
                @if ($team)
               <!--  <li><a class="head-nav-item" href="/community/team/statistics"><i class="icon count-icon"></i><span class="tip">数据统计</span></a></li>
               <li><a class="head-nav-item" href="/community/team/qrcode"><i class="icon qcode-icon"></i><span class="tip">二维码</span></a></li> -->
                @endif
                <li>
                    <div class="head-nav-item" href="javascript:;" >
                        <span class="text">@if ($team){{ $team->getName() }}@else &nbsp; @endif</span>
                        <span class="icon-arrow-down"></span>
                        <dl>
                            <dd><a class="logout" href="javascript:;">退出</a></dd>
                        </dl>
                    </div>
                </li>
            </ul>
            <a href="/community/team/setting/profile" class="logo"><img src="/static/images/logo-new.png" alt="" /></a>
        </div>
    </div>

    <div class="main-warp">
        <div class="browser" id="browser">
            <div class="p15">
                @yield('content')
            </div>
        </div>
        <div class="menu-warp">
            <div class="menu">
                <div class="menu-head club-info">社团</div>
                <ul>
                   <li class="@if ($key === 'profile' || $key === 'authentication' || $key === 'condition' || $key === 'passwd' || $key === 'bind' ) active @endif">
                    <a href="/community/team/setting/profile">社团信息</a>
                    </li>
                   <li class="@if ($key === 'managerMember' || $key === 'verifyPend' || $key === 'verifyRefuse') active @endif"><a href="/community/team/manager">成员管理</a></li>
                   <!-- <li class="@if ($key === 'manager') active @endif"><a href="/community/team/manager">成员管理</a></li> -->
                   <li class="@if ($key === 'notice') active @endif"><a href="/community/team/notice">社团通知</a></li>
                </ul>
            </div>
            <div class="menu">
                <div class="menu-head operating-activity">活动</div>
                <ul>
                    <li class="@if ($key === 'publish') active @endif"><a href="/community/activity/publish">发布活动</a></li>
                    <li class="@if ($key === 'activityList') active @endif"><a href="/community/activity/list">活动管理</a></li>
                    <li class="@if ($key === 'news') active @endif"><a href="/community/activity/news/publish">活动资讯</a></li>
                </ul>
            </div>

            <div class="menu">
                <div class="menu-head wallet">财务</div>
                <ul>
                    <li class="@if ($key === 'financeIndex') active @endif"><a href="/community/finance/index">交费详情</a></li>
                    <!-- <li class="@if ($key === 'stream') active @endif"><a href="/community/finance/stream">社团流水</a></li>
                    <li class="@if ($key === 'withdrawals') active @endif"><a href="/community/finance/withdrawals">申请提现</a></li> -->
                </ul>
            </div>
            <a href="" id="sub-open" style=""></a>
        </div>
    </div>

    <div class="foot-warp">
        <div class="foot-body">
            <!-- <a href="/org_v2/default/about">关于我们</a> <span class="foot-split">&nbsp;</span>
            <a href="/org_v2/default/contact">联系我们</a> <span class="foot-split">&nbsp;</span>
            <a href="/org_v2/default/partners">合作伙伴</a> -->
            <br/>
            <span class="copyright">
                <a href="http://www.jh008.com/">www.jh008.com</a> 2015 &copy; All Rights Reserved <a href="http://www.miitbeian.gov.cn/publish/query/indexFirst.action" target="_blank">蜀ICP备14010211号</a>
            </span>
        </div>
    </div>

    <script src="/static/plugins/jquery-1.7.1.min.js"></script>
    <script src="/static/plugins/layer/layer.js"></script>
    <script src="/static/plugins/jquery.validate.js"></script>
    <script src="/static/plugins/ktable/utilHelper.js"></script>
    <script src="/static/plugins/ktable/k-paginate.js"></script>
    <script src="/static/plugins/ktable/k-table.js"></script>
    <script src="/static/plugins/jquery-ui-1.11.2.custom/jquery-ui.min.js"></script>
    <script src="/static/js/common/K.js"></script>
    <script src="/static/js/common/lc.js"></script>
    <script src="/static/js/common/base.js"></script>
    <script src="/static/js/common/dialogUi.js"></script>
    <script src="/static/js/common/server.js"></script>
    <script src="/static/js/common/json2.js"></script>
    <script src="/static/js/common/logout.js"></script>
    @yield('javascript')
</body>
</html>