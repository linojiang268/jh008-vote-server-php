(function(){

var Observe = K.Observe;

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

// validator 扩展验证手机号码方法
jQuery.validator.addMethod("isPhone", function(value, element, param) {
    var myreg = /^(((17[0-9]{1})|(13[0-9]{1})|(15[0-9]{1})|(18[0-9]{1}))+\d{8})$/; 
    if(!myreg.test(value)) { 
        return false; 
    } 
    return this.optional(element) || param;   
}, $.validator.format("请输入有效的手机号码！"));

// validator 扩展验证手机号码或者座机号
jQuery.validator.addMethod("telphone", function(value, element, param) {
    var mobile_reg = /^(((17[0-9]{1})|(13[0-9]{1})|(15[0-9]{1})|(18[0-9]{1}))+\d{8})$/,
        tel_reg = /^0\d{2,3}\-\d{7,8}$/;
    if(!value) { 
        return false; 
    } 
    if (!mobile_reg.test(value) && !tel_reg.test(value)) {
        return false;
    }
    return this.optional(element) || param;   
}, $.validator.format("请输入有效的手机号码或座机号码！"));

// validator 扩展验证时间方法
jQuery.validator.addMethod("isTime", function(value, element, param) {
    var length = value.length;
    var myreg = /^[1-9]\d{3}-\d{2}-\d{2}\s{1}\d{2}\:\d{2}\:\d{2}$/; 
    if(!myreg.test(value)) { 
        return false; 
    } 
    return this.optional(element) || param;   
}, $.validator.format("正确的时间格式为xxxx-xx-xx xx:xx:xx"));

K.LIST_SIZE = 20;

// 是否手机号
K.isPhone = function(value) {
    if (!/^(((17[0-9]{1})|(13[0-9]{1})|(15[0-9]{1})|(18[0-9]{1}))+\d{8})$/.test(value)) {
        return false;
    }
    return true;
}

// 是否座机号
K.isTel = function(value) {
    if (!/^0\d{2,3}\-\d{7,8}$/.test(value)) {
        return false;
    }
    return true;
}

$.datepicker.regional['zh-CN'] = {   
    clearText: '清除',   
    clearStatus: '清除已选日期',   
    closeText: '关闭',   
    closeStatus: '不改变当前选择',   
    prevText: '<上月',   
    prevStatus: '显示上月',   
    prevBigText: '<<',   
    prevBigStatus: '显示上一年',   
    nextText: '下月>',   
    nextStatus: '显示下月',   
    nextBigText: '>>',   
    nextBigStatus: '显示下一年',   
    currentText: '今天',   
    currentStatus: '显示本月',   
    monthNames: ['一月','二月','三月','四月','五月','六月', '七月','八月','九月','十月','十一月','十二月'],   
    monthNamesShort: ['一','二','三','四','五','六', '七','八','九','十','十一','十二'],   
    monthStatus: '选择月份',   
    yearStatus: '选择年份',   
    weekHeader: '周',   
    weekStatus: '年内周次',   
    dayNames: ['星期日','星期一','星期二','星期三','星期四','星期五','星期六'],   
    dayNamesShort: ['周日','周一','周二','周三','周四','周五','周六'],   
    dayNamesMin: ['日','一','二','三','四','五','六'],   
    dayStatus: '设置 DD 为一周起始',   
    dateStatus: '选择 m月 d日, DD',   
    dateFormat: 'yy-mm-dd',   
    firstDay: 1,   
    initStatus: '请选择日期',   
    isRTL: false
};

$.datepicker.setDefaults($.datepicker.regional['zh-CN']);  

// tab管理器
K.tabManager = function() {
    var managerObject = {};
    var wrapFn = function(name, fn) {
        var El = $('#' + name),
            hasInit = false; // 
        return function() {
            var result = fn && fn.call(null, El, hasInit);
            hasInit = true;
            return result;
        }
    }

    // name 为容器元素的id
    // fn 切换到某个tab容器时的执行函数
    var _make = function(name, fn) {
        if (!managerObject[name]) {
            managerObject[name] = wrapFn(name, fn);
        }
    }

    // 切换至某个tab
    var _start = function(name) {
        if (managerObject[name]) {
            var result = managerObject[name].call(null);
            if ($.type(result) == 'function') {
                managerObject[name] = result;
                result.call(null);
            }
        }
    }

    return {
        make: _make,
        start: _start
    }
};

K.StringToDate = function(DateStr){if(typeof DateStr=="undefined")return new Date();if(typeof DateStr=="date")return DateStr;var converted = Date.parse(DateStr);var myDate = new Date(converted);if(isNaN(myDate)){DateStr=DateStr.replace(/:/g,"-");DateStr=DateStr.replace(" ","-");DateStr=DateStr.replace(".","-");var arys= DateStr.split('-');switch(arys.length){case 7 : myDate = new Date(arys[0],--arys[1],arys[2],arys[3],arys[4],arys[5],arys[6]);break;case 6 : myDate = new Date(arys[0],--arys[1],arys[2],arys[3],arys[4],arys[5]);break;default: myDate = new Date(arys[0],--arys[1],arys[2]);break;};};return myDate;}

})()