@extends('layouts.main')

@section('title', '活动资讯')

@section('stylesheet')
    <!-- <link rel="stylesheet" href="/static/css/activity/activityNews.css"/> -->
    <link rel="stylesheet" href="/static/plugins/webuploader/webuploader.css"/>
    <link type="text/css" rel="stylesheet" href="/static/ueditor1_4_3-utf8-php/themes/default/css/ueditor.css">
    <link rel="stylesheet" href="http://xiumi.us/connect/ue/xiumi-ue-v1.css">
    <link rel="stylesheet" href="/static/css/activity/activityNewsPublish.css">
@endsection

@section('content')
    <div class="m_1">
        <div class="ui-tab2">
            <ul class="ui-tab2-items">
                <li class="ui-tab2-item ui-tab2-item-current">
                    <a href="/community/activity/news/publish">资讯发布</a>
                </li> 
                <li class="ui-tab2-item">
                    <a href="/community/activity/news/list">资讯列表</a>
                </li>          
            </ul>
        </div>
        @if ( $news )
            <div id="news-cover" class="news-cover-upload">
                <p class="tip1">活动封面上传: </p>
                <!-- <div class="ui-uploadThumb ui-uploadThumb-has">
                    <div class="ui-uploadThumb-link " href="javascript:;">
                        <div class="ui-upload-img-wrap">
                            <img src="{{$news->getcoverUrl()}}" alt="">
                        </div> 
                        <div class="ui-uploadThumb-option">
                            <a href="javascript:;" class="close"><i class="icon iconfont"></i></a>
                        </div>
                    </div>
                </div> -->
            </div>
        @elseif ( !$news )
            <div class="ui-select1 ui-select-middel mt20 mb20" style="width:230px; display:none;">
                <span class="ui-select-text">请选择对应的活动</span>
                <span class='tri-down'></span>
                <ul class="dropdown-menu" role="menu"></ul>
                <div class="dropdown-page-w">
                    <a id="page-prev" class="tri-left" href="javascript:;"></a>
                    <div class="page-index-w"></div>
                    <a id="page-next" class="tri-right" href="javascript:;"></a>
                </div>      
            </div>
            <br/>
            <div id="news-cover" class="news-cover-upload">
                <!-- <div class="ui-uploadThumbs clearfix mt20"> 
                    <div class="ui-uploadThumb ">
                        <div class="ui-uploadThumb-link" href="javascript:;">
                            <div class="ui-upload-img-wrap">
                                <img src="" alt="" />
                            </div>             
                        </div>
                    </div>
                </div> -->
            </div>
         @endif
        <div class="mt20">
            <input type="text" id="newstitle" class="form-control" style="width:206px;" placeholder="请输入标题">
        </div>
        <div class="mt20"> 
            <script id="container" name="content" type="text/plain"></script>
        </div>

        <a href="javascript:;" id="newsSubmit" class="button button-orange news-submit">提交</a>
    </div>

    <input type="hidden" id='newsId' value="{{ $news ? $news->getId() : ''}}">
    <input type="hidden" id='coverUrl' value="{{ $news ? $news->getcoverUrl() : ''}}">
    {!! csrf_field() !!}

@endsection

@section('javascript')
    <script src="/static/ueditor1_4_3-utf8-php/ueditor.config.js"></script>
    <script src="/static/ueditor1_4_3-utf8-php/ueditor.all.js"></script>
    <script src="/static/plugins/webuploader/webuploader.js"></script>
    <script src="/static/js/common/imageUpload.js"></script>
    <script src="/static/js/common/dropdownPage.js"></script>
    

    <script>
        UE.registerUI('dialog', function (editor, uiName) {
            var btn = new UE.ui.Button({
                name   : 'xiumi-connect',
                title  : '秀米',
                onclick: function () {
                    var dialog = new UE.ui.Dialog({
                        iframeUrl: '/static/ueditor1_4_3-utf8-php/xiumi-ue-dialog-v1.html',
                        editor   : editor,
                        name     : 'xiumi-connect',
                        title    : "秀米图文消息助手",
                        cssRules : "width: " + (window.innerWidth - 60) + "px;" + "height: " + (window.innerHeight - 60) + "px;",
                    });
                    dialog.render();
                    dialog.open();
                }
            });

            return btn;
        });

        var ue = UE.getEditor('container', {
            toolbars: [
                ['fullscreen', 'source', 'undo', 'redo'],
                ['bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'removeformat', 
                'autotypeset', '|', 'forecolor', 'backcolor', 'insertorderedlist', 'insertunorderedlist', 'selectall', 'cleardoc',
                'horizontal', 'simpleupload', 'time', 'date', 'link', 'justifyleft', 'justifyright', 'justifycenter', 'fontfamily',
                'fontsize']
            ]
        });
    </script>
    
    @if( $news )
    <script>
        var newsTitle = '{{ $news->getTitle()}}';
        //资讯内容要过滤掉多余的回车才不报错
        var newsContent = '{!!$str=preg_replace("/\s+/", " ", $news->getContent())!!}';
        console.log(newsTitle);
        console.log(newsContent);
        $("#newstitle").val(newsTitle);
        ue.ready(function() {
            ue.setContent(newsContent);
        });   
    </script>
    @endif
    <script src="/static/js/activity/activityNewsPublish.js"></script>
@endsection
