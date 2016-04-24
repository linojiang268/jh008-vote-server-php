(function(){
    window.K = K = {};

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

    //K.getVersion = function() {
    //    var u = navigator.userAgent, app = navigator.appVersion;
    //    var versions = { //移动终端浏览器版本信息
    //        //trident: u.indexOf('Trident') > -1, //IE内核
    //        //presto: u.indexOf('Presto') > -1, //opera内核
    //       // gecko: u.indexOf('Gecko') > -1 && u.indexOf('KHTML') == -1, //火狐内核
    //       // mobile: !!u.match(/AppleWebKit.*Mobile.*/)||!!u.match(/AppleWebKit/), //是否为移动终端
    //        ios: !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/), //ios终端
    //        android: u.indexOf('Android') > -1 || u.indexOf('Linux') > -1, //android终端或者uc浏览器
    //        //iPhone: u.indexOf('iPhone') > -1 || u.indexOf('Mac') > -1, //是否为iPhone或者QQHD浏览器
    //        iPad: u.indexOf('iPad') > -1, //是否iPad
    //       // webApp: u.indexOf('Safari') == -1, //是否web应该程序，没有头部与底部
    //        //webKit: u.indexOf('AppleWebKit') > -1 //苹果、谷歌内核
    //    };
    //
    //    for (var key in versions) {
    //        if (versions[key])
    //            return key;
    //    }
    //
    //    return false;
    //}

    K.makeMask = (function() {
        return function(type) {
            var content = '';
            if (type == 'weixin') {
                content =   '<div class="mask-guide clearfix">' +
                                '<img class="mask-guide-img" src="/static/wap/images/arrowhead.png" alt="">' +
                            '</div>' +
                            '<span class="mask-tip">点击右上角按钮，选择"在浏览器中打开"</span>'; 
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

    K.testApp = function(type, fn) { // 是否安装了app 
        var way = '';
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

    K.checkScrollToBottom = (function() {
        var scrollInterval = null;
        return function(options) {
            if (!options) return;
            if (scrollInterval) {
                clearInterval(scrollInterval);
            }
            
            var el = options.el || $(document);
            var count = 0, lock = false, hasTip = false;
            var _callback = function() {
                count++;
                if (options.nums && count > options.nums) {
                    clearInterval(scrollInterval);
                    return;
                }            
                options.callback && options.callback(function(res){
                    if (res.result == 0) {
                        lock = true;
                    } else if (res.result == 1 || res.result == 2) {
                        lock = false;
                    }
                });
            };



            scrollInterval = setInterval( function(){
                if ( !el.find('.homePanel').length ) {
                    return false;
                }
                var documentEl = el,
                    scrollTop = documentEl.scrollTop();
                    if (scrollTop + 80 + documentEl.innerHeight() > documentEl.children().height()) {
                        if (lock  == true && !hasTip) {
                            hasTip = true;
                            options.tip && options.tip();
                            return;
                        }
                        _callback();
                    } else {
                        hasTip = false;
                    }
            } , 500);
        }
    })();

    // attrndant modal
    K.aModal = function(option){
        var o = {},
            defaults = {
                title: '提示',
                titleCenter: true,
                content: '',
                width: '60%',
                height: '180',
                ok: true,
                okText: '确定',
                okCallback: function(){},
                closeBtn: false
            };

        var modal = function(){};
        modal.prototype = {
            constructor: modal,
            init: function(options){

                var op = $.extend(o, defaults, options),
                  self = this;
                //创建dom 并绑定事件
                this.createDom(op);
                return this;
            },
            createDom: function(op){
                var self = this,
                    arr  = [];
                arr.push('<div class="modal-mask"></div>');
                arr.push('<div class="modal-w">');
                arr.push('<h3 class="modal-title">'+op.title+'<span class="modal-close hide">x</span></h3>');
                arr.push('<div class="modal-content">'+op.content+'</div>');
                arr.push('<div class="modal-operate"><button class="sure-btn">'+op.okText+'</button></div>');
                arr.push('</div>');
                arr.join('');
                $('body').prepend(arr.join(''));//插入最前面
                
                //缓存变量
                var $modal    = $('.modal-w'),
                    $title    = $('.modal-title'),
                    $content  = $('.modal-content'),
                    $sureBtn  = $('.sure-btn'),
                    $closeBtn = $('.modal-close'),
                    $modalOp  = $('.modal-operate');
                //绑定事件
                if ( op.closeBtn ) {
                    $closeBtn
                        .show()
                        .on('click',function(e) {
                            self.destroy();
                        });
                }else {
                    $closeBtn.hide();
                }

                $modal.on('click', '.sure-btn', function(e) {
                    if ( op.okCallback ) { op.okCallback(); }
                    //销毁弹窗
                    self.destroy();
                });

                //调整样式
                $modal
                    .width(op.width)
                    .height(op.height)
                    .css({marginTop:"-"+op.height/2+'px'});
                        //要算上padding的值
                $content.height(op.height-$title.height()-$modalOp.height()-20-36);

                if( !op.titleCenter ) { $title.removeClass('tc').addClass('tl'); }
            },
            destroy: function(){
                $('.modal-mask').remove();
                $('.modal-w').remove();
            }
        };
        return (new modal()).init(option);
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

    // Returns a function, that, as long as it continues to be invoked, will not
    // be triggered. The function will be called after it stops being called for
    // N milliseconds.
    var debounce = function(func, wait) {
      var timeout;
      return function() {
        var context = this, args = arguments;
        var later = function() {
          timeout = null;
          func.apply(context, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    };
    
    K.throttle = function(func, wait) {
      var context, args, timeout, throttling, more;
      var whenDone = debounce(function(){ more = throttling = false; }, wait);
      return function() {
        context = this; args = arguments;
        var later = function() {
          timeout = null;
          if (more) func.apply(context, args);
          whenDone();
        };
        if (!timeout) timeout = setTimeout(later, wait);
        if (throttling) {
          more = true;
        } else {
          func.apply(context, args);
        }
        whenDone();
        throttling = true;
      };
    };

})();