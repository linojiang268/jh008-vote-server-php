@extends('layouts.main')

@section('title', '社团流水')

@section('stylesheet')
    <link rel="stylesheet" href="/static/css/finance/finance.css"/>
@endsection

@section('content')
    <div class="section-bd">
        <div class="search-w">
            <div class="date-w">
                <input class="form-control" id="date-start" type="text" placeholder="请选择日期" />
                <span>至</span>
                <input class="form-control" id="date-end" type="text" placeholder="请选择日期" />
            </div>
            <a href="javascript:;" class='button button-orange button-pre btn-search'><i class="icon iconfont"></i>搜&nbsp;索</a>
            <div class=" balance-w balance-w2 ">
                <span>余额：<strong>￥4879.00</strong></span>
            </div>
        </div>

        <div class="table-w inventory-table">
        </div>
    </div>
@endsection

@section('javascript')
    <script src="/static/plugins/laydate/laydate.js"></script>
    <script src="/static/plugins/laypage/laypage.js"></script>
    <script src="/static/js/finance/stream.js"></script>
@endsection