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
                        <th>手机号</th>
                        <th>三围</th>
                        <th>身高</th>
                        <th>体重</th>
                        <th>鞋码</th>
                        <th>查看详情</th>
                        <th>操作</th>
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
                        <th>手机号</th>
                        <th>三围</th>
                        <th>身高</th>
                        <th>体重</th>
                        <th>鞋码</th>
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
                    <label for="" class="ui-label">三围:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= bwh %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">身高:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= height %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">体重:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= weight %></div>
                    </div>
                </div>
                <div class="ui-form-item">
                    <label for="" class="ui-label">鞋码:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= shoe_size %></div>
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
                <div class="ui-form-item">
                    <label for="" class="ui-label">简历:</label>
                    <div class="ui-form-item-wrap">
                        <div class="ui-form-item-text"><%= intro  %></div>
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
    <script src="/static/planeModel/js/server.js"></script>
    <script src="/static/js/common/json2.js"></script>
    <script src="/static/js/common/artTemplate.js"></script>
    <script src="/static/planeModel/js/auditList.js"></script>
</body>
</html>