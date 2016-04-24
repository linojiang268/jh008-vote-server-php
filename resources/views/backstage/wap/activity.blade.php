@extends('layouts.wapMain')

@section('meta')
    <meta name="description" content="{{ isset($activity['title']) ? $activity['title'] : '活动不存在'}}">
@endsection

@section('title'){{ isset($activity['title']) ? $activity['title'] : '活动不存在'}}@endsection

@section('stylesheet')
    <link rel="stylesheet" href="/static/wap/css/components.css?201510121344"/>
    <link rel="stylesheet" href="/static/wap/css/page.css?201510141250"/>
    @if ($errors->isEmpty())
    <link rel="stylesheet" href="/static/wap/css/animation.css"/>
    <script src="http://api.map.baidu.com/api?type=quick&ak=hqkEDHjAXn4VaTzt3a7RRZGP&v=1.0"></script>
    @endif
@endsection

@section('content')
<div class="section-bd">
    <div id="p1" class="content-page page page1 activity-mobile-w page-active">
        <div class="inner-page t-b"><img src="/static/wap/images/juchi1.png" class="res-img" alt=""></div>
        <div class="inner-page shadow">
            @if (!$errors->isEmpty())
                <div class="">
                    <span style="margin: 0 auto; width: 10em; display: block; padding-top: 5em;">
                        <img class="res-img" src="/static/wap/images/error_icon.png" alt="">
                    </span>
                    <span style="text-align: center; margin: 0 auto; width: 10em; display: block; padding: 2em 0;">{{ $errors->first() }}</span>
                </div>
            @else
            <section class="section-activity">
                <!-- activity banner and team-info-->
                <div class="section-banner w100p">
                    <div class="ac-title">
                        <h2 class="ac-t">{{$activity['title']}}</h2>
                        <p>
                            <a href="/wap/team/detail?team_id={{$activity['team_id']}}">
                                <span class="team-name">
                                    <i>主办方:</i>
                                    {{$activity['team']['name']}}
                                </span>
                            </a>
                            <!-- 这里有一个电话的图标 -->
                        </p>
                        @if ( $activity['telephone'] )
                            <a href="tel:{{$activity['telephone']}}" class="phone-icon"></a>
                        @endif
                    </div>
                    
                </div>
                <!-- activity detail -->
                <div class="section-ac-detail w100p">
                    <img src="{{$activity['cover_url']}}" alt="" class="ac-cover">
                    <div class="ac-date">
                        <h2 class="h-title">
                            活动时间: <span class="ac-date-time">{{$activityTimeZone}}</span>
                        </h2>
                    </div>
                    <h2 class="h-title">
                        活动详情:
                        <span id='ac-more' class="expand btn--o">展开&or;</span> | <a href="javascript:;" id="baoming-query" data-page="p5">报名查询</a>
                    </h2>
                    <!-- <p class="ac-time">时间:<span>{{$activity['begin_time']}} - {{$activity['end_time']}}</span></p>
                    <p class="ac-location">地点: <span>{{$activity['address']}}</span></p> -->
                    <div class="ac-des ac-des--hidden">
                        <div class="des-content">
                            {!!$activity['detail']!!}
                        </div>
                    </div>
                </div>
                <!-- activity address -->
                <div class="section-ac-address w100p">
                    <h2 class="h-title">活动地址:</h2>
                    <p class="ac-address">
                        {{$activity['address']}}
                    </p>
                </div>
                <!-- activity member have sign up -->
                <div class="section-sign-Members hide">
                    <h2 class="h-title-arrow">已报名<i class="arrow-right"></i></h2>
                    <div class="members-w">这里显示地图</div>
                </div>
                <!-- activity route -->
                <div class="section-ac-route">
                    <button id="location-btn" class="location-btn"></button>
                    <div class="ac-map" id="map"></div>
                </div>
            </section>
            @endif
        </div>
        <div class="inner-page b-b"><img src="/static/wap/images/juchi.png" class="res-img" alt=""></div>
    </div>
    @if ($errors->isEmpty())
    <div id="p2" class="content-page page activity-mobile-w page-next">
        <div class="inner-page  t-b"><img src="/static/wap/images/juchi1.png" class="res-img" alt=""></div>
        <div class="inner-page shadow">
            <!-- input baoming info -->
            <section class="section-baoming-regist">
                <div class="query-no-w hide">
                    <p>对不起，你还没有报名本活动或报名信息已失效</p>
                    <p>立即报名</p>
                </div>
                <h3 class="hint-title">填写报名信息</h3>
                <div class="section-form">
                        @foreach($activity['enroll_attrs'] as $a)
                            <input class='attr-item' type='text' name='{{$a}}' placeholder='{{$a}}'/>
                        @endforeach
                </div>
                <div class="submit-w">
                    <input class="btn--green" id="next" data-page="p3" type="button" value="下一步"/>
                </div>
            </section> 
        </div>
        <div class="inner-page b-b"><img src="/static/wap/images/juchi.png" class="res-img" alt=""></div>
    </div>
    <div id="p5" class="content-page page activity-mobile-w page-next">
        <div class="inner-page  t-b"><img src="/static/wap/images/juchi1.png" class="res-img" alt=""></div>
        <div class="inner-page shadow">
            <!-- input baoming info -->
            <section class="section-baoming-regist section-baoming-query">
                <h3 class="hint-title">报名信息查询</h3>
                <div class="section-form">
                    <input type="tel" class="tel-input" id="query-num" name='phoneNum' placeholder='请输入手机号'/>
                </div>
                <div class="submit-w">
                    <input class="btn--green" id="query-next" type="button" value="下一步"/>
                </div>
            </section> 
        </div>
        <div class="inner-page b-b"><img src="/static/wap/images/juchi.png" class="res-img" alt=""></div>
    </div>
    <div id="p3" class="content-page page activity-mobile-w page-next">
        <div class="inner-page  t-b"><img src="/static/wap/images/juchi1.png" class="res-img" alt=""></div>
        <div class="inner-page shadow">
            <!-- get phone captcha and submit info in this section -->
            <section class="section-get-captcha">
                <h3 class="hint-title">验证手机号</h3>
                <div class="section-captcha-validate">
                    <p>手机号码：<span class="phone-num" id="phone-num"></span></p>
                    <input type="tel" maxlength="4" class="tel-input captcha-input" id="captcha" placeholder="输入验证码"/><input type="button" class="btn--hollow-green get-captcha" id="get-captcha" value="获取验证码"/>
                    <div class="error-mes"></div>
                </div>
                <div class="submit-w">
                    <input type="button" class="btn--green validate" id="submit" data-page="p4" value="验证"/>
                </div>
            </section>
        </div>
        <div class="inner-page b-b"><img src="/static/wap/images/juchi.png" class="res-img" alt=""></div>
    </div>
    <div id="p4" class="content-page page activity-mobile-w page-next">
        <div class="inner-page t-b"><img src="/static/wap/images/juchi1.png" class="res-img" alt=""></div>
        <div class="inner-page shadow">
            <!-- baoming success -->
            <section class="section-succeed">
            @if (!$activity['auditing'] && $activity['enroll_fee_type'] !=3)
                <!-- 报名成功 -->
                <h3 class="hint-title"><span>恭喜你</span><span>成功报名本次活动</span></h3>
                <p class="hint-info status-success"></p>
                <div class="section-app-des">
                    <p>    
                        <span>请下载集合APP:</span>
                        <span>进入活动日历，查看活动手册及签到</span>
                    </p>
                </div>
            @elseif ($activity['auditing'])
                <!-- 待审核 -->
                <h3 class="hint-title"><span>待审核</span><span>主办方将通过电话或短信通知审核结果</span></h3>
                <p class="hint-info status-wait"></p>
                <div class="section-app-des">
                    <p>    
                        <span>请下载集合APP:</span>
                        <span>进入活动日历，实时查看审核结果，通过后可以浏览活动手册</span>
                    </p>
                </div>
            @endif
            </section>
            <!-- 无权限参加 -->
            <section class="section-downApp-for-baoming section-refuse hide">
                <h3 class="hint-title">
                    <span>对不起</span>
                    <span>本活动仅限社团成员参加</span>
                </h3>
                <p class="hint-info status-refuse"></p>
                <div class="section-app-des">
                    <p>
                        <span>您可以下载集合APP:</span>
                        <span>加入该社团，获得报名资格，或选择其他活动报名参加</span>
                    </p>
                </div>
            </section>
        </div>
        <div class="inner-page b-b"><img src="/static/wap/images/juchi.png" class="res-img" alt=""></div>
        @include('backstage.wap.downloadBanner')
    </div>
    @endif
</div>

    <input type="hidden" id="aid" value="{{$activity['id']}}">
    @if ($errors->isEmpty())
    <input type="hidden" id="sub_status" value="{{$activity['sub_status']}}">
    <input type="hidden" id="location" value="{{$activity['location'][0]}},{{$activity['location'][1]}}">
    <input type="hidden" id="auditing" value="{{$activity['auditing']}}">
    <input type="hidden" id="ft" value="{{$activity['enroll_fee_type']}}">
    <input type="hidden" id='city' value="{{$activity['city']['name']}}">
    {!! csrf_field() !!}
    @endif
@endsection

@section('footer')
    @if ($errors->isEmpty())
    <footer class="section-footer">
        <!-- activity sign up submit -->
        <div class="section-baoming w100p">
            <span class="baoming-price">
                @if ( $activity['enroll_fee_type'] == 3 )
                    ￥<i>{{$activity['enroll_fee']}}</i>
                @elseif ( $activity['enroll_fee_type'] == 2 )
                AA制
                @else
                免费
                @endif
            </span>
            <button class="baoming btn--green-s" id="baoming-Btn" data-page="p2">立即报名</button>
        </div>
    </footer>
    @endif
@endsection

@section('javascript')
    <script src="/static/wap/js/activityPage.js"></script>
    <script src="/static/wap/js/page.js?2015101317012"></script>
    @if ($errors->isEmpty())
    <script src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
    <script>
        var appId = '{{ $sign_package["appId"] }}',
            timestamp = '{{ $sign_package["timestamp"] }}',
            nonceStr = '{{ $sign_package["nonceStr"] }}',
            signature = '{{ $sign_package["signature"] }}';

        var actTitle = '{{ $activity['team']['name'] }}',
            actLink  = location.href,
            actDesc  = '{{$shareActDesc}}';
            actImg   = '{{$activity["cover_url"]}}';

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
