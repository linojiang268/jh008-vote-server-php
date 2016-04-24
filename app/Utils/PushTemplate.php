<?php
namespace Jihe\Utils;

abstract class PushTemplate
{
    //=============================================================================================
    //                                      平台系统消息
    //=============================================================================================
    // 版本升级
    const VERSION_UPGRADE = "有新版本，请点击更新。";
    
    // 自定义系统通知
    const CUSTOM_NOTICE = "";
    
    //=============================================================================================
    //                                     用户：账号消息
    //=============================================================================================
    // 短信验证码
    const SMS_VERIFICATION_CODE = "";
    
    // 用户注册成功
//    const USER_REGISTED_SUCCESSFUL = "";
    
    // 用户被修改
    const PASSWORD_CHANGED = "";

    // 用户被踢掉
    const USER_KICKED = "您的帐号在另一地点登录，您被迫下线。如果这不是您本人的操作，那么您的密码很可能已泄露。";
    //=============================================================================================
    //                                     用户：社团相关
    //=============================================================================================
    // 被导入白名单
    const IMPORTED_INTO_WHITE_LIST = "您被%s邀请加入“%s”。";
    
    // 社团成员加入申请被通过
    const TEAM_MEMBER_ENROLLMENT_REQUEST_APPROVED = "";
    // 社团成员加入申请被拒绝
    const TEAM_MEMBER_ENROLLMENT_REQUEST_REJECTED = "";
    // 社团成员加入申请被拉黑
    const TEAM_MEMBER_ENROLLMENT_REQUEST_BE_BLACKLISTING = "";
    
    // 社团成员被踢出
    const TEAM_MEMBER_KICKED_OUT = "";
    
    // 社团自定义通知
    const TEAM_CUSTOM_NOTICE = "";
    //=============================================================================================
    //                                     用户：活动相关
    //=============================================================================================
    // H5活动报名成功（活动无需审核且无需支付）
    const ACTIVITY_MEMBER_ENROLLED_SUCCESSFUL_FORM_WEB = "";
    
    // H5活动报名申请等待支付（活动无需审核、需支付，仅在APP中支付）
    const ACTIVITY_MEMBER_ENROLLMENT_REQUEST_WAITING_FOR_PAYMENT_FORM_WEB = "";
    
    // H5活动报名申请审核中（活动需审核，审核结果仅在APP中查看）
    const ACTIVITY_MEMBER_ENROLLMENT_REQUEST_PENDING_FORM_WEB = "";
    // H5活动报名成功（活动需审核、无需支付、已审核通过）
    const ACTIVITY_MEMBER_ENROLLMENT_REQUEST_APPROVED_AND_ENROLLED_SUCCESSFUL_FORM_WEB = "";
    // H5活动报名申请通过，等待支付（活动需审核、审核通过、待支付，仅在APP中支付）
    const ACTIVITY_MEMBER_ENROLLED_SUCCESSFUL_AND_WAITINT_FOR_PAYMENT_FORM_WEB = "";
    
    // 活动报名成功（活动无需审核且无需支付）
    const ACTIVITY_MEMBER_ENROLLED_SUCCESSFUL = "";
    
    // 活动报名成功（活动需审核、无需支付、已审核通过）
    const ACTIVITY_MEMBER_ENROLLMENT_REQUEST_APPROVED_AND_ENROLLED_SUCCESSFUL = "";
    // 活动报名申请通过，等待支付（活动需审核、审核通过、待支付，仅在APP中支付）
    const ACTIVITY_MEMBER_ENROLLED_SUCCESSFUL_AND_WAITINT_FOR_PAYMENT = "";
    // 活动报名申请被拒绝（H5和APP）
    const ACTIVITY_MEMBER_ENROLLEDMENT_REQUEST_REJECTED = "";

    // 第一次申请参加活动
    const ACTIVITY_MEMBER_ENROLLMENT_REQUEST_FOR_THE_FIRST_TIME = "亲爱的用户%s，点击活动日历可以查看过往活动。";

    // 统计1天后即将开始的活动
    const ACTIVITY_ABOUT_TO_BEGIN = "";
    
    // 自己参加的社团发布了新活动
    const ACTIVITY_PUBLISHED_BY_ENROLLED_TEAM = "\"%s\"活动已发布，快去参加吧。";
    // 曾经参加过的活动的社团发布了新活动
    const ACTIVITY_PUBLISHED_BY_ACTIVITY_EVER_ENROLLED = "\"%s\"活动已发布，快去参加吧。";
    
    // 活动相册有更新
    // const ACTIVITY_ALBUM_UPDATED = "“%s”活动相册有更新，快来围观";
    
    // 活动成员签到了
    // const ACTIVITY_MEMBER_CHECKIN = "“%s”活动已经有人在现场完成签到";
    
    // 自定义活动通知
    const ACTIVITY_NOTICE = "";
    //=============================================================================================
    //                                     团长：社团相关
    //=============================================================================================
    // 社团创建申请审核中
    const TEAM_ENROLLMENT_REQUEST_PENDING = "";
    // 社团创建申请被通过
    const TEAM_ENROLLMENT_REQUEST_APPROVED = "";
    // 社团创建申请被拒绝
    const TEAM_ENROLLMENT_REQUEST_REJECTED = "";
    
    // 社团资料修改申请审核中
    const TEAM_UPDATE_REQUEST_PENDING = "";
    // 社团资料修改申请被通过
    const TEAM_UPDATE_REQUEST_APPROVED = "";
    // 社团资料修改申请被拒绝
    const TEAM_UPDATE_REQUEST_REJECTED = "";
    
    // 社团认证申请审核中
    const TEAM_CERTIFICATION_REQUEST_PENDING = "";
    // 社团认证申请被通过
    const TEAM_CERTIFICATION_REQUEST_APPROVED = "";
    // 社团认证申请被拒绝
    const TEAM_CERTIFICATION_REQUEST_REJECTED = "";
    
    // 统计待审核的社团成员加入申请
    const SOME_TEAM_MEMBER_ENROLLMENT_REQUEST_PENDING = "";
    //=============================================================================================
    //                                     团长：活动相关
    //=============================================================================================
    // 自己发布的活动
    const ACTIVITY_PUBLISHED_BY_SELF_TEAM = "“%s”活动已经发布成功，请预览。";
    
    // 统计待审核的活动报名申请
    // const SOME_ACTIVITY_MEMBER_ENROLLMENT_REQUEST_PENDING = "";
    // 统计新报名的活动成员
    const SOME_NEW_ACTIVITY_MEMBER_ENROLLED = "";
    
    // 提醒自己的活动即将开始
    const SELF_ACTIVITY_ABOUT_TO_BEGIN = "";
    
    // 统计待审核的用户上传的活动相册图片
    const SOME_ACTIVITY_MEMBER_ALBUM_IMAGE_REQUEST_PENDING = "";
    //=============================================================================================
    
    /**
     * general message use template of content with variable parameters, 
     *                                      for example:
     *                                          there is: const TEMPLATE = "这是第%d条消息：%s。";
     *                                          call:     PushTemplate::generalMessage(PushTemplat::TEMPLATE, 1, 'notice message');
     *                                          return:   "这是第1条消息：notice message。"
     * 
     * @param $template   template of content
     * @param variable parameters need to filled
     */
    public static function generalMessage() 
    {
        return call_user_func_array('sprintf', func_get_args());
    }
}
