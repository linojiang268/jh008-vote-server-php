@extends('layouts.wapMain')

@section('meta')
    <meta name="description" id="description" content="{{ isset($team) ? $team->getIntroduction() : "" }}">
@endsection

@section('title'){{ isset($team) ? $team->getName() : "社团" }}@endsection

@section('stylesheet')
    <link rel="stylesheet" href="/static/wap/css/team.css?201510191407">
@endsection

@section('content')
    <div class="content-page team-page">
        <div class="inner-page  t-b"><img src="/static/wap/images/juchi1.png" class="res-img" alt=""></div> 
        <div class="inner-page shadow">
            <div class="container">
                @if (!$errors->isEmpty())
                <section class="team-infor clearfix" style="height:310px;">
                     <div class="alert alert-error" style="font-weight:bolder; font-size: 20px; text-align:center; padding-top: 50px;">{{ $errors->first() }}</div>
                </section>
                @else
                <section class="team-infor clearfix">
                    <div class="infor-logo">
                        <div class="infor-logo-wrap">
                            <div class="logo-frame">
                                <img class="logo res-img" src="{{ $team->getLogoUrl() }}" alt="">
                            </div>
                        </div>
                    </div>
                    <div class="name-con">
                        <span class="title">{{ $team->getName() }}</span>
                        <span class="time">入驻时间：{{ substr($team->getCreatedAt(), 0, 10) }}</span>
                    </div>
                    <a class="phone" href="tel:{{ $team->getContactPhone() }}"><img class="res-img" src="/static/wap/images/phone-icon.png" alt=""></a>
                </section>
                <section class="team-numbers">
                    <ul class="clearfix">
                        <li>
                            <a class href="javascript:;">
                                <img class="res-img"  src="/static/wap/images/user@2x.png" alt="">
                                <span class="num">{{ $member_num }}人</span>
                            </a>
                        </li>
                        <li>
                            <a href="javascript:;">
                                <img class="res-img" src="/static/wap/images/hd@2x.png" alt="">
                                <span class="num">{{ $activity_num }}个活动</span>
                            </a>
                        </li>
                        <li>
                            <a href="javascript:;">
                                <img class="res-img" src="/static/wap/images/cal@2x.png" alt="">
                                <span class="num">{{ $album_image_num }}张</span>
                            </a>
                        </li>
                    </ul>
                </section>
                <section class="team-main">
                    <!--  <img class="res-img" src="/static/wap/images/content@2x.png" alt=""> -->
                </section>
                <section class="team-activity">
                    <ul class="t-actList">
                        @if ($activities)
                            @foreach ($activities as $index=>$activity)
                                <li class="t-actList-item">
                                    <a class="t-actList-link" href="/wap/activity/detail?activity_id={{ $activity->getId() }}">
                                        @if ($index==0)
                                            <p class="t-actList-tip">近期活动</p>
                                        @endif
                                        <div class="t-actList-content clearfix">
                                            <span class="name">{{ $activity->getTitle() }}{{ $index }}</span>
                                        </div>
                                        <div class="t-actList-time">
                                            <span>{{ substr($activity->getBeginTime(), 0, 10) }}</span>
                                            <div class="detail" href="/wap/activity/detail?activity_id=">
                                                <!-- <span class="detail-text">详情</span> -->
                                        <span class="detail-icon">
                                            <img class="res-img" src="/static/wap/images/Forward-100@2x.png" alt="{{ $activity->getId() }}" />
                                        </span>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        @endif
                    </ul>
                </section>
                @endif
                </div>
        </div>        
         @include('backstage.wap.downloadBanner')
    </div>
   
@endsection

@section('footer')
    @if (!empty($team))
    <footer class="team-footer">
        <a href="javascript:;" class="btn-green join-team-btn" id="joinTeamBtn" data-text="立即加入社团">
            立即加入社团
        </a>
    </footer>
    @endif
@endsection

@section('javascript')
    @if ($errors->isEmpty())
    <script src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
    <script type="text/javascript">
        var teamId = '{{ $team->getId() }}';
        $('#joinTeamBtn').click(function(e) {
            var loading = K.loadingButton($(this)).load();
            K.testApp('joinTeam', function() {
                loading.unload();
            });
            e.preventDefault();
            e.stopPropagation();
        });

        var appId = '{{ $sign_package["appId"] }}',
            timestamp = '{{ $sign_package["timestamp"] }}',
            nonceStr = '{{ $sign_package["nonceStr"] }}',
            signature = '{{ $sign_package["signature"] }}';

        var actTitle = '{{ $team->getName() }}',
            actLink  = location.href,
            actDesc  = '{{ $team->getIntroduction() }}',
            actImg   = '{{ $team->getLogoUrl() }}';

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
                type: '', // 分享类型,music、video或link，不填默认为link
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
    @endif
@endsection