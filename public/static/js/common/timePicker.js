/**
 * 时间选择器 
 * by pheart
 */

$(function(){

var util = K.util;

/** 
 * timepicker
 * select hour & minute
 * @ params {Object} options
        - container  
        - defaultValue
        - minuteInterval
        - select            the callback of select the time.
 */
var TimePicker = function(options) {
    if (!options.container) {
        throw new Error('container参数不能为空');
    }
    var defaultOptions = {
        container: null,
        defaultValue: '00:00',
        hourInterval: 24,
        minuteInterval: 6,
        select: null
    }
    this.options = $.extend(defaultOptions, options);
    this.initialize();
}

TimePicker.prototype = {
    constructor: TimePicker,
    initialize: function() {
        // create dom
        var El, _this = this;
        this.El = El = $('<div class="clock-sel-con"><ul class="clock-sel-nav"></ul></div>');
        var nav = this.El.find('.clock-sel-nav');
            hourInterval = this.options.hourInterval,
            minuteInterval = this.options.minuteInterval;
        for (var i = 0; i < hourInterval; i++) {
            var item = this.createItemUi(i, minuteInterval);
            nav.append(item);
        }
        this.options.container.append(this.El);
        this.setInputValue(this.options.defaultValue);
        //set events
        this.options.container.click(function() {
            _this.El.toggle();
            if (_this.El.css('display') == 'block') {
                if (!_this.cb) {
                    var h = _this.El.find('.clock-sel-nav').height();
                    _this.cb = Math.floor(h/24*12);                    
                }
                _this.El[0].scrollTop = _this.cb;
            }
        });

        this.El.on('click', 'li', function(e) {
            var cur = $(this).find('.item-bot');
            if (cur.css('display') == 'none') {
                El.find('.item-bot').hide();
                cur.show();
            } else {
                cur.hide();
            }

            if ($(e.target)[0].tagName == 'A') {
                var value = $(e.target).text();
                _this.selectValue(value);
            }

            e.stopPropagation();
        });

        this.El.on('click', 'dd a', function(e) {
            var select = _this.options.select;
            var value = $(this).text();
            _this.selectValue(value, function() {
                _this.El.hide();
                this.El.find('.item-bot').hide();
            });
            e.stopPropagation();
        });

        util.outsiteClick(this.options.container, function() {
            _this.El.hide();
        }, [
            {parent: 'date-pick-clock'}
        ]);

    },
    selectValue: function(value, callback) {
        var select = this.options.select;
        if ($.type(select) == 'function') {
            select.call(null, value);
            this.setInputValue(value);
        }
        callback && callback.call(this);
    },
    createItemUi: function(hour, mInterval) {
        var hour = hour || 0,
            mInterval = mInterval || 0,
            hourText = hour < 10 ? '0' + hour : hour;
        var domArr = [];
        var mString = [], mLength;//<a href="javascript:;" class="item-top-icon"><i class="icon iconfont"></i></a>
        domArr.push('<li><a class="item-top">' + hourText + ':00</a>');
        domArr.push();
        if (mInterval > 0) {
            mLength = Math.floor(60 / mInterval);
            for (var i = 1 ; i < mInterval; i++) {
                var m = mLength * i;
                var mtext = m < 10 ? (m == 0 ? '00' : '0' + m) : m;
                mString.push('<dd><a href="javascript:;">' + hourText + ':' + mtext + '</a></dd>');
            }               
        }
        if (mString.length) {
            domArr.push('<dl class="item-bot clearfix">');
            domArr.push(mString.join(' '));
            domArr.push('</dl>');
        }
        domArr.push('</li>');
        return domArr.join(' ');
    },
    setInputValue: function(value) {
        this.options.container && (
            this.options.container.find('input').val(value)
        )
    }
}

K.TimePicker = TimePicker;

});