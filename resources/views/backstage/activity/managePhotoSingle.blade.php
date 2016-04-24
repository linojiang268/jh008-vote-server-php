@extends('layouts.main')

@section('title', '缴费详情')

@section('stylesheet')
    <link rel="stylesheet" href="/static/css/activity/activity_usersphotocheck.css"/>
@endsection

@section('content')
    <div class="h_1" >
        <div class="h_2"></div>
        <div class="h_3">齐丽萍决定是否合适的客户发送的空间发挥</div>
        <div class="clear" ></div>
    </div>
    <div class="m_1">
        @include('layouts.activityManagerNav')
        <div class="lr">
           <div class="header_div">
              <a class="header_div1" href="/community/activity/manage/photo/master">主办方相册 （999）</a>
              <a class="header_div2" href="/community/activity/manage/photo/users">用户相册 （999）</a>
              <div class="header_div3"></div><div class="clear" ></div>
           </div>

           <div class="img_div">
               <div class="img">
                    <img src="" class="tp" />
               </div>
              <div class="clear" ></div>
           </div>
           
           <div class="sc">
               <div class="sc1"  > 删 除 </div>
            </div>
           
            <div class="fh">
               <div class="fh1"> 其余通过审核 </div>
            </div>
            <div class="fhh"></div>
      </div>
    </div>
@endsection

@section('javascript')
    <script src="/static/js/activity/activity_usersphotocheck.js"></script>
@endsection