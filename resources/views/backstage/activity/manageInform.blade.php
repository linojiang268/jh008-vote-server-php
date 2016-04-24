@extends('layouts.main')

@section('title', '缴费详情')

@section('stylesheet')
    <link rel="stylesheet" href="/static/css/activity/activityManage.css"/>
@endsection

@section('content')
     <!-- <div class="activity-title clearfix" >
        <div class="img"></div>
        <div class="text">齐丽萍决定是否合适的客户发送的空间发挥</div>
     </div> -->
     <div class="manage-wrap">
        @include('layouts.activityManagerNav')
        <div class="clearfix activity-inform" id="noticeCon">
          <div class="w700 mt50">
            <div class="ui-form" name=""  action="#" id="noticeContainer">
                <div class="ui-form-item">
                    <label for="" class="ui-label">
                        通知内容:
                    </label>
                    <div class="ui-form-item-wrap">
                        <div class="limitText" id="pushWayLimit">
                            <div class="textarea-con">
                                <textarea  id="noticeTextarea" class="limitText-ta" placeholder="可输入128个字"></textarea>
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

                <div class="ui-form-item notice-way-item">
                    <label for="" class="ui-label">
                        通知方式:
                    </label>
                    <div class="ui-form-item-wrap">
                        <div class="radioSels clearfix" id="noticeWay">
                            <div class="radio-wrap sel">
                                <input type="radio" checked="checked" name="pushWay" value="1">
                                <label><i class="icon iconfont"></i>消息推送</label>
                            </div>
                            <div class="radio-wrap">
                                <input type="radio" name="pushWay" value="2">
                                <label><i class="icon iconfont"></i>短信</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="ui-form" name=""  action="#" id="">
              <div class="ui-form-item">
                  <label for="" class="ui-label"></label>
                  <div class="ui-form-item-wrap">
                      <a href="javascript:;" class="button button-orange w100 mt30" id="sendBtn">发送</a>
                  </div>
              </div>
            </div>            
          </div>
        </div>
     </div>
     {!! csrf_field() !!}
@endsection

@section('javascript')
    <script>
      var activity_id = "{{ $activity_id }}";
      var activityName = "{{  $activity_title }}"
    </script>
    <script src="/static/js/common/notice.js"></script>
    <script src="/static/js/activity/activityInform.js"></script>
@endsection