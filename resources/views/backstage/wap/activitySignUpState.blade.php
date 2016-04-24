@extends('layouts.wapMain')

@section('meta')
    <meta name="description" content="{{$activity['title']}}">
@endsection

@section('title'){{$activity['team']['name']}}@endsection

@section('stylesheet')
    <link rel="stylesheet" href="/static/wap/css/components.css"/>
    <link rel="stylesheet" href="/static/wap/css/page.css?201509221535"/>
@endsection

@section('content')

<div class="section-bd">
    <div id="p4" class="content-page page activity-mobile-w page-active">
        <div class="inner-page t-b"><img src="/static/wap/images/juchi1.png" class="res-img" alt=""></div>
        <div class="inner-page shadow">
            <!-- baoming success -->
            <section class="section-succeed">
            @if ( $status == 3 && !$key )
                <!-- 报名成功 -->
                <h3 class="hint-title"><span>恭喜你</span><span>成功报名本次活动</span></h3>
                <p class="hint-info status-success"></p>
                <div class="section-app-des">
                    <p>    
                        <span>请下载集合APP:</span>
                        <span>进入活动日历，查看活动手册及签到</span>
                    </p>
                </div>
            @elseif ( $status == 3 && $key == 'next' )
                <!-- 下一步 报名成功 -->
                <h3 class="hint-title"><span id='userPhoneNum'>{{ $mobile }}</span><span>已成功报名本次活动</span></h3>
                <p class="hint-info status-warning"><span>请勿重复报名</span></p>
                <div class="section-app-des">
                    <p>    
                        <span>请下载集合APP:</span>
                        <span>进入活动日历，查看活动手册及签到</span>
                    </p>
                </div>  
            @elseif ( $status == 3 && $key == 'query' )
                <!-- 下一步 报名成功 -->
                <h3 class="hint-title"><span id='userPhoneNum'>{{ $mobile }}</span><span>已成功报名本次活动</span></h3>
               <p class="hint-info status-success"></p>
                <div class="section-app-des">
                    <p>    
                        <span>请下载集合APP:</span>
                        <span>进入活动日历，查看活动手册及签到</span>
                    </p>
                </div>  
            @elseif ( $status == 1 && ( !$key || $key !='next') )
                <!-- 待审核 -->
                <h3 class="hint-title"><span>待审核</span><span>主办方将通过电话或短信通知审核结果</span></h3>
                <p class="hint-info status-wait"></p>
                <div class="section-app-des">
                    <p>    
                        <span>请下载集合APP:</span>
                        <span>进入活动日历，实时查看审核结果，通过后可以浏览活动手册</span>
                    </p>
                </div>
            @elseif ( $status == 1 && $key == 'next' )
                <!-- 下一步 待审核 -->
                <h3 class="hint-title"><span id='userPhoneNum'>{{ $mobile }}</span><span>已提交报名信息，请耐心等待审核</span></h3>
                <p class="hint-info status-warning"><span>请勿重复报名</span></p>
                <div class="section-app-des">
                    <p>    
                        <span>请下载集合APP:</span>
                        <span>进入活动日历，实时查看审核结果，通过后可以浏览活动手册</span>
                    </p>
                </div>
             @elseif ( $status == -1 )
                <!-- 被拒绝 -->
             <h3 class="hint-title"><span>很遗憾</span><span>您没有被活动选中</span></h3>
                <p class="hint-info status-refuse"><span></span></p>
                <div class="section-app-des">
                    <p>    
                        <span>您可以下载集合APP:</span>
                        <span>查看更多您感兴趣的活动</span>
                    </p>
                </div>
            @endif
            </section>
          
        </div>
        <div class="inner-page b-b"><img src="/static/wap/images/juchi.png" class="res-img" alt=""></div>
        @include('backstage.wap.downloadBanner')
    </div>
</div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   
        <input type="hidden" id="aid" value="{{$activity['id']}}">
        <input type="hidden" id="auditing" value="{{$activity['auditing']}}">
        <input type="hidden" id="ft" value="{{$activity['enroll_fee_type']}}">
        <input type="hidden" id="mobile" value="{{$mobile}}">
        <input type="hidden" id="userStatus" value="{{$status}}">

    <input type="hidden" id='city' value="{{$activity['city']['name']}}">
    {!! csrf_field() !!}
@endsection

@section('javascript')
    <script type="text/javascript" src="/static/wap/js/pageUserStatus.js?201509172385"></script>
@endsection
