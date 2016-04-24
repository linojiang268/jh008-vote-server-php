@extends('layouts.wapMain')

@section('meta')
    <meta name="description" content="缴费">
@endsection

@section('title')活动缴费@endsection

@section('stylesheet')
    <link rel="stylesheet" href="/static/wap/css/components.css"/>
    <link rel="stylesheet" href="/static/wap/css/page.css"/>
    <link rel="stylesheet" href="/static/wap/css/animation.css"/>
@endsection

@section('content')

@if ( !$activity['auditing'] && $activity['enroll_fee_type'] == 3 )
<div class="section-pay">
    <div class="ac-pay-w">
        <div class="howmuch-w">
            <div class="howmuch">金额<span><i>{{$activity['enroll_fee']}}</i>元</span></div>
        </div>
        <div class="pay-way-w">
            <a href="javascript:;" class="pay-weixin">微信支付<i></i></a>
            <!-- <a href="" class="pay-zfb">支付宝支付<i></i></a> -->
        </div>
        <div class="pay-tip">
            <div>报名手机号： {{ $mobile }}</div>
            请在<span>30分钟</span>内完成支付
        </div>
    </div>
</div>  
@elseif ( $activity['auditing'] && $activity['enroll_fee_type'] == 3 )    
<div class="section-pay">
    <div class="ac-pay-w">
        <div class="howmuch-w">
            <div class="howmuch">金额<span><i>{{$activity['enroll_fee']}}</i>元</span></div>
        </div>
        <div class="pay-way-w">
            <a href="javascript:;" class="pay-weixin">微信支付<i></i></a>
            <!-- <a href="" class="pay-zfb">支付宝支付<i></i></a> -->
        </div>
        <div class="pay-tip">
            <div>报名手机号： {{ $mobile }}</div>
            <div class="tips">在活动开始前完成支付</div>
            <div class="end-date"> {{ substr($activity['enroll_end_time'] , 0 , 16) }}前</div>
        </div>
    </div>
</div>  
@endif

    <input type="hidden" id='openid' value="{{$openid}}">
    <input type="hidden" id='aid' value="{{$activity['id']}}">
    <input type="hidden" id='order_no' value="{{$order_no}}">
    <input type="hidden" id='mobile' value="{{$mobile}}">
    @if($wx_jssdk_config)
    <input type="hidden" id='wx_jssdk_appid' value="{{ $wx_jssdk_config['appId'] }}">
    <input type="hidden" id='wx_jssdk_timestamp' value="{{ $wx_jssdk_config['timestamp'] }}">
    <input type="hidden" id='wx_jssdk_nonceStr' value="{{ $wx_jssdk_config['nonceStr'] }}">
    <input type="hidden" id='wx_jssdk_signature' value="{{ $wx_jssdk_config['signature'] }}">
    @endif
    {!! csrf_field() !!}
@endsection

@section('javascript')
 <script src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
    <script src="/static/wap/js/activityPay.js?201509181802"></script>
@endsection
