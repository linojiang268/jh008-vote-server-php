@extends('admin.layout.main')
    <link rel="stylesheet" href="/static/plugins/webuploader/webuploader.css"/>
    <link rel="stylesheet" href="/static/admin/css/page.css">
@section('title', '财务列表')

@section('stylesheet')

@endsection


@section('content')
    <div class="filter-con">
        <dl class="filter-bar">
            <dt>转账状态:</dt>
            <dd class="filter-select"><a href="javascript:;" data-v="0">所有</a></dd>
            <dd class=""><a href="javascript:;" data-v="1">待转账</a></dd>
            <dd class=""><a href="javascript:;" data-v="2">转账中</a></dd>
            <dd class=""><a href="javascript:;" data-v="3">已转账</a></dd>
        </dl>
        <div class="search-w mt20">
            <div class="date-w">
                <input class="form-control" id="date-start" type="text" placeholder="请选择日期">
                <span>至</span>
                <input class="form-control" id="date-end" type="text" placeholder="请选择日期">
            </div>
            <a href="javascript:;" id="activitySearch-btn" class="button button-orange button-pre btn-search"><i class="icon iconfont"></i>搜&nbsp;索</a>
        </div>
    </div>  
    <div id="membersList" class="mt20">
        <div id="listCon">
            <div id="tableCon">
                <table>
                    <thead>
                        <tr>
                            <th>序号</th>
                            <th>活动名称</th>
                            <th>开始时间</th>
                            <th>结束时间</th>
                            <th>社团名</th>
                            <th>状态</th>
                            <th>总金额/已打款金额(元)</th>
                            <th>查看详情</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="popMask">
        <div class="section-finance-pop" id='f-pop'>
            <div class="finance-item">
                <label for="f-money">金额：</label>
                <input type="number" id="f-money" placeholder="请输入金额(元)">
            </div>
            <div class="finance-item">
                <label for="f-type">类型：</label>
                <select name="f-type" id="f-type">
                    <option value="1">报名费：</option>
                </select>
            </div>
            <div class="finance-item">
                <label for="f-voucher">凭证：</label>
                <div class="f-evidence" id="f-evidence"></div>
            </div>
        </div>
    </div>
    {!! csrf_field() !!}
@endsection


@section('javascript')
<script src="/static/plugins/webuploader/webuploader.js"></script>
    <script src="/static/js/common/imageUpload.js"></script>
    <script src="/static/plugins/laydate/laydate.js"></script>
    <script src="/static/admin/js/accountant/list.js"></script>
@endsection
