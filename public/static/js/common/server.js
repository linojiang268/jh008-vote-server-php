(function(){

var server = K.ns('server');

var contextPath = "/community/";
/**
 * 发起请求方法
 * @param type{get|post}    请求类型
 * @param api               请求地址 url
 * @param parameters        请求发布参数
 * @param success           回调方法,(错误也会调用)
 * @param async             事后异步请求
 * @returns {*}             ajax对象
 */
var send = function (type, api, parameters, success, async) {
    typeof success == 'function' || (success = function () {
    });
    var request = $.ajax({
        url: api + "?r=" + Math.random(),
        data: parameters,
        type: type,
        dataType: 'json',
        async: true,
        cache: false,
        headers: {"Cache-Control": "no-cache", "Accept": "application/json"},
        timeout: 300000,
        success: function (data, textStatus, jqXHR) {
            success.call(this, data, textStatus, jqXHR);
        },
        error: function (jqXHR, textStatus, errorThrown) {

            //alert(jqXHR+errorThrown+textStatus);
            if (jqXHR.status == 401) {
                location.href = contextPath;
            } else {
                if (!errorThrown) {
                    return false;
                }
                var errors = {
                    101: "网络不稳定或不畅通，请检查网络设置",
                    403: "服务器禁止此操作！",
                    500: "服务器遭遇异常阻止了当前请求的执行<br/><br/><br/>"
                };

                var msg = null;
                switch (textStatus) {
                    case "timeout":
                        msg = "网络连接超时，请检查网络是否畅通！";
                        break;
                    case "error":
                        if (errors[jqXHR.status]) {
                            var data = null;
                            try {
                                data = jQuery.parseJSON(jqXHR.responseText);
                            } catch (e) {
                            }
                            if (data && data.message) {
                                msg = data.message;
                            } else {
                                msg = errors[jqXHR.status];
                            }
                        } else {
                            msg = "服务器响应异常<br/><br/>" + (jqXHR.status == 0 ? "" : jqXHR.status) + "&nbsp;" + errorThrown;
                        }
                        break;
                    case "abort":
                        msg = null;//"数据连接已被取消！";
                        break;
                    case "parsererror":
                        msg = "数据解析错误！";
                        break;
                    default:
                        msg = "出现错误:" + textStatus + "！";
                }
                if (errorThrown.code != null && errorThrown.message != null && !errors[errorThrown.code]) {
                    msg += "</br>[code:" + errorThrown.code + "][message:" + errorThrown.message + "]" + (null == errorThrown.stack ? "" : errorThrown.stack);
                }
                if (msg == null) {
                    msg = '';
                }
                success.call(this, {code: jqXHR.status, msg: msg}, textStatus, jqXHR, errorThrown);
            }
        }
    });
    return request;
}


// 登录
server.login = function (data, callback) {
    //alert(data);
    return send('post', contextPath + 'login', data, callback);
}

// 退出登录 
server.logout = function (data, callback) {
    return send('get', contextPath + 'logout', data, callback);
}

// team request create
server.createTeam = function (data, callback) {
    return send('post', contextPath + 'team/request/create', data, callback);
}

// team request update
server.updateTeam = function (data, callback) {
    return send('post', contextPath + 'team/request/update', data, callback);
}

// update/certifications
server.updateCertifications = function (data, callback) {
    return send('post', contextPath + 'team/update/certifications', data, callback);
}

// set team verify to has read
server.inspect = function (data, callback) {
    return send('post', contextPath + 'team/request/inspect', data, callback);
}

// requirement update
server.updateRequirement = function (data, callback) {
    return send('post', contextPath + 'team/requirement/update', data, callback);
}

// create team group
server.createTeamGroup = function(data, callback) {
    return send('post', contextPath + 'team/group/create', data, callback);
}

// update team group
server.updateTeamGroup = function(data, callback) {
    return send('post', contextPath + 'team/group/update', data, callback);
}

// delete team group
server.deleteTeamGroup = function(data, callback) {
    return send('post', contextPath + 'team/group/delete', data, callback);
}

// list team groups
server.listTeamGroup = function(data, callback) {
    return send('get', contextPath + 'team/group/list', data, callback);
}

// udpate member's group
server.updateMemberGroup = function(data, callback) {
    return send('post', contextPath + 'team/member/group/update', data, callback);
}

// list enrollment pending members 
server.listMemberPendingEnrollment = function(data, callback) {
    return send('get', contextPath + 'team/member/enrollment/pending', data, callback);
}

// list enrollment rejected members 
server.listMemberRejectedEnrollment = function(data, callback) {
    return send('get', contextPath + 'team/member/enrollment/rejected', data, callback);
}

// reject enrollment member
server.rejectMemberEnrollment = function(data, callback) {
    return send('post', contextPath + 'team/member/enrollment/reject', data, callback);
}

// approve enrollment member
server.approveMemberEnrollment = function(data, callback) {
    return send('post', contextPath + 'team/member/enrollment/approve', data, callback);
}

// reply the blacklist member
server.replyblacklistMemberEnrollment = function(data, callback) {
    return send('post', contextPath + 'team/member/enrollment/blacklist', $.extend({_method: 'delete'}, data), callback);
}

// blacklist enrollment member
server.blacklistMemberEnrollment = function(data, callback) {
    return send('post', contextPath + 'team/member/enrollment/blacklist', data, callback);
}

// update member
server.updateMember = function(data, callback) {
    return send('post', contextPath + 'team/member/update', data, callback);
}

// update memo 
server.updateMemo = function(data, callback) {
    return send('post', contextPath + 'team/member/enrollment/update', data, callback);
}

// update blacklist memo 
server.updateBlacklistMemo = function(data, callback) {
    return send('post', contextPath + 'team/member/enrollment/blacklist/update', data, callback);
}

// update whitelist memo 
server.updateWhitelistMemo = function(data, callback) {
    return send('post', contextPath + 'team/member/enrollment/whitelist/update', data, callback);
}

// blacklist list
server.blacklistList = function(data, callback) {
    return send('get', contextPath + 'team/member/enrollment/blacklist', data, callback);
}

// reply the whitelist member
server.replywhitelistMemberEnrollment = function(data, callback) {
    return send('post', contextPath + 'team/member/enrollment/whitelist', $.extend({_method: 'delete'}, data), callback);
}

// whitelist list
server.whitelistList = function(data, callback) {
    return send('get', contextPath + 'team/member/enrollment/whitelist', data, callback);
}

// whitelist add member
server.whitelistMemberEnrollment = function(data, callback) {
    return send('post', contextPath + 'team/member/enrollment/whitelist', data, callback);
}

// list all members
server.listMembers = function(data, callback) {
    return send('get', contextPath + 'team/member/list', data, callback);
}

// send notice 
server.sendNotice = function(data, callback) {
    return send('post', contextPath + 'team/notice/send', data, callback);
}

// send notice 
server.teamNoticeList = function(data, callback) {
    return send('get', contextPath + 'team/notices/list', data, callback);
}

// create activity
server.createActivity = function(data, callback) {
    return send('post', contextPath + 'activity/add', data, callback);
}

// update activity
server.updateActivity = function(data, callback) {
    return send('post', contextPath + 'activity/update', data, callback);
}

// publish activity
server.publishActivity = function(data, callback) {
    return send('post', contextPath + 'activity/dopublish', data, callback);
}

// get activity by id
server.getActivity = function(data, callback) {
    return send('get', contextPath + 'activity/info', data, callback);
}

// list activity data
server.listdata = function(data, callback) {
    return send('get', contextPath + 'activity/listdata', data, callback);
}

//manage group
server.getGroups = function(data, callback) {
    return send('get', contextPath + 'activity/group/list', data, callback);
}

//create groups
server.createGroups = function(data, callback) {
    return send('post', contextPath + 'activity/group/create', data, callback);
}

//setGroup
server.setGroup = function(data, callback) {
    return send('post', contextPath + 'activity/member/group/update', data, callback);
}

//getCheckInQRCodes
server.getCheckInQRCodes = function(data, callback) {
    return send('get', contextPath + 'activity/checkin/qrcode/list', data, callback);
}

//createCheckInQRCodes
server.createCheckInQRCodes = function(data, callback) {
    return send('post', contextPath + 'activity/checkin/qrcode/create', data, callback);
}

//downloadCheckInQRCodes
server.downloadCheckInQRCodes = function(data, callback) {
    return send('get', contextPath + 'activity/checkin/qrcode/download', data, callback);
}

//list managers in qrcode
server.listMembersSignQrcode = function(data, callback) {
    return send('get', contextPath + 'activity/checkin/list/all', data, callback);
}

//deleteGroup
server.deleteGroup = function(data, callback){
    return send('post', contextPath + 'activity/group/delete', data, callback);
}

// activity send notice
server.activitySendNotice = function(data, callback){
    return send('post', contextPath + 'activity/send/notice', data, callback);
}

//finance index
server.financeIndex = function(data, callback) {
    return send('get',contextPath + 'finance/enrollment/income', data, callback);
}

//finance indexDetail
server.financeIndexDetail = function(data, callback) {
    return send('get',contextPath + 'finance/enrollment/payment', data, callback);
}

// list album
server.listAlbum = function(data, callback) {
    return send('get',contextPath + 'activity/album/image/list', data, callback);
}

// approve album
server.approveAlbum = function(data, callback) {
    return send('post',contextPath + 'activity/album/image/approve', data, callback);
}

// add album
server.addAlbum = function(data, callback) {
    return send('post',contextPath + 'activity/album/image/add', data, callback);
}

// remove album
server.removeAlbum = function(data, callback) {
    return send('post',contextPath + 'activity/album/image/remove', data, callback);
}

// getTeamNews
server.getTeamNews = function(data, callback) {
    return send('get',contextPath + 'news', data, callback);
}

// getNewsDetail
server.getNewsDetail = function(data, callback) {
    return send('get',contextPath + 'news', data, callback);
}

// publishNews
server.publishNews = function(data, callback) {
    return send('post',contextPath + 'news', data, callback);
}

// deleteNews
server.deleteNews = function(data, newsId, callback) {
    return send('post',contextPath + 'news/'+newsId, $.extend({_method: 'delete'}, data), callback);
}

// updateNews
server.updateNews = function(data, newsId, callback) {
    return send('post',contextPath + 'news/'+newsId, $.extend({_method: 'put'}, data), callback);
}

// getApplicantsList
server.getApplicantsList = function(data, callback) {
    return send('get',contextPath + 'activity/applicant/list', data, callback);
}

// approveApplicant
server.approveApplicant = function(data, callback) {
    return send('post',contextPath + 'activity/applicant/approve', data, callback);
}

// refuseApplicant
server.refuseApplicant = function(data, callback) {
    return send('post',contextPath + 'activity/applicant/refuse', data, callback);
}

// enforcePayment
server.enforcePayment = function(data, callback) {
    return send('post',contextPath + 'activity/applicant/payment/enforce', data, callback);
}

// searchTeamActivitiesByActivityTime
server.searchTeamActivitiesByActivityTime = function(data, callback) {
    return send('post',contextPath + 'activity/search/time', data, callback);
}

// deleteActivityById
server.deleteActivityById = function(data, callback) {
    return send('post',contextPath + 'activity/delete', data, callback);
}

// activity plan list
server.planList = function(data, callback) {
    return send('get',contextPath + 'activity/plan/list', data, callback);
}

// update plan list
server.addPlanList = function(data, callback) {
    return send('post',contextPath + 'activity/plan/add', data, callback);
}

// get activify file list
server.activityFileList = function(data, callback) {
    return send('get',contextPath + 'activity/file/list', data, callback);
}

// delete activity file
server.deleteActivityFile = function(data, callback) {
    return send('post',contextPath + 'activity/file/remove', data, callback);
}

//addSingleVip
server.addSingleVip = function(data, callback) {
    return send('post',contextPath + 'activity/member/add', data, callback);
}

//importActivityMembers
server.importActivityMembers = function(data, callback) {
    return send('post',contextPath + 'activity/import/members', data, callback);
}

})()