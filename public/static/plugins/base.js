(function(){
/**
 * 命名空间声明
 */

var base = K.ns('base');


//接口根目录
var contextPath = '/';
(function (window, document, undefined) {
    var js = document.scripts, script = js[js.length - 1], jsPath = script.src;
    var jsDirname = jsPath.substring(0, jsPath.lastIndexOf("/"));
    var staticDirname = jsDirname.substring(0, jsDirname.lastIndexOf("/"));
    if (window != window.parent) {
        //falert("adfadfs");return ;
        //设置title
        window.parent.document.title = window.document.title;

        if (window.parent.jQuery) {

            //设置iframe页面大小
            var _browser = window.parent.jQuery('#browser');
            var _menu_warp = window.parent.jQuery('.menu-warp');
            //window.top.jQuery('#browser')
            if (_browser.size() > 0) {
                var _last_height = 0;
                setInterval(function () {
                    var ih = $('html,body').innerHeight();
                    var mwh = _menu_warp.height();
                    ih > mwh || (ih = mwh);
                    //console.log(ih,_last_height);
                    if (ih != _last_height) {
                        _last_height = ih;
                        _browser.animate({'height': _last_height}, 150);
                    }

                }, 250);
            }

        }
        if (window.parent.layer) {
            window.player = window.parent.layer; //获取到layer对话框
        }
    }


})(window, document, undefined);


//------- 常用函数声明区域 --------------------------------------------------------

/**
 * 随机url
 * @param url
 * @returns {string}
 */
base.randomUrl = function (url) {
    var lc = (((url + "").indexOf('?') > -1) ? '&' : '?');
    url = url + lc + '_=' + Math.random();
    lc = null;
    return url;
}

/**
 * 简单的原型重载方法
 * @args 参数无限制.
 * @type {Function}
 */
base.extend = function () {
    //获取传递给函数的参数
    var args = arguments;
    var len = args.length;
    var extend_obj = {};
    if (len > 0) {
        extend_obj = args[0];
        if (len > 1) {
            for (var i = 1; i < len; i++) {
                var o = args[i];
                if (o)
                    for (var key in o) {
                        extend_obj[key] = o[key];
                    }
            }
        }
    }
    return extend_obj;
}


/**
 * 执行函数方法
 * @type {Function}
 */
base.call = function (callback, thisObj, args) {
    if (!args) args = [];
    if (typeof callback == 'function') {
        return callback.apply(thisObj || this, args);
    }
    return this;
}

/*
 window.addEventListener('load', function () {
 if (jQuery) {
 //公共功能代码
 jQuery(function () {
 jQuery('.logout').click(function () {
 base.server('loginout', {}, function (ret) {
 //console.log(ret);
 if (ret.code == '0') {
 window.top.location.href = '/org_v2/default/login';
 }
 })
 });
 });
 }

 });
 */
function win_load() {
    if (jQuery) {
        //公共功能代码
        jQuery(function () {
            jQuery('.logout').click(function () {
                base.server('loginout', {}, function (ret) {
                    //console.log(ret);
                    if (ret.code == '0') {
                        window.top.location.href = '/org_v2/default/login';
                    }
                })
            });
        });
    }
}

if (window.addEventListener) {
    window.addEventListener('load', win_load);
} else {
    window.attachEvent('onload', win_load);
}

/**
 * 时间格式化
 * @param format_str  格式化字符串(不区分大小写)
 * YYYY         完整的年份
 * MM           月份(包含0)
 * DD           日期(包含0)
 * HH           时间(包含0)
 * II           分钟(包含0)
 * SS           秒(包含0)
 * y            两位年份
 * m            当前月份
 * d            当前日期
 * h            当前时间
 * i            当前分
 * s            当前秒
 * @returns {string}
 */
Date.prototype.format = function (format_str) {
    var fullYear = this.getFullYear();//完整年份
    var month = this.getMonth();//月份
    var date = this.getDate();//日期
    var hours = this.getHours();//小时
    var minutes = this.getMinutes();//分钟
    var seconds = this.getSeconds();//秒数
    var fs = format_str + "";
    fs = fs.replace(/y{4}/i, fullYear);
    fs = fs.replace(/m{2}/i, month < 9 ? '0' + (month + 1) : month + 1);
    fs = fs.replace(/d{2}/i, date < 9 ? '0' + (date + 1) : date + 1);
    fs = fs.replace(/h{2}/i, hours < 9 ? '0' + (hours + 1) : hours + 1);
    fs = fs.replace(/i{2}/i, minutes < 9 ? '0' + (minutes + 1) : minutes + 1);
    fs = fs.replace(/s{2}/i, seconds < 9 ? '0' + (seconds + 1) : seconds + 1);
    fs = fs.replace(/s/i, seconds);
    fs = fs.replace(/i/i, minutes);
    fs = fs.replace(/h/i, hours);
    fs = fs.replace(/d/i, date);
    fs = fs.replace(/m/i, month + 1);
    fs = fs.replace(/y/i, (fullYear + "").substring(2));
    fullYear = null;
    month = null;
    date = null;
    hours = null;
    minutes = null;
    seconds = null;
    return fs;
}


window.Uploader = base.Uploader = function (options) {
    options = base.extend({}, options);
}


})()