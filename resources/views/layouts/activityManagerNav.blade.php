<div class="activity-title clearfix" >
    <div class="img"></div>
    <div class="text" id="ac-title">{{ $activity_title }}</div>
</div>
<div class="ui-tab2">
    <ul class="ui-tab2-items">
        <li class="ui-tab2-item @if ($key === 'manageCheck') ui-tab2-item-current @endif">
            <a href="/community/activity/{{$activity_id}}/manage/check"><i class="icon iconfont"></i>报名审核</a>
        </li>
        <li class="ui-tab2-item @if ($key === 'manageInform') ui-tab2-item-current @endif">
            <a href="/community/activity/{{$activity_id}}/manage/inform"><i class="icon iconfont"></i>发送通知</a>
        </li>
        <!-- <li class="ui-tab2-item @if ($key === 'manageGroup') ui-tab2-item-current @endif">
                    <a href="/community/activity/{{$activity_id}}/manage/group"><i class="icon iconfont"></i>分组</a>
                </li> -->        
        <li class="ui-tab2-item @if ($key === 'manageSign') ui-tab2-item-current @endif">
            <a href="/community/activity/{{$activity_id}}/manage/sign"><i class="icon iconfont"></i>活动手册</a>
        </li>
        <li class="ui-tab2-item @if ($key === 'managePhotoMaster' || $key === 'managePhotoUsers' || $key === 'managePhotoSingle') ui-tab2-item-current @endif">
            <a href="/community/activity/{{$activity_id}}/manage/photo/master"><i class="icon iconfont"></i>照片墙</a>
        </li> 
        <li class="ui-tab2-item @if ($key === 'manageShare') ui-tab2-item-current @endif">
            <a href="/community/activity/{{$activity_id}}/manage/share"><i class="icon iconfont"></i>分享</a>
        </li>          
    </ul>
</div>