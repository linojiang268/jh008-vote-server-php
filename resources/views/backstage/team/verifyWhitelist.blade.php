@extends('layouts.main')

@section('title', '成员审核 - 黑名单')
    <link rel="stylesheet" href="/static/plugins/webuploader/webuploader.css"/>
    <link rel="stylesheet" href="/static/css/team/assn.css"/>
@section('stylesheet')

@endsection

@section('content')
    <div class="examine-con">
        <div class="p15">
            <div class="ui-tab2">
                <ul class="ui-tab2-items">
                    <li class="ui-tab2-item">
                        <a href="/community/team/verify/pend">待审核</a>
                    </li>
                    <li class="ui-tab2-item">
                        <a href="/community/team/verify/refuse">已拒绝</a>
                    </li>
                    <li class="ui-tab2-item">
                        <a href="/community/team/verify/blacklist">黑名单</a>
                    </li>
                    <li class="ui-tab2-item ui-tab2-item-current">
                        <a href="/community/team/verify/whitelist">白名单</a>
                    </li>              
                </ul>
            </div>
           <div id="membersList" class="mt20">
                @if ($status == 0)
                    <p class="tip">你的社团还未认证，暂时不能使用白名单功能。 <a class="link-tip" href="/community/team/setting/authentication">去认证</a></p>
                @elseif ($status == 1)
                    <p class="tip">你的社团正在认证中，认证成功后才能使用白名单功能。 <a class="link-tip" href="/community/team/setting/authentication">认证详情</a></p>
                @elseif ($status == 2)
                    <div class="mt20 mb15">
                        <div id="uploaderExcel" class="uploader-excel">
                            <a id="filePicker">导入白名单模板</a>
                        </div>
                        <a href="/static/wb.xls" class="button button-m button-b-blue ml15">下载导入模板</a>
                        <a href="javascript:;" id="add" class="button button-m button-b-orange ml15">手动添加白名单</a>
                    </div>
                    <div id="tableCon">
                        <table>
                            <thead>
                                <tr>
                                    <th>序号</th>
                                    <th>手机号</th>
                                    <th>群名称</th>
                                    <th>备注信息</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
    {!! csrf_field() !!}
@endsection

@section('javascript')
    <script src="/static/plugins/webuploader/webuploader.js" ></script>
    <script src="/static/js/team/verifyWhitelist.js" ></script>
@endsection