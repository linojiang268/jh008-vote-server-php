$(function(){
    window.K = K = {};
    var Android_download_url = 'http://dev.file.jhla.com.cn/app/android/jihe.apk',
        Ios_download_url = 'https://itunes.apple.com/cn/app/ji-he-zhao-huo-dong-jiao-peng/id935532535?l=en&mt=8', 
        SCHEMA = 'com.jhla.app.start://';
        
    K.isWeiXin = function() { //是否微信浏览器
        var ua = window.navigator.userAgent.toLowerCase();
        if(ua.match(/MicroMessenger/i) == 'micromessenger'){
            return true;
        }else{
            return false;
        }
    }

    K.is_mobileQQ = function() { // 是否手机qq浏览器
        var ua = navigator.userAgent.toLowerCase();
        if (ua.match('nettype/wifi')) {
            return true;
        } else {
            return false;
        }
    }

    K.getVersion = function() {
        var u = navigator.userAgent, app = navigator.appVersion;
        var versions = { //移动终端浏览器版本信息
            //trident: u.indexOf('Trident') > -1, //IE内核
            //presto: u.indexOf('Presto') > -1, //opera内核
           // gecko: u.indexOf('Gecko') > -1 && u.indexOf('KHTML') == -1, //火狐内核
           // mobile: !!u.match(/AppleWebKit.*Mobile.*/)||!!u.match(/AppleWebKit/), //是否为移动终端
            ios: !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/), //ios终端
            android: u.indexOf('Android') > -1 || u.indexOf('Linux') > -1, //android终端或者uc浏览器
            //iPhone: u.indexOf('iPhone') > -1 || u.indexOf('Mac') > -1, //是否为iPhone或者QQHD浏览器
            iPad: u.indexOf('iPad') > -1, //是否iPad
           // webApp: u.indexOf('Safari') == -1, //是否web应该程序，没有头部与底部
            //webKit: u.indexOf('AppleWebKit') > -1 //苹果、谷歌内核
        };

        for (var key in versions) {
            if (versions[key])
                return key;
        }

        return false;
    }

    K.makeMask = (function() {
        return function(type) {
            var content = '';
            if (type == 'weixin') {
                content =   '<div class="mask-guide clearfix">' +
                                '<img class="mask-guide-img" src="/static/wap/images/arrowhead.png" alt="">' +
                                // '<img class="mask-guide-img s-xguide-down trans" src="/static/wap/images/arrowhead_right.png" alt="">' +
                                // '<img class="mask-guide-img s-xguide-down arrow-1" src="/static/wap/images/arrowhead_right.png" alt="">' +
                            '</div>' +
                            '<span class="mask-tip">点击右上角按钮，选择在浏览器打开</span>'; 
            } else {
                content = '<span class="mask-tip">在其他浏览器打开操作</span>';
            }
            if ($('.mask').length) {
                $('.mask').addClass('slidedown').removeClass('slideup').removeClass('mask-remove').show();
            } else {
                var dom = '<div class="mask slidedown">' +
                    '<div class="wrap-page">' +
                        '<div class="mask-page">' +     
                        '</div>' + content + 
                    '</div>' +
                '</div>';
                $('body').append(dom);
                $('.mask').click(function(e) {
                    $('.mask').addClass('slideup').addClass('mask-remove').removeClass('slidedown');
                })
            }
        }
    })();


    K.NativeApp = {
        home: function() {
            return '';
        },
        joinTeam: function(teamId) { // 进入加入社团页面
            return '?team=' + teamId;
        }
    }

    K.forceDownload = function() { // 去下载
        var downloadUrl = '',
            version = this.getVersion();
        switch (version) {
            case 'android': 
                downloadUrl = Android_download_url;
                break;
            case 'ios':
                downloadUrl = Ios_download_url;
                break;
            case 'iPad':
                downloadUrl = Ios_download_url;
                break;
            default: break;
        }
       
        if (downloadUrl) {
            location.href = downloadUrl;
        }
    }

    K.testApp = function(type, fn) { // 是否安装了app 
        var way;
        way = K.isWeiXin() ? 'weixin' : K.is_mobileQQ() ? 'qq' : '';
        if (way) {
            K.makeMask(way);
            fn();
            return false;
        }

        var timeout, t = 1000, hasApp = true;
        var type = type || 'home';

        setTimeout(function () {
            if (!hasApp) {
                K.forceDownload();
            }
            document.body.removeChild(ifr);
            fn && fn();
        }, 2000);
      
        var t1 = Date.now();
        var schema = '';
        var ifr = document.createElement("iframe"); 
        if (schema = K.NativeApp[type]) {
            schema = SCHEMA + schema;
        } else {
            schema = SCHEMA;
        }
        ifr.setAttribute('src', schema);  
        ifr.setAttribute('style', 'display:none');  
        document.body.appendChild(ifr);

        timeout = setTimeout(function () {  
             var t2 = Date.now();  
             if (!t1 || t2 - t1 < t + 100) {  
                 hasApp = false;  
             }  
        }, t);  
    }

    K.loadingButton = function(el) {
        var text = el.attr('data-text'),
            loadGif = $('<img class="load-button-img" src="/static/wap/images/loading-2.gif" />');

        return {
            load: function() {
                el.html(loadGif);
                return this;
            },
            unload: function() {
                el.html(text);
            }
        }
    }

    K.isMobile = function(mobile) {
        var myreg = /^(((17[0-9]{1})|(13[0-9]{1})|(15[0-9]{1})|(18[0-9]{1}))+\d{8})$/; 
        if(!myreg.test(mobile)) { 
            return false; 
        }
        return true;
    }

    K.captchaCountDown = function(el, time, fn) {
        var originalValue = el.val();
        if ($.type(time) != 'number') 
            return false;

        function showText() {
            var text = '(' + time + '秒后失效)';
            el.val(text);
        }

        function recover() {
            clearInterval(timer);
            el.removeClass('btn-hollow-disable');            
        }

        showText();
        el.addClass('btn-hollow-disable');

        var timer = setInterval(function() {
            time--;
            if (time < 0) {
                el.val(originalValue);
                recover();
                if (fn) {
                    fn.call();
                }
                return;
            }
            showText();
        }, 1000);

        return {
            close: function() {
                if (timer) clearInterval(timer);
                recover();
            }
        }
    }

    // 是否座机号
    K.isTel = function(value) {
        if (!/^0\d{2,3}\-\d{7,8}$/.test(value)) {
            return false;
        }
        return true;
    }

    K.ns = function(str) {
        var parts = str.split("."),
            parent = K;

        if(parts[0] === "K"){
            parts = parts.slice(1);
        }

        for(var i = 0,l = parts.length; i<l;i++){
            if(typeof parent[parts[i]] === "undefined"){
                parent[parts[i]] = {};
            }
            parent = parent[parts[i]];
        }
        return parent;
    }

    // alert
    window.oAlert = window.alert;
    window.alert = (function() {
        var timer = '';
        var alertEl = $('<div class="ui-alert"><div class="ui-alert-content"></div></div>');
        return function(text) {
            if (timer) return;
            var uiSelect = $('.ui-alert');
            if (!uiSelect.length) {
                alertEl.find('.ui-alert-content').text(text);
                $('body').append(alertEl);
            } else {
                uiSelect.show();
            }

            timer = setTimeout(function() {
                alertEl.remove();
                clearTimeout(timer);
                timer = null;
            }, 1500);
        }
    })();

    $('#downloadBannerBtn').click(function(e) { // 顶部导航下载条
        var loading = K.loadingButton($(this)).load();
        K.testApp('', function() {
            loading.unload();
        });
        e.preventDefault();
        e.stopPropagation();
    })
})
