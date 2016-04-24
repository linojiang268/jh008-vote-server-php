@extends('layouts.main')

@section('title', '报名审核')

@section('stylesheet')
    <link rel="stylesheet" href="/static/plugins/webuploader/webuploader.css"/>
    <link rel="stylesheet" href="/static/css/activity/activityCheck.css"/>
@endsection

@section('content')
 <!--     <div class="h_1" >
        <div class="h_2"></div>
        <div class="h_3">齐丽萍决定是否合适的客户发送的空间发挥</div>
        <div class="clear" ></div>
     </div> -->
     <div class="m_1">
        @include('layouts.activityManagerNav')
        <div class="lr">
            <div class="team-opration-entry mt15">
                <div class="btn-group">
                    <a href="javascript:;" id="dbm" class="vip-import-btn button button-grey w90">代报名</a>
                    <a href="/community/activity/member/export?activity={{$activity_id}}" class="vip-import-btn button button-grey w90">导出报名信息</a>
                </div>
                <div class="input-entry">
                    <span class="tri-angle"><i class="tri-angle-inner"></i></span>
                    <span id="close" class="close">x</span>
                    <div class="ui-form input-form-w">
                        <form id="dbm-form" action="">
                            <!-- <div class="ui-form-item">
                                <label for="name" class="ui-label">姓名:</label>
                                <input type="text" id="name" class="form-control">
                            </div>
                            <div class="ui-form-item">
                                <label for="phone" class="ui-label">电话:</label>
                                <input type="text" id="phone" class="form-control">
                            </div> -->
                            <div class="ui-form-item form-btns tr">
                                <a href="javascript:;" id="dbm-form-submit" class="dbm-form-submit button button-grey">提交</a>
                            </div>
                        </form>
                    </div>
                    <div class="ui-form import-form-w">
                        <h3 class="des">您也可以批量导入报名数据</h3>
                        <form id="import-form" enctype="multipart/form-data" action="/community/activity/import/members" method="post">
                            <div class="ui-form-item">
                                <label for="excel" class="ui-label">选择文件:</label>
                                <!-- <input type="file" id="excel" class="form-control"> -->
                                <div class="file-upload-w">
                                    <a id="excel" class="select-file-btn button button-grey">选择文件</a>
                                    <span class="upload-message"></span>
                                </div>
                            </div> 
                            <div class="ui-form-item form-btns">
                                <a href="/community/activity/import/members/template?activity={{$activity_id}}" class="button button-grey">下载报名模板</a>
                                <a href="javascript:;" data-text="批量导入" id="batch-import-btn" class="batch-import-btn button button-grey">批量导入</a>
                            </div>   
                        </form>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
            <div class="b-table">
                <div  class="xxk mt15">
                    <div class="wsh hide" data-table="wsh_lr">待审核</div>
                    <div class="ysh hide" data-table="ysh_lr">待缴费</div>
                    <div class="ybm on" data-table="ybm_lr">已报名</div>
                    <div class="yjj hide" data-table="yjj_lr">已拒绝</div>
                </div>
                <div class="neiron mt15">
                    <!--未审核-->
                    <div class="wsh_lr hide">
                       <div class="k-table-container" id="tableCon1">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>序号</th>
                                        <th>姓名</th>
                                        <th>电话</th>
                                        <th>自定义信息</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody >
                                </tbody>
                            </table>
                        </div>
                    </div>
                     <!--待缴费-->
                     <div class="ysh_lr hide">
                         <div class="k-table-container" id="tableCon2">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>序号</th>
                                        <th>姓名</th>
                                        <th>电话</th>
                                        <th>自定义信息</th>
                                        <th>状态</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody style="">
                                </tbody>
                            </table>
                        </div>
                     </div>
                     <!--已报名-->
                     <div class="ybm_lr">
                         <div class="k-table-container" id="tableCon3">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>序号</th>
                                        <th>姓名</th>
                                        <th>电话</th>
                                        <th>自定义信息</th>
                                        <th>操作</th>
                                    </tr>

                                </thead>
                                <tbody style="">
                                </tbody>
                            </table>
                        </div>
                     </div>
                     <!--已拒绝-->
                     <div class="yjj_lr hide">
                        <div class="k-table-container" id="tableCon4">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>序号</th>
                                        <th>姓名</th>
                                        <th>电话</th>
                                        <th>自定义信息</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody style="">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="clear" ></div>
            </div>
            
        </div>
        <div style="height: 30px;"></div>
     </div>
     <input type="hidden" value='{{$activity_id}}' id="activity_id">
     {!! csrf_field() !!}
@endsection

@section('javascript')
    <script type="text/javascript">
        var activity_id = {{$activity_id}};
    </script>
    <script src="/static/plugins/webuploader/webuploader.js"></script>
    <script src="/static/js/activity/activityCheck.js"></script>
@endsection