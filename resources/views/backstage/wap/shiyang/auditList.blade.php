<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="renderer" content="webkit">
    <title></title>
    <link rel="stylesheet" href="/static/css/common/reset.css"/>
    <link rel="stylesheet" href="/static/css/common/base.css"/>
    <link rel="stylesheet" href="/static/css/iconfont/iconfont.css"/>
    <link rel="stylesheet" href="/static/css/common/lc_ui.css"/>
    <link rel="stylesheet" href="/static/css/common/lc_layer.css"/>
    <link rel="stylesheet" href="/static/plugins/ktable/skins/k-table.css"/>
    <link rel="stylesheet" href="/static/css/common/common.css"/>
    <style type="text/css">
        .audit-w,
        .pass-w {
            width: 1200px;
            padding: 0 50px;
            margin: 10px auto 10px;
        }
        .audit-w h2,
        .pass-w h2 {
            height: 50px;
            line-height: 50px;
            padding-left: 10px;
            font-size: 24px;
            color: red;
            background: rgba(255,0,0,0.1);
        }
        .ui-uploadThumb {
            height: auto;
            min-height: 154px;
        }
    </style>
</head>
<body>
<div class="section-bd">
    <div class="audit-w">
        <h2>待审核</h2>
        <div class="aduit-table" id="aduit-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>编号</th>
                        <th>姓名</th>
                        <th>性别</th>
                        <th>手机号</th>
                        <th>年龄</th>
                        <th>工作单位</th>
                        <th>年薪</th>
                        <th>微信号</th>
                        <th>才艺</th>
                        <th>申请成为上台嘉宾</th>
                        <th colspan="2">操作</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <div class="pass-w">
        <h2>已通过</h2>
        <div class="passed-table" id="passed-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>编号</th>
                        <th>姓名</th>
                        <th>性别</th>
                        <th>手机号</th>
                        <th>年龄</th>                        
                        <th>工作单位</th>
                        <th>年薪</th>
                        <th>微信号</th>
                        <th>才艺</th>
                        <th>申请成为上台嘉宾</th>
                        <th>查看详情</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>


<script id="detail_template" type="text/template">
        <div class="detail-dia-con">
            <div class="ui-form" name="" method="post" action="#" id="">
                <div class="ui-form-item">
                    <label for="" class="ui-label">姓名:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= name %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">手机号:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= mobile %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">性别:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><% if(gender == 1) {%>男<%} else if(gender == 2){%>女<%}%></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">年龄:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= age %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">工作单位:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= work_unit %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">年薪:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= yearly_salary %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">微信号:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= wechat_id %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">才艺:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= talent %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label" style="line-height: 14px;">申请成为上台嘉宾:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= guest_apply ? "是" : "否" %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">个人照片:</label>
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
            </div>
        </div>
</script>

    <script src="/static/plugins/jquery-1.7.1.min.js"></script>
    <script src="/static/plugins/jquery-ui-1.11.2.custom/jquery-ui.min.js"></script>
    <script src="/static/plugins/layer/layer.js"></script>
    <script src="/static/plugins/jquery.validate.js"></script>
    <script src="/static/plugins/ktable/utilHelper.js"></script>
    <script src="/static/plugins/ktable/k-paginate.js"></script>
    <script src="/static/plugins/ktable/k-table.js"></script>
    <script src="/static/js/common/K.js"></script>
    <script src="/static/js/common/lc.js"></script>
    <script src="/static/js/common/base.js"></script>
    <script src="/static/js/common/dialogUi.js"></script>
    <script src="/static/shiyang/js/server.js"></script>
    <script src="/static/js/common/json2.js"></script>
    <script src="/static/js/common/artTemplate.js"></script>
    <script src="/static/shiyang/js/auditList.js"></script>
</body>
</html>