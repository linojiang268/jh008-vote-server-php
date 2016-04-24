@extends('layouts.main')

@section('title', '提现列表')

@section('stylesheet')
    <link rel="stylesheet" href="/static/css/finance/finance.css"/>
@endsection

@section('content')
    <div class="section-bd">
        <div class="w800">
            <div class="ui-tab2">
                <ul class="ui-tab2-items">
                    <li class="ui-tab2-item">
                        <a href="/community/finance/withdrawals">申请体现</a>
                    </li>
                    <li class="ui-tab2-item ui-tab2-item-current">
                        <a href="/community/finance/withdrawals/list">提现列表</a>
                    </li>           
                </ul>
            </div>

            <div class="table-w transfer-table">
                <div class="total-w">
                    <span>总计：<strong>￥4879.00</strong></span>
                </div>
                <div class="transfer_list-w"></div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script type="text/javascript" src="/static/js/finance/transferList.js"></script>
@endsection
