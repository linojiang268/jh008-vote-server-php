@extends('layouts.main')

@section('title', '社团通知-通知记录')

@section('stylesheet')
    <link rel="stylesheet" href="/static/css/team/assn.css"/>
@endsection

@section('content')
    <div class="notice-con">
        <div class="notice-ways-con">

            <div class="ui-tab2">
                <ul class="ui-tab2-items">
                    <li class="ui-tab2-item">
                        <a href="/community/team/notice">发送通知</a>
                    </li>
                    <li class="ui-tab2-item ui-tab2-item-current">
                        <a href="/community/team/notice/list">通知记录</a>
                    </li>            
                </ul>
            </div>

            <div class="p15">
                <div id="tabs">
                    <!-- <div class="ui-tab">
                        <ul class="ui-tab-items">
                            <li class="ui-tab-item" role="all">
                                <a href="javascript:;">所有</a>
                            </li>
                            <li class="ui-tab-item" role="system">
                                <a href="javascript:;">app系统消息</a>
                            </li>
                            <li class="ui-tab-item" role="message">
                                <a href="javascript:;">短信</a>
                            </li>
                        </ul>
                    </div>  -->
                    <div class="mt20">
                        <div id="tableCon">
                            <table>
                                <thead>
                                    <tr>
                                        <th>序号</th>
                                        <th>发送时间</th>
                                        <th>发送内容</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>                        
                    </div>      
                </div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script src="/static/js/team/noticeRecord.js" ></script>  
@endsection