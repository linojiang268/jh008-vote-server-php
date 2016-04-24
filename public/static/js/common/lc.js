/**
 * @name 零创后台UI库
 * @description 配合lc_ui.css使用
 * @author pheart
 */
$(function(){

window.lc = lc = {};

// pub / on
var observe = {
    on: function(name, callback, context){
        if(!this['eventList']){
            this['eventList'] = {};
        }
        this.eventList[name] = {};
        this.eventList[name].context = context || '';
        this.eventList[name].callback = callback;
    },
    trigger: function(name){
        var argument = Array.prototype.slice.call(arguments, 1);
        if(this.eventList && this.eventList[name] && $.type(this.eventList[name].callback)==='function'){
            var context = this.eventList[name].context || this;
            this.eventList[name].callback.apply(context, argument);
        }
    },
    remove: function(name){
        if (!this.eventList) return false;
        delete this.eventList[name];
    },
    has: function(name){
        if (!this.eventList) return false;
        return this.eventList[name] ? true : false;
    },
    make: function(o){
        for(var i in this){
            if(i!='make'){
                o[i] = this[i];
            }
        }
    }
};

/**
 *
 */
var outsiteClick = (function() {
    var eventList = [];
    $(document).click(function(e) {
        var target = e.srcElement || e.target;
        $.each(eventList, function(i, item) {
            var flag = false;
            if (item.el[0] == target) {
                flag = true;
            } else {
                if (item.filter && $.type(item.filter) == 'array') {
                    for (var i=0; i<item.filter.length; i++) {
                        if (item.filter[i].className) {
                            if ($(target).hasClass(item.filter[i].className)) {
                                flag = true;
                                break;
                            }
                        } else if(item.filter[i].parent) {
                            if ($(target).parents('.'+item.filter[i].parent)[0]){
                                flag = true;
                                break;
                            }
                        }
                    }
                }                   
            }

            if (!flag) {
                item.fn.call(item.context, target); 
            }
        })
    });

    return function(el, callback, filter, /*isTrue,*/ context) {
        var parms = {
            el: el,
            fn: callback,
            filter: filter || null,
            context: context || null/*,
            isTrue: isTrue.toString() == 'false' ? isTrue : true*/
        }
        eventList.push(parms);
    }
})();


// 生成唯一的id
function uniqueId() {
    var time = new Date().getTime();
    return time + Math.floor(Math.random() * 10000);
}


lc.SelectManager = (function() {
    var eventHandlers = {};

    function on(El, callback) {
        if (!(El instanceof jQuery))
            return false;

        if (!eventHandlers[El.data('eid')]) {
            eventHandlers[El.data('eid')] = [];
        }

        eventHandlers[El.data('eid')].push(callback);        
    }

    function trigger(El) {
        var eid = El.data('eid'),
            itemHandlers = eventHandlers[eid];
        if (itemHandlers) {
            for (var i = 0; i < itemHandlers.length; i++) {
                if ($.type(itemHandlers[i]) == 'function') {
                    var arguments = $.makeArray(arguments).slice(1);
                    itemHandlers[i].apply(null, arguments);
                }
            }
        }
    }

    return {
        on: on,
        trigger: trigger
    }
})();

/**
 *  register keyup events to global.
 */
/*var registerKeyup = (function(){
    var list = []; 
    $('body').keyup(function(e){
        var target = e.target;
        $.each(list, function(i, item){
            var context = null || item.context;
            if (item.handle) {
                item.handle.call(context, $(target));
            }
        })
    })

    function _regist(handleCallback) {
        list.push({handle: handleCallback});
    }

    return {
        regist: _regist
    }
})()

function limitTextRegistKeyup() {
    var callback = function(el) {
        var target = el, parentEl, limit, textShowEl;
        if (target[0].nodeName == 'TEXTAREA' && target.hasClass('limitText-ta')) {
            parentEl = target.parents('.limitText');
            if (!parentEl.length) return;
            textShowEl = target.siblings('.text-tip');
            limit = Number($.trim(textShowEl.text()).split('/')[1]);
            var value = target.val();
            var textLength = value.length;
            if(textLength > limit) {
                target.val(value.slice(0, limit));
                textShowEl.text(limit + '/' + limit);
            } else {
                textShowEl.text(textLength + '/' + limit);
            }
        }

        if (target.parents('')) {

        }
    }
    registerKeyup.regist(callback);
}*/


/**
 * placeholder 
 */
$.fn.lc_placeholder = function() {
    var target = $(this);
    var placeholderText = target.attr('placeholder');
    if (!('placeholder' in document.createElement('input'))) {
        var value = target.val(); 
        if (!value) {
            target.val(placeholderText);
        }
        
        target.focus(function(){
            var value = $.trim(target.val());
            if (value == placeholderText) {
                target.val('');
            }
        }).blur(function(){
            var value = $.trim(target.val());
            if (!value) {
                target.val(placeholderText);
            }
        })
    }
}

/**
 * limittext
 */
$.fn.lc_limitText = function() {
    var target = $(this),
        textarea, textTip;
    textarea = target.find('textarea');
    if (!textarea.length) return;
    textarea.keyup(function(){
        textShowEl = target.find('.text-tip');
        limit = Number($.trim(textShowEl.text()).split('/')[1]);
        var value = textarea.val();
        var textLength = value.length;
        if(textLength > limit) {
            textarea.val(value.slice(0, limit));
            textShowEl.text(limit + '/' + limit);
        } else {
            textShowEl.text(textLength + '/' + limit);
        }        
    })
}

/**
 * uiSelect 下拉列表
 */
$.fn.lc_uiSelect = function() {
    var target = $(this),
        dropMenu = target.find('.dropdown-menu'),
        showText = target.find('.ui-select-text');
    target.on('click', '.dropdown-menu li a', function(e){
        dropMenu.hide();
        showText.text($(e.target).text());
        e.stopPropagation();
    }).on('click', function(){
        dropMenu.toggle();
    })
    outsiteClick(target, function(srcElement){
        if (!$(srcElement).parents('.ui-select').length && srcElement != target[0]) {
            dropMenu.hide();
        }
    });
}

/**
 * uiSelect 下拉列表,带分页
 *
 * @author: HC
 */
$.fn.lc_uiSelectWithPage = function() {
    var target = $(this),
        dropMenu = target.find('.dropdown-menu'),
        dropMenuPage = target.find('.dropdown-page-w'),
        showText = target.find('.ui-select-text');
    target.on('click', '.dropdown-menu li a', function(e){
        dropMenu.hide();
        dropMenuPage.hide();
        showText.text($(e.target).text());
        e.stopPropagation();
    }).on('click','.dropdown-page-w a',function(e){
        e.stopPropagation();
    }).on('click', function(event){
        dropMenu.toggle();
        dropMenuPage.toggle();
    });
    outsiteClick(target, function(srcElement){
        if (!$(srcElement).parents('.ui-select1').length && srcElement != target[0]) {
            dropMenu.hide();
            dropMenuPage.hide();
        }
    });
}

/**
 * radioSel 单选框
 */
$.fn.lc_radioSel = function() {
    var target = $(this);
    if (!target.length) return;
    target.data('eid', uniqueId());
    target.on('click', 'label', function(e) {
        var curTarget = $(e.currentTarget),
            curParent,
            radio;
        target.find('.radio-wrap').removeClass('sel');
        curParent = curTarget.parent();
        curParent.addClass('sel');
        radio = curParent.find('input[type="radio"]');
        radio.attr('checked', true);
        e.stopPropagation();
        lc.SelectManager.trigger(target, radio.val());
    })
}


/**
 * checkboxSel 复选框
 */
$.fn.lc_checkboxSel = function() {
    var target = $(this), checkboxWrap;
    if (!target.length) return;
    checkboxWrap = target.find('.checkbox-wrap')
    if (!checkboxWrap.length) return;
    $.each(checkboxWrap, function(i){
        if (checkboxWrap.eq(i).attr('d-select') == 'on') {
            checkboxWrap.eq(i).addClass('sel');
            checkboxWrap.eq(i).find('input[type=checkbox]').attr('checked', true);
        }
    })
    target.on('click', 'label', function(e) {
        var curTarget = $(e.currentTarget),
            curParent;
        curParent = curTarget.parent();
        if (curParent.attr('d-select') != 'on') {
            if (curParent.hasClass('sel')) {
                curParent.removeClass('sel');
                curParent.find('input[type="checkbox"]').attr('checked', false);  
            } else {
                curParent.addClass('sel');
                curParent.find('input[type="checkbox"]').attr('checked', true);            
            }
        }
        e.stopPropagation();
    })
}

/**
 * tabs 页面可动态切换
 * 返回的对象可订阅'switch' 事件
 * @parms {jqueryEl | string | ..} 类似于为jquery选择器第一个参数
 */
lc.tabs = function(o) {
    var managerObject = {};
    var wrapFn = function(name, fn) {
        var El = $('#' + name),
            hasInit = false; // 
        return function() {
            var result = fn && fn.call(null, El, hasInit);
            hasInit = true;
            return result;
        }
    };

    // name 为容器元素的id
    // fn 切换到某个tab容器时的执行函数
    var _make = function(name, fn) {
        if (!managerObject[name]) {
            managerObject[name] = wrapFn(name, fn);
        }
    };

    // 切换至某个tab
    var _start = function(name) {
        if (managerObject[name]) {
            var result = managerObject[name].call(null);
            if ($.type(result) == 'function') {
                managerObject[name] = result;
                result.call(null);
            }
        }
    };

    var o = $(o),
        result = {
            // 选中某一项
            select: function(role) {
                if (tab) {
                    var tabItems = tab.find('.ui-tab-item');
                    $.each(tabItems, function(i, tabItem) {
                        if (tabItems.eq(i).attr('role') == role) {
                            tabItems.eq(i).trigger('click');
                        }
                    })
                }
            },
            make: _make
        };
    //observe.make(result);

    var target = o,
        tab = target.find('.ui-tab'),
        tabPage = target.find('.tab-pages');

    tab.on('click', '.ui-tab-item', function(e) {
        var item = $(e.currentTarget), 
            role = item.attr('role');

        tab.find('.ui-tab-item').removeClass('ui-tab-item-current');
        (tabPage.find('.tab-page').length) && tabPage.find('.tab-page').removeClass('tab-page-active');
        item.addClass('ui-tab-item-current');
        (tabPage.find('#' + role).length) && tabPage.find('#' + role).addClass('tab-page-active');
        _start(role);
        //result.trigger('switch', role);
    });

    return result;
}

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


// 初始化 placeholder
function initPlaceholder() {
    var els = $('input[type="text"], input[type="password"], textarea');
    if (els.length) {
        $.each(els, function(i){
            els.eq(i).lc_placeholder();
        })
    }
}

// 初始化 LimitText
function initLimitText() {
    var els = $('body').find('.limitText');
    if (els.length) {
        $.each(els, function(i){
            els.eq(i).lc_limitText();
        })
    }
}

// 初始化 UiSelect
function initUiSelect() {
    var els = $('.ui-select');
    if (els.length) {
        $.each(els, function(i){
            els.eq(i).lc_uiSelect();
        })
    }
}

// 初始化 UiSelectWithPage,分页
function initUiSelectWithPage() {
    var els = $('.ui-select1');
    if (els.length) {
        $.each(els, function(i){
            els.eq(i).lc_uiSelectWithPage();
        })
    }
}

// 初始化 RadioSel
function initRadioSel() {
    var els = $('.radioSels');
    if (els.length) {
        $.each(els, function(i){
            els.eq(i).lc_radioSel();
        })
    }
}

// 初始化 CheckboxSel
function initCheckboxSel() {
    var els = $('.checkboxSels');
    if (els.length) {
        $.each(els, function(i){
            els.eq(i).lc_checkboxSel();
        })
    }
}

initPlaceholder();
initLimitText();
initUiSelect();
initUiSelectWithPage();
initRadioSel();
initCheckboxSel();

})