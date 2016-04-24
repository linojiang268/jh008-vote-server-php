@extends('layouts.main')

@section('title', '主办方相册')

@section('stylesheet')
    <link rel="stylesheet" href="/static/plugins/webuploader/webuploader.css"/>
    <link rel="stylesheet" href="/static/css/activity/activityManage.css"/>
@endsection

@section('content')
      <div class="manage-wrap">
        @include('layouts.activityManagerNav')
        <div class="photo-con">
          <div class="ui-tab mt10">
            <ul class="ui-tab-items">
                <li class="ui-tab-item ui-tab-item-current">
                    <a href="/community/activity/{{ $activity_id }}/manage/photo/master">主办方相册</a>
                </li>
                <li class="ui-tab-item">
                    <a href="/community/activity/{{ $activity_id }}/manage/photo/users">用户相册</a>
                </li>
            </ul>
          </div>

          <div class="photos-manager" id="photosManager"></div>

        </div>
      </div>
      {!! csrf_field() !!}
@endsection

@section('javascript')
    <script>
      var activityId = "{{ $activity_id }}";
    </script>
    <script src="/static/js/activity/photo.js"></script>
    <script src="/static/plugins/webuploader/webuploader.js"></script>
    <script src="/static/js/activity/photosApplication.js"></script>
    <script src="/static/js/activity/activityMasterPhoto.js"></script>
@endsection