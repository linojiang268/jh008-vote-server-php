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
        <div>
          <div class="share-tip">分享本活动至：</div> 
          <div class="share-area">
            <div class="bdsharebuttonbox clearfix">
               <a href="#" class="share-sina share-btn" data-cmd="tsina" title="分享到新浪微博"></a>
               <a href="#" class="share-weixin share-btn" data-cmd="weixin" title="分享到微信"></a>
               <a href="#" class="share-qzone share-btn" data-cmd="qzone" title="分享到QQ空间"></a>
               <a href="#" class="share-renren share-btn" data-cmd="renren" title="分享到人人网"></a>
               <a href="#" class="share-qq share-btn" data-cmd="sqq" title="分享到QQ好友"></a>
            </div>
            <!-- http://share.baidu.com/code/advance
            bdText : '自定义分享内容',  
            bdDesc : '自定义分享摘要',  
            bdUrl : '自定义分享url地址',    
            bdPic : '自定义分享图片'
            -->
            </div>
            <div class="sure-wrap">
              <input type="text" id="share-url" value="{{config('app')['url']}}{{route('activity.detail', ['activity_id' => $activity_id], false)}}" readonly>
              <a href="javascript:;" id="copy-btn" class="copy-btn button button-orange">点击复制 (活动详情)</a>
            </div>
            <div class="sure-wrap">
              <input type="text" id="share-url1" value="{{config('app')['url']}}{{route('activity.detail', ['activity_id' => $activity_id], false)}}#page=p2" readonly>
              <a href="javascript:;" id="copy-btn1" class="copy-btn button button-orange">点击复制 (活动报名)</a>
            </div>  
        </div>
    </div>
    <input type="hidden" id="aid" value="{{$activity_id}}">
@endsection

@section('javascript')
    <script src="/static/js/activity/activityShare.js"></script>
@endsection