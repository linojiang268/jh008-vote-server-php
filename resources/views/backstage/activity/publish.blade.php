@extends('layouts.main')

@section('title', '发布活动')

@section('stylesheet')
    <link rel="stylesheet" href="/static/plugins/webuploader/webuploader.css"/>
    <link type="text/css" rel="stylesheet" href="/static/ueditor1_4_3-utf8-php/themes/default/css/ueditor.css">
    <link rel="stylesheet" href="/static/css/activity/publish.css"/>
@endsection

@section('content')
    <div class="step-slider mt10">
        <div class="step-slider-body publish-body">
            <!--第一步 基本信息 开始-->
            <div id="baseInfor" class="step-slider-item ui-form baseinfor">
                <form action="" id="baseInforForm">
                    <div class="ui-form-item">
                        <label for="title" class="ui-label">
                            <span class="ui-form-required">*</span>
                            活动名称 :
                        </label>
                        <div class="ui-form-item-wrap">
                            <input name="title" id="title" class="form-control act-title-input" type="text" placeholder="请输入活动名称"/>
                        </div>
                    </div>
                    <div class="ui-form-item">
                        <label for="begin_time" class="ui-label">
                            <span class="ui-form-required">*</span>
                            活动时间 :
                        </label>
                        <div class="ui-form-item-wrap clearfix">
                            <div class="ui-form-time ui-form-time-start">
                                <span class="time-tip">开始时间</span>
                                @if ($activity && $activity['status'] == 1)
                                    <span>{{$activity['begin_time']}}</span>
                                @else
                                <div>
                                    <span class="date-pick date-pick-canlendar">
                                        <i class="sprite-icon i-canlendar"></i>
                                        <input name="act_begin_date" readonly="true"  id="act_start_date" class="form-control" type="text" placeholder=""/>
                                    </span>
                                    <span class="date-pick date-pick-clock" id="actStartPick">
                                        <i class="sprite-icon i-clock"></i>
                                        <input name="act_begin_clock" readonly="true" id="act_start_clock" class="form-control" type="text" placeholder=""/>
                                    </span>
                                </div>
                                @endif
                            </div>
                            <div class="ui-form-time ui-form-time-end">
                                <span class="time-tip">结束时间</span>
                                @if ($activity && $activity['status'] == 1)
                                    <span>{{$activity['end_time']}}</span>
                                @else
                                <div>
                                    <span class="date-pick date-pick-canlendar">
                                        <i class="sprite-icon i-canlendar"></i>
                                        <input name="act_end_date" readonly="true" id="act_end_date" class="form-control" type="text" placeholder=""/>
                                    </span>
                                    <span class="date-pick date-pick-clock" id="actEndPick">
                                        <i class="sprite-icon i-clock"></i>
                                        <input name="act_end_clock" readonly="true" id="act_end_clock" class="form-control" type="text" placeholder=""/>
                                    </span>
                                </div>
                                @endif
                            </div>
                        </div>   
                    </div>
                    <div class="ui-form-item">
                        <label for="end_time" class="ui-label">
                            <span class="ui-form-required">*</span>
                            报名时间 :
                        </label>
                        <div class="ui-form-item-wrap clearfix">
                            <div class="ui-form-time ui-form-time-start">
                                <span class="time-tip">开始时间</span>
                                <div>
                                    <span class="date-pick date-pick-canlendar">
                                        <i class="sprite-icon i-canlendar"></i>
                                        <input name="enroll_start_date" readonly="true" id="enroll_start_date" class="form-control w100" type="text" placeholder=""/>
                                    </span>
                                    <span class="date-pick date-pick-clock" id="enrollStartPick">
                                        <i class="sprite-icon i-clock"></i>
                                        <input name="enroll_start_clock" readonly="true" id="enroll_start_clock" class="form-control w100" type="text" placeholder=""/>
                                    </span>
                                </div>
                            </div>
                            <div class="ui-form-time ui-form-time-end">
                                <span class="time-tip">结束时间</span>
                                <div>
                                    <span class="date-pick date-pick-canlendar">
                                        <i class="sprite-icon i-canlendar"></i>
                                        <input name="enroll_end_date" readonly="true" id="enroll_end_date" class="form-control w100" type="text" placeholder=""/>
                                    </span>
                                    <span class="date-pick date-pick-clock" id="enrollEndPick">
                                        <i class="sprite-icon i-clock"></i>
                                        <input name="enroll_end_clock" readonly="true" id="enroll_end_clock" class="form-control w100" type="text" placeholder=""/>
                                    </span>
                                </div>
                            </div>
                        </div> 
                    </div>
                    <div class="ui-form-item">
                        <label for="" class="ui-label">
                            <span class="ui-form-required">*</span>活动地点:
                        </label>
                        <div class="ui-form-item-wrap">
                            <div class="address-con">
                                <input name="address" id="address" class="form-control act-address-input" placeholder="例如：高新区香年广场" type="text"/>
                                <div class="set-nav-w">
                                    <span class="ui-form-required">*</span>
                                    <a href="javascript:;" class="button button-orange button-m set-nav-btn" id="setNavigation">设置导航</a>
                                    <span class="ui-form-item-text add-alias-tip" style="color:red;">请拖动地图点选位置，方便用户使用手机导航。</span>
                                </div>
                                
                                <div class="address-map" id="addressMapCon">
                                    <div class="active-pos-map" id="addressMap"></div>  
                                </div> 
                            </div>
                        </div>
                    </div>
                    <div class="ui-form-item">
                        <label for="enroll_limit_num" class="ui-label">
                            <span class="ui-form-required">*</span>
                            活动费用:
                        </label>
                        <div class="ui-form-item-wrap">
                            @if ($activity && $activity['status'] == 1)
                                @if ($activity['enroll_fee_type'] == 1)
                                <div class="radioSelsHistogram clearfix" id="enrollFeeType">
                                    <div class="radio-wrap sel">
                                        <label>免费</label>
                                    </div>
                                </div>
                                <div id="enrollFeeTip">
                                    <div id="enrollFeeFree">
                                        <span class="map-use-tip"><i class="icon iconfont"></i>活动不收取任何费用</span>
                                    </div>
                                </div>
                                @elseif ($activity['enroll_fee_type'] == 2)
                                <div class="radioSelsHistogram clearfix" id="enrollFeeType">
                                    <div class="radio-wrap sel">
                                        <label>AA制</label>
                                    </div>
                                </div>
                                <div id="enrollFeeTip">
                                    <div id="enrollFeeAA">
                                        <span class="map-use-tip"><i class="icon iconfont"></i>活动会产生费用，根据实际开销线下自行收取</span>
                                    </div>
                                </div>
                                @elseif ($activity['enroll_fee_type'] == 3)
                                <div class="radioSelsHistogram clearfix" id="enrollFeeType">
                                    <div class="radio-wrap sel">
                                        <label>收费</label>
                                    </div>
                                </div>
                                <div id="enrollFeeTip">
                                    <div id="enrollFeeUnfree">
                                        <span class="map-use-tip"><i class="icon iconfont"></i>活动需收取费用，报名后在线交费</span>
                                        <div class="mt10">
                                            <span>{{$activity['enroll_fee']}}</span>元(<a href="javascript:;">支付宝</a> | <a href="javascript:;">微信</a> 两种方式都支持,结算无手续费)                                        
                                        </div>
                                    </div>
                                </div>
                                @endif
                            @else
                            <div class="radioSels radioSelsHistogram clearfix" id="enrollFeeType">
                                <div class="radio-wrap sel">
                                    <input type="radio" name="enroll_fee_type" value="1" />
                                    <label>免费</label>
                                </div>
                                <div class="radio-wrap">
                                    <input type="radio" name="enroll_fee_type" value="3" />
                                    <label>收费</label>
                                </div>
                                <div class="radio-wrap">
                                    <input type="radio" name="enroll_fee_type" value="2" />
                                    <label>AA制</label>
                                </div>
                            </div>
                            <div id="enrollFeeTip" class="none">
                                <div id="enrollFeeFree">
                                    <span class="map-use-tip"><i class="icon iconfont"></i>活动不收取任何费用</span>
                                </div>
                                <div id="enrollFeeAA">
                                    <span class="map-use-tip"><i class="icon iconfont"></i>根据实际开销线下收取</span>
                                </div>
                                <div id="enrollFeeUnfree">
                                    <span class="map-use-tip"><i class="icon iconfont"></i>请填写金额，活动发布后不能修改</span>
                                    <div class="mt10">
                                        <input name="total_fee" id="total_fee" class="form-control w50 ml10" type="text">
                                        元&nbsp;&nbsp;&nbsp;<span style='color:#ccc;'>（支持&nbsp;<a style='color:#ccc;' href="javascript:;">微信</a> | <a style='color:#ccc;' href="javascript:;">支付宝</a> 两种支付方式，无手续费）</span>                                        
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="ui-form-item">
                        <label for="enroll_limit" class="ui-label">
                            <span class="ui-form-required">*</span>
                            报名条件:
                        </label>
                        <div class="ui-form-item-wrap">
                            @if ($activity && $activity['status'] == 1)
                                @if ($activity['auditing'] == 0) 
                                    <div class="radioSelsHistogram clearfix" id="auditing">
                                        <div class="radio-wrap sel">
                                            <label>不审核</label>
                                        </div>
                                    </div>
                                    <div id="auditingTip">
                                        <div id="aduitingNeed">
                                            <span class="map-use-tip"><i class="icon iconfont"></i>请设置报名人数，符合报名条件的用户按顺序先报先得。</span>
                                            <div class="mt10">
                                                报名人数：<input name="enroll_limit_num_inp" id="enroll_limit_num_inp" class="form-control w50 ml10" type="text">
                                            </div>
                                        </div>
                                    </div>
                                @elseif ($activity['auditing'] == 1)
                                    <div class="radioSelsHistogram clearfix" id="auditing">
                                        <div class="radio-wrap sel">
                                            <label>需审核</label>
                                        </div>
                                    </div>
                                    <div id="auditingTip">
                                        <div id="aduitingUnNeed">
                                            <span class="map-use-tip"><i class="icon iconfont"></i>不限制报名人数，由管理员审核报名条件后挑选。</span>
                                        </div>
                                    </div>
                                @endif
                            @else
                            <div class="radioSels radioSelsHistogram clearfix" id="auditing">
                                <div class="radio-wrap sel">
                                    <input type="radio" name="auditing" value="0" />
                                    <label>不审核</label>
                                </div>
                                <div class="radio-wrap">
                                    <input type="radio" name="auditing" value="1"/>
                                    <label>需审核</label>
                                </div>
                            </div>
                            <div id="auditingTip" class="none">
                                <div id="aduitingNeed">
                                    <span class="map-use-tip"><i class="icon iconfont"></i>请设置报名人数，符合报名条件的用户按顺序先报先得。</span>
                                    <div class="mt10">
                                        报名人数：<input name="enroll_limit_num_inp" id="enroll_limit_num_inp" class="form-control w50 ml10" type="text">
                                    </div>
                                </div>
                                <div id="aduitingUnNeed">
                                    <span class="map-use-tip"><i class="icon iconfont"></i>不限制报名人数，由管理员审核报名条件后挑选。</span>
                                </div>
                            </div>
                            @endif
                            <div id="enrollAttrsCon" class="enroll-attrs-con clearfix">
                                <h2 class="enroll-attrs-title" >请设置报名条件</h2>
                                <div class="enroll-attrs-wrap">
                                    <p class="tip1">默认条件：</p>
                                    <div id="enrollAttrs" class="enroll-attrs">
                                        
                                    </div>
                                    <a href="javascript:;" id="addAttrsBtn" class="button button-lg button-lg-pre button-orange add-attr-btn"><i class="icon iconfont"></i>添加新字段</a>
                                </div>

                                <div id="preveiwMobile" class="preview-mobile">
                                    

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ui-form-item">
                        <label for="contact" class="ui-label">
                            <span class="ui-form-required">*</span>
                            联系人 :
                        </label>
                        <div class="ui-form-item-wrap">
                            <input name="contact" id="contact" class="form-control w200" type="text" placeholder="请输入联系人"/>
                        </div>
                    </div>
                    <div class="ui-form-item">
                        <label for="telephone" class="ui-label">
                            <span class="ui-form-required">&nbsp;</span>
                            联系电话 :
                        </label>
                        <div class="ui-form-item-wrap">
                            <input name="telephone" id="telephone" class="form-control w200" type="text" placeholder="请输入联系电话"/>
                            <span class="ui-form-item-text add-alias-tip">请填写手机号或座机号（座机格式：028-85175989）</span>
                        </div>

                    </div>

                    <div class="ui-form-item">
                        <label for="call_phone" class="ui-label">
                            <span class="ui-form-required">*</span>
                            上传图片 :
                        </label>
                        <div class="ui-form-item-wrap">
                            <div class="ui-form-item-text">请上传1-4张活动相关图片，吸引用户通过手机浏览。</div>
                            <div class="ui-uploadThumbs act-thumbs clearfix mt20">
                                <div class="ui-uploadThumb" id="uploadSelector">
                                    <div class="ui-uploadThumb-link" href="javascript:;">
                                        <div class="ui-uploadThumb-wrap">
                                            <div class="ui-uploadThumb-upload">
                                                <span class="ui-uploadThumb-icon"><i class="icon iconfont"></i></span>
                                                <!-- <span class="ui-uploadThumb-text">上传活动详情照片</span>                                -->
                                            </div>
                                        </div>
                                    </div>
                                </div>      
                            </div>                            
                        </div>
                    </div>               

                    <div class="ui-form-item detail-wrap">
                        <label for="call_phone" class="ui-label">
                            <span class="ui-form-required">&nbsp;</span>
                            活动详情 :
                        </label>
                        <div class="ui-form-item-wrap">
                            <div class="w800">
                                <script id="container" name="content" type="text/plain"></script>
                                <p class="detail-error-tip"></p>
                            </div>
                        </div>
                    </div>

                    <div class="ui-form-item">
                        <label for="call_phone" class="ui-label">
                            <span class="ui-form-required">&nbsp;</span>
                        </label>
                        <div class="ui-form-item-wrap">
                            <!-- <input type="submit" class="ui-form-submit publish-form-submit" value="下一步"> -->
                            @if ($activity && $activity['status'] == 1)
                                <a href="javascript:void(0);" id="saveBtn" class="button button-blue">保存</a>
                            @else
                                <a href="javascript:void(0);" id="saveBtn" class="button button-blue">保存</a>
                                <a href="javascript:void(0);" id="publishBtn" class="button button-orange ml50">发布</a>
                            @endif                        
                        </div>
                    </div>
                </form>
            </div>
            <!--第一步 基本信息 结束-->

            <!--第五步 完成 开始-->
            <div id="finishStep" class="step-slider-item" style="display: none">
                <div class="fx_div">
                    <div class="fx_div_mag">
                        <i class="icon iconfont" style="font-size:25px;color: #4ACF98;"></i>&nbsp;
                        <span>活动已发布 , 请打开APP社团页面核对信息！</span>
                    </div>
                    <div class="fenxiang">
                        <div class="fenxiang_text"></div>
                        <div class="fenxiang_text1">分享本活动至:</div>
                        <div class="clear"></div>
                    </div>


                    <div class="lr_fx1">
                        <div class="bdsharebuttonbox">
                            <a href="#" class="bds_tsina lr_fx_xl" data-cmd="tsina" title="分享到新浪微博"></a>
                            <a href="#" class="bds_weixin lr_fx_wx" data-cmd="weixin" title="分享到微信"></a>
                            <a href="#" class="bds_qzone lr_fx_kj" data-cmd="qzone" title="分享到QQ空间"></a>
                            <a href="#" class="bds_renren lr_fx_rr" data-cmd="renren" title="分享到人人网"></a>
                            <a href="#" class="bds_sqq lr_fx_qq" data-cmd="sqq" title="分享到QQ好友"></a>
                        </div>
                    </div>
                </div>
                <div class="fx_titi">您还可以:</div>
                <div class="fs_fl clearfix" id="manageCon">
                    <a href="/community/activity/manage/share" class="fs_fl_both fx_bg"></a>
                    <a href="/community/activity/manage/sign" class="fs_fl_both qd_bg"></a>
                    <a href="/community/activity/manage/check" class="fs_fl_center sh_bg"></a>
                    <a href="/community/activity/manage/group" class="fs_fl_both fz_bg"></a>
                    <a href="/community/activity/manage/inform" class="fs_fl_both hd_bg"></a>
                    <a href="/community/activity/manage/photo/master" class="fs_fl_center zp_bg"></a>
                </div>
            </div>
            <!--第五步 完成 结束-->
        </div>
        <div class="cb"></div>
    </div>

    <div class="cb"></div>
    {!! csrf_field() !!}
@endsection

@section('javascript')
    <script type="text/javascript">
        var uploader_url = "/user/userInfo/imgUp";
        var swf = '/static/plugins/webuploader/Uploader.swf';
        var activityId = '{{ $activityId }}';
        var cityName = '{{ $cityName }}';
        var status = @if($activity && $activity['status'] == 1) 'publish' @else '' @endif ;
    </script>
    <script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=hqkEDHjAXn4VaTzt3a7RRZGP"></script>
    <script src="/static/plugins/laydate/laydate.js"></script>
    <script src="/static/plugins/webuploader/webuploader.js"></script>
    <script src="/static/ueditor1_4_3-utf8-php/ueditor.config.js"></script>
    <script src="/static/ueditor1_4_3-utf8-php/ueditor.all.js"></script>
    <script src="/static/js/activity/mapHelper.js"></script>
    <script src="/static/js/common/timePicker.js"></script>
    <script src="/static/js/activity/publish1.js"></script>
@endsection