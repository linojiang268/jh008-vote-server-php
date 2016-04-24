(function(){

var server = K.ns('server');

var contextPath = "/admin/";
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
};


// login
server.login = function (data, callback) {
    return send('post', contextPath + 'login', data, callback);
};

// logout
/*server.logout = function (data, callback) {
    return send('get', contextPath + 'logout', data, callback);
}*/

// accountsList
server.accountsList = function (data, callback) {
    return send('get', contextPath + 'user/list', data, callback);
};

// reset account password
server.resetPassword = function (data, callback) {
    return send('post', contextPath + 'user/password/reset', data, callback);
};

// create account
server.createAccount = function (data, callback) {
    return send('post', contextPath + 'user/create', data, callback);
};

// delete account 
server.removeAccount = function (data, callback) {
    return send('post', contextPath + 'user/remove', $.extend({_method: 'delete'}, data), callback);
};

// update self password
server.updatePassword = function (data, callback) {
    return send('post', contextPath + 'user/password/update', data, callback);
};

// tags list
server.tags = function (data, callback) {
    return send('get', contextPath + 'tag/list', data, callback);
};

// team request list
server.teamRequests = function (data, callback) {
    return send('get', contextPath + 'team/request/list', data, callback);
};

// team request appreove
server.teamApprove = function (data, callback) {
    return send('post', contextPath + 'team/request/enrollment/approve', data, callback);
};

// team request reject
server.teamReject = function(data, callback) {
    return send('post', contextPath + 'team/request/enrollment/reject', data, callback);
};

// team update request appreove
server.teamUpdateRequestApprove = function (data, callback) {
    return send('post', contextPath + 'team/request/update/approve', data, callback);
};

// team update request reject
server.teamUpdateRequestReject = function(data, callback) {
    return send('post', contextPath + 'team/request/update/reject', data, callback);
};

// team certification list
server.teamCertification = function(data, callback) {
    return send('get', contextPath + 'team/certification/list', data, callback);
};

// listIncome
server.listIncome = function(data, callback) {
    return send('get', contextPath + 'accountant/income/list', data, callback);
};

// listIncome
server.doIncomeTransfer = function(data, callback) {
    return send('post', contextPath + 'accountant/income/transfer/do', data, callback);
};

// listIncome
server.confirmIncomeTransfer = function(data, callback) {
    return send('post', contextPath + 'accountant/income/transfer/confirm', data, callback);
};

// listIncome
server.finishIncomeTransfer = function(data, callback) {
    return send('post', contextPath + 'accountant/income/transfer/finish', data, callback);
};

server.teamCertificationDetail = function(data, callback) {
    return send('get', contextPath + 'team/certification/info/list', data, callback);
};

// team certification approve
server.teamCertificationApprove = function(data, callback) {
    return send('post', contextPath + 'team/certification/approve', data, callback);
};

// team certification reject
server.teamCertificationReject = function(data, callback) {
    return send('post', contextPath + 'team/certification/reject', data, callback);
};

// team list
server.teamList = function(data, callback) {
    return send('get', contextPath + 'team/list', data, callback);
};

// team set tag
server.teamSetTag = function(data, callback) {
    return send('post', contextPath + 'team/tag', data, callback);
};

// team freeze
server.teamForbidden = function(data, callback) {
    return send('post', contextPath + 'team/forbidden', data, callback);
};

// team forbidden
server.teamCancelForbidden = function(data, callback) {
    return send('post', contextPath + 'team/forbidden/cancel', data, callback);
};

// activity list
server.activityList = function(data, callback) {
    return send('post', contextPath + 'activity/list', data, callback);
};

// activity delete
server.activityDelete = function(data, callback) {
    return send('post', contextPath + 'activity/delete', data, callback);
};

// activity restore
server.activityRestore = function (data, callback) {
    return send('post', contextPath + 'activity/restore', data, callback);
};

// activity settags
server.activitySettags = function (data, callback) {
    return send('post', contextPath + 'activity/settags', data, callback);
};

// activity detail
server.activityDetail = function (data, callback) {
    return send('get', contextPath + 'activity/detail', data, callback);
};

// send notice
server.sendNotice = function (data, callback) {
    return send('post', contextPath + 'notice/send', data, callback);
};

// notice list
server.noticeList = function (data, callback) {
    return send('get', contextPath + 'notice/list', data, callback);
};

// upgrade push
server.upgradePush = function (data, callback) {
    return send('post', contextPath + 'upgrade/push', data, callback);
};

})()