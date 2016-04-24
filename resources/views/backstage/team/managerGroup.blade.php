@extends('layouts.main')

@section('title', '成员管理')

@section('stylesheet')
    <link rel="stylesheet" href="/static/css/team/assn.css"/>
@endsection

@section('content')
    <div class="manage-con">
        <div class="ui-tab2">
            <ul class="ui-tab2-items">
                <li class="ui-tab2-item">
                    <a href="/community/team/manager">成员管理</a>
                </li>
                <li class="ui-tab2-item ui-tab2-item-current">
                    <a href="/community/team/manager/group">组管理</a>
                </li>            
            </ul>
        </div>
        <a href="javascript:;" class="button button-blue mt20" id="createGroup">建组</a>
        <div class="mt20">
            <div id="GroupList">
                <div id="tableCon">
                    <table>
                        <thead>
                            <tr>
                                <th>编号</th>
                                <th>名称</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
        {!! csrf_field() !!}
    </div>
@endsection

@section('javascript')
    <script src="/static/js/team/manageGroup.js"></script>
@endsection