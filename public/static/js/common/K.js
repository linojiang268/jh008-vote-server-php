/*!
 @Name：零创后台简洁版
 @Author：pheart  
 */
(function(window){
	var P;

	P = {};

	P.introduce = {
		__BUILD_TIME: '20130925145439',

		version: '0.01',

		author: 'pheart'
	};

	$.extend(P, {
		ns: function(str) {
		    var parts = str.split("."),
		    parent = K,
		    i=0,
		    l=0;

		    if(parts[0]==="K"){
		        parts = parts.slice(1);
		    }
		    for(i=0,l=parts.length; i<l;i++){
		        if(typeof parent[parts[i]] === "undefined"){
		            parent[parts[i]] = {};
		        }
		        parent = parent[parts[i]];
		    }
		    return parent;
		}

	});

	window.K = P;

})(window);


(function(K){
  /**
   * 
   * {value: val, isEmpty: callback, validate: fn }
   *
   */
  K.Validater = function() {

  	var Validater = {};

  	function run(data) {
  		var isEmpty = data.isEmpty,
  			value = data.value || '';
  		if(!value && isEmpty) {
  			isEmpty.call(null, value);
  			return false;
  		}
  		if(data.validate && !data.validate.call(null, value)) {
  			return false;
  		}
  		return true;
  	}

  	var createValidater = function() {
  		var newValidater = {};
  		newValidater.list = [];
  		// @param {Array | Object}
  		newValidater.push = function() { 
  			var _this = this,
  				data = arguments[0],
  				datatype = $.type(data);
  			if(datatype == 'array') {
  				_this.list.concat(data);
  			}else if(datatype == 'object') {
  				_this.list.push(data);
  			}
  		};
  		// success callback
  		newValidater.run = function( done ) {
  			var _this = this,
  				list = this.list,
  				flag = true;
  			for(var i=0, l=list.length; i<l; i++) {
  				if(!run(list[i])) {
  					flag = false;
  					break;
  				}
  			}

  			flag && done();
  		};
  		return newValidater;
  	}	

  	var init = function() {
  		return createValidater();
  	}
  	return init;
  }
})(K);

(function(K){
  /**
   * inheritance implementation.
   * depend on  prototype.js
   * 
   */
   
  var Class = (function() {
      var _toString = Object.prototype.toString,
          _hasOwnProperty = Object.prototype.hasOwnProperty;

      function keys(object) {
        if ($.type(object) !== 'object') { throw new TypeError(); }
        var results = [];
        for (var property in object) {
          if (_hasOwnProperty.call(object, property))
            results.push(property);
        }

        if (IS_DONTENUM_BUGGY) {
          for (var i = 0; property = DONT_ENUMS[i]; i++) {
            if (_hasOwnProperty.call(object, property))
              results.push(property);
          }
        }

        return results;
      }

      function argumentNames(value) {
        var names = value.toString().match(/^[\s\(]*function[^(]*\(([^)]*)\)/)[1]
          .replace(/\/\/.*?[\r\n]|\/\*(?:.|[\r\n])*?\*\//g, '')
          .replace(/\s+/g, '').split(',');
        return names.length == 1 && !names[0] ? [] : names;
      }

      function wrap(method, wrapper) {
        var __method = method;
        return function() {
          var _this = this;
          var a = update([bind(_this, __method)], arguments);
          return wrapper.apply(this, a);
        }
      }

      function bind(method, context) {
        var tmp;

        if ($.type(context) == 'function') {
          tmp = context;
          context = method;
          method = tmp;
        }

        var bound = function() { 
          var a = arguments;
          return method.apply(context, a);
        }

        return bound;
      }

      function $A(iterable) {
        if (!iterable) return [];
        if ('toArray' in Object(iterable)) return iterable.toArray();
        var length = iterable.length || 0, results = new Array(length);
        while (length--) results[length] = iterable[length];
        return results;
      }

      function update(array, args) {
        var arrayLength = array.length, length = args.length;
        while (length--) array[arrayLength + length] = args[length];
        return array;
      }


      var IS_DONTENUM_BUGGY = (function(){
        for (var p in { toString: 1 }) {
          if (p === 'toString') return false;
        }
        return true;
      })();

      function subclass() {};
      function create() {
        var parent = null, properties = $A(arguments);
        if ($.isFunction(properties[0]))
          parent = properties.shift();

        function klass() {
          this.initialize.apply(this, arguments);
        }

        $.extend(klass, Class.Methods);
        klass.superclass = parent;
        klass.subclasses = [];

        if (parent) {
          subclass.prototype = parent.prototype;
          klass.prototype = new subclass;
          parent.subclasses.push(klass);
        }

        for (var i = 0, length = properties.length; i < length; i++)
          klass.addMethods(properties[i]);

        if (!klass.prototype.initialize)
          klass.prototype.initialize = $.noop;

        klass.prototype.constructor = klass;
        return klass;
      }

      function addMethods(source) {
        var ancestor   = this.superclass && this.superclass.prototype,
            properties = keys(source);

        if (IS_DONTENUM_BUGGY) {
          if (source.toString != Object.prototype.toString)
            properties.push("toString");
          if (source.valueOf != Object.prototype.valueOf)
            properties.push("valueOf");
        }

        for (var i = 0, length = properties.length; i < length; i++) {
          var property = properties[i], value = source[property];
          if (ancestor && $.isFunction(value) &&
              argumentNames(value)[0] == "$super") {
            var method = value;
            value = wrap((function(m) {
              return function() { return ancestor[m].apply(this, arguments); };
            })(property), method);

            value.valueOf = (function(method) {
              return function() { return method.valueOf.call(method); };
            })(method);

            value.toString = (function(method) {
              return function() { return method.toString.call(method); };
            })(method);
            
          }
          this.prototype[property] = value;
        }

        return this;
      }

      return {
        create: create,
        Methods: {
          addMethods: addMethods
        }
      };

  })();

  K.Class = Class;

})(K);

(function(){
    /**
     * 工具函数
     */  
    var util = K.ns('util');

    util.htmlDecode = function(text) {
        var temp = document.createElement("div");
        temp.innerHTML = text;
        var output = temp.innerText || temp.textContent;
        temp = null;
        return output;
    }

    /** 
     * @params{Object}
            -ThList
            -columnNameList
            -rowClass
            -sourcea
            -perPageNums
     */
    util.PagTable = function(options) {
        var defaultOptions = {
            paginateBtns: ['<i class="icon iconfont"></i>', '<i class="icon iconfont"></i>'],
            skin: 'lc-table-c'
        };
        return new K.Table($.extend(defaultOptions, options));
    }

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

    // Returns a function, that, when invoked, will only be triggered at most once
    // during a given window of time.
    util.throttle = function(func, wait) {
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

    /**
     * 元素外点击执行
     * @parms {Jquery} jquery对象
     * @parms {Function} 元素外点击执行函数
     * @parms {Array} 过滤符合该条件的元素
            -className 目标元素的class
            -parent 父元素class
     * @parms {Object} callback函数执行的上下文
     * @return undefined
     */
    util.outsiteClick = (function() {
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
                              var parent = $(target).parents('.'+item.filter[i].parent);
                              if (parent[0] && item.el.closest('.'+item.filter[i].parent)[0] == parent[0]){
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

    /**
     * baseView with events
     */
    util.BaseView = K.Class.create({
        setEvents: function() {
          if (!this.events && $.type(this.events) != 'object') return;
          if (!this.eventsHandler && $.type(this.eventsHandler) != 'object') return;
          K.BindEventsHelper.setEvents(this.events, this.eventsHandler, this.El, this);
        },
        undelegate: function() {
          if (this.El) {
            K.BindEventsHelper.undelegate(this.El);
          }
        }
    });

    /**
     * 页面管理器 
     * 同一个页面中有多个容器，仅能显示某一个
     */
    util.pageManager = function() {
        var list = [];

        function checkIsExit(name) {
            // default by name
            if (list.length) {
                for (var i=0, l=list.length; i<l; i++) {
                    if (list[i].name == name) {
                        return list[i];
                    }
                }
            }
            return false;
        }

        /**
         * @params {Object} 
                -el
                -parent
                -name
         */
        function add(/* object */) {
            var argument = arguments[0];
            if (argument && $.type(argument) == 'object') {
                if (!checkIsExit(argument.name)){
                    list.push(argument);
                }
            } else {
                throw Error('没有传入page add 参数');
            }
        }

        function show(name) {
            var result = checkIsExit(name);
            if (result) {
                hide();
                result.parent && result.parent.show();
                result.el && result.el.show();
            } else {
                throw new Error('name 不存在');
            }
        }

        function hide() {
            if (list.length) {
                for (var i=0, l=list.length; i<l; i++) {
                    list[i].parent && list[i].parent.hide();
                    list[i].el && list[i].el.hide();
                }
            }
        }

        function render(name, ele) {
            var result = checkIsExit(name);
            if (result) {
                show(name);
                result.el && result.el.html(ele);
            } else {
                throw new Error('name 不存在');
            }
        }

        return {
            add: add,
            show: show,
            hide: hide,
            render: render
        }
    }


    var Validate = function() {
        var list = [];
        
        // @params {boolean | string | function}
        // @params {fn} if check false then execute
        function _check(term, fn) {
            if (term) {
                list.push({term: term, fn: fn});
            }
        }

        function start() {
            var length = list.length, index = 0; 
            function runItem(item) {
                var type, term, fn; 
                function goRun() {
                    index ++;
                    if (index >= length) {
                        return true;
                    }
                    return runItem(list[index]);
                }
                if (item) {
                    term = item.term;
                    fn = item.fn;               
                    type = $.type(term);
                    if (type == 'function') {
                        if (!term()) {
                            fn && fn();
                            return false;
                        } else {
                            return goRun();
                        }
                    } else {
                        if (!term) {
                            fn && fn();
                            return false;
                        } else {
                            return goRun();
                        }
                    }   
                }
            }
            return runItem(list[0]);
        }



        function run(fn) {
            if (start()) {
                fn && fn();
            }
        }
 
        return {
            check: _check,
            run: run
        }
    }

    var regexp = {
        user: /^\S{6,16}$/, // 6-16位
        password: /^\S{6,16}$/, // 6-16位
        size: function(start, end) {
            if (!end) end = start;
            return new RegExp('^\\S{' + start + ',' + end + '}$');          
        },
        checkUser: function(user) {
            return function() {
                if (!regexp.user.test(user)) 
                    return false;
                return true;                
            }
        },
        checkSize: function(val, start, end) {
            return function() {
                var reg = regexp.size(start, end);
                if (!reg.test(val))
                    return false;
                return true;                
            }
        },
        checkEmpty: function(val) {
            return function() {
                if (!val.length)
                    return false;
                return true;                
            }
        },
        checkSame: function(a, b) {
            return function() {
                if (a != b) 
                    return false;
                return true;
            }
        }
    }

    var myValidate = function(t) {
        if(t){
            tip = t;
        }
        var validate = Validate();
        function _check(term, text) {
            validate.check(term, function(){
                tip(text);
            });
        }   
        function _run(fn) {
            validate.run(fn);
        }
        return {
            check: _check,
            run: _run
        }
    }
    myValidate.regexp = regexp;

    util.myValidate = myValidate;
    
})()

K.Observe = window.utilHelper ? window.utilHelper.Observe : '';

// plugin/ktable/utilhelper
K.BindEventsHelper = window.utilHelper ? window.utilHelper.BindEventsHelper : '';



// plugin/ktable/table
K.Table = window.Table ? window.Table : '';


// plugin/ktable/paginate
K.Paginate = window.Paginate ? window.Paginate : '';

