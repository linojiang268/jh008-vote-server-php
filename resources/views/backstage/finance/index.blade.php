@extends('layouts.main')

@section('title', '交费详情')

@section('stylesheet')
    <link rel="stylesheet" href="/static/css/finance/finance.css"/>
@endsection

@section('content')
    <div class="section-bd">
        <div class="payment-tips">
            <p class="payment-tips-title">结算说明</p>
            <p class="payment-t-i">1、报名结束后的3个工作日内，我们会通过邮件和短信提醒您核对收入，并通过支付宝完成转账，无手续费。</p>
            <p class="payment-t-i">2、单个活动总收入不超过5万的，一笔完成转账；超过5万的，依次分笔处理。</p>
            <p class="payment-t-i">3、目前暂不支持用户在线退款，请您收到总收入后自行处理。</p>
            <p class="payment-t-i">4、若有疑问或其它要求请致电400-876-1176。</p>
        </div>
        <div class="table-w payment-table">
        </div>
    </div>
@endsection

@section('javascript')
    <script type="text/javascript" src="/static/js/finance/index.js"></script>
@endsection
