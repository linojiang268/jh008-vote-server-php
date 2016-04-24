@extends('layouts.main')

@section('title', '支付宝绑定')

@section('stylesheet')
    <link rel="stylesheet" href="/static/css/team/assn.css"/>
@endsection

@section('content')
    @include('layouts.settingNav')
    
    <div class="p15">
        <p class="tip1">支付宝绑定</p>
        <div class="mt20">
            <input type="email" class="form-control w300" placeholder="请输入支付宝账号">
            <a href="javascript:;" class="button button-orange ml10">绑定</a>
        </div>
        <div class="mt50">
            <p class="tip2">支付宝账号 694413162 已经被绑定 <a href="#" class="ml10">解除绑定</a></p>
            <div class="mt20">
                <input type="email" class="form-control w300" placeholder="请输入手机号码">
                <a href="javascript:;" class="button button-orange ml10">获取验证码</a>
            </div>
            <div class="mt20">
                <input type="email" class="form-control w300" placeholder="请输入手机动态验证码">
            </div>
            <div class="mt20">
                <a href="javascript:;" class="button button-orange">确认解除绑定</a>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script src="/static/js/team/bind.js"></script>
@endsection