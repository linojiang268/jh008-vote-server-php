@extends('layouts.main')

@section('title', '社团通知')

@section('stylesheet')
    <link rel="stylesheet" href="/static/css/team/assn.css"/>
@endsection

@section('content')
    <div class="notice-con" id="noticeCon">
        <div class="notice-ways-con">
            <div class="ui-tab2">
                <ul class="ui-tab2-items">
                    <li class="ui-tab2-item ui-tab2-item-current">
                        <a href="/community/team/notice">发送通知</a>
                    </li>
                    <li class="ui-tab2-item">
                        <a href="/community/team/notice/list">通知记录</a>
                    </li>            
                </ul>
            </div>
            <div class="p15">

                <div class="ui-form" name="" method="post" action="#" id="noticeContainer">
                    <div class="ui-form-item notice-way-item">
                        <label for="" class="ui-label">
                            类型:
                        </label>
                        <div class="ui-form-item-wrap">
                            <div class="radioSels clearfix" id="noticeWay">
                                <div class="radio-wrap sel">
                                    <input type="radio" checked="checked" name="pushWay" value="1">
                                    <label><i class="icon iconfont"></i>APP消息推送</label>
                                </div>
                                <div class="radio-wrap">
                                    <input type="radio" name="pushWay" value="2">
                                    <label><i class="icon iconfont"></i>短信</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ui-form-item">
                        <label for="" class="ui-label">
                            内容:
                        </label>
                        <div class="ui-form-item-wrap">
                            <div class="limitText" id="pushWayLimit">
                                <div class="textarea-con">
                                    <textarea id="noticeTextarea" class="limitText-ta" placeholder="可输入128个字"></textarea>
                                    <span class="text-tip">0/128</span>                      
                                </div>
                            </div> 
                            <div class="limitText" id="smsWayLimit" style="display:none">
                                <div class="textarea-con">
                                    <textarea id="msgTextarea" class="limitText-ta" placeholder="可输入60个字"></textarea>
                                    <span class="text-tip">0/60</span>                      
                                </div>
                            </div> 
                        </div>
                    </div>
                    <div class="ui-form-item">
                        <label for="" class="ui-label">
                            对象:
                        </label>
                        <div class="ui-form-item-wrap">
                            <div class="notice-sel-con" id="noticeMembersCon">
                                <div class="unsel-main">
                                    <div class="p15">
                                        <div class="search-wrap">
                                            <input type="text" class="search" id="search" placeholder="请输入查找关键字">
                                            <a class="del-btn"><i class="icon iconfont" id="delSearch"></i></a>
                                        </div>
                                        <div class="sel-wrap">
                                            <div class="checkboxSels clearfix sel-all">
                                                <div class="checkbox-wrap">
                                                    <input type="checkbox" name="selAll" id="selAll" value="1">
                                                    <label><i class="icon iconfont"></i>全部</label>
                                                </div>
                                            </div>
                                            <div id="groupNavCon">
                                                <div id="groupList"></div>
                                                <div id="searchList">
                                                    <ul class="member-nav" id="searchNav"></ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mid-main">
                                    <span class="mid-show"><i class="icon iconfont"></i></span>
                                </div>
                                <div class="sel-main">
                                    <div class="sel-main-wrap">
                                        <p class="sel-num-tip">已选：<span id="selNumText"></span></p>
                                        <div class="sel-has-con">
                                            <ul class="member-nav" id="hasSelNav"></ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="send-notice-c">
                                    <a href="javascript:;" class="button button-orange" id="sendBtn">发送</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    {!! csrf_field() !!}
@endsection

@section('javascript')
    @include('backstage.front.noticeDialog')
    <script>
        var teamName = '{{ $team->getName() }}';
    </script>
    <script src="/static/js/common/notice.js" ></script>   
    <script src="/static/js/team/notice.js" ></script>   
@endsection