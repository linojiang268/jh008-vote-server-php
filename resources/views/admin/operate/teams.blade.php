@extends('admin.layout.main')

@section('title', '社团列表')
    <link rel="stylesheet" href="/static/admin/css/page.css">
@section('stylesheet')

@endsection


@section('content')
    <div class="filter-con">
        <dl class="filter-bar" data-role="tagged">
            <dt>是否已标记标签:</dt>
            <dd class="filter-select"><a href="javascript:;" data-v="2">不限</a></dd>
            <dd class=""><a href="javascript:;" data-v="1">已标记</a></dd>
            <dd class=""><a href="javascript:;" data-v="0">未标记</a></dd>
        </dl>
        <dl class="filter-bar" data-role="forbidden">
            <dt>是否封停:</dt>
            <dd class="filter-select"><a href="javascript:;" data-v="2">不限</a></dd>
            <dd class=""><a href="javascript:;" data-v="1">已封停</a></dd>
            <dd class=""><a href="javascript:;" data-v="0">未封停</a></dd>
        </dl>
        <div class="mt15">
            <input type="text" placeholder="请输入社团名称搜索" id="search" class="form-control w300">
            <a href="javascript:;" class="button button-orange ml10" id="searchBtn">搜索</a>
        </div>
    </div>  
    <div id="membersList" class="mt20">
        <div id="listCon">
            <div id="tableCon">
                <table>
                    <thead>
                        <tr>
                            <th>序号</th>
                            <th>社团名称</th>
                            <th>查看详情</th>
                            <th>标签</th>
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
    <script type="text/template" id="team_detail_template">
        <div class="detail-dia-con">
            <div class="ui-form" name="" method="post" action="#" id="">
                <div class="ui-form-item">
                    <label for="" class="ui-label">社团名称:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= name %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">团队Logo:</label>
                    <div class="ui-form-item-wrap">
                        <div class="logo-w"><img src="<%= logo_url %>">
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">城市:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= city_name %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">地址:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= address %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">联系人:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= contact %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">联系电话:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= contact_phone %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">电子邮箱:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= email %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">团队简介:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= introduction  %></div>
                    </div>
                </div>
            </div>
        </div>
    </script>
    <script type="text/javascript" src="/static/js/common/artTemplate.js"></script>
    <script type="text/javascript" src="/static/admin/js/operate/teams.js"></script>
@endsection
