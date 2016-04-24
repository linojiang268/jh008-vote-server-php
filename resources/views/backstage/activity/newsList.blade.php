@extends('layouts.main')

@section('title', '活动资讯')

@section('stylesheet')
    <link rel="stylesheet" href="/static/css/activity/activity.css"/>
@endsection

@section('content')
    <div class="m_1">
        <div class="ui-tab2">
            <ul class="ui-tab2-items">
                <li class="ui-tab2-item">
                    <a href="/community/activity/news/publish">资讯发布</a>
                </li>
                <li class="ui-tab2-item ui-tab2-item-current">
                    <a href="/community/activity/news/list">资讯列表</a>
                </li>           
            </ul>
        </div>

        <div class="ui-select1 activity-select ui-select-middel" style="display: none;">
            <span class="ui-select-text">请选择要查看的活动</span>
            <span class='tri-down'></span>
            <ul class="dropdown-menu" role="menu"></ul>
            <div class="dropdown-page-w">
                <a id="page-prev" class="tri-left" href="javascript:;"></a>
                <div class="page-index-w"></div>
                <a id="page-next" class="tri-right" href="javascript:;"></a>
            </div>      
        </div>

        <div class="mt20"> 
            <div class="k-table-container" id="tableCon">
                <table class="table">
                    <thead>
                        <tr>
                            <th>序号</th>
                            <th>发布时间</th>
                            <th>标题</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody style="">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    {!! csrf_field() !!}
@endsection

@section('javascript')
<script src="/static/js/common/dropdownPage.js"></script>
<script src="/static/js/activity/activityReports.js"></script>
    
@endsection