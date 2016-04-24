@extends('layouts.main')

@section('title', '活动管理')

@section('stylesheet')
    <link rel="stylesheet" href="/static/css/activity/activity.css"/>
@endsection

@section('content')
    <div class="search-w">
        <div class="date-w">
            <input class="form-control" id="date-start" type="text" placeholder="请选择日期">
            <span>至</span>
            <input class="form-control" id="date-end" type="text" placeholder="请选择日期">
        </div>
        <a href="javascript:;" id="activitySearch-btn" class="button button-orange button-pre btn-search"><i class="icon iconfont"></i>搜&nbsp;索</a>
    </div>
  
    <div class="mt20">
        <div class="k-table-container" id="tableCon">
            <table class="table">
                <thead>
                    <tr>
                        <th>活动名称</th>
                        <th>活动开始时间</th>
                        <th>报名人数</th>
                        <th>状态</th>
                        <th>修改内容</th>
                        <th>签到管理</th>
                        <th>活动管理</th>
                    </tr>
                </thead>
                <tbody style="">
                </tbody>
            </table>
        </div>
    </div>
    {!! csrf_field() !!}
@endsection

@section('javascript')
    <script type="text/template" id="activity_detail_template">
        <div class="detail-dia-con">
            <div class="ui-form" name="" method="post" action="#" id="">
                <div class="ui-form-item">
                    <label for="" class="ui-label">活动名称:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= title %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">活动开始时间:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= begin_time %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">活动结束时间:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= end_time %></div>
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
                        <div class="ui-form-item-text"><%= telephone %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">活动图片:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-uploadThumbs clearfix mt20"> 
                            <% if (images_url) { %>
                                <% for (var i = 0; i < images_url.length; i++) { %>
                                    <div class="ui-uploadThumb ui-uploadThumb-has">
                                        <div class="ui-uploadThumb-link" href="javascript:;">
                                            <div class="ui-upload-img-wrap">
                                                <img src="<%= images_url[i] %>" alt="" />
                                            </div>             
                                        </div>
                                    </div>
                                <% } %>
                            <% } %>
                        </div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">详情地址:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= address  %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">地址别名:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= brief_address  %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">活动详情:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text" id="detail"><%= detail  %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">报名开始时间:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= enroll_begin_time  %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">报名结束时间:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= enroll_end_time  %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">是否审核:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text">
                            <% if (auditing == 1) {  %>
                                审核
                            <% } else if (auditing == 0) { %>
                                不审核
                            <% } %>
                        </div>
                    </div>
                </div> 
                <div class="ui-form-item">
                    <label for="" class="ui-label">人数:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text">
                            <% if (enroll_limit > 0){  %>
                                <%= enroll_limit %>人
                            <% } else { %>
                                不限
                            <% } %>
                        </div>
                    </div>
                </div> 
                <div class="ui-form-item">
                    <label for="" class="ui-label">报名费:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text">
                            <% if (enroll_fee_type == 1){  %>
                                免费
                            <% } else if (enroll_fee_type == 2) { %>
                                AA制
                            <% } else if (enroll_fee_type == 3) { %>
                                收费 (<%= enroll_fee %>元)
                            <% } %>
                        </div>
                    </div>
                </div>                                
                <div class="ui-form-item">
                    <label for="" class="ui-label">报名资料:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text">
                            <% if (enroll_attrs) { %>
                                <% for (var i = 0; i < enroll_attrs.length; i++) { %>
                                    <a href="javascript:;" class="button button-orange button-m mr10 mt10"><%= enroll_attrs[i] %></a>
                                <% } %>
                            <% } %>
                        </div>
                    </div>
                </div> 
            </div>
        </div>
    </script>
    <script src="/static/plugins/laydate/laydate.js"></script>
    <script src="/static/js/common/artTemplate.js"></script>
    <script src="/static/js/activity/activityList.js"></script>
@endsection