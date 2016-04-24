@extends('layouts.wapMain')

@section('title')活动签到@endsection

@section('stylesheet')
    <link rel="stylesheet" href="/static/wap/css/components.css"/>
    <link rel="stylesheet" href="/static/wap/css/pageSign.css?2015091211336"/>
@endsection

@section('content')
<div class="section-bd">
    <div id="p1" class="content-page page activity-mobile-w page-active">
        <div class="inner-page t-b"><img src="/static/wap/images/juchi1.png" class="res-img" alt=""></div>
        <div class="inner-page shadow">
            <section class="section-authentication">
                <h3 class="hint-title">活动签到</h3>
                <p class="ac-title">
                    {{$activity['title']}}
                </p>
                <div class="section-captcha-validate">
                <!-- <input type="images" src="/captcha" class="btn--hollow-green get-captcha" id="get-captcha"/> -->
                    <div class="phone-w">
                        <input type="tel" class="phone-input" id="mobile_num" placeholder="输入手机号"/>
                    </div>
                    <div class="error-mes"></div>
                    <div class="c-w">
                        <input type="tel" maxlength="4" class="captcha-input" id="captcha" placeholder="输入验证码">
                        <img src="/captcha" alt="验证码加载中..." id="img-captcha" class="img-captcha">
                        <div class="captcha-loading"><img src="/static/wap/images/loading-2.gif"></div>
                    </div>
                </div>
                <div class="submit-w">
                    <input type="button" class="btn--green validate" id="submit" data-page="p5" value="提交"/>
                </div>
            </section> 
        </div>
        <div class="inner-page b-b"><img src="/static/wap/images/juchi.png" class="res-img" alt=""></div>
        @include('backstage.wap.downloadBanner')
    </div>
</div>
<input type="hidden" id="aid" value="{{$activity['id']}}">
    {!! csrf_field() !!}
@endsection

@section('javascript')
    <script src="/static/wap/js/pageSign.js?2015091211318"></script>
    
@endsection
