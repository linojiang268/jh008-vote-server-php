@extends('layouts.main')

@section('title', '成员管理')

@section('stylesheet')
    <link rel="stylesheet" href="/static/css/team/assn.css"/>
@endsection

@section('content')
    <div class="manage-con">
        <!-- <div class="ui-tab2">
            <ul class="ui-tab2-items">
                <li class="ui-tab2-item ui-tab2-item-current">
                    <a href="/community/team/manager">成员管理</a>
                </li>
                <li class="ui-tab2-item">
                    <a href="/community/team/manager/group">组管理</a>
                </li>            
            </ul>
        </div> -->
        @include('layouts.memberManagerNav')
        <div class="mt20">
            <div class="filter-con clearfix">
                <ul class="filter-menu clearfix">
                    <li class="filter-menu-item">
                        <a class="filter-item-link" href="javascript:;">
                            <span class="fitler-item-link-tip" id="filterItemLinkTip">全部</span>
                            <i class="icon iconfont"></i>
                        </a>
                        <ul class="subfilter-menu" id="groupFilter">
                            <li class="sf-menu-item" id="allFilter">
                                <a href="javascript:;" class="sf-item-link">全部</a>
                            </li>
                            @if ($groups)
                                @foreach ($groups as $group)
                                    <li class="sf-menu-item">
                                        <a href="javascript:;" data-id="{{ $group->getId() }}" class="sf-item-link">{{ $group->getName() }}</a>
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                    </li>
                </ul>
                <input type="text" id="searchInput" class="form-control search-input" placeholder="请输入昵称、手机号搜索">
                <a href="javascript:;" id="search" class="button button-orange button-m">搜索</a>
                <a href="" target="_blank" id="export" class="button button-orange button-m fr export-button">导出Excel</a>            
            </div>

            <div id="memberList">
                <div id="tableCon">
                    <table>
                        <thead>
                            <tr>
                                <th><input id="selectAll" type="checkbox" name="selectAll"></th>
                                <th>编号</th>
                                <th>加入时间</th>
                                <th>手机号</th>
                                <th>昵称</th>
                                <!-- <th>所在组</th> -->
                                <th>自定义信息</th>
                                <th>备注信息</th>
                                <!-- <th>操作</th> -->
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
           <!--  <div class="ope-bar">
               <a href="javascript:;" class="button" id="pitchAdjustGroup">批量调整分组</a>
               <a href="javascript:;" class="button ml10" id="pitchCancelGroup">批量取消分组</a>
           </div> -->
        </div>
        {!! csrf_field() !!}
    </div>
@endsection

@section('javascript')
    <script src="/static/js/team/manage.js"></script>
@endsection
