@extends('layouts.main')

@section('title', '申请提现')

@section('stylesheet')
    <link rel="stylesheet" href="/static/css/finance/finance.css"/>
@endsection

@section('content')
    <div class="section-bd">
        <div class="w800">
            <div class="ui-tab2">
                <ul class="ui-tab2-items">
                    <li class="ui-tab2-item ui-tab2-item-current">
                        <a href="/community/finance/withdrawals"><!--<i class="icon iconfont"></i>-->申请体现</a>
                    </li>
                    <li class="ui-tab2-item">
                        <a href="/community/finance/withdrawals/list"><!--<i class="icon iconfont"></i>-->提现列表</a>
                    </li>           
                </ul>
            </div>  

            <div class="info-w">
                <form>
                    <div class="balance-w">
                          <span>余额：<strong>￥4879.00</strong></span>
                    </div>
                    <div class="info-item info-item-1">
                        <label>提现金额：</label>
                        <input type="text" class="form-control"/>元
                    </div>
                    <div class="info-item info-item-2">
                        <label>姓&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;名：</label>
                        <input type="text" class="form-control"/>
                    </div>
                    <div class="info-item info-item-3">
                        <span><i>*</i>申请提现后，将在3个工作日内处理您的申请</span>
                    </div>

                    <input type="submit" class="ui-form-submit" value="申请提现" />
                </form>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script type="text/javascript" src="/static/js/finance/payment.js"></script>
@endsection
