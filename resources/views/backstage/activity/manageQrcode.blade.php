@extends('layouts.main')

@section('stylesheet')
    <link rel="stylesheet" href="/static/plugins/webuploader/webuploader.css"/>
    <link rel="stylesheet" href="/static/css/activity/activityManage.css"/>
@endsection

@section('content')
    <div class="manage-wrap">
      <div class="p15">
        <div id="tabs">
          <div class="ui-tab">
              <ul class="ui-tab-items">     
                  <li class="ui-tab-item" role="qrcode">
                      <a href="javascript:;">签到二维码</a>
                  </li>                
                  <li class="ui-tab-item" role="list">
                      <a href="javascript:;">签到管理</a>
                  </li>
              </ul>
          </div> 
          <div class="tab-pages">

            <!-- qrcode tab -->
            <div class="tab-page" id="qrcode">
              <div>
                  <p class="qrcode-tip mt50">用户可使用“集合APP”或“微信”扫码签到</p>
                  <p class="qrcode-tip mt10">管理员可以通过“集合APP”随时查看报名及签到情况</p>
                @if ($qr_code_url)
                  <div class="qrcode-img-w">
                    <img src="{{$qr_code_url}}" class="qrcode-img" alt="">
                  </div>
                  <div class="tc">
                    <a href="/community/activity/checkin/qrcode/download?activity_id={{$activity_id}}" class="button button-orange">下载</a>
                  </div>
                @endif
              </div>
            </div>

            <!-- list tab -->
            <div class="tab-page" id="list">
              <div class="sign-area p15">
                <div class="mt30">
                  <div class="k-table-container" id="tableCon">
                      <table class="table">
                          <thead>
                              <tr>
                                  <th>编号</th>
                                  <th>昵称</th>
                                  <th>手机号</th>
                                  <th>签到时间</th>
                              </tr>
                          </thead>
                          <tbody>
                          </tbody>
                      </table>
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
    <script>
      var qr_code_id = '{{$qr_code_id}}';
    </script>
    <script src="/static/plugins/webuploader/webuploader.js"></script>
    <script src="/static/plugins/laydate/laydate.js"></script>
    <script src="/static/js/activity/activityQrcode.js"></script>
    <script>
        var activity_id = {{$activity_id}};
    </script>
@endsection