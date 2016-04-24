@extends('layouts.main')

@section('title', '成员审核 - 黑名单')
    <link rel="stylesheet" href="/static/css/team/assn.css"/>
@section('stylesheet')

@endsection

@section('content')
    <div class="examine-con">
        <div class="ui-tab2">
            <ul class="ui-tab2-items">
                <li class="ui-tab2-item">
                    <a href="/community/team/verify/pend">待审核</a>
                </li>
                <li class="ui-tab2-item">
                    <a href="/community/team/verify/refuse">已拒绝</a>
                </li>
                <li class="ui-tab2-item ui-tab2-item-current">
                    <a href="/community/team/verify/blacklist">黑名单</a>
                </li>
                <li class="ui-tab2-item">
                    <a href="/community/team/verify/whitelist">白名单</a>
                </li>             
            </ul>
        </div>
        <div id="membersList" class="mt20">
            <div id="tableCon">
                <table>
                    <thead>
                        <tr>
                            <th>序号</th>
                            <th>手机号</th>
                            <th>昵称</th>
                            <th>自定义信息</th>
                            <th>备注信息</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    {!! csrf_field() !!}
@endsection

@section('javascript')
    <script src="/static/js/team/verifyBlacklist.js" ></script>
@endsection