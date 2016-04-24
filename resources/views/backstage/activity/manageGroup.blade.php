@extends('layouts.main')

@section('title', '缴费详情')

@section('stylesheet')
    <link rel="stylesheet" href="/static/css/activity/activityGroup.css"/>
@endsection

@section('content')
<!--      <div class="h_1" >
        <div class="h_2"></div>
        <div class="h_3">齐丽萍决定是否合适的客户发送的空间发挥</div>
        <div class="clear" ></div>
     </div> -->
     <div class="m_1">
        @include('layouts.activityManagerNav')
        <div class="lr">
           <div class="lr1">
              <div class="lr2"></div>
              <div class="lr3">
                 <div class="sz">
                    <div class="sz1"></div>
                    <div class="sz2">活动总人数:</div>
                    <div class="sz3" id="user-num"></div>
                    |<div class="clear" ></div>
                </div>
                 <div class="sz4">
                  分成：<input id="groups-input" type="text" maxlength="100" style="width:18px;text-align: center;height:18px;margin-top: 5px;border-radius: 4px;color: #999999;border: 1px solid #e4e4e7;" /> 组 
                 </div>
                 <div class="fqdd" style="">分 组</div>
              </div>
              <div class="clear" ></div>
           </div>
        
            <div class="lr_fx">
              <div class="fz-div-w"></div>
            <div class="clear" ></div>
            </div>
            
            <div class="fh">
               <a class="fh1" href="/org_v2/default/activity_list"> 返 回 </a>
            </div>
            <div class="fhh"></div>
            {!! csrf_field() !!}
      </div>
      
     </div>
@endsection

@section('javascript')
    <script src="/static/js/activity/activityGroup.js"></script>
    <script type="text/javascript">
      var activity_id = {{$activity_id}};
      console.log(activity_id);
    </script>
@endsection
