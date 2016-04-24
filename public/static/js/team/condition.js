(function(){
	var Class = K.Class,
		util = K.util,
		server = K.server,
		DialogUi = K.dialogUi;

/*	var conditionsManager = (function() {
		var joinType;

		function _init() {
			var conditions = [];
			var uiInfors = $('#enrollAttrs').find('.ui-attr-item');
			uiInfors.each(function(i) {
				var condition = {};
				var target = uiInfors.eq(i).find('.ui-infor-link');
				condition.requirement = target.val();
				condition.id = target.attr('data-id');
				conditions.push(condition);
			});
			// set joinType flag.
			joinType = getjoinType();
			enrollAttrsManager.set(conditions);
		}

		return {
			init: _init,
			//add: _add,
			//remove: _remove,
			get: function() {
				return conditions;
			},
			setJoinType: function(value) {
				joinType = value;
			},
			getData: function() {
				var result = {};
				result.join_type = joinType;
				result.requirements = enrollAttrsManager.get().enroll_attrs;
				return result;
			}
		}
	})();*/

	function getjoinType() {
		var condition = $('input[name=condition]:checked');
		if (!condition.length) return false;
		return condition.val();
	}

    var enrollAttrsManager = (function() {
        var enroll_attrs = [],
            defaultAttrs = [{requirement: '手机号'}],
            joinType,
            enrollAttrs = $('#enrollAttrs'),
            preveiwMobile = $('#preveiwMobile');

        var throttle = util.throttle;

        function createEnrollAttr(value_obj, defaultFlag) {
            var htmlEl = defaultFlag ?
                $('<div class="ui-infor ui-attr-item" data-id="1">' +
                    '<a href="javascript:;" attr-id="'+ (value_obj.id || '') +'" class="ui-infor-link">' + value_obj.requirement + '</a>' +
                    '</div>') :
                $('<div class="ui-attr-item"><input type="text" value="'+ (value_obj && value_obj.requirement ? value_obj.requirement : '') +'" data-id="'+ (value_obj && value_obj.id ? value_obj.id : '') +'" class="form-control ui-infor-link" placeholder="请输入报名条件" />' +
                    '<a href="javascript:;" class="ui-attr-item-del"><i class="icon iconfont"></i></a></div>');

            setEvent(htmlEl);
            enrollAttrs.append(htmlEl);
        }

        function setEvent(htmlEl) {
            htmlEl.find('.iconfont').click(function() {
                htmlEl.remove();
                getAttr();
            });

            htmlEl.find('input').keyup(function() {
                throttleKeyUpHandler(this.value, $(this));
            });
        }

        function checkUnique(value) {
            var obj = {};
            $.each(enroll_attrs, function(index,attr) {
                if (!obj[attr.requirement]) {
                    obj[attr.requirement] = 1;
                } else {
                    obj[attr.requirement] ++;
                }
            });

            if (obj[value] >= 1) {
                return false;
            }
            return true;
        }

        var throttleKeyUpHandler = throttle(function(value, el){
            if (!checkUnique(value)) {
                DialogUi.tip(el, '该条件已存在');
            } else {
                getAttr();
            }
        }, 300);

        function getAttr() {
            var result = [];
            /*$.each(defaultAttrs, function(index, attr) {
                result.push(attr);
            });*/

            var inputs = enrollAttrs.find('.form-control');
            $.each(inputs, function(i){
                var value = inputs.eq(i).val();
                var item  = {};
                if (value) {
                	item.requirement = value;
                	if (inputs.eq(i).attr('data-id')) {
                		item.id = inputs.eq(i).attr('data-id');
                	}
                    result.push(item);
                }
            });
            enroll_attrs = result;
            previewInMobile();
        }

        function isDefault(value) {
            return ~defaultAttrs.join('').indexOf(value);
        }

        function iteratorAttrs(attrs) {
            $.each(attrs, function(i, enroll_attr) {
                if (isDefault(enroll_attr)) {
                    createEnrollAttr(enroll_attr, true);
                } else {
                    createEnrollAttr(enroll_attr);
                }
            })
        }

        function renderEnrollAttrs() {
            if (enroll_attrs && enroll_attrs.length) {
                iteratorAttrs(enroll_attrs);
            } else {
                iteratorAttrs(defaultAttrs);
            }
            getAttr();
        }

        $('#addAttrsBtn').click(function(){
            createEnrollAttr('');
        })

        function previewInMobile() {
            var attrs = defaultAttrs.concat(enroll_attrs);
            var result0 = '<h3 class="title">加入条件预览：</h3>';
            var result = '';
            $.each(attrs, function(index, attr) {
                result += '<input type="text" value="'+ attr.requirement +'" class="attr-input" readOnly="true" />';
            });
            result = '<div class="preview-mobileAttr-w">' + result + '</div>';
            preveiwMobile.html(result0 + result);
        }

        return {
            set: function(enroll_attrs_array) {
                if (enroll_attrs_array) {
                    if ($.type(enroll_attrs_array) == 'array' && enroll_attrs_array.length) {
                        enroll_attrs = enroll_attrs_array;
                    }
                }
                if (!enroll_attrs_array.length) {
                	createEnrollAttr();
                }
                getAttr();
                //renderEnrollAttrs();
            },
            get: function() {
                return {
                    enroll_attrs: enroll_attrs
                }
            },
            init: function() {
				var conditions = [];
				var uiInfors = $('#enrollAttrs').find('.ui-attr-item');
				uiInfors.each(function(i) {
					var condition = {};
					var target = uiInfors.eq(i).find('.ui-infor-link');
					setEvent(uiInfors.eq(i));
					condition.requirement = target.val();
					condition.id = target.attr('data-id');
					conditions.push(condition);
				});
				// set joinType flag.
				joinType = getjoinType();
				this.set(conditions);
            },
            setJoinType: function(value) {
                joinType = value;
            },
			getData: function() {
				var result = {};
				result.join_type = joinType;
				result.requirements = this.get().enroll_attrs;
				return result;
			}
        }
    })();

	$('.radioSels').on('click', 'label', function(e) {
		var deferred = $.Deferred();
		deferred.done(function() {
			var joinType = getjoinType();
			if (joinType == 0) {
				$('.verify-con').hide();
			} else {
				$('.verify-con').show();
			}
			enrollAttrsManager.setJoinType(joinType);
		});

		setTimeout(function() {
			deferred.resolve();
		}, 100)
	})

	$('#sureBtn').click(function() {
		var conditions = enrollAttrsManager.getData();
		//console.log(conditions); return;
		conditions._token = $('input[name="_token"]').val();
		var dialog = DialogUi.loading('正在修改中');
		server.updateRequirement(conditions, function(resp) {
			dialog.close();
			if (resp.code == 0) {
				location.reload();
			} else {
				DialogUi.alert(resp.message || '修改加入条件失败');
			}
		})
	})


	var page = {
		initialize: function(){
			enrollAttrsManager.init();
		}
	}

	page.initialize();

})()