window.H = {};

$(function(){
	//引入库
	var Server = K.server,
		DialogUi = K.dialogUi;
	//tab dom操作
	$('.xxk').on('click', 'div', function() {
		var desTable = $(this).data("table");
		$('.' + desTable ).show().siblings().hide();
		$(this).addClass('on').siblings().removeClass('on');
	});
	//获取token
	_token = $('input[name="_token"]').val();

	/**
	 * [description] 根据id获取活动信息
	 * @return {[type]} [description]
	 */
	var parms = {
		"activity" : activity_id
	};
	Server.getActivity(parms,function(resp){
		if (resp.code == 0) {
			console.dir(resp.activity);
			Page.init(resp.activity);
			//type:1(不缴费)，type:2(AA制)，type:3(要缴费)
			if (resp.activity.enroll_fee_type == 3 && resp.activity.auditing == 1) {
				//1、审核且缴费
				$(".wsh").show();
				$(".ysh").show();
				$(".yjj").show();
				H.table_check.initialize();
				H.table_enforce.initialize();
				H.table_refuse.initialize();
				H.table_enforce_display = 1;
				H.table_refuse_display = 1;
				//console.log(H);
			}else if (resp.activity.enroll_fee_type != 3 && resp.activity.auditing == 1) {
				//2、审核但不缴费
				$(".wsh").show();
				// $(".ysh").show();
				$(".yjj").show();
				H.table_check.initialize();
				// H.table_enforce.initialize();
				H.table_refuse.initialize();
				// H.table_enforce_display = 1;
				H.table_refuse_display = 1;
			}else if(resp.activity.enroll_fee_type == 3 && resp.activity.auditing == 0){
				//3、不审核要缴费
				// $(".wsh").show();
				$(".ysh").show();
				// $(".yjj").show();
				// H.table_check.initialize();
				H.table_enforce.initialize();
				// H.table_refuse.initialize();
				H.table_enforce_display = 0;
				// H.table_refuse_display = 1;
			}else if (resp.activity.enroll_fee_type != 3 && resp.activity.auditing == 0) {
				//4、不审核且不缴费
				// $(".wsh").show();
				// $(".ysh").show();
				// $(".yjj").show();
				// H.table_check.initialize();
				// H.table_enforce.initialize();
				// H.table_refuse.initialize();
				// H.table_enforce_display = 1;
				// H.table_refuse_display = 1;
			}else {
				throw "报名审核出错(activityCheck.js)";
				return;
			}
		};
	});

	/*[2015-10-21]*/
	var Page = {
		init: function(activityDate){
			var data = activityDate || {};
			this.renderInputEntry(data);
			this.initSubmit(data);
			this.initImportExcel(data);
		},
		renderInputEntry: function(data){
			//渲染表单
			var attrs = data.enroll_attrs || {},
				$form = $('#dbm-form'),
				arr = [],
				_this = this;
			if ( !attrs ) {
				console.error('可供填写的属性字段没有获取到。');
				$form.prepend('404');
				return;
			}
			$.each(attrs,function(index, el) {
				arr.push('<div class="ui-form-item">');
				if ( index < 2 ) {
					//手机和姓名必填
					arr.push('<label class="ui-label"><span class="ui-form-required">*</span>'+el+':</label>');
				}else {
					arr.push('<label class="ui-label">'+el+':</label>');
				}
				arr.push('<input type="text" name="'+el+'" class="form-control">');
				arr.push('</div>');
			});
			$form.prepend(arr.join(''));

			$('.team-opration-entry')
				.on('click', '#dbm', function(event) {
					if ( $(this).hasClass('out') ) {
						$(this).removeClass('out');
						$('.input-entry').slideUp('fast');
						_this.uploaderDestroy();
					}else {
						$(this).addClass('out');
						$('.input-entry').slideDown('fast');
						//创建uploader
						if ( !_this.uploader ) {
							_this.uploader = _this.setUpload({
								activity:data.id,
								_token : _token
							});
						}
					}
				})
				.on('click', '#close', function(event) {
					$('#dbm').removeClass('out');
					$('.input-entry').slideUp('fast');
					_this.uploaderDestroy();
				});
		},
		initSubmit: function(data){
			var id = data.id,
				model = [],
				params = {},
				$submit = $('#dbm-form-submit'),
				fnEnd = false;
			$submit.on('click', function(event) {
				$input = $('#dbm-form').find('input.form-control');
				$input.each(function(index) {
					var $siblingsLabel = $(this).siblings('label'),
						value = $(this).val();
					if ( $siblingsLabel.has('.ui-form-required').length && !value ) {
						DialogUi.alert("请填写"+$(this).attr('name'));
						fnEnd = true;
						return false;
					} else {
						fnEnd = false;
					}
					model.push({"key":$(this).attr('name'),"value":$(this).val()});
				});
				//如果未满足条件则退出函数
				if ( fnEnd ) return false;

				params = {
					activity : id,
					attrs : JSON.stringify(model),
					_token : _token
				}
				Server.addSingleVip(params,function(resp){
					console.dir(resp);
					if ( resp.code == 0 ) {
						DialogUi.alert(resp.message);
						//清空表单
						$input.val('');
						//切换到已报名表格
						$('.ybm').trigger('click');
						//刷新表格
						H.table_approve.tableRefresh();
					} else {
						DialogUi.alert(resp.message);
					}
				});	
			});
			
		},
		initImportExcel: function(data){
			var aid = data.id,
				$excel = $('#excel'),
				_this = this,
				params = {
					activity: aid,
					_token: _token
				};
			var uploader = _this.setUpload(params);
			$('#batch-import-btn').on('click', function(event) {
				if ( !$('.upload-message').text() ){
					DialogUi.alert('请先选择文件。');
					return false;
				}
				_this.uploader.upload();
			});
		},
		setUpload: function(params){
			var _this = this;
			var uploader = WebUploader.create({
	            formData: params,
	            fileVal: 'member_list',
	            auto: false,
	            swf: '/static/plugins/webuploader/Uploader.swf',
	            server: '/community/activity/import/members',
	            pick: {
	            	id: '#excel',
	            	multiple: false
	            },
	            method: 'post',
	            accept: {
	            	extensions: 'xls,xlsx'
	            }
        	});

			uploader.on('beforeFileQueued', function( file ) {
				if ( $('#batch-import-btn').has('img').length ){
					DialogUi.alert('有文件正在上传，请上传完成后，再重选选择文件。')
					return false;
				}
				console.log(file);
			});

			uploader.on( 'fileQueued', function( file ) {
				console.log(file);
				$('.upload-message').text(file.name);
			});

			uploader.on('startUpload', function(){
				var $uploadBtn = $('#batch-import-btn');
				_this.utils.loadingBtn($uploadBtn).load();
			});

	        uploader.on('uploadSuccess', function(file, resp) {
	        	console.log(resp);

	        	var $uploadBtn = $('#batch-import-btn');
				_this.utils.loadingBtn($uploadBtn).unload();
				$('.upload-message').text('');

	            if (resp.code === 0) {
	                if ( !resp.failed.length ) {
	                	DialogUi.alert('导入成功');
	                	 //刷新表格
	                	$('.ybm').trigger('click');
						H.table_approve.tableRefresh();
	                }else {
	                	var dom = [];
	                	dom.push('<h1 style="color: red;text-align:center;">部分导入信息错误</h1>')
	                	$.each(resp.failed,function(index, el) {
	                		var item = '<div>第' + el.row + "行：" + el.message + '</div>';
	                		dom.push(item);
	                	});
	                	DialogUi.alert(dom.join(''));
	                	 //刷新表格
	                	$('.ybm').trigger('click');
						H.table_approve.tableRefresh();
	                }
	            } else {
	            	DialogUi.alert(resp.message);
	            }
	        });

	        uploader.on('uploadComplete', function(file) {
	        	console.log(file);
	        });
	        uploader.on('uploadError', function(file) {
	        	console.log(file);
	        });
	        uploader.on('error', function(e) {
	        	if ( e == 'Q_TYPE_DENIED' ) {
	        		DialogUi.alert('只能导入excel文件。');
	        	}
	        });

	        return uploader;
		},
		uploaderDestroy: function() {
			if ( !this.uploader ) return;
			this.uploader.destroy();
			this.uploader = null;
			$('.upload-message').text('');
		},
		utils:{
			loadingBtn: function(el){
        		var text = el.attr('data-text'),
	            loadGif = $('<img class="load-button-img" src="/static/wap/images/loading-2.gif" style="height:15px;vertical-align:middle;" />');
		        return {
		            load: function() {
		                el.html(loadGif);
		                el.append(' 导入中')
		            },
		            unload: function() {
		                el.html(text);
		            }
		        };
			}
		}
	};
});
//待审核
(function(){
	var util = K.util,
		DialogUi = K.dialogUi,
		Server = K.server;

	var BlackList = (function() {
		var table = null;
		function renderTablePag() {
			return util.PagTable({
				el: 'tableCon1',
				columnNameList: [
					'index', 
					'name',
                    'mobile',
                    function(data){
                    	var arr = [];
                    	$.each(data.attrs,function(index, el) {
                    		var str = '<div class="member-attrs">'+el.key+'：'+el.value+'</div>';
                    		arr.push(str);
                    	});
                    	//console.log(arr.join(''));
                    	return arr.join('');
                    },
					function(data){
						return   '<a href="javascript:;" id="pass" class="button button-pre"><i class="icon iconfont"></i>通过</a>&nbsp;&nbsp;'+'<a href="javascript:;" id="refuse" class="button button-pre"><i class="icon iconfont"></i>拒绝</a>';
					}
				],
				source: function(o, PagTable, option) {
					var parms = {};
					parms.activity_id = activity_id;
					parms.page = o.currentPage;
					parms.status = 1;
					Server.getApplicantsList(parms,function(resp) {
						if (resp.code == 0) {
							// if (resp.list.length) {
								PagTable({totalPage: resp.pages, datas: resp.list});
							// }
							//console.log(resp);
						} else {
							alert(resp.msg || '查询数据列表出错');
						}
					}); 
				},
				events: {
					"click #pass": "pass",
					"click #refuse": "refuse"
				},
				eventsHandler: {
					pass: function(e, row) {
						//console.log(row.data);
						var parms = {
							'activity_id': row.data.activity_id,
							'applicant_id': row.data.id,
							'_token': _token
						};
						Server.approveApplicant(parms,function(resp) {
							//console.log(resp);
							if (resp.code == 0) {
								row.trigger('destroy');
								//console.log(H);
								if (H.table_enforce_display) {
									H.table_enforce.tableRefresh();
								}else if(H.table_approve_display) {
									H.table_approve.tableRefresh();
								}
							}
						});
						
					},
					refuse: function(e, row) {
						//console.log(row.data);
						var parms = {
							'activity_id': row.data.activity_id,
							'applicant_id': row.data.id,
							'_token':_token
						};
						Server.refuseApplicant(parms,function(resp) {
							//console.log(resp);
							if (resp.code == 0) {
								row.trigger('destroy');
								//刷新已拒绝表格
								if (H.table_refuse_display) {
									H.table_refuse.tableRefresh();
								}
							}else {
								DialogUi.alert(resp.message,function() {
									window.location.reload();
								});
							}
						});
					}
				}
			})
		}

		function _render() {
			table = renderTablePag();
		}
		function _refresh(){
			table.refresh();
		}
		return {
			render: _render,
			refresh: _refresh
		}
	})()

	var page = {
		initialize: function(){
			BlackList.render();
		},
		tableRefresh: function(){
			BlackList.refresh();
		}
	}

	// page.initialize();

	H.table_check = page;
	H.table_check_display = 0;
	//console.log(H);
})();

//待缴费
(function(){
	var util = K.util,
		DialogUi = K.dialogUi,
		Server = K.server;

	var BlackList = (function() {
		var table = null;
		function renderTablePag() {
			return util.PagTable({
				el: 'tableCon2',
				columnNameList: [
					'index', 
					'name',
                    'mobile',
                    function(data){
                    	var arr = [];
                    	$.each(data.attrs,function(index, el) {
                    		var str = '<div class="member-attrs">'+el.key+'：'+el.value+'</div>';
                    		arr.push(str);
                    	});
                    	//console.log(arr.join(''));
                    	return arr.join('');
                    },
                    
                    // 'infro',
					function(data){
						if ( data.expire_at ) {
							return '缴费中';
						}
						return '未缴费';
					},
					function(data){
						// if ( data.noticed || data.expire_at ) {
						// 	return '<a href="javascript:;" class="button button-pre"><i class="icon iconfont"></i>已通知</a>';
						// };
						// return   '<a href="javascript:;" id="payNotice" class="button button-pre"><i class="icon iconfont"></i>通知收费</a>';
						return '无';
					}
				],
				source: function(o, PagTable, option) {
					var parms = {};
					parms.activity_id = activity_id;
					parms.page = o.currentPage;
					parms.status = 2;
					Server.getApplicantsList(parms,function(resp) {
						if (resp.code == 0) {
							// if (resp.list.length) {
								PagTable({totalPage: resp.pages, datas: resp.list});
							// }
							//console.log(resp);
						} else {
							alert(resp.msg || '查询数据列表出错');
						}
					}); 
				},
				events: {
					"click #payNotice": "payNotice"
				},
				eventsHandler: {
					payNotice: function(e, row) {
						
						//console.log(row);
						var parms = {
							'activity_id': row.data.activity_id,
							'applicant_ids': [row.data.id],
							'_token':_token
						};
						//console.log(parms);
						Server.enforcePayment(parms,function(resp) {
							//console.log(resp);
							if ( resp.code == 0 ) {
								//console.log($(e.target).text());
								row.data.noticed = true;
								// window.location.reload();
								// H.table_enforce.tableRefresh();
								row.refresh();
							}
						});
					}
				}
			})
		}

		function _render() {
			table = renderTablePag();
		}
		function _refresh(){
			table.refresh();
		}
		return {
			render: _render,
			refresh: _refresh
		}
	})()

    var page = {
		initialize: function(){
			BlackList.render();
		},
		tableRefresh: function(){
			BlackList.refresh();
		}
	}

	//这里先不初始化，要根据活动是否缴费来初始化
	// page.initialize();

	H.table_enforce = page;
	H.table_enforce_display = 0;

})();

//已报名
(function(){
	var util = K.util,
		DialogUi = K.dialogUi,
		Server = K.server;

	var BlackList = (function() {
		var table = null;
		function renderTablePag() {
			return util.PagTable({
				el: 'tableCon3',
				columnNameList: [
					'index', 
					'name',
                    'mobile',
                    function(data){
                    	var arr = [];
                    	$.each(data.attrs,function(index, el) {
                    		var str = '<div class="member-attrs">'+el.key+'：'+el.value+'</div>';
                    		arr.push(str);
                    	});
                    	////console.log(arr.join(''));
                    	return arr.join('');
                    },
                    /*
                    'infro',
					function(data){
						return '<div class="infor-wrap"><input type="text" id="infor" class="form-control" value="'+ data.infro +'" /></div>';
					},*/
					function(data){
						return   '无';
					}
				],
				source: function(o, PagTable, option) {
					var parms = {};
					parms.activity_id = activity_id;
					parms.page = o.currentPage;
					parms.status = 3;
					//server.acts(parms, function(resp){
					Server.getApplicantsList(parms,function(resp) {
						if (resp.code == 0) {
							// if (resp.list.length) {
								PagTable({totalPage: resp.pages, datas: resp.list});
							// }
							////console.log(resp);
						} else {
							alert(resp.msg || '查询数据列表出错');
						}
					}); 
				},
				events: {
					"click #recovery": "recovery",
					"focus #infor": "inforFocus",
					"blur #infor": "inforBlur"
				},
				eventsHandler: {
					recovery: function(e, row) {row.setData();row.refresh();
						var curDialog = DialogUi.msg('正在恢复中...');
						setTimeout(function(){
							curDialog.close();
						}, 1000);
					},
					inforFocus: function(e, row) {
						var target = $(e.target);
						target.addClass('form-control-long');
					},
					inforBlur: function(e, row) {
						var target = $(e.target);
						target.removeClass('form-control-long');
					}
				}
			})
		}

		function _render() {
			table = renderTablePag();
		}
		function _refresh(){
			table.refresh();
		}
		return {
			render: _render,
			refresh: _refresh
		}
	})()

    var page = {
		initialize: function(){
			BlackList.render();
		},
		tableRefresh: function(){
			BlackList.refresh();
		}
	}

	page.initialize();

	H.table_approve = page;
	H.table_approve_display = 1;
	////console.log(H);
})();

//已拒绝
(function(){
	var util = K.util,
		DialogUi = K.dialogUi,
		Server = K.server;

	var BlackList = (function() {
		var table=null;
		function renderTablePag() {
			return util.PagTable({
				el: 'tableCon4',
				columnNameList: [
					'index', 
					'name',
                    'mobile',
                   	function(data){
                    	var arr = [];
                    	$.each(data.attrs,function(index, el) {
                    		var str = '<div class="member-attrs">'+el.key+'：'+el.value+'</div>';
                    		arr.push(str);
                    	});
                    	////console.log(arr.join(''));
                    	return arr.join('');
                    },
                    
                    /*
                    'infro',
					function(data){
						return '<div class="infor-wrap"><input type="text" id="infor" class="form-control" value="'+ data.infro +'" /></div>';
					},*/
					function(data){
					return   '无';	
					}
				],
				source: function(o, PagTable, option) {
					var parms = {};
					parms.activity_id = activity_id;
					parms.page = o.currentPage;
					parms.status = -1;
					//server.acts(parms, function(resp){
					Server.getApplicantsList(parms,function(resp) {
						if (resp.code == 0) {
							// if (resp.list.length) {
								PagTable({totalPage: resp.pages, datas: resp.list});
							// }
							////console.log(resp);
						} else {
							alert(resp.msg || '查询数据列表出错');
						}
					}); 
				},
				events: {
					"click #recovery": "recovery",
					"focus #infor": "inforFocus",
					"blur #infor": "inforBlur"
				},
				eventsHandler: {
					recovery: function(e, row) {row.setData();row.refresh();
						var curDialog = DialogUi.msg('正在恢复中...');
						setTimeout(function(){
							curDialog.close();
						}, 1000);
					},
					inforFocus: function(e, row) {
						var target = $(e.target);
						target.addClass('form-control-long');
					},
					inforBlur: function(e, row) {
						var target = $(e.target);
						target.removeClass('form-control-long');
					}
				}
			})
		}

		function _render() {
			table = renderTablePag();
		}
		function _refresh(){
			table.refresh();
		}
		return {
			render: _render,
			refresh: _refresh
		}
	})()

    var page = {
		initialize: function(){
			BlackList.render();
		},
		tableRefresh: function(){
			BlackList.refresh();
		}
	}

	// page.initialize();

	H.table_refuse = page;
	H.table_refuse_display = 0;	
	////console.log(H);
})()