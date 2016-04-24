<?php
namespace Jihe\Utils;

abstract class SmsTemplate
{
    //=============================================================================================
    //                                      平台系统消息
    //=============================================================================================
    // 版本升级
    const VERSION_UPGRADE = "";
    
    // 自定义系统通知
    const CUSTOM_NOTICE = "";
    
    //=============================================================================================
    //                                     用户：账号消息
    //=============================================================================================
    // 短信验证码
    const SMS_VERIFICATION_CODE = "本次验证码%s，%s分钟有效。如非本人操作，请忽略。";
    
    // 用户注册成功
    // const USER_REGISTED_SUCCESSFUL = "亲爱的用户，恭喜您注册成功。集合兴趣，争当团长，客服热线400-876-1176。";
    
    // 用户密码被修改
    const PASSWORD_CHANGED = "亲爱的用户，您已经成功修改密码，如非本人操作请联系客服：400-876-1176。";
    //=============================================================================================
    //                                     用户：社团相关
    //=============================================================================================
    // 被导入白名单
    const IMPORTED_INTO_WHITE_LIST = "“%s”负责人%s邀请您加入集合APP。下载地址：d.jh008.com。";
    
    // 社团成员加入申请被通过
    const TEAM_MEMBER_ENROLLMENT_REQUEST_APPROVED = "恭喜您通过了“%s”的加入申请，快去查看社团的最新动态吧！";
    // 社团成员加入申请被拒绝
    const TEAM_MEMBER_ENROLLMENT_REQUEST_REJECTED = "很抱歉！您未通过“%s”的加入申请，您可核对信息后再次提交或与社团负责人联系。";
    // 社团成员加入申请被拉黑
    const TEAM_MEMBER_ENROLLMENT_REQUEST_BE_BLACKLISTING = "很抱歉！“%s”没有通过您的加入申请，您可以加入其它感兴趣的社团。";
    
    // 社团成员被踢出
    const TEAM_MEMBER_KICKED_OUT = "很抱歉！“%s”已将您移出社团，您可以加入其它感兴趣的社团。";
    
    // 社团自定义通知
    const TEAM_CUSTOM_NOTICE = "";
    //=============================================================================================
    //                                     用户：活动相关
    //=============================================================================================
    // H5报名过程中，如果创建了新用户，则填充此模板
    const USER_REGISTED_SUCCESSFUL_FROM_WEB = "帐号%s****%s，初始密码：%s。";
    
    // H5活动报名成功（活动无需审核且无需支付）如果创建新用户，末尾%s拼接账号信息，老用户则拼接“”
    const ACTIVITY_MEMBER_ENROLLED_SUCCESSFUL_FORM_WEB = "恭喜您成功报名“%s”活动，请下载集合APP方便签到：http://d.jh008.com。%s";
    
    // H5活动报名申请等待支付（活动无需审核、需支付，仅在APP中支付）如果创建新用户，末尾%s拼接账号信息，老用户则拼接“”
    const ACTIVITY_MEMBER_ENROLLMENT_REQUEST_WAITING_FOR_PAYMENT_FORM_WEB = "报名信息已提交，请在30分钟内下载集合APP，登录后完成支付：http://d.jh008.com。%s";
    
    // H5活动报名申请审核中（活动需审核，审核结果仅在APP中查看）如果创建新用户，末尾%s拼接账号信息，老用户则拼接“”
    const ACTIVITY_MEMBER_ENROLLMENT_REQUEST_PENDING_FORM_WEB = "报名信息已提交，请等待审核。下载集合APP可实时查看审核进度：http://d.jh008.com。%s";
    // H5活动报名成功（活动需审核、无需支付、已审核通过）
    const ACTIVITY_MEMBER_ENROLLMENT_REQUEST_APPROVED_AND_ENROLLED_SUCCESSFUL_FORM_WEB = "恭喜您被“%s”活动选中，报名成功。请尽快下载集合APP，登录后查看活动手册：http://d.jh008.com。";
    // H5活动报名申请通过，等待支付（活动需审核、审核通过、待支付，仅在APP中支付）
    const ACTIVITY_MEMBER_ENROLLED_SUCCESSFUL_AND_WAITINT_FOR_PAYMENT_FORM_WEB = "恭喜您被“%s”活动选中，请尽快下载集合APP，登录后完成支付：http://d.jh008.com。";
    
    // 活动报名成功（活动无需审核且无需支付，或者无需审核、需支付且支付成功）
    // const ACTIVITY_MEMBER_ENROLLED_SUCCESSFUL = "恭喜您成功报名“%s”活动，客服热线400-876-1176。";
    
    // 活动报名成功（活动需审核、无需支付、已审核通过）
    const ACTIVITY_MEMBER_ENROLLMENT_REQUEST_APPROVED_AND_ENROLLED_SUCCESSFUL = "恭喜您被“%s”活动选中，报名成功。请尽快登录查看活动手册。";
    // 活动报名申请通过，等待支付（活动需审核、审核通过、待支付）
    const ACTIVITY_MEMBER_ENROLLED_SUCCESSFUL_AND_WAITINT_FOR_PAYMENT = "恭喜您被“%s”活动选中，请尽快登录完成支付。";
    // 活动报名申请被拒绝（H5和APP）
    const ACTIVITY_MEMBER_ENROLLEDMENT_REQUEST_REJECTED = "很遗憾！您未通过“%s”活动的报名，您可以使用集合APP报名其它感兴趣的活动。";

    // 第一次申请参加活动
    const ACTIVITY_MEMBER_ENROLLMENT_REQUEST_FOR_THE_FIRST_TIME = "";
    
    // 统计1天后即将开始的活动
    const ACTIVITY_ABOUT_TO_BEGIN = "“%s”提醒：您参加的“%s”活动将于明天开始，请准时参加并使用集合APP签到：http://d.jh008.com。";
    
    // 自己参加的社团发布了新活动
    const ACTIVITY_PUBLISHED_BY_ENROLLED_TEAM = "";
    // 曾经参加过的活动的社团发布了新活动
    const ACTIVITY_PUBLISHED_BY_ACTIVITY_EVER_ENROLLED = "";
    
    // 活动相册有更新
    // const ACTIVITY_ALBUM_UPDATED = "";
    
    // 活动成员签到了
    // const ACTIVITY_MEMBER_CHECKIN = "";
    
    // 自定义活动通知
    const ACTIVITY_NOTICE = "";
    //=============================================================================================
    //                                     团长：社团相关
    //=============================================================================================
    // 社团创建申请审核中
    const TEAM_ENROLLMENT_REQUEST_PENDING = "尊敬的用户您好，社团创建的申请已经提交，我们将尽快审核。";
    // 社团创建申请被通过
    const TEAM_ENROLLMENT_REQUEST_APPROVED = "恭喜您！“%s”的创建申请已经通过审核，赶快登录发布活动吧！";
    // 社团创建申请被拒绝
    const TEAM_ENROLLMENT_REQUEST_REJECTED = "非常抱歉，“%s”的创建申请未能通过，如有疑问请拨打客服热线400-876-1176。";
    
    // 社团资料修改申请审核中
    const TEAM_UPDATE_REQUEST_PENDING = "尊敬的团长您好，社团资料修改的申请已经提交，我们将尽快审核。";
    // 社团资料修改申请被通过
    const TEAM_UPDATE_REQUEST_APPROVED = "恭喜您！“%s”资料修改的申请已经通过。";
    // 社团资料修改申请被拒绝
    const TEAM_UPDATE_REQUEST_REJECTED = "非常抱歉，“%s”资料修改的申请未能通过，如有疑问请拨打客服热线400-876-1176。";
    
    // 社团认证申请审核中
    const TEAM_CERTIFICATION_REQUEST_PENDING = "尊敬的团长您好，社团认证的申请已经提交，我们将尽快审核。";
    // 社团认证申请被通过
    const TEAM_CERTIFICATION_REQUEST_APPROVED = "恭喜您！“%s”的认证申请已经通过审核，快使用白名单功能召集您的成员吧！";
    // 社团认证申请被拒绝
    const TEAM_CERTIFICATION_REQUEST_REJECTED = "非常抱歉，“%s”的认证申请未能通过，如有疑问请拨打客服热线400-876-1176。";
    
    // 统计待审核的社团成员加入申请
    const SOME_TEAM_MEMBER_ENROLLMENT_REQUEST_PENDING = "尊敬的团长您好！有%d人申请加入“%s”，请尽快审核。";
    //=============================================================================================
    //                                     团长：活动相关
    //=============================================================================================
    // 自己发布的活动
    const ACTIVITY_PUBLISHED_BY_SELF_TEAM = "";
    
    // 统计待审核的活动报名申请
    // const SOME_ACTIVITY_MEMBER_ENROLLMENT_REQUEST_PENDING = "您的活动“%s”有%d人正在等待您的审核，请尽快处理。";
    // 统计新报名的活动成员
    const SOME_NEW_ACTIVITY_MEMBER_ENROLLED = "尊敬的团长您好！有%d人报名“%s”活动，请尽快设置活动手册。";
    
    // 提醒自己的活动即将开始
    const SELF_ACTIVITY_ABOUT_TO_BEGIN = "尊敬的团长，集合APP提醒您：您的“%s”活动即将开始，请及时做好准备工作。";
    
    // 统计待审核的用户上传的活动相册图片
    const SOME_ACTIVITY_MEMBER_ALBUM_IMAGE_REQUEST_PENDING = "“%s”活动的用户相册有新照片上传，请尽快审核。";
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
