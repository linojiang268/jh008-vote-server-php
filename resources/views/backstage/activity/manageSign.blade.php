@extends('layouts.main')

@section('title', '缴费详情')

@section('stylesheet')
    <link rel="stylesheet" href="/static/plugins/webuploader/webuploader.css"/>
    <link rel="stylesheet" href="/static/css/activity/activityManage.css"/>
@endsection

@section('content')
    <!-- <div class="activity-title clearfix" >
      <div class="img"></div>
      <div class="text">齐丽萍决定是否合适的客户发送的空间发挥</div>
     </div> -->
    <div class="manage-wrap">
      @include('layouts.activityManagerNav')
      <div class="p15">
        <div id="tabs">
          <div class="ui-tab">
              <ul class="ui-tab-items">
                  <!-- <li class="ui-tab-item" role="sign">
                         <a href="javascript:;">签到管理</a>
                     </li>   -->   
                  <!-- <li class="ui-tab-item" role="qrcode">
                                <a href="javascript:;">签到二维码设置</a>
                            </li>  -->          
                  <li class="ui-tab-item ui-tab-item-current" role="process">
                      <a href="javascript:;">活动流程</a>
                  </li>
                  <li class="ui-tab-item" role="sponsor">
                      <a href="javascript:;">主办方</a>
                  </li>                  
                  <li class="ui-tab-item" role="file">
                      <a href="javascript:;">文档</a>
                  </li>
                  <li class="ui-tab-item" role="roadLine">
                      <a href="javascript:;">路线指引</a>
                  </li>
              </ul>
          </div>  
          <div class="tab-pages">
            <div class="tab-page tab-page-active" id="process">
              <div class="process-area">
                <div class="p15">
                  <table class="process-table table" id="processTable">
                    <thead>
                      <tr>
                        <th class="time-row">开始时间</th>
                        <th class="time-row">结束时间</th>
                        <th class="text-row">流程内容</th>
                        <th class="ope-row">操作</th>
                      </tr>
                    </thead>
                    <tbody>
                    </tbody>
                  </table> 
                  <a href="javascript:;" id="save" class="button button-blue mt20">保存流程设置</a>          
                </div>
              </div>
            </div>
            <div class="tab-page" id="sponsor">
              <div class="p15">
                <p class="tip1">主办方列表：</p>
                <div id="conditions" class="mt20">
                  
                </div>
                <div class="verify-raise">
                  <input type="text" id="conditionInput" class="form-control w200 mr10" placeholder="请填写主办方">
                  <a href="javascript:;" class="button button-blue" id="addCondition" >添加</a>
                </div>
                <div class="mt20">
                  <a href="javascript:;" id="sureBtn" class="button button-orange">确定修改</a>
                </div>
              </div>
            </div>
            <div class="tab-page" id="qrcode">
              <div class="qrcode-area">
                <p class="tip1">签到二维码设置</p>
                <div class="sign-operate">
                  <div class="clearfix">
                      <div class="sz">
                        整个活动中，你需要成员共签到：
                        <input id="sign-input" type="text" maxlength="2" /> 次
                      </div>
                      <a href="javascript:;" id="sign-sure" class="button button-orange button-m">确定</a>
                  </div>
                </div>
            
                <ul class="sign-qrcodes clearfix">
                  <li class="sign-qrcode"><a class="sign-qrcode-content"></a></li>
                  <li class="sign-qrcode"><a class="sign-qrcode-content"></a></li>
                  <li class="sign-qrcode"><a class="sign-qrcode-content"></a></li>
                  <li class="sign-qrcode"><a class="sign-qrcode-content"></a></li>
                  <li class="sign-qrcode"><a class="sign-qrcode-content"></a></li>
                  <li class="sign-qrcode"><a class="sign-qrcode-content"></a></li>
                  <li class="sign-qrcode"><a class="sign-qrcode-content"></a></li>
                  <li class="sign-qrcode"><a class="sign-qrcode-content"></a></li>
                  <li class="sign-qrcode"><a class="sign-qrcode-content"></a></li>
                  <li class="sign-qrcode"><a class="sign-qrcode-content"></a></li>
                </ul>
                        
                <div class="sure-wrap">
                  <a href="/community/activity/checkin/qrcode/download?activity_id={{$activity_id}}" id="batch-download" class="button button-blue">批量下载</a>
                  <a href="javascript:;" class="button button-orange ml20">返回</a>
                </div>             
              </div>
            </div>
            <div class="tab-page" id="sign">
              <div>
                @if ($qr_code_url)
                  <div >
                    <img src="{{$qr_code_url}}" style="width:200px; margin: 15px 0 0 0;" alt="">
                  </div>
                @endif
              </div>
              <div class="sign-area p15">
                <!-- <div class="sign-area-top">
                  <span class="tip">签到二维码列表：</span>
                  <div class="ui-select ui-select-middel" id="signSelect">
                    <span class="ui-select-text"></span>
                    <span class="tri-down"></span>
                    <ul class="dropdown-menu" role="menu" style="display: none;">
                    </ul>
                  </div>                 
                </div> -->
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

            <div class="tab-page" id="file">
              <div class="file-area p15">
                <div class="upload-file mt10">
                  <a href="javascript:;" id="uploadFile" class="upload-file-btn">上传资料</a>
                  <!-- <span class="upload-progress-bar">
                    <span class="upload-progress"></span>
                  </span> -->
                  <div class="loading-div" id="uploadLoading">
                    <div class="upload-loading-wrap" id="uploadLoadingWrap">
                      <span class="loading-text">文件正在上传中</span>
                      <i class="loading"></i>                      
                    </div>
                  </div>
                  <p class="upload-file-tip">注：上传文件不能超过10M。</p>
                </div>
                <div class="file-list mt20">
                  <div class="k-table-container" id="fileTableCon">
                      <table class="table">
                          <thead>
                              <tr>
                                  <th>编号</th>
                                  <th>文件名</th>
                                  <th>大小</th>
                                  <th>操作</th>
                              </tr>
                          </thead>
                          <tbody>
                          </tbody>
                      </table>
                  </div>
                </div>
              </div>
            </div>

            <div class="tab-page" id="roadLine">
              <div class="roadLine-area p15">
                <div class="activity-poi-map-warp  luxian">
                    <div class="ui-form-item address-search">
                        <input name="search-poi" id="poiSearch" class="form-control w400" type="text"
                               placeholder="请输入起点"/>
                        <a href="javascript:void(0);" class="button search-btn button-orange" id="roadSearch">搜索</a>
                    </div>
                    <div class="active-pos-map" id="roudMap" style="height: 500px;"></div>
                    <div class="mt15">
                        <a href="javascript:void(0);" id="save" class="button search-btn button-orange" id="roadSearch">保存修改</a>
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
    <script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=hqkEDHjAXn4VaTzt3a7RRZGP"></script>
    <script src="/static/plugins/webuploader/webuploader.js"></script>
    <script src="/static/plugins/laydate/laydate.js"></script>
    <script src="/static/js/activity/mapHelper.js"></script>
    <script src="/static/js/activity/activitySign.js"></script>
    <script>
        var activity_id = {{$activity_id}};
    </script>
@endsection