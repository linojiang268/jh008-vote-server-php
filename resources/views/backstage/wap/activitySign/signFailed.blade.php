@extends('layouts.wapMain')

@section('stylesheet')
    <link rel="stylesheet" href="/static/wap/css/components.css"/>
    <link rel="stylesheet" href="/static/wap/css/page.css"/>
    <link rel="stylesheet" href="/static/wap/css/animation.css"/>
@endsection

@section('content')
<div class="section-bd">
    <div id="p1" class="content-page page activity-mobile-w page-active">
        <div class="inner-page t-b"><img src="/static/wap/images/juchi1.png" class="res-img" alt=""></div>
        <div class="inner-page shadow">
            <section class="section-downApp-for-baoming section-succeed">
                <h3 class="hint-title">
                    <!-- <span>对不起</span> -->
                    <span>恭喜你</span>
                    <!-- <span>您没有报名无法签到，请与主办方联系</span> -->
                    <span>签到成功</span>
                <!-- </h3> -->
                <p class="hint-info status-success"></p>
                <div class="section-app-des">
                    <p>
                        <span>您可以下载集合APP:</span>
                        <span>发现活动，加入社团，认识更多有共同爱好的人</span>
                    </p>
                </div>
            </section>
        </div>
        <div class="inner-page b-b"><img src="/static/wap/images/juchi.png" class="res-img" alt=""></div>
    </div>
</div>
@endsection

@section('javascript')
    <!--<script src="/static/wap/js/page.js"></script>-->
    <!--<script src="http://api.map.baidu.com/api?type=quick&ak=hqkEDHjAXn4VaTzt3a7RRZGP&v=1.0"></script>
    <script src="/static/wap/js/activityPage.js"></script>
    <script src="/static/wap/js/page.js"></script> -->
@endsection
