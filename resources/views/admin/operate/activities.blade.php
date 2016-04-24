@extends('admin.layout.main')

@section('title', '活动列表')
    <link rel="stylesheet" href="/static/admin/css/page.css">
@section('stylesheet')

@endsection


@section('content')
    <div class="filter-con">
        <dl class="filter-bar" data-role="tags">
            <dt>是否已标记标签:</dt>
            <dd class="filter-select"><a href="javascript:;" data-v="0">不限</a></dd>
            <dd class=""><a href="javascript:;" data-v="1">已标记</a></dd>
            <dd class=""><a href="javascript:;" data-v="2">未标记</a></dd>
        </dl>
        <dl class="filter-bar" data-role="status">
            <dt>是否封停:</dt>
            <dd class="filter-select"><a href="javascript:;" data-v="0">不限</a></dd>
            <dd class=""><a href="javascript:;"  data-v="1">已封停</a></dd>
            <dd class=""><a href="javascript:;"  data-v="2">未封停</a></dd>
        </dl>
        <div class="mt15">
            <input type="text" placeholder="请输入活动名称搜索" id="search" class="form-control w300">
            <a href="javascript:;" class="button button-orange ml10" id="serachBtn">搜索</a>
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
                            <th>状态</th>
                            <th>标签</th>
                            <th>查看详情</th>
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
                        <div class="ui-form-item-text"><%= enroll_bgein_time  %></div>
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
    <script type="text/javascript" src="/static/js/common/artTemplate.js"></script>
    <script type="text/javascript" src="/static/admin/js/operate/activities.js"></script>
@endsection
