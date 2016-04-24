/**
 * @name 项目中所有依赖的弹窗
 * @description 基于后台UI，在layer基础上创建符合零创后台UI的弹窗库
 * @author pheart
 */
 
(function(){

var dialog = K.ns('dialogUi');

/**
 * 文本框 
 * @params {String} text 文本信息
 * @parmas {Function} callback 文本框回调函数
 */
dialog.alert = function(text, callback) {
    return layer.open({
        time: 0,
        content: '<p class="alert-con">'+ text +'</p>',
        closeBtn: false,
        title: false,
        area: ['360px', '200px'],
        skin: 'lc-layui-layer',
        btn: ['确定'],
        yes: function(index, layero) {
            callback && callback();
            layer.close(index);
        }
    })
}

/**
 * 确定弹出框
 * @parms {Object}
        - text {String} 文本信息
        - okText {String} 确认按钮文本信息
        - cancelText {String} 取消按钮文本信息
        - okCallback {Function} 确认按钮回调函数
        - cancelCallback {Function} 取消按钮回调函数
 */
dialog.confirm = function(options) {
    return layer.open({
        time: 0,
        content: '<p class="alert-con">'+ options.text || '' +'</p>',
        closeBtn: false,
        title: false,
        area: ['360px', '200px'],
        skin: 'lc-layui-layer',
        btn: [options.okText || '确定', options.cancelText || '取消'],
        yes: function(index, layero) {
            options.okCallback && options.okCallback();
            layer.close(index);
        },
        cancel: function(index) {
            options.cancelCallback && options.cancelCallback();

        }
    }) 
}

/**
 * 加载弹出框
 */
dialog.wait = function() {
    var dia =  layer.open({
        type: 3
    });

    return {
        close: function() {
            layer.close(dia);
        }
    }
}

/**
 * 提示弹出框
 * @params {Element} ele 目标元素
 * @parmas {String} content 提示文本内容
 * @parms  {Object} options 可拓展的选项
 */
dialog.tip = function(ele, content, options) {
    var settings = $.extend({
        type: 4,
        tips: 3,
        closeBtn: false,
        content: [content, ele],
        time: 2000 
    }, options || {});

    var dia = layer.open(settings);
    return {
        close: function() {
            layer.close(dia);
        }
    }
}

/**
 * 完全依赖于lyaer配置选项的弹框
 */
dialog.open = function(options) {
    var dia = layer.open(options);
    return {
        close: function() {
            layer.close(dia);
        }
    }
}

/**
 * 加载中信息提示框
 * @params {String} text 提示文本
 */
dialog.loading = function(text) {
    var dia = layer.msg(text || '', {time:false, icon:16, offset: '200px'});
    return {
        close: function() {
            layer.close(dia);
        }
    }
}

/**
 * 消息提示框
 * @params {String} text 消息文本
 */
dialog.message = function(text, time) {
    var dia = layer.msg(text || '', {
        time: time || 1000
    });
    return {
        close: function() {
            layer.close(dia);
        }
    }
}

/**
 * textarea弹出框
 * #parms {Object} opt
        - title
        - callback
        - content
        - text
 */
dialog.textarea = function(opt) {
    var dia = layer.open({
        type: 0,
        title: opt.title,
        area: opt.area || ['400px', '250px'],
        content: '<div class="refuse-dia">' +
            '<p class="tip textarea-dia-text">'+ (opt.text || '') +'</p>' +
            '<textarea name="" id="refuseContent" placeholder="选填">'+ (opt.content || '') +'</textarea>' +
            '<div class="mt20">' +
                '<a id="sureBtn" href="javascript:;" class="button button-orange">确定</a>' +
                '<a id="cancelBtn" href="javascript:;" class="button button-orange">取消</a>' +
            '</div>' +
        '</div>',
        btn: false,
        success: function(layero, index) {
            layero.find('#sureBtn').click(function() {
                var text = $.trim(layero.find('#refuseContent').val());
                opt.callback && opt.callback(text, function() {
                    layer.close(index);
                });
            })
            layero.find('#cancelBtn').click(function() {
                layer.close(index);
            })
        }
    });
    return {
        close: function() {
            layer.close(dia);
        }
    }
}

/**
 * addMark弹出框
 * #parms {Object} opt
        - title
        - callback
        - content
 */
dialog.addMark = function(opt) {
    return dialog.textarea($.extend({
        title: '添加备注',
        area: ['400px', '250px']
    }, opt));
}

/**
 * 单选弹出框
 * 方向  success  content  title  data['', {key:, value:}] 
 */
dialog.radio = (function() {
    var model = [];
    var addRadio = function(value, layero) {
        model.push(value);
        if (layero) {
            layero.find('.radioSels').append(createRadioItemUi(value));
        }
    }

    // 创造radio html结构
    var createRadioItemUi = function(value, curdata) {
        if (curdata && curdata.value == value) {
            return  '<div class="radio-wrap sel">' +
                        '<input type="radio" checked=true name="aaa" value="'+ value +'">' +
                        '<label><i class="icon iconfont"></i>'+ value +'</label>' +
                    '</div>';
        } else {
            return  '<div class="radio-wrap">' +
                        '<input type="radio" name="aaa" value="'+ value +'">' +
                        '<label><i class="icon iconfont"></i>'+ value +'</label>' +
                    '</div>';
        }
    }

    var getAllRadio = function(datas, curdata) {
        var result = '';
        if ($.type(datas) != 'array') return;
        model = datas;
        $.each(datas, function(i, data) {
            var val = '', dataType;
            dataType = $.type(data);
            if (dataType == 'object') {
                val = data.value;
            } else if (dataType == 'string') {
                val = data;
            } else {
                return;
            }
            result += createRadioItemUi(val, curdata);
        })
        return result;
    }

    var selectValue = function(value) {
        if (!value || !model.length) return;
        for (var i = 0; i < model.length; i++) {
            var dataType, data = model[i];
            dataType = $.type(data);
            if (dataType == 'object') {
                if (data.value == value) return data;
            } else if (dataType == 'string') {
                if (data == value) return data;
            }
        }
        return;
    }

    /**
     * @parms {Object} options
            - title 
            - curdata
            - content 
            - datas 
            - success
            - callback
     */
    return function(options) {
        var radioHtmlString = getAllRadio(options.datas, options.curdata);
        var content = '<div class="group-dia">' + 
                        '<div>'+ (options.title || '') +'</div>' + 
                        '<div class="radioSels clearfix">'+ radioHtmlString +'</div>' + 
                            '<div class="add-wrap">'+  ( options.content ? options.content : '' )  +
                        '</div>'+
                        '<div id="content">' +
                            '<div class="mt10"><a href="javascript:;" id="sureBtn" class="button button-blue button-m">确定</a></div>' +
                        '</div>' +
                    '</div>';

        var dia = layer.open({
            content: content,
            title: false,
            btn: false,
            skin: 'lc-layui-tip-white',
            success: function(layero, index) {
                layero.find('.radioSels').lc_radioSel();
                layero.find('#sureBtn').click(function(e){
                    var value = selectValue(layero.find('input[type="radio"]:checked').val());
                    options.callback && options.callback(value, function() {
                        layer.close(index);
                    });
                })

                options.success && options.success(layero, index, {add: addRadio});
            }
        })      

        return {
            close: function() {
                layer.close(dia); 
            },
            show: function() {
                layer.show(dia);
            }
        }
    }

})()

/**
 * 手机屏幕预览弹出框
 * @parms {String} content 内容展示
 */
dialog.mobile = function(content) {
    var dia = layer.open({
            title: false,
            content:    '<div class="phone-con">' + 
                            '<div class="phone-wrap">' + 
                                '<div class="phone-main"><div class="p15">' + 
                                    (content || '') + 
                                '</div></div>' + 
                            '</div>' + 
                        '</div>',
            btn: false,
            area: ['396px', '658px']
    })
    return {
        close: function() {
            layer.close(dia);
        }
    }
}

})()